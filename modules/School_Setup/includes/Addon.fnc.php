<?php
/**
 * Add-on functions
 * Used by Modules.inc.php & Plugins.inc.php
 *
 * @package RosarioSIS
 */

/**
 * Make add-on Title (translate) + display README or README.md inside ColorBox
 *
 * @param string $type        Add-on Type: 'module' or 'plugin'.
 * @param string $addon_title Add-on Title.
 * @param string $activated   Is add-on activated (empty string or 'Y').
 *
 * @return string Add-on README HTML.
 */
function AddonMakeReadMe( $type, $addon_title, $activated = '' )
{
	global $RosarioCorePlugins,
		$RosarioCoreModules;

	if ( ! in_array( $type, [ 'module', 'plugin' ] )
		|| ! $addon_title )
	{
		return '';
	}

	// Format & translate plugin title
	$addon_title_echo = _( str_replace( '_', ' ', $addon_title ) );

	if ( ! in_array( $addon_title, $RosarioCorePlugins )
		&& ! in_array( $addon_title, $RosarioCoreModules )
		&& $activated )
	{
		$addon_title_echo = dgettext( $addon_title, str_replace( '_', ' ', $addon_title ) );
	}

	$readme_path = 'modules/' . $addon_title . '/README';

	if ( $type === 'plugin' )
	{
		$readme_path = 'plugins/' . $addon_title . '/README';
	}

	$return = $addon_title_echo;

	// if README.md file, display in Colorbox

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ( file_exists( $readme_path )
			|| (  ( $readme_path = $readme_path . '.md' )
				&& file_exists( $readme_path ) ) ) )
	{
		//get README.md content
		$readme_content = file_get_contents( $readme_path );

		// Convert MarkDown text to HTML.
		$readme_content = '<div class="markdown-to-html">' . $readme_content . '</div>';

		$return = '<div style="display:none;"><div class="addon-readme" id="README_' . $addon_title . '">' .
			$readme_content . '</div></div>';

		$return .= '<a class="colorboxinline" href="#README_' . $addon_title . '">' .
			$addon_title_echo . '</a>';
	}

	return $return;
}

/**
 * Delete Tree
 * Recursively delete a directory and its files.
 *
 * If one of the files cannot be deleted,
 * no files are deleted & `false` is returned.
 * Dry run is always performed first.
 *
 * @param  string  $dir  Directory to delete.
 * @param  string  $mode delete|dryrun Mode (optional). Defaults to 'delete'.
 * @return boolean true on success, else false.
 */
function AddonDelTree( $dir, $mode = 'delete' )
{
	if ( ! is_dir( $dir ) )
	{
		return true;
	}

	if ( $mode === 'delete' )
	{
		// Run dry run mode first.
		$can_delete = AddonDelTree( $dir, 'dryrun' );

		if ( ! $can_delete )
		{
			return false;
		}
	}

	$return = true;

	$files = array_diff( scandir( $dir ), [ '.', '..' ] );

	foreach ( (array) $files as $file )
	{
		if ( is_dir( $dir . '/' . $file ) )
		{
			$return = AddonDelTree( $dir . '/' . $file, $mode );
		}
		elseif ( is_writable( $dir . '/' . $file ) )
		{
			if ( $mode !== 'dryrun' )
			{
				unlink( $dir . '/' . $file );
			}
		}
		else
		{
			return false;
		}
	}

	return $mode === 'dryrun' ? $return && is_writable( $dir ) : rmdir( $dir );
}


/**
 * Make Add-on Activated column
 *
 * @param bool $activated Activated or not.
 *
 * @return string Activated column
 */
function AddonMakeActivated( $activated )
{
	if ( isset( $_REQUEST['LO_save'] ) )
	{
		if ( $activated )
		{
			return _( 'Yes' );
		}

		return _( 'No' );
	}

	if ( $activated )
	{
		return button( 'check' );
	}

	return button( 'x' );
}

/**
 * Unzip add-on
 *
 * @since 12.0
 *
 * @global $error "No [Modules|Plugins] were found."
 *
 * @uses AddonZipCanUnzip()
 *
 * @param string $type            Type: 'module' or 'plugin'.
 * @param string $zip_path        Path to zip file.
 * @param string $extract_to_path Path to were the zip files will be extracted.
 *
 * @return string Add-on dir path or empty if error.
 */
