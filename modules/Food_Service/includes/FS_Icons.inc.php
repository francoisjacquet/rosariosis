<?php
// Food Service Icons Path
// You can override the Path definition in the config.inc.php file

if ( ! isset( $FS_IconsPath ) )
{
	$FS_IconsPath = 'assets/FS_icons/';
}
// Food Service icons

//FJ Food Service icons functions

//used in MenuItems.php, ServeMenus.php & Kiosk.php
/**
 * @param $value
 * @param $name
 * @param $width
 */
function makeIcon( $value, $name, $width = '36' )
{
	global $FS_IconsPath;

	if ( $value )
	{
		return '<img src="' . $FS_IconsPath . $value . '" width="' . $width . '" />';
	}
	else
	{
		return '&nbsp;';
	}
}
