<?php

require_once 'modules/Food_Service/includes/FS_Icons.inc.php';

$_REQUEST['menu_id'] = issetVal( $_REQUEST['menu_id'] );

if ( $_REQUEST['modfunc'] === 'select' )
{
	$_SESSION['FSA_type'] = $_REQUEST['fsa_type'];

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

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

// @since 11.3.2 Add User key to $_SESSION['FSA_sale']
$fsa_sale_user_key = $_REQUEST['type'] === 'student' ?
	'student_' . UserStudentID() : 'staff_' . UserStaffID();

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

$header = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&modfunc=select&menu_id=' . $_REQUEST['menu_id'] . '&fsa_type=student' ) . '">' .
	( $_REQUEST['type'] === 'student' ?
	'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

$header .= ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&modfunc=select&menu_id=' . $_REQUEST['menu_id'] . '&fsa_type=staff' ) . '">' .
	( $_REQUEST['type'] === 'staff' ?
	'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

DrawHeader( ( $_SESSION['FSA_type'] == 'staff' ? _( 'User' ) : _( 'Student' ) ) . ' &minus; ' . ProgramTitle() );

User( 'PROFILE' ) === 'student' ? '' : DrawHeader( $header );

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

	unset( $_SESSION['FSA_sale'][ $fsa_sale_user_key ] );
}
else
{
	$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
}

$menu_title = issetVal( $menus_RET[$_REQUEST['menu_id']][1]['TITLE'], '' );

if ( $_REQUEST['modfunc'] === 'add' )
{
	if ( ! empty( $_REQUEST['item_sn'] ) )
	{
		$_SESSION['FSA_sale'][ $fsa_sale_user_key ][] = $_REQUEST['item_sn'];
	}

	// Unset modfunc & item sn & redirect URL.
	RedirectURL( [ 'modfunc', 'item_sn' ] );
}

if ( $_REQUEST['modfunc'] === 'remove' )
{
	if ( $_REQUEST['id'] !== '' )
	{
		unset( $_SESSION['FSA_sale'][ $fsa_sale_user_key ][$_REQUEST['id']] );
	}

	// Unset modfunc & ID & redirect URL.
	RedirectURL( [ 'modfunc', 'id' ] );
}

require_once 'modules/Food_Service/' . ( $_SESSION['FSA_type'] == 'staff' ? 'Users/' : 'Students/' ) . '/ServeMenus.php';

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
