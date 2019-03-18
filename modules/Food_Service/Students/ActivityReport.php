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

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	$where = '';

	if ( ! empty( $_REQUEST['type_select'] ) )
	{
		$where .= "AND fst.SHORT_NAME='" . $_REQUEST['type_select'] . "' ";
	}

	if ( ! empty( $_REQUEST['staff_select'] ) )
	{
		$where .= "AND fst.SELLER_ID='" . $_REQUEST['staff_select'] . "' ";
	}

	if ( $_REQUEST['detailed_view'] == 'true' )
	{
		$RET = DBGet( "SELECT fst.TRANSACTION_ID AS TRANS_ID,fst.TRANSACTION_ID,
	    	fst.ACCOUNT_ID,fst.SHORT_NAME,fst.STUDENT_ID,fst.DISCOUNT,
	    	(SELECT sum(AMOUNT)
	    		FROM FOOD_SERVICE_TRANSACTION_ITEMS
	    		WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
	    	fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
			db_case( array(
				'fst.STUDENT_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM STUDENTS WHERE STUDENT_ID=fst.STUDENT_ID)",
			) ) . " AS FULL_NAME," .
			db_case( array(
				'fst.SELLER_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=fst.SELLER_ID)",
			) ) . " AS SELLER
		FROM FOOD_SERVICE_TRANSACTIONS fst
		WHERE SYEAR='" . UserSyear() . "'
		AND fst.TIMESTAMP BETWEEN '" . $date . "' AND date '" . $date . "' +1
		AND SCHOOL_ID='" . UserSchool() . "'" . $where . "
		ORDER BY " . ( $_REQUEST['by_name'] ? "FULL_NAME," : '' ) . "fst.TRANSACTION_ID DESC", array( 'DATE' => 'ProperDateTime', 'SHORT_NAME' => 'bump_count' ) );

		foreach ( (array) $RET as $RET_key => $RET_val )
		{
			$RET[$RET_key] = array_map( 'types_locale', $RET_val );
		}

		foreach ( (array) $RET as $key => $value )
		{
			// get details of each transaction
			$tmpRET = DBGet( "SELECT TRANSACTION_ID AS TRANS_ID,*,'" . $value['SHORT_NAME'] . "' AS TRANSACTION_SHORT_NAME FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . $value['TRANSACTION_ID'] . "'", array( 'SHORT_NAME' => 'bump_items_count' ) );

			foreach ( (array) $tmpRET as $RET_key => $RET_val )
			{
				$tmpRET[$RET_key] = array_map( 'options_locale', $RET_val );
			}

			// merge transaction and detail records
			$RET[$key] = array( $value ) + $tmpRET;
		}

		//echo '<pre>'; var_dump($RET); echo '</pre>';

		$columns = array(
			'TRANSACTION_ID' => _( 'ID' ),
			'ACCOUNT_ID' => _( 'Account ID' ),
			'FULL_NAME' => _( 'Student' ),
			'DATE' => _( 'Date' ),
			'BALANCE' => _( 'Balance' ),
			'DISCOUNT' => _( 'Discount' ),
			'DESCRIPTION' => _( 'Description' ),
			'DISCOUNT' => _( 'Discount' ),
			'AMOUNT' => _( 'Amount' ),
			'SELLER' => _( 'User' ),
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
		$RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.ACCOUNT_ID,fst.SHORT_NAME,
	    fst.STUDENT_ID,fst.DISCOUNT,
		(SELECT sum(AMOUNT)
			FROM FOOD_SERVICE_TRANSACTION_ITEMS
			WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
		fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
			db_case( array(
				'fst.STUDENT_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM STUDENTS WHERE STUDENT_ID=fst.STUDENT_ID)",
			) ) . " AS FULL_NAME
		FROM FOOD_SERVICE_TRANSACTIONS fst
		WHERE SYEAR='" . UserSyear() . "'
		AND fst.TIMESTAMP BETWEEN '" . $date . "' AND date '" . $date . "' +1
		AND SCHOOL_ID='" . UserSchool() . "'" . $where . "
		ORDER BY " . ( $_REQUEST['by_name'] ? "FULL_NAME," : '' ) . "fst.TRANSACTION_ID DESC", array( 'DATE' => 'ProperDateTime', 'SHORT_NAME' => 'bump_count' ) );

		$columns = array(
			'TRANSACTION_ID' => _( 'ID' ),
			'ACCOUNT_ID' => _( 'Account ID' ),
			'FULL_NAME' => _( 'Student' ),
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

	$type_select = '<span class="nobr">' . _( 'Type' ) . ' <select name="type_select"><option value="">' . _( 'Not Specified' ) . '</option>';

	foreach ( (array) $types as $short_name => $type )
	{
		$type_select .= '<option value="' . $short_name . '"' . ( $_REQUEST['type_select'] == $short_name ? ' selected' : '' ) . '>' . $type['DESCRIPTION'] . '</option>';
	}

	$type_select .= '</select></span>';

	$staff_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
		FROM STAFF
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOLS LIKE '%," . UserSchool() . ",%'
		AND PROFILE='admin'
		ORDER BY LAST_NAME" );

	$staff_select = '<span class="nobr">' . _( 'User' ) . ' <select name=staff_select><option value="">' . _( 'Not Specified' ) . '</option>';

	foreach ( (array) $staff_RET as $staff )
	{
		$staff_select .= '<option value="' . $staff['STAFF_ID'] . '"' . ( $_REQUEST['staff_select'] == $staff['STAFF_ID'] ? ' selected' : '' ) . '>' . $staff['FULL_NAME'] . '</option>';
	}

	$staff_select .= '</select></span>';

	$PHP_tmp_SELF = PreparePHP_SELF();

	echo '<form action="' . $PHP_tmp_SELF . '" method="GET">';

	// FJ add label on checkbox.
	DrawHeader(
		PrepareDate( $date, '_date' ) . ' - ' .
		$type_select . ' - ' . $staff_select .
		SubmitButton( _( 'Go' ) ) );

	DrawHeader( CheckBoxOnclick( 'by_name', _( 'Sort by Name' ) ) );

	echo '</form>';

	if ( $_REQUEST['detailed_view'] != 'true' )
	{
		DrawHeader( '<a href="' . PreparePHP_SELF( $_REQUEST, array(), array( 'detailed_view' => 'true' ) ) . '">' .
			_( 'Detailed View' ) . '</a>' );
	}
	else
	{
		DrawHeader( '<a href="' . PreparePHP_SELF( $_REQUEST, array(), array( 'detailed_view' => 'false' ) ) . '">' . _( 'Original View' ) . '</a>' );
	}

	if ( $_REQUEST['detailed_view'] == 'true' )
	{
		$LO_types = array( array( array() ) );

		foreach ( (array) $types as $type )
		{
			if ( $type['COUNT'] )
			{
				$LO_types[] = array( array( 'DESCRIPTION' => $type['DESCRIPTION'], 'DETAIL' => '', 'COUNT' => $type['COUNT'], 'AMOUNT' => number_format( $type['AMOUNT'], 2 ) ) );

				foreach ( (array) $type['ITEMS'] as $item )
				{
					if ( $item[1]['COUNT'] )
					{
						$LO_types[last( $LO_types )][] = array( 'DESCRIPTION' => $type['DESCRIPTION'], 'DETAIL' => $item[1]['DESCRIPTION'], 'COUNT' => $item[1]['COUNT'], 'AMOUNT' => number_format( $item[1]['AMOUNT'], 2 ) );
					}
				}
			}
		}

		$types_columns = array( 'DESCRIPTION' => _( 'Description' ), 'DETAIL' => _( 'Detail' ), 'COUNT' => _( 'Count' ), 'AMOUNT' => _( 'Amount' ) );
		$types_group = array( 'DESCRIPTION' );
	}
	else
	{
		$LO_types = array( array() );

		foreach ( (array) $types as $type )
		{
			if ( $type['COUNT'] )
			{
				$LO_types[] = array( 'DESCRIPTION' => $type['DESCRIPTION'], 'COUNT' => $type['COUNT'], 'AMOUNT' => number_format( $type['AMOUNT'], 2 ) );
			}
		}

		$types_columns = array( 'DESCRIPTION' => _( 'Description' ), 'COUNT' => _( 'Count' ), 'AMOUNT' => _( 'Amount' ) );
	}

	unset( $LO_types[0] );

	ListOutput( $LO_types, $types_columns, 'Transaction Type', 'Transaction Types', false, $types_group, array( 'save' => false, 'search' => false, 'print' => false ) );

	ListOutput( $RET, $columns, 'Transaction', 'Transactions', $link, $group, array( 'save' => false, 'search' => false, 'print' => false ) );
}
