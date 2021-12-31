<?php
/**
 * School Setup module Menu entries
 *
 * @uses $menu global var
 *
 * @package RosarioSIS
 * @subpackage modules
 *
 * @see  Menu.php in root folder
 */

$menu['School_Setup']['admin'] = [
	'title' => _( 'School' ),
	'default' => 'School_Setup/Calendar.php',
	'School_Setup/PortalNotes.php' => _( 'Portal Notes' ),
	'School_Setup/PortalPolls.php' => _( 'Portal Polls' ),
	'School_Setup/Calendar.php' => _( 'Calendars' ),
	'School_Setup/MarkingPeriods.php' => _( 'Marking Periods' ),
	'School_Setup/Periods.php' => _( 'Periods' ),
	'School_Setup/GradeLevels.php' => _( 'Grade Levels' ),
	'School_Setup/Schools.php' => _( 'School Information' ),
	1 => _( 'Setup' ),
	'School_Setup/CopySchool.php' => _( 'Copy School' ),
	'School_Setup/SchoolFields.php' => _( 'School Fields' ),
	'School_Setup/Configuration.php' => _( 'Configuration' ),
	2 => _( 'Utilities' ),
	'School_Setup/Rollover.php' => _( 'Rollover' ),
	3 => dgettext( 'Access_Log', _( 'Security' ) ),
	'School_Setup/AccessLog.php' => _( 'Access Log' ),
	'School_Setup/DatabaseBackup.php' => _( 'Database Backup' ),
];

$menu['School_Setup']['teacher'] = [
	'title' => _( 'School' ),
	'default' => 'School_Setup/Calendar.php',
	'School_Setup/Schools.php' => _( 'School Information' ),
	'School_Setup/Calendar.php' => _( 'Calendars' ),
	'School_Setup/MarkingPeriods.php' => _( 'Marking Periods' ),
	// Add Periods to teachers.
	'School_Setup/Periods.php' => _( 'Periods' ),
];

$menu['School_Setup']['parent'] = [
	'title' => _( 'School' ),
	'default' => 'School_Setup/Calendar.php',
	'School_Setup/Schools.php' => _( 'School Information' ),
	'School_Setup/Calendar.php' => _( 'Calendars' ),
	// Add Marking Periods to parents & students.
	'School_Setup/MarkingPeriods.php' => _( 'Marking Periods' ),
];

$exceptions['School_Setup'] = [
	'School_Setup/PortalNotes.php' => true,
	'School_Setup/Rollover.php' => true,
];
