<?php

/**
 * Update Attendance Daily
 *
 * @uses AttendanceDailyTotalMinutesPresent()
 * @uses AttendanceDailyTotalMinutes()
 *
 * @since 7.2.4 Take in Account Calendar Day Minutes.
 * @since 11.2 Dynamic Daily Attendance calculation based on total course period minutes
 *
 * @param int         $student_id Student ID.
 * @param string      $date       School Day, defaults to today (optional).
 * @param string|bool $comment    Comment (optional).
 *
 * @return void Return early if Total Minutes is false (no course periods during the day).
 */
function UpdateAttendanceDaily( $student_id, $date = '', $comment = false )
{
	if ( ! $date )
	{
		$date = DBDate();
	}

	$total_present = AttendanceDailyTotalMinutesPresent( $student_id, $date );

	if ( $total_present === false )
	{
		return;
	}

	$length = '0.0';

	$attendance_day_minutes = AttendanceDailyTotalMinutes( $student_id, $date );

	if ( Config( 'ATTENDANCE_FULL_DAY_MINUTES' ) )
	{
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
	}

	if ( $total_present >= $attendance_day_minutes )
	{
		$length = '1.0';
	}
	elseif ( $total_present >= ( $attendance_day_minutes / 2 ) )
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
				'MINUTES_PRESENT' => (int) $total_present,
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

	if ( $current_RET[1]['MINUTES_PRESENT'] != $total_present
		|| $current_RET[1]['STATE_VALUE'] != $length )
	{
		$columns = [
			'MINUTES_PRESENT' => (int) $total_present,
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
 * Attendance Daily Calculate Total Minutes Present
 *
 * @since 11.2 Breaking Change: use AttendanceDailyTotalMinutesPresent() instead of AttendanceDailyTotalMinutes()
 * @since 11.2 Action hook, filter Total Minutes Present
 *
 * @param int    $student_id Student ID.
 * @param string $date       School Day.
 *
 * @return float|bool Total Minutes Present or false if School Periods Length sum is 0.
 */
function AttendanceDailyTotalMinutesPresent( $student_id, $date )
{
	$total_minutes = AttendanceDailyTotalMinutes( $student_id, $date );

	if ( $total_minutes == 0 )
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

	$total_half = DBGetOne( $total_sql . " AND ac.STATE_CODE='H'" );

	$total_present = $total_minutes - $total_absent - ( $total_half * .5 );

	/**
	 * Action hook, filter Total Minutes Present
	 *
	 * @since 11.2
	 *
	 * @example add_action( 'Attendance/includes/UpdateAttendanceDaily.fnc.php|total_minutes', 'MyFilter', 5 );
	 * @example function MyFilter( $tag, &$total_present, $total_minutes, $total_absent, $total_half ) { $total_present = ... }
	 */
	do_action(
		'Attendance/includes/UpdateAttendanceDaily.fnc.php|total_minutes_present',
		[ &$total_present, $total_minutes, $total_absent, $total_half ]
	);

	return $total_present;
}

/**
 * Attendance Daily Total Minutes
 *
 * @since 5.3
 * @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
 * @since 11.2 Breaking Change: use AttendanceDailyTotalMinutesPresent() instead of AttendanceDailyTotalMinutes()
 *
 * @param int    $student_id Student ID.
 * @param string $date       School Day.
 *
 * @return int Total Minutes.
 */
function AttendanceDailyTotalMinutes( $student_id, $date )
{
	$total_sql = AttendanceDailyTotalMinutesSQL( $student_id, $date );

	$total_minutes = DBGetOne( $total_sql );

	return $total_minutes;
}

/**
 * Attendance Daily Total Minutes SQL
 *
 * @since 11.2
 *
 * @param int    $student_id     Student ID.
 * @param string $date_or_column School Day or Date column.
 *
 * @return string Total Minutes SQL.
 */
function AttendanceDailyTotalMinutesSQL( $student_id, $date_or_column )
{
	global $DatabaseType;

	if ( VerifyDate( $date_or_column ) )
	{
		$all_mp_ids = GetAllMP( 'QTR', GetCurrentMP( 'QTR', $date_or_column, false ) );

		$date_sql = "'" . $date_or_column . "'";
	}
	else
	{
		// Is SQL date column name, sanitize: allow a-z_A-Z.
		$date_sql = preg_replace( '/[^a-z_A-Z\.]/', '', $date_or_column );

		$all_mp_ids = "SELECT MARKING_PERIOD_ID
			FROM school_marking_periods
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND " . $date_or_column . ">=START_DATE
			AND " . $date_or_column . "<=END_DATE";
	}

	$total_sql = "SELECT SUM(sp2.LENGTH) AS TOTAL
	FROM schedule s2,course_periods cp2,school_periods sp2,attendance_calendar ac2,course_period_school_periods cpsp2
	WHERE cp2.COURSE_PERIOD_ID=cpsp2.COURSE_PERIOD_ID
	AND s2.COURSE_PERIOD_ID=cp2.COURSE_PERIOD_ID
	AND position(',0,' IN cp2.DOES_ATTENDANCE)>0
	AND ac2.SCHOOL_DATE=" . $date_sql . "
	AND (ac2.BLOCK=sp2.BLOCK OR sp2.BLOCK IS NULL)
	AND ac2.CALENDAR_ID=cp2.CALENDAR_ID
	AND ac2.SCHOOL_ID=s2.SCHOOL_ID
	AND ac2.SYEAR=s2.SYEAR
	AND s2.SYEAR=cp2.SYEAR
	AND sp2.PERIOD_ID=cpsp2.PERIOD_ID
	AND s2.STUDENT_ID='" . (int) $student_id . "'
	AND s2.SYEAR='" . UserSyear() . "'
	AND (" . $date_sql . " BETWEEN s2.START_DATE AND s2.END_DATE OR (s2.END_DATE IS NULL AND " . $date_sql . ">=s2.START_DATE))
	AND s2.MARKING_PERIOD_ID IN (" . $all_mp_ids . ")";

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$total_sql .= " AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(SCHOOL_DATE)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0
			THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . "
			ELSE COUNT(SCHOOL_DATE)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE SCHOOL_DATE<=ac2.SCHOOL_DATE
			AND SCHOOL_DATE>=(SELECT START_DATE
				FROM school_marking_periods
				WHERE START_DATE<=ac2.SCHOOL_DATE
				AND END_DATE>=ac2.SCHOOL_DATE
				AND MP='QTR'
				AND SCHOOL_ID=ac2.SCHOOL_ID
				AND SYEAR=ac2.SYEAR)
			AND CALENDAR_ID=cp2.CALENDAR_ID)
			" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
			" FOR 1) IN cpsp2.DAYS)>0";
	}
	else
	{
		$total_sql .= " AND position(substring('UMTWHFS' FROM " .
		( $DatabaseType === 'mysql' ?
			"DAYOFWEEK(cast(" . $date_sql . " AS DATE))" :
			"cast(extract(DOW FROM cast(" . $date_sql . " AS DATE))+1 AS int)" ) .
		" FOR 1) IN cpsp2.DAYS)>0";
	}

	return $total_sql;
}
