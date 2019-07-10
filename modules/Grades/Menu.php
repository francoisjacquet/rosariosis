<?php
/**
 * Grades module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Grades']['admin'] = array(
	'title' => _( 'Grades' ),
	'default' => 'Grades/GPARankList.php',
	'Grades/ReportCards.php' => _( 'Report Cards' ),
	'Grades/HonorRoll.php' => _( 'Honor Roll' ),
	'Grades/Transcripts.php' => _( 'Transcripts' ),
	1 => _( 'Reports' ),
	'Grades/StudentGrades.php' => _( 'Student Grades' ),
	'Grades/TeacherCompletion.php' => _( 'Teacher Completion' ),
	'Grades/GradeBreakdown.php' => _( 'Grade Breakdown' ),
	'Grades/FinalGrades.php' => _( 'Final Grades' ),
	'Grades/GPARankList.php' => _( 'GPA / Class Rank List' ),
	2 => _( 'Setup' ),
	'Grades/ReportCardGrades.php' => _( 'Grading Scales' ),
	'Grades/ReportCardComments.php' => _( 'Report Card Comments' ),
	'Grades/ReportCardCommentCodes.php' => _( 'Comment Codes' ),
	'Grades/EditHistoryMarkingPeriods.php' => _( 'History Marking Periods' ),
	3 => _( 'Utilities' ),
	'Grades/EditReportCardGrades.php' => _( 'Edit Student Grades' ),
	'Grades/MassCreateAssignments.php' => _( 'Mass Create Assignments' ),
);

$menu['Grades']['teacher'] = array(
	'title' => _( 'Grades' ),
	'default' => 'Grades/Grades.php',
	'Grades/InputFinalGrades.php' => _( 'Input Final Grades' ),
	'Grades/ReportCards.php' => _( 'Report Cards' ),
	1 => _( 'Gradebook' ),
	'Grades/Grades.php' => _( 'Grades' ),
	'Grades/Assignments.php' => _( 'Assignments' ),
	//'Grades/Assignments-new.php' => _( 'Assignments' ),
	'Grades/AnomalousGrades.php' => _( 'Anomalous Grades' ),
	'Grades/ProgressReports.php' => _( 'Progress Reports' ),
	// FJ add Grade Breakdown.
	'Grades/GradebookBreakdown.php' => _( 'Grade Breakdown' ),
	2 => _( 'Reports' ),
	'Grades/StudentGrades.php' => _( 'Student Grades' ),
	'Grades/FinalGrades.php' => _( 'Final Grades' ),
	'Grades/GPARankList.php' => _( 'GPA / Class Rank List' ),
	3 => _( 'Setup' ),
	'Grades/Configuration.php' => _( 'Configuration' ),
	'Grades/ReportCardGrades.php' => _( 'Grading Scales' ),
	'Grades/ReportCardComments.php' => _( 'Report Card Comments' ),
	'Grades/ReportCardCommentCodes.php' => _( 'Comment Codes' ),
);

$menu['Grades']['parent'] = array(
	'title' => _( 'Grades' ),
	'default' => 'Grades/StudentGrades.php',
	'Grades/StudentGrades.php' => _( 'Gradebook Grades' ),
	'Grades/StudentAssignments.php' => _( 'Assignments' ),
	'Grades/FinalGrades.php' => _( 'Final Grades' ),
	'Grades/ReportCards.php' => _( 'Report Cards' ),
	'Grades/Transcripts.php' => _( 'Transcripts' ),
	'Grades/GPARankList.php' => _( 'GPA / Class Rank' ),
);

if ( $RosarioModules['Users'] )
{
	$menu['Users']['admin'] += array(
		'Users/TeacherPrograms.php&include=Grades/InputFinalGrades.php' => _( 'Input Final Grades' ),
		'Users/TeacherPrograms.php&include=Grades/Grades.php' => _( 'Gradebook Grades' ),
		'Users/TeacherPrograms.php&include=Grades/AnomalousGrades.php' => _( 'Anomalous Grades' ),
	);
}
