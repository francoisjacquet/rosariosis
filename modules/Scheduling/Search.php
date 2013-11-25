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
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>