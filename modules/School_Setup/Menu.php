<?php
$menu['School_Setup']['admin'] = array(
	'default' => 'School_Setup/Calendar.php',
	'School_Setup/PortalNotes.php' => _( 'Portal Notes' ),
	//FJ Portal Polls
	'School_Setup/PortalPolls.php' => _( 'Portal Polls' ),
	//FJ add Database Backup
	'School_Setup/MarkingPeriods.php' => _( 'Marking Periods' ),
	'School_Setup/Calendar.php' => _( 'Calendars' ),
	'School_Setup/Periods.php' => _( 'Periods' ),
	'School_Setup/GradeLevels.php' => _( 'Grade Levels' ),
	'School_Setup/Rollover.php' => _( 'Rollover' ),
	'School_Setup/DatabaseBackup.php'  => _( 'Database Backup' ),
	1 => _( 'School' ),
	'School_Setup/Schools.php' => _( 'School Information' ),
	'School_Setup/Schools.php&new_school=true' => _( 'Add a School' ),
	'School_Setup/CopySchool.php' => _( 'Copy School' ),
	//FJ add School Fields
	'School_Setup/SchoolFields.php' => _( 'School Fields' ),
	//FJ add School Configuration
	'School_Setup/Configuration.php'  => _( 'School Configuration' ),
);

$menu['School_Setup']['teacher'] = array(
	'default' => 'School_Setup/Calendar.php',
	'School_Setup/Schools.php' => _( 'School Information' ),
	'School_Setup/MarkingPeriods.php' => _( 'Marking Periods' ),
	'School_Setup/Calendar.php' => _( 'Calendars' ),
	//FJ add Periods to teachers
	'School_Setup/Periods.php' => _( 'Periods' )
);

$menu['School_Setup']['parent'] = array(
	'default' => 'School_Setup/Calendar.php',
	'School_Setup/Schools.php' => _( 'School Information' ),
	'School_Setup/Calendar.php' => _( 'Calendars' )
);

$exceptions['School_Setup'] = array(
	'School_Setup/PortalNotes.php' => true,
	'School_Setup/Schools.php&new_school=true' => true,
	'School_Setup/Rollover.php' => true
);

//FJ add translation
_( 'School Setup' );
?>