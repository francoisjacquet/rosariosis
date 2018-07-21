<?php
/**
 * Program Title function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Get Program Title
 *
 * @example DrawHeader( ProgramTitle() );
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['HeaderIcon'], uses $_ROSARIO['Menu']
 *
 * @param  string $modname  Specify program/modname (optional).
 *
 * @return string Program title or 'RosarioSIS' if not found
 */
function ProgramTitle( $modname = '' )
{
	global $_ROSARIO;

	if ( empty( $modname ) )
	{
		$modname = $_REQUEST['modname'];
	}

	if ( $modname === 'misc/Portal.php' )
	{
		$_ROSARIO['HeaderIcon'] = 'misc';

		return ParseMLField( Config( 'TITLE' ) );
	}

	// Generate Menu if needed.
	if ( ! isset( $_ROSARIO['Menu'] ) )
	{
		require_once 'Menu.php';
	}

	// Loop modules.
	foreach ( (array) $_ROSARIO['Menu'] as $modcat => $programs )
	{
		// Modname not in current Module, continue.
		if ( ! isset( $programs[ $modname ] ) )
		{
			continue;
		}

		// Set Header Icon.
		if ( ! isset( $_ROSARIO['HeaderIcon'] )
			|| $_ROSARIO['HeaderIcon'] !== false )
		{
			// Get right icon for Teacher Programs.
			if ( mb_substr( $modname, 0, 25 ) === 'Users/TeacherPrograms.php' )
			{
				$_ROSARIO['HeaderIcon'] = mb_substr( $modname, 34, mb_strpos( $modname, '/', 34 ) - 34 );
			}
			else
				$_ROSARIO['HeaderIcon'] = $modcat;
		}

		return $programs[ $modname ];
	}

	// Program not found!
	return 'RosarioSIS';
}
