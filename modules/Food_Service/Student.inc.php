<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( UserStudentID()
		&& AllowEdit()
		&& ! empty( $_REQUEST['food_service'] ) )
	{
		$sql = "UPDATE food_service_student_accounts SET ";

		foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
		{
			$sql .= DBEscapeIdentifier( $column_name ) . "='" . trim( $value ) . "',";
		}

		$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

		DBQuery( $sql );
	}

	// $_REQUEST['modfunc'] = false;

	// Unset food service & redirect URL.
	RedirectURL( [ 'food_service' ] );
}

if ( ! $_REQUEST['modfunc']
	&& UserStudentID() )
{
	$student = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID=(SELECT ACCOUNT_ID
			FROM food_service_student_accounts
			WHERE STUDENT_ID=s.STUDENT_ID)) AS BALANCE,
		(SELECT ACCOUNT_ID FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS ACCOUNT_ID,
		(SELECT STATUS FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS STATUS,
		(SELECT DISCOUNT FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS DISCOUNT,
		(SELECT BARCODE FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS BARCODE
		FROM students s
		WHERE s.STUDENT_ID='" . UserStudentID() . "'" );

	$student = $student[1];

	// Find other students associated with the same account.
	$xstudents = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME
		FROM students s,food_service_student_accounts fssa
		WHERE fssa.ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'
		AND s.STUDENT_ID=fssa.STUDENT_ID
		AND s.STUDENT_ID!='" . UserStudentID() . "'" );

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo NoInput(  ( $student['BALANCE'] < 0 ? '<span style="color:red">' : '' ) . $student['BALANCE'] . ( $student['BALANCE'] < 0 ? '</span>' : '' ), _( 'Balance' ) );

	echo '</td></tr></table>';
	echo '<hr>';

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo TextInput(
		$student['ACCOUNT_ID'],
		'food_service[ACCOUNT_ID]',
		_( 'Account ID' ),
		'required size=12 maxlength=10'
	);

	// warn if account non-existent (balance query failed)

	if ( $student['BALANCE'] == '' )
	{
		echo MakeTipMessage(
			_( 'Non-existent account!' ),
			_( 'Warning' ),
			button( 'warning' )
		);
	}

	// warn if other students associated with the same account

	if ( ! empty( $xstudents ) )
	{
		$warning = _( 'Other students associated with the same account' ) . ':<br />';

		foreach ( (array) $xstudents as $xstudent )
		{
			$warning .= '&nbsp;' . $xstudent['FULL_NAME'] . '<br />';
		}

		echo MakeTipMessage(
			$warning,
			_( 'Warning' ),
			button( 'warning' )
		);
	}

	echo '</td>';

	$options = [ 'Inactive' => _( 'Inactive' ), 'Disabled' => _( 'Disabled' ), 'Closed' => _( 'Closed' ) ];

	echo '<td>' . SelectInput(
		$student['STATUS'],
		'food_service[STATUS]',
		_( 'Status' ),
		$options,
		_( 'Active' )
	) . '</td></tr>';

	$options = [ 'Reduced' => _( 'Reduced' ), 'Free' => _( 'Free' ) ];

	echo '<tr><td>' . SelectInput(
		$student['DISCOUNT'],
		'food_service[DISCOUNT]',
		_( 'Discount' ),
		$options,
		_( 'Full' )
	) . '</td>';

	echo '<td>' . TextInput(
		$student['BARCODE'],
		'food_service[BARCODE]',
		_( 'Barcode' ),
		'size=12 maxlength=25'
	) . '</td></tr></table>';

	/**
	 * Food Service tab fields table after action hook
	 * Add your own fields
	 *
	 * @since 11.2
	 */
	do_action( 'Food_Service/Student.inc.php|table_after', [ $student ] );
}