function AddonUnzip( $type, $zip_path, $extract_to_path )
{
	global $error;

	if ( ! in_array( $type, [ 'module', 'plugin' ] ) )
	{
		return '';
	}

	if ( ! AddonZipCanUnzip( $zip_path ) )
	{
		return '';
	}

	// Extract zip file.
	$zip = new ZipArchive;
	$zip_open = $zip->open( $zip_path );

	$zip->extractTo( $extract_to_path );

	$zip->close();

	$addon_dir_paths = glob( $extract_to_path . '*', GLOB_ONLYDIR );

	$addon_dir_path = '';

	foreach ( (array) $addon_dir_paths as $maybe_addon_dir_path )
	{
		if ( basename( $maybe_addon_dir_path ) !== '__MACOSX' )
		{
			$addon_dir_path = $maybe_addon_dir_path;

			break;
		}
	}

	if ( ! $addon_dir_path )
	{
		$error[] = sprintf(
			_( 'No %s were found.' ),
			( $type === 'module' ? _( 'Modules' ) : _( 'Plugins' ) )
		);
	}

	return $addon_dir_path;
}

/**
 * Check if can unzip add-on zip file and if enough free disk space
 * Send error by email to administrator if not enough free disk space
 *
 * @since 12.0
 *
 * @global $error "Could not copy files. You may have run out of disk space."
 *
 * @uses ErrorSendEmail()
 *
 * @link https://github.com/danielmiessler/SecLists/tree/master/Payloads/Zip-Bombs
 *
 * @param string $zip_path Zip file path.
 *
 * @return bool False if not safe, else true.
 */
function AddonZipCanUnzip( $zip_path )
{
	global $error;

	$zip = new ZipArchive;
	$zip_open = $zip->open( $zip_path );

	if ( $zip_open !== true )
	{
		$error[] = _( 'Cannot open file.' );

		return false;
	}

	$uncompressed_size = 0;

	for ( $i = 0; $i < $zip->numFiles; $i++ )
	{
		$file_info = $zip->statIndex( $i );

		if ( ! $file_info )
		{
			$error[] = _( 'Cannot open file.' );

			return false;
		}

		$uncompressed_size += $file_info['size'];
	}

	$free_space = @disk_free_space( dirname( $zip_path ) );

	if ( $free_space
		&& ( $uncompressed_size * 2.1 ) > $free_space )
	{
		// Not enough free space on disk!
		$free_space_error = _( 'Could not copy files. You may have run out of disk space.' );

		$error[] = $free_space_error;

		ErrorSendEmail( [
			'Uncompressed size: ' . HumanFilesize( $uncompressed_size ),
			'Free space: ' . HumanFilesize( $free_space ),
		], $free_space_error );

		return false;
	}

	return true;
}

/**
 * Post add-on installation (first activation) statistics
 *
 * @see `ROSARIO_DISABLE_USAGE_STATISTICS` optional configuration constant
 *
 * @since 12.0
 *
 * @param string $type      Add-on type: module|plugin.
 * @param string $addon_dir Add-on directory. For example: 'My_Module'.
 *
 * @return bool False if usage statistics are disabled, true otherwise.
 */
function AddonInstallationStatisticsPost( $type, $addon_dir )
{
	if ( ! in_array( $type, [ 'module', 'plugin' ] )
		|| ! $addon_dir )
	{
		return false;
	}

	if ( defined( 'ROSARIO_DISABLE_USAGE_STATISTICS' )
		&& ROSARIO_DISABLE_USAGE_STATISTICS )
	{
		return false;
	}

	$data = [
		'type' => $type,
		'addon_dir' => $addon_dir,
		'lang' => mb_substr( $_SESSION['locale'], 0, 5 ),
	];

	?>
	<script>
		$(document).ready(function(){
			var addonInstallationData = <?php echo json_encode( $data ); ?>;

			$.ajax({
				type: 'POST',
				url: 'https://www.rosariosis.org/addon-statistics/installation-submit.php',
				data: addonInstallationData,
				complete: function(jqxhr,status) {
					console.log('status: ' + status);
					console.log('response: ' + jqxhr.responseText);
				}
			});
		});
	</script>
	<?php

	return true;
}
