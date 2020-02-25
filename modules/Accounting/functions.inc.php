<?php
function _makeIncomesRemove( $value, $column )
{
	global $THIS_RET;

	return button(
		'remove',
		_( 'Delete' ),
		'"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' . $THIS_RET['ID'] . '"'
	);
}

function _makeSalariesRemove( $value, $column )
{
	return _makeIncomesRemove( $value, $column );
}

function _makePaymentsRemove( $value, $column )
{
	return _makeIncomesRemove( $value, $column );
}

function _makeIncomesTextInput( $value, $column )
{
	global $THIS_RET;

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

	if ( $column === 'AMOUNT' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( ! $value )
	{
		$extra .= ' size=15';
	}

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra, $div );
}


function _makeIncomesDateInput( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	return DateInput( $value, 'values[' . $id . '][' . $column . ']', '', ( $id !== 'new' ), false );
}

function _makePaymentsDateInput( $value, $name )
{
	return _makeIncomesDateInput( $value, $name );
}

function _makeSalariesTextInput( $value, $name )
{
	return _makeIncomesTextInput( $value, $name );
}

function _makeSalariesDateInput($value='',$name)
{	global $THIS_RET;

	if ( $THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	$name = 'values['.$id.']['.$name.']';

	return DateInput($value,$name);
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
 * Add Salaries dropdown to reconcile Payment:
 * Automatically fills the Comments & Amount inputs.
 *
 * @since 5.1
 *
 * @uses _makePaymentsTextInput()
 *
 * @param  string $value Comments value.
 * @param  string $name  Column name, 'COMMENTS'.
 *
 * @return string Text input if not new or if no Salaries found, else Text input & Salaries dropdown.
 */
function _makePaymentsCommentsInput( $value, $name )
{
	global $THIS_RET;

	$text_input = _makePaymentsTextInput( $value, $name );

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		return $text_input;
	}

	// Add Salaries dropdown to reconcile Payment.
	$salaries_RET = DBGet( "SELECT ID,TITLE,ASSIGNED_DATE,DUE_DATE,AMOUNT
		FROM ACCOUNTING_SALARIES
		WHERE STAFF_ID='" . UserStaffID() . "'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY ASSIGNED_DATE DESC
		LIMIT 100" );

	if ( ! $salaries_RET )
	{
		return $text_input;
	}

	$salaries_options = array();

	foreach ( $salaries_RET as $salary )
	{
		$salaries_options[ $salary['AMOUNT'] . '|' . $salary['TITLE'] ] = ProperDate( $salary['ASSIGNED_DATE'], 'short' ) .
			' — ' . Currency( $salary['AMOUNT'] ) .
			' — ' . $salary['TITLE'];
	}

	// JS automatically fills the Comments & Amount inputs.
	ob_start();
	?>
	<script>
		var accountingPaymentsSalariesReconcile = function( amountComments ) {
			var separatorIndex = amountComments.indexOf( '|' ),
				amount = amountComments.substring( 0, separatorIndex ),
				comments = amountComments.substring( separatorIndex + 1 );

			$('#valuesnewAMOUNT').val( amount );
			$('#valuesnewCOMMENTS').val( comments );
		};
	</script>
	<?php
	$js = ob_get_clean();

	// Chosen select so we can search Salaries by date, amount, & title.
	$select_input = ChosenSelectInput(
		'',
		'accounting_salaries',
		'',
		$salaries_options,
		'N/A',
		'onchange="accountingPaymentsSalariesReconcile(this.options[selectedIndex].value);"'
	);

	return $text_input . ' ' . $js . $select_input;
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
