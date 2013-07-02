<?php
if($_REQUEST['student_id']=='new')
{
	unset($_SESSION['student_id']);
	$_SESSION['unset_student'] = true;
}	
$_REQUEST['modname'] = 'Scheduling/Schedule.php';

$modcat = 'Scheduling';

if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">window.location.href=window.location.href.replace("Search.php","Schedule.php");parent.help.location="Bottom.php?modcat='.$modcat.'&modname='.$_REQUEST['modname'].'";</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>