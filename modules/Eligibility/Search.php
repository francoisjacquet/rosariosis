<?php
if(User('PROFILE')=='teacher')
	$_REQUEST['modname'] = 'Eligibility/EnterEligibility.php';
else
	$_REQUEST['modname'] = 'Eligibility/Student.php';

$modcat = 'Eligibility';
if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; $(\'#menu a[href$="'.$_REQUEST['modname'].'"]:first\').each(function(){selMenuA(this);});</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>