<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( UserStudentID()
		&& AllowEdit()
		&& ! empty( $_REQUEST['food_service'] ) )
	{
		$sql = "UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET ";

		foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
		{
			$sql .= DBEscapeIdentifier( $column_name ) . "='" . trim( $value ) . "',";
		}

		$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

		DBQuery( $sql );
	}

	// $_REQUEST['modfunc'] = false;

	// Unset food service & redirect URL.
	RedirectURL( array( 'food_service' ) );
}

if ( ! $_REQUEST['modfunc']
	&& UserStudentID() )
{
	$student = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		fssa.ACCOUNT_ID,fssa.STATUS,fssa.DISCOUNT,fssa.BARCODE,
		(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE
		FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa
		WHERE s.STUDENT_ID='" . UserStudentID() . "'
		AND fssa.STUDENT_ID=s.STUDENT_ID" );

	$student = $student[1];

	// Find other students associated with the same account.
	$xstudents = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME
		FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa
		WHERE fssa.ACCOUNT_ID='" . $student['ACCOUNT_ID'] . "'
		AND s.STUDENT_ID=fssa.STUDENT_ID
		AND s.STUDENT_ID!='" . UserStudentID() . "'" );

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo NoInput(  ( $student['BALANCE'] < 0 ? '<span style="color:red">' : '' ) . $student['BALANCE'] . ( $student['BALANCE'] < 0 ? '</span>' : '' ), _( 'Balance' ) );

	echo '</td></tr></table>';
	echo '<hr />';

	echo '<table class="width-100 valign-top fixed-col"><tr><td>';

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
	$options = array( 'Inactive' => _( 'Inactive' ), 'Disabled' => _( 'Disabled' ), 'Closed' => _( 'Closed' ) );
	echo '<td>' . SelectInput( $student['STATUS'], 'food_service[STATUS]', _( 'Status' ), $options, _( 'Active' ) ) . '</td>';
	echo '</tr><tr>';

	$options = array( 'Reduced' => _( 'Reduced' ), 'Free' => _( 'Free' ) );

	echo '<td>' . SelectInput( $student['DISCOUNT'], 'food_service[DISCOUNT]', _( 'Discount' ), $options, _( 'Full' ) ) . '</td>';
	echo '<td>' . TextInput( $student['BARCODE'], 'food_service[BARCODE]', _( 'Barcode' ), 'size=12 maxlength=25' ) . '</td>';
	echo '</tr>';
	echo '</table>';
}
