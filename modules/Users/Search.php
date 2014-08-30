<?php
$modcat = 'Users';
$_REQUEST['modname'] = '';
if(AllowUse('Users/User.php'))
{
	$_REQUEST['modname'] = $_REQUEST['next_modname'] = 'Users/User.php';
}
elseif(AllowUse('Users/Preferences.php'))
{
	$_REQUEST['modname'] = 'Users/Preferences.php';
}
if($_REQUEST['modname'])
{
	echo '<SCRIPT>modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>