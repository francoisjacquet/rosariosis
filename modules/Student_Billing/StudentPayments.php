<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Student_Billing/functions.inc.php';

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );

	Search( 'student_id', issetVal( $extra ) );
}

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit()
	&& UserStudentID() )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values', 'post' );

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( $id !== 'new' )
		{
			$columns['FILE_ATTACHED'] = _savePaymentsFile( $id );

			if ( ! $columns['FILE_ATTACHED'] )
			{
				unset( $columns['FILE_ATTACHED'] );

				if ( empty( $columns ) )
				{
					// No file, and FILE_ATTACHED was the only column, skip.
					continue;
				}
			}

			DBUpdate(
				'billing_payments',
				$columns,
				[ 'STUDENT_ID' => UserStudentID(), 'ID' => (int) $id ]
			);
		}
		elseif ( isset( $columns['AMOUNT'] )
			&& is_numeric( $columns['AMOUNT'] )
			&& $columns['PAYMENT_DATE'] )
		{
			$insert_columns = [
				'STUDENT_ID' => UserStudentID(),
				'SCHOOL_ID' => UserSchool(),
				'SYEAR' => UserSyear(),
			];

			$columns['FILE_ATTACHED'] = _savePaymentsFile( $id );

			// @since 11.2 Add CREATED_BY column to billing_fees & billing_payments tables
			$columns['CREATED_BY'] = DBEscapeString( User( 'NAME' ) );

			DBInsert(
				'billing_payments',
				$insert_columns + $columns
			);
		}
	}

	// Unset values, month_values, day_values, year_values, billing_fees & redirect URL.
	RedirectURL( [ 'values', 'month_values', 'day_values', 'year_values', 'billing_fees' ] );
}

