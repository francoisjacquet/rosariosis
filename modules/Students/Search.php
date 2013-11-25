<?php
//if($_SESSION['student_id'] && (User('PROFILE')=='admin' || User('PROFILE')=='teacher'))
//{
//	unset($_SESSION['student_id']);
//	echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'?modname="+document.getElementById("modname_input").value; menu_link.target = "menu"; ajaxLink(menu_link);</script>';
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
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>