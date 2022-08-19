<?php

require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';
require_once 'modules/Attendance/includes/AttendanceCodes.fnc.php';

$_REQUEST['student_id'] = issetVal( $_REQUEST['student_id'], '' );
$_REQUEST['period_id'] = issetVal( $_REQUEST['period_id'], '' );

// Fix period_id=all: only for StudentSummary.php.
if ( $_REQUEST['period_id'] === 'all' )
{
	RedirectURL( 'period_id' );
}

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

if ( ! empty( $_REQUEST['attendance'] )
	&& $_POST['attendance']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['attendance'] as $student_id => $values )
	{
		foreach ( (array) $values as $school_date => $columns)
		{
			$sql = "UPDATE attendance_period SET ADMIN='Y',";

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE SCHOOL_DATE='" . $school_date . "'
				AND PERIOD_ID='" . (int) $_REQUEST['period_id'] . "'
				AND STUDENT_ID='" . (int) $student_id . "'";

			DBQuery( $sql );

			UpdateAttendanceDaily( $student_id, $school_date );
		}
	}

	// Unset attendance & redirect URL.
	RedirectURL( 'attendance' );
}

//FJ bugfix bug when Back to Student Search
//if ( $_REQUEST['search_modfunc'] || $_REQUEST['student_id'] || UserStudentID() || User( 'PROFILE' ) === 'parent' || User( 'PROFILE' ) === 'student')
if ( $_REQUEST['search_modfunc']
	|| $_REQUEST['student_id']
	|| User( 'PROFILE' ) === 'parent'
	|| User( 'PROFILE' ) === 'student')
{
	if ( User( 'PROFILE' ) === 'student' )
	{
		$_REQUEST['student_id'] = UserStudentID();
	}
	elseif ( $_REQUEST['student_id']
		|| User( 'PROFILE' ) === 'parent' )
	{
		// Just to set UserStudentID().
		Search( 'student_id' );
	}
	elseif ( User( 'PROFILE' ) === 'admin'
		|| User( 'PROFILE' ) === 'teacher' )
	{
		unset( $_SESSION['student_id'] );
	}

	// Fix GET parameters appearing multiple times in URL.
	$remove_request_params = [
		'month_start',
		'day_start',
		'year_start',
		'month_end',
		'day_end',
		'year_end',
		'period_id',
	];

	$PHP_tmp_SELF = PreparePHP_SELF( $_REQUEST, $remove_request_params );

	$period_select = '<select name="period_id" id="period_id" onchange="ajaxPostForm(this.form,true);">
		<option value=""' . ( empty( $_REQUEST['period_id'] ) ? ' selected' : '' ) . '>' .
		_( 'Daily' ) . '</option>';

	if ( ! UserStudentID() )
	{
		if ( User( 'PROFILE' ) === 'admin' )
		{
			//FJ multiple school periods for a course period
			//$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE FROM school_periods sp WHERE sp.SYEAR='".UserSyear()."' AND sp.SCHOOL_ID='".UserSchool()."' AND (SELECT count(1) FROM course_periods WHERE position(',0,' IN DOES_ATTENDANCE)>0 AND PERIOD_ID=sp.PERIOD_ID AND SYEAR=sp.SYEAR AND SCHOOL_ID=sp.SCHOOL_ID)>0 ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER" );
			$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE
			FROM school_periods sp
			WHERE sp.SYEAR='" . UserSyear() . "'
			AND sp.SCHOOL_ID='" . UserSchool() . "'
			AND (SELECT count(1)
				FROM course_periods cp,course_period_school_periods cpsp
				WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
				AND position(',0,' IN cp.DOES_ATTENDANCE)>0
				AND cpsp.PERIOD_ID=sp.PERIOD_ID
				AND cp.SYEAR=sp.SYEAR
				AND cp.SCHOOL_ID=sp.SCHOOL_ID)>0
			ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER,sp.TITLE" );
		}
		else
		{
			$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE
			FROM school_periods sp,course_periods cp, course_period_school_periods cpsp
			WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND position(',0,' IN cp.DOES_ATTENDANCE)>0
			AND sp.PERIOD_ID=cpsp.PERIOD_ID
			AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );
		}

		foreach ( (array) $periods_RET as $period )
		{
			$period_select .= '<option value="' . AttrEscape( $period['PERIOD_ID'] ) . '"' .
				( $_REQUEST['period_id'] == $period['PERIOD_ID'] ? ' selected' : '' ) . '>' .
				$period['TITLE'] . '</option>';
		}
	}
	else
	{
		if ( is_numeric( $_REQUEST['period_id'] ) )
		{
			$_REQUEST['period_id'] = 'PERIOD';
		}

		$period_select .= '<option value="PERIOD"' .
			( $_REQUEST['period_id'] === 'PERIOD' ? ' selected' : '' ) . '>' .
			_( 'By Period' ) . '</option>';

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			/**
			 * Teacher: My Periods option.
			 *
			 * @since 3.8
			 */
			$period_select .= '<option value="TEACHER"' .
				( $_REQUEST['period_id'] === 'TEACHER' ? ' selected' : '' ) . '>' .
				_( 'My Periods' ) . '</option>';
		}
	}

	$period_select .= '</select>
		<label for="period_id" class="a11y-hidden">' . _( 'Periods' ) . '</label>';

	echo '<form action="' . $PHP_tmp_SELF . '" method="GET">';

	DrawHeader( _( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) .
		' &nbsp; ' . _( 'to' ) . ' &nbsp; ' . PrepareDate( $end_date, '_end', false ) . ' ' .
		Buttons( _( 'Go' ) ),
		$period_select
	);

	echo '</form>';

	if ( ! UserStudentID() && $_REQUEST['period_id'] )
	{
		$has_edit_form = true;

		echo '<form action="' . PreparePHP_SELF() . '" method="POST">';
	}

	DrawHeader(
		( empty( $_REQUEST['period_id'] ) ? '' : AttendanceCodesTipMessage() ),
		( ! empty( $has_edit_form ) ? SubmitButton() : '' )
	);
}

