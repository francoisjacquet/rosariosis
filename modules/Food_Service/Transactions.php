<?php

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

/*if ( $_REQUEST['type']=='staff')
{
$tabcolor_s = '#DFDFDF'; $textcolor_s = '#999999';
$tabcolor_u = Preferences('HEADER'); $textcolor_u = '#FFFFFF';
}
else
{
$tabcolor_s = Preferences('HEADER'); $textcolor_s = '#FFFFFF';
$tabcolor_u = '#DFDFDF'; $textcolor_u = '#999999';
}*/

$header = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=student' ) . '">' .
	( $_REQUEST['type'] === 'student' ?
	'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

$header .= ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=staff' ) . '">' .
	( $_REQUEST['type'] === 'staff' ?
	'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

DrawHeader(  ( $_REQUEST['type'] == 'staff' ? _( 'User' ) : _( 'Student' ) ) . ' &minus; ' . ProgramTitle() );
User( 'PROFILE' ) === 'student' ? '' : DrawHeader( $header );

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Transaction' ) ) )
	{
		require_once 'modules/Food_Service/includes/DeleteTransaction.fnc.php';

		DeleteTransaction( $_REQUEST['id'], $_REQUEST['type'] );

		// Unset modfunc & ID redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

require_once 'modules/Food_Service/' . ( $_REQUEST['type'] == 'staff' ? 'Users' : 'Students' ) . '/Transactions.php';

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

/**
 * @param $value
 * @return mixed
 */
function is_money( $value )
{
	if ( $value > 0 )
	{
		if ( ! mb_strpos( $value, '.' )
			&& $value >= 100 )
		{
			// We deduce value was entered without decimal point, add it.
			$value = $value / 100;
		}

		// Fix SQL error:
		// A field with precision 9, scale 2 must round to an absolute value less than 10^7.

		if ( ! mb_strpos( $value, '.' )
			&& mb_strlen( $value ) > 7 )
		{
			return false;
		}

		if ( mb_strpos( $value, '.' )
			&& $value > 9999999.99 )
		{
			return false;
		}

		return (float) $value;
	}
	else
	{
		return false;
	}
}
