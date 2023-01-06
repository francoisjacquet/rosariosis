<?php
//FJ move Attendance.php from functions/ to modules/Attendance/includes
require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';

if ( ! empty( $_REQUEST['month_date'] )
	&& ! empty( $_REQUEST['day_date'] )
	&& ! empty( $_REQUEST['year_date'] ) )
{
	$date = $_REQUEST['year_date'] . '-' . $_REQUEST['month_date'] . '-' . $_REQUEST['day_date'];
}
else
{
	$date = DBDate();
}

$current_RET = DBGet( "SELECT ATTENDANCE_TEACHER_CODE,ATTENDANCE_CODE,ATTENDANCE_REASON,STUDENT_ID,ADMIN,COURSE_PERIOD_ID FROM attendance_period WHERE SCHOOL_DATE='" . $date . "'", [], [ 'STUDENT_ID', 'COURSE_PERIOD_ID' ] );

if ( $_REQUEST['attendance'] && $_POST['attendance'] && AllowEdit() )
{
	foreach ( (array) $_REQUEST['attendance'] as $student_id => $values )
	{
		foreach ( (array) $values as $period => $columns )
		{
			if ( $current_RET[$student_id][$period] )
			{
				$sql = "UPDATE attendance_period SET ADMIN='Y',";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE SCHOOL_DATE='" . $date . "' AND COURSE_PERIOD_ID='" . (int) $period . "' AND STUDENT_ID='" . (int) $student_id . "'";
				DBQuery( $sql );
			}
			else
			{
				$period_id = DBGetOne( "SELECT PERIOD_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . (int) $period . "'" );

				$sql = "INSERT INTO attendance_period ";

				$fields = 'STUDENT_ID,SCHOOL_DATE,PERIOD_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID,ADMIN,';
				$values = "'" . $student_id . "','" . $date . "','" . $period_id . "','" . GetCurrentMP( 'QTR', $date ) . "','" . $period . "','Y',";

				$go = 0;

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';
						$values .= "'" . $value . "',";
						$go = true;
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				if ( $go )
				{
					DBQuery( $sql );
				}
			}
		}

		UpdateAttendanceDaily( $student_id, $date );
	}

	$current_RET = DBGet( "SELECT ATTENDANCE_TEACHER_CODE,ATTENDANCE_CODE,ATTENDANCE_REASON,STUDENT_ID,ADMIN,COURSE_PERIOD_ID FROM attendance_period WHERE SCHOOL_DATE='" . $date . "'", [], [ 'STUDENT_ID', 'COURSE_PERIOD_ID' ] );

	// Unset attendance & redirect URL.
	RedirectURL( 'attendance' );
}

$codes_RET = DBGet( "SELECT ID,SHORT_NAME,TITLE FROM attendance_codes WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "'" );
$periods_RET = DBGet( "SELECT PERIOD_ID,SHORT_NAME,TITLE FROM school_periods WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

if ( isset( $_REQUEST['student_id'] ) && $_REQUEST['student_id'] !== 'new' )
{
	if ( UserStudentID() != $_REQUEST['student_id'] )
	{
		SetUserStudentID( $_REQUEST['student_id'] );
	}

	$functions = [ 'ATTENDANCE_CODE' => '_makeCodePulldown', 'ATTENDANCE_TEACHER_CODE' => '_makeCode', 'ATTENDANCE_REASON' => '_makeReasonInput' ];

	$schedule_RET = DBGet( "SELECT s.STUDENT_ID,c.TITLE AS COURSE,cp.PERIOD_ID,cp.COURSE_PERIOD_ID,
	p.TITLE AS PERIOD_TITLE,'' AS ATTENDANCE_CODE,'' AS ATTENDANCE_TEACHER_CODE,'' AS ATTENDANCE_REASON
	FROM schedule s,courses c,course_periods cp,school_periods p
	WHERE s.SYEAR='" . UserSyear() . "'
	AND s.SCHOOL_ID='" . UserSchool() . "'
	AND s.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', $date ) ) . ")
	AND s.COURSE_ID=c.COURSE_ID
	AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND cp.PERIOD_ID=p.PERIOD_ID
	AND cp.DOES_ATTENDANCE='Y'
	AND s.STUDENT_ID='" . (int) $_REQUEST['student_id'] . "'
	AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
	ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER", $functions );

	$columns = [ 'PERIOD_TITLE' => _( 'Period' ), 'COURSE' => _( 'Course' ), 'ATTENDANCE_CODE' => _( 'Attendance Code' ), 'ATTENDANCE_TEACHER_CODE' => _( 'Teacher\'s Entry' ), 'ATTENDANCE_REASON' => _( 'Comments' ) ];
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=student&student_id=' . $_REQUEST['student_id']  ) . '" method="POST">';
	DrawHeader( ProgramTitle(), '<input type="submit" value="' . AttrEscape( _( 'Update' ) ) . '" />' );
	DrawHeader( PrepareDate( $date, '_date' ) );
	ListOutput( $schedule_RET, $columns, _( 'Course' ), _( 'Courses' ) );
	echo '</form>';
}
else
{
	$extra['WHERE'] = " AND EXISTS (SELECT '' FROM attendance_period ap,attendance_codes ac WHERE ap.SCHOOL_DATE='" . $date . "' AND ap.STUDENT_ID=ssm.STUDENT_ID AND ap.ATTENDANCE_CODE=ac.ID AND ac.SCHOOL_ID=ssm.SCHOOL_ID AND ac.SYEAR=ssm.SYEAR ";

	if ( isset( $_REQUEST['codes'] )
		&& ! empty( $_REQUEST['codes'] ) )
	{
		$REQ_codes = $_REQUEST['codes'];

		foreach ( (array) $REQ_codes as $key => $value )
		{
			if ( ! $value )
			{
				unset( $REQ_codes[$key] );
			}
			elseif ( $value === 'A' )
			{
				$abs = true;
			}
		}
	}
	else
	{
		$abs = true;
	}

	if ( ! empty( $REQ_codes ) && ! $abs )
	{
		$extra['WHERE'] .= " AND ac.ID IN (";

		foreach ( (array) $REQ_codes as $code )
		{
			$extra['WHERE'] .= "'" . $code . "',";
		}

		$extra2['WHERE'] = $extra['WHERE'] = mb_substr( $extra['WHERE'], 0, -1 ) . ')';
	}
	elseif ( $abs )
	{
		$RET = DBGet( "SELECT ID FROM attendance_codes WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL)" );

		if ( ! empty( $RET ) )
		{
			$extra['WHERE'] .= " AND ac.ID IN (";

			foreach ( (array) $RET as $code )
			{
				$extra['WHERE'] .= "'" . $code['ID'] . "',";
			}

			$extra2['WHERE'] = $extra['WHERE'] = mb_substr( $extra['WHERE'], 0, -1 ) . ')';
		}
	}

	$extra['WHERE'] .= ')';
	$extra2['WHERE'] .= ')';

	$extra2['SELECT'] .= ',p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME,p.MIDDLE_NAME,
	sjp.STUDENT_RELATION,pjc.TITLE,pjc.VALUE,a.PHONE,sjp.ADDRESS_ID ';
	$extra2['FROM'] .= ',address a,people p,people_join_contacts pjc,students_join_people sjp,students_join_address sja ';
	$extra2['WHERE'] .= ' AND sja.STUDENT_ID=ssm.STUDENT_ID AND sjp.STUDENT_ID=sja.STUDENT_ID AND pjc.PERSON_ID=sjp.PERSON_ID AND p.PERSON_ID=sjp.PERSON_ID AND sjp.ADDRESS_ID=a.ADDRESS_ID AND (sjp.CUSTODY=\'Y\' OR sjp.EMERGENCY=\'Y\') ';
	$extra2['group'] = [ 'STUDENT_ID', 'PERSON_ID' ];
	$contacts_RET = GetStuList( $extra2 );

	$columns = [];
	$extra['SELECT'] .= ',NULL AS STATE_VALUE,NULL AS PHONE';
	$extra['functions']['PHONE'] = 'makeContactInfo';
	$extra['functions']['STATE_VALUE'] = '_makeStateValue';
	$extra['columns_before']['PHONE'] = 'Contact';
	$extra['columns_after']['STATE_VALUE'] = 'Present';
	$extra['BackPrompt'] = false;
	$extra['Redirect'] = false;
	$extra['new'] = true;

	foreach ( (array) $periods_RET as $period )
	{
		$extra['SELECT'] .= ",'' AS PERIOD_" . $period['PERIOD_ID'];
		$extra['functions']['PERIOD_' . $period['PERIOD_ID']] = '_makeCodePulldown';
		$extra['columns_after']['PERIOD_' . $period['PERIOD_ID']] = $period['SHORT_NAME'];
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';
	DrawHeader( ProgramTitle(), '<input type="submit" value="' . AttrEscape( _( 'Update' ) ) . '" />' );

	if ( $REQ_codes )
	{
		foreach ( (array) $REQ_codes as $code )
		{
			$code_pulldowns .= _makeCodeSearch( $code );
		}
	}
	elseif ( $abs )
	{
		$code_pulldowns = _makeCodeSearch( 'A' );
	}
	else
	{
		$code_pulldowns = _makeCodeSearch();
	}

	if ( UserStudentID() )
	{
		$current_student_link = '<a href="' .
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=student&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'] . '&year_date=' . $_REQUEST['year_date'] . '&student_id=' . UserStudentID() ) .
			'">' . _( 'Current Student' ) . '</a></td><td>';
	}

	DrawHeader(
		PrepareDate( $date, '_date' ),
		'<table><tr><td>' . $current_student_link .
		button(
			'add',
			'',
			'"#" onclick="' . AttrEscape( 'addHTML(' . json_encode( _makeCodeSearch() ) . ',\'code_pulldowns\'); return false;' ) . '"'
		) . '</td><td><div id="code_pulldowns">' . $code_pulldowns . '</div></td></tr></table>' );

	$_REQUEST['search_modfunc'] = 'list';
	Search( 'student_id', $extra );

	echo '</form>';
}

/**
 * @param $value
 * @param $title
 */
function _makeCodePulldown( $value, $title )
{
	global $THIS_RET, $codes_RET, $current_RET, $current_schedule_RET, $date;

	if ( ! isset( $current_schedule_RET[$THIS_RET['STUDENT_ID']] ) || ! is_array( $current_schedule_RET[$THIS_RET['STUDENT_ID']] ) )
	{
		$current_schedule_RET[$THIS_RET['STUDENT_ID']] = DBGet( "SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID
		FROM schedule s,course_periods cp
		WHERE s.STUDENT_ID='" . (int) $THIS_RET['STUDENT_ID'] . "'
		AND s.SYEAR='" . UserSyear() . "'
		AND s.SCHOOL_ID='" . UserSchool() . "'
		AND cp.COURSE_PERIOD_ID = s.COURSE_PERIOD_ID
		AND cp.DOES_ATTENDANCE='Y'
		AND s.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', $date ) ) . ")
		AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)", [], [ 'PERIOD_ID' ] );

		if ( ! $current_schedule_RET[$THIS_RET['STUDENT_ID']] )
		{
			$current_schedule_RET[$THIS_RET['STUDENT_ID']] = [];
		}
	}

	if ( ! empty( $THIS_RET['COURSE'] ) )
	{
		$period = $THIS_RET['COURSE_PERIOD_ID'];
		$period_id = $THIS_RET['PERIOD_ID'];

		foreach ( (array) $codes_RET as $code )
		{
			$options[$code['ID']] = $code['TITLE'];
		}
	}
	else
	{
		$period_id = mb_substr( $title, 7 );
		$period = $current_schedule_RET[$THIS_RET['STUDENT_ID']][$period_id][1]['COURSE_PERIOD_ID'];

		foreach ( (array) $codes_RET as $code )
		{
			$options[$code['ID']] = $code['SHORT_NAME'];
		}
	}

	$val = $current_RET[$THIS_RET['STUDENT_ID']][$period][1]['ATTENDANCE_CODE'];

	if ( $current_schedule_RET[$THIS_RET['STUDENT_ID']][$period_id] )
	{
		return SelectInput( $val, 'attendance[' . $THIS_RET['STUDENT_ID'] . '][' . $period . '][ATTENDANCE_CODE]', '', $options );
	}
	else
	{
		return false;
	}
}

/**
 * @param $value
 * @param $title
 * @return mixed
 */
function _makeCode( $value, $title )
{
	global $THIS_RET, $codes_RET, $current_RET;

	foreach ( (array) $codes_RET as $code )
	{
		if ( $current_RET[$THIS_RET['STUDENT_ID']][$THIS_RET['COURSE_PERIOD_ID']][1]['ATTENDANCE_TEACHER_CODE'] == $code['ID'] )
		{
			return $code['TITLE'];
		}
	}
}

/**
 * @param $value
 * @param $title
 */
function _makeReasonInput( $value, $title )
{
	global $THIS_RET, $codes_RET, $current_RET;

	$val = $current_RET[$THIS_RET['STUDENT_ID']][$THIS_RET['COURSE_PERIOD_ID']][1]['ATTENDANCE_REASON'];

	return TextInput( $val, 'attendance[' . $THIS_RET['STUDENT_ID'] . '][' . $THIS_RET['COURSE_PERIOD_ID'] . '][ATTENDANCE_REASON]', '', $options );
}

/**
 * @param $value
 * @return mixed
 */
function _makeCodeSearch( $value = '' )
{
	global $codes_RET, $code_search_selected;

	$return = '<select name=codes[]><option value="">All</option><option value="A"' . (  ( $value == 'A' ) ? ' selected' : '' ) . '>NP</option>';

	if ( ! empty( $codes_RET ) )
	{
		foreach ( (array) $codes_RET as $code )
		{
			if ( $value == $code['ID'] )
			{
				$return .= '<option value="' . AttrEscape( $code['ID'] ) . '" selected>' . $code['SHORT_NAME'] . '</option>';
			}
			else
			{
				$return .= '<option value="' . AttrEscape( $code['ID'] ) . '">' . $code['SHORT_NAME'] . '</option>';
			}
		}
	}

	$return .= '</select>';

	return $return;
}

/**
 * @param $value
 * @param $name
 */
function _makeStateValue( $value, $name )
{
	global $THIS_RET, $date;

	$value = DBGetOne( "SELECT STATE_VALUE
		FROM attendance_day
		WHERE STUDENT_ID='" . (int) $THIS_RET['STUDENT_ID'] . "'
		AND SCHOOL_DATE='" . $date . "'" );

	if ( $value == '0.0' )
	{
		return 'None';
	}
	elseif ( $value == '.5' )
	{
		return 'Half-Day';
	}
	else
	{
		return 'Full-Day';
	}
}