if ( $_REQUEST['modfunc'] === 'remove'
	// @since 8.5 Admin Student Payments Delete restriction.
	&& AllowEdit( 'Student_Billing/StudentPayments.php&modfunc=remove' ) )
{
	if ( DeletePrompt( _( 'Payment' ) ) )
	{
		$file_attached = DBGetOne( "SELECT FILE_ATTACHED
			FROM billing_payments
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		if ( ! empty( $file_attached )
			&& file_exists( $file_attached ) )
		{
			// Delete File Attached.
			unlink( $file_attached );
		}

		DBQuery( "DELETE FROM billing_payments
			WHERE ID='" . (int) $_REQUEST['id'] . "'
			OR REFUNDED_PAYMENT_ID='" . (int) $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'refund'
	// @since 8.5 Also exclude Refund.
	&& AllowEdit( 'Student_Billing/StudentPayments.php&modfunc=remove' ) )
{
	if ( DeletePrompt( _( 'Payment' ), _( 'Refund' ) ) )
	{
		$payment_RET = DBGet( "SELECT COMMENTS,AMOUNT
			FROM billing_payments
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		$comments = $payment_RET[1]['COMMENTS'] ?
			$payment_RET[1]['COMMENTS'] . ' &mdash; ' . _( 'Refund' ) :
			_( 'Refund' );

		DBInsert(
			'billing_payments',
			[
				'SYEAR' => UserSyear(),
				'SCHOOL_ID' => UserSchool(),
				'STUDENT_ID' => UserStudentID(),
				'AMOUNT' => ( $payment_RET[1]['AMOUNT'] * -1 ),
				'PAYMENT_DATE' => DBDate(),
				'COMMENTS' => DBEscapeString( $comments ),
				'REFUNDED_PAYMENT_ID' => (int) $_REQUEST['id'],
				// @since 11.2 Add CREATED_BY column to billing_fees & billing_payments tables
				'CREATED_BY' => DBEscapeString( User( 'NAME' ) ),
			]
		);

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$payments_total = 0;

	$functions = [
		'REMOVE' => '_makePaymentsRemove',
		'AMOUNT' => '_makePaymentsAmount',
		'PAYMENT_DATE' => 'ProperDate',
		'COMMENTS' => '_makePaymentsCommentsInput',
		'LUNCH_PAYMENT' => '_lunchInput',
		'FILE_ATTACHED' => '_makePaymentsFileInput',
		'CREATED_AT' => 'ProperDateTime',
	];

	$refunded_payments_RET = DBGet( "SELECT '' AS REMOVE,ID,REFUNDED_PAYMENT_ID,
		AMOUNT,PAYMENT_DATE,COMMENTS,LUNCH_PAYMENT,FILE_ATTACHED,
		CREATED_BY,CREATED_AT
		FROM billing_payments
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND (REFUNDED_PAYMENT_ID IS NOT NULL)", $functions, [ 'REFUNDED_PAYMENT_ID' ] );

	$payments_RET = DBGet( "SELECT '' AS REMOVE,ID,REFUNDED_PAYMENT_ID,
		AMOUNT,PAYMENT_DATE,COMMENTS,LUNCH_PAYMENT,FILE_ATTACHED,
		CREATED_BY,CREATED_AT
		FROM billing_payments
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND REFUNDED_PAYMENT_ID IS NULL ORDER BY ID", $functions );

	$i = 1;
	$RET = [];

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[$i] = $payment;

		if ( ! empty( $refunded_payments_RET[$payment['ID']] ) )
		{
			$i++;
			$RET[$i] = ( $refunded_payments_RET[$payment['ID']][1] + [ 'row_color' => 'FF0000' ] );
		}

		$i++;
	}

	$columns = [];

	if ( ! empty( $RET )
		&& empty( $_REQUEST['print_statements'] )
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ];
	}

	$columns += [
		'AMOUNT' => _( 'Amount' ),
		'PAYMENT_DATE' => _( 'Date' ),
		'COMMENTS' => _( 'Comment' ),
		'LUNCH_PAYMENT' => _( 'Lunch Payment' ),
	];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$columns += [ 'FILE_ATTACHED' => _( 'File Attached' ) ];
	}

	if ( isset( $_REQUEST['expanded_view'] )
		&& $_REQUEST['expanded_view'] === 'true' )
	{
		// @since 11.2 Expanded View: Add Created by & Created at columns.
		$columns += [
			'CREATED_BY' => _( 'Created by' ),
			'CREATED_AT' => _( 'Created at' ),
		];
	}

	$link = [];

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		$link['add']['html'] = [
			'REMOVE' => button( 'add' ),
			'AMOUNT' => _makePaymentsTextInput( '', 'AMOUNT' ),
			'PAYMENT_DATE' => _makePaymentsDateInput( DBDate(), 'PAYMENT_DATE' ),
			'COMMENTS' => _makePaymentsCommentsInput( '', 'COMMENTS' ),
			'LUNCH_PAYMENT' => _lunchInput( '', 'LUNCH_PAYMENT' ),
			'FILE_ATTACHED' => _makePaymentsFileInput( '', 'FILE_ATTACHED' ),
		];
	}

	// Do hook.
	// @since 6.5.1 Move header action hook above form.
	do_action( 'Student_Billing/StudentPayments.php|student_payments_header' );

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&student_id=' . UserStudentID() ) . '" method="POST">';

		if ( AllowEdit() )
		{
			if ( ! isset( $_REQUEST['expanded_view'] )
				|| $_REQUEST['expanded_view'] !== 'true' )
			{
				$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'true' ] ) . '">' .
				_( 'Expanded View' ) . '</a>';
			}
			else
			{
				$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'false' ] ) . '">' .
				_( 'Original View' ) . '</a>';
			}

			DrawHeader( $expanded_view_header, SubmitButton() );
		}

		$options = [];
	}
	else
	{
		$options = [ 'center' => false, 'add' => false ];
	}

	ListOutput(
		$RET,
		$columns,
		'Payment',
		'Payments',
		$link,
		[],
		$options
	);

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	$fees_total = DBGetOne( "SELECT SUM(f.AMOUNT) AS TOTAL
		FROM billing_fees f
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'" );

	$table = '<table class="align-right student-billing-totals"><tr>
		<td>' . _( 'Total from Fees' ) . ': </td>
		<td>' . Currency( $fees_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Payments' ) . ': </td>
		<td>' . Currency( $payments_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Balance' ) . ': </td>
		<td><b>' . Currency(  ( $fees_total - $payments_total ), 'CR' ) . '</b></td>
		</tr></table>';

	DrawHeader( $table );

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '</form>';
	}
}
