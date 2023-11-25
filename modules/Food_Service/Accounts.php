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

$header = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=student' ) . '">' .
	( $_REQUEST['type'] === 'student' ?
	'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

$header .= ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=staff' ) . '">' .
	( $_REQUEST['type'] === 'staff' ?
	'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

DrawHeader(  ( $_REQUEST['type'] == 'staff' ? _( 'User' ) : _( 'Student' ) ) . ' &minus; ' . ProgramTitle() );
User( 'PROFILE' ) === 'student' ? '' : DrawHeader( $header );

require_once 'modules/Food_Service/' . ( $_REQUEST['type'] == 'staff' ? 'Users' : 'Students' ) . '/Accounts.php';

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
