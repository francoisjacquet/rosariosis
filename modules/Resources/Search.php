<?php
$_REQUEST["modname"] = "Resources/Redirect.php";
$_REQUEST['to'] = "forums";

if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>
