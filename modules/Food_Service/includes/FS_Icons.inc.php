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
 * @since 6.0 Add TipMessage to Food Service Icon.
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
		$THIS_RET;

	if ( $value )
	{
		$return = '<img src="' . URLEscape( $FS_IconsPath . $value ) . '" width="' . AttrEscape( $width ) . '" />';

		if ( $THIS_RET )
		{
			// Call from DBGet() for ListOutput.
			// Add TipMessage.
			require_once 'ProgramFunctions/TipMessage.fnc.php';

			return MakeTipMessage(
				'<img src="' . URLEscape( $FS_IconsPath . $value ) . '" width="128" />',
				_( 'Icon' ),
				$return
			);
		}

		return $return;
	}

	return '&nbsp;';
}
