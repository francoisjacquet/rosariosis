<?php
/**
 * Attendance module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Attendance']['admin'] = array(
	'title' => _( 'Attendance' ),
	'default' => 'Attendance/Administration.php',
	'Attendance/Administration.php' => _( 'Administration' ),
	'Attendance/AddAbsences.php' => _( 'Add Absences' ),
	1 => _( 'Reports' ),
	'Attendance/TeacherCompletion.php' => _( 'Teacher Completion' ),
	'Attendance/Percent.php' => _( 'Average Daily Attendance' ),
	'Attendance/Percent.php&list_by_day=true' => _( 'Average Attendance by Day' ),
	'Attendance/DailySummary.php' => _( 'Attendance Chart' ),
	'Attendance/StudentSummary.php' => _( 'Absence Summary' ),
	2 => _( 'Utilities' ),
	'Attendance/FixDailyAttendance.php' => _( 'Recalculate Daily Attendance' ),
	'Attendance/DuplicateAttendance.php' => _( 'Delete Duplicate Attendance' ),
	3 => _( 'Setup' ),
	'Attendance/AttendanceCodes.php' => _( 'Attendance Codes' ),
);

$menu['Attendance']['teacher'] = array(
	'title' => _( 'Attendance' ),
	'default' => 'Attendance/TakeAttendance.php',
	'Attendance/TakeAttendance.php' => _( 'Take Attendance' ),
	'Attendance/DailySummary.php' => _( 'Attendance Chart' ),
	'Attendance/StudentSummary.php' => _( 'Absence Summary' )
);

$menu['Attendance']['parent'] = array(
	'title' => _( 'Attendance' ),
	'default' => 'Attendance/StudentSummary.php',
	'Attendance/StudentSummary.php' => _( 'Absences' ),
	'Attendance/DailySummary.php' => _( 'Daily Summary' )
);

if ( $RosarioModules['Users'] )
{
	$menu['Users']['admin'] += array(
		'Users/TeacherPrograms.php&include=Attendance/TakeAttendance.php' => _( 'Take Attendance' ),
	);
}

$exceptions['Attendance'] = array(
	'Attendance/AddAbsences.php' => true
);
