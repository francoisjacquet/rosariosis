<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( UserStudentID()
		&& AllowEdit()
		&& ! empty( $_REQUEST['food_service'] )
		&& ! empty( $_POST['food_service'] ) )
	{
		if ( ! empty( $_REQUEST['food_service']['BARCODE'] ) )
		{
			$question = _( 'Are you sure you want to assign that barcode?' );

			$account_id = DBGetOne( "SELECT ACCOUNT_ID
				FROM FOOD_SERVICE_STUDENT_ACCOUNTS
				WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'
				AND STUDENT_ID!='" . UserStudentID() . "'" );

			if ( $account_id )
			{
				$student_full_name = DBGetOne( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME
					FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa
					WHERE s.STUDENT_ID=fssa.STUDENT_ID
					AND fssa.ACCOUNT_ID='" . $account_id . "'" );

				$message = sprintf(
					_( "That barcode is already assigned to Student <b>%s</b>." ),
					$student_full_name
				) . ' ' .
				_( "Hit OK to reassign it to the current student or Cancel to cancel all changes." );
			}
			else
			{
				$account_id = DBGetOne( "SELECT STAFF_ID
					FROM FOOD_SERVICE_STAFF_ACCOUNTS
					WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );

				if ( $account_id )
				{
					$staff_full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
						FROM STAFF
						WHERE STAFF_ID='" . $account_id . "'" );

					$message = sprintf(
						_( "That barcode is already assigned to User <b>%s</b>." ),
						$staff_full_name
					) . ' ' .
					_( "Hit OK to reassign it to the current student or Cancel to cancel all changes." );
				}
			}
		}

		if ( ! $account_id
			|| Prompt( 'Confirm', $question, $message ) )
		{
			if ( ! isset( $_REQUEST['food_service']['ACCOUNT_ID'] )
				|| ( (string) (int) $_REQUEST['food_service']['ACCOUNT_ID'] === $_REQUEST['food_service']['ACCOUNT_ID']
					&& $_REQUEST['food_service']['ACCOUNT_ID'] > 0 ) )
			{
				$sql = "UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET ";

				foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
				{
					$sql .= DBEscapeIdentifier( $column_name ) . "='" . trim( $value ) . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

				if ( ! empty( $_REQUEST['food_service']['BARCODE'] ) )
				{
					DBQuery( "UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
					DBQuery( "UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
				}

				DBQuery( $sql );
			}
			else
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}

			// Unset modfunc & redirect URL.
			RedirectURL( 'modfunc' );
		}
	}
	else
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

Widgets( 'fsa_discount' );
Widgets( 'fsa_status' );
Widgets( 'fsa_barcode' );
Widgets( 'fsa_account_id' );

$extra['SELECT'] .= ",coalesce(fssa.STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE";

if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
{
	$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
	$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
}

$extra['functions'] += array( 'BALANCE' => 'red' );
$extra['columns_after'] = array( 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) );

Search( 'student_id', $extra );

// FJ fix SQL bug invalid numeric data
echo ErrorMessage( $error );

if ( UserStudentID() && ! $_REQUEST['modfunc'] )
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
	AND s.STUDENT_ID!='" . UserStudentID() . "'" .
		( $_REQUEST['include_inactive'] ? '' :
			" AND exists(SELECT ''
		FROM STUDENT_ENROLLMENT
		WHERE STUDENT_ID=s.STUDENT_ID
		AND SYEAR='" . UserSyear() . "'
		AND (START_DATE<=CURRENT_DATE AND (END_DATE IS NULL OR CURRENT_DATE<=END_DATE)))" ) );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

	DrawHeader(
		CheckBoxOnclick(
			'include_inactive',
			_( 'Include Inactive Students in Shared Account' )
		),
		SubmitButton()
	);

	echo '<br />';

	PopTable( 'header', _( 'Account Information' ), 'width="100%"' );

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo NoInput( $student['FULL_NAME'], '<b>' . $student['STUDENT_ID'] . '</b>' );

	echo '</td><td>';

	echo NoInput( red( $student['BALANCE'] ), _( 'Balance' ) );

	echo '</td></tr></table>';
	echo '<hr />';

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
			button( 'warning', '', '', 'bigger' )
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
			button( 'warning', '', '', 'bigger' )
		);
	}

	echo '</td>';
	$options = array( 'Inactive' => _( 'Inactive' ), 'Disabled' => _( 'Disabled' ), 'Closed' => _( 'Closed' ) );
	echo '<td>' . SelectInput( $student['STATUS'], 'food_service[STATUS]', _( 'Status' ), $options, _( 'Active' ) ) . '</td>';
	echo '</tr><tr>';
	$options = array( 'Reduced' => _( 'Reduced' ), 'Free' => _( 'Free' ) );
	echo '<td>' . SelectInput( $student['DISCOUNT'], 'food_service[DISCOUNT]', _( 'Discount' ), $options, _( 'Full' ) ) . '</td>';
	echo '<td>' . TextInput( $student['BARCODE'], 'food_service[BARCODE]', _( 'Barcode' ), 'size=12 maxlength=25' ) . '</td>';
	echo '</tr></table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}
