<?php
/**
 * Calculate the number of filled seats in a course period
 *
 * Used in Courses.php, MassSchedule.php, Schedule.php, Scheduler.php & UnfilledRequests.php
 *
 * @since 4.1 Fix SQL error when no MPs
 *
 * @param  array  $period Course Period.
 * @param  string $date   Date. Defaults to current date.
 *
 * @return string         Filled seats.
 */
function calcSeats0( $period, $date = '' )
{
	$mp = $period['MARKING_PERIOD_ID'];

	$all_mp = GetAllMP( GetMP( $mp, 'MP' ), $mp );

	$filled_seats = DBGetOne( "SELECT
		max((SELECT count(1)
		FROM SCHEDULE ss JOIN STUDENT_ENROLLMENT sem ON (sem.STUDENT_ID=ss.STUDENT_ID AND sem.SYEAR=ss.SYEAR)
		WHERE ss.COURSE_PERIOD_ID='" . $period['COURSE_PERIOD_ID'] . "'
		AND (ss.MARKING_PERIOD_ID='" . $mp . "' OR ss.MARKING_PERIOD_ID IN (" . $all_mp . "))
		AND (ac.SCHOOL_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ac.SCHOOL_DATE<=ss.END_DATE))
		AND (ac.SCHOOL_DATE>=sem.START_DATE AND (sem.END_DATE IS NULL OR ac.SCHOOL_DATE<=sem.END_DATE)))) AS FILLED_SEATS
	FROM ATTENDANCE_CALENDAR ac
	WHERE ac.CALENDAR_ID='" . $period['CALENDAR_ID'] . "'
	AND ac.SCHOOL_DATE BETWEEN " . ( $date ?
		"'" . $date . "'" :
		db_case( array(
			"(CURRENT_DATE>'" . GetMP( $mp, 'END_DATE' ) . "')",
			'TRUE',
			"'" . GetMP( $mp, 'START_DATE' ) . "'",
			'CURRENT_DATE',
		) )
	) . " AND '" . GetMP( $mp, 'END_DATE' ) . "'" );

	return $filled_seats;
}
