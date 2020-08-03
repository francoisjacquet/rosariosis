<?php
/**
 * Requests Report
 *
 * Included in RequestsReport.php
 *
 * @package RosarioSIS
 * @subpackage Scheduling
 */

$count_RET = DBGet( "SELECT cs.TITLE as SUBJECT_TITLE,c.TITLE as COURSE_TITLE,sr.COURSE_ID,COUNT(*) AS COUNT,
	(SELECT sum(TOTAL_SEATS)
		FROM COURSE_PERIODS cp
		WHERE cp.COURSE_ID=sr.COURSE_ID) AS SEATS,
	(SELECT count(STUDENT_ID)
		FROM SCHEDULE s
		WHERE s.COURSE_ID=sr.COURSE_ID) AS STUDENTS
	FROM SCHEDULE_REQUESTS sr,COURSES c,COURSE_SUBJECTS cs
	WHERE cs.SUBJECT_ID=c.SUBJECT_ID
	AND sr.COURSE_ID=c.COURSE_ID
	AND sr.SYEAR='" . UserSyear() . "'
	AND sr.SCHOOL_ID='" . UserSchool() . "'
	GROUP BY sr.COURSE_ID,cs.TITLE,c.TITLE" );

$columns = array(
	'SUBJECT_TITLE' => _( 'Subject' ),
	'COURSE_TITLE' => _( 'Course' ),
	'COUNT' => _( 'Number of Requests' ),
	'SEATS' => _( 'Seats' ),
	'STUDENTS' => _( 'Students' ),
);

ListOutput( $count_RET, $columns, 'Subject', 'Subjects' );
