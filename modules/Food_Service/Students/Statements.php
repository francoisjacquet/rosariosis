<?php

Widgets( 'fsa_discount' );
Widgets( 'fsa_status' );
Widgets( 'fsa_barcode' );
Widgets( 'fsa_account_id' );

$extra['SELECT'] .= ",coalesce(fssa.STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE";

if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
{
	$extra['FROM'] = ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
	$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
}

$extra['functions'] += array( 'BALANCE' => 'red' );
$extra['columns_after'] = array( 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) );

Search( 'student_id', $extra );

if ( UserStudentID() && ! $_REQUEST['modfunc'] )
{
	$student = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
	fsa.ACCOUNT_ID,fsa.STATUS,
	(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fsa.ACCOUNT_ID) AS BALANCE
	FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fsa
	WHERE s.STUDENT_ID='" . UserStudentID() . "'
	AND fsa.STUDENT_ID=s.STUDENT_ID" );

	$student = $student[1];

	// Find other students associated with the same account.
	$xstudents = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME
	FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa
	WHERE fssa.ACCOUNT_ID='" . $student['ACCOUNT_ID'] . "'
	AND s.STUDENT_ID=fssa.STUDENT_ID
	AND s.STUDENT_ID!='" . UserStudentID() . "'
	AND exists(SELECT ''
		FROM STUDENT_ENROLLMENT
		WHERE STUDENT_ID=s.STUDENT_ID
		AND SYEAR='" . UserSyear() . "'
		AND (START_DATE<=CURRENT_DATE AND (END_DATE IS NULL OR CURRENT_DATE<=END_DATE)))" );

	if ( ! empty( $xstudents ) )
	{
		$student_select = _( 'Student' ) . ' <select name="student_select"><option value="">' . _( 'Not Specified' ) . '</option>';
		$student_select .= '<option value="' . $student['STUDENT_ID'] . '"' . ( $_REQUEST['student_select'] == $student['STUDENT_ID'] ? ' selected' : '' ) . '>' . $student['FULL_NAME'] . '</option>';

		foreach ( (array) $xstudents as $xstudent )
		{
			$student_select .= '<option value="' . $xstudent['STUDENT_ID'] . '"' . ( $_REQUEST['student_select'] == $xstudent['STUDENT_ID'] ? ' selected' : '' ) . '>' . $xstudent['FULL_NAME'] . '</option>';
		}

		$student_select .= '</select>';
	}

	echo '<form action="' . PreparePHP_SELF() . '" method="POST">';
	DrawHeader( _( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start' ) . ' ' . _( 'to' ) . ' ' . PrepareDate( $end_date, '_end' ) . ' : ' . $type_select . ( $student_select ? ' : ' . $student_select : '' ) . ' : <input type="submit" value="' . _( 'Go' ) . '">' );
	echo '</form>';

	DrawHeader( NoInput( $student['FULL_NAME'], '&nbsp;' . $student['STUDENT_ID'] ), '', NoInput( red( $student['BALANCE'] ), _( 'Balance' ) ) );

	if ( $_REQUEST['detailed_view'] != 'true' )
	{
		DrawHeader( '<a href="' . PreparePHP_SELF( $_REQUEST, array(), array( 'detailed_view' => 'true' ) ) . '">' . _( 'Detailed View' ) . '</a>' );
	}
	else
	{
		DrawHeader( '<a href="' . PreparePHP_SELF( $_REQUEST, array(), array( 'detailed_view' => 'false' ) ) . '">' . _( 'Original View' ) . '</a>' );
	}

	if ( $student['BALANCE'] )
	{
		if ( ! empty( $_REQUEST['student_select'] ) )
		{
			$where = " AND fst.STUDENT_ID='" . $_REQUEST['student_select'] . "'";
		}

		if ( ! empty( $_REQUEST['type_select'] ) )
		{
			$where .= " AND fst.SHORT_NAME='" . $_REQUEST['type_select'] . "'";
		}

		if ( $_REQUEST['detailed_view'] == 'true' )
		{
			$RET = DBGet( "SELECT fst.TRANSACTION_ID AS TRANS_ID,fst.TRANSACTION_ID,
			fst.STUDENT_ID,fst.DISCOUNT,
			(SELECT sum(AMOUNT) FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
				db_case( array(
					'fst.STUDENT_ID',
					"''",
					'NULL',
					"(SELECT " . DisplayNameSQL() . " FROM STUDENTS WHERE STUDENT_ID=fst.STUDENT_ID)",
				) ) . " AS STUDENT," .
				db_case( array(
					'fst.SELLER_ID',
					"''",
					'NULL',
					"(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=fst.SELLER_ID)",
				) ) . " AS SELLER
			FROM FOOD_SERVICE_TRANSACTIONS fst
			WHERE fst.ACCOUNT_ID='" . $student['ACCOUNT_ID'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1" .
				$where . "
			ORDER BY fst.TRANSACTION_ID DESC", array( 'DATE' => 'ProperDateTime', 'BALANCE' => 'red' ) );

			foreach ( (array) $RET as $RET_key => $RET_val )
			{
				$RET[$RET_key] = array_map( 'types_locale', $RET_val );
			}

			// get details of each transaction

			foreach ( (array) $RET as $key => $value )
			{
				$tmpRET = DBGet( "SELECT TRANSACTION_ID AS TRANS_ID,* FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . $value['TRANSACTION_ID'] . "'" );
//FJ add translation

				foreach ( (array) $tmpRET as $RET_key => $RET_val )
				{
					$tmpRET[$RET_key] = array_map( 'options_locale', $RET_val );
				}

				// merge transaction and detail records
				$RET[$key] = array( $RET[$key] ) + $tmpRET;
			}

			$columns = array(
				'TRANSACTION_ID' => _( 'ID' ),
				'STUDENT' => _( 'Student' ),
				'DATE' => _( 'Date' ),
				'BALANCE' => _( 'Balance' ),
				'DISCOUNT' => _( 'Discount' ),
				'DESCRIPTION' => _( 'Description' ),
				'AMOUNT' => _( 'Amount' ),
				'SELLER' => _( 'Seller' ),
			);

			$group = array( array( 'TRANSACTION_ID' ) );

			$link['remove']['link'] = PreparePHP_SELF(
				$_REQUEST,
				array( 'delete_cancel' ),
				array( 'modfunc' => 'delete' )
			);

			$link['remove']['variables'] = array(
				'transaction_id' => 'TRANS_ID',
				'item_id' => 'ITEM_ID',
			);
		}
		else
		{
			$RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.DISCOUNT,(SELECT sum(AMOUNT) FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION
			FROM FOOD_SERVICE_TRANSACTIONS fst
			WHERE fst.ACCOUNT_ID='" . $student['ACCOUNT_ID'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND fst.TIMESTAMP BETWEEN '" . $start_date . "'
			AND date '" . $end_date . "'+1 " . $where . "
			ORDER BY fst.TRANSACTION_ID DESC", array( 'DATE' => 'ProperDateTime', 'BALANCE' => 'red' ) );

			$columns = array(
				'TRANSACTION_ID' => _( 'ID' ),
				'DATE' => _( 'Date' ),
				'BALANCE' => _( 'Balance' ),
				'DISCOUNT' => _( 'Discount' ),
				'DESCRIPTION' => _( 'Description' ),
				'AMOUNT' => _( 'Amount' ),
			);

			foreach ( (array) $RET as $RET_key => $RET_val )
			{
				$RET[$RET_key] = array_map( 'types_locale', $RET_val );
			}
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
		echo ErrorMessage( array( _( 'This student does not have a valid Meal Account.' ) ) );
	}
}
