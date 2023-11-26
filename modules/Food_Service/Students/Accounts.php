<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

Widgets( 'fsa_discount' );
Widgets( 'fsa_status' );
Widgets( 'fsa_barcode' );
Widgets( 'fsa_account_id' );

$extra['SELECT'] .= ",(SELECT coalesce(STATUS,'" . DBEscapeString( _( 'Active' ) ) . "')
	FROM food_service_student_accounts
	WHERE STUDENT_ID=s.STUDENT_ID) AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID=(SELECT ACCOUNT_ID
	FROM food_service_student_accounts
	WHERE STUDENT_ID=s.STUDENT_ID)) AS BALANCE";

$extra['functions'] += [ 'BALANCE' => 'red' ];
$extra['columns_after'] = [ 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) ];

Search( 'student_id', $extra );

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
				FROM food_service_student_accounts
				WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'
				AND STUDENT_ID!='" . UserStudentID() . "'" );

			if ( $account_id )
			{
				$student_full_name = DBGetOne( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME
					FROM students s,food_service_student_accounts fssa
					WHERE s.STUDENT_ID=fssa.STUDENT_ID
					AND fssa.ACCOUNT_ID='" . (int) $account_id . "'" );

				$message = sprintf(
					_( "That barcode is already assigned to Student <b>%s</b>." ),
					$student_full_name
				) . ' ' .
				_( "Hit OK to reassign it to the current student or Cancel to cancel all changes." );
			}
			else
			{
				$account_id = DBGetOne( "SELECT STAFF_ID
					FROM food_service_staff_accounts
					WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );

				if ( $account_id )
				{
					$staff_full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
						FROM staff
						WHERE STAFF_ID='" . (int) $account_id . "'" );

					$message = sprintf(
						_( "That barcode is already assigned to User <b>%s</b>." ),
						$staff_full_name
					) . ' ' .
					_( "Hit OK to reassign it to the current student or Cancel to cancel all changes." );
				}
			}
		}

		if ( empty( $account_id )
			|| Prompt( 'Confirm', $question, $message ) )
		{
			if ( ! isset( $_REQUEST['food_service']['ACCOUNT_ID'] )
				|| ( (string) (int) $_REQUEST['food_service']['ACCOUNT_ID'] === $_REQUEST['food_service']['ACCOUNT_ID']
					&& $_REQUEST['food_service']['ACCOUNT_ID'] > 0 ) )
			{
				$sql = "UPDATE food_service_student_accounts SET ";

				foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
				{
					$sql .= DBEscapeIdentifier( $column_name ) . "='" . trim( $value ) . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

				if ( ! empty( $_REQUEST['food_service']['BARCODE'] ) )
				{
					DBQuery( "UPDATE food_service_student_accounts SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
					DBQuery( "UPDATE food_service_staff_accounts SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
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

if ( $_REQUEST['modfunc'] === 'create' )
{
	if ( UserStudentID()
		&& AllowEdit()
		&& ! DBGet( "SELECT 1
			FROM food_service_student_accounts
			WHERE STUDENT_ID='" . UserStudentID() . "'" ) )
	{
		if ( (string) (int) $_REQUEST['food_service']['ACCOUNT_ID'] === $_REQUEST['food_service']['ACCOUNT_ID']
				&& $_REQUEST['food_service']['ACCOUNT_ID'] > 0 )
		{
			DBInsert(
				'food_service_student_accounts',
				[ 'STUDENT_ID' => UserStudentID() ] + $_REQUEST['food_service']
			);

			// Fix SQL error, Check if Account ID already exists
			$account_id_exists = DBGetOne( "SELECT 1
				FROM food_service_accounts
				WHERE ACCOUNT_ID='" . (int) $_REQUEST['food_service']['ACCOUNT_ID'] . "'" );

			if ( ! $account_id_exists )
			{
				DBInsert(
					'food_service_accounts',
					[
						'ACCOUNT_ID' => (int) $_REQUEST['food_service']['ACCOUNT_ID'],
						'BALANCE' => '0.00',
						'TRANSACTION_ID' => '0',
					]
				);
			}
		}
		else
		{
			$error[] = _( 'Please enter valid Numeric data.' );
		}
	}

	// Unset modfunc & food service & redirect URL.
	RedirectURL( [ 'modfunc', 'food_service' ] );
}

// FJ fix SQL bug invalid numeric data
echo ErrorMessage( $error );

if ( UserStudentID() && ! $_REQUEST['modfunc'] )
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
	AND s.STUDENT_ID!='" . UserStudentID() . "'" .
		( ! empty( $_REQUEST['include_inactive'] ) ? '' :
			" AND exists(SELECT ''
		FROM student_enrollment
		WHERE STUDENT_ID=s.STUDENT_ID
		AND SYEAR='" . UserSyear() . "'
		AND (START_DATE<=CURRENT_DATE AND (END_DATE IS NULL OR CURRENT_DATE<=END_DATE)))" ) );

	if ( $student['ACCOUNT_ID'] )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=update&student_id=' . UserStudentID() ) . '" method="POST">';

		DrawHeader(
			CheckBoxOnclick(
				'include_inactive',
				_( 'Include Inactive Students in Shared Account' )
			),
			SubmitButton()
		);
	}
	else
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=create&student_id=' . UserStudentID() ) . '" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Create Account' ) ) );
	}

	echo '<br />';

	PopTable( 'header', _( 'Account Information' ), 'width="100%"' );

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo NoInput( $student['FULL_NAME'], $student['STUDENT_ID'] );

	echo '</td><td>';

	echo NoInput( red( $student['BALANCE'] ), _( 'Balance' ) );

	echo '</td></tr></table><hr>';

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo TextInput(
		$student['ACCOUNT_ID'],
		'food_service[ACCOUNT_ID]',
		_( 'Account ID' ),
		'type="number" required min="1" max="999999999"'
	);

	// warn if account non-existent (balance query failed)

	if ( $student['BALANCE'] == '' )
	{
		echo ' ' . MakeTipMessage(
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
			button( 'warning', '', '', 'bigger' )
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
	 * Student Account fields table after action hook
	 * Add your own fields
	 *
	 * @since 11.2
	 */
	do_action( 'Food_Service/Students/Accounts.php|table_after', [ $student ] );

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( $student['ACCOUNT_ID'] ? '' : _( 'Create Account' ) ) . '</div></form>';
}
