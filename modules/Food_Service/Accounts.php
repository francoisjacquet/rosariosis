<?php
require_once('modules/Food_Service/includes/DeletePromptX.fnc.php');

if($_REQUEST['type'])
	$_SESSION['FSA_type'] = $_REQUEST['type'];
else
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];

$header = '<TABLE class="cellpadding-0 cellspacing-0" style="height:14px;"><TR>';
//modif Francois: add translation
//modif Francois: remove DrawTab params
$header .= '<TD style="width:10px;"></TD><TD>'.DrawTab(_('Students'),"Modules.php?modname=$_REQUEST[modname]&type=student").'</TD>';
$header .= '<TD style="width:10px;"></TD><TD>'.DrawTab(_('Users'),   "Modules.php?modname=$_REQUEST[modname]&type=staff").'</TD>';
$header .= '<TD style="width:10px;"></TD></TR></TABLE>';

DrawHeader(($_REQUEST['type']=='staff'?_('User'):_('Student')).' &minus; '.ProgramTitle(),(User('PROFILE')=='student'?'':'<TABLE><TR><TD>'.$header.'</TD></TR></TABLE>'));

include('modules/Food_Service/'.($_REQUEST['type']=='staff'?'Users':'Students').'/Accounts.php');

function red($value)
{
	if($value<0)
		return '<span style="color:red">'.$value.'</span>';
	else
		return $value;
}
?>