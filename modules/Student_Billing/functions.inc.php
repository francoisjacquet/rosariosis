<?php
/**
* @file $Id: functions.inc.php 580 2007-06-05 19:19:11Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

function _makeFeesRemove($value,$column)
{	global $THIS_RET,$waived_fees_RET;
	
	if(!$waived_fees_RET)
		$waived_fees_RET = DBGet(DBQuery("SELECT f.WAIVED_FEE_ID FROM BILLING_FEES f WHERE f.STUDENT_ID='".UserStudentID()."' AND f.WAIVED_FEE_ID IS NOT NULL AND f.SYEAR='".UserSyear()."' AND f.SCHOOL_ID='".UserSchool()."'"),array(),array('WAIVED_FEE_ID'));

	if(!$THIS_RET['WAIVED_FEE_ID'] && !$waived_fees_RET[$THIS_RET['ID']])
		$return = button('remove',_('Waive'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=waive&id='.$THIS_RET['ID'].'"');
	elseif($waived_fees_RET[$THIS_RET['ID']])
		$return = '<span style="color:#00A642; text-align:center">'._('Waived').'</span>';
	return $return.button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove&id='.$THIS_RET['ID'].'"');
}

function _makePaymentsRemove($value,$column)
{	global $THIS_RET,$refunded_payments_RET;
	
	if(!$refunded_payments_RET)
		$refunded_payments_RET = DBGet(DBQuery("SELECT p.REFUNDED_PAYMENT_ID FROM BILLING_PAYMENTS p WHERE p.STUDENT_ID='".UserStudentID()."' AND (p.REFUNDED_PAYMENT_ID IS NOT NULL AND p.REFUNDED_PAYMENT_ID!='') AND p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."'"),array(),array('REFUNDED_PAYMENT_ID'));

	if(!$THIS_RET['REFUNDED_PAYMENT_ID'] && !$refunded_payments_RET[$THIS_RET['ID']])
		$return = button('remove',_('Refund'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=refund&id='.$THIS_RET['ID'].'"');
	elseif($refunded_payments_RET[$THIS_RET['ID']])
		$return = '<span style="color:#00A642; text-align:center">'._('Refunded').'</span>';
	return $return.button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove&id='.$THIS_RET['ID'].'"');
}

function _makeFeesTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['WAIVED_FEE_ID'])
		$THIS_RET['row_colow'] = 'FFFFFF';
	if($THIS_RET['ID'])
	{
		$id = $THIS_RET['ID'];
		$div = 'force';
	}
	else
	{
		$id = 'new';
		$div = false;
	}
	
	if($name=='AMOUNT')
		$extra = 'size=5 maxlength=10';
	
	return TextInput($value,'values['.$id.']['.$name.']','',$extra,$div);
}

function _makeFeesDateInput($value='',$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	$name = '['.$id.']['.$name.']';

	return DateInput($value,$name);
}

function _makeFeesAmount($value,$column)
{	global $fees_total;

	$fees_total += $value;
	return Currency($value);
}

function _makePaymentsTextInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	if($name=='AMOUNT')
		$extra = 'size=5 maxlength=10';
	
	return TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function _makePaymentsAmount($value,$column)
{	global $payments_total;

	$payments_total += $value;
	return Currency($value);
}

function _lunchInput($value,$column)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
	{
		$id = 'new';
		$new = true;
	}
	
	return CheckboxInput($value,'values['.$id.']['.$column.']','','',$new,_('Yes'),_('No'));
}

?>