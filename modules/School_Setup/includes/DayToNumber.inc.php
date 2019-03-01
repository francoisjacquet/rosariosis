<?php
/**
 * Translate a day of the week to its corresponding number according to the attendance days for the school
 * Monday 1 to Sunday 7
 * Example: if Monday is a legal holiday, then Tuesday is 1 and the next Monday is 7
 *
 * @param $date ISO date or UNIX timestamp.
 * @return false if the day is not attendance day
 */
function dayToNumber( $date )
{
	if ( is_numeric( $date ) )
	{
		$date = date( 'Y-m-d', $date );
	}

	// Check if the day is attendance day.
	$check_day_RET = DBGet( DBQuery( "SELECT 1
		FROM attendance_calendar
		WHERE school_date='" . $date . "'
		AND school_id='" . ( $school_id = UserSchool() ) . "'" ) );

	if ( empty( $check_day_RET ) )
	{
		return false;
	}

	// Quarter start date.
	$begin_quarter_RET = DBGet( DBQuery( "SELECT start_date
		FROM school_marking_periods
		WHERE start_date<='" . $date . "'
		AND end_date>='" . $date . "'
		AND mp='QTR'
		AND school_id='" . $school_id . "'" ) );

	if ( empty( $begin_quarter_RET ) )
	{
		return false;
	}

	$begin_quarter = $begin_quarter_RET[1]['START_DATE'];

	// Number of school days since the beginning of the quarter.
	$school_days_RET = DBGet( DBQuery( "SELECT COUNT(school_date) AS school_days
		FROM attendance_calendar
		WHERE school_date>='" . $begin_quarter . "'
		AND school_date<='" . $date . "'
		AND school_id='" . $school_id . "'" ) );

	$school_days = $school_days_RET[1]['SCHOOL_DAYS'];

	if ( $school_days % SchoolInfo( 'NUMBER_DAYS_ROTATION' ) == 0 )
	{
		return SchoolInfo( 'NUMBER_DAYS_ROTATION' );
	}

	return $school_days % SchoolInfo( 'NUMBER_DAYS_ROTATION' );
}
