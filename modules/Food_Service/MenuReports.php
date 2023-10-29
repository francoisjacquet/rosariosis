<?php

$_REQUEST['type_select'] = issetVal( $_REQUEST['type_select'], '' );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01', 'set' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate(), 'set' );

DrawHeader( ProgramTitle() );

$menus_RET = DBGet( "SELECT MENU_ID,TITLE
	FROM food_service_menus
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'MENU_ID' ] );

if ( empty( $_REQUEST['menu_id'] ) )
{
	if ( empty( $_SESSION['FSA_menu_id'] ) )
	{
		if ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( [ _( 'There are no menus yet setup.' ) ], 'fatal' );
		}
	}
	else
	{
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
	}
}
else
{
	$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
}

$menu_title = $menus_RET[$_REQUEST['menu_id']][1]['TITLE'];

$users = [
	'Student' => [
		'' => [ 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ],
		'Reduced' => [ 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ],
		'Free' => [ 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ],
	],
	'User' => [
		'' => [ 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ],
	],
];

$users_totals = [
	'Student' => [ 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ],
	'User' => [ 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ],
	'' => [ 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ],
];

$users_columns = [
	'ELLIGIBLE' => _( 'Eligible' ),
	'DAYS_POSSIBLE' => _( 'Days Possible' ),
	'TOTAL_ELLIGIBLE' => _( 'Total Eligible' ),
	'PARTICIPATED' => _( 'Participated' ),
];

