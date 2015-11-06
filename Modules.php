<?php

require_once 'Warehouse.php';

// If no modname found, go back to index
if ( !isset( $_REQUEST['modname'] )
	|| empty( $_REQUEST['modname'] ) )
{
	header( 'Location: index.php' );
	exit();
}

$modname = $_REQUEST['modname'];

if ( !isset( $_REQUEST['modfunc'] ) )
	$_REQUEST['modfunc'] = false;

// not printing PDF
if ( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	// save $_REQUEST vars in session
	if ( empty( $_REQUEST['LO_save'] )
		&& ( mb_strpos( $modname, 'misc/' ) === false
			|| $modname === 'misc/Portal.php'
			|| $modname === 'misc/Registration.php'
			|| $modname === 'misc/Export.php' ) )
	{
		$_SESSION['_REQUEST_vars'] = $_REQUEST;
	}

	// popup window detection
	$_ROSARIO['is_popup'] = isPopup( $modname, $_REQUEST['modfunc'] );

	// AJAX request detection
	$_ROSARIO['not_ajax'] = empty( $_SERVER['HTTP_X_REQUESTED_WITH'] )
		|| $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest';
	
	// output Header HTML
	if ( $_ROSARIO['is_popup']
		|| $_ROSARIO['not_ajax'] )
	{
		Warehouse( 'header' );
	}
}
// print PDF
else
	// start buffer
	ob_start();


/**
 * FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
 * allow PHP scripts in misc/ one by one in place of the whole folder
 */
$allowed = in_array(
	$modname,
	array(
		'misc/ChooseRequest.php',
		'misc/ChooseCourse.php',
		'misc/Portal.php',
		'misc/ViewContact.php'
	)
);

// browse allowed programs and look for requested modname
if ( !$allowed )
{
	require_once 'Menu.php';

	foreach ( (array)$_ROSARIO['Menu'] as $modcat => $programs )
	{
		foreach ( (array)$programs as $program => $title )
		{
			//FJ fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF
			if ( $modname == $program
				|| ( mb_strpos( $program, $modname ) === 0
					&& mb_strpos( $_SERVER['QUERY_STRING'], $program ) === 8 ) )
			{
				$allowed = true;

				//eg: "Student_Billing/Statements.php&_ROSARIO_PDF"
				$_ROSARIO['ProgramLoaded'] = $program;
			}
		}

		if ( $allowed )
			break;
	}
}

if ( $allowed )
{
	// force search_modfunc
	if ( Preferences( 'SEARCH' ) !== 'Y' )
		$_REQUEST['search_modfunc'] = 'list';

	require_once 'modules/' . $modname;
}

// not allowed, hacking attempt?
elseif ( User( 'USERNAME' ) )
{
	require_once 'ProgramFunctions/HackingLog.fnc.php';

	HackingLog();
}

// output Footer HTML
if ( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	Warehouse( 'footer' );
}
