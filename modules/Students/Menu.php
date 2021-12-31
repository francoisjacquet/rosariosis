<?php
/**
 * Students module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Students']['admin'] = [
	'title' => _( 'Students' ),
	'default' => 'Students/Student.php',
	'Students/Student.php' => _( 'Student Info' ),
	// Note: Do NOT merge with Student Info. We'd lose Profile permission to Add.
	'Students/Student.php&include=General_Info&student_id=new' => _( 'Add a Student' ),
	'Students/AssignOtherInfo.php' => _( 'Group Assign Student Info' ),
	'Students/AddUsers.php' => _( 'Associate Parents with Students' ),
	1 => _( 'Reports' ),
	'Students/AdvancedReport.php' => _( 'Advanced Report' ),
	'Students/AddDrop.php' => _( 'Add / Drop Report' ),
	//FJ add Student Breakdown
	'Students/StudentBreakdown.php' => _( 'Student Breakdown' ),
	'Students/Letters.php' => _( 'Print Letters' ),
	'Students/StudentLabels.php' => _( 'Print Student Labels' ),
	'Students/PrintStudentInfo.php' => _( 'Print Student Info' ),
	2 => _( 'Setup' ),
	'Students/StudentFields.php' => _( 'Student Fields' ),
	'Students/EnrollmentCodes.php' => _( 'Enrollment Codes' ),
];

$menu['Students']['teacher'] = [
	'title' => _( 'Students' ),
	'default' => 'Students/Student.php',
	'Students/Student.php' => _( 'Student Info' ),
	'Students/AddUsers.php' => _( 'Associated Parents' ),
	1 => _( 'Reports' ),
	'Students/AdvancedReport.php' => _( 'Advanced Report' ),
	'Students/StudentLabels.php' => _( 'Print Student Labels' ),
	'Students/Letters.php' => _( 'Print Letters' ),
];

$menu['Students']['parent'] = [
	'title' => _( 'Students' ),
	'default' => 'Students/Student.php',
	'Students/Student.php' => _( 'Student Info' ),
];

$exceptions['Students'] = [
	'Students/Student.php&include=General_Info&student_id=new' => true,
	'Students/AssignOtherInfo.php' => true,
];