// @since 9.2.1 SQL use REPLACE() instead of to_char() for MySQL compatibility
$cal_RET = DBGet( "SELECT DISTINCT SCHOOL_DATE,CONCAT('_', REPLACE(CAST(SCHOOL_DATE AS char(10)),'-','')) AS SHORT_DATE
	FROM attendance_calendar
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
	ORDER BY SCHOOL_DATE" );

//FJ bugfix bug when Back to Student Search
//if (UserStudentID() || $_REQUEST['student_id'] || User( 'PROFILE' ) === 'parent')
if ( $_REQUEST['student_id']
	|| User( 'PROFILE' ) === 'parent' )
{
	if ( $_REQUEST['period_id'] )
	{
		//FJ multiple school periods for a course period
		/*$sql = "SELECT
				cp.TITLE as COURSE_PERIOD,sp.TITLE as PERIOD,cp.PERIOD_ID
			FROM
				schedule s,courses c,course_periods cp,school_periods sp
			WHERE
				s.COURSE_ID = c.COURSE_ID AND s.COURSE_ID = cp.COURSE_ID
				AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND cp.PERIOD_ID = sp.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0
				AND s.SYEAR = c.SYEAR AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
				AND s.STUDENT_ID='".UserStudentID()."' AND s.SYEAR='".UserSyear()."'
				AND ('".DBDate()."' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
			ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER
			";*/
		$sql = "SELECT cp.TITLE as COURSE_PERIOD,sp.TITLE as PERIOD,cpsp.PERIOD_ID
			FROM schedule s,courses c,course_periods cp,school_periods sp,course_period_school_periods cpsp
			WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND	s.COURSE_ID=c.COURSE_ID
			AND s.COURSE_ID=cp.COURSE_ID
			AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
			AND cpsp.PERIOD_ID=sp.PERIOD_ID
			AND position(',0,' IN cp.DOES_ATTENDANCE)>0
			AND s.SYEAR=c.SYEAR
			AND cp.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
			AND s.STUDENT_ID='" . UserStudentID() . "'
			AND s.SYEAR='" . UserSyear() . "'
			AND ('" . DBDate() . "' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)";

		if ( User( 'PROFILE' ) === 'teacher'
			&& $_REQUEST['period_id'] === 'TEACHER' )
		{
			/**
			 * Teacher: My Periods option.
			 *
			 * @since 3.8
			 * @since 6.9 Add Secondary Teacher.
			 */
			$sql .= " AND (cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
				OR SECONDARY_TEACHER_ID='" . User( 'STAFF_ID' ) . "')";
		}

		$sql .= " ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER";

		$schedule_RET = DBGet( $sql );

		$sql = "SELECT ap.SCHOOL_DATE,ap.PERIOD_ID,ac.SHORT_NAME,ac.STATE_CODE,ac.DEFAULT_CODE,ac.TITLE
			FROM attendance_period ap,attendance_codes ac
			WHERE ap.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
			AND ap.ATTENDANCE_CODE=ac.ID
			AND ap.STUDENT_ID='" . UserStudentID() . "'";

		$attendance_RET = DBGet( $sql, [], [ 'SCHOOL_DATE', 'PERIOD_ID' ] );
	}
	else
	{
		$schedule_RET[1] = [
			'COURSE_PERIOD' => _( 'Daily Attendance' ),
			'PERIOD_ID' => '0',
		];

		$attendance_RET = DBGet( "SELECT ad.SCHOOL_DATE,'0' AS PERIOD_ID,
			ad.STATE_VALUE AS STATE_CODE," .
			db_case( [ 'ad.STATE_VALUE', "'0.0'", "'A'", "'1.0'", "'P'", "'H'" ] ) . " AS SHORT_NAME
			FROM attendance_day ad
			WHERE ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
			AND ad.STUDENT_ID='" . UserStudentID() . "'", [], [ 'SCHOOL_DATE', 'PERIOD_ID' ] );
	}

	$i = 0;
	$col_period = false;

	$student_RET = [];

	foreach ( (array) $schedule_RET as $course )
	{
		$i++;

		$student_RET[ $i ]['TITLE'] = $course['COURSE_PERIOD'];

		if ( ! empty( $course['PERIOD'] ) )
		{
			$student_RET[ $i ]['PERIOD'] = $course['PERIOD'];
			$col_period = true;
		}

		foreach ( (array) $cal_RET as $value )
		{
			if ( empty( $attendance_RET[ $value['SCHOOL_DATE'] ][ $course['PERIOD_ID'] ] ) )
			{
				$student_RET[ $i ][ $value['SHORT_DATE'] ] = '';

				continue;
			}

			$student_RET[ $i ][ $value['SHORT_DATE'] ] = MakeAttendanceCode(
				$attendance_RET[ $value['SCHOOL_DATE'] ][ $course['PERIOD_ID'] ][1]['STATE_CODE'],
				( $_REQUEST['period_id'] ?
					$attendance_RET[ $value['SCHOOL_DATE'] ][ $course['PERIOD_ID'] ][1]['SHORT_NAME'] :
					'' ),
				( $_REQUEST['period_id'] ?
					$attendance_RET[ $value['SCHOOL_DATE'] ][ $course['PERIOD_ID'] ][1]['TITLE'] :
					'' )
			);
		}
	}

	$columns = [ 'TITLE' => _( 'Course' ) ];

	if ( $col_period )
	{
		$columns['PERIOD'] = _( 'Period' );
	}

	foreach ( (array) $cal_RET as $value )
	{
		$school_date = ProperDate( $value['SCHOOL_DATE'], 'short' );

		// Remove year to gain space.
		$school_date = str_replace( date( 'Y' ), '', $school_date );

		$school_date = strip_tags( $school_date );

		// Remove trailing slash "/" or dash "-" or dot ".".
		$school_date = trim( $school_date, '/-. ' );

		$columns[ $value['SHORT_DATE'] ] = isset( $_REQUEST['LO_save'] ) ?
			$school_date : '<span class="proper-date">' . $school_date . '</span>';
	}

	// Student view, list courses.
	ListOutput( $student_RET, $columns, 'Course', 'Courses' );
}
else
{
	// in pre-2.11 versions the attendance data would be queried for all students here but data for #students*#days can be a lot
	// in 2.11 this was switched to incremental query in the _makeColor function
	if ( empty( $_REQUEST['period_id'] )
		|| ! is_numeric( $_REQUEST['period_id'] ) )
	{
		// @since 9.2.1 SQL use REPLACE() instead of to_char() for MySQL compatibility
		$att_sql = "SELECT ad.STATE_VALUE AS STATE_CODE,
			SCHOOL_DATE,CONCAT('_', REPLACE(CAST(ad.SCHOOL_DATE AS char(10)),'-','')) AS SHORT_DATE
		FROM attendance_day ad,student_enrollment ssm
		WHERE ad.STUDENT_ID=ssm.STUDENT_ID
		AND (('" . DBDate() . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)
			AND '" . DBDate() . "'>=ssm.START_DATE)
		AND ssm.SCHOOL_ID='" . UserSchool() . "'
		AND ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		AND ad.STUDENT_ID=";
	}
	else
	{
		// @since 9.2.1 SQL use REPLACE() instead of to_char() for MySQL compatibility
		$att_sql = "SELECT ap.ATTENDANCE_CODE,ap.SCHOOL_DATE,CONCAT('_', REPLACE(CAST(ap.SCHOOL_DATE AS char(10)),'-','')) AS SHORT_DATE
		FROM attendance_period ap,student_enrollment ssm
		WHERE ap.STUDENT_ID=ssm.STUDENT_ID
		AND ap.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		AND ap.PERIOD_ID='" . (int) $_REQUEST['period_id'] . "'
		AND ap.STUDENT_ID=";
	}

	foreach ( (array) $cal_RET as $value )
	{
		$school_date_col = '_' . str_replace( '-', '', $value['SCHOOL_DATE'] );

		$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
		$extra['SELECT'] .= ",'' as " . $school_date_col;

		$proper_date = ProperDate( $value['SCHOOL_DATE'], 'short' );

		// Remove year to gain space.
		$proper_date = str_replace( date( 'Y' ), '', $proper_date );

		$proper_date = strip_tags( $proper_date );

		// Remove trailing slash "/" or dash "-" or dot ".".
		$proper_date = trim( $proper_date, '/-. ' );

		$extra['columns_after'][ $school_date_col ] = isset( $_REQUEST['LO_save'] ) ?
			$proper_date : '<span class="proper-date">' . $proper_date . '</span>';

		$extra['functions'][ $school_date_col ] = '_makeColor';
	}

	$extra['link']['FULL_NAME']['link'] = PreparePHP_SELF();

	$extra['link']['FULL_NAME']['variables'] = [ 'student_id' => 'STUDENT_ID' ];

	Widgets( 'course' );

	Widgets( 'absences' );

	$extra['new'] = true;

	$extra['action'] = '&report=' . $_REQUEST['report'];

	Search( 'student_id', $extra );

	if ( ! empty( $has_edit_form ) ) {

		echo '<br /><div class="center">' . SubmitButton() . '</div>';

		echo '</form>';
	}
}

function _makeColor( $value, $column )
{
	global $THIS_RET,
		$att_sql;

	static $att_RET = [],
		$attendance_codes_RET;

	if ( empty( $att_RET[ $THIS_RET['STUDENT_ID'] ] ) )
	{
		$att_RET[ $THIS_RET['STUDENT_ID'] ] = DBGet( $att_sql .
			"'" . $THIS_RET['STUDENT_ID'] . "'", [], [ 'SHORT_DATE' ] );
	}

	if ( empty( $att_RET[ $THIS_RET['STUDENT_ID'] ][ $column ][1] ) )
	{
		return '';
	}

	$att = $att_RET[ $THIS_RET['STUDENT_ID'] ][ $column ][1];

	if ( $_REQUEST['period_id'] )
	{
		if ( empty( $attendance_codes_RET ) )
		{
			$attendance_codes_RET = DBGet( "SELECT ID,DEFAULT_CODE,STATE_CODE,SHORT_NAME,TITLE
				FROM attendance_codes
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND TABLE_NAME='0'", [], [ 'ID' ] );
		}

		return MakeAttendanceCode(
			issetVal( $attendance_codes_RET[ $att['ATTENDANCE_CODE'] ][1]['STATE_CODE'] ),
			makeCodePulldown( $att['ATTENDANCE_CODE'], $THIS_RET['STUDENT_ID'], $column ),
			issetVal( $attendance_codes_RET[ $att['ATTENDANCE_CODE'] ][1]['TITLE'] )
		);
	}

	return MakeAttendanceCode( $att['STATE_CODE'] );
}


function makeCodePulldown( $value, $student_id, $date )
{
	static $attendance_code_options,
		$attendance_codes_RET;

	$date = mb_substr( $date, 1, 4 ) . '-' . mb_substr( $date, 5, 2 ) . '-' . mb_substr( $date, 7 );

	if ( empty( $attendance_codes_RET ) )
	{
		$attendance_codes_RET = DBGet( "SELECT ID,DEFAULT_CODE,STATE_CODE,SHORT_NAME
			FROM attendance_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND TABLE_NAME='0'", [], [ 'ID' ] );
	}

	if ( empty( $attendance_code_options ) )
	{
		foreach ( (array) $attendance_codes_RET as $id => $code )
		{
			$attendance_code_options[ $id ] = $code[1]['SHORT_NAME'];
		}
	}

	return SelectInput(
		$value,
		'attendance[' . $student_id . '][' . $date . '][ATTENDANCE_CODE]',
		'',
		$attendance_code_options
	);
}
