<?php

function _makeFeesRemove( $value, $column )
{
	global $THIS_RET,
		$waived_fees_RET;

	if ( ! $waived_fees_RET )
	{
		$waived_fees_RET = DBGet( "SELECT f.WAIVED_FEE_ID
			FROM BILLING_FEES f
			WHERE f.STUDENT_ID='" . UserStudentID() . "'
			AND f.WAIVED_FEE_ID IS NOT NULL
			AND f.SYEAR='" . UserSyear() . "'
			AND f.SCHOOL_ID='" . UserSchool() . "'", array(), array( 'WAIVED_FEE_ID' ) );
	}

	if ( ! $THIS_RET['WAIVED_FEE_ID'] && ! $waived_fees_RET[ $THIS_RET['ID'] ] )
	{
		$return = button(
			'remove',
			_( 'Waive' ),
			'"Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=waive&id=' . $THIS_RET['ID'] . '"'
		);
	}
	elseif ( $waived_fees_RET[ $THIS_RET['ID'] ] )
	{
		$return = '<span style="color:#00A642">' . _( 'Waived' ) . '</span> ';
	}

	return $return . button(
		'remove',
		_( 'Delete' ),
		'"Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=remove&id=' . $THIS_RET['ID'] . '"'
	);
}

function _makePaymentsRemove( $value, $column )
{
	global $THIS_RET,
		$refunded_payments_RET;

	if ( ! $refunded_payments_RET )
	{
		$refunded_payments_RET = DBGet( "SELECT p.REFUNDED_PAYMENT_ID
			FROM BILLING_PAYMENTS p
			WHERE p.STUDENT_ID='" . UserStudentID() . "'
			AND (p.REFUNDED_PAYMENT_ID IS NOT NULL AND p.REFUNDED_PAYMENT_ID!='')
			AND p.SYEAR='" . UserSyear() . "'
			AND p.SCHOOL_ID='" . UserSchool() . "'", array(), array( 'REFUNDED_PAYMENT_ID' ) );
	}

	if ( ! $THIS_RET['REFUNDED_PAYMENT_ID']
		&& ! $refunded_payments_RET[ $THIS_RET['ID'] ] )
	{
		$return = button(
			'remove',
			_( 'Refund' ),
			'"Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=refund&id=' . $THIS_RET['ID'] . '"'
		);
	}
	elseif ( $refunded_payments_RET[ $THIS_RET['ID'] ] )
	{
		$return = '<span class="center" style="color:#00A642">' . _( 'Refunded' ) . '</span>';
	}

	return $return . button(
		'remove',
		_( 'Delete' ),
		'"Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=remove&id=' . $THIS_RET['ID'] . '"'
	);
}

function _makeFeesTextInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['WAIVED_FEE_ID'] )
	{
		$THIS_RET['row_colow'] = 'FFFFFF';
	}

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
		$div = 'force';
	}
	else
	{
		$id = 'new';
		$div = false;
	}

	$extra = 'maxlength=255';

	if ( $name === 'AMOUNT' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( ! $value )
	{
		$extra .= ' size=15';
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra,
		$div
	);
}

function _makeFeesDateInput($value='',$name)
{	global $THIS_RET;

	if ( $THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	$name = 'values['.$id.']['.$name.']';

	return DateInput($value,$name);
}

function _makeFeesAmount($value,$column)
{	global $fees_total;

	$fees_total += $value;
	return Currency($value);
}

function _makePaymentsTextInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	$extra = 'maxlength=255';

	if ( $name === 'AMOUNT' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( ! $value )
	{
		$extra .= ' size=15';
	}

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

function _makePaymentsDateInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	return DateInput( $value, 'values[' . $id . '][' . $name . ']', '', ( $id !== 'new' ), false );
}

function _makePaymentsAmount($value,$column)
{	global $payments_total;

	$payments_total += $value;
	return Currency($value);
}

function _lunchInput( $value, $column )
{
	global $THIS_RET;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	return CheckboxInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		'',
		( $id === 'new' )
	);
}
