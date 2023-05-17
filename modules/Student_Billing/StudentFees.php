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
			$columns['FILE_ATTACHED'] = _saveFeesFile( $id );

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
				'billing_fees',
				$columns,
				[ 'STUDENT_ID' => UserStudentID(), 'ID' => (int) $id ]
			);
		}

		// New: check for Title & Amount.
		elseif ( $columns['TITLE']
			&& $columns['AMOUNT'] != '' )
		{
			$insert_columns = [
				'STUDENT_ID' => UserStudentID(),
				'SCHOOL_ID' => UserSchool(),
				'SYEAR' => UserSyear(),
				'ASSIGNED_DATE' => DBDate(),
			];

			$columns['FILE_ATTACHED'] = _saveFeesFile( $id );

			$columns['AMOUNT'] = preg_replace( '/[^0-9.-]/', '', $columns['AMOUNT'] );

			DBInsert(
				'billing_fees',
				$insert_columns + $columns
			);
		}
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Fee' ) ) )
	{
		$file_attached = DBGetOne( "SELECT FILE_ATTACHED
			FROM billing_fees
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		if ( ! empty( $file_attached )
			&& file_exists( $file_attached ) )
		{
			// Delete File Attached.
			unlink( $file_attached );
		}

		$delete_sql = "DELETE FROM billing_fees
			WHERE ID='" . (int) $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM billing_fees
			WHERE WAIVED_FEE_ID='" . (int) $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'waive'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Fee' ), _( 'Waive' ) ) )
	{
		$fee_RET = DBGet( "SELECT TITLE,AMOUNT
			FROM billing_fees
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		DBInsert(
			'billing_fees',
			[
				'SYEAR' => UserSyear(),
				'SCHOOL_ID' => UserSchool(),
				'TITLE' => DBEscapeString( $fee_RET[1]['TITLE'] . " " . _( 'Waiver' ) ),
				'AMOUNT' => ( $fee_RET[1]['AMOUNT'] * -1 ),
				'WAIVED_FEE_ID' => (int) $_REQUEST['id'],
				'STUDENT_ID' => UserStudentID(),
				'ASSIGNED_DATE' => DBDate(),
				'COMMENTS' => DBEscapeString( _( 'Waiver' ) ),
			]
		);

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

echo ErrorMessage( $error );

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	$fees_total = 0;

	$functions = [
		'REMOVE' => '_makeFeesRemove',
		'ASSIGNED_DATE' => 'ProperDate',
		'DUE_DATE' => '_makeFeesDateInput',
		'COMMENTS' => '_makeFeesTextInput',
		'AMOUNT' => '_makeFeesAmount',
		'FILE_ATTACHED' => '_makeFeesFileInput',
	];

	$waived_fees_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,
		f.DUE_DATE,f.COMMENTS,f.AMOUNT,f.WAIVED_FEE_ID,f.FILE_ATTACHED
		FROM billing_fees f
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND f.WAIVED_FEE_ID IS NOT NULL", $functions, [ 'WAIVED_FEE_ID' ] );

	$fees_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,
		f.DUE_DATE,f.COMMENTS,f.AMOUNT,f.WAIVED_FEE_ID,f.FILE_ATTACHED
		FROM billing_fees f
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND f.WAIVED_FEE_ID IS NULL
		ORDER BY f.ASSIGNED_DATE", $functions );

	$i = 1;
	$RET = [];

	foreach ( (array) $fees_RET as $fee )
	{
		$RET[$i] = $fee;

		if ( ! empty( $waived_fees_RET[$fee['ID']] ) )
		{
			$i++;
			$RET[$i] = ( $waived_fees_RET[$fee['ID']][1] + [ 'row_color' => '00FF66' ] );
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
		'TITLE' => _( 'Fee' ),
		'AMOUNT' => _( 'Amount' ),
		'ASSIGNED_DATE' => _( 'Assigned' ),
		'DUE_DATE' => _( 'Due' ),
		'COMMENTS' => _( 'Comment' ),
	];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$columns += [ 'FILE_ATTACHED' => _( 'File Attached' ) ];
	}

	$link = [];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$link['add']['html'] = [
			'REMOVE' => button( 'add' ),
			'TITLE' => _makeFeesTextInput( '', 'TITLE' ),
			'AMOUNT' => _makeFeesTextInput( '', 'AMOUNT' ),
			'ASSIGNED_DATE' => ProperDate( DBDate() ),
			'DUE_DATE' => _makeFeesDateInput( '', 'DUE_DATE' ),
			'COMMENTS' => _makeFeesTextInput( '', 'COMMENTS' ),
			'FILE_ATTACHED' => _makeFeesFileInput( '', 'FILE_ATTACHED' ),
		];
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';
		//DrawStudentHeader();

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

	// Do hook.
	do_action( 'Student_Billing/StudentFees.php|student_fees_header' );

	ListOutput( $RET, $columns, 'Fee', 'Fees', $link, [], $options );

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
			FROM billing_payments p
			WHERE p.STUDENT_ID='" . UserStudentID() . "'
			AND p.SYEAR='" . UserSyear() . "'" );

		$table = '<table class="align-right student-billing-totals"><tr><td>' . _( 'Total from Fees' ) . ': ' . '</td><td>' . Currency( $fees_total ) . '</td></tr>';

		$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Payments' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

		$table .= '<tr><td>' . _( 'Balance' ) . ': </td>
			<td><b>' . Currency(  ( $fees_total - $payments_total ), 'CR' ) .
			'</b></td></tr></table>';

		DrawHeader( $table );

		echo '</form>';
	}
}
