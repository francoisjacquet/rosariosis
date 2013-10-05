<?php
$_REQUEST['modname'] = "School_Setup/Calendar.php"; 
//$js_extra = "window.location.href = window.location.href.replace('Search.php','Calendar.php');";

$modcat = 'School_Setup';
if(AllowUse($_REQUEST['modname']))
{
	//echo '<SCRIPT type="text/javascript">'.$js_extra.'parent.help.location="Bottom.php?modcat='.$modcat.'&modname='.$_REQUEST['modname'].'";</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>
