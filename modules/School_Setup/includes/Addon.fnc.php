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
	$return = true;

	if ( $mode === 'delete' )
	{
		// Run dry run mode first.
		$can_delete = AddonDelTree( $dir, 'dryrun' );

		if ( ! $can_delete )
		{
			return false;
		}
	}

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
