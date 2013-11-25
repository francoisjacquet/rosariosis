<?php
if(User('PROFILE')=='teacher')
	$_REQUEST['modname'] = 'Eligibility/EnterEligibility.php';
else
	$_REQUEST['modname'] = 'Eligibility/Student.php';

$modcat = 'Eligibility';
if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>