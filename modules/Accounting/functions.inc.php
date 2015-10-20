<?php
function _makeIncomesRemove($value,$column)
{	global $THIS_RET;
	
	return button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove&id='.$THIS_RET['ID'].'"');
}

function _makeSalariesRemove($value,$column)
{	global $THIS_RET;
	
	return button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove&id='.$THIS_RET['ID'].'"');
}

function _makePaymentsRemove($value,$column)
{	global $THIS_RET;
	
	return button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove&id='.$THIS_RET['ID'].'"');
}

function _makeIncomesTextInput($value,$name)
{	global $THIS_RET;

	if ($THIS_RET['ID'])
	{
		$id = $THIS_RET['ID'];
		$div = 'force';
	}
	else
	{
		$id = 'new';
		$div = false;
	}
	
	if ($name=='AMOUNT')
		$extra = 'size=5 maxlength=10';
	
	return TextInput($value,'values['.$id.']['.$name.']','',$extra,$div);
}

function _makeSalariesTextInput($value,$name)
{	global $THIS_RET;

	if ($THIS_RET['ID'])
	{
		$id = $THIS_RET['ID'];
		$div = 'force';
	}
	else
	{
		$id = 'new';
		$div = false;
	}
	
	if ($name=='AMOUNT')
		$extra = 'size=5 maxlength=10';
	
	return TextInput($value,'values['.$id.']['.$name.']','',$extra,$div);
}

function _makeSalariesDateInput($value='',$name)
{	global $THIS_RET;

	if ($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	$name = '['.$id.']['.$name.']';

	return DateInput($value,$name);
}

function _makePaymentsTextInput($value,$name)
{	global $THIS_RET;
	
	if ($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	if ($name=='AMOUNT')
		$extra = 'size=5 maxlength=10';
	
	return TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function _makeSalariesAmount($value,$column)
{	global $salaries_total;

	$salaries_total += $value;
	return Currency($value);
}

function _makeIncomesAmount($value,$column)
{	global $incomes_total;

	$incomes_total += $value;
	return Currency($value);
}

function _makePaymentsAmount($value,$column)
{	global $payments_total;

	$payments_total += $value;
	return Currency($value);
}

?>