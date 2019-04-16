<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( UserStaffID()
		&& AllowEdit() )
	{
		if ( ! empty( $_REQUEST['food_service'] ) )
		{
			$sql = "UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET ";

			foreach ( (array) $_REQUEST['food_service'] as $column_name => $value )
			{
				$sql .= DBEscapeIdentifier( $column_name ) . "='" . trim( $value ) . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE STAFF_ID='" . UserStaffID() . "'";
			DBQuery( $sql );
		}
	}

	// $_REQUEST['modfunc'] = false;

	// Unset food service & redirect URL.
	RedirectURL( array( 'food_service' ) );
}

if ( ! $_REQUEST['modfunc']
	&& UserStaffID() )
{
	$staff = DBGet( "SELECT s.STAFF_ID,s.FIRST_NAME||' '||s.LAST_NAME,
	(SELECT s.STAFF_ID FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,
	(SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
	(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE,
	(SELECT BARCODE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BARCODE
	FROM STAFF s
	WHERE s.STAFF_ID='" . UserStaffID() . "'" );
	$staff = $staff[1];

	echo '<table class="width-100p">';
	echo '<tr>';
	echo '<td class="valign-top">';
	echo '<table class="width-100p"><tr>';

	echo '<td class="valign-top">' . NoInput(  ( $staff['BALANCE'] < 0 ? '<span style="color:red">' : '' ) . $staff['BALANCE'] . ( $staff['BALANCE'] < 0 ? '</span>' : '' ), 'Balance' );

	// warn if account non-existent (balance query failed)

	if ( ! $staff['ACCOUNT_ID'] )
	{
		echo '<br />' . MakeTipMessage(
			_( 'This user does not have a Meal Account.' ),
			_( 'Warning' ),
			button( 'warning' )
		);
	}

	echo '</td>';

	echo '</tr></table>';
	echo '</td></tr></table>';
	echo '<hr />';

	echo '<table class="width-100p fixed-col">';
	echo '<tr><td class="valign-top">';

	echo '<table class="width-100p">';
	echo '<tr>';
	$options = array( 'Inactive' => _( 'Inactive' ), 'Disabled' => _( 'Disabled' ), 'Closed' => _( 'Closed' ) );
	echo '<td>' . ( $staff['ACCOUNT_ID'] ? SelectInput( $staff['STATUS'], 'food_service[STATUS]', _( 'Status' ), $options, _( 'Active' ) ) : NoInput( '-', _( 'Status' ) ) ) . '</td>';
	echo '<td>' . ( $staff['ACCOUNT_ID'] ? TextInput( $staff['BARCODE'], 'food_service[BARCODE]', _( 'Barcode' ), 'size=12 maxlength=25' ) : NoInput( '-', _( 'Barcode' ) ) ) . '</td>';
	echo '</tr>';
	echo '</table>';

	echo '</td></tr>';
	echo '</table>';
}
