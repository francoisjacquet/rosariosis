<?php
/**
 * Translate a day of the week to its corresponding number according to the attendance days for the school
 * Monday 1 to Sunday 7
 * Example: if Monday is a legal holiday, then Tuesday is 1 and the next Monday is 7
 *
 * @since 7.0 Fix case when multiple calendars and school years.
 *
 * @param $date        ISO date or UNIX timestamp.
 * @param $calendar_id Calendar ID.
 *
 * @return false if the day is not attendance day
 */
function dayToNumber( $date, $calendar_id = 0 )
{
	if ( is_numeric( $date ) )
	{
		$date = date( 'Y-m-d', $date );
	}

	if ( ! $calendar_id )
	{
		// Get Default Calendar.
		$calendar_id = DBGetOne( "SELECT CALENDAR_ID
			FROM attendance_calendars
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY DEFAULT_CALENDAR" );
	}

	// Check if the day is attendance day.
	$is_school_day = DBGetOne( "SELECT 1
		FROM attendance_calendar
		WHERE SCHOOL_DATE='" . $date . "'
		AND CALENDAR_ID='" . (int) $calendar_id . "'" );

	if ( ! $is_school_day )
	{
		return false;
	}

	// Quarter start date.
	$begin_quarter_date = DBGetOne( "SELECT START_DATE
		FROM school_marking_periods
		WHERE START_DATE<='" . $date . "'
		AND END_DATE>='" . $date . "'
		AND MP='QTR'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( empty( $begin_quarter_date ) )
	{
		return false;
	}

	// Number of school days since the beginning of the quarter.
	$school_days = DBGetOne( "SELECT COUNT(SCHOOL_DATE) AS SCHOOL_DAYS
		FROM attendance_calendar
		WHERE SCHOOL_DATE>='" . $begin_quarter_date . "'
		AND SCHOOL_DATE<='" . $date . "'
		AND CALENDAR_ID='" . (int) $calendar_id . "'" );

	if ( $school_days % SchoolInfo( 'NUMBER_DAYS_ROTATION' ) == 0 )
	{
		return SchoolInfo( 'NUMBER_DAYS_ROTATION' );
	}

	return $school_days % SchoolInfo( 'NUMBER_DAYS_ROTATION' );
}
