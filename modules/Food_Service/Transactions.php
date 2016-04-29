<?php

if ( $_REQUEST['type'])
	$_SESSION['FSA_type'] = $_REQUEST['type'];
else
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];

/*if ( $_REQUEST['type']=='staff')
{
	$tabcolor_s = '#DFDFDF'; $textcolor_s = '#999999';
	$tabcolor_u = Preferences('HEADER'); $textcolor_u = '#FFFFFF';
}
else
{
	$tabcolor_s = Preferences('HEADER'); $textcolor_s = '#FFFFFF';
	$tabcolor_u = '#DFDFDF'; $textcolor_u = '#999999';
}*/
//FJ remove DrawTab params
$header = '<a href="Modules.php?modname='.$_REQUEST['modname'].'&type=student"><b>'._('Students').'</b></a>';
$header .= ' | <a href="Modules.php?modname='.$_REQUEST['modname'].'&type=staff"><b>'._('Users').'</b></a>';

DrawHeader(($_REQUEST['type']=='staff'?_('User'):_('Student')).' &minus; '.ProgramTitle());
User('PROFILE')=='student'?'':DrawHeader($header);

if ( $_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if ( DeletePrompt( _( 'Transaction' ) ) )
	{
		require_once 'modules/Food_Service/includes/DeleteTransaction.fnc.php';
		DeleteTransaction($_REQUEST['id'],$_REQUEST['type']);
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
	}
}

require_once 'modules/Food_Service/'.($_REQUEST['type']=='staff'?'Users':'Students').'/Transactions.php';

function red($value)
{
	if ( $value<0)
		return '<span style="color:red">'.$value.'</span>';
	else
		return $value;
}

function is_money($value)
{
	if ( $value > 0) {
		if (mb_strpos($value,'.')) return $value;
		elseif ( $value >= 100) return $value/100;
		else return $value;
	}
	else return false;
}
