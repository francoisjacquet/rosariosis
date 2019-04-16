<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( UserStaffID()
		&& AllowEdit() )
	{
		if ( ! empty( $_REQUEST['submit']['delete'] ) )
		{
			if ( DeletePrompt( _( 'User Account' ) ) )
			{
				DBQuery( "DELETE FROM FOOD_SERVICE_STAFF_ACCOUNTS
					WHERE STAFF_ID='" . UserStaffID() . "'" );

				// Unset modfunc & redirect URL.
				RedirectURL( 'modfunc' );
			}

			//unset($_REQUEST['submit']);
		}
		elseif ( ! empty( $_REQUEST['food_service'] ) )
		{
			if ( ! empty( $_REQUEST['food_service']['BARCODE'] ) )
			{
				$question = _( 'Are you sure you want to assign that barcode?' );

				$account_id = DBGetOne( "SELECT STAFF_ID
					FROM FOOD_SERVICE_STAFF_ACCOUNTS
					WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'
					AND STAFF_ID!='" . UserStaffID() . "'" );

				if ( $account_id )
				{
					$staff_full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
						FROM STAFF
						WHERE STAFF_ID='" . $account_id . "'" );

					$message = sprintf(
						_( "That barcode is already assigned to User <b>%s</b>." ),
						$staff_full_name
					) . ' ' .
					_( "Hit OK to reassign it to the current user or Cancel to cancel all changes." );
				}
				else
				{
					$account_id = DBGetOne( "SELECT ACCOUNT_ID
						FROM FOOD_SERVICE_STUDENT_ACCOUNTS
						WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );

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
						_( "Hit OK to reassign it to the user student or Cancel to cancel all changes." );
					}
				}
			}

			if ( ! $account_id
				|| Prompt( 'Confirm', $question, $message ) )
			{
				$sql = 'UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET ';

				foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
				{
					$sql .= DBEscapeIdentifier( $column_name ) . "='" . trim( $value ) . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE STAFF_ID='" . UserStaffID() . "'";

				if ( ! empty( $_REQUEST['food_service']['BARCODE'] ) )
				{
					DBQuery( "UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
					DBQuery( "UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='" . trim( $_REQUEST['food_service']['BARCODE'] ) . "'" );
				}

				DBQuery( $sql );

				// Unset modfunc redirect URL.
				RedirectURL( 'modfunc' );
			}
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
	if ( UserStaffID()
		&& AllowEdit()
		&& ! DBGet( "SELECT 1
			FROM FOOD_SERVICE_STAFF_ACCOUNTS
			WHERE STAFF_ID='" . UserStaffID() . "'" ) )
	{
		$fields = 'STAFF_ID,BALANCE,TRANSACTION_ID,';
		$values = "'" . UserStaffID() . "','0.00','0',";

		foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
		{
			$fields .= DBEscapeIdentifier( $column_name ) . ',';

			$values .= "'" . trim( $value ) . "',";
		}

		$sql = 'INSERT INTO FOOD_SERVICE_STAFF_ACCOUNTS (' . mb_substr( $fields, 0, -1 ) .
		') VALUES (' . mb_substr( $values, 0, -1 ) . ')';

		DBQuery( $sql );
	}

	// Unset modfunc & food service & redirect URL.
	RedirectURL( array( 'modfunc', 'food_service' ) );
}

StaffWidgets( 'fsa_balance' );
StaffWidgets( 'fsa_status' );
StaffWidgets( 'fsa_barcode' );
StaffWidgets( 'fsa_exists_Y' );

$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE";
$extra['SELECT'] .= ",(SELECT coalesce(STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS";
$extra['functions'] += array( 'BALANCE' => 'red' );
$extra['columns_after'] = array( 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) );

Search( 'staff_id', $extra );

if ( UserStaffID() && ! $_REQUEST['modfunc'] )
{
	$staff = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
	(SELECT s.STAFF_ID FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,
	(SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
	(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE,
	(SELECT BARCODE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BARCODE
	FROM STAFF s
	WHERE s.STAFF_ID='" . UserStaffID() . "'" );

	$staff = $staff[1];

	if ( $staff['ACCOUNT_ID'] )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

		DrawHeader(
			'',
			SubmitButton( _( 'Save' ), 'submit[save]' ) .
			( $staff['BALANCE'] == 0 ?
				SubmitButton( _( 'Delete Account' ), 'submit[delete]', '' ) : // No .primary button class.
				''
			)
		);
	}
	else
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=create" method="POST">';
		DrawHeader( '', SubmitButton( _( 'Create Account' ) ) );
	}

	echo '<br />';
	PopTable( 'header', _( 'Account Information' ), 'width="100%"' );

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	echo NoInput( $staff['FULL_NAME'], $staff['STAFF_ID'] );

	// warn if other users associated with the same account

	if ( ! $staff['ACCOUNT_ID'] )
	{
		echo '<br />' . MakeTipMessage(
			_( 'This user does not have a Meal Account.' ),
			_( 'Warning' ),
			button( 'warning' )
		);
	}

	echo '</td><td>';

	echo NoInput( red( $staff['BALANCE'] ), _( 'Balance' ) );

	echo '</td></tr></table>';
	echo '<hr />';

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	$options = array( 'Inactive' => _( 'Inactive' ), 'Disabled' => _( 'Disabled' ), 'Closed' => _( 'Closed' ) );
	echo ( $staff['ACCOUNT_ID'] ? SelectInput( $staff['STATUS'], 'food_service[STATUS]', _( 'Status' ), $options, _( 'Active' ) ) : NoInput( '-', _( 'Status' ) ) );
	echo '</td>';
	echo '<td>';
	echo ( $staff['ACCOUNT_ID'] ? TextInput( $staff['BARCODE'], 'food_service[BARCODE]', _( 'Barcode' ), 'size=12 maxlength=25' ) : NoInput( '-', _( 'Barcode' ) ) );
	echo '</td>';
	echo '</tr></table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}
