<?php

$_REQUEST['detailed_view'] = issetVal( $_REQUEST['detailed_view'], '' );

StaffWidgets( 'fsa_status' );
StaffWidgets( 'fsa_barcode' );
StaffWidgets( 'fsa_exists_Y' );

$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
$extra['SELECT'] .= ",(SELECT coalesce(STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS STATUS";

$extra['SELECT'] .= ",(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE";

$extra['functions'] += [ 'BALANCE' => 'red' ];

$extra['columns_after'] = [ 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) ];

Search( 'staff_id', $extra );

if ( UserStaffID() && ! $_REQUEST['modfunc'] )
{
	$staff = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		(SELECT STAFF_ID FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,
		(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
		FROM staff s
		WHERE s.STAFF_ID='" . UserStaffID() . "'" );

	$staff = $staff[1];

	echo '<form action="' . PreparePHP_SELF() . '" method="POST">';

	DrawHeader(
		_( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start' ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end' ) . ' ' .
		$type_select .
		' ' . Buttons( _( 'Go' ) )
	);

	echo '</form>';

//FJ fix bug no balance
	//	DrawHeader(NoInput($staff['FULL_NAME'],'&nbsp;'.$staff['STAFF_ID']),'', NoInput(red($student['BALANCE']),_('Balance')));
	DrawHeader(
		NoInput( $staff['FULL_NAME'], $staff['STAFF_ID'] ),
		NoInput( red( $staff['BALANCE'] ), _( 'Balance' ) )
	);

	if ( $_REQUEST['detailed_view'] != 'true' )
	{
		DrawHeader( "<a href=" . PreparePHP_SELF( $_REQUEST, [], [ 'detailed_view' => 'true' ] ) . ">" . _( 'Detailed View' ) . "</a>" );
	}
	else
	{
		DrawHeader( "<a href=" . PreparePHP_SELF( $_REQUEST, [], [ 'detailed_view' => 'false' ] ) . ">" . _( 'Original View' ) . "</a>" );
	}

	if ( $staff['ACCOUNT_ID'] && $staff['BALANCE'] != '' )
	{
		$where = '';

		if ( ! empty( $_REQUEST['type_select'] ) )
		{
			$where = " AND fst.SHORT_NAME='" . $_REQUEST['type_select'] . "'";
		}

		if ( $_REQUEST['detailed_view'] == 'true' )
		{
			$RET = DBGet( "SELECT fst.TRANSACTION_ID AS TRANS_ID,fst.TRANSACTION_ID,
			(SELECT sum(AMOUNT) FROM food_service_staff_transaction_items WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			fst.STAFF_ID,fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
				db_case( [
					'fst.SELLER_ID',
					"''",
					'NULL',
					"(SELECT " . DisplayNameSQL() . " FROM staff WHERE STAFF_ID=fst.SELLER_ID)",
				] ) . " AS SELLER
			FROM food_service_staff_transactions fst
			WHERE fst.STAFF_ID='" . UserStaffID() . "'
			AND fst.SYEAR='" . UserSyear() . "'
			AND fst.TIMESTAMP BETWEEN '" . $start_date . "'	AND date '" . $end_date . "' +1" .
				$where . "
			ORDER BY fst.TRANSACTION_ID DESC", [ 'DATE' => 'ProperDateTime', 'BALANCE' => 'red' ] );

			foreach ( (array) $RET as $RET_key => $RET_val )
			{
				$RET[$RET_key] = array_map( 'types_locale', $RET_val );
			}

			// get details of each transaction

			foreach ( (array) $RET as $key => $value )
			{
				$tmpRET = DBGet( "SELECT TRANSACTION_ID AS TRANS_ID,
					ITEM_ID,TRANSACTION_ID,AMOUNT,SHORT_NAME,DESCRIPTION
					FROM food_service_staff_transaction_items
					WHERE TRANSACTION_ID='" . (int) $value['TRANSACTION_ID'] . "'" );

				foreach ( (array) $tmpRET as $RET_key => $RET_val )
				{
					$tmpRET[$RET_key] = array_map( 'options_locale', $RET_val );
				}

				// merge transaction and detail records
				$RET[$key] = [ $RET[$key] ] + $tmpRET;
			}

			$columns = [
				'TRANSACTION_ID' => _( 'ID' ),
				'DATE' => _( 'Date' ),
				'BALANCE' => _( 'Balance' ),
				'DESCRIPTION' => _( 'Description' ),
				'AMOUNT' => _( 'Amount' ),
				'SELLER' => _( 'User' ),
			];

			$group = [ [ 'TRANSACTION_ID' ] ];

			$link['remove']['link'] = PreparePHP_SELF(
				$_REQUEST,
				[ 'delete_cancel' ],
				[ 'modfunc' => 'delete' ]
			);

			$link['remove']['variables'] = [
				'transaction_id' => 'TRANS_ID',
				'item_id' => 'ITEM_ID',
			];
		}
		else
		{
			$RET = DBGet( "SELECT fst.TRANSACTION_ID,
			(SELECT sum(AMOUNT) FROM food_service_staff_transaction_items WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION
			FROM food_service_staff_transactions fst
			WHERE fst.STAFF_ID='" . UserStaffID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1" .
				$where . "
			ORDER BY fst.TRANSACTION_ID DESC", [ 'DATE' => 'ProperDateTime', 'BALANCE' => 'red' ] );

			$columns = [
				'TRANSACTION_ID' => _( 'ID' ),
				'DATE' => _( 'Date' ),
				'BALANCE' => _( 'Balance' ),
				'DESCRIPTION' => _( 'Description' ),
				'AMOUNT' => _( 'Amount' ),
			];

			foreach ( (array) $RET as $RET_key => $RET_val )
			{
				$RET[$RET_key] = array_map( 'types_locale', $RET_val );
			}

			$link = $group = [];
		}

		ListOutput( $RET, $columns, 'Transaction', 'Transactions', $link, $group );
	}
	else
	{
		echo ErrorMessage( [ _( 'This user does not have a Meal Account.' ) ] );
	}
}
