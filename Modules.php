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

// Save $_REQUEST vars in session: used to recreate $_REQUEST in Bottom.php.
if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
	&& empty( $_REQUEST['LO_save'] )
	&& ( mb_strpos( $modname, 'misc/' ) === false
		|| $modname === 'misc/Portal.php' )
	&& $modname !== 'Reports/SavedReports.php' )
{
	$_SESSION['_REQUEST_vars'] = $_REQUEST;
}

// Set Popup window detection.
isPopup( $modname, $_REQUEST['modfunc'] );

// Output Header HTML.
Warehouse( 'header' );


/**
 * FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
 * allow PHP scripts in misc/ one by one in place of the whole folder.
 */
$allowed = in_array(
	$modname,
	[
		'misc/ChooseRequest.php',
		'misc/ChooseCourse.php',
		'misc/Portal.php',
		'misc/ViewContact.php',
	]
);

// Browse allowed programs and look for requested modname.
if ( ! $allowed )
{
	// Generate Menu.
	require_once 'Menu.php';

	// @since 10.3 Fix program not found when query string is URL encoded.
	$query_string = urldecode( $_SERVER['QUERY_STRING'] );

	foreach ( (array) $_ROSARIO['Menu'] as $modcat => $programs )
	{
		foreach ( (array) $programs as $program => $title )
		{
			if ( is_int( $program ) )
			{
				continue;
			}

			// FJ fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF.
			if ( $modname == $program
				|| ( mb_strpos( $program, $modname ) === 0
					&& mb_strpos( $query_string, $program ) === 8 ) )
			{
				$allowed = true;

				// Eg: "Student_Billing/Statements.php&_ROSARIO_PDF".
				$_ROSARIO['ProgramLoaded'] = $program;

				break 2;
			}
		}
	}
}

if ( $allowed )
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

// Output Footer HTML.
Warehouse( 'footer' );
