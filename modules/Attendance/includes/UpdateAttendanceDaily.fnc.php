<?php

/**
 * Update Attendance Daily
 *
 * @uses AttendanceDailyCalculateTotal()
 *
 * @since 7.2.4 Take in Account Calendar Day Minutes.
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

	if ( $total === false )
	{
		return;
	}

	$length = '0.0';

	// @since 7.2.4 Take in Account Calendar Day Minutes.
	$attendance_day_minutes = DBGetOne( "SELECT MINUTES
		FROM attendance_calendar
		WHERE SCHOOL_DATE='" . $date . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND CALENDAR_ID=(SELECT CALENDAR_ID
			FROM student_enrollment
			WHERE STUDENT_ID='" . (int) $student_id . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND ('" . $date . "' BETWEEN START_DATE AND END_DATE OR (END_DATE IS NULL AND '" . $date . "'>=START_DATE))
			LIMIT 1)" );

	if ( ! $attendance_day_minutes
		|| $attendance_day_minutes === '999' )
	{
		// Calendar day Minutes is full day (999) or not set, use config.
		$attendance_day_minutes = Config( 'ATTENDANCE_FULL_DAY_MINUTES' );
	}

	if ( $total >= $attendance_day_minutes )
	{
		$length = '1.0';
	}
	elseif ( $total >= ( $attendance_day_minutes / 2 ) )
	{
		$length = '.5';
	}

	$current_RET = DBGet( "SELECT MINUTES_PRESENT,STATE_VALUE,COMMENT
		FROM attendance_day
		WHERE STUDENT_ID='" . (int) $student_id . "' AND SCHOOL_DATE='" . $date . "'" );

	if ( empty( $current_RET ) )
	{
		DBInsert(
			'attendance_day',
			[
				'SYEAR' => UserSyear(),
				'STUDENT_ID' => (int) $student_id,
				'SCHOOL_DATE' => $date,
				'MINUTES_PRESENT' => (int) $total,
				'STATE_VALUE' => $length,
				'MARKING_PERIOD_ID' => GetCurrentMP( 'QTR', $date, false ),
				'COMMENT' => $comment,
			]
		);

		return;
	}

	$where_columns = [
		'STUDENT_ID' => (int) $student_id,
		'SCHOOL_DATE' => $date,
	];

	if ( $current_RET[1]['MINUTES_PRESENT'] != $total
		|| $current_RET[1]['STATE_VALUE'] != $length )
	{
		$columns = [
			'MINUTES_PRESENT' => (int) $total,
			'STATE_VALUE' => $length,
		];

		if ( $comment !== false )
		{
			$columns += [ 'COMMENT' => $comment ];
		}

		DBUpdate( 'attendance_day', $columns, $where_columns );
	}
	elseif ( $comment !== false
		&& $current_RET[1]['COMMENT'] != $comment )
	{
		DBUpdate( 'attendance_day', [ 'COMMENT' => $comment ], $where_columns );
	}
}


/**
 * Attendance Daily Calculate Total Minutes
 *
 * @since 5.3
 * @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
 *
 * @param int    $student_id Student ID.
 * @param string $date       School Day.
 *
 * @return float|bool Total in Minutes or false if School Periods Length sum is 0.
 */
function AttendanceDailyTotalMinutes( $student_id, $date )
{
	global $DatabaseType;

	$total_sql = "SELECT SUM(sp.LENGTH) AS TOTAL
	FROM schedule s,course_periods cp,school_periods sp,attendance_calendar ac,course_period_school_periods cpsp
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
	AND s.STUDENT_ID='" . (int) $student_id . "'
	AND s.SYEAR='" . UserSyear() . "'
	AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
	AND s.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', $date, false ) ) . ")";

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$total_sql .= " AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(SCHOOL_DATE)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0
			THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . "
			ELSE COUNT(SCHOOL_DATE)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE SCHOOL_DATE<=ac.SCHOOL_DATE
			AND SCHOOL_DATE>=(SELECT START_DATE
				FROM school_marking_periods
				WHERE START_DATE<=ac.SCHOOL_DATE
				AND END_DATE>=ac.SCHOOL_DATE
				AND MP='QTR'
				AND SCHOOL_ID=ac.SCHOOL_ID
				AND SYEAR=ac.SYEAR)
			AND CALENDAR_ID=cp.CALENDAR_ID)
			" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
			" FOR 1) IN cpsp.DAYS)>0";
	}
	else
	{
		$total_sql .= " AND position(substring('UMTWHFS' FROM " .
		( $DatabaseType === 'mysql' ?
			"DAYOFWEEK(cast('" . $date . "' AS DATE))" :
			"cast(extract(DOW FROM cast('" . $date . "' AS DATE))+1 AS int)" ) .
		" FOR 1) IN cpsp.DAYS)>0";
	}

	$total = DBGetOne( $total_sql );

	if ( $total == 0 )
	{
		// Return false if School Periods Length sum is 0.
		return false;
	}

	$total_sql = "SELECT SUM(sp.LENGTH) AS TOTAL
		FROM attendance_period ap,school_periods sp,attendance_codes ac
		WHERE ap.STUDENT_ID='" . (int) $student_id . "'
		AND ap.SCHOOL_DATE='" . $date . "'
		AND ap.PERIOD_ID=sp.PERIOD_ID
		AND ac.ID=ap.ATTENDANCE_CODE
		AND sp.SYEAR='" . UserSyear() . "'";

	$total_absent = DBGetOne( $total_sql . " AND ac.STATE_CODE='A'" );

	$total_half_day = DBGetOne( $total_sql . " AND ac.STATE_CODE='H'" );

	$total = $total - $total_absent - ( $total_half_day * .5 );

	return $total;
}
