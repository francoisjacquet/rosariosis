<?php

require_once 'modules/Scheduling/includes/calcSeats0.fnc.php';

// @since 7.1 Add Start Date input.
$start_date = RequestedDate( 'start_date', DBDate() );

$confirm_function = '_returnTrue';

if ( $_REQUEST['modname'] == 'Scheduling/Scheduler.php' && empty( $_REQUEST['run'] ) )
{
	$confirm_function = 'Prompt';
	DrawHeader( ProgramTitle() );
}

$confirm_html = '<table class="width-100p"><tr><td>' .
	CheckboxInput(
		'Y',
		'test_mode',
		_( 'Test Mode' ),
		'',
		true
	) . '</td></tr><tr><td>' .
	CheckboxInput(
		'',
		'delete',
		_( 'Delete Current Schedules' ),
		'',
		true
	) . '</td></tr><tr><td>' .
	// @since 7.1 Add Start Date input.
	DateInput(
		DBDate(),
		'start_date',
		_( 'Start Date' ),
		false,
		false
	) . '</td></tr></table>';

$confirm_ok = $confirm_function(
	_( 'Confirm Scheduler Run' ),
	_( 'Are you sure you want to run the scheduler?' ),
	$confirm_html
);

if ( $confirm_ok )
{
	echo '<br />';
	PopTable( 'header', _( 'Scheduler Progress' ) );
	echo '<table class="cellspacing-0 center" style="border: solid 1px; height:19px"><tr>';

	for ( $i = 1; $i <= 100; $i++ )
	{
		echo '<td id="cell' . $i . '" style="width:3px;"></td>';
	}

	echo '</tr></table><br /><div id="percentDIV"><span class="loading"></span> ' . _( 'Processing Requests ...' ) . ' </div>';
	PopTable( 'footer' );
	ob_flush();
	flush();
	set_time_limit( 300 );

	$fy_id = GetFullYearMP();

	$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
		FROM custom_fields
		WHERE ID=200000000", [], [ 'ID' ] );

	$sql_gender = ",'None' as GENDER";

	if ( $custom_fields_RET['200000000']
		&& $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
	{
		$sql_gender = ",s.CUSTOM_200000000 as GENDER";
	}

	$sql = "SELECT r.REQUEST_ID,r.STUDENT_ID" . $sql_gender . ",r.SUBJECT_ID,r.COURSE_ID,MARKING_PERIOD_ID,WITH_TEACHER_ID,NOT_TEACHER_ID,WITH_PERIOD_ID,NOT_PERIOD_ID,(SELECT COUNT(*) FROM course_periods cp2 WHERE cp2.COURSE_ID=r.COURSE_ID) AS SECTIONS
	FROM schedule_requests r,students s,student_enrollment ssm
	WHERE s.STUDENT_ID=ssm.STUDENT_ID AND ssm.SYEAR=r.SYEAR
	AND ('" . $start_date . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)
	AND s.STUDENT_ID=r.STUDENT_ID
	AND r.SYEAR='" . UserSyear() . "'
	AND r.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY REQUEST_ID"; // ORDER BY SECTIONS.

	$requests_RET = DBGet( $sql, [], [ 'REQUEST_ID' ] );

	if ( ! empty( $_REQUEST['delete'] )
		&& ! empty( $requests_RET ) )
	{
		DBQuery( "DELETE FROM schedule
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND (SCHEDULER_LOCK!='Y' OR SCHEDULER_LOCK IS NULL)" );
	}

	$periods_RET = DBGet( "SELECT COURSE_PERIOD_ID,MARKING_PERIOD_ID,MP,TOTAL_SEATS,CALENDAR_ID
		FROM course_periods
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	foreach ( (array) $periods_RET as $period )
	{
		$seats = calcSeats0( $period );

		DBUpdate(
			'course_periods',
			[ 'FILLED_SEATS' => $seats ],
			[ 'COURSE_PERIOD_ID' => (int) $period['COURSE_PERIOD_ID'] ]
		);
	}

	$count = DBGet( "SELECT COUNT(*) AS COUNT
		FROM schedule
		WHERE SCHOOL_ID='" . UserSchool() . "'" );

	//FJ multiple school periods for a course period
	//$sql = "SELECT PARENT_ID,COURSE_PERIOD_ID,COURSE_ID,COURSE_ID AS COURSE,GENDER_RESTRICTION,PERIOD_ID,DAYS,TEACHER_ID,MARKING_PERIOD_ID,MP,COALESCE(TOTAL_SEATS,0)-COALESCE(FILLED_SEATS,0) AS AVAILABLE_SEATS,(SELECT COUNT(*) FROM course_periods cp2 WHERE cp2.COURSE_ID=cp.COURSE_ID) AS SECTIONS FROM course_periods cp ORDER BY SECTIONS,AVAILABLE_SEATS";
	$sql = "SELECT PARENT_ID,cp.COURSE_PERIOD_ID,COURSE_ID,COURSE_ID AS COURSE,GENDER_RESTRICTION,cpsp.PERIOD_ID,cpsp.DAYS,TEACHER_ID,MARKING_PERIOD_ID,MP,COALESCE(TOTAL_SEATS,0)-COALESCE(FILLED_SEATS,0) AS AVAILABLE_SEATS,
	(SELECT COUNT(*) FROM course_periods cp2 WHERE cp2.COURSE_ID=cp.COURSE_ID) AS SECTIONS
	FROM course_periods cp,course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY COURSE_ID,SHORT_NAME"; //ORDER BY SECTIONS,AVAILABLE_SEATS

	$cp_parent_RET = DBGet( $sql, [], [ 'PARENT_ID' ] );

	//$sql = "SELECT PARENT_ID,COURSE_PERIOD_ID,COURSE_ID,COURSE_ID AS COURSE,GENDER_RESTRICTION,PERIOD_ID,DAYS,TEACHER_ID,MARKING_PERIOD_ID,MP,COALESCE(TOTAL_SEATS,0)-COALESCE(FILLED_SEATS,0) AS AVAILABLE_SEATS,(SELECT COUNT(*) FROM course_periods cp2 WHERE cp2.COURSE_ID=cp.COURSE_ID) AS SECTIONS FROM course_periods cp WHERE PARENT_ID=COURSE_PERIOD_ID ORDER BY SECTIONS,AVAILABLE_SEATS";
	$sql = "SELECT PARENT_ID,cp.COURSE_PERIOD_ID,COURSE_ID,SHORT_NAME,COURSE_ID AS COURSE,GENDER_RESTRICTION,cpsp.PERIOD_ID,cpsp.DAYS,TEACHER_ID,MARKING_PERIOD_ID,MP,COALESCE(TOTAL_SEATS,0)-COALESCE(FILLED_SEATS,0) AS AVAILABLE_SEATS,
	(SELECT COUNT(*) FROM course_periods cp2 WHERE cp2.COURSE_ID=cp.COURSE_ID) AS SECTIONS
	FROM course_periods cp,course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND PARENT_ID=cp.COURSE_PERIOD_ID
	AND SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY COURSE_ID,SHORT_NAME"; //ORDER BY SECTIONS,AVAILABLE_SEATS

	$cp_course_RET = DBGet( $sql, [], [ 'COURSE' ] );

	$mps_RET = DBGet( "SELECT PARENT_ID,MARKING_PERIOD_ID
		FROM school_marking_periods
		WHERE MP='QTR'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'", [], [ 'PARENT_ID', 'MARKING_PERIOD_ID' ] );

	// GET FILLED/LOCKED REQUESTS
	//FJ multiple school periods for a course period
	/*$sql = "SELECT s.STUDENT_ID,r.REQUEST_ID,s.COURSE_PERIOD_ID,cp.PARENT_ID,s.COURSE_ID,cp.PERIOD_ID FROM schedule_requests r,schedule s,course_periods cp WHERE
	s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.PARENT_ID=cp.COURSE_PERIOD_ID AND
	r.SYEAR='".UserSyear()."' AND r.SCHOOL_ID='".UserSchool()."' AND s.SYEAR=r.SYEAR AND s.SCHOOL_ID=r.SCHOOL_ID
	AND s.COURSE_ID=r.COURSE_ID AND r.STUDENT_ID = s.STUDENT_ID
	AND ('".DBDate()."' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)";*/
	$sql = "SELECT s.STUDENT_ID,r.REQUEST_ID,s.COURSE_PERIOD_ID,cp.PARENT_ID,s.COURSE_ID,cpsp.PERIOD_ID
	FROM schedule_requests r,schedule s,course_periods cp,course_period_school_periods cpsp
	WHERE cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND cp.PARENT_ID=cp.COURSE_PERIOD_ID
	AND r.SYEAR='" . UserSyear() . "'
	AND r.SCHOOL_ID='" . UserSchool() . "'
	AND s.SYEAR=r.SYEAR
	AND s.SCHOOL_ID=r.SCHOOL_ID
	AND s.COURSE_ID=r.COURSE_ID
	AND r.STUDENT_ID=s.STUDENT_ID
	AND ('" . $start_date . "' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)";

	$locked_RET = DBGet( $sql, [], [ 'STUDENT_ID', 'REQUEST_ID' ] );

	$schedule = [];

	foreach ( (array) $locked_RET as $student_id => $courses )
	{
		foreach ( (array) $courses as $request_id => $course )
		{
			$course = $course[1];

			foreach ( (array) $cp_parent_RET[$course['PARENT_ID']] as $slice )
			{
				$schedule[$student_id][$slice['PERIOD_ID']][] = $slice + [ 'REQUEST_ID' => $request_id ];
				$filled[$request_id] = true;
			}
		}
	}

	if ( ob_get_level() == 0 )
	{
		ob_start();
	}

	$last_percent = 0;
	$completed = 0;
	$requests_count = count( (array) $requests_RET );
//FJ fix error Warning: Invalid argument supplied for foreach()
	$unfilled = [];

	foreach ( (array) $requests_RET as $request_id => $request )
	{
		// EXISTING / LOCKED COURSE

		if ( ! empty( $locked_RET[$request[1]['STUDENT_ID']][$request[1]['REQUEST_ID']] ) )
		{
			$completed++;
			continue;
		}

		$scheduled = _scheduleRequest( $request[1] );

		if ( ! $scheduled )
		{
			$not_request = [];

			if ( ! empty( $locked_RET[$request[1]['STUDENT_ID']] ) )
			{
				foreach ( (array) $locked_RET[$request[1]['STUDENT_ID']] as $request_id => $requests )
				{
					$not_request[] = $request_id;
				}
			}

			$moved = _moveRequest( $request[1], $not_request );

			if ( ! $moved )
			{
				$unfilled[] = $request;
			}
			else
			{
				$filled[$request[1]['REQUEST_ID']] = true;
			}
		}
		else
		{
			$filled[$request[1]['REQUEST_ID']] = true;
		}

		$completed++;

		$percent = round( $completed * 100 / $requests_count, 0 );

		if ( $percent > $last_percent )
		{
			echo '<script>';

			for ( $i = $last_percent + 1; $i <= $percent; $i++ )
			{
				echo 'cell' . $i . '.bgColor=' . json_encode( Preferences( 'HIGHLIGHT' ) ) . ';' . "\r";
			}

			echo 'document.getElementById("percentDIV").innerHTML = ' . json_encode( sprintf( _( '%d%% Done' ), $percent ) ) . ';</script>';
			ob_flush();
			flush();
			$last_percent = $percent;
		}
	}

	echo '<!-- unfilled ' . count( $unfilled ) . ' -->';

	foreach ( (array) $unfilled as $key => $request )
	{
		$scheduled = _scheduleRequest( $request[1] );

		if ( ! $scheduled )
		{
			$not_request = [];

			if ( ! empty( $locked_RET[$request[1]['STUDENT_ID']] ) )
			{
				foreach ( (array) $locked_RET[$request[1]['STUDENT_ID']] as $request_id => $requests )
				{
					$not_request[] = $request_id;
				}
			}

			$moved = _moveRequest( $request[1], $not_request );

			if ( $moved )
			{
				unset( $unfilled[$key] );
			}
		}
		else
		{
			unset( $unfilled[$key] );
		}
	}

	echo '<!-- unfilled ' . count( $unfilled ) . ' -->';

	if ( empty( $_REQUEST['test_mode'] ) )
	{
		echo '<script>document.getElementById("percentDIV").innerHTML = ' .
			json_encode( '<span class="loading" style="visibility: visible;"></span> ' . _( 'Saving Schedules ...' ) . ' ' ) . ';</script>';
		echo str_pad( ' ', 4096 );
		ob_flush();
		flush();

		db_trans_start();

		$date = $start_date;
		$course_period_temp = '';
		$student_id_temp = '';
		$scount = 0;
		$bad_locked = 0;

		foreach ( (array) $schedule as $student_id => $periods )
		{
			$course_periods_temp = [];

			foreach ( (array) $periods as $course_periods )
			{
				foreach ( (array) $course_periods as $period_id => $course_period )
				{
					$scount++;

					if ( empty( $locked_RET[$student_id][$course_period['REQUEST_ID']] )
						&& ! ( in_array( $course_period['COURSE_PERIOD_ID'], $course_periods_temp ) ) )
					{
						$insert_sql = DBInsertSQL(
							'schedule',
							[
								'SYEAR' => UserSyear(),
								'SCHOOL_ID' => UserSchool(),
								'STUDENT_ID' => (int) $student_id,
								'COURSE_ID' => (int) $course_period['COURSE_ID'],
								'COURSE_PERIOD_ID' => (int) $course_period['COURSE_PERIOD_ID'],
								'MP' => $course_period['MP'],
								'MARKING_PERIOD_ID' => (int) $course_period['MARKING_PERIOD_ID'],
								'START_DATE' => $date,
							]
						);

						db_trans_query( $insert_sql );

						// Hook.
						do_action( 'Scheduling/Scheduler.php|schedule_student' );
					}
					else
					{
						$bad_locked++;
					}

					//	db_trans_query($connection,"INSERT INTO schedule (SYEAR,SCHOOL_ID,STUDENT_ID,START_DATE,COURSE_ID,COURSE_PERIOD_ID,MP,MARKING_PERIOD_ID) values('".UserSyear()."','".UserSchool()."','".$student_id."','".$date."','".$course_period['COURSE_ID']."','".$course_period['COURSE_PERIOD_ID']."','".$course_period['MP']."','".$course_period['MARKING_PERIOD_ID']."');");

					//FJ multiple school periods for a course period
					$course_periods_temp[] = $course_period['COURSE_PERIOD_ID'];
				}
			}
		}

		echo '<!-- Bad Locked ' . $bad_locked . ' -->';
		echo '<!-- Schedule Count() ' . $scount . '-->';
		//echo 'Empty Courses:';

		foreach ( (array) $cp_parent_RET as $parent_id => $course_period )
		{
			$course_period = $course_period[1];
			//if ( $course_period['AVAILABLE_SEATS']<='0')
			//	echo $course_period['COURSE_ID'].': '.$course_period['COURSE_PERIOD_ID'].'<br />';
			db_trans_query( "UPDATE course_periods
				SET FILLED_SEATS=TOTAL_SEATS-'" . $course_period['AVAILABLE_SEATS'] . "'
				WHERE PARENT_ID='" . (int) $parent_id . "'" );
		}

		db_trans_commit();
	}

	if ( ( empty( $_REQUEST['test_mode'] )
			|| ! empty( $_REQUEST['delete'] ) )
		&& $DatabaseType === 'postgresql' )
	{
		echo '<script>document.getElementById("percentDIV").innerHTML = ' .
			json_encode( '<span class="loading" style="visibility: visible;"></span> ' . _( 'Optimizing ...' ) . ' ' ) . ';</script>';
		echo str_pad( ' ', 4096 );
		ob_flush();
		flush();

		// SQL VACUUM & ANALIZE are for PostgreSQL only.
		DBQuery( "VACUUM" );
		DBQuery( "ANALYZE" );
	}

	$error_msg = ErrorMessage( $error );

	echo '<script>document.getElementById("percentDIV").innerHTML = ' .
		json_encode( $error_msg . button( 'check' ) . ' ' . _( 'Done.' ) ) . ';</script>';
	ob_end_flush();

	echo '<br /><br />';

	$_REQUEST['search_modfunc'] = 'list';

	require_once 'modules/Scheduling/includes/UnfilledRequests.php';
}

/**
 * @param $request
 * @param $not_parent_id
 */
function _scheduleRequest( $request, $not_parent_id = false )
//{	global $requests_RET,$cp_parent_RET,$cp_course_RET,$mps_RET,$schedule,$filled,$unfilled;
{
	global $cp_parent_RET, $cp_course_RET, $schedule, $filled;

	$possible = [];

	foreach ( (array) $cp_course_RET[$request['COURSE_ID']] as $course_period )
	{
		foreach ( (array) $cp_parent_RET[$course_period['COURSE_PERIOD_ID']] as $slice )
		{
			if ( $slice['PARENT_ID'] == $not_parent_id )
			{
				// ALREADY SCHEDULED HERE.
				continue 2;
			}

			if ( $slice['AVAILABLE_SEATS'] <= 0 )
			{
				// NO SEATS.
				continue 2;
			}

			if ( $slice['GENDER_RESTRICTION'] != 'N'
				&& $slice['GENDER_RESTRICTION'] != mb_substr( $request['GENDER'], 0, 1 ) )
			{
				// SLICE VIOLATES GENDER RESTRICTION.
				continue 2;
			}

			if ( $slice['PARENT_ID'] == $slice['COURSE_PERIOD_ID']
				&& ( ( $request['WITH_TEACHER_ID'] != '' && $slice['TEACHER_ID'] != $request['WITH_TEACHER_ID'] )
					|| ( $request['NOT_TEACHER_ID'] && $slice['TEACHER_ID'] == $request['NOT_TEACHER_ID'] )
					|| ( $request['NOT_PERIOD_ID'] && $slice['PERIOD_ID'] == $request['NOT_PERIOD_ID'] ) ) )
			{
				// PARENT VIOLATES TEACHER / PERIOD REQUESTS.
				continue 2;
			}

			if ( $slice['PARENT_ID'] == $slice['COURSE_PERIOD_ID']
				&& ( $request['WITH_PERIOD_ID'] && $slice['PERIOD_ID'] != $request['WITH_PERIOD_ID'] ) )
			{
				// Fix Multiple School Periods: Course Period School Period does not match, skip.
				continue;
			}

			if ( ! empty( $schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']] ) )
			{
				// SHOULD LOOK FOR COMPATIBLE CP's IF NOT THE COMPLETE WEEK/YEAR.

				foreach ( (array) $schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']] as $existing_slice )
				{
					if ( $existing_slice['PARENT_ID'] != $not_parent_id && _isConflict( $existing_slice, $slice ) )
					{
						continue 3;
					}
				}
			}
		}

		// No conflict.
		$possible[] = $course_period;
	}

	if ( empty( $possible ) )
	{
		// If this point is reached, the request could not be scheduled.
		return false;
	}

	if ( $not_parent_id )
	{
		foreach ( (array) $cp_parent_RET[$not_parent_id] as $key => $slice )
		{
			foreach ( (array) $schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']] as $key2 => $item )
			{
				if ( $item['COURSE_PERIOD_ID'] == $slice['COURSE_PERIOD_ID'] )
				{
					// IF THIS COURSE IS BEING SCHEDULED A SECOND TIME, DELETE THE ORIGINAL ONE.
					$filled[$schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']][$key2]['REQUEST_ID']] = false;

					unset( $schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']][$key2] );

					$cp_parent_RET[$not_parent_id][$key]['AVAILABLE_SEATS']++;
				}
			}
		}
	}

	// CHOOSE THE BEST CP.
	_scheduleBest( $request, $possible );

	return true;
}

/**
 * @param $request
 * @param $not_request
 * @param false $not_parent_id
 */
function _moveRequest( $request, $not_request = false, $not_parent_id = false )
{
	global $requests_RET, $cp_parent_RET, $cp_course_RET, $schedule;

	if ( ! $not_request || ! is_array( $not_request ) )
	{
		$not_request = [];
	}

	if ( ! empty( $cp_course_RET[$request['COURSE_ID']] ) )
	{
		foreach ( (array) $cp_course_RET[$request['COURSE_ID']] as $course_period )
		{
			// CLEAR OUT A SLOT FOR EACH $slice

			foreach ( (array) $cp_parent_RET[$course_period['PARENT_ID']] as $slice )
			{
				/* Don't bother to move courses around if request can't be scheduled here anyway. */

				if ( $slice['AVAILABLE_SEATS'] <= 0 )
				{
					// SEAT COUNTS.
					continue 2;
				}

				if ( $slice['GENDER_RESTRICTION'] != 'N'
					&& $slice['GENDER_RESTRICTION'] != mb_substr( $request['GENDER'], 0, 1 ) )
				{
					// SLICE VIOLATES GENDER RESTRICTION.
					continue 2;
				}

				if ( $slice['PARENT_ID'] == $slice['COURSE_PERIOD_ID']
					&& ( ( $request['WITH_TEACHER_ID'] != '' && $slice['TEACHER_ID'] != $request['WITH_TEACHER_ID'] )
						|| ( $request['NOT_TEACHER_ID'] && $slice['TEACHER_ID'] == $request['NOT_TEACHER_ID'] )
						|| ( $request['NOT_PERIOD_ID'] && $slice['PERIOD_ID'] == $request['NOT_PERIOD_ID'] ) ) )
				{
					// PARENT VIOLATES TEACHER / PERIOD REQUESTS.
					continue 2;
				}

				if ( $slice['PARENT_ID'] == $slice['COURSE_PERIOD_ID']
					&& ( $request['WITH_PERIOD_ID'] && $slice['PERIOD_ID'] != $request['WITH_PERIOD_ID'] ) )
				{
					// Fix Multiple School Periods: Course Period School Period does not match, skip.
					continue;
				}

				if ( ! empty( $schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']] ) )
				{
					foreach ( (array) $schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']] as $existing_slice )
					{
						if ( in_array( $existing_slice['REQUEST_ID'], $not_request ) )
						{
							continue 3;
						}

						if ( true )
						{
							$not_request_temp = $not_request;
							$not_request_temp[] = $existing_slice['REQUEST_ID'];

							if ( ! $scheduled = _scheduleRequest( $requests_RET[$existing_slice['REQUEST_ID']][1], $existing_slice['PARENT_ID'] ) )
							{
								if ( ! $moved = _moveRequest( $requests_RET[$existing_slice['REQUEST_ID']][1], $not_request_temp, $existing_slice['PARENT_ID'] ) )
								{
									continue 3;
								}
							}
						}
					}
				}
				else
				{
					// WTF???
				}
			}

			if ( _scheduleRequest( $request, $not_parent_id ) )
			{
				return true;
			}
		}
	}

	// If this point is reached, the request could not be scheduled.
	return false;
}

/**
 * @param $existing_slice
 * @param $slice
 */
function _isConflict( $existing_slice, $slice )
//{	global $requests_RET,$cp_parent_RET,$cp_course_RET,$mps_RET,$schedule,$filled,$unfilled,$fy_id;
{
	global $mps_RET, $fy_id;

	$mp_conflict = $days_conflict = false;
	// LOOK FOR CONFLICT IN SCHEDULED SLICE -- CONFLICT == SEATS,MP,DAYS,PERIOD TIMES

	// MARKING PERIOD CONFLICTS

	if ( $existing_slice['MARKING_PERIOD_ID'] == $fy_id
		|| ( $slice['MARKING_PERIOD_ID'] == $fy_id
			&& ( ! $request['MARKING_PERIOD_ID'] || $request['MARKING_PERIOD_ID'] == $slice['MARKING_PERIOD_ID'] ) ) )
	{
		$mp_conflict = true;
	}
	// if either course is full year
	elseif ( $existing_slice['MARKING_PERIOD_ID'] == $slice['MARKING_PERIOD_ID'] )
	{
		$mp_conflict = true;
	}
	// if both fall in the same QTR or SEM
	elseif ( $existing_slice['MP'] == $slice['MP'] )
	{
		$mp_conflict = false;
	}
	// both are SEM's or QTR's, but not the same
	elseif ( $existing_slice['MP'] == 'SEM'
		&& $mps_RET[$existing_slice['MARKING_PERIOD_ID']][$slice['MARKING_PERIOD_ID']] )
	{
		$mp_conflict = true;
	}
	// the new course is a quarter in the existing semester
	elseif ( $mps_RET[$slice['MARKING_PERIOD_ID']][$existing_slice['MARKING_PERIOD_ID']] )
	{
		$mp_conflict = true;
	}
	// the existing course is a quarter in the new semester
	else
	{
		$mp_conflict = false;
	}
	// not the same MP, but no conflict

	if ( $mp_conflict ) // only look for a day conflict if there's already an MP conflict
	{
		if ( mb_strlen( $slice['DAYS'] ) + mb_strlen( $existing_slice['DAYS'] ) > 7 )
		{
			$days_conflict = true;
		}
		else
		{
			$days_len = mb_strlen( $slice['DAYS'] );

			for ( $i = 0; $i < $days_len; $i++ )
			{
				if ( mb_strpos( $existing_slice['DAYS'], mb_substr( $slice['DAYS'], $i, 1 ) ) !== false )
				{
					$days_conflict = true;
					break;
				}
			}
		}

		if ( $days_conflict )
		{
			return true;
		}
		// Go to the next available section
	}

	return false; // There is no conflict
}

/**
 * @param $request
 * @param $possible
 */
function _scheduleBest( $request, $possible )
{
	global $cp_parent_RET, $schedule;

	$best = $possible[0];

	if ( count( (array) $possible ) > 1 )
	{
		foreach ( (array) $possible as $course_period )
		{
			if ( $cp_parent_RET[$course_period['COURSE_PERIOD_ID']][1]['AVAILABLE_SEATS'] > $cp_parent_RET[$best['COURSE_PERIOD_ID']][1]['AVAILABLE_SEATS'] )
			{
				$best = $course_period;
			}
		}
	}

	foreach ( (array) $cp_parent_RET[$best['COURSE_PERIOD_ID']] as $key => $slice )
	{
		$schedule[$request['STUDENT_ID']][$slice['PERIOD_ID']][] = $slice + [ 'REQUEST_ID' => $request['REQUEST_ID'] ];

		$cp_parent_RET[$best['COURSE_PERIOD_ID']][$key]['AVAILABLE_SEATS']--;
	}
}

/**
 * @param $arg1
 * @param $arg2
 * @param $arg3
 */
function _returnTrue( $arg1, $arg2 = '', $arg3 = '' )
{
	return true;
}
