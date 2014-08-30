<?php
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
	echo '<SCRIPT>modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}
?>