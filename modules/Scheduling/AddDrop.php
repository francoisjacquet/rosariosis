<?php
require_once 'ProgramFunctions/TipMessage.fnc.php';

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

echo '<form action="' . PreparePHP_SELF() . '" method="GET">';

DrawHeader(
	_( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) . ' ' .
	_( 'to' ) . ' ' . PrepareDate( $end_date, '_end', false ) .
	SubmitButton( _( 'Go' ) ) );

echo '</form>';

$enrollment_RET = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,se.START_DATE AS START_DATE,NULL AS END_DATE,se.START_DATE AS DATE,se.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME
FROM schedule se,students s,courses c,course_periods cp
WHERE c.COURSE_ID=se.COURSE_ID
AND cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
AND cp.COURSE_ID=c.COURSE_ID
AND s.STUDENT_ID=se.STUDENT_ID
AND se.SCHOOL_ID='" . UserSchool() . "'
AND se.START_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
UNION
SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,NULL AS START_DATE,se.END_DATE AS END_DATE,se.END_DATE AS DATE,se.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME
FROM schedule se,students s,courses c,course_periods cp
WHERE c.COURSE_ID=se.COURSE_ID
AND cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
AND cp.COURSE_ID=c.COURSE_ID
AND s.STUDENT_ID=se.STUDENT_ID
AND se.SCHOOL_ID='" . UserSchool() . "'
AND se.END_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
ORDER BY DATE DESC", [
	'FULL_NAME' => '_makeStudentScheduleLink',
	'START_DATE' => 'ProperDate',
	'END_DATE' => 'ProperDate'
] );

$columns = [
	'FULL_NAME' => _( 'Student' ),
	'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
	'COURSE_TITLE' => _( 'Course' ),
	'TITLE' => _( 'Course Period' ),
	'START_DATE' => _( 'Enrolled' ),
	'END_DATE' => _( 'Dropped' ),
];

ListOutput( $enrollment_RET, $columns, 'Schedule Record', 'Schedule Records' );

/**
 * Make Student Schedule link
 *
 * @since 10.0
 *
 * Local function
 * DBGet() callback
 *
 * @param  string $value  Student Full Name.
 * @param  string $column Column.
 *
 * @return string         Link to Student Info program.
 */
function _makeStudentScheduleLink( $value, $column = 'FULL_NAME' )
{
	global $THIS_RET;

	$modname = 'Scheduling/Schedule.php';

	if ( ! AllowUse( $modname )
		|| ! $THIS_RET['STUDENT_ID'] )
	{
		return $value;
	}

	$link = 'Modules.php?modname=' . $modname . '&student_id=' . $THIS_RET['STUDENT_ID'];

	return '<a href="' . URLEscape( $link ) . '">' . MakeStudentPhotoTipMessage( $THIS_RET['STUDENT_ID'], $value ) . '</a>';
}
