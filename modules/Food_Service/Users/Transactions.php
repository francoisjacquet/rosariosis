<?php
require_once 'ProgramFunctions/TipMessage.fnc.php';

StaffWidgets( 'fsa_status' );
StaffWidgets( 'fsa_barcode' );
StaffWidgets( 'fsa_exists_Y' );

$extra['SELECT'] .= ",(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE";
$extra['SELECT'] .= ",(SELECT STATUS FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS STATUS";
$extra['functions'] += [ 'BALANCE' => 'red' ];
$extra['columns_after'] = [ 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) ];

Search( 'staff_id', $extra );

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& $_REQUEST['modfunc'] === 'save' )
{
	if ( UserStaffID()
		&& AllowEdit() )
	{
		//$existing_account = DBGet( 'SELECT \'exists\' FROM food_service_staff_accounts WHERE STAFF_ID='.UserStaffID() );
		//if ( !count($existing_account))
		//	BackPrompt('That user does not have a Meal Account. Choose a different username and try again.');

		if (  ( $_REQUEST['values']['TYPE'] == 'Deposit' || $_REQUEST['values']['TYPE'] == 'Credit' || $_REQUEST['values']['TYPE'] == 'Debit' ) && ( $amount = is_money( $_REQUEST['values']['AMOUNT'] ) ) )
		{
			$fields = 'SYEAR,SCHOOL_ID,STAFF_ID,BALANCE,' . DBEscapeIdentifier( 'TIMESTAMP' ) . ',SHORT_NAME,DESCRIPTION,SELLER_ID';

			$values = "'" . UserSyear() . "','" . UserSchool() . "','" . UserStaffID() . "',
				(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID='" . UserStaffID() . "'),
				CURRENT_TIMESTAMP,'" . mb_strtoupper( $_REQUEST['values']['TYPE'] ) . "','" .
				$_REQUEST['values']['TYPE'] . "','" . User( 'STAFF_ID' ) . "'";

			$sql = "INSERT INTO food_service_staff_transactions (" . $fields . ") values (" . $values . ")";

			DBQuery( $sql );

			$transaction_id = DBLastInsertID();

			$full_description = DBEscapeString( _( $_REQUEST['values']['OPTION'] ) ) . ' ' . $_REQUEST['values']['DESCRIPTION'];

			DBInsert(
				'food_service_staff_transaction_items',
				[
					'ITEM_ID' => '0',
					'TRANSACTION_ID' => (int) $transaction_id,
					'AMOUNT' => ( $_REQUEST['values']['TYPE'] === 'Debit' ? -$amount : $amount ),
					'SHORT_NAME' => mb_strtoupper( $_REQUEST['values']['OPTION'] ),
					'DESCRIPTION' => $full_description,
				]
			);

			DBQuery( "UPDATE food_service_staff_accounts
				SET TRANSACTION_ID='" . (int) $transaction_id . "',BALANCE=BALANCE+(SELECT sum(AMOUNT)
					FROM food_service_staff_transaction_items
					WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
				WHERE STAFF_ID='" . UserStaffID() . "'" );
		}
		else
		{
			$error[] = _( 'Please enter valid Type and Amount.' );
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

echo ErrorMessage( $error );

if ( UserStaffID()
	&& ! $_REQUEST['modfunc'] )
{
	$staff = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
	(SELECT STAFF_ID FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,
	(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
	FROM staff s
	WHERE s.STAFF_ID='" . UserStaffID() . "'" );

	$staff = $staff[1];

	//$PHP_tmp_SELF = PreparePHP_SELF();
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&staff_id=' . UserStaffID() ) . '" method="POST">';

	DrawHeader( '', ResetButton( _( 'Cancel' ) ) . SubmitButton() );

	$staff_name_photo = MakeUserPhotoTipMessage( $staff['STAFF_ID'], $staff['FULL_NAME'] );

	DrawHeader(
		NoInput( $staff_name_photo, $staff['STAFF_ID'] ),
		NoInput( red( $staff['BALANCE'] ), _( 'Balance' ) )
	);

	if ( $staff['ACCOUNT_ID'] && $staff['BALANCE'] != '' )
	{
		$RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.DESCRIPTION AS TYPE,fsti.DESCRIPTION,fsti.AMOUNT
		FROM food_service_staff_transactions fst,food_service_staff_transaction_items fsti
		WHERE fst.SYEAR='" . UserSyear() . "'
		AND fst.STAFF_ID='" . UserStaffID() . "'
		AND fst.TIMESTAMP BETWEEN CURRENT_DATE
		AND (CURRENT_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '1 DAY' : "'1 DAY'" ) . ")
		AND fsti.TRANSACTION_ID=fst.TRANSACTION_ID
		ORDER BY fst.TRANSACTION_ID,fsti.ITEM_ID" );

		// TODO: code duplication!
		/**
		 * @param $type
		 * @return mixed
		 */
		function types_locale( $type )
		{
			$types = [ 'Deposit' => _( 'Deposit' ), 'Credit' => _( 'Credit' ), 'Debit' => _( 'Debit' ) ];

			if ( array_key_exists( $type, $types ) )
			{
				return $types[$type];
			}

			return $type;
		}

		/**
		 * @param $option
		 * @return mixed
		 */
		function options_locale( $option )
		{
			$options = [ 'Cash ' => _( 'Cash' ), 'Check' => _( 'Check' ), 'Credit Card' => _( 'Credit Card' ), 'Debit Card' => _( 'Debit Card' ), 'Transfer' => _( 'Transfer' ) ];

			if ( array_key_exists( $option, $options ) )
			{
				return $options[$option];
			}

			return $option;
		}

		foreach ( (array) $RET as $RET_key => $RET_val )
		{
			$RET_temp[$RET_key] = array_map( 'types_locale', $RET_val );
			$RET[$RET_key] = array_map( 'options_locale', $RET_temp[$RET_key] );
		}

		if ( AllowEdit() )
		{
			$types = [
				'Deposit' => _( 'Deposit' ),
				'Credit' => _( 'Credit' ),
				'Debit' => _( 'Debit' ),
			];

			$link['add']['html']['TYPE'] = SelectInput( '', 'values[TYPE]', '', $types, false );

			$options = [
				'Cash' => _( 'Cash' ),
				'Check' => _( 'Check' ),
				'Credit Card' => _( 'Credit Card' ),
				'Debit Card' => _( 'Debit Card' ),
				'Transfer' => _( 'Transfer' ),
			];

			$link['add']['html']['DESCRIPTION'] = SelectInput( '', 'values[OPTION]', '', $options, false ) . ' ' .
				TextInput( '', 'values[DESCRIPTION]', '', 'size=20 maxlength=50' );

			$link['add']['html']['AMOUNT'] = TextInput(
				'',
				'values[AMOUNT]',
				'',
				'type="number" step="0.01" max="999999999999" min="0" required'
			);

			$link['add']['html']['remove'] = button( 'add' );

			$link['remove']['link'] = "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&staff_id=' . UserStaffID();

			$link['remove']['variables'] = [ 'id' => 'TRANSACTION_ID' ];
		}

		$columns = [ 'TYPE' => _( 'Type' ), 'DESCRIPTION' => _( 'Description' ), 'AMOUNT' => _( 'Amount' ) ];

		ListOutput(
			$RET,
			$columns,
			'Earlier Transaction',
			'Earlier Transactions',
			$link,
			false,
			[ 'save' => false, 'search' => false ]
		);

		echo '<div class="center">' . SubmitButton() . '</div>';
	}
	else
	{
		echo ErrorMessage( [ _( 'This user does not have a Meal Account.' ) ] );
	}

	echo '</form>';
}
