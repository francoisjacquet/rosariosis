<?php
require_once 'ProgramFunctions/TipMessage.fnc.php';


Widgets( 'fsa_discount' );
Widgets( 'fsa_status' );
Widgets( 'fsa_barcode' );
Widgets( 'fsa_account_id' );

$extra['SELECT'] .= ",coalesce(fssa.STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE";

if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
{
	$extra['FROM'] .= ",food_service_student_accounts fssa";
	$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
}

$extra['functions'] += [ 'BALANCE' => 'red' ];
$extra['columns_after'] = [ 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) ];

Search( 'student_id', $extra );

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& $_REQUEST['modfunc'] === 'save' )
{
	if ( UserStudentID()
		&& AllowEdit() )
	{
		$account_id = DBGetOne( "SELECT ACCOUNT_ID
			FROM food_service_student_accounts
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		if (  ( $_REQUEST['values']['TYPE'] == 'Deposit' || $_REQUEST['values']['TYPE'] == 'Credit' || $_REQUEST['values']['TYPE'] == 'Debit' ) && ( $amount = is_money( $_REQUEST['values']['AMOUNT'] ) ) )
		{
			$fields = 'SYEAR,SCHOOL_ID,ACCOUNT_ID,BALANCE,' . DBEscapeIdentifier( 'TIMESTAMP' ) . ',SHORT_NAME,DESCRIPTION,SELLER_ID';

			$values = "'" . UserSyear() . "','" . UserSchool() . "','" . $account_id . "',
				(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID='" . (int) $account_id . "'),
				CURRENT_TIMESTAMP,'" . mb_strtoupper( $_REQUEST['values']['TYPE'] ) . "','" .
				$_REQUEST['values']['TYPE'] . "','" . User( 'STAFF_ID' ) . "'";

			$sql = "INSERT INTO food_service_transactions (" . $fields . ") values (" . $values . ")";

			DBQuery( $sql );

			$transaction_id = DBLastInsertID();

			$full_description = DBEscapeString( _( $_REQUEST['values']['OPTION'] ) ) . ' ' . $_REQUEST['values']['DESCRIPTION'];

			DBInsert(
				'food_service_transaction_items',
				[
					'ITEM_ID' => '0',
					'TRANSACTION_ID' => (int) $transaction_id,
					'AMOUNT' => ( $_REQUEST['values']['TYPE'] === 'Debit' ? -$amount : $amount ),
					'DISCOUNT' => '',
					'SHORT_NAME' => mb_strtoupper( $_REQUEST['values']['OPTION'] ),
					'DESCRIPTION' => $full_description,
				]
			);

			DBQuery( "UPDATE food_service_accounts
				SET TRANSACTION_ID='" . (int) $transaction_id . "',BALANCE=BALANCE+(SELECT sum(AMOUNT)
					FROM food_service_transaction_items
					WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
				WHERE ACCOUNT_ID='" . (int) $account_id . "'" );
		}
		else
		{
			$error[] = _( 'Please enter valid Type and Amount.' );
		}
	}

	// Unset modfunc & values redirect URL.
	RedirectURL( 'modfunc' );
}

echo ErrorMessage( $error );

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
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

	//$PHP_tmp_SELF = PreparePHP_SELF();
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&student_id=' . UserStudentID() ) . '" method="POST">';

	DrawHeader( '', ResetButton( _( 'Cancel' ) ) . SubmitButton() );

	$student_name_photo = MakeStudentPhotoTipMessage( $student['STUDENT_ID'], $student['FULL_NAME'] );

	DrawHeader(
		NoInput( $student_name_photo, $student['STUDENT_ID'] ),
		NoInput( red( $student['BALANCE'] ), _( 'Balance' ) )
	);

	if ( $student['BALANCE'] != '' )
	{
		$RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.DESCRIPTION AS TYPE,fsti.DESCRIPTION,fsti.AMOUNT
		FROM food_service_transactions fst,food_service_transaction_items fsti
		WHERE fst.SYEAR='" . UserSyear() . "'
		AND fst.ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'
		AND (fst.STUDENT_ID IS NULL OR fst.STUDENT_ID='" . UserStudentID() . "')
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

			$link['remove']['link'] = "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&student_id=' . UserStudentID();
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

		echo '<br /><div class="center">' . SubmitButton() . '</div>';
	}
	else
	{
		echo ErrorMessage( [ _( 'This student does not have a valid Meal Account.' ) ] );
	}

	echo '</form>';
}
