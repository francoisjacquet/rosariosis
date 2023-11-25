<?php

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01', 'set' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate(), 'set' );

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
	'&day_start=' . $_REQUEST['day_start'] . '&month_start=' . $_REQUEST['month_start'] . '&year_start=' . $_REQUEST['year_start'] .
	'&day_end=' . $_REQUEST['day_end'] . '&month_end=' . $_REQUEST['month_end'] . '&year_end=' . $_REQUEST['year_end'] .
	'&type=student' ) . '">' .
	( $_REQUEST['type'] === 'student' ?
	'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

$header .= ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&day_start=' . $_REQUEST['day_start'] . '&month_start=' . $_REQUEST['month_start'] . '&year_start=' . $_REQUEST['year_start'] .
	'&day_end=' . $_REQUEST['day_end'] . '&month_end=' . $_REQUEST['month_end'] . '&year_end=' . $_REQUEST['year_end'] .
	'&type=staff' ) . '">' .
	( $_REQUEST['type'] === 'staff' ?
	'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

DrawHeader(  ( $_REQUEST['type'] == 'staff' ? _( 'User' ) : _( 'Student' ) ) . ' &minus; ' . ProgramTitle() );
User( 'PROFILE' ) === 'student' ? '' : DrawHeader( $header );

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

$types = [ 'DEPOSIT' => _( 'Deposit' ), 'CREDIT' => _( 'Credit' ), 'DEBIT' => _( 'Debit' ) ];

$menus_RET = DBGet( "SELECT TITLE
	FROM food_service_menus
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

$type_select = ' &mdash; <label>' . _( 'Type' ) . ': <select name=type_select>
	<option value="">' . _( 'Not Specified' ) . '</option>';

foreach ( (array) $types as $short_name => $type )
{
	$type_select .= '<option value="' . AttrEscape( $short_name ) . '"' .
		( isset( $_REQUEST['type_select'] ) && $_REQUEST['type_select'] == $short_name ? ' selected' : '' ) . '>' .
		$type . '</option>';
}

foreach ( (array) $menus_RET as $menu )
{
	$type_select .= '<option value="' . AttrEscape( $menu['TITLE'] ) . '"' .
		( isset( $_REQUEST['type_select'] ) && $_REQUEST['type_select'] == $menu['TITLE'] ? ' selected' : '' ) . '>' .
		$menu['TITLE'] . '</option>';
}

$type_select .= '</select></label>';

//FJ add translation
/**
 * @param $type
 * @return mixed
 */
function types_locale( $type )
{
	$types = [ 'Deposit' => _( 'Deposit' ), 'Credit' => _( 'Credit' ), 'Debit' => _( 'Debit' ) ];

	if ( array_key_exists( $type, $types ) )
	{
		return $types[$type];
	}

	return $type;
}

/**
 * @param $option
 * @return mixed
 */
function options_locale( $option )
{
	$options = [ 'Cash ' => _( 'Cash' ), 'Check' => _( 'Check' ), 'Credit Card' => _( 'Credit Card' ), 'Debit Card' => _( 'Debit Card' ), 'Transfer' => _( 'Transfer' ) ];

	if ( array_key_exists( $option, $options ) )
	{
		return $options[$option];
	}

	return $option;
}

require_once 'modules/Food_Service/' . ( $_REQUEST['type'] == 'staff' ? 'Users' : 'Students' ) . '/Statements.php';

/**
 * @param $value
 * @return mixed
 */
function red( $value )
{
	if ( $value < 0 )
	{
		return '<span style="color:red">' . $value . '</span>';
	}
	else
	{
		return $value;
	}
}
