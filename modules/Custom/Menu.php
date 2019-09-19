<?php
/**
 * Custom module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

// Custom Students programs
if ( $RosarioModules['Students'] )
{
	$menu['Students']['admin'] += array(
		3 => _( 'Utilities' ),
		'Custom/MyReport.php' => _( 'My Report' ),
		'Custom/CreateParents.php' => _( 'Create Parent Users' ),
		'Custom/RemoveAccess.php' => _( 'Remove Access' ),
	);

	$exceptions['Students'] += array(
		'Custom/CreateParents.php' => true,
		'Custom/NotifyParents.php' => true,
	);

	$menu['Students']['parent'] += array(
		'Custom/Registration.php' => _( 'Registration' ),
	);

	// FJ disable Registration for students.
	/*if ( User( 'PROFILE' ) === 'student' )
	{
		unset( $menu['Students']['parent']['Custom/Registration.php'] );
	}*/
}

// Custom Users programs
if ( $RosarioModules['Users'] )
{
	$menu['Users']['admin'] += array(
		3 => _( 'Utilities' ),
		'Custom/NotifyParents.php' => _( 'Notify Parents' ),
	);
}

// Custom Attendance programs
if ( $RosarioModules['Attendance'] )
{
	// Place Attendance Summary program before Utilities separator.
	$utilities_pos = array_search( 2, array_keys( $menu['Attendance']['admin'] ) );

	$menu['Attendance']['admin'] = array_merge(
	    array_slice( $menu['Attendance']['admin'], 0, $utilities_pos ),
	    array( 'Custom/AttendanceSummary.php' => _( 'Attendance Summary' ) ),
	    array_slice( $menu['Attendance']['admin'], $utilities_pos )
	);
}
