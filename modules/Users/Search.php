<?php
//if($_SESSION['staff_id'] && User('PROFILE')=='admin')
//{
//	unset($_SESSION['staff_id']);
//	echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'?modname="+document.getElementById("modname_input").value; menu_link.target = "menu"; ajaxLink(menu_link);</script>';
//}

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
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>