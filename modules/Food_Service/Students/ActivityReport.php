<?php

$_REQUEST['detailed_view'] = issetVal( $_REQUEST['detailed_view'], '' );
$_REQUEST['type_select'] = issetVal( $_REQUEST['type_select'], '' );

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
		$where .= "AND fst.SELLER_ID='" . (int) $_REQUEST['staff_select'] . "' ";
	}

	if ( $_REQUEST['detailed_view'] == 'true' )
	{
		$RET = DBGet( "SELECT fst.TRANSACTION_ID AS TRANS_ID,fst.TRANSACTION_ID,
	    	fst.ACCOUNT_ID,fst.SHORT_NAME,fst.STUDENT_ID,fst.DISCOUNT,
	    	(SELECT sum(AMOUNT)
	    		FROM food_service_transaction_items
	    		WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
	    	fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
			db_case( [
				'fst.STUDENT_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM students WHERE STUDENT_ID=fst.STUDENT_ID)",
			] ) . " AS FULL_NAME," .
			db_case( [
				'fst.SELLER_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM staff WHERE STAFF_ID=fst.SELLER_ID)",
			] ) . " AS SELLER
		FROM food_service_transactions fst
		WHERE SYEAR='" . UserSyear() . "'
		AND fst.TIMESTAMP BETWEEN '" . $date . "' AND date '" . $date . "' +1
		AND SCHOOL_ID='" . UserSchool() . "'" . $where . "
		ORDER BY " . ( ! empty( $_REQUEST['by_name'] ) ? "FULL_NAME," : '' ) . "fst.TRANSACTION_ID DESC", [ 'DATE' => 'ProperDateTime', 'SHORT_NAME' => 'bump_count' ] );

		foreach ( (array) $RET as $RET_key => $RET_val )
		{
			$RET[$RET_key] = array_map( 'types_locale', $RET_val );
		}

		foreach ( (array) $RET as $key => $value )
		{
			// get details of each transaction
			$tmpRET = DBGet( "SELECT TRANSACTION_ID AS TRANS_ID,
				ITEM_ID,TRANSACTION_ID,AMOUNT,DISCOUNT,SHORT_NAME,DESCRIPTION,
				'" . DBEscapeString( $value['SHORT_NAME'] ) . "' AS TRANSACTION_SHORT_NAME
				FROM food_service_transaction_items
				WHERE TRANSACTION_ID='" . (int) $value['TRANSACTION_ID'] . "'", [ 'SHORT_NAME' => 'bump_items_count' ] );

			foreach ( (array) $tmpRET as $RET_key => $RET_val )
			{
				$tmpRET[$RET_key] = array_map( 'options_locale', $RET_val );
			}

			// merge transaction and detail records
			$RET[$key] = [ $value ] + $tmpRET;
		}

		//echo '<pre>'; var_dump($RET); echo '</pre>';

		$columns = [
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
		$RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.ACCOUNT_ID,fst.SHORT_NAME,
	    fst.STUDENT_ID,fst.DISCOUNT,
		(SELECT sum(AMOUNT)
			FROM food_service_transaction_items
			WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
		fst.BALANCE,fst.TIMESTAMP AS DATE,fst.DESCRIPTION," .
			db_case( [
				'fst.STUDENT_ID',
				"''",
				'NULL',
				"(SELECT " . DisplayNameSQL() . " FROM students WHERE STUDENT_ID=fst.STUDENT_ID)",
			] ) . " AS FULL_NAME
		FROM food_service_transactions fst
		WHERE SYEAR='" . UserSyear() . "'
		AND fst.TIMESTAMP BETWEEN '" . $date . "' AND date '" . $date . "' +1
		AND SCHOOL_ID='" . UserSchool() . "'" . $where . "
		ORDER BY " . ( ! empty( $_REQUEST['by_name'] ) ? "FULL_NAME," : '' ) . "fst.TRANSACTION_ID DESC", [ 'DATE' => 'ProperDateTime', 'SHORT_NAME' => 'bump_count' ] );

		$columns = [
			'TRANSACTION_ID' => _( 'ID' ),
			'ACCOUNT_ID' => _( 'Account ID' ),
			'FULL_NAME' => _( 'Student' ),
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

		$link = $group = [];
	}

	$type_select = '<label>' . _( 'Type' ) .
		' <select name="type_select"><option value="">' . _( 'Not Specified' ) . '</option>';

	foreach ( (array) $types as $short_name => $type )
	{
		$type_select .= '<option value="' . AttrEscape( $short_name ) . '"' . ( $_REQUEST['type_select'] == $short_name ? ' selected' : '' ) . '>' . $type['DESCRIPTION'] . '</option>';
	}

	$type_select .= '</select></label>';

	$staff_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
		FROM staff
		WHERE SYEAR='" . UserSyear() . "'
		AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
		AND PROFILE='admin'
		ORDER BY FULL_NAME" );

	$staff_select = '<label>' . _( 'User' ) .
		' <select name="staff_select"><option value="">' . _( 'Not Specified' ) . '</option>';

	foreach ( (array) $staff_RET as $staff )
	{
		$staff_select .= '<option value="' . AttrEscape( $staff['STAFF_ID'] ) . '"' .
			( isset( $_REQUEST['staff_select'] ) && $_REQUEST['staff_select'] == $staff['STAFF_ID'] ? ' selected' : '' ) . '>' .
			$staff['FULL_NAME'] . '</option>';
	}

	$staff_select .= '</select></label>';

	$PHP_tmp_SELF = PreparePHP_SELF();

	echo '<form action="' . $PHP_tmp_SELF . '" method="GET">';

	// FJ add label on checkbox.
	DrawHeader(
		PrepareDate( $date, '_date' ) . ' &mdash; ' .
		$type_select . ' &mdash; ' . $staff_select .
		SubmitButton( _( 'Go' ) ) );

	DrawHeader( CheckBoxOnclick( 'by_name', _( 'Sort by Name' ) ) );

	echo '</form>';

	if ( $_REQUEST['detailed_view'] != 'true' )
	{
		DrawHeader( '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'detailed_view' => 'true' ] ) . '">' .
			_( 'Detailed View' ) . '</a>' );
	}
	else
	{
		DrawHeader( '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'detailed_view' => 'false' ] ) . '">' . _( 'Original View' ) . '</a>' );
	}

	if ( $_REQUEST['detailed_view'] == 'true' )
	{
		$LO_types = [ [ [] ] ];

		foreach ( (array) $types as $type )
		{
			if ( $type['COUNT'] )
			{
				$LO_types[] = [ [ 'DESCRIPTION' => $type['DESCRIPTION'], 'DETAIL' => '', 'COUNT' => $type['COUNT'], 'AMOUNT' => number_format( $type['AMOUNT'], 2 ) ] ];

				foreach ( (array) $type['ITEMS'] as $item )
				{
					if ( $item[1]['COUNT'] )
					{
						$LO_types[last( $LO_types )][] = [
							'DESCRIPTION' => $type['DESCRIPTION'],
							'DETAIL' => $item[1]['DESCRIPTION'],
							'COUNT' => $item[1]['COUNT'],
							'AMOUNT' => number_format( $item[1]['AMOUNT'], 2 ),
						];
					}
				}
			}
		}

		$types_columns = [
			'DESCRIPTION' => _( 'Description' ),
			'DETAIL' => _( 'Detail' ),
			'COUNT' => _( 'Count' ),
			'AMOUNT' => _( 'Amount' ),
		];

		$types_group = [ 'DESCRIPTION' ];
	}
	else
	{
		$LO_types = [ [] ];

		foreach ( (array) $types as $type )
		{
			if ( $type['COUNT'] )
			{
				$LO_types[] = [
					'DESCRIPTION' => $type['DESCRIPTION'],
					'COUNT' => $type['COUNT'],
					'AMOUNT' => number_format( $type['AMOUNT'], 2 ),
				];
			}
		}

		$types_columns = [
			'DESCRIPTION' => _( 'Description' ),
			'COUNT' => _( 'Count' ),
			'AMOUNT' => _( 'Amount' ),
		];

		$types_group = [];
	}

	unset( $LO_types[0] );

	ListOutput(
		$LO_types,
		$types_columns,
		'Transaction Type',
		'Transaction Types',
		false,
		$types_group,
		[ 'save' => false, 'search' => false, 'print' => false ]
	);

	ListOutput(
		$RET,
		$columns,
		'Transaction',
		'Transactions',
		$link,
		$group,
		[ 'save' => false, 'search' => false, 'print' => false ]
	);
}
