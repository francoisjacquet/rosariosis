<?php
$_REQUEST['modname'] = "School_Setup/Calendar.php"; 

$modcat = 'School_Setup';
if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT>modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>
