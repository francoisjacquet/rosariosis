<?php
function _makeIncomesRemove( $value, $column )
{
	global $THIS_RET;

	return button(
		'remove',
		_( 'Delete' ),
		'"' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' . $THIS_RET['ID'] ) . '"'
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
		$extra = ' type="number" step="any"';
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

function _makeSalariesDateInput( $value='', $name )
{	global $THIS_RET;

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
		$extra = ' type="number" step="any"';
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
 * @since 7.7 Remove Salaries having a Payment (same Amount & Comments (Title), after or on Assigned Date).
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
		FROM ACCOUNTING_SALARIES sal
		WHERE STAFF_ID='" . UserStaffID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND NOT EXISTS(SELECT 1
			FROM ACCOUNTING_PAYMENTS
			WHERE STAFF_ID='" . UserStaffID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND AMOUNT=sal.AMOUNT
			AND (COMMENTS=sal.TITLE OR COMMENTS LIKE '%' || sal.TITLE OR COMMENTS LIKE sal.TITLE || '%')
			AND PAYMENT_DATE>=sal.ASSIGNED_DATE)
		ORDER BY ASSIGNED_DATE DESC
		LIMIT 20" );

	if ( ! $salaries_RET )
	{
		return $text_input;
	}

	$salaries_options = [];

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

	// Select so we can search Salaries by date, amount, & title.
	$select_input = SelectInput(
		'',
		'accounting_salaries',
		'',
		$salaries_options,
		'N/A',
		'onchange="accountingPaymentsSalariesReconcile(this.value);" style="width: 250px;"'
	);

	return $text_input . ' ' . $js . $select_input;
}

function _makeSalariesAmount( $value, $column )
{
	global $salaries_total;

	$salaries_total += $value;

	return Currency( $value );
}

function _makeIncomesAmount( $value, $column )
{
	global $incomes_total;

	$incomes_total += $value;

	return Currency( $value );
}

function _makePaymentsAmount( $value, $column )
{
	global $payments_total;

	$payments_total += $value;

	return Currency( $value );
}

/**
 * Make Salaries File Attached Input
 *
 * @since 8.1
 *
 * @param  string $value File path value.
 * @param  string $name  Column name, 'FILE_ATTACHED'.
 *
 * @return string        File Input HTML or link to download File.
 */
function _makeSalariesFileInput( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		return FileInput(
			'FILE_ATTACHED'
		);
	}

	if ( empty( $value )
		|| ! file_exists( $value ) )
	{
		return '';
	}

	$file_path = $value;

	$file_name = mb_substr( mb_strrchr( $file_path, '/' ), 1 );

	$file_size = HumanFilesize( filesize( $file_path ) );

	// Truncate file name if > 36 chars.
	$file_name_display = mb_strlen( $file_name ) <= 36 ?
		$file_name :
		mb_substr( $file_name, 0, 30 ) . '..' . mb_strrchr( $file_name, '.' );

	$file = button(
		'download',
		$file_name_display,
		'"' . URLEscape( $file_path ) . '" target="_blank" title="' . $file_name . ' (' . $file_size . ')"',
		'bigger'
	);

	return $file;
}

/**
 * Make Payments File Attached Input
 *
 * @since 8.3
 *
 * @param  string $value File path value.
 * @param  string $name  Column name, 'FILE_ATTACHED'.
 *
 * @return string        File Input HTML or link to download File.
 */
function _makePaymentsFileInput( $value, $column )
{
	return _makeSalariesFileInput( $value, $column );
}

/**
 * Make Incomes File Attached Input
 *
 * @since 8.4
 *
 * @param  string $value File path value.
 * @param  string $name  Column name, 'FILE_ATTACHED'.
 *
 * @return string        File Input HTML or link to download File.
 */
function _makeIncomesFileInput( $value, $column )
{
	return _makeSalariesFileInput( $value, $column );
}
