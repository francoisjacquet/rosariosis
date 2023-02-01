<?php
/**
 * Master Schedule Report
 *
 * Included in ScheduleReport.php
 *
 * @package RosarioSIS
 * @subpackage Scheduling
 */

//FJ multiple school periods for a course period
/*$sections_RET = DBGet( "SELECT cs.TITLE as SUBJECT_TITLE,c.TITLE AS COURSE,cp.COURSE_ID,cp.PERIOD_ID,cp.TEACHER_ID,cp.ROOM,cp.TOTAL_SEATS AS SEATS,cp.MARKING_PERIOD_ID FROM course_periods cp,courses c,course_subjects cs WHERE cs.SUBJECT_ID=c.SUBJECT_ID AND cp.COURSE_ID=c.COURSE_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."'",array('PERIOD_ID' => 'GetPeriod','TEACHER_ID' => 'GetTeacher','MARKING_PERIOD_ID' => '_makeMP'),array('COURSE'));*/

// @since 7.8 Add Include Inactive Students checkbox.
DrawHeader(
	CheckBoxOnclick(
		'include_inactive',
		_( 'Include Inactive Students' )
	)
);

$is_include_inactive = isset( $_REQUEST['include_inactive'] ) && $_REQUEST['include_inactive'] === 'Y';

$where_active_sql = '';

if ( ! $is_include_inactive )
{
	$where_active_sql = " AND '" . DBDate() . "'>=START_DATE
	AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL)
	AND MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")";
}

$sections_RET = DBGet( "SELECT cs.TITLE as SUBJECT_TITLE,c.TITLE AS COURSE,cp.COURSE_ID,
	cp.TEACHER_ID,cp.ROOM,cp.TOTAL_SEATS AS SEATS,cp.MARKING_PERIOD_ID,
	(SELECT " . DBSQLCommaSeparatedResult( 'sp.TITLE', ', ' ) . "
		FROM school_periods sp,course_period_school_periods cpsp
		WHERE sp.SYEAR='" . UserSyear() . "'
		AND cpsp.PERIOD_ID=sp.PERIOD_ID
		AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID) AS PERIODS,
	(SELECT COUNT(STUDENT_ID)
		FROM schedule
		WHERE COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID" . $where_active_sql . ") AS STUDENTS
FROM course_periods cp,courses c,course_subjects cs
WHERE cs.SUBJECT_ID=c.SUBJECT_ID
AND cp.COURSE_ID=c.COURSE_ID
AND cp.SYEAR='" . UserSyear() . "'
AND cp.SCHOOL_ID='" . UserSchool() . "'
ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,COURSE", [ 'TEACHER_ID' => 'GetTeacher', 'MARKING_PERIOD_ID' => '_makeMP' ] );

$columns = [
	'SUBJECT_TITLE' => _( 'Subject' ),
	'COURSE' => _( 'Course' ),
	'PERIODS' => _( 'Periods' ),
	'TEACHER_ID' => _( 'Teacher' ),
	'ROOM' => _( 'Room' ),
	'SEATS' => _( 'Seats' ),
	'STUDENTS' => _( 'Students' ),
	'MARKING_PERIOD_ID' => _( 'Marking Period' ),
];

ListOutput( $sections_RET, $columns, 'Course Period', 'Course Periods' );

/**
 * @param $marking_period_id
 * @param $column
 * @return mixed
 */
function _makeMP( $marking_period_id, $column )
{
	if ( ! $mp_title = GetMP( $marking_period_id, 'TITLE' ) )
	{
		$mp_title = $marking_period_id;
	}

	return $mp_title;
}
