<?php
$_REQUEST["modname"] = "Resources/Redirect.php";
$_REQUEST['to'] = "forums";

if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; $(\'#menu a[href$="'.$_REQUEST['modname'].'"]:first\').each(function(){selMenuA(this);});</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>
