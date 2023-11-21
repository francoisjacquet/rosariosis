<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Accounting/functions.inc.php';

$_REQUEST['print_statements'] = issetVal( $_REQUEST['print_statements'], '' );

// Set start date.
$start_date = RequestedDate( 'start', '' );

// Fix PostgreSQL error if BETWEEN date is empty.
$start_date_sql = $start_date ? $start_date : '1900-01-01';

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );
}

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit() )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values', 'post' );

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( isset( $columns['CATEGORY_ID'] )
			&& $columns['CATEGORY_ID'] === '0' )
		{
			// Category ID 0 is N/A, reset to NULL.
			$columns['CATEGORY_ID'] = '';
		}

		if ( $id !== 'new' )
		{
			$columns['FILE_ATTACHED'] = _saveExpensesFile( $id );

			if ( ! $columns['FILE_ATTACHED'] )
			{
				unset( $columns['FILE_ATTACHED'] );
			}

			DBUpdate(
				'accounting_payments',
				$columns,
				[ 'ID' => (int) $id ]
			);
		}
		elseif ( isset( $columns['AMOUNT'] )
			&& is_numeric( $columns['AMOUNT'] )
			&& $columns['PAYMENT_DATE']
			&& $columns['TITLE'] )
		{
			$insert_columns = [ 'SYEAR' => UserSyear(), 'SCHOOL_ID' => UserSchool() ];

			$columns['FILE_ATTACHED'] = _saveExpensesFile( $id );

			DBInsert(
				'accounting_payments',
				$insert_columns + $columns
			);
		}
	}

	// Unset values & dates & redirect URL.
	RedirectURL( [ 'values', 'month_values', 'day_values', 'year_values' ] );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Expense' ) ) )
	{
		$file_attached = DBGetOne( "SELECT FILE_ATTACHED
			FROM accounting_payments
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		if ( ! empty( $file_attached )
			&& file_exists( $file_attached ) )
		{
			// Delete File Attached.
			unlink( $file_attached );
		}

		DBQuery( "DELETE FROM accounting_payments
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$payments_total = 0;

	$functions = [
		'REMOVE' => '_makePaymentsRemove',
		'CATEGORY_ID' => '_makePaymentsCategory',
		'AMOUNT' => '_makePaymentsAmount',
		'PAYMENT_DATE' => 'ProperDate',
		'COMMENTS' => '_makePaymentsTextInput',
		'FILE_ATTACHED' => '_makePaymentsFileInput',
	];

	$payments_RET = DBGet( "SELECT '' AS REMOVE,ID,TITLE,CATEGORY_ID,AMOUNT,PAYMENT_DATE,
		COMMENTS,FILE_ATTACHED
		FROM accounting_payments
		WHERE SYEAR='" . UserSyear() . "'
		AND STAFF_ID IS NULL
		AND SCHOOL_ID='" . UserSchool() . "'
		AND PAYMENT_DATE BETWEEN '" . $start_date_sql . "'
		AND '" . $end_date . "'
		ORDER BY PAYMENT_DATE,ID", $functions );

	$i = 1;
	$RET = [];

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[$i] = $payment;
		$i++;
	}

	$columns = [];

	if ( ! empty( $RET )
		&& ! $_REQUEST['print_statements']
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ];
	}

	$columns += [
		'TITLE' => _( 'Expense' ),
		'CATEGORY_ID' => _( 'Category' ),
		'AMOUNT' => _( 'Amount' ),
		'PAYMENT_DATE' => _( 'Date' ),
		'COMMENTS' => _( 'Comment' ),
	];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$columns += [ 'FILE_ATTACHED' => _( 'File Attached' ) ];
	}

	if ( ! $_REQUEST['print_statements']
		&& AllowEdit() )
	{
		$link['add']['html'] = [
			'REMOVE' => button( 'add' ),
			'TITLE' => _makePaymentsTextInput( '', 'TITLE' ),
			'CATEGORY_ID' => _makePaymentsCategory( '', 'CATEGORY_ID' ),
			'AMOUNT' => _makePaymentsTextInput( '', 'AMOUNT' ),
			'PAYMENT_DATE' => _makePaymentsDateInput( DBDate(), 'PAYMENT_DATE' ),
			'COMMENTS' => _makePaymentsTextInput( '', 'COMMENTS' ),
			'FILE_ATTACHED' => _makePaymentsFileInput( '', 'FILE_ATTACHED' ),
		];
	}

	if ( ! $_REQUEST['print_statements'] )
	{
		echo '<form action="' . PreparePHP_SELF( [], [
			'month_start',
			'day_start',
			'year_start',
			'month_end',
			'day_end',
			'year_end',
		] ) . '" method="GET">';

		DrawHeader( _( 'Timeframe' ) . ': ' .
			PrepareDate( $start_date, '_start', true ) . ' &nbsp; ' . _( 'to' ) . ' &nbsp; ' .
			PrepareDate( $end_date, '_end', false ) . ' ' . Buttons( _( 'Go' ) ) );

		echo '</form>';
	}

	if ( ! $_REQUEST['print_statements'] && AllowEdit() )
	{
		echo '<form action="' . PreparePHP_SELF() . '" method="POST">';
		DrawHeader( '', SubmitButton() );
		$options = [];
	}
	else
	{
		$options = [ 'center' => false, 'add' => false ];
	}

	ListOutput( $RET, $columns, 'Expense', 'Expenses', $link, [], $options );

	if ( ! $_REQUEST['print_statements'] && AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	$incomes_total = DBGetOne( "SELECT SUM(f.AMOUNT) AS TOTAL
		FROM accounting_incomes f
		WHERE f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.ASSIGNED_DATE BETWEEN '" . $start_date_sql . "'
		AND '" . $end_date . "'" );

	$table = '<table class="align-right accounting-totals"><tr><td>' . _( 'Total from Incomes' ) . ': ' . '</td><td>' . Currency( $incomes_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Expenses' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Balance' ) . ': <b>' . '</b></td><td><b id="update_balance">' . Currency(  ( $incomes_total - $payments_total ) ) . '</b></td></tr>';

	//add General Balance
	$table .= '<tr><td colspan="2"><hr></td></tr><tr><td>' . _( 'Total from Incomes' ) . ': ' . '</td><td>' . Currency( $incomes_total ) . '</td></tr>';

	if ( $RosarioModules['Student_Billing'] )
	{
		$student_payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
			FROM billing_payments p
			WHERE p.SYEAR='" . UserSyear() . "'
			AND p.SCHOOL_ID='" . UserSchool() . "'
			AND p.PAYMENT_DATE BETWEEN '" . $start_date_sql . "'
			AND '" . $end_date . "'" );

		$table .= '<tr><td>& ' . _( 'Total from Student Payments' ) . ': ' . '</td><td>' . Currency( $student_payments_total ) . '</td></tr>';
	}
	else
	{
		$student_payments_total = 0;
	}

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Expenses' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

	$staff_payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
		FROM accounting_payments p
		WHERE p.STAFF_ID IS NOT NULL
		AND p.SYEAR='" . UserSyear() . "'
		AND p.SCHOOL_ID='" . UserSchool() . "'
		AND p.PAYMENT_DATE BETWEEN '" . $start_date_sql . "'
		AND '" . $end_date . "'" );

	$table .= '<tr><td>& ' . _( 'Total from Staff Payments' ) . ': ' . '</td><td>' . Currency( $staff_payments_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'General Balance' ) . ': </td>
		<td><b id="update_balance">' . Currency(  ( $incomes_total + $student_payments_total - $payments_total - $staff_payments_total ) ) .
		'</b></td></tr></table>';

	DrawHeader( $table );

	if ( ! $_REQUEST['print_statements']
		&& AllowEdit() )
	{
		echo '</form>';
	}
}
