<?php

Widgets( 'fsa_discount' );
Widgets( 'fsa_status' );
Widgets( 'fsa_barcode' );
Widgets( 'fsa_account_id' );

$extra['SELECT'] .= ",coalesce(fssa.STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE";

if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
{
	$extra['FROM'] = ",food_service_student_accounts fssa";
	$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
}

$extra['functions'] += [ 'BALANCE' => 'red' ];
$extra['columns_after'] = [ 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) ];

Search( 'student_id', $extra );

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
	AND s.STUDENT_ID!='" . UserStudentID() . "'
	AND exists(SELECT ''
		FROM student_enrollment
		WHERE STUDENT_ID=s.STUDENT_ID
		AND SYEAR='" . UserSyear() . "'
		AND (START_DATE<=CURRENT_DATE AND (END_DATE IS NULL OR CURRENT_DATE<=END_DATE)))" );

	$student_select = '';

	if ( ! empty( $xstudents ) )
	{
		$student_select = _( 'Student' ) . ' <select name="student_select"><option value="">' . _( 'Not Specified' ) . '</option>';
		$student_select .= '<option value="' . AttrEscape( $student['STUDENT_ID'] ) . '"' . ( $_REQUEST['student_select'] == $student['STUDENT_ID'] ? ' selected' : '' ) . '>' . $student['FULL_NAME'] . '</option>';

		foreach ( (array) $xstudents as $xstudent )
		{
			$student_select .= '<option value="' . AttrEscape( $xstudent['STUDENT_ID'] ) . '"' . ( $_REQUEST['student_select'] == $xstudent['STUDENT_ID'] ? ' selected' : '' ) . '>' . $xstudent['FULL_NAME'] . '</option>';
		}

		$student_select .= '</select>';
	}

	echo '<form action="' . PreparePHP_SELF() . '" method="POST">';

	DrawHeader(
		_( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start' ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end' ) .
		' ' . $type_select .
		( $student_select ? ' : ' . $student_select : '' ) .
		' ' . Buttons( _( 'Go' ) )
	);

	echo '</form>';

	DrawHeader(
		NoInput( $student['FULL_NAME'], $student['STUDENT_ID'] ),
		NoInput( red( $student['BALANCE'] ), _( 'Balance' ) )
	);

	if ( ! isset( $_REQUEST['detailed_view'] )
		|| $_REQUEST['detailed_view'] !== 'true' )
	{
		DrawHeader(
			'<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'detailed_view' => 'true' ] ) . '">' . _( 'Detailed View' ) . '</a>'
		);
	}
	else
	{
		DrawHeader(
			'<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'detailed_view' => 'false' ] ) . '">' . _( 'Original View' ) . '</a>'
		);
	}

	if ( $student['BALANCE'] )
	{
		$where = '';

		if ( ! empty( $_REQUEST['student_select'] ) )
		{
			$where .= " AND fst.STUDENT_ID='" . (int) $_REQUEST['student_select'] . "'";
		}

		if ( ! empty( $_REQUEST['type_select'] ) )
		{
			$where .= " AND fst.SHORT_NAME='" . $_REQUEST['type_select'] . "'";
		}

		if ( isset( $_REQUEST['detailed_view'] )
			&& $_REQUEST['detailed_view'] == 'true' )
		{
			$RET = DBGet( "SELECT fst.TRANSACTION_ID AS TRANS_ID,fst.TRANSACTION_ID,
			fst.STUDENT_ID,fst.DISCOUNT,
			(SELECT sum(AMOUNT) FROM food_service_transaction_items WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
				db_case( [
					'fst.STUDENT_ID',
					"''",
					'NULL',
					"(SELECT " . DisplayNameSQL() . " FROM students WHERE STUDENT_ID=fst.STUDENT_ID)",
				] ) . " AS STUDENT," .
				db_case( [
					'fst.SELLER_ID',
					"''",
					'NULL',
					"(SELECT " . DisplayNameSQL() . " FROM staff WHERE STAFF_ID=fst.SELLER_ID)",
				] ) . " AS SELLER
			FROM food_service_transactions fst
			WHERE fst.ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1" .
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
					ITEM_ID,TRANSACTION_ID,AMOUNT,DISCOUNT,SHORT_NAME,DESCRIPTION
					FROM food_service_transaction_items
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
				'STUDENT' => _( 'Student' ),
				'DATE' => _( 'Date' ),
				'BALANCE' => _( 'Balance' ),
				'DISCOUNT' => _( 'Discount' ),
				'DESCRIPTION' => _( 'Description' ),
				'AMOUNT' => _( 'Amount' ),
				'SELLER' => _( 'Seller' ),
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
			$RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.DISCOUNT,(SELECT sum(AMOUNT) FROM food_service_transaction_items WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION
			FROM food_service_transactions fst
			WHERE fst.ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND fst.TIMESTAMP BETWEEN '" . $start_date . "'
			AND date '" . $end_date . "'+1 " . $where . "
			ORDER BY fst.TRANSACTION_ID DESC", [ 'DATE' => 'ProperDateTime', 'BALANCE' => 'red' ] );

			$columns = [
				'TRANSACTION_ID' => _( 'ID' ),
				'DATE' => _( 'Date' ),
				'BALANCE' => _( 'Balance' ),
				'DISCOUNT' => _( 'Discount' ),
				'DESCRIPTION' => _( 'Description' ),
				'AMOUNT' => _( 'Amount' ),
			];

			foreach ( (array) $RET as $RET_key => $RET_val )
			{
				$RET[$RET_key] = array_map( 'types_locale', $RET_val );
			}

			$group = [];

			$link = [];
		}

		ListOutput(
			$RET,
			$columns,
			'Transaction',
			'Transactions',
			$link,
			$group
		);
	}
	else
	{
		echo ErrorMessage( [ _( 'This student does not have a valid Meal Account.' ) ] );
	}
}
