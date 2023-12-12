<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

StaffWidgets( 'fsa_balance' );
StaffWidgets( 'fsa_status' );
StaffWidgets( 'fsa_barcode' );
StaffWidgets( 'fsa_exists_Y' );

$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
$extra['SELECT'] .= ",(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE";
$extra['SELECT'] .= ",(SELECT coalesce(STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS STATUS";
$extra['functions'] += [ 'BALANCE' => 'red' ];
$extra['columns_after'] = [ 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) ];

Search( 'staff_id', $extra );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( UserStaffID()
		&& AllowEdit()
		&& ! empty( $_REQUEST['food_service'] ) )
	{
		if ( ! empty( $_REQUEST['food_service']['BARCODE'] ) )
		{
			$question = _( 'Are you sure you want to assign that barcode?' );

			$account_id = DBGetOne( "SELECT STAFF_ID
				FROM food_service_staff_accounts
				WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'
				AND STAFF_ID!='" . UserStaffID() . "'" );

			if ( $account_id )
			{
				$staff_full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
					FROM staff
					WHERE STAFF_ID='" . (int) $account_id . "'" );

				$message = sprintf(
					_( "That barcode is already assigned to User <b>%s</b>." ),
					$staff_full_name
				) . ' ' .
				_( "Hit OK to reassign it to the current user or Cancel to cancel all changes." );
			}
			else
			{
				$account_id = DBGetOne( "SELECT ACCOUNT_ID
					FROM food_service_student_accounts
					WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );

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
					_( "Hit OK to reassign it to the user student or Cancel to cancel all changes." );
				}
			}
		}

		if ( empty( $account_id )
			|| Prompt( 'Confirm', $question, $message ) )
		{
			$sql = 'UPDATE food_service_staff_accounts SET ';

			foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
			{
				$sql .= DBEscapeIdentifier( $column_name ) . "='" . trim( $value ) . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE STAFF_ID='" . UserStaffID() . "'";

			if ( ! empty( $_REQUEST['food_service']['BARCODE'] ) )
			{
				DBQuery( "UPDATE food_service_staff_accounts SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
				DBQuery( "UPDATE food_service_student_accounts SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
			}

			DBQuery( $sql );

			// Unset modfunc redirect URL.
			RedirectURL( 'modfunc' );
		}
	}
	else
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( $_REQUEST['modfunc'] === 'delete' )
{
	if ( DeletePrompt( _( 'User Account' ) ) )
	{
		DBQuery( "DELETE FROM food_service_staff_accounts
			WHERE STAFF_ID='" . UserStaffID() . "'" );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( $_REQUEST['modfunc'] === 'create' )
{
	if ( UserStaffID()
		&& AllowEdit()
		&& ! DBGet( "SELECT 1
			FROM food_service_staff_accounts
			WHERE STAFF_ID='" . UserStaffID() . "'" ) )
	{
		DBInsert(
			'food_service_staff_accounts',
			[
				'STAFF_ID' => UserStaffID(),
				'BALANCE' => '0.00',
				'TRANSACTION_ID' => '0',
			] + $_REQUEST['food_service']
		);
	}

	// Unset modfunc & food service & redirect URL.
	RedirectURL( [ 'modfunc', 'food_service' ] );
}

if ( UserStaffID() && ! $_REQUEST['modfunc'] )
{
	$staff = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
	(SELECT s.STAFF_ID FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,
	(SELECT STATUS FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
	(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE,
	(SELECT BARCODE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BARCODE
	FROM staff s
	WHERE s.STAFF_ID='" . UserStaffID() . "'" );

	$staff = $staff[1];

	if ( $staff['ACCOUNT_ID'] )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=update&staff_id=' . UserStaffID() ) . '" method="POST">';

		DrawHeader(
			'',
			SubmitButton() .
			( $staff['BALANCE'] == 0 && AllowEdit() ?
				'<input type="button" value="' .
					AttrEscape( _( 'Delete Account' ) ) .
					// Change form action's modfunc to delete.
					'" onclick="ajaxLink(this.form.action.replace(\'modfunc=update\',\'modfunc=delete\'));" />'
				: ''
			)
		);
	}
	else
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=create&staff_id=' . UserStaffID() ) . '" method="POST">';
		DrawHeader( '', SubmitButton( _( 'Create Account' ) ) );
	}

	echo '<br />';
	PopTable( 'header', _( 'Account Information' ), 'width="100%"' );

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo NoInput( $staff['FULL_NAME'], $staff['STAFF_ID'] );

	// warn if other users associated with the same account

	if ( ! $staff['ACCOUNT_ID'] )
	{
		echo ' ' . MakeTipMessage(
			_( 'This user does not have a Meal Account.' ),
			_( 'Warning' ),
			button( 'warning' )
		);
	}

	echo '</td><td>';

	echo NoInput( red( $staff['BALANCE'] ), _( 'Balance' ) );

	echo '</td></tr></table><hr>';

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	$options = [ 'Inactive' => _( 'Inactive' ), 'Disabled' => _( 'Disabled' ), 'Closed' => _( 'Closed' ) ];

	echo SelectInput(
		$staff['STATUS'],
		'food_service[STATUS]',
		_( 'Status' ),
		$options,
		_( 'Active' )
	) . '</td><td>';

	echo TextInput(
		$staff['BARCODE'],
		'food_service[BARCODE]',
		_( 'Barcode' ),
		'size=12 maxlength=25'
	) . '</td></tr></table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( $staff['ACCOUNT_ID'] ? '' : _( 'Create Account' ) ) . '</div></form>';
}
