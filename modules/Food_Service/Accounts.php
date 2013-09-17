<?php
require_once('modules/Food_Service/includes/DeletePromptX.fnc.php');

if($_REQUEST['type'])
	$_SESSION['FSA_type'] = $_REQUEST['type'];
else
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];

//modif Francois: add translation
//modif Francois: remove DrawTab params
$header = '<a href="Modules.php?modname='.$_REQUEST['modname'].'&type=student">'._('Students').'</a> - <a href="Modules.php?modname='.$_REQUEST['modname'].'&type=staff">'._('Users').'</a>';

DrawHeader(($_REQUEST['type']=='staff'?_('User'):_('Student')).' &minus; '.ProgramTitle());
User('PROFILE')=='student'?'':DrawHeader($header);

include('modules/Food_Service/'.($_REQUEST['type']=='staff'?'Users':'Students').'/Accounts.php');

function red($value)
{
	if($value<0)
		return '<span style="color:red">'.$value.'</span>';
	else
		return $value;
}
?>