<?php
//FJ multiple school periods for a course period
/*$requests_RET = DBGet( "SELECT r.COURSE_ID AS CRS,
r.COURSE_ID,cp.COURSE_PERIOD_ID,
c.TITLE AS COURSE_TITLE,cp.PERIOD_ID,
(cp.TOTAL_SEATS-cp.FILLED_SEATS) AS OPEN_SEATS,s.STUDENT_ID AS SCHEDULED
FROM SCHEDULE_REQUESTS r,
COURSES c,SCHOOL_PERIODS sp,
COURSE_PERIODS cp LEFT OUTER JOIN SCHEDULE s ON
(s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND s.STUDENT_ID='".UserStudentID()."')
WHERE
r.SYEAR='".UserSyear()."' AND r.SCHOOL_ID='".UserSchool()."'
AND r.COURSE_ID=cp.COURSE_ID AND c.COURSE_ID=cp.COURSE_ID
AND r.STUDENT_ID='".UserStudentID()."'
AND sp.PERIOD_ID=cp.PERIOD_ID
ORDER BY ".db_case(array('s.STUDENT_ID',"''","NULL",'sp.SORT_ORDER'))."
",array(),array('CRS','PERIOD_ID'));*/
$requests_RET = DBGet( "SELECT r.COURSE_ID AS CRS,r.COURSE_ID,cp.COURSE_PERIOD_ID,
c.TITLE AS COURSE_TITLE,cp.PERIOD_ID,
(cp.TOTAL_SEATS-cp.FILLED_SEATS) AS OPEN_SEATS,s.STUDENT_ID AS SCHEDULED
FROM SCHEDULE_REQUESTS r,COURSES c,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp,COURSE_PERIODS cp
LEFT OUTER JOIN SCHEDULE s ON (s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND s.STUDENT_ID='" . UserStudentID() . "')
WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
AND r.SYEAR='" . UserSyear() . "'
AND r.SCHOOL_ID='" . UserSchool() . "'
AND r.COURSE_ID=cp.COURSE_ID
AND c.COURSE_ID=cp.COURSE_ID
AND r.STUDENT_ID='" . UserStudentID() . "'
AND sp.PERIOD_ID=cpsp.PERIOD_ID
ORDER BY " . db_case( array( 's.STUDENT_ID', "''", "NULL", 'sp.SORT_ORDER' ) ) . " ", array(), array( 'CRS', 'PERIOD_ID' ) );

$periods_RET = DBGet( "SELECT PERIOD_ID,SHORT_NAME FROM SCHOOL_PERIODS WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' ORDER BY SORT_ORDER" );
echo '<table class="center" style="border: 1px solid;">';
echo '<tr><td></td>';

foreach ( (array) $periods_RET as $period )
{
	echo '<td><b>' . $period['SHORT_NAME'] . '</b></td>';
}

foreach ( (array) $requests_RET as $course => $periods )
{
	echo '<tr><td><b>' . $periods[key( $periods )][1]['COURSE_TITLE'] . '</b></td>';

	foreach ( (array) $periods_RET as $period )
	{
		if ( $periods[$period['PERIOD_ID']][1]['SCHEDULED'] )
		{
			$color = '0000FF';
		}
		elseif ( $periods[$period['PERIOD_ID']] )
		{
			if ( $periods[$period['PERIOD_ID']][1]['OPEN_SEATS'] == 0 )
			{
				$color = 'FFFF00';
			}
			else
			{
				$color = '00FF00';
			}
		}
		else
		{
			$color = 'CCCCCC';
		}

		echo '<td style="height:10px; width:6px; background-color:#' . $color . ';"></td>';
	}

	echo '</tr>';
}

echo '</table>';
