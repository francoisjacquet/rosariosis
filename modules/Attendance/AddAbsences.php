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
		FROM ATTENDANCE_PERIOD
		WHERE EXTRACT(MONTH FROM SCHOOL_DATE)='" . ( $_REQUEST['month'] * 1 ) . "'
		AND EXTRACT(YEAR FROM SCHOOL_DATE)='" . $_REQUEST['year'] . "'
		AND PERIOD_ID IN (" . $periods_list . ")
		AND STUDENT_ID IN (" . $students_list . ")", array(), array( 'STUDENT_ID', 'SCHOOL_DATE', 'PERIOD_ID' ) );

		$state_code = DBGetOne( "SELECT STATE_CODE
			FROM ATTENDANCE_CODES
			WHERE ID='" . $_REQUEST['absence_code'] . "'" );

		foreach ( (array) $_REQUEST['student'] as $student_id )
		{
			foreach ( (array) $_REQUEST['dates'] as $date => $yes )
			{
				$current_mp = GetCurrentMP( 'QTR', $date );
				$all_mp = GetAllMP( 'QTR', $current_mp );
				//FJ days numbered
				//FJ multiple school periods for a course period

				if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
				{
					$course_periods_RET = DBGet( "SELECT s.COURSE_PERIOD_ID,cpsp.PERIOD_ID,cp.HALF_DAY
					FROM SCHEDULE s,COURSE_PERIODS cp,ATTENDANCE_CALENDAR ac,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
					WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND ac.SCHOOL_DATE='" . $date . "'
					AND ac.CALENDAR_ID=cp.CALENDAR_ID
					AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL)
					AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
					AND s.STUDENT_ID='" . $student_id . "'
					AND cpsp.PERIOD_ID IN (" . $periods_list . ")
					AND position(',0,' IN cp.DOES_ATTENDANCE)>0
					AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE))
					AND position(substring('MTWHFSU' FROM cast(
						(SELECT CASE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
						FROM attendance_calendar
						WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=ac.SCHOOL_DATE AND end_date>=ac.SCHOOL_DATE AND mp='QTR' AND SCHOOL_ID=ac.SCHOOL_ID)
						AND school_date<=ac.SCHOOL_DATE
						AND SCHOOL_ID=ac.SCHOOL_ID)
					AS INT) FOR 1) IN cpsp.DAYS)>0
					AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
					AND ac.SCHOOL_ID=s.SCHOOL_ID", array(), array( 'PERIOD_ID' ) );
				}
				else
				{
					$course_periods_RET = DBGet( "SELECT s.COURSE_PERIOD_ID,cpsp.PERIOD_ID,cp.HALF_DAY
						FROM SCHEDULE s,COURSE_PERIODS cp,ATTENDANCE_CALENDAR ac,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
						WHERE sp.PERIOD_ID=cpsp.PERIOD_ID
						AND ac.SCHOOL_DATE='" . $date . "'
						AND ac.CALENDAR_ID=cp.CALENDAR_ID
						AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL)
						AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						AND s.STUDENT_ID='" . $student_id . "'
						AND cpsp.PERIOD_ID IN (" . $periods_list . ")
						AND position(',0,' IN cp.DOES_ATTENDANCE)>0
						AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE))
						AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM ac.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0 AND s.MARKING_PERIOD_ID IN ($all_mp)", array(), array( 'PERIOD_ID' ) );
				}

				//echo '<pre>'; var_dump($course_periods_RET); echo '</pre>';

				foreach ( (array) $_REQUEST['period'] as $period_id => $yes )
				{
					if ( ! $yes )
					{
						continue;
					}

					$course_period_id = $course_periods_RET[$period_id][1]['COURSE_PERIOD_ID'];

					if ( $course_period_id && ! ( $course_periods_RET[$period_id][1]['COURSE_PERIOD_ID'] == 'Y' && $state_code == 'H' ) )
					{
						if ( empty( $current_RET[$student_id][$date][$period_id] ) )
						{
							$sql = "INSERT INTO ATTENDANCE_PERIOD
							(STUDENT_ID,SCHOOL_DATE,PERIOD_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID,ATTENDANCE_CODE,ATTENDANCE_REASON,ADMIN)
							VALUES('" . $student_id . "','" . $date . "','" . $period_id . "','" .
							$current_mp . "','" . $course_period_id . "','" . $_REQUEST['absence_code'] . "','" .
							$_REQUEST['absence_reason'] . "','Y')";

							DBQuery( $sql );
						}
						else
						{
							$sql = "UPDATE ATTENDANCE_PERIOD
							SET ATTENDANCE_CODE='" . $_REQUEST['absence_code'] . "',ATTENDANCE_REASON='" .
							$_REQUEST['absence_reason'] . "',ADMIN='Y',COURSE_PERIOD_ID='" . $course_period_id . "'
							WHERE STUDENT_ID='" . $student_id . "'
							AND SCHOOL_DATE='" . $date . "'
							AND PERIOD_ID='" . $period_id . "'";

							DBQuery( $sql );
						}
					}
				}

				UpdateAttendanceDaily(
					$student_id,
					$date,
					( $_REQUEST['absence_reason'] ? $_REQUEST['absence_reason'] : false )
				);
			}
		}

		$note[] = button( 'check' ) . '&nbsp;' . _( 'Absence records were added for the selected students.' );
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
	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Add Absences to Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', _( 'Add Absences' ) );

		echo '<table class="cellpadding-5 col1-align-right center"><tr><td>' . _( 'Add Absence to Periods' ) . '</td>';
		echo '<td><table><tr>';

		//FJ multiple school periods for a course period
		//$periods_RET = DBGet( "SELECT SHORT_NAME,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND EXISTS (SELECT '' FROM COURSE_PERIODS WHERE PERIOD_ID=SCHOOL_PERIODS.PERIOD_ID AND position(',0,' IN DOES_ATTENDANCE)>0) ORDER BY SORT_ORDER" );
		$periods_RET = DBGet( "SELECT SHORT_NAME,PERIOD_ID
		FROM SCHOOL_PERIODS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND EXISTS (SELECT '' FROM COURSE_PERIOD_SCHOOL_PERIODS cpsp, COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=SCHOOL_PERIODS.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0)
		ORDER BY SORT_ORDER" );

		foreach ( (array) $periods_RET as $period )
		{
			echo '<td><label><input type="CHECKBOX" value="Y" name="period[' . $period['PERIOD_ID'] . ']"> ' . $period['SHORT_NAME'] . '</label></td>';
		}

		echo '</tr></table></td>';

		echo '<tr><td>' . _( 'Absence Code' ) . '</td><td><select name="absence_code">';

		$codes_RET = DBGet( "SELECT TITLE,ID
			FROM ATTENDANCE_CODES
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND TABLE_NAME='0'" );

		foreach ( (array) $codes_RET as $code )
		{
			echo '<option value=' . $code['ID'] . '>' . $code['TITLE'] . '</option>';
		}

		echo '</select></td></tr>';

		echo '<tr><td>' . _( 'Absence Reason' ) . '</td><td>';

		echo TextInput(
			'',
			'absence_reason',
			'',
			'size=20 maxlength=100',
			false
		);

		echo '</td></tr>';

		echo '<tr><td colspan="2"><div class="center">';

		$time = mktime( 0, 0, 0, $_REQUEST['month'] * 1, 1, mb_substr( $_REQUEST['year'], 2 ) );

		echo PrepareDate( mb_strtoupper( date( "d-M-y", $time ) ), '', false, array( 'M' => 1, 'Y' => 1, 'submit' => true ) );

		$skip = date( "w", $time );
		$last = 31;

		while ( ! checkdate( $_REQUEST['month'] * 1, $last, mb_substr( $_REQUEST['year'], 2 ) ) )
		{
			$last--;
		}

		echo '</div><table class="width-100p"><tr>';
		//echo '<th>S</th><th>M</th><th>T</th><th>W</th><th>Th</th><th>F</th><th>S</th></tr><tr>';

		echo '<th>' . mb_substr( _( 'Sunday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Monday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Tuesday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Wednesday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Thursday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Friday' ), 0, 3 ) .
		'</th><th>' . mb_substr( _( 'Saturday' ), 0, 3 ) .
		'</th></tr><tr>';

		$calendar_RET = DBGet( "SELECT SCHOOL_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND MINUTES!='0'
			AND EXTRACT(MONTH FROM SCHOOL_DATE)='" . ( $_REQUEST['month'] * 1 ) . "'", array(), array( 'SCHOOL_DATE' ) );

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

			echo '<td><label>' . $i . '<input type="checkbox" name="dates[' . $this_date . ']" value="Y"' . $disabled . '></label></td>';

			$skip++;

			if ( $skip % 7 == 0 && $i != $last )
			{
				echo '</tr><tr>';
			}
		}

		echo '</tr></table>';
		echo '</td></tr></table>';

		PopTable( 'footer' );

		echo '<br />';
	}

	Widgets( 'course' );
	Widgets( 'absences' );

	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );

	$extra['columns_before'] = array(
		'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ),
	);

	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Absences to Selected Students' ) ) . '</div></form>';
	}
}
