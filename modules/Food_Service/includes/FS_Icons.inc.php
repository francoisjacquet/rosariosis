<?php
// Food Service Icons Path
// You can override the Path definition in the config.inc.php file
if (!isset($FS_IconsPath))
	$FS_IconsPath = 'assets/FS_icons/'; // Food Service icons

//FJ Food Service icons functions

//used in MenuItems.php, ServeMenus.php & Kiosk.php
function makeIcon($value,$name,$height='30')
{	global $FS_IconsPath;

	if ($value)
		return '<IMG src="'.$FS_IconsPath.$value.'" height="'.$height.'" />';
	else
		return '&nbsp;';
}
