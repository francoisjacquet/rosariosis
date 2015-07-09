<?php

include( 'Warehouse.php' );

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
if( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	// save $_REQUEST vars in session
	if( empty( $_REQUEST['LO_save'] )
		&& ( mb_strpos( $modname, 'misc/' ) === false
			|| $modname === 'misc/Portal.php'
			|| $modname === 'misc/Registration.php'
			|| $modname === 'misc/Export.php' ) )
		$_SESSION['_REQUEST_vars'] = $_REQUEST;

	$_ROSARIO['is_popup'] = $_ROSARIO['not_ajax'] = false;

	// popup window detection
	//FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	if ( in_array(
			$modname,
			array(
				'misc/ChooseRequest.php',
				'misc/ChooseCourse.php',
				'misc/ViewContact.php'
			)
		)
		|| ( $modname === 'School_Setup/Calendar.php'
			&& $_REQUEST['modfunc'] === 'detail' )
		|| ( in_array(
				$modname,
				array(
					'Scheduling/MassDrops.php',
					'Scheduling/Schedule.php',
					'Scheduling/MassSchedule.php',
					'Scheduling/MassRequests.php',
					'Scheduling/Courses.php'
				)
			)
			&& $_REQUEST['modfunc'] === 'choose_course' ) )
	{
		$_ROSARIO['is_popup'] = true;
	}
	// AJAX request detection
	elseif ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] )
		|| $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest' )
	{
		$_ROSARIO['not_ajax'] = true;
	}
	
	// output Header HTML
	if ( $_ROSARIO['is_popup'] || $_ROSARIO['not_ajax'] )
		Warehouse( 'header' );
}
// print PDF
else
	// start buffer
	ob_start();


$allowed = false;

/**
 * FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
 * allow PHP scripts in misc/ one by one in place of the whole folder
 */
if ( in_array(
		$modname,
		array(
			'misc/ChooseRequest.php',
			'misc/ChooseCourse.php',
			'misc/Portal.php',
			'misc/ViewContact.php'
		)
	) )
{
	$allowed = true;
}

// browse allowed programs and look for requested modname
else
{
	include( 'Menu.php' );

	foreach( $_ROSARIO['Menu'] as $modcat => $programs )
	{
		foreach( $programs as $program => $title )
		{
			//FJ fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF
			if( $modname == $program
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

if( $allowed )
{
	// force search_modfunc
	if( Preferences( 'SEARCH' ) !== 'Y' )
		$_REQUEST['search_modfunc'] = 'list';

	include( 'modules/' . $modname );
}

// not allowed, hacking attempt?
elseif( User( 'USERNAME' ) )
{
	include( 'ProgramFunctions/HackingLog.fnc.php' );

	HackingLog();
}

// output Footer HTML
if( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
	Warehouse( 'footer' );

?>