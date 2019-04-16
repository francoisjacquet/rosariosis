<?php

$date = RequestedDate( 'date', DBDate(), 'set' );

if ( ! empty( $_REQUEST['type'] ) )
	$_SESSION['FSA_type'] = $_REQUEST['type'];
else
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];


$header = '<a href="Modules.php?modname=' . $_REQUEST['modname'] .
	'&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] .
	'&year_date=' . $_REQUEST['year_date'] . '&type=student">' .
	( ! isset( $_REQUEST['type'] ) || $_REQUEST['type'] === 'student' ?
		'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

$header .= ' | <a href="Modules.php?modname='.$_REQUEST['modname'] .
	'&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] .
	'&year_date=' . $_REQUEST['year_date'] . '&type=staff">' .
	( isset( $_REQUEST['type'] ) && $_REQUEST['type'] === 'staff' ?
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

			DBQuery( 'BEGIN; ' . $sql1 . '; ' . $sql2 . '; ' . $sql3 . '; COMMIT' );

			// Unset modfunc & transaction ID & item ID & redirect URL.
			RedirectURL( array( 'modfunc', 'transaction_id', 'item_id' ) );
		}
	}
	elseif ( DeletePrompt( _( 'Transaction' ) ) )
	{
		require_once 'modules/Food_Service/includes/DeleteTransaction.fnc.php';

		DeleteTransaction( $_REQUEST['transaction_id'], $_REQUEST['type'] );

		// Unset modfunc & transaction ID & redirect URL.
		RedirectURL( array( 'modfunc', 'transaction_id' ) );
	}
}

$transaction_items = array(
	'CASH' => array( 1 => array( 'DESCRIPTION' => _( 'Cash' ), 'COUNT' => 0, 'AMOUNT' => 0 ) ),
	'CHECK' => array( 1 => array( 'DESCRIPTION' => _( 'Check' ), 'COUNT' => 0, 'AMOUNT' => 0 ) ),
	'CREDIT CARD' => array( 1 => array( 'DESCRIPTION' => _( 'Credit Card' ), 'COUNT' => 0, 'AMOUNT' => 0 ) ),
	'DEBIT CARD' => array( 1 => array( 'DESCRIPTION' => _( 'Debit Card' ),'COUNT' => 0,'AMOUNT' => 0 ) ),
	'TRANSFER' => array( 1 => array( 'DESCRIPTION' => _( 'Transfer' ), 'COUNT' => 0, 'AMOUNT' => 0 ) ),
	'' => array( 1 => array( 'DESCRIPTION' => 'n/s', 'COUNT' => 0, 'AMOUNT' => 0 ) ),
);

$menus_RET = DBGet( "SELECT TITLE
	FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER" );

// echo '<pre>'; var_dump($menus_RET); echo '</pre>';
$items = DBGet( "SELECT SHORT_NAME,DESCRIPTION,0 AS COUNT
	FROM FOOD_SERVICE_ITEMS
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER", array(), array( 'SHORT_NAME' ) );

// echo '<pre>'; var_dump($items); echo '</pre>';

$types = array(
	'DEPOSIT' => array(
		'DESCRIPTION' => _( 'Deposit' ),
		'COUNT' => 0,
		'AMOUNT' => 0,
		'ITEMS' => $transaction_items,
	),
	'CREDIT' => array(
		'DESCRIPTION' => _('Credit'),
		'COUNT' => 0,
		'AMOUNT' => 0,
		'ITEMS' => $transaction_items,
	),
	'DEBIT' => array(
		'DESCRIPTION' => _('Debit'),
		'COUNT' => 0,
		'AMOUNT' => 0,
		'ITEMS' => $transaction_items,
	),
);

foreach ( (array) $menus_RET as $menu )
{
	$types += array(
		$menu['TITLE'] => array(
			'DESCRIPTION' => $menu['TITLE'],
			'COUNT' => 0,
			'AMOUNT' => 0,
			'ITEMS' => $items,
		),
	);
}


require_once 'modules/Food_Service/' .
	( $_REQUEST['type'] === 'staff' ? 'Users' : 'Students' ) . '/ActivityReport.php';

// echo '<pre>'; var_dump($RET); echo '</pre>';

// echo '<pre>'; var_dump($types); echo '</pre>';

// echo '<pre>'; var_dump($LO_types); echo '</pre>';

function types_locale( $type ) {
	$types = array(
		'Deposit' => _( 'Deposit' ),
		'Credit' => _( 'Credit' ),
		'Debit' => _( 'Debit' ),
	);

	if (array_key_exists( $type, $types ) )
	{
		return $types[ $type ];
	}

	return $type;
}

function options_locale( $option )
{
	$options = array(
		'Cash ' => _( 'Cash' ),
		'Check' => _( 'Check' ),
		'Credit Card' => _( 'Credit Card' ),
		'Debit Card' => _( 'Debit Card' ),
		'Transfer' => _( 'Transfer' ),
	);

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
		$types += array(
			$value => array(
				'DESCRIPTION' => '<span style="color:red">' . $value . '</span>',
				'COUNT' => 1,
				'ITEMS' => array(),
				'AMOUNT' => $THIS_RET['AMOUNT'],
			),
		);
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
		$types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'][ $value ][1]['AMOUNT'] += $THIS_RET['AMOUNT'];
	}
	else
	{
		$types[ $THIS_RET['TRANSACTION_SHORT_NAME'] ]['ITEMS'] += array(
			$value => array(
				1 => array(
					'DESCRIPTION' => '<span style="color:red">' . $value . '</span>',
					'COUNT' => 1,
					'AMOUNT' => $THIS_RET['AMOUNT'],
				),
			),
		);
	}

	return $value;
}
