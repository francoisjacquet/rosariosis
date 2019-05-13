<?php

require_once 'modules/Student_Billing/functions.inc.php';

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );

	Search( 'student_id', $extra );
}

if ( $_REQUEST['values']
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
			$sql = "UPDATE BILLING_FEES SET ";

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "' AND ID='" . $id . "'";
			DBQuery( $sql );
		}

		// New: check for Title.
		elseif ( $columns['TITLE'] )
		{
			$sql = "INSERT INTO BILLING_FEES ";

			$fields = 'ID,STUDENT_ID,SCHOOL_ID,SYEAR,ASSIGNED_DATE,';
			$values = db_seq_nextval( 'BILLING_FEES_SEQ' ) . ",'" . UserStudentID() . "','" . UserSchool() . "','" . UserSyear() . "','" . DBDate() . "',";

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
	if ( DeletePrompt( _( 'Fee' ) ) )
	{
		$delete_sql = "DELETE FROM BILLING_FEES
			WHERE ID='" . $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM BILLING_FEES
			WHERE WAIVED_FEE_ID='" . $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'waive'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Fee' ), _( 'Waive' ) ) )
	{
		$fee_RET = DBGet( "SELECT TITLE,AMOUNT
			FROM BILLING_FEES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		DBQuery( "INSERT INTO BILLING_FEES (ID,SYEAR,SCHOOL_ID,TITLE,AMOUNT,WAIVED_FEE_ID,
			STUDENT_ID,ASSIGNED_DATE,COMMENTS)
			VALUES (" .
			db_seq_nextval( 'BILLING_FEES_SEQ' ) . ",'" .
			UserSyear() . "','" .
			UserSchool() . "','" .
			DBEscapeString( $fee_RET[1]['TITLE'] . " " . _( 'Waiver' ) ) . "','" .
			( $fee_RET[1]['AMOUNT'] * -1 ) . "','" .
			$_REQUEST['id'] . "','" .
			UserStudentID() . "','" .
			DBDate() . "','" .
			DBEscapeString( _( 'Waiver' ) ) . "')" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	$fees_total = 0;

	$functions = array(
		'REMOVE' => '_makeFeesRemove',
		'ASSIGNED_DATE' => 'ProperDate',
		'DUE_DATE' => '_makeFeesDateInput',
		'COMMENTS' => '_makeFeesTextInput',
		'AMOUNT' => '_makeFeesAmount',
	);

	$waived_fees_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,
		f.DUE_DATE,f.COMMENTS,f.AMOUNT,f.WAIVED_FEE_ID
		FROM BILLING_FEES f
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND f.WAIVED_FEE_ID IS NOT NULL", $functions, array( 'WAIVED_FEE_ID' ) );

	$fees_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,
		f.DUE_DATE,f.COMMENTS,f.AMOUNT,f.WAIVED_FEE_ID
		FROM BILLING_FEES f
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND (f.WAIVED_FEE_ID IS NULL OR f.WAIVED_FEE_ID='')
		ORDER BY f.ASSIGNED_DATE", $functions );

	$i = 1;
	$RET = array();

	foreach ( (array) $fees_RET as $fee )
	{
		$RET[$i] = $fee;

		if ( $waived_fees_RET[$fee['ID']] )
		{
			$i++;
			$RET[$i] = ( $waived_fees_RET[$fee['ID']][1] + array( 'row_color' => '00FF66' ) );
		}

		$i++;
	}

	if ( ! empty( $RET ) && ! $_REQUEST['print_statements'] && AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$columns = array( 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' );
	}
	else
	{
		$columns = array();
	}

	$columns += array(
		'TITLE' => _( 'Fee' ),
		'AMOUNT' => _( 'Amount' ),
		'ASSIGNED_DATE' => _( 'Assigned' ),
		'DUE_DATE' => _( 'Due' ),
		'COMMENTS' => _( 'Comment' ),
	);

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$link['add']['html'] = array(
			'REMOVE' => button( 'add' ),
			'TITLE' => _makeFeesTextInput( '', 'TITLE' ),
			'AMOUNT' => _makeFeesTextInput( '', 'AMOUNT' ),
			'ASSIGNED_DATE' => ProperDate( DBDate() ),
			'DUE_DATE' => _makeFeesDateInput( '', 'DUE_DATE' ),
			'COMMENTS' => _makeFeesTextInput( '', 'COMMENTS' ),
		);
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';
		//DrawStudentHeader();

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		$options = array();
	}
	else
	{
		$options = array( 'center' => false );
	}

	// Do hook.
	do_action( 'Student_Billing/StudentFees.php|student_fees_header' );

	ListOutput( $RET, $columns, 'Fee', 'Fees', $link, array(), $options );

	if ( ! $_REQUEST['print_statements']
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
			FROM BILLING_PAYMENTS p
			WHERE p.STUDENT_ID='" . UserStudentID() . "'
			AND p.SYEAR='" . UserSyear() . "'" );

		$table = '<table class="align-right"><tr><td>' . _( 'Total from Fees' ) . ': ' . '</td><td>' . Currency( $fees_total ) . '</td></tr>';

		$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Payments' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

		$table .= '<tr><td>' . _( 'Balance' ) . ': </td>
			<td><b>' . Currency(  ( $fees_total - $payments_total ), 'CR' ) .
			'</b></td></tr></table>';

		DrawHeader( $table );

		echo '</form>';
	}
}
