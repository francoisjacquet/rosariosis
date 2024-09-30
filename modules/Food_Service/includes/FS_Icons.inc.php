<?php
/**
 * Food Service icons functions
 */
// Food Service Icons upload path global.
if ( ! isset( $FS_IconsPath )
	&& is_dir( 'assets/FS_icons/' )
	// Check if dir has files.
	&& glob( 'assets/FS_icons/*' ) )
{
	// @deprecated since 12.0 Food Service Icons upload path $FS_IconsPath global var
	$FS_IconsPath = 'assets/FS_icons/';
}

/**
 * Food Service icons
 *
 * Used in MenuItems.php, ServeMenus.php & Kiosk.php
 *
 * @global $FS_IconsPath
 * @global $FileUploadsPath
 *
 * @since 6.0 Add TipMessage to Food Service Icon.
 * @since 12.0 Use $FileUploadsPath . 'FS_icons/' instead of $FS_IconsPath
 *
 * @param $value
 * @param $name
 * @param $width
 *
 * @return string HTML image with icon.
 */
function makeIcon( $value, $name, $width = '48' )
{
	global $FS_IconsPath,
		$FileUploadsPath,
		$THIS_RET;

	// @since 11.4 Use $FileUploadsPath . 'FS_icons/' instead of $FS_IconsPath
	$fs_icons_path = $FS_IconsPath ?
		$FS_IconsPath :
		$FileUploadsPath . 'FS_icons/';

	if ( $value )
	{
		$return = '<img src="' . URLEscape( $fs_icons_path . $value ) . '" width="' . AttrEscape( $width ) . '" />';

		if ( $THIS_RET )
		{
			// Call from DBGet() for ListOutput.
			// Add TipMessage.
			require_once 'ProgramFunctions/TipMessage.fnc.php';

			return MakeTipMessage(
				'<img src="' . URLEscape( $fs_icons_path . $value ) . '" width="128" />',
				_( 'Icon' ),
				$return
			);
		}

		return $return;
	}

	return '&nbsp;';
}
