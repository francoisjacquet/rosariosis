<?php
/**
 * Custom module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 */

// Custom Students programs
if ( $RosarioModules['Students'] )
{
	$menu['Students']['admin'] += array(
		3 => _( 'Utilities' ),
		'Custom/MyReport.php' => _( 'My Report' ),
		'Custom/CreateParents.php' => _( 'Create Parent Users' ),
	);

	$exceptions['Students'] += array(
		'Custom/CreateParents.php' => true,
		'Custom/NotifyParents.php' => true,
	);
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
	$menu['Attendance']['admin'] += array(
		'Custom/AttendanceSummary.php' => _( 'Attendance Summary' )
	);
}
