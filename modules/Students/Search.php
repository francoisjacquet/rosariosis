<?php
//if($_SESSION['student_id'] && (User('PROFILE')=='admin' || User('PROFILE')=='teacher'))
//{
//	unset($_SESSION['student_id']);
//	echo '<script type="text/javascript">parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
//}

$modcat = 'Students';
$_REQUEST['modname'] = '';
if(AllowUse('Students/Student.php'))
{
	$_REQUEST['modname'] = $_REQUEST['next_modname'] = 'Students/Student.php';
	if(User('PROFILE')=='parent' || User('PROFILE')=='student')
		$_REQUEST['search_modfunc'] = 'list';
}
if($_REQUEST['modname'])
{
	//echo '<SCRIPT type="text/javascript">parent.help.location="Bottom.php?modcat='.$modcat.'&modname='.$_REQUEST['modname'].'";</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>