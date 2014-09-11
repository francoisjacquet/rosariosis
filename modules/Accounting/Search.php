<?php
$_REQUEST["modname"] = "Accounting/Incomes.php";

if(User('PROFILE')=='teacher')
	$_REQUEST["modname"] = "Accounting/Salaries.php";

if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT>modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>
