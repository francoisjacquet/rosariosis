<?php

$date = RequestedDate( 'date', DBDate(), 'set' );

if ( empty( $_SESSION['FSA_type'] ) )
{
	$_SESSION['FSA_type'] = 'student';
}

if ( ! empty( $_REQUEST['type'] ) )
{
	$_SESSION['FSA_type'] = $_REQUEST['type'];
}
else
{
	$_REQUEST['type'] = $_SESSION['FSA_type'];
}

$header = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] .
	'&year_date=' . $_REQUEST['year_date'] . '&type=student' ) . '">' .
	( $_REQUEST['type'] === 'student' ?
		'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

$header .= ' | <a href="' . URLEscape( 'Modules.php?modname='.$_REQUEST['modname'] .
	'&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] .
	'&year_date=' . $_REQUEST['year_date'] . '&type=staff' ) . '">' .
	( $_REQUEST['type'] === 'staff' ?
		'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

DrawHeader(($_REQUEST['type']=='staff' ? _('User') : _('Student')).' &minus; '.ProgramTitle());
User( 'PROFILE' ) === 'student'?'':DrawHeader($header);

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( $_REQUEST['item_id'] != '' )
	{
		if ( DeletePrompt( _( 'Transaction Item' ) ) )
		{
			require_once 'modules/Food_Service/includes/DeleteTransactionItem.fnc.php';

			DeleteTransactionItem(
				$_REQUEST['transaction_id'],
				$_REQUEST['item_id'],
				$_REQUEST['type']
			);

			// Unset modfunc & transaction ID & item ID & redirect URL.
			RedirectURL( [ 'modfunc', 'transaction_id', 'item_id' ] );
		}
	}
	elseif ( DeletePrompt( _( 'Transaction' ) ) )
	{
		require_once 'modules/Food_Service/includes/DeleteTransaction.fnc.php';

		DeleteTransaction( $_REQUEST['transaction_id'], $_REQUEST['type'] );

		// Unset modfunc & transaction ID & redirect URL.
		RedirectURL( [ 'modfunc', 'transaction_id' ] );
	}
}

$transaction_items = [
	'CASH' => [ 1 => [ 'DESCRIPTION' => _( 'Cash' ), 'COUNT' => 0, 'AMOUNT' => 0 ] ],
	'CHECK' => [ 1 => [ 'DESCRIPTION' => _( 'Check' ), 'COUNT' => 0, 'AMOUNT' => 0 ] ],
	'CREDIT CARD' => [ 1 => [ 'DESCRIPTION' => _( 'Credit Card' ), 'COUNT' => 0, 'AMOUNT' => 0 ] ],
	'DEBIT CARD' => [ 1 => [ 'DESCRIPTION' => _( 'Debit Card' ),'COUNT' => 0,'AMOUNT' => 0 ] ],
	'TRANSFER' => [ 1 => [ 'DESCRIPTION' => _( 'Transfer' ), 'COUNT' => 0, 'AMOUNT' => 0 ] ],
	'' => [ 1 => [ 'DESCRIPTION' => 'n/s', 'COUNT' => 0, 'AMOUNT' => 0 ] ],
];

$menus_RET = DBGet( "SELECT TITLE
	FROM food_service_menus WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

// echo '<pre>'; var_dump($menus_RET); echo '</pre>';
$items = DBGet( "SELECT SHORT_NAME,DESCRIPTION,0 AS COUNT
	FROM food_service_items
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'SHORT_NAME' ] );

// echo '<pre>'; var_dump($items); echo '</pre>';

$types = [
	'DEPOSIT' => [
		'DESCRIPTION' => _( 'Deposit' ),
		'COUNT' => 0,
		'AMOUNT' => 0,
		'ITEMS' => $transaction_items,
	],
	'CREDIT' => [
		'DESCRIPTION' => _('Credit'),
		'COUNT' => 0,
		'AMOUNT' => 0,
		'ITEMS' => $transaction_items,
	],
	'DEBIT' => [
		'DESCRIPTION' => _('Debit'),
		'COUNT' => 0,
		'AMOUNT' => 0,
		'ITEMS' => $transaction_items,
	],
];

foreach ( (array) $menus_RET as $menu )
{
	$types += [
		$menu['TITLE'] => [
			'DESCRIPTION' => $menu['TITLE'],
			'COUNT' => 0,
			'AMOUNT' => 0,
			'ITEMS' => $items,
		],
	];
}


require_once 'modules/Food_Service/' .
	( $_REQUEST['type'] === 'staff' ? 'Users' : 'Students' ) . '/ActivityReport.php';

// echo '<pre>'; var_dump($RET); echo '</pre>';

// echo '<pre>'; var_dump($types); echo '</pre>';

// echo '<pre>'; var_dump($LO_types); echo '</pre>';

function types_locale( $type ) {
	$types = [
		'Deposit' => _( 'Deposit' ),
		'Credit' => _( 'Credit' ),
		'Debit' => _( 'Debit' ),
	];

	if (array_key_exists( $type, $types ) )
	{
		return $types[ $type ];
	}

	return $type;
}

function options_locale( $option )
{
	$options = [
		'Cash ' => _( 'Cash' ),
		'Check' => _( 'Check' ),
		'Credit Card' => _( 'Credit Card' ),
		'Debit Card' => _( 'Debit Card' ),
		'Transfer' => _( 'Transfer' ),
	];

	if ( array_key_exists( $option, $options ) )
	{
		return $options[ $option ];
	}

	return $option;
}

function last( &$array )
{
	end( $array );

	return key( $array );
}

function bump_count( $value )
{
	global $THIS_RET,
		$types;

	if ( $types[ $value ] )
	{
		$types[ $value ]['COUNT']++;
		$types[ $value ]['AMOUNT'] += $THIS_RET['AMOUNT'];
	}
	else
	{
		$types += [
			$value => [
				'DESCRIPTION' => '<span style="color:red">' . $value . '</span>',
				'COUNT' => 1,
				'ITEMS' => [],
				'AMOUNT' => $THIS_RET['AMOUNT'],
			],
		];
	}

	return $value;
}

function bump_items_count( $value )
{
	global $THIS_RET,
		$types;

	if ( $types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'][ $value ] )
	{
		$types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'][ $value ][1]['COUNT']++;

		if ( ! isset( $types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'][ $value ][1]['AMOUNT'] ) )
		{
			$types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'][ $value ][1]['AMOUNT'] = 0;
		}

		$types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'][ $value ][1]['AMOUNT'] += $THIS_RET['AMOUNT'];
	}
	else
	{
		$types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'] += [
			$value => [
				1 => [
					'DESCRIPTION' => '<span style="color:red">' . $value . '</span>',
					'COUNT' => 1,
					'AMOUNT' => $THIS_RET['AMOUNT'],
				],
			],
		];
	}

	return $value;
}
