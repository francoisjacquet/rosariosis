<?php

function _makeFeesRemove( $value, $column )
{
	global $THIS_RET,
		$waived_fees_RET;

	if ( ! $waived_fees_RET )
	{
		$waived_fees_RET = DBGet( "SELECT f.WAIVED_FEE_ID
			FROM billing_fees f
			WHERE f.STUDENT_ID='" . UserStudentID() . "'
			AND f.WAIVED_FEE_ID IS NOT NULL
			AND f.SYEAR='" . UserSyear() . "'
			AND f.SCHOOL_ID='" . UserSchool() . "'", [], [ 'WAIVED_FEE_ID' ] );
	}

	$return = '';

	if ( empty( $THIS_RET['WAIVED_FEE_ID'] )
		&& empty( $waived_fees_RET[ $THIS_RET['ID'] ] ) )
	{
		$return = button(
			'remove',
			_( 'Waive' ),
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=waive&id=' . $THIS_RET['ID'] . '&student_id=' . UserStudentID() )
		) . '<br class="rbr"> ';
	}
	elseif ( ! empty( $waived_fees_RET[ $THIS_RET['ID'] ] ) )
	{
		$return = '<span style="color:#00A642">' . _( 'Waived' ) . '</span><br class="rbr"> ';
	}

	return $return . button(
		'remove',
		_( 'Delete' ),
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&id=' . $THIS_RET['ID'] . '&student_id=' . UserStudentID() )
	);
}

function _makePaymentsRemove( $value, $column )
{
	global $THIS_RET,
		$refunded_payments_RET;

	if ( ! $refunded_payments_RET )
	{
		$refunded_payments_RET = DBGet( "SELECT p.REFUNDED_PAYMENT_ID
			FROM billing_payments p
			WHERE p.STUDENT_ID='" . UserStudentID() . "'
			AND (p.REFUNDED_PAYMENT_ID IS NOT NULL)
			AND p.SYEAR='" . UserSyear() . "'
			AND p.SCHOOL_ID='" . UserSchool() . "'", [], [ 'REFUNDED_PAYMENT_ID' ] );
	}

	$return = '';

	if ( empty( $THIS_RET['REFUNDED_PAYMENT_ID'] )
		&& empty( $refunded_payments_RET[ $THIS_RET['ID'] ] ) )
	{
		if ( AllowEdit( 'Student_Billing/StudentPayments.php&modfunc=remove' ) )
		{
			// @since 8.5 Admin Student Payments Delete restriction.
			// @since 8.6 Also exclude Refund.
			$return = button(
				'remove',
				_( 'Refund' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
					'&modfunc=refund&id=' . $THIS_RET['ID'] . '&student_id=' . UserStudentID() )
			) . '<br class="rbr"> ';
		}
	}
	elseif ( ! empty( $refunded_payments_RET[ $THIS_RET['ID'] ] ) )
	{
		$return = '<span style="color:#00A642">' . _( 'Refunded' ) . '</span><br class="rbr"> ';
	}

	if ( AllowEdit( 'Student_Billing/StudentPayments.php&modfunc=remove' ) )
	{
		// @since 8.5 Admin Student Payments Delete restriction.
		$return .= button(
			'remove',
			_( 'Delete' ),
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=remove&id=' . $THIS_RET['ID'] . '&student_id=' . UserStudentID() )
		);
	}

	return $return;
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
		$extra = ' type="number" step="0.01" max="999999999999" min="-999999999999"';
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

function _makeFeesDateInput( $value, $name )
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
		$extra = ' type="number" step="0.01" max="999999999999" min="-999999999999"';
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
 * @since 6.2 Remove Fees having a Payment (same Amount & Comments (Title), after or on Assigned Date).
 * @since 8.0 Remove Waived Fees from list.
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
	// Remove Fees having a Payment (same Amount & Comments (Title), after or on Assigned Date).
	// Remove Waived Fees from list.
	$fees_RET = DBGet( "SELECT ID,TITLE,ASSIGNED_DATE,DUE_DATE,AMOUNT
		FROM billing_fees bf
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND WAIVED_FEE_ID IS NULL
		AND NOT EXISTS(SELECT 1
			FROM billing_payments
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND AMOUNT=bf.AMOUNT
			AND (COMMENTS=bf.TITLE OR COMMENTS LIKE CONCAT('%',bf.TITLE) OR COMMENTS LIKE CONCAT(bf.TITLE,'%'))
			AND PAYMENT_DATE>=bf.ASSIGNED_DATE)
		AND NOT EXISTS(SELECT 1
			FROM billing_fees
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND WAIVED_FEE_ID=bf.ID)
		ORDER BY ASSIGNED_DATE DESC
		LIMIT 20" );

	if ( ! $fees_RET )
	{
		return $text_input;
	}

	$fees_options = [];

	foreach ( $fees_RET as $fee )
	{
		$fees_options[ $fee['AMOUNT'] . '|' . $fee['TITLE'] . '|' . $fee['ASSIGNED_DATE'] ] = ProperDate( $fee['ASSIGNED_DATE'], 'short' ) .
			' — ' . Currency( $fee['AMOUNT'] ) .
			' — ' . $fee['TITLE'];
	}

	// JS automatically fills the Comments & Amount inputs.
	ob_start();
	?>
	<script>
		var billingPaymentsFeeReconcile = function( amountCommentsDate ) {
			var amountCommentsDateSplit = amountCommentsDate.split( '|' ),
				amount = amountCommentsDateSplit[0],
				comments = amountCommentsDateSplit[1],
				date = amountCommentsDateSplit[2];

			$('#valuesnewAMOUNT').val( amount );
			$('#valuesnewCOMMENTS').val( comments );
		};
	</script>
	<?php
	$js = ob_get_clean();

	$select_input = SelectInput(
		'',
		'billing_fees',
		'',
		$fees_options,
		'N/A',
		'onchange="billingPaymentsFeeReconcile(this.value);" style="width: 158px;"'
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

/**
 * Make Fees File Attached Input
 *
 * @since 8.1
 * @since 10.4 Add File Attached Input for existing Fees
 *
 * @param  string $value File path value.
 * @param  string $name  Column name, 'FILE_ATTACHED'.
 *
 * @return string        File Input HTML or link to download File.
 */
function _makeFeesFileInput( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		return InputDivOnclick(
			'FILE_ATTACHED',
			FileInput( 'FILE_ATTACHED' ),
			button( 'add' ),
			''
		);
	}

	if ( empty( $value )
		|| ! file_exists( $value ) )
	{
		if ( isset( $_REQUEST['_ROSARIO_PDF'] )
			|| ! AllowEdit() )
		{
			return '';
		}

		// Add hidden FILE_ATTACHED input so it gets saved even if no other columns to save.
		return '<input type="hidden" name="values[' . $THIS_RET['ID'] . '][FILE_ATTACHED]" value="" />' .
			InputDivOnclick(
				'FILE_ATTACHED_' . $THIS_RET['ID'],
				FileInput( 'FILE_ATTACHED_' . $THIS_RET['ID'] ),
				button( 'add' ),
				''
			);
	}

	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	$file_path = $value;

	$file_name = basename( $file_path );

	$file_size = HumanFilesize( filesize( $file_path ) );

	$file = button(
		'download',
		'',
		'"' . URLEscape( $file_path ) . '" target="_blank" title="' . AttrEscape( $file_name . ' (' . $file_size . ')' ) . '"',
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
	return _makeFeesFileInput( $value, $column );
}

/**
 * Save Fees File
 *
 * @since 10.4
 * @since 10.8.2 Add datetime to filename to make it harder to predict
 *
 * @param  int|string $id Fee ID or 'new'.
 *
 * @return string     File path or empty.
 */
function _saveFeesFile( $id )
{
	global $error,
		$FileUploadsPath;

	$input = $id === 'new' ? 'FILE_ATTACHED' : 'FILE_ATTACHED_' . $id;

	if ( ! isset( $_FILES[ $input ] ) )
	{
		return '';
	}

	$file_attached = FileUpload(
		$input,
		$FileUploadsPath . UserSyear() . '/student_' . UserStudentID() . '/',
		FileExtensionWhiteList(),
		0,
		$error,
		'',
		FileNameTimestamp( $_FILES[ $input ]['name'] )
	);

	// Fix SQL error when quote in uploaded file name.
	return DBEscapeString( $file_attached );
}

/**
 * Save Payments File
 *
 * @since 10.4
 *
 * @param  int|string $id Payment ID or 'new'.
 *
 * @return string     File path or empty.
 */
function _savePaymentsFile( $id )
{
	return _saveFeesFile( $id );
}
