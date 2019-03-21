<?php

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01', 'set' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate(), 'set' );

DrawHeader( ProgramTitle() );

$types = array(
	'Student' => array(
		'DEPOSIT' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
		'CREDIT' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
		'DEBIT' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
	),
	'User' => array(
		'DEPOSIT' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
		'CREDIT' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
		'DEBIT' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
	),
);

$types_totals = array(
	'Student' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
	'User' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
	'' => array( 'CASH' => 0, 'CHECK' => 0, 'CREDIT CARD' => 0, 'DEBIT CARD' => 0, 'TRANSFER' => 0, '' => 0 ),
);

$types_rows = array(
	'DEPOSIT' => _( 'Deposit' ),
	'CREDIT' => _( 'Credit' ),
	'DEBIT' => _( 'Debit' ),
);

$types_columns = array(
	'CASH' => _( 'Cash' ),
	'CHECK' => _( 'Check' ),
	'CREDIT CARD' => _( 'Credit Card' ),
	'DEBIT CARD' => _( 'Debit Card' ),
	'TRANSFER' => _( 'Transfer' ),
	'' => 'n/s'
);


echo '<form action="' . PreparePHP_SELF() . '" method="GET">';
DrawHeader(
	_( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start' ) . ' ' .
	_( 'to' ) . ' ' . PrepareDate( $end_date, '_end' ) .
	SubmitButton( _( 'Go' ) )
);
echo '</form>';

$RET = DBGet( "SELECT 'Student' AS TYPE,fst.SHORT_NAME,fsti.SHORT_NAME AS ITEM_SHORT_NAME,sum(fsti.AMOUNT) AS AMOUNT
FROM FOOD_SERVICE_TRANSACTION_ITEMS fsti, FOOD_SERVICE_TRANSACTIONS fst
WHERE fst.SHORT_NAME NOT IN (SELECT TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID='" . UserSchool() . "')
AND fsti.TRANSACTION_ID=fst.TRANSACTION_ID
AND fst.SYEAR='" . UserSyear() . "'
AND fst.SCHOOL_ID='" . UserSchool() . "'
AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
GROUP BY fst.SHORT_NAME,fsti.SHORT_NAME", array( 'ITEM_SHORT_NAME' => 'bump_amount' ) );
//echo '<pre>'; var_dump($RET); echo '</pre>';

$RET = DBGet( "SELECT 'User' AS TYPE,fst.SHORT_NAME,fsti.SHORT_NAME AS ITEM_SHORT_NAME,sum(fsti.AMOUNT) AS AMOUNT
FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS fsti,FOOD_SERVICE_STAFF_TRANSACTIONS fst
WHERE fst.SHORT_NAME NOT IN (SELECT TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID='" . UserSchool() . "')
AND fsti.TRANSACTION_ID=fst.TRANSACTION_ID
AND fst.SYEAR='" . UserSyear() . "'
AND fst.SCHOOL_ID='" . UserSchool() . "'
AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
GROUP BY fst.SHORT_NAME,fsti.SHORT_NAME", array( 'ITEM_SHORT_NAME' => 'bump_amount' ) );
//echo '<pre>'; var_dump($RET); echo '</pre>';

$LO_types = array( 0 => array() );
//FJ add translation
$users_locale = array( 'Student' => _( 'Student' ), 'User' => _( 'User' ) );

foreach ( (array) $types as $user => $trans )
{
	$TMP_types = array( 0 => array() );

	foreach ( (array) $trans as $tran => $value )
	{
		//echo '<pre>'; var_dump($value); echo '</pre>';
		$total = array_sum( $value );

		if ( $total != 0 )
		{
			$TMP_types[] = array(
				'TYPE' => ( empty( $users_locale[$user] ) ? $user : $users_locale[$user] ),
				'TRANSACTION' => $types_rows[$tran],
				'TOTAL' => '<b>' . number_format( $total, 2 ) . '</b>'
			) + array_map( 'format', $value );
		}
	}

	$total = array_sum( $types_totals[$user] );

	$TMP_types[] = array(
		'TYPE' => '<b>' . ( empty( $users_locale[$user] ) ? $user : $users_locale[$user] ) . '</b>',
		'TRANSACTION' => '<b>' . _( 'Totals' ) . '</b>',
		'TOTAL' => '<b>' . number_format( $total, 2 ) . '</b>'
	) + array_map( 'bold_format', $types_totals[$user] );

	unset( $TMP_types[0] );
	$LO_types[] = $TMP_types;
}

$total = array_sum( $types_totals[''] );
bold_format( $total );

foreach ( (array) $types_totals[''] as $key => $value )
{
	if ( $value == 0 )
	{
		unset( $types_columns[$key] );
	}
}

$LO_types[] = array( array(
	'TYPE' => '<b>' . _( 'Totals' ) . '</b>',
	'TOTAL' => '<b>' . number_format( $total, 2 ) . '</b>'
) + array_map( 'bold_format', $types_totals[''] ) );

unset( $LO_types[0] );

$LO_columns = array(
	'TYPE' => _( 'Type' ),
	'TRANSACTION' => _( 'Transaction' )
) + $types_columns + array( 'TOTAL' => _( 'Total' ) );

ListOutput(
	$LO_types,
	$LO_columns,
	'Type',
	'Types',
	false,
	array( array() ),
	array( 'save' => false, 'search' => false, 'print' => false )
);

/**
 * @param $item
 */
function format( $item )
{
	return number_format( $item, 2 );
}

/**
 * @param $item
 */
function bold( $item )
{
	return '<b>' . $item . '</b>';
}

/**
 * @param $item
 */
function bold_format( $item )
{
	return '<b>' . number_format( $item, 2 ) . '</b>';
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function bump_amount( $value, $column )
{
	global $THIS_RET, $types, $types_rows, $types_columns, $types_totals;

	if ( $types[$THIS_RET['TYPE']][$THIS_RET['SHORT_NAME']] )
	{
		$types[$THIS_RET['TYPE']][$THIS_RET['SHORT_NAME']][$value] += $THIS_RET['AMOUNT'];
	}
	else
	{
		$types[$THIS_RET['TYPE']] += array( $THIS_RET['SHORT_NAME'] => array( $value => $THIS_RET['AMOUNT'] ) );
		$types_rows[$THIS_RET['SHORT_NAME']] = $THIS_RET['SHORT_NAME'];
	}

	if ( ! $types_columns[$value] )
	{
		$types_columns += array( $value => $value );
		$types_totals['Student'][$value] = 0;
		$types_totals['User'][$value] = 0;
		$types_totals[$THIS_RET['TYPE']][$value] = 0;
		$types_totals[''][$value] = 0;
	}

	$types_totals[$THIS_RET['TYPE']][$value] += $THIS_RET['AMOUNT'];
	$types_totals[''][$value] += $THIS_RET['AMOUNT'];

	return $value;
}
