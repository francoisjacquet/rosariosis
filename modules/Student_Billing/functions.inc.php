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

	if ( empty( $THIS_RET['WAIVED_FEE_ID'] )
		&& empty( $waived_fees_RET[ $THIS_RET['ID'] ] ) )
	{
		$return = button(
			'remove',
			_( 'Waive' ),
			'"Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=waive&id=' . $THIS_RET['ID'] . '"'
		) . ' ';
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
			AND (p.REFUNDED_PAYMENT_ID IS NOT NULL)
			AND p.SYEAR='" . UserSyear() . "'
			AND p.SCHOOL_ID='" . UserSchool() . "'", array(), array( 'REFUNDED_PAYMENT_ID' ) );
	}

	$return = '';

	if ( empty( $THIS_RET['REFUNDED_PAYMENT_ID'] )
		&& empty( $refunded_payments_RET[ $THIS_RET['ID'] ] ) )
	{
		$return = button(
			'remove',
			_( 'Refund' ),
			'"Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=refund&id=' . $THIS_RET['ID'] . '"'
		) . ' ';
	}
	elseif ( ! empty( $refunded_payments_RET[ $THIS_RET['ID'] ] ) )
	{
		$return = '<span style="color:#00A642">' . _( 'Refunded' ) . '</span> ';
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

	if ( ! empty( $THIS_RET['WAIVED_FEE_ID'] ) )
	{
		$THIS_RET['row_colow'] = 'FFFFFF';
	}

	if ( ! empty( $THIS_RET['ID'] ) )
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

function _makeFeesDateInput( $value='', $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	$name = 'values[' . $id . '][' . $name . ']';

	return DateInput( $value, $name );
}

function _makeFeesAmount( $value, $column )
{
	global $fees_total;

	$fees_total += $value;

	return Currency( $value );
}

function _makePaymentsTextInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
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

/**
 * Make Payments Comments Input
 * Add Fees dropdown to reconcile Payment:
 * Automatically fills the Comments & Amount inputs.
 *
 * @since 5.1
 *
 * @uses _makePaymentsTextInput()
 *
 * @param  string $value Comments value.
 * @param  string $name  Column name, 'COMMENTS'.
 *
 * @return string Text input if not new or if no Fees found, else Text input & Fees dropdown.
 */
function _makePaymentsCommentsInput( $value, $name )
{
	global $THIS_RET;

	$text_input = _makePaymentsTextInput( $value, $name );

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		return $text_input;
	}

	// Add Fees dropdown to reconcile Payment.
	$fees_RET = DBGet( "SELECT ID,TITLE,ASSIGNED_DATE,DUE_DATE,AMOUNT
		FROM BILLING_FEES
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND (WAIVED_FEE_ID IS NULL OR WAIVED_FEE_ID='')
		ORDER BY ASSIGNED_DATE DESC
		LIMIT 100" );

	if ( ! $fees_RET )
	{
		return $text_input;
	}

	$fees_options = array();

	foreach ( $fees_RET as $fee )
	{
		$fees_options[ $fee['AMOUNT'] . '|' . $fee['TITLE'] ] = ProperDate( $fee['ASSIGNED_DATE'], 'short' ) .
			' — ' . Currency( $fee['AMOUNT'] ) .
			' — ' . $fee['TITLE'];
	}

	// JS automatically fills the Comments & Amount inputs.
	ob_start();
	?>
	<script>
		var billingPaymentsFeeReconcile = function( amountComments ) {
			var separatorIndex = amountComments.indexOf( '|' ),
				amount = amountComments.substring( 0, separatorIndex ),
				comments = amountComments.substring( separatorIndex + 1 );

			$('#valuesnewAMOUNT').val( amount );
			$('#valuesnewCOMMENTS').val( comments );
		};
	</script>
	<?php
	$js = ob_get_clean();

	// Chosen select so we can search Fees by date, amount, & title.
	$select_input = ChosenSelectInput(
		'',
		'billing_fees',
		'',
		$fees_options,
		'N/A',
		'onchange="billingPaymentsFeeReconcile(this.options[selectedIndex].value);"'
	);

	return $text_input . ' ' . $js . $select_input;
}

function _makePaymentsDateInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	return DateInput( $value, 'values[' . $id . '][' . $name . ']', '', ( $id !== 'new' ), false );
}

function _makePaymentsAmount( $value, $column )
{
	global $payments_total;

	$payments_total += $value;

	return Currency( $value );
}

function _lunchInput( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
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
