<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Accounting/functions.inc.php';

$_REQUEST['print_statements'] = issetVal( $_REQUEST['print_statements'], '' );

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
		if ( $id !== 'new' )
		{
			$sql = "UPDATE ACCOUNTING_INCOMES SET ";

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
			DBQuery( $sql );
		}
		elseif ( $columns['AMOUNT'] !== ''
			&& $columns['ASSIGNED_DATE'] )
		{
			$sql = "INSERT INTO ACCOUNTING_INCOMES ";

			$fields = 'ID,SCHOOL_ID,SYEAR,';
			$values = db_seq_nextval( 'accounting_incomes_id_seq' ) . ",'" . UserSchool() . "','" . UserSyear() . "',";

			if ( isset( $_FILES['FILE_ATTACHED'] ) )
			{
				$columns['FILE_ATTACHED'] = FileUpload(
					'FILE_ATTACHED',
					$FileUploadsPath . UserSyear() . '/staff_' . User( 'STAFF_ID' ) . '/',
					FileExtensionWhiteList(),
					0,
					$error
				);

				// Fix SQL error when quote in uploaded file name.
				$columns['FILE_ATTACHED'] = DBEscapeString( $columns['FILE_ATTACHED'] );
			}

			$go = 0;

			foreach ( (array) $columns as $column => $value )
			{
				if ( ! empty( $value ) || $value == '0' )
				{
					if ( $column == 'AMOUNT' )
					{
						$value = preg_replace( '/[^0-9.-]/', '', $value );
					}

					$fields .= DBEscapeIdentifier( $column ) . ',';
					$values .= "'" . $value . "',";
					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

			if ( $go )
			{
				DBQuery( $sql );
			}
		}
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Income' ) ) )
	{
		$file_attached = DBGetOne( "SELECT FILE_ATTACHED
			FROM ACCOUNTING_INCOMES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		if ( ! empty( $file_attached )
			&& file_exists( $file_attached ) )
		{
			// Delete File Attached.
			unlink( $file_attached );
		}

		DBQuery( "DELETE FROM ACCOUNTING_INCOMES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$incomes_total = 0;

	$functions = [
		'REMOVE' => '_makeIncomesRemove',
		'ASSIGNED_DATE' => 'ProperDate',
		'COMMENTS' => '_makeIncomesTextInput',
		'AMOUNT' => '_makeIncomesAmount',
		'FILE_ATTACHED' => '_makeIncomesFileInput',
	];

	$incomes_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,f.COMMENTS,
		f.AMOUNT,f.FILE_ATTACHED
		FROM ACCOUNTING_INCOMES f
		WHERE f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY f.ASSIGNED_DATE", $functions );

	$i = 1;
	$RET = [];

	foreach ( (array) $incomes_RET as $income )
	{
		$RET[$i] = $income;
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
		'TITLE' => _( 'Income' ),
		'AMOUNT' => _( 'Amount' ),
		'ASSIGNED_DATE' => _( 'Assigned' ),
		'COMMENTS' => _( 'Comment' ),
	];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$columns += [ 'FILE_ATTACHED' => _( 'File Attached' ) ];
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$link['add']['html'] = [
			'REMOVE' => button( 'add' ),
			'TITLE' => _makeIncomesTextInput( '', 'TITLE' ),
			'AMOUNT' => _makeIncomesTextInput( '', 'AMOUNT' ),
			'ASSIGNED_DATE' => _makeIncomesDateInput( DBDate(), 'ASSIGNED_DATE' ),
			'COMMENTS' => _makeIncomesTextInput( '', 'COMMENTS' ),
			'FILE_ATTACHED' => _makeIncomesFileInput( '', 'FILE_ATTACHED' ),
		];
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		$options = [];
	}
	else
	{
		$options = [ 'center' => false ];
	}

	ListOutput( $RET, $columns, 'Income', 'Incomes', $link, [], $options );

	if ( ! $_REQUEST['print_statements']
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	$payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
		FROM ACCOUNTING_PAYMENTS p
		WHERE p.STAFF_ID IS NULL
		AND p.SYEAR='" . UserSyear() . "'
		AND p.SCHOOL_ID='" . UserSchool() . "'" );

	$table = '<table class="align-right"><tr><td>' . _( 'Total from Incomes' ) . ': ' . '</td><td>' . Currency( $incomes_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Expenses' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Balance' ) . ': <b>' . '</b></td><td><b id="update_balance">' . Currency(  ( $incomes_total - $payments_total ) ) . '</b></td></tr>';

	//add General Balance
	$table .= '<tr><td colspan="2"><hr /></td></tr><tr><td>' . _( 'Total from Incomes' ) . ': ' . '</td><td>' . Currency( $incomes_total ) . '</td></tr>';

	if ( $RosarioModules['Student_Billing'] )
	{
		$student_payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
			FROM BILLING_PAYMENTS p
			WHERE p.SYEAR='" . UserSyear() . "'
			AND p.SCHOOL_ID='" . UserSchool() . "'" );

		$table .= '<tr><td>& ' . _( 'Total from Student Payments' ) . ': ' . '</td><td>' . Currency( $student_payments_total ) . '</td></tr>';
	}
	else
	{
		$student_payments_total = 0;
	}

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Expenses' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

	$staff_payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
		FROM ACCOUNTING_PAYMENTS p
		WHERE p.STAFF_ID IS NOT NULL
		AND p.SYEAR='" . UserSyear() . "'
		AND p.SCHOOL_ID='" . UserSchool() . "'" );

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
