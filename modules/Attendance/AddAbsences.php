<?php
//FJ move Attendance.php from functions/ to modules/Attendance/includes
require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';

DrawHeader( ProgramTitle() );

if ( empty( $_REQUEST['month'] ) )
{
	$_REQUEST['month'] = date( 'm' );
}

if ( empty( $_REQUEST['year'] ) )
{
	$_REQUEST['year'] = date( 'Y' );
}
else
{
	$_REQUEST['year'] = ( $_REQUEST['year'] < 1900 ? '20' . $_REQUEST['year'] : $_REQUEST['year'] );
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['period'] )
		&& ! empty( $_REQUEST['student'] )
		&& ! empty( $_REQUEST['dates'] ) )
	{
		$periods_list = "'" . implode( "','", array_keys( $_REQUEST['period'] ) ) . "'";

		$students_list = "'" . implode( "','", $_REQUEST['student'] ) . "'";

		$current_RET = DBGet( "SELECT STUDENT_ID,PERIOD_ID,SCHOOL_DATE
		FROM attendance_period
		WHERE EXTRACT(MONTH FROM SCHOOL_DATE)='" . ( $_REQUEST['month'] * 1 ) . "'
		AND EXTRACT(YEAR FROM SCHOOL_DATE)='" . $_REQUEST['year'] . "'
		AND PERIOD_ID IN (" . $periods_list . ")
		AND STUDENT_ID IN (" . $students_list . ")", [], [ 'STUDENT_ID', 'SCHOOL_DATE', 'PERIOD_ID' ] );

		$go = false;

		foreach ( (array) $_REQUEST['student'] as $student_id )
		{
			foreach ( (array) $_REQUEST['dates'] as $date => $yes )
			{
				$current_mp = GetCurrentMP( 'QTR', $date );
				$all_mp = GetAllMP( 'QTR', $current_mp );

				if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
				{
					// FJ days numbered.
					// FJ multiple school periods for a course period.
					$course_periods_RET = DBGet( "SELECT s.COURSE_PERIOD_ID,cpsp.PERIOD_ID
					FROM schedule s,course_periods cp,attendance_calendar ac,school_periods sp,course_period_school_periods cpsp
					WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND ac.SCHOOL_DATE='" . $date . "'
					AND ac.CALENDAR_ID=cp.CALENDAR_ID
					AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL)
					AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
					AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND s.STUDENT_ID='" . (int) $student_id . "'
					AND cpsp.PERIOD_ID IN (" . $periods_list . ")
					AND position(',0,' IN cp.DOES_ATTENDANCE)>0
					AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE))
					AND position(substring('MTWHFSU' FROM cast(
						(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
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
					" FOR 1) IN cpsp.DAYS)>0
					AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
					AND ac.SCHOOL_ID=s.SCHOOL_ID
					AND ac.SYEAR=s.SYEAR", [], [ 'PERIOD_ID' ] );
				}
				else
				{
					// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
					$course_periods_RET = DBGet( "SELECT s.COURSE_PERIOD_ID,cpsp.PERIOD_ID
						FROM schedule s,course_periods cp,attendance_calendar ac,school_periods sp,course_period_school_periods cpsp
						WHERE sp.PERIOD_ID=cpsp.PERIOD_ID
						AND ac.SCHOOL_DATE='" . $date . "'
						AND ac.CALENDAR_ID=cp.CALENDAR_ID
						AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL)
						AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
						AND s.STUDENT_ID='" . (int) $student_id . "'
						AND cpsp.PERIOD_ID IN (" . $periods_list . ")
						AND position(',0,' IN cp.DOES_ATTENDANCE)>0
						AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE))
						AND position(substring('UMTWHFS' FROM " .
						( $DatabaseType === 'mysql' ?
							"DAYOFWEEK(ac.SCHOOL_DATE)" :
							"cast(extract(DOW FROM ac.SCHOOL_DATE)+1 AS int)" ) .
						" FOR 1) IN cpsp.DAYS)>0 AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")", [], [ 'PERIOD_ID' ] );
				}

				//echo '<pre>'; var_dump($course_periods_RET); echo '</pre>';

				foreach ( (array) $_REQUEST['period'] as $period_id => $yes )
				{
					$course_period_id = issetVal( $course_periods_RET[$period_id][1]['COURSE_PERIOD_ID'] );

					if ( ! $yes
						|| ! $course_period_id )
					{
						continue;
					}

					if ( empty( $current_RET[$student_id][$date][$period_id] ) )
					{
						DBInsert(
							'attendance_period',
							[
								'STUDENT_ID' => (int) $student_id,
								'SCHOOL_DATE' => $date,
								'PERIOD_ID' => (int) $period_id,
								'MARKING_PERIOD_ID' => (int) $current_mp,
								'COURSE_PERIOD_ID' => (int) $course_period_id,
								'ATTENDANCE_CODE' => $_REQUEST['absence_code'],
								'ATTENDANCE_REASON' => $_REQUEST['absence_reason'],
								'ADMIN' => 'Y',
							]
						);
					}
					else
					{
						DBUpdate(
							'attendance_period',
							[
								'ATTENDANCE_CODE' => $_REQUEST['absence_code'],
								'ATTENDANCE_REASON' => $_REQUEST['absence_reason'],
								'ADMIN' => 'Y',
								'COURSE_PERIOD_ID' => (int) $course_period_id,
							],
							[
								'STUDENT_ID' => (int) $student_id,
								'SCHOOL_DATE' => $date,
								'PERIOD_ID' => (int) $period_id,
							]
						);
					}

					$go = true;
				}

				UpdateAttendanceDaily(
					$student_id,
					$date,
					( $_REQUEST['absence_reason'] ? $_REQUEST['absence_reason'] : false )
				);
			}
		}

		if ( $go )
		{
			$note[] = button( 'check' ) . '&nbsp;' . _( 'Absence records were added for the selected students.' );
		}
	}
	else
	{
		$error[] = _( 'You must choose at least one period and one student.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",NULL AS CHECKBOX";

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Add Absences to Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', _( 'Add Absences' ) );

		echo '<table class="cellpadding-5"><tr><td><table><tr>';

		//FJ multiple school periods for a course period
		//$periods_RET = DBGet( "SELECT SHORT_NAME,PERIOD_ID FROM school_periods WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND EXISTS (SELECT '' FROM course_periods WHERE PERIOD_ID=school_periods.PERIOD_ID AND position(',0,' IN DOES_ATTENDANCE)>0) ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );
		$periods_RET = DBGet( "SELECT COALESCE(SHORT_NAME,TITLE) AS SHORT_NAME,PERIOD_ID
		FROM school_periods
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND EXISTS (SELECT '' FROM course_period_school_periods cpsp, course_periods cp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=school_periods.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0)
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

		$i = 0;

		foreach ( (array) $periods_RET as $period )
		{
		    if ( $i++ % 3 == 0 )
		    {
		        echo '</tr><tr>';
		    }

			echo '<td><label><input type="CHECKBOX" value="Y" name="period[' . $period['PERIOD_ID'] . ']"> ' . $period['SHORT_NAME'] . '</label></td>';
		}

		echo '</tr></table>' .
			'&nbsp;<label class="nobr"><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'period\');">&nbsp;' .
			_( 'Check All' ) . '</label>' .
			FormatInputTitle( _( 'Add Absence to Periods' ) ) . '</td></tr>';

		echo '<tr><td><label><select name="absence_code">';

		$codes_RET = DBGet( "SELECT TITLE,ID
			FROM attendance_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND TABLE_NAME='0'" );

		foreach ( (array) $codes_RET as $code )
		{
			echo '<option value=' . $code['ID'] . '>' . $code['TITLE'] . '</option>';
		}

		echo '</select>' . FormatInputTitle( _( 'Absence Code' ) ) . '</label></td></tr>';

		echo '<tr><td>';

		echo TextInput(
			'',
			'absence_reason',
			_( 'Absence Reason' ),
			'size=20 maxlength=100',
			false
		) . '</td></tr>';

		echo '<tr><td><div class="center">';

		$time = mktime( 0, 0, 0, $_REQUEST['month'] * 1, 1, mb_substr( $_REQUEST['year'], 2 ) );

		echo PrepareDate( mb_strtoupper( date( "d-M-y", $time ) ), '', false, [ 'M' => 1, 'Y' => 1, 'submit' => true ] );

		$skip = date( "w", $time );
		$last = 31;

		while ( ! checkdate( $_REQUEST['month'] * 1, $last, mb_substr( $_REQUEST['year'], 2 ) ) )
		{
			$last--;
		}

		echo '</div><table class="width-100p"><thead><tr>';
		//echo '<th>S</th><th>M</th><th>T</th><th>W</th><th>Th</th><th>F</th><th>S</th></tr><tr>';

		echo '<th>' . mb_substr( _( 'Sunday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Monday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Tuesday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Wednesday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Thursday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Friday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Saturday' ), 0, 3 ) .
		'</th></tr></thead><tbody><tr>';

		$calendar_RET = DBGet( "SELECT SCHOOL_DATE
			FROM attendance_calendar
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND MINUTES!='0'
			AND EXTRACT(MONTH FROM SCHOOL_DATE)='" . ( $_REQUEST['month'] * 1 ) . "'", [], [ 'SCHOOL_DATE' ] );

		for ( $i = 1; $i <= $skip; $i++ )
		{
			echo '<td></td>';
		}

		for ( $i = 1; $i <= $last; $i++ )
		{
			$this_date = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . ( $i < 10 ? '0' . $i : $i );

			if ( empty( $calendar_RET[$this_date] ) )
			{
				$disabled = ' DISABLED';
			}
			elseif ( date( 'Y-m-d' ) === $this_date )
			{
				$disabled = ' checked';
			}
			else
			{
				$disabled = '';
			}

			echo '<td><label><input type="checkbox" name="dates[' . $this_date . ']" value="Y"' . $disabled . '> ' . $i . '</label></td>';

			$skip++;

			if ( $skip % 7 == 0 && $i != $last )
			{
				echo '</tr><tr>';
			}
		}

		echo '</tr></tbody></table>';
		echo '</td></tr></table>';

		PopTable( 'footer' );

		echo '<br />';
	}

	Widgets( 'course' );
	Widgets( 'absences' );

	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];

	$extra['columns_before'] = [
		'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ),
	];

	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Absences to Selected Students' ) ) . '</div></form>';
	}
}
