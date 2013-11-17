<?php
if(User('PROFILE')=='teacher')
	$_REQUEST['modname'] = 'Grades/Grades.php';
elseif(User('PROFILE')=='parent' || User('PROFILE')=='student')
	$_REQUEST['modname'] = 'Grades/StudentGrades.php';
else
	$_REQUEST['modname'] = 'Grades/GPARankList.php';

$modcat = 'Grades';
if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; $(\'#menu a[href$="'.$_REQUEST['modname'].'"]:first\').each(function(){selMenuA(this);});</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>