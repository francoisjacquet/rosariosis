<?php
//FJ move Attendance.php from functions/ to modules/Attendance/includes
require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';
require_once 'modules/Attendance/includes/AttendanceCodes.fnc.php';

$_REQUEST['table'] = issetVal( $_REQUEST['table'] );
$_REQUEST['expanded_view'] = issetVal( $_REQUEST['expanded_view'], '' );

DrawHeader( ProgramTitle() );

// Set date.
$date = RequestedDate( 'date', DBDate(), 'set' );

if ( ! empty( $_SESSION['Administration.php']['date'] )
	&& $_SESSION['Administration.php']['date'] !== $date )
{
	// Unset attendance & attendance day & redirect URL.
	RedirectURL( [ 'attendance', 'attendance_day' ] );
}

if ( $_REQUEST['table'] == '' )
{
	$_REQUEST['table'] = '0';
}

if ( $_REQUEST['table'] == '0' )
{
	$table = 'attendance_period';
	$extra_sql = '';
}
else
{
	$table = 'lunch_period';
	$extra_sql = " AND TABLE_NAME='" . (int) $_REQUEST['table'] . "'";
}

$_SESSION['Administration.php']['date'] = $date;
$current_mp = GetCurrentMP( 'QTR', $date, false );

if ( ! $current_mp )
{
	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="POST">';

	DrawHeader(
		PrepareDate( $date, '_date', false, [ 'submit' => true ] )
	);

	echo '</form>';

	ErrorMessage( [ _( 'The selected date is not in a school quarter.' ) ], 'fatal' );
}

$all_mp = GetAllMP( 'QTR', $current_mp );

$current_Q = "SELECT ATTENDANCE_TEACHER_CODE,ATTENDANCE_CODE,ATTENDANCE_REASON,COMMENT,
	STUDENT_ID,ADMIN,PERIOD_ID
	FROM " . DBEscapeIdentifier( $table ) .
	" WHERE SCHOOL_DATE='" . $date . "'" . $extra_sql;

