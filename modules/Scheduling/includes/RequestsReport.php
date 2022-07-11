<?php
/**
 * Requests Report
 *
 * Included in RequestsReport.php
 *
 * @package RosarioSIS
 * @subpackage Scheduling
 */

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

$count_RET = DBGet( "SELECT cs.TITLE as SUBJECT_TITLE,c.TITLE as COURSE_TITLE,sr.COURSE_ID,COUNT(*) AS COUNT,
	(SELECT sum(TOTAL_SEATS)
		FROM course_periods cp
		WHERE cp.COURSE_ID=sr.COURSE_ID) AS SEATS,
	(SELECT count(STUDENT_ID)
		FROM schedule s
		WHERE s.COURSE_ID=sr.COURSE_ID" . $where_active_sql . ") AS STUDENTS
	FROM schedule_requests sr,courses c,course_subjects cs
	WHERE cs.SUBJECT_ID=c.SUBJECT_ID
	AND sr.COURSE_ID=c.COURSE_ID
	AND sr.SYEAR='" . UserSyear() . "'
	AND sr.SCHOOL_ID='" . UserSchool() . "'
	GROUP BY cs.SORT_ORDER,cs.TITLE,sr.COURSE_ID,c.TITLE" );

$columns = [
	'SUBJECT_TITLE' => _( 'Subject' ),
	'COURSE_TITLE' => _( 'Course' ),
	'COUNT' => _( 'Number of Requests' ),
	'SEATS' => _( 'Seats' ),
	'STUDENTS' => _( 'Students' ),
];

ListOutput( $count_RET, $columns, 'Subject', 'Subjects' );
