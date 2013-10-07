<?php

if($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start'])
	while(!VerifyDate($start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start']))
		$_REQUEST['day_start']--;
else
{
	$_REQUEST['day_start'] = '01';
	$_REQUEST['month_start'] = mb_strtoupper(date('M'));
	$_REQUEST['year_start'] = date('Y');
	$start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start'];
}

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
	while(!VerifyDate($end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end']))
		$_REQUEST['day_end']--;
else
{
	$_REQUEST['day_end'] = date('d');
	$_REQUEST['month_end'] = mb_strtoupper(date('M'));
	$_REQUEST['year_end'] = date('Y');
	$end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end'];
}

if($_REQUEST['type'])
	$_SESSION['FSA_type'] = $_REQUEST['type'];
else
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];

//modif Francois: remove DrawTab params
$header = '<a href="Modules.php?modname='.$_REQUEST['modname'].'&day_start='.$_REQUEST['day_start'].'&month_start='.$_REQUEST['month_start'].'&year_start='.$_REQUEST['year_start'].'&day_end='.$_REQUEST['day_end'].'&month_end='.$_REQUEST['month_end'].'&year_end='.$_REQUEST['year_end'].'&type=student">'._('Students').'</a>';
$header .= ' - <a href="Modules.php?modname='.$_REQUEST['modname'].'&day_start='.$_REQUEST['day_start'].'&month_start='.$_REQUEST['month_start'].'&year_start='.$_REQUEST['year_start'].'&day_end='.$_REQUEST['day_end'].'&month_end='.$_REQUEST['month_end'].'&year_end='.$_REQUEST['year_end'].'&type=staff">'._('Users').'</a>';

DrawHeader(($_REQUEST['type']=='staff'?_('User'):_('Student')).' &minus; '.ProgramTitle());
User('PROFILE')=='student'?'':DrawHeader($header);

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	require_once('modules/Food_Service/includes/DeletePromptX.fnc.php');
	if($_REQUEST['item_id']!='')
	{
//modif Francois: add translation
		if(DeletePromptX(_('Transaction Item')))
		{
			require_once('modules/Food_Service/includes/DeleteTransactionItem.fnc.php');
			DeleteTransactionItem($_REQUEST['transaction_id'],$_REQUEST['item_id'],$_REQUEST['type']);
			DBQuery('BEGIN; '.$sql1.'; '.$sql2.'; '.$sql3.'; COMMIT');
			unset($_REQUEST['modfunc']);
			unset($_REQUEST['delete_ok']);
			unset($_SESSION['_REQUEST_vars']['modfunc']);
			unset($_SESSION['_REQUEST_vars']['delete_ok']);
		}
	}
	else
	{
		if(DeletePromptX(_('Transaction')))
		{
			require_once('modules/Food_Service/includes/DeleteTransaction.fnc.php');
			DeleteTransaction($_REQUEST['transaction_id'],$_REQUEST['type']);
			unset($_REQUEST['modfunc']);
			unset($_REQUEST['delete_ok']);
			unset($_SESSION['_REQUEST_vars']['modfunc']);
			unset($_SESSION['_REQUEST_vars']['delete_ok']);
		}
	}
}

if(empty($_REQUEST['modfunc']))

{
$types = array('DEPOSIT'=>_('Deposit'),'CREDIT'=>_('Credit'),'DEBIT'=>_('Debit'));
$menus_RET = DBGet(DBQuery('SELECT TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER'));

$type_select = _('Type').': <SELECT name=type_select><OPTION value=\'\'>'._('Not Specified').'</OPTION>';
foreach($types as $short_name=>$type)
	$type_select .= '<OPTION value="'.$short_name.'"'.($_REQUEST['type_select']==$short_name ? ' SELECTED="SELECTED"' : '').'>'.$type.'</OPTION>';
foreach($menus_RET as $menu)
	$type_select .= '<OPTION value="'.$menu['TITLE'].'"'.($_REQUEST['type_select']==$menu['TITLE'] ? ' SELECTED="SELECTED"' : '').'>'.$menu['TITLE'].'</OPTION>';
$type_select .= '</SELECT>';

//modif Francois: add translation
function types_locale($type) {
	$types = array('Deposit'=>_('Deposit'),'Credit'=>_('Credit'),'Debit'=>_('Debit'));
	if (array_key_exists($type, $types)) {
		return $types[$type];
	}
	return $type;
}
function options_locale($option) {
	$options = array('Cash '=>_('Cash'),'Check'=>_('Check'),'Credit Card'=>_('Credit Card'),'Debit Card'=>_('Debit Card'),'Transfer'=>_('Transfer'));
	if (array_key_exists($option, $options)) {
		return $options[$option];
	}
	return $option;
}
include('modules/Food_Service/'.($_REQUEST['type']=='staff'?'Users':'Students').'/Statements.php');
}

function red($value)
{
	if($value<0)
		return '<span style="color:red">'.$value.'</span>';
	else
		return $value;
}
?>