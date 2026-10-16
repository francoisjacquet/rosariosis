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

	if ( $addon_title === 'School_Setup' )
	{
		$addon_title_echo = _( 'School' );
	}

	if ( ! in_array( $addon_title, $RosarioCorePlugins )
		&& ! in_array( $addon_title, $RosarioCoreModules )
		&& $activated )
	{
		$addon_title_echo = dgettext( $addon_title, str_replace( '_', ' ', $addon_title ) );
	}

	$readme_path = $type . 's/' . $addon_title . '/README.md';

	$return = $addon_title_echo;

	// if README.md file, display in Colorbox

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& file_exists( $readme_path ) )
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
 * @since 12.2 Send RosarioSIS version
 *
 * @param string $type      Add-on type: module|plugin.
 * @param string $addon_dir Add-on directory. For example: 'My_Module'.
 *
 * @return bool False if usage statistics are disabled or no curl, true otherwise.
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

	if ( $_REQUEST['modfunc'] === 'installation_statistics_post' )
	{
		if ( empty( $_SESSION['AddonInstallationStatisticsPost'] )
			|| $_SESSION['AddonInstallationStatisticsPost'] !== $addon_dir )
		{
			// Prevent hacking.
			return false;
		}

		unset( $_SESSION['AddonInstallationStatisticsPost'] );

		$ip = ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )
			// Filter IP, HTTP_* headers can be forged.
			&& filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ?
			$_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );

		$data = [
			'type' => $type,
			'addon_dir' => $addon_dir,
			'lang' => mb_substr( $_SESSION['locale'], 0, 5 ),
			'rosario_version' => ROSARIO_VERSION,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'ip' => $ip,
		];

		// @since 12.5 CPS remove unsafe-inline Javascript
		try
		{
			$curl = new curl;

			$curl->post(
				'https://www.rosariosis.org/addon-statistics/installation-submit.php',
				$data
			);
		}
		catch ( Exception $e )
		{
			// No curl installed, fail.
			return false;
		}

		return true;
	}

	// Prevent hacking.
	$_SESSION['AddonInstallationStatisticsPost'] = $addon_dir;

	$url = 'Modules.php?modname=School_Setup/Configuration.php&tab=' . $_REQUEST['tab'] .
		'&modfunc=installation_statistics_post&addon_dir=' . $addon_dir;

	// @since 12.5 CPS remove unsafe-inline Javascript
	?>
	<input type="hidden" disabled id="ajax_url" value="<?php echo URLEscape( $url ); ?>" />
	<script src="assets/js/csp/modules/AjaxUrl.js?v=12.5"></script>
	<?php

	return true;
}

/**
 * Upsell add-on to Premium
 * Add a "PREMIUM" button which opens a colorBox with the PREMIUM.md file's content
 *
 * Note: place translations of the PREMIUM.md file inside locale/[code]/
 *
 * @since 12.1
 *
 * @param string $type      Add-on type: module|plugin.
 * @param string $addon_dir Add-on directory. For example: 'My_Module'.
 *
 * @return string Empty if no PREMIUM.md file found.
 */
function AddonUpsellPremium( $type, $addon_dir, $md_filename = 'PREMIUM.md' )
{
	if ( ! in_array( $type, [ 'module', 'plugin' ] )
		|| ! $addon_dir
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return '';
	}

	$md_filename = basename( $md_filename );

	$premium_md_paths = [
		$type . 's/' . $addon_dir . '/locale/' . $_SESSION['locale'] . '/' . $md_filename,
		$type . 's/' . $addon_dir . '/' . $md_filename,
	];

	if ( file_exists( $premium_md_paths[0] ) )
	{
		$premium_md_path = $premium_md_paths[0];
	}
	elseif ( file_exists( $premium_md_paths[1] ) )
	{
		$premium_md_path = $premium_md_paths[1];
	}
	else
	{
		return '';
	}

	// Get PREMIUM.md content
	$premium_md_content = file_get_contents( $premium_md_path );

	if ( strpos( $_SERVER['HTTP_HOST'], '.rosariosis.com' ) !== false ) // TODO add explanation on login page! "The administrator at your school who signed up to rosariosis.com can [login here](). Please contact this person or use the Contact form linked above if you need help."
	{
		if ( User( 'PROFILE' ) !== 'admin' )
		{
			// Only admins should see this Button on rosariosis.com.
			return '';
		}

		// If on rosariosis.com, replace .org link with .com link
		$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

		$locale_short = $lang_2_chars === 'fr' || $lang_2_chars === 'es' ?
			$lang_2_chars . '/' : '';

		$com_link = '(https://www.rosariosis.com/' . $locale_short . 'switch-to-premium/)';

		$addon_slug = mb_strtolower( str_replace( '_', '-', $addon_dir ) );

		$org_links = [
			'$\(https://www\.rosariosis\.org/' . $type . 's/' . $addon_slug . '.*\)$',
			'$\(https://www\.rosariosis\.org/' . $lang_2_chars . '/' . $type . 's/' . $addon_slug . '.*\)$',
		];

		$premium_md_content = preg_replace( $org_links, $com_link, $premium_md_content );
	}

	// Convert MarkDown text to HTML.
	$premium_content = '<div class="markdown-to-html">' . $premium_md_content . '</div>';

	$return = '<div style="display:none;"><div class="addon-upsell-premium" id="PREMIUM_' . $addon_dir . '">' .
		$premium_content . '</div></div>';

	$return .= '<a class="colorboxinline button-upsell-premium" href="#PREMIUM_' . $addon_dir . '">Premium</a>';

	return $return;
}

/**
 * Check if update available for add-on
 *
 * @since 13.0
 *
 * @uses RosarioSIS\Functions\AddonInfo class
 *
 * @param string $type      Add-on type: module|plugin.
 * @param string $addon_dir Add-on directory. For example: 'My_Module'.
 *
 * @return string Empty if no version found, ❌ if no update, ✅ + remote / new versions + dates otherwise.
 */
function AddonMakeUpdateAvailable( $type, $addon_dir )
{
	$addon_info = new RosarioSIS\Functions\AddonInfo( $type, $addon_dir );

	$version = $addon_info->get( [ 'version' ] );

	if ( ! $version )
	{
		return '';
	}

	$version_remote = $addon_info->remoteGet( [ 'version' ] );

	if ( ! $version_remote )
	{
		return '';
	}

	if ( version_compare( $version, $version_remote, '<' ) )
	{
		if ( ! empty( $_REQUEST['LO_save'] ) )
		{
			return _( 'Yes' );
		}

		$date = $addon_info->get( [ 'time' ] );

		$date_remote = $addon_info->remoteGet( [ 'time' ] );

		require_once 'ProgramFunctions/TipMessage.fnc.php';

		$msg = '<table><tr><td>' . NoInput( $version, ProperDate( $date, 'short' ) ) .
			'</td><td> → </td><td>' .
			NoInput( $version_remote, ProperDate( $date_remote, 'short' ) ) . '</td></tr></table>';

		return MakeTipMessage( $msg, _( 'Update available' ), button( 'check' ) );
	}

	return ! empty( $_REQUEST['LO_save'] ) ? _( 'No' ) : button( 'x' );
}
