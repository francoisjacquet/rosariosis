<?php
//if($_SESSION['staff_id'] && User('PROFILE')=='admin')
//{
//	unset($_SESSION['staff_id']);
//	echo '<script type="text/javascript">parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
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
	echo '<SCRIPT type="text/javascript">parent.help.location="Bottom.php?modcat='.$modcat.'&modname='.$_REQUEST['modname'].'";</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>