<?php
$_REQUEST["modname"] = "Resources/Resources.php";

if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT>modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>