$items_RET = DBGet( "SELECT SHORT_NAME,DESCRIPTION
	FROM food_service_items
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

$items = [];
$items_columns = [];

foreach ( (array) $items_RET as $value )
{
	$items += [ $value['SHORT_NAME'] => 0 ];
	$items_columns += [ $value['SHORT_NAME'] => $value['DESCRIPTION'] ];
}

//echo '<pre>'; var_dump($items); echo '</pre>';
//echo '<pre>'; var_dump($items_columns); echo '</pre>';

$types = [ 'Student' => [ '' => $items,
	'Reduced' => $items,
	'Free' => $items,
],
	'User' => [ '' => $items,
	],
];

$types_totals = [ 'Student' => $items,
	'User' => $items,
	'' => $items,
];

$types_columns = $items_columns;

$type_select = '<select name="type_select" onchange="ajaxPostForm(this.form,true);"><option value=participation' . ( 'sales' == $_REQUEST['type_select'] ? '' : ' selected' ) . '>' . _( 'Participation' ) . '</option><option value="sales"' . ( 'sales' == $_REQUEST['type_select'] ? ' selected' : '' ) . '>' . _( 'Sales' ) . '</option></select>';

//$calendars_RET = DBGet( "SELECT acs.CALENDAR_ID,(SELECT count(1) FROM attendance_calendar WHERE CALENDAR_ID=acs.CALENDAR_ID AND SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."') AS DAY_COUNT FROM attendance_calendars acs WHERE acs.SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'" );

$RET = DBGet( "SELECT 'Student' AS TYPE, fssa.DISCOUNT,count(1) AS DAYS,(SELECT count(1)
	FROM attendance_calendar
	WHERE CALENDAR_ID=ac.CALENDAR_ID
	AND SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "') AS ELLIGIBLE
FROM food_service_student_accounts fssa,student_enrollment ssm,attendance_calendar ac
WHERE ac.CALENDAR_ID=ssm.CALENDAR_ID
AND ac.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
AND fssa.STATUS IS NULL
AND ssm.STUDENT_ID=fssa.STUDENT_ID
AND ssm.SYEAR='" . UserSyear() . "'
AND ssm.SCHOOL_ID='" . UserSchool() . "'
AND (ac.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL AND ac.SCHOOL_DATE>=ssm.START_DATE)
GROUP BY fssa.DISCOUNT,ac.CALENDAR_ID", [ 'ELLIGIBLE' => 'bump_dep', 'DAYS' => 'bump_dep' ] );
//echo '<pre>'; var_dump($RET); echo '</pre>';

$RET = DBGet( "SELECT 'User' AS TYPE,'' AS DISCOUNT,count(1) AS DAYS,(SELECT count(1)
	FROM attendance_calendar
	WHERE CALENDAR_ID=ac.CALENDAR_ID
	AND SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "') AS ELLIGIBLE
FROM food_service_staff_accounts fssa,staff s,attendance_calendar ac
WHERE ac.CALENDAR_ID=(SELECT CALENDAR_ID FROM attendance_calendars WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' AND DEFAULT_CALENDAR='Y')
AND ac.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
AND fssa.STATUS IS NULL
AND s.STAFF_ID=fssa.STAFF_ID
AND (s.SCHOOLS IS NULL OR position(CONCAT(',', '" . UserSchool() . "', ',') IN s.SCHOOLS)>0)
GROUP BY ac.CALENDAR_ID", [ 'ELLIGIBLE' => 'bump_dep', 'DAYS' => 'bump_dep' ] );
//echo '<pre>'; var_dump($RET); echo '</pre>';

$RET = DBGet( "SELECT 1 AS PARTICIPATED,'Student' AS TYPE,DISCOUNT
FROM food_service_transactions
WHERE SYEAR='" . UserSyear() . "'
AND SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
AND TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
AND SCHOOL_ID='" . UserSchool() . "'
GROUP BY STUDENT_ID,DISCOUNT", [ 'PARTICIPATED' => 'bump_dep' ] );

$RET = DBGet( "SELECT 1 AS PARTICIPATED,'User' AS TYPE,'' AS DISCOUNT
FROM food_service_staff_transactions
WHERE SYEAR='" . UserSyear() . "'
AND SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
AND TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
AND SCHOOL_ID='" . UserSchool() . "'
GROUP BY STAFF_ID", [ 'PARTICIPATED' => 'bump_dep' ] );

//FJ add translation
$users_locale = [ 'Student' => _( 'Student' ), 'User' => _( 'User' ) ];

if ( 'sales' == $_REQUEST['type_select'] )
{
	$RET = DBGet( "SELECT 'Student' AS TYPE,fsti.SHORT_NAME,fst.DISCOUNT,-sum((SELECT AMOUNT
		FROM food_service_transaction_items
		WHERE TRANSACTION_ID=fsti.TRANSACTION_ID
		AND ITEM_ID=fsti.ITEM_ID)) AS COUNT
	FROM food_service_transactions fst,food_service_transaction_items fsti
	WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID
	AND fst.SYEAR='" . UserSyear() . "'
	AND fst.SCHOOL_ID='" . UserSchool() . "'
	AND fst.SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
	AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
	GROUP BY fsti.SHORT_NAME,fst.DISCOUNT", [ 'SHORT_NAME' => 'bump_count' ] );

	$RET = DBGet( "SELECT 'User' AS TYPE,fsti.SHORT_NAME,'' AS DISCOUNT,-sum((SELECT sum(AMOUNT)
		FROM food_service_staff_transaction_items
		WHERE TRANSACTION_ID=fsti.TRANSACTION_ID
		AND SHORT_NAME=fsti.SHORT_NAME)) AS COUNT
	FROM food_service_staff_transactions fst,food_service_staff_transaction_items fsti
	WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID
	AND fst.SYEAR='" . UserSyear() . "'
	AND fst.SCHOOL_ID='" . UserSchool() . "'
	AND fst.SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
	AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
	GROUP BY fsti.SHORT_NAME", [ 'SHORT_NAME' => 'bump_count' ] );

	$LO_types = [ 0 => [ [] ] ];

	foreach ( (array) $users as $user => $discounts )
	{
		$TMP_types = [ 0 => [] ];

		foreach ( (array) $discounts as $discount => $value )
		{
			$total = array_sum( $types[$user][$discount] );

			$TMP_types[] = [
				'TYPE' => ( empty( $users_locale[$user] ) ? $user : $users_locale[$user] ),
				'DISCOUNT' => $discount,
				'ELLIGIBLE' => number_format( $value['ELLIGIBLE'], 1 ),
				'DAYS_POSSIBLE' => number_format(  ( ! empty( $value['ELLIGIBLE'] ) ? $value['DAYS'] / $value['ELLIGIBLE'] : 0 ), 1 ),
				'TOTAL_ELLIGIBLE' => issetVal( $value['DAYS'] ),
				'PARTICIPATED' => $value['PARTICIPATED'],
				'TOTAL' => '<b>' . format( $total ) . '</b>',
			] + array_map( 'format', $types[$user][$discount] );
		}

		$total = array_sum( $types_totals[$user] );

		$TMP_types[] = [
			'TYPE' => '<b>' . ( empty( $users_locale[$user] ) ? $user : $users_locale[$user] ) . '</b>',
			'DISCOUNT' => '<b>' . _( 'Totals' ) . '</b>',
			'ELLIGIBLE' => '<b>' . number_format( $users_totals['']['ELLIGIBLE'], 1 ) . '</b>',
			'DAYS_POSSIBLE' => '<b>' . number_format(  ( ! empty( $users_totals[$user]['ELLIGIBLE'] ) ? $users_totals[$user]['DAYS'] / $users_totals[$user]['ELLIGIBLE'] : 0 ), 1 ) . '</b>',
			'TOTAL_ELLIGIBLE' => '<b>' . issetVal( $users_totals[$user]['DAYS'] ) . '</b>',
			'PARTICIPATED' => '<b>' . $users_totals[$user]['PARTICIPATED'] . '</b>',
			'TOTAL' => '<b>' . format( $total ) . '</b>',
		] + array_map( 'bold_format', $types_totals[$user] );

		unset( $TMP_types[0] );

		$LO_types[] = $TMP_types;
	}

	$total = array_sum( $types_totals[''] );

	foreach ( (array) $types_totals[''] as $key => $value )
	{
		if ( 0 == $value )
		{
			unset( $types_columns[$key] );
		}
	}

	$LO_types[] = [
		[
			'TYPE' => '<b>' . _( 'Totals' ) . '</b>',
			'ELLIGIBLE' => '<b>' . number_format( $users_totals['']['ELLIGIBLE'], 1 ) . '</b>',
			'DAYS_POSSIBLE' => '<b>' . number_format(  ( ! empty( $users_totals['']['ELLIGIBLE'] ) ? $users_totals['']['DAYS'] / $users_totals['']['ELLIGIBLE'] : 0 ), 1 ) . '</b>',
			'TOTAL_ELLIGIBLE' => '<b>' . $users_totals['']['DAYS'] . '</b>',
			'PARTICIPATED' => '<b>' . $users_totals['']['PARTICIPATED'] . '</b>',
			'TOTAL' => '<b>' . format( $total ) . '</b>',
		] + array_map( 'bold_format', $types_totals[''] ),
	];

	unset( $LO_types[0] );

	$LO_columns = [
		'TYPE' => _( 'Type' ),
		'DISCOUNT' => _( 'Discount' ),
	] + $users_columns + $types_columns + [ 'TOTAL' => _( 'Total' ) ];
}
else
{
	$RET = DBGet( "SELECT 'Student' AS TYPE,fst.DISCOUNT,fsti.SHORT_NAME,count(*) AS COUNT
	FROM food_service_transactions fst,food_service_transaction_items fsti
	WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID
	AND fst.SYEAR='" . UserSyear() . "'
	AND fst.SCHOOL_ID='" . UserSchool() . "'
	AND fst.SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
	AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
	GROUP BY fsti.SHORT_NAME,fst.DISCOUNT", [ 'SHORT_NAME' => 'bump_count' ] );

	$RET = DBGet( "SELECT 'User' AS TYPE,'' AS DISCOUNT,fsti.SHORT_NAME,count(*) AS COUNT
	FROM food_service_staff_transactions fst,food_service_staff_transaction_items fsti
	WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID
	AND fst.SYEAR='" . UserSyear() . "'
	AND fst.SCHOOL_ID='" . UserSchool() . "'
	AND fst.SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
	AND fst.TIMESTAMP BETWEEN '" . $start_date . "' AND date '" . $end_date . "' +1
	GROUP BY fsti.SHORT_NAME", [ 'SHORT_NAME' => 'bump_count' ] );

	$LO_types = [ 0 => [] ];

	foreach ( (array) $users as $user => $discounts )
	{
		$TMP_types = [ 0 => [] ];

		foreach ( (array) $discounts as $discount => $value )
		{
			//FJ fix error Warning: Division by zero
			$TMP_types[] = [
				'TYPE' => ( empty( $users_locale[$user] ) ? $user : $users_locale[$user] ),
				'DISCOUNT' => $discount,
				'ELLIGIBLE' => number_format( $value['ELLIGIBLE'], 1 ),
				'DAYS_POSSIBLE' => ( 0 == $value['ELLIGIBLE'] ? '0.0' : number_format( $value['DAYS'] / $value['ELLIGIBLE'], 1 ) ),
				'TOTAL_ELLIGIBLE' => issetVal( $value['DAYS'] ),
				'PARTICIPATED' => $value['PARTICIPATED'],
			] + $types[$user][$discount];
		}

		$TMP_types[] = [
			'TYPE' => '<b>' . ( empty( $users_locale[$user] ) ? $user : $users_locale[$user] ) . '</b>',
			'DISCOUNT' => '<b>' . _( 'Totals' ) . '</b>',
			'ELLIGIBLE' => '<b>' . number_format( $users_totals[$user]['ELLIGIBLE'], 1 ) . '</b>',
			'DAYS_POSSIBLE' => '<b>' . number_format(
				( empty( $users_totals[$user]['ELLIGIBLE'] ) ?
					0 : $users_totals[$user]['DAYS'] / $users_totals[$user]['ELLIGIBLE'] ),
				1
			) . '</b>',
			'TOTAL_ELLIGIBLE' => '<b>' . issetVal( $users_totals[$user]['DAYS'], '' ) . '</b>',
			'PARTICIPATED' => '<b>' . $users_totals[$user]['PARTICIPATED'] . '</b>',
		] + array_map( 'bold', $types_totals[$user] );

		unset( $TMP_types[0] );

		$LO_types[] = $TMP_types;
	}

	foreach ( (array) $types_totals[''] as $key => $value )
	{
		if ( 0 == $value )
		{
			unset( $types_columns[$key] );
		}
	}

	$LO_types[] = [ [
		'TYPE' => '<b>' . _( 'Totals' ) . '</b>',
		'ELLIGIBLE' => '<b>' . number_format( $users_totals['']['ELLIGIBLE'], 1 ) . '</b>',
		'DAYS_POSSIBLE' => '<b>' . number_format( ( empty( $users_totals['']['ELLIGIBLE'] ) ? 0 : $users_totals['']['DAYS'] / $users_totals['']['ELLIGIBLE'] ), 1 ) . '</b>',
		'TOTAL_ELLIGIBLE' => '<b>' . issetVal( $users_totals['']['DAYS'] ) . '</b>',
		'PARTICIPATED' => '<b>' . $users_totals['']['PARTICIPATED'] . '</b>',
	] + array_map( 'bold', $types_totals[''] ) ];

	unset( $LO_types[0] );

	$LO_columns = [ 'TYPE' => _( 'Type' ), 'DISCOUNT' => _( 'Discount' ) ] + $users_columns + $types_columns;
}

$PHP_tmp_SELF = PreparePHP_SELF();
echo '<form action="' . $PHP_tmp_SELF . '" method="POST">';

DrawHeader(
	_( 'Timeframe' ) . ': ' .
	PrepareDate( $start_date, '_start' ) .
	' &nbsp; ' . _( 'to' ) . ' &nbsp; ' . PrepareDate( $end_date, '_end' ) .
	' ' . Buttons( _( 'Go' ) )
);

DrawHeader( $type_select );
echo '<br />';

$date_start_end_type_url_params = '&day_start=' . $_REQUEST['day_start'] .
	'&month_start=' . $_REQUEST['month_start'] . '&year_start=' . $_REQUEST['year_start'] .
	'&day_end=' . $_REQUEST['day_end'] . '&month_end=' . $_REQUEST['month_end'] .
	'&year_end=' . $_REQUEST['year_end'] . '&type_select=' . $_REQUEST['type_select'];

$tabs = [];

foreach ( (array) $menus_RET as $id => $menu )
{
	$tabs[] = [
		'title' => $menu[1]['TITLE'],
		'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id .
		$date_start_end_type_url_params,
	];
}

$LO_options = [
	'count' => false,
	'download' => false,
	'search' => false,
	'header' => WrapTabs(
		$tabs,
		'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] .
		$date_start_end_type_url_params
	),
];

ListOutput( $LO_types, $LO_columns, '.', '.', [], [ [ '' ] ], $LO_options );
echo '</form>';

/**
 * Number format
 * Local function
 *
 * @uses number_format
 *
 * @param $item
 */
function format( $item )
{
	return number_format( $item, 2 );
}

/**
 * Add bold HTML tags
 * Local function
 *
 * @param $item
 */
function bold( $item )
{
	return '<b>' . $item . '</b>';
}

/**
 * Add bold HTML tags & format number
 * Local function
 *
 * @uses bold & format
 * @param $item
 */
function bold_format( $item )
{
	return bold( format( $item ) );
}

/**
 * Count users' days, elligibile, participated
 * Local function
 *
 * @global $THIS_RET
 * @global $users, $users_totals
 *
 * @param  $value
 * @param  $column
 * @return mixed
 */
function bump_dep( $value, $column )
{
	global $THIS_RET,
		$users,
		$users_totals;

	if ( 'ELLIGIBLE' == $column )
	{
		$value = $THIS_RET['DAYS'] / $value;
	}

	if ( ! $users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']] )
	{
		$users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']] = [ 'DAYS' => 0, 'ELLIGIBLE' => 0, 'PARTICIPATED' => 0 ];
	}

	if ( ! isset( $users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']][$column] ) )
	{
		$users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']][$column] = null;
	}

	$users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']][$column] += $value;

	if ( ! isset( $users_totals[$THIS_RET['TYPE']][$column] ) )
	{
		$users_totals[$THIS_RET['TYPE']][$column] = null;
	}

	$users_totals[$THIS_RET['TYPE']][$column] += $value;

	if ( ! isset( $users_totals[''][$column] ) )
	{
		$users_totals[''][$column] = null;
	}

	$users_totals[''][$column] += $value;

	return $THIS_RET[$column];
}

/**
 * Count types and types totals
 * Local function
 *
 * @global $THIS_RET
 * @global $types, $types_columns, $types_totals
 *
 * @param  $value
 * @param  $column
 * @return mixed
 */
function bump_count( $value, $column )
{
	global $THIS_RET, $types, $types_columns, $types_totals;

	if ( $types[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']] )
	{
		$types[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']][$value] += $THIS_RET['COUNT'];
	}
	else
	{
		$types[$THIS_RET['TYPE']] += [ $THIS_RET['DISCOUNT'] => [ $value => $THIS_RET['COUNT'] ] ];
	}

	if ( ! $types_columns[$value] )
	{
		$types_columns += [ $value => '<span style="color:red">' . $value . '</span>' ];
		$types_totals['Student'][$value] = 0;
		$types_totals['User'][$value] = 0;
		$types_totals[$THIS_RET['TYPE']][$value] = $THIS_RET['COUNT'];
		$types_totals[''][$value] = $THIS_RET['COUNT'];
	}
	else
	{
		$types_totals[$THIS_RET['TYPE']][$value] += $THIS_RET['COUNT'];
		$types_totals[''][$value] += $THIS_RET['COUNT'];
	}

	return $value;
}
