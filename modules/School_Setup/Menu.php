<?php
$menu['School_Setup']['admin'] = array(
						'School_Setup/PortalNotes.php'=>_('Portal Notes'),
//modif Francois: Portal Polls
						'School_Setup/PortalPolls.php'=>_('Portal Polls'),
//modif Francois: add Database Backup
						'School_Setup/DatabaseBackup.php' =>_('Database Backup'),
						'School_Setup/Schools.php'=>_('School Information'),
						'School_Setup/Schools.php&new_school=true'=>_('Add a School'),
						'School_Setup/CopySchool.php'=>_('Copy School'),
						'School_Setup/MarkingPeriods.php'=>_('Marking Periods'),
						'School_Setup/Calendar.php'=>_('Calendars'),
						'School_Setup/Periods.php'=>_('Periods'),
						'School_Setup/GradeLevels.php'=>_('Grade Levels'),
						'School_Setup/Rollover.php'=>_('Rollover'),
//modif Francois: add School Configuration
						'School_Setup/Configuration.php' =>_('School Configuration'),
					);

$menu['School_Setup']['teacher'] = array(
						'School_Setup/Schools.php'=>_('School Information'),
						'School_Setup/MarkingPeriods.php'=>_('Marking Periods'),
						'School_Setup/Calendar.php'=>_('Calendars'),
//modif Francois: add Periods to teachers
						'School_Setup/Periods.php'=>_('Periods')
					);

$menu['School_Setup']['parent'] = array(
						'School_Setup/Schools.php'=>_('School Information'),
						'School_Setup/Calendar.php'=>_('Calendars')
					);

$exceptions['School_Setup'] = array(
						'School_Setup/PortalNotes.php'=>true,
						'School_Setup/Schools.php&new_school=true'=>true,
						'School_Setup/Rollover.php'=>true
					);
//modif Francois: add translation
_('School Setup');
?>