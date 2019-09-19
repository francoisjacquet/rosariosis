<?php

/**
 * Update Attendance Daily
 *
 * @uses AttendanceDailyCalculateTotal()
 *
 * @param int         $student_id Student ID.
 * @param string      $date       School Day, defaults to today (optional).
 * @param string|bool $comment    Comment (optional).
 *
 * @return void Return early if Total Minutes is 0.
 */
function UpdateAttendanceDaily( $student_id, $date = '', $comment = false )
{
	if ( ! $date )
	{
		$date = DBDate();
	}

	$total = AttendanceDailyTotalMinutes( $student_id, $date );

	if ( $total == 0 )
	{
		return;
	}

	$length = '0.0';

	if ( $total >= Config( 'ATTENDANCE_FULL_DAY_MINUTES' ) )
	{
		$length = '1.0';
	}
	elseif ( $total >= ( Config( 'ATTENDANCE_FULL_DAY_MINUTES' ) / 2 ) )
	{
		$length = '.5';
	}

	$current_RET = DBGet( "SELECT MINUTES_PRESENT,STATE_VALUE,COMMENT
		FROM ATTENDANCE_DAY
		WHERE STUDENT_ID='" . $student_id . "' AND SCHOOL_DATE='" . $date . "'" );

	if ( empty( $current_RET ) )
	{
		DBQuery( "INSERT INTO ATTENDANCE_DAY (SYEAR,STUDENT_ID,SCHOOL_DATE,MINUTES_PRESENT,STATE_VALUE,MARKING_PERIOD_ID,COMMENT)
			VALUES('" . UserSyear() . "','" . $student_id . "','" . $date . "','" . $total . "','" .
			$length . "','" . GetCurrentMP( 'QTR', $date, false ) . "','" . $comment . "')" );

		return;
	}

	if ( $current_RET[1]['MINUTES_PRESENT'] != $total
		|| $current_RET[1]['STATE_VALUE'] != $length )
	{
		DBQuery( "UPDATE ATTENDANCE_DAY
			SET MINUTES_PRESENT='" . $total . "',STATE_VALUE='" . $length . "'" .
			( $comment !== false ? ",COMMENT='" . $comment . "'" : '' ) . "
			WHERE STUDENT_ID='" . $student_id . "'
			AND SCHOOL_DATE='" . $date . "'" );
	}
	elseif ( $comment !== false
		&& $current_RET[1]['COMMENT'] != $comment )
	{
		DBQuery( "UPDATE ATTENDANCE_DAY
			SET COMMENT='" . $comment . "'
			WHERE STUDENT_ID='" . $student_id . "'
			AND SCHOOL_DATE='" . $date . "'" );
	}
}


/**
 * Attendance Daily Calculate Total Minutes
 *
 * @since 5.3
 *
 * @param int    $student_id Student ID.
 * @param string $date       School Day.
 *
 * @return float Total in Minutes.
 */
function AttendanceDailyTotalMinutes( $student_id, $date )
{
	$total_sql = "SELECT SUM(sp.LENGTH) AS TOTAL
	FROM SCHEDULE s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,ATTENDANCE_CALENDAR ac, COURSE_PERIOD_SCHOOL_PERIODS cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND position(',0,' IN cp.DOES_ATTENDANCE)>0
	AND ac.SCHOOL_DATE='" . $date . "'
	AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL)
	AND ac.CALENDAR_ID=cp.CALENDAR_ID
	AND ac.SCHOOL_ID=s.SCHOOL_ID
	AND ac.SYEAR=s.SYEAR
	AND s.SYEAR=cp.SYEAR
	AND sp.PERIOD_ID=cpsp.PERIOD_ID
	AND s.STUDENT_ID='" . $student_id . "'
	AND s.SYEAR='" . UserSyear() . "'
	AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
	AND s.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', $date, false ) ) . ")";

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$total_sql .= " AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0
			THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . "
			ELSE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<='" . $date . "' AND end_date>='" . $date . "' AND mp='QTR' AND SCHOOL_ID=s.SCHOOL_ID)
			AND school_date<='" . $date . "'
			AND SCHOOL_ID=s.SCHOOL_ID) AS INT) FOR 1) IN cpsp.DAYS)>0";
	}
	else
	{
		$total_sql .= " AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM cast('" . $date . "' AS DATE)) AS INT)+1 FOR 1) IN cpsp.DAYS)>0";
	}

	$total = DBGetOne( $total_sql );

	if ( $total == 0 )
	{
		return 0;
	}

	$total_sql = "SELECT SUM(sp.LENGTH) AS TOTAL
		FROM ATTENDANCE_PERIOD ap,SCHOOL_PERIODS sp,ATTENDANCE_CODES ac
		WHERE ap.STUDENT_ID='" . $student_id . "'
		AND ap.SCHOOL_DATE='" . $date . "'
		AND ap.PERIOD_ID=sp.PERIOD_ID
		AND ac.ID=ap.ATTENDANCE_CODE
		AND sp.SYEAR='" . UserSyear() . "'";

	$total_absent = DBGetOne( $total_sql . " AND ac.STATE_CODE='A'" );

	$total_half_day = DBGetOne( $total_sql . " AND ac.STATE_CODE='H'" );

	$total = $total - $total_absent - ( $total_half_day * .5 );

	return $total;
}
