<?php

//FJ multiple school periods for a course period
/*$sections_RET = DBGet( "SELECT cs.TITLE as SUBJECT_TITLE,c.TITLE AS COURSE,cp.COURSE_ID,cp.PERIOD_ID,cp.TEACHER_ID,cp.ROOM,cp.TOTAL_SEATS AS SEATS,cp.MARKING_PERIOD_ID FROM COURSE_PERIODS cp,COURSES c,COURSE_SUBJECTS cs WHERE cs.SUBJECT_ID=c.SUBJECT_ID AND cp.COURSE_ID=c.COURSE_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."'",array('PERIOD_ID' => 'GetPeriod','TEACHER_ID' => 'GetTeacher','MARKING_PERIOD_ID' => '_makeMP'),array('COURSE'));*/
$sections_RET = DBGet( "SELECT cs.TITLE as SUBJECT_TITLE,c.TITLE AS COURSE,cp.COURSE_ID,cpsp.PERIOD_ID,cp.TEACHER_ID,cp.ROOM,cp.TOTAL_SEATS AS SEATS,cp.MARKING_PERIOD_ID
FROM COURSE_PERIODS cp,COURSES c,COURSE_SUBJECTS cs,COURSE_PERIOD_SCHOOL_PERIODS cpsp
WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
AND cs.SUBJECT_ID=c.SUBJECT_ID
AND cp.COURSE_ID=c.COURSE_ID
AND cp.SYEAR='" . UserSyear() . "'
AND cp.SCHOOL_ID='" . UserSchool() . "'
ORDER BY SUBJECT_TITLE,COURSE,cpsp.PERIOD_ID", array( 'PERIOD_ID' => '_getPeriod', 'TEACHER_ID' => 'GetTeacher', 'MARKING_PERIOD_ID' => '_makeMP' ), array( 'COURSE' ) );

$columns = array( 'SUBJECT_TITLE' => _( 'Subject' ), 'COURSE' => _( 'Course' ), 'PERIOD_ID' => _( 'Period' ), 'TEACHER_ID' => _( 'Teacher' ), 'ROOM' => _( 'Room' ), 'SEATS' => _( 'Seats' ), 'MARKING_PERIOD_ID' => _( 'Marking Period' ) );

DrawHeader( ProgramTitle() );
ListOutput( $sections_RET, $columns, 'Course', 'Courses', array(), array( array( 'COURSE', 'SUBJECT_TITLE' ) ) );

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

/**
 * @param $period_id
 * @param $title
 * @return mixed
 */
function _getPeriod( $period_id, $title = '' )
{
	static $periods_RET;

	if ( empty( $periods_RET ) )
	{
		$sql = "SELECT TITLE, PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='" . UserSyear() . "'";
		$periods_RET = DBGet( $sql, array(), array( 'PERIOD_ID' ) );
	}

	return $periods_RET[$period_id][1]['TITLE'];
}
