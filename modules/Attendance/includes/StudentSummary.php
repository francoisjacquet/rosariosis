<?php
require_once 'modules/Attendance/includes/AttendanceCodes.fnc.php';

$_REQUEST['student_id'] = issetVal( $_REQUEST['student_id'] );
$_REQUEST['period_id'] = issetVal( $_REQUEST['period_id'] );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

// Fix period_id=PERIOD or period_id=TEACHER: only for DailySummary.php.
if ( $_REQUEST['period_id'] === 'PERIOD'
	|| $_REQUEST['period_id'] === 'TEACHER' )
{
	RedirectURL( 'period_id' );
}

//FJ bugfix bug when Back to Student Search

if ( $_REQUEST['search_modfunc']
	|| $_REQUEST['student_id']
	|| User( 'PROFILE' ) === 'parent'
	|| User( 'PROFILE' ) === 'student' )
{
	$period_select = '';

	if ( ! UserStudentID() && ! $_REQUEST['student_id'] )
	{
		$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE
		FROM school_periods sp
		WHERE sp.SYEAR='" . UserSyear() . "'
		AND sp.SCHOOL_ID='" . UserSchool() . "'
		AND EXISTS (SELECT ''
			FROM course_periods cp, course_period_school_periods cpsp
			WHERE  cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND cpsp.PERIOD_ID=sp.PERIOD_ID
			AND position(',0,' IN cp.DOES_ATTENDANCE)>0
			" . ( User( 'PROFILE' ) === 'teacher' ? " AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" : '' ) . ")
		ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER,sp.TITLE" );

		$period_select = '<select name="period_id" id="period_id" onchange="ajaxPostForm(this.form,true);">
			<option value="">' . _( 'Daily' ) . '</option>';

		if ( ! empty( $periods_RET ) )
		{
			//FJ All periods

			if ( count( $periods_RET ) > 1 )
			{
				$period_select .= '<option value="all"' . ( ( $_REQUEST['period_id'] == 'all' ) ? ' selected' : '' ) . '>' . _( 'All Periods' ) . '</option>';
			}

			foreach ( (array) $periods_RET as $period )
			{
				$period_select .= '<option value="' . AttrEscape( $period['PERIOD_ID'] ) . '"' .
					( isset( $_REQUEST['period_id'] ) && $_REQUEST['period_id'] == $period['PERIOD_ID'] ? ' selected' : '' ) .
					'>' . $period['TITLE'] . '</option>';
			}
		}

		$period_select .= '</select>
			<label for="period_id" class="a11y-hidden">' . _( 'Periods' ) . '</label>';
	}

	echo '<form action="' . PreparePHP_SELF( [], [
		'month_start',
		'day_start',
		'year_start',
		'month_end',
		'day_end',
		'year_end',
	] ) . '" method="GET">';

	DrawHeader( _( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) .
		' &nbsp; ' . _( 'to' ) . ' &nbsp; ' . PrepareDate( $end_date, '_end', false ) .
		' ' . Buttons( _( 'Go' ) ),
		$period_select
	);

	echo '</form>';
}

if ( ! empty( $_REQUEST['period_id'] ) )
{
	//FJ All periods

	if ( $_REQUEST['period_id'] == 'all' )
	{
		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$period_ids_RET = DBGet( "SELECT PERIOD_ID
				FROM course_period_school_periods
				WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );
		}
		else
		{
			$period_ids_RET = DBGet( "SELECT PERIOD_ID
				FROM school_periods
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );
		}

		$period_ids_list = [];

		foreach ( (array) $period_ids_RET as $period_id )
		{
			$period_ids_list[] = $period_id['PERIOD_ID'];
		}

		$period_ids_list = implode( ',', $period_ids_list );
	}
	else
	{
		$period_ids_list = $_REQUEST['period_id'];
	}

	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
	$extra['SELECT'] .= ",(SELECT count(*) FROM attendance_period ap,attendance_codes ac
		WHERE ac.ID=ap.ATTENDANCE_CODE AND (ac.STATE_CODE='A' OR ac.STATE_CODE='H') AND ap.STUDENT_ID=ssm.STUDENT_ID
		AND ap.PERIOD_ID IN (" . $period_ids_list . ")
		AND ap.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND ac.SYEAR=ssm.SYEAR) AS STATE_ABS";

	$extra['columns_after']['STATE_ABS'] = _( 'State Abs' );

	$codes_RET = DBGet( "SELECT ID,TITLE
		FROM attendance_codes
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND TABLE_NAME='0'
		AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL)" );

	if ( ! empty( $codes_RET ) && count( $codes_RET ) > 1 )
	{
		foreach ( (array) $codes_RET as $code )
		{
			$extra['SELECT'] .= ",(SELECT count(*) FROM attendance_period ap,attendance_codes ac
				WHERE ac.ID=ap.ATTENDANCE_CODE
				AND ac.ID='" . (int) $code['ID'] . "'
				AND ap.PERIOD_ID IN (" . $period_ids_list . ")
				AND ap.STUDENT_ID=ssm.STUDENT_ID
				AND ap.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "') AS ABS_" . $code['ID'];

			$extra['columns_after']['ABS_' . $code['ID']] = $code['TITLE'];
		}
	}
}
else
{
	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
	$extra['SELECT'] .= ",(SELECT COALESCE((sum(STATE_VALUE-1)*-1),0.0) FROM attendance_day ad
		WHERE ad.STUDENT_ID=ssm.STUDENT_ID
		AND ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND ad.SYEAR=ssm.SYEAR) AS STATE_ABS";

	$extra['columns_after']['STATE_ABS'] = _( 'Days Absent' );
}