if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
{
	// FJ days numbered.
	// FJ multiple school periods for a course period.
	$current_schedule_Q = "SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID
	FROM schedule s,course_periods cp,course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND s.STUDENT_ID='__student_id__'
	AND s.SYEAR='" . UserSyear() . "'
	AND s.SCHOOL_ID='" . UserSchool() . "'
	AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID
	AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0
	AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
	AND position(substring('MTWHFSU' FROM cast(
		(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
		FROM attendance_calendar
		WHERE SCHOOL_DATE<='" . $date . "'
		AND SCHOOL_DATE>=(SELECT START_DATE
			FROM school_marking_periods
			WHERE START_DATE<='" . $date . "'
			AND END_DATE>='" . $date . "'
			AND MP='QTR'
			AND SCHOOL_ID=s.SCHOOL_ID
			AND SYEAR=s.SYEAR)
		AND CALENDAR_ID=cp.CALENDAR_ID)
	" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
	" FOR 1) IN cpsp.DAYS)>0
	AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
	ORDER BY s.START_DATE ASC";
}
else
{
	// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
	$current_schedule_Q = "SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID
	FROM schedule s,course_periods cp, course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND s.STUDENT_ID='__student_id__'
	AND s.SYEAR='" . UserSyear() . "'
	AND s.SCHOOL_ID='" . UserSchool() . "'
	AND cp.COURSE_PERIOD_ID = s.COURSE_PERIOD_ID
	AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0
	AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
	AND position(substring('UMTWHFS' FROM " .
	( $DatabaseType === 'mysql' ?
		"DAYOFWEEK(cast('" . $date . "' AS DATE))" :
		"cast(extract(DOW FROM cast('" . $date . "' AS DATE))+1 AS int)" ) .
	" FOR 1) IN cpsp.DAYS)>0
	AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
	ORDER BY s.START_DATE ASC";
}

// TODO: can be optimized? Remove PERIOD_ID index.
$current_RET = DBGet( $current_Q, [], [ 'STUDENT_ID', 'PERIOD_ID' ] );

if ( ! empty( $_REQUEST['attendance'] ) // Fix GET form: do not check $_POST.
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['attendance'] as $student_id => $values )
	{
		if ( empty( $current_schedule_RET[$student_id] ) )
		{
			$current_schedule_RET[$student_id] = DBGet( str_replace( '__student_id__', $student_id, $current_schedule_Q ), [], [ 'PERIOD_ID' ] );

			if ( empty( $current_schedule_RET[$student_id] ) )
			{
				$current_schedule_RET[$student_id] = true;
			}
		}

		foreach ( (array) $values as $period_id => $columns )
		{
			$course_period_id = $current_schedule_RET[$student_id][$period_id][1]['COURSE_PERIOD_ID'];

			if ( ! empty( $current_RET[$student_id][$period_id] ) )
			{
				DBUpdate(
					$table,
					[
						'ADMIN' => 'Y',
						'COURSE_PERIOD_ID' => (int) $course_period_id,
					] + $columns,
					[
						'STUDENT_ID' => (int) $student_id,
						'SCHOOL_DATE' => $date,
						'PERIOD_ID' => (int) $period_id,
					]
				);
			}
			else
			{
				$insert_columns = [
					'STUDENT_ID' => (int) $student_id,
					'SCHOOL_DATE' => $date,
					'PERIOD_ID' => (int) $period_id,
					'MARKING_PERIOD_ID' => (int) $current_mp,
					'ADMIN' => 'Y',
					'COURSE_PERIOD_ID' => (int) $course_period_id,
				];

				DBInsert(
					$table,
					$insert_columns + $columns
				);
			}
		}

		UpdateAttendanceDaily(
			$student_id,
			$date,
			issetVal( $_REQUEST['attendance_day'][$student_id]['COMMENT'], false )
		);

		unset( $_REQUEST['attendance_day'][$student_id] );
	}

	// TODO: can be optimized? Remove PERIOD_ID index.
	$current_RET = DBGet( $current_Q, [], [ 'STUDENT_ID', 'PERIOD_ID' ] );

	// Unset attendance & redirect URL.
	RedirectURL( 'attendance' );
}

if ( ! empty( $_REQUEST['attendance_day'] ) )
{
	foreach ( (array) $_REQUEST['attendance_day'] as $student_id => $comment )
	{
		UpdateAttendanceDaily(
			$student_id,
			$date,
			$comment['COMMENT']
		);
	}

	// Unset attendance day & redirect URL.
	RedirectURL( 'attendance_day' );
}

$codes_RET = DBGet( "SELECT ID,SHORT_NAME,TITLE,STATE_CODE
	FROM attendance_codes
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	AND TABLE_NAME='" . (int) $_REQUEST['table'] . "'" );

$periods_RET = DBGet( "SELECT sp.PERIOD_ID,COALESCE(sp.SHORT_NAME,sp.TITLE) AS SHORT_NAME,sp.TITLE
	FROM school_periods sp
	WHERE sp.SCHOOL_ID='" . UserSchool() . "'
	AND sp.SYEAR='" . UserSyear() . "'
	AND EXISTS (SELECT '' FROM course_periods cp,course_period_school_periods cpsp
		WHERE cpsp.PERIOD_ID=sp.PERIOD_ID
		AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.SYEAR='" . UserSyear() . "'
		AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0)
	ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER,sp.TITLE" );

$categories_RET = DBGet( "SELECT ID,TITLE
	FROM attendance_code_categories
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'" );

$headerl = '';

if ( ! empty( $categories_RET ) )
{
	$tmp_PHP_SELF = PreparePHP_SELF( $_REQUEST );

	$headerl .= '<a href="' . $tmp_PHP_SELF . '&table=0">';

	$headerl .= $_REQUEST['table'] == '0' ? '<b>' . _( 'Attendance' ) . '</b>' : _( 'Attendance' );

	$headerl .= '</a>';

	foreach ( (array) $categories_RET as $category )
	{
		$headerl .= ' | <a href="' . $tmp_PHP_SELF . '&table=' . $category['ID'] . '">';

		$headerl .= $_REQUEST['table'] == $category['ID'] ? '<b>' . $category['TITLE'] . '</b>' : $category['TITLE'];

		$headerl .= '</a>';
	}
}

if ( isset( $_REQUEST['student_id'] ) && $_REQUEST['student_id'] !== 'new' )
{
	if ( UserStudentID() != $_REQUEST['student_id'] )
	{
		SetUserStudentID( $_REQUEST['student_id'] );
	}

	$functions = [
		'ATTENDANCE_CODE' => '_makeCodePulldown',
		'ATTENDANCE_TEACHER_CODE' => '_makeCode',
		'ATTENDANCE_REASON' => '_makeReasonInput',
		'COMMENT' => '_makeReason',
	];

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		// FJ days numbered.
		// FJ multiple school periods for a course period.
		$schedule_RET = DBGet( "SELECT s.STUDENT_ID,c.TITLE AS COURSE,cpsp.PERIOD_ID,
			cp.COURSE_PERIOD_ID,p.TITLE AS PERIOD_TITLE,s.STUDENT_ID AS ATTENDANCE_CODE,
			s.STUDENT_ID AS ATTENDANCE_TEACHER_CODE,s.STUDENT_ID AS ATTENDANCE_REASON,
			s.STUDENT_ID AS COMMENT
		FROM schedule s,courses c,course_periods cp,school_periods p,attendance_calendar ac, course_period_school_periods cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND s.SYEAR='" . UserSyear() . "'
		AND s.SCHOOL_ID='" . UserSchool() . "'
		AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
		AND s.COURSE_ID=c.COURSE_ID
		AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND cpsp.PERIOD_ID=p.PERIOD_ID
		AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0
		AND s.STUDENT_ID='" . (int) $_REQUEST['student_id'] . "'
		AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
		AND position(substring('MTWHFSU' FROM cast(
			(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE SCHOOL_DATE<=ac.SCHOOL_DATE
			AND SCHOOL_DATE>=(SELECT START_DATE
				FROM school_marking_periods
				WHERE START_DATE<=ac.SCHOOL_DATE
				AND END_DATE>=ac.SCHOOL_DATE
				AND MP='QTR'
				AND SCHOOL_ID=s.SCHOOL_ID
				AND SYEAR=s.SYEAR)
			AND CALENDAR_ID=cp.CALENDAR_ID)
		" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
		" FOR 1) IN cpsp.DAYS)>0
		AND ac.CALENDAR_ID=cp.CALENDAR_ID
		AND ac.SCHOOL_DATE='" . $date . "'
		AND ac.MINUTES!='0'
		ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER", $functions );
	}
	else
	{
		// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
		$schedule_RET = DBGet( "SELECT
		s.STUDENT_ID,c.TITLE AS COURSE,cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,p.TITLE AS PERIOD_TITLE,
		s.STUDENT_ID AS ATTENDANCE_CODE,s.STUDENT_ID AS ATTENDANCE_TEACHER_CODE,s.STUDENT_ID AS ATTENDANCE_REASON,s.STUDENT_ID AS COMMENT
		FROM schedule s,courses c,course_periods cp,school_periods p,attendance_calendar ac, course_period_school_periods cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
		s.SYEAR='" . UserSyear() . "' AND s.SCHOOL_ID='" . UserSchool() . "'
		AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
		AND s.COURSE_ID=c.COURSE_ID
		AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=p.PERIOD_ID AND position(',$_REQUEST[table],' IN cp.DOES_ATTENDANCE)>0
		AND s.STUDENT_ID='" . (int) $_REQUEST['student_id'] . "'
		AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
		AND position(substring('UMTWHFS' FROM " .
		( $DatabaseType === 'mysql' ?
			"DAYOFWEEK(cast('" . $date . "' AS DATE))" :
			"cast(extract(DOW FROM cast('" . $date . "' AS DATE))+1 AS int)" ) .
		" FOR 1) IN cpsp.DAYS)>0
		AND ac.CALENDAR_ID=cp.CALENDAR_ID AND ac.SCHOOL_DATE='" . $date . "' AND ac.MINUTES!='0'
		ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER", $functions );
	}

	$columns = [
		'PERIOD_TITLE' => _( 'Period' ),
		'COURSE' => _( 'Course' ),
		'ATTENDANCE_CODE' => _( 'Attendance Code' ),
		'ATTENDANCE_TEACHER_CODE' => _( 'Teacher\'s Entry' ),
		'ATTENDANCE_REASON' => _( 'Office Comment' ),
		'COMMENT' => _( 'Teacher Comment' ),
	];

	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="POST">';

	DrawHeader(
		PrepareDate( $date, '_date', false, [ 'submit' => true ] ),
		SubmitButton( _( 'Update' ) )
	);

	$headerr = '<a href="' . PreparePHP_Self( $_REQUEST, [ 'student_id' ] ) . '">' .
	_( 'Student List' ) . '</a>';

	DrawHeader( $headerl, $headerr );

	ListOutput( $schedule_RET, $columns, 'Course', 'Courses' );

	echo '</form>';
}
else
{
	$extra['WHERE'] = " AND EXISTS (SELECT '' FROM " . DBEscapeIdentifier( $table ) . " ap,attendance_codes ac
		WHERE ap.SCHOOL_DATE='" . $date . "'
		AND ap.STUDENT_ID=ssm.STUDENT_ID
		AND ap.ATTENDANCE_CODE=ac.ID
		AND ac.SCHOOL_ID=ssm.SCHOOL_ID
		AND ac.SYEAR=ssm.SYEAR " . str_replace( 'TABLE_NAME', 'ac.TABLE_NAME', $extra_sql );

	$extra2['WHERE'] = $_REQUEST['expanded_view'] != 'true' ? $extra['WHERE'] : '';

	$abs = false;

	if ( ! empty( $_REQUEST['codes'] ) )
	{
		$REQ_codes = $_REQUEST['codes'];

		foreach ( (array) $REQ_codes as $key => $value )
		{
			if ( ! $value )
			{
				unset( $REQ_codes[$key] );
			}
			elseif ( $value == 'A' )
			{
				$abs = true;
			}
		}
	}
	else
	{
		$abs = ( $_REQUEST['table'] == '0' ); //true;
	}

	if ( ! empty( $REQ_codes ) && ! $abs )
	{
		$extra['WHERE'] .= " AND ac.ID IN (";

		foreach ( (array) $REQ_codes as $code )
		{
			$extra['WHERE'] .= "'" . (int) $code . "',";
		}

		if ( $_REQUEST['expanded_view'] != 'true' )
		{
			$extra2['WHERE'] = $extra['WHERE'] = mb_substr( $extra['WHERE'], 0, -1 ) . ')';
		}
		else
		{
			$extra['WHERE'] = mb_substr( $extra['WHERE'], 0, -1 ) . ')';
		}
	}
	elseif ( $abs )
	{
		$RET = DBGet( "SELECT ID
			FROM attendance_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL)
			AND TABLE_NAME='" . (int) $_REQUEST['table'] . "'" );

		if ( ! empty( $RET ) )
		{
			$extra['WHERE'] .= " AND ac.ID IN (";

			foreach ( (array) $RET as $code )
			{
				$extra['WHERE'] .= "'" . (int) $code['ID'] . "',";
			}

			if ( $_REQUEST['expanded_view'] != 'true' )
			{
				$extra2['WHERE'] = $extra['WHERE'] = mb_substr( $extra['WHERE'], 0, -1 ) . ')';
			}
			else
			{
				$extra['WHERE'] = mb_substr( $extra['WHERE'], 0, -1 ) . ')';
			}
		}
	}

	$extra['WHERE'] .= ')';

	// EXPANDED VIEW BREAKS THIS QUERY.  PLUS, PHONE IS ALREADY AN OPTION IN EXPANDED VIEW

	if ( $_REQUEST['expanded_view'] != 'true' && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$extra2['WHERE'] .= ')';

		$extra2['SELECT_ONLY'] = 'ssm.STUDENT_ID,p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME,p.MIDDLE_NAME,
		sjp.STUDENT_RELATION,pjc.TITLE,pjc.VALUE,a.PHONE,sjp.ADDRESS_ID ';

		$extra2['FROM'] = ',address a,students_join_address sja LEFT OUTER JOIN students_join_people sjp ON (sja.STUDENT_ID=sjp.STUDENT_ID AND sja.ADDRESS_ID=sjp.ADDRESS_ID AND (sjp.CUSTODY=\'Y\' OR sjp.EMERGENCY=\'Y\')) LEFT OUTER JOIN people p ON (p.PERSON_ID=sjp.PERSON_ID) LEFT OUTER JOIN people_join_contacts pjc ON (pjc.PERSON_ID=p.PERSON_ID) ';

		$extra2['WHERE'] .= ' AND a.ADDRESS_ID=sja.ADDRESS_ID AND sja.STUDENT_ID=ssm.STUDENT_ID ';

		$extra2['ORDER_BY'] = 'COALESCE(sjp.CUSTODY,\'N\') DESC';

		$extra2['group'] = [ 'STUDENT_ID', 'PERSON_ID' ];

		$contacts_RET = GetStuList( $extra2 );
		$extra['columns_before']['PHONE'] = button( 'down_phone' );
	}

	$columns = [];

	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

	$extra['SELECT'] .= ',s.STUDENT_ID AS PHONE';

	$extra['functions']['PHONE'] = 'makeContactInfo';

	if ( $_REQUEST['table'] == '0' )
	{
		$extra['SELECT'] .= ",(SELECT STATE_VALUE FROM attendance_day WHERE STUDENT_ID=ssm.STUDENT_ID AND SCHOOL_DATE='" . $date . "') AS STATE_VALUE";
		$extra['SELECT'] .= ",(SELECT COMMENT FROM attendance_day WHERE STUDENT_ID=ssm.STUDENT_ID AND SCHOOL_DATE='" . $date . "') AS DAILY_COMMENT";
		$extra['functions']['STATE_VALUE'] = '_makeStateValue';
		$extra['functions']['DAILY_COMMENT'] = '_makeStateValue';

		$extra['columns_after']['STATE_VALUE'] = _( 'Present' );
		$extra['columns_after']['DAILY_COMMENT'] = _( 'Day Comment' );
	}

	// $extra['link']['FULL_NAME']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&month_date='.$_REQUEST['month_date'].'&day_date='.$_REQUEST['day_date'].'&year_date='.$_REQUEST['year_date'].'&table='.$_REQUEST['table'];
	$extra['link']['FULL_NAME']['link'] = PreparePHP_SELF( $_REQUEST );
	$extra['link']['FULL_NAME']['variables'] = [ 'student_id' => 'STUDENT_ID' ];
	$extra['BackPrompt'] = false;
	$extra['Redirect'] = false;
	$extra['new'] = true;

	$extra3 = $extra;

	$students_RET = GetStuList( $extra3 );

	$student_ids = [];

	foreach ( $students_RET as $student )
	{
		$student_ids[] = $student['STUDENT_ID'];
	}

	if ( $student_ids )
	{
		$current_schedules_RET = DBGet( str_replace(
			"='__student_id__'",
			" IN('" . implode( "','", $student_ids ) . "')",
			$current_schedule_Q
		), [], [ 'PERIOD_ID' ] );
	}

	foreach ( (array) $periods_RET as $period )
	{
		if ( empty( $current_schedules_RET[ $period['PERIOD_ID'] ] ) )
		{
			// @since 11.0 Skip School Period column if has no students scheduled for selected date
			continue;
		}

		$extra['SELECT'] .= ",s.STUDENT_ID AS PERIOD_" . $period['PERIOD_ID'];
		$extra['functions']['PERIOD_' . $period['PERIOD_ID']] = '_makeCodePulldown';
		$extra['columns_after']['PERIOD_' . $period['PERIOD_ID']] = $period['SHORT_NAME'];
	}

	if ( ! empty( $REQ_codes ) )
	{
		$code_pulldowns = '';

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

	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="POST">';

	DrawHeader(
		PrepareDate( $date, '_date', false, [ 'submit' => true ] ),
		SubmitButton( _( 'Update' ) )
	);

	$current_student_link = '';

	if ( UserStudentID() )
	{
		$current_student_link = ' <a href="' .
		PreparePHP_Self( $_REQUEST, [], [ 'student_id' => UserStudentID() ] ) . '">' .
		_( 'Current Student' ) . '</a></td><td>';
	}

	if ( $headerl )
	{
		// Header already has Attendance categories: add spacing.
		$headerl .= ' &mdash; ';
	}

	$headerl .= AttendanceCodesTipMessage( '', $_REQUEST['table'] );

	$headerr = '<table style="float: right;"><tr><td class="align-right">' .
	button(
		'add',
		'',
		'"#" onclick="' . AttrEscape( 'addHTML(' . json_encode( _makeCodeSearch() ) .
			',\'code_pulldowns\'); return false;' ) . '"'
	) . '</td><td><div id="code_pulldowns">' . $code_pulldowns . '</div></td>' .
		'<td class="align-right">' . $current_student_link . '</td></tr></table>';

	DrawHeader( $headerl, $headerr );

	$_REQUEST['search_modfunc'] = 'list';

	Search( 'student_id', $extra );

	echo '<br /><div class="center">' . SubmitButton( _( 'Update' ) ) . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $title
 */
function _makeCodePulldown( $value, $title )
{
	global $THIS_RET,
	$codes_RET,
	$current_RET,
	$current_schedule_RET,
	$current_schedule_Q;

	if ( empty( $current_schedule_RET[$value] ) )
	{
		$current_schedule_RET[$value] = DBGet( str_replace( '__student_id__', $value, $current_schedule_Q ), [], [ 'PERIOD_ID' ] );

		if ( empty( $current_schedule_RET[$value] ) )
		{
			$current_schedule_RET[$value] = true;
		}
	}

	if ( ! empty( $THIS_RET['COURSE'] ) )
	{
		$period_id = $THIS_RET['PERIOD_ID'];
		$code_title = 'TITLE';
	}
	else
	{
		$period_id = mb_substr( $title, 7 );
		$code_title = 'SHORT_NAME';
	}

	if ( ! empty( $current_schedule_RET[$value][$period_id] ) )
	{
		$val = isset( $current_RET[$value][$period_id][1]['ATTENDANCE_CODE'] ) ?
			$current_RET[$value][$period_id][1]['ATTENDANCE_CODE'] : null;

		foreach ( (array) $codes_RET as $code )
		{
			$options[$code['ID']] = $code[$code_title];

			if ( $val === $code['ID'] )
			{
				$current_code_title = $code['TITLE'];

				$current_state_code = $code['STATE_CODE'];
			}
		}

		return MakeAttendanceCode(
			issetVal( $current_state_code ),
			SelectInput( $val, 'attendance[' . $value . '][' . $period_id . '][ATTENDANCE_CODE]', '', $options ),
			issetVal( $current_code_title )
		);
	}

	return false;
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
		if ( isset( $current_RET[$value][$THIS_RET['PERIOD_ID']][1]['ATTENDANCE_TEACHER_CODE'] )
			&& $current_RET[$value][$THIS_RET['PERIOD_ID']][1]['ATTENDANCE_TEACHER_CODE'] == $code['ID'] )
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

	$val = isset( $current_RET[$value][$THIS_RET['PERIOD_ID']][1]['ATTENDANCE_REASON'] ) ?
		$current_RET[$value][$THIS_RET['PERIOD_ID']][1]['ATTENDANCE_REASON'] : '';

	return TextInput(
		$val,
		'attendance[' . $value . '][' . $THIS_RET['PERIOD_ID'] . '][ATTENDANCE_REASON]',
		''
	);
}

/**
 * @param $value
 * @param $title
 * @return mixed
 */
function _makeReason( $value, $title )
{
	global $THIS_RET, $current_RET;

	return issetVal( $current_RET[$value][$THIS_RET['PERIOD_ID']][1]['COMMENT'], '' );
}

/**
 * @param $value
 * @return mixed
 */
function _makeCodeSearch( $value = '' )
{
	global $codes_RET, $code_search_selected;

	// Fix bug when selected Attendance code is "All": set value to 0.
	$return = '<select name="codes[]"><option value="0">' . _( 'All' ) . '</option>';

	if ( $_REQUEST['table'] == '0' )
	{
		$return .= '<option value="A"' . (  ( $value == 'A' ) ? ' selected' : '' ) . '>' . _( 'Not Present' ) . '</option>';
	}

	if ( ! empty( $codes_RET ) )
	{
		foreach ( (array) $codes_RET as $code )
		{
			$return .= '<option value="' . AttrEscape( $code['ID'] ) . '"' . ( $value == $code['ID'] ? ' selected' : '' ) . '>' . $code['TITLE'] . '</option>';
		}
	}

	$return .= '</select>';

	return $return;
}

/**
 * Make Present State value or Day Comment input.
 *
 * @since 5.0 Add color codes for Present State values.
 *
 * @param string $value Value.
 * @param string $name  Column name: 'STATE_VALUE' or 'DAY_COMMENT'.
 *
 * @return string Present State value.
 */
function _makeStateValue( $value, $name )
{
	global $THIS_RET;

	if ( $name == 'STATE_VALUE' )
	{
		return MakeAttendanceCode( $value );
	}
	else
	{
		return TextInput( $value, 'attendance_day[' . $THIS_RET['STUDENT_ID'] . '][COMMENT]' );
	}
}
