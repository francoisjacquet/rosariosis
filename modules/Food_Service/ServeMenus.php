<?php
require_once('modules/Food_Service/includes/DeletePromptX.fnc.php');

if($_REQUEST['modfunc']=='select')
{
	$_SESSION['FSA_type'] = $_REQUEST['fsa_type'];
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['type'])
	$_SESSION['FSA_type'] = $_REQUEST['type'];
else
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];

/*if($_REQUEST['type']=='staff')
{
	$tabcolor_s = '#DFDFDF'; $textcolor_s = '#999999';
	$tabcolor_u = Preferences('HEADER'); $textcolor_u = '#FFFFFF';
}
else
{
	$tabcolor_s = Preferences('HEADER'); $textcolor_s = '#FFFFFF';
	$tabcolor_u = '#DFDFDF'; $textcolor_u = '#999999';
}*/
//modif Francois: remove DrawTab params
$header = '<a href="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=select&menu_id='.$_REQUEST['menu_id'].'&fsa_type=student">'._('Students').'</a>';
$header .= ' - <a href="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=select&menu_id='.$_REQUEST['menu_id'].'&fsa_type=staff">'._('Users').'</a>';

DrawHeader(($_SESSION['FSA_type']=='staff' ? _('User') : _('Student')).' &minus; '.ProgramTitle());
User('PROFILE')=='student'?'':DrawHeader($header);

$menus_RET = DBGet(DBQuery('SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER'),array(),array('MENU_ID'));
if(!$_REQUEST['menu_id'])
{
	if(!$_SESSION['FSA_menu_id'])
		if(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
	else
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
	unset($_SESSION['FSA_sale']);
}
else
	$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];

if ($_REQUEST['modfunc']=='add')
{
	if($_REQUEST['item_sn'])
		$_SESSION['FSA_sale'][] = $_REQUEST['item_sn'];
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='remove')
{
	if($_REQUEST['id']!='')
		unset($_SESSION['FSA_sale'][$_REQUEST['id']]);
	unset($_REQUEST['modfunc']);
}

include('modules/Food_Service/'.($_SESSION['FSA_type']=='staff'?'Users/':'Students/').'/ServeMenus.php');

function red($value)
{
        if($value<0)
                return '<span style="color:red">'.$value.'</span>';
        else
                return $value;
}

function makeIcon($value,$name)
{	global $FS_IconsPath;

	if($value)
		return '<IMG src="'.$FS_IconsPath.'/'.$value.'" height="30">';
	else
		return '&nbsp;';
}
?>
