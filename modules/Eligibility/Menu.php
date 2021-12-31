<?php
/**
 * Eligibility module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Eligibility']['admin'] = [
	'title' => _( 'Activities' ),
	'default' => 'Eligibility/Student.php',
	'Eligibility/Student.php' => _( 'Student Screen' ),
	'Eligibility/AddActivity.php' => _( 'Add Activity' ),
	1 => _( 'Reports' ),
	'Eligibility/StudentList.php' => _( 'Student List' ),
	'Eligibility/TeacherCompletion.php' => _( 'Teacher Completion' ),
	2 => _( 'Setup' ),
	'Eligibility/Activities.php' => _( 'Activities' ),
	'Eligibility/EntryTimes.php' => _( 'Entry Times' )
];

$menu['Eligibility']['teacher'] = [
	'title' => _( 'Activities' ),
	'default' => 'Eligibility/EnterEligibility.php',
	'Eligibility/EnterEligibility.php' => _( 'Enter Eligibility' )
];

$menu['Eligibility']['parent'] = [
	'title' => _( 'Activities' ),
	'default' => 'Eligibility/Student.php',
	'Eligibility/Student.php' => _( 'Student Screen' ),
	'Eligibility/StudentList.php' => _( 'Student List' )
];

if ( $RosarioModules['Users'] )
{
	$menu['Users']['admin'] += [
		'Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php' => _( 'Enter Eligibility' )
	];
}

$exceptions['Eligibility'] = [
	'Eligibility/AddActivity.php' => true
];

if ( $RosarioModules['Users'] )
{
	$exceptions['Users'] += [
		'Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php' => true
	];
}
