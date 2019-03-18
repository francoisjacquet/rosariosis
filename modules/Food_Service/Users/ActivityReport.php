<?php

StaffWidgets( 'fsa_status' );
StaffWidgets( 'fsa_barcode' );
StaffWidgets( 'fsa_exists_Y' );

$extra['SELECT'] .= ",(SELECT coalesce(STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE";
$extra['functions'] += array( 'BALANCE' => 'red' );
$extra['columns_after'] = array( 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) );

Search( 'staff_id', $extra );

if ( UserStaffID() && ! $_REQUEST['modfunc'] )
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
		fst.SHORT_NAME,fst.STAFF_ID,
		(SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
		fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
			db_case( array(
				'fst.STAFF_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=fst.STAFF_ID)",
			) ) . " AS FULL_NAME," .
			db_case( array(
				'fst.SELLER_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=fst.SELLER_ID)",
			) ) . " AS SELLER
		FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst
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
			$tmpRET = DBGet( "SELECT TRANSACTION_ID AS TRANS_ID,*,'" . $value['SHORT_NAME'] . "' AS TRANSACTION_SHORT_NAME FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . $value['TRANSACTION_ID'] . "'", array( 'SHORT_NAME' => 'bump_items_count' ) );

			//FJ add translation

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
			'FULL_NAME' => _( 'User' ),
			'DATE' => _( 'Date' ),
			'BALANCE' => _( 'Balance' ),
			'DESCRIPTION' => _( 'Description' ),
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
		$RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.SHORT_NAME,fst.STAFF_ID,
		(SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
		fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
			db_case( array(
				'fst.STAFF_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=fst.STAFF_ID)",
			) ) . " AS FULL_NAME
		FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst
		WHERE SYEAR='" . UserSyear() . "'
		AND fst.TIMESTAMP BETWEEN '" . $date . "' AND date '" . $date . "' +1
		AND SCHOOL_ID='" . UserSchool() . "'" . $where . "
		ORDER BY " . ( $_REQUEST['by_name'] ? "FULL_NAME," : '' ) . "fst.TRANSACTION_ID DESC", array( 'DATE' => 'ProperDateTime', 'SHORT_NAME' => 'bump_count' ) );

		$columns = array(
			'TRANSACTION_ID' => _( 'ID' ),
			'FULL_NAME' => _( 'User' ),
			'DATE' => _( 'Date' ),
			'BALANCE' => _( 'Balance' ),
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

	DrawHeader(
		PrepareDate( $date, '_date' ) . ' - ' . $type_select . ' - ' .
		$staff_select . ' ' . SubmitButton( _( 'Go' ) ) );

	DrawHeader( CheckBoxOnclick( 'by_name', _( 'Sort by Name' ) ) );

	echo '</form>';

	if ( ! empty( $_REQUEST['type_select'] ) )
	{
		$where = "AND fst.SHORT_NAME='" . $_REQUEST['type_select'] . "' ";
	}

	if ( ! empty( $_REQUEST['staff_select'] ) )
	{
		$where = "AND fst.SELLER_ID='" . $_REQUEST['staff_select'] . "' ";
	}

	if ( $_REQUEST['detailed_view'] != 'true' )
	{
		DrawHeader( '<a href="' . PreparePHP_SELF( $_REQUEST, array(), array( 'detailed_view' => 'true' ) ) . '">' . _( 'Detailed View' ) . '</a>' );
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
