<?php
/**
 * Modules
 *
 * Warehouse header
 * Get requested program / modname, if allowed
 * Warehouse footer
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

// If no modname found, go back to index.
if ( empty( $_REQUEST['modname'] ) )
{
	header( 'Location: index.php' );
	exit();
}

$modname = $_REQUEST['modname'];

if ( ! isset( $_REQUEST['modfunc'] ) )
{
	$_REQUEST['modfunc'] = false;
}

$_ROSARIO['page'] = 'modules';

// Output Header HTML.
Warehouse( 'header' );

// Performance: up to 10% faster compared to loading Menu.php.
if ( AllowUse() )
{
	// Force search_modfunc to list.
	if ( Preferences( 'SEARCH' ) !== 'Y' )
	{
		$_REQUEST['search_modfunc'] = 'list';
	}
	elseif ( ! isset( $_REQUEST['search_modfunc'] ) )
	{
		$_REQUEST['search_modfunc'] = '';
	}

	if ( substr( $modname, -4, 4 ) !== '.php'
		|| strpos( $modname, '..' ) !== false
		/*|| ! is_file( 'modules/' . $modname )*/ )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';

		HackingLog();
	}
	else
	{
		require_once 'modules/' . $modname;
	}
}

// Not allowed, hacking attempt?
elseif ( User( 'USERNAME' ) )
{
	require_once 'ProgramFunctions/HackingLog.fnc.php';

	HackingLog();
}

/**
 * Save $_REQUEST vars in session: used to recreate $_REQUEST in Bottom.php
 * Note: Code duplicated inside ErrorMessage() for fatal errors
 *
 * @since 11.5 Copy $_REQUEST to $_SESSION['_REQUEST_vars'] last
 */
if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
	&& empty( $_REQUEST['LO_save'] )
	&& ( mb_strpos( $modname, 'misc/' ) === false
		|| $modname === 'misc/Portal.php' ) )
{
	$_SESSION['_REQUEST_vars'] = $_REQUEST;
}

// Output Footer HTML.
Warehouse( 'footer' );