$extra['link']['FULL_NAME']['link'] = PreparePHP_SELF();

$extra['link']['FULL_NAME']['variables'] = [ 'student_id' => 'STUDENT_ID' ];

Widgets( 'course' );

Widgets( 'absences' );

$extra['new'] = true;

$extra['action'] = '&report=' . $_REQUEST['report'];

Search( 'student_id', $extra );

$is_student_report = UserStudentID();

if ( User( 'PROFILE' ) === 'student'
	|| User( 'PROFILE' ) === 'parent' )
{
	$is_student_report = ! empty( $_REQUEST['student_id'] )
		&& $_REQUEST['student_id'] === UserStudentID();
}

if ( $is_student_report )
{
	$full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
		FROM students
		WHERE STUDENT_ID='" . UserStudentID() . "'" );

	DrawHeader( $full_name, AttendanceCodesTipMessage() );

	$absences_RET = DBGet( "SELECT ap.STUDENT_ID,ap.PERIOD_ID,ap.SCHOOL_DATE,ac.SHORT_NAME,
		ac.TITLE,ac.STATE_CODE,ad.STATE_VALUE,ad.COMMENT AS OFFICE_COMMENT,ap.COMMENT AS TEACHER_COMMENT
	FROM attendance_period ap,attendance_day ad,attendance_codes ac
	WHERE ap.STUDENT_ID=ad.STUDENT_ID
	AND ap.SCHOOL_DATE=ad.SCHOOL_DATE
	AND ap.ATTENDANCE_CODE=ac.ID
	AND (ac.DEFAULT_CODE!='Y' OR ac.DEFAULT_CODE IS NULL)
	AND ap.STUDENT_ID='" . UserStudentID() . "'
	AND ap.SCHOOL_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'
	AND ad.SYEAR='" . UserSyear() . "'
	ORDER BY ap.SCHOOL_DATE", [], [ 'SCHOOL_DATE', 'PERIOD_ID' ] );

	$days_RET = [];

	$i = 0;

	foreach ( (array) $absences_RET as $school_date => $absences )
	{
		$i++;

		$days_RET[$i]['SCHOOL_DATE'] = ProperDate( $school_date );

		$days_RET[$i]['DAILY'] = _makeStateValue( $absences[key( $absences )][1]['STATE_VALUE'] );

		$days_RET[$i]['OFFICE_COMMENT'] = $absences[key( $absences )][1]['OFFICE_COMMENT'];

		foreach ( (array) $absences as $period_id => $absence )
		{
			$days_RET[$i][$period_id] = _makeColor(
				$absence[1]['SHORT_NAME'],
				$absence[1]['TITLE'],
				$absence[1]['STATE_CODE']
			);

			if ( ! empty( $absence[1]['TEACHER_COMMENT'] ) )
			{
				// @since 5.0 Merge Period & Teacher Comment columns to gain space.
				$days_RET[$i][$period_id] .= ' <span class="size-1">' . $absence[1]['TEACHER_COMMENT'] . '</span>';
			}
		}
	}

	//FJ multiple school periods for a course period
	//$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.SHORT_NAME FROM school_periods sp,schedule s,course_periods cp WHERE sp.SCHOOL_ID='".UserSchool()."' AND sp.SYEAR='".UserSyear()."' AND s.STUDENT_ID='".UserStudentID()."' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0 ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER" );
	$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.SHORT_NAME,sp.TITLE
	FROM school_periods sp,schedule s,course_periods cp,course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND sp.SCHOOL_ID='" . UserSchool() . "'
	AND sp.SYEAR='" . UserSyear() . "'
	AND s.STUDENT_ID='" . UserStudentID() . "'
	AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID
	AND cpsp.PERIOD_ID=sp.PERIOD_ID
	AND position(',0,' IN cp.DOES_ATTENDANCE)>0
	ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER" );

	$columns['SCHOOL_DATE'] = _( 'Date' );

	$columns['DAILY'] = _( 'Present' );

	$columns['OFFICE_COMMENT'] = _( 'Office Comment' );

	foreach ( (array) $periods_RET as $period )
	{
		// Fix column name is empty, use Period Title if no Short Name.
		$columns[$period['PERIOD_ID']] = ( $period['SHORT_NAME'] ? $period['SHORT_NAME'] : $period['TITLE'] );
	}

	ListOutput(
		$days_RET,
		$columns,
		'Day',
		'Days'
	);
}

/**
 * @param $value
 */
function _makeStateValue( $value )
{
	return MakeAttendanceCode( $value );
}

/**
 * @param $value
 * @param $title
 * @param $state_code
 */
function _makeColor( $value, $title, $state_code )
{
	return MakeAttendanceCode(
		$state_code,
		$value,
		$title
	);
}
