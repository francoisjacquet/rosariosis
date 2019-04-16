<?php
//FJ move Attendance.php from functions/ to modules/Attendance/includes
require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';

DrawHeader( ProgramTitle() );

// Set date.
$date = RequestedDate( 'date', DBDate(), 'set' );

if ( $_SESSION['Administration.php']['date']
	&& $_SESSION['Administration.php']['date'] !== $date )
{
	// Unset attendance & attendance day & redirect URL.
	RedirectURL( array( 'attendance', 'attendance_day' ) );
}

if ( $_REQUEST['table'] == '' )
{
	$_REQUEST['table'] = '0';
}

if ( $_REQUEST['table'] == '0' )
{
	$table = 'ATTENDANCE_PERIOD';
	$extra_sql = '';
}
else
{
	$table = 'LUNCH_PERIOD';
	$extra_sql = " AND TABLE_NAME='" . $_REQUEST['table'] . "'";
}

$_SESSION['Administration.php']['date'] = $date;
$current_mp = GetCurrentMP( 'QTR', $date, false );

if ( ! $current_mp )
{
	echo '<form action="' .
	PreparePHP_SELF( $_REQUEST ) .
		'" method="POST">';

	DrawHeader(
		PrepareDate( $date, '_date', false, array( 'submit' => true ) )
	);

	echo '</form>';

	ErrorMessage( array( _( 'The selected date is not in a school quarter.' ) ), 'fatal' );
}

$all_mp = GetAllMP( 'QTR', $current_mp );

$current_Q = "SELECT ATTENDANCE_TEACHER_CODE,ATTENDANCE_CODE,ATTENDANCE_REASON,COMMENT,
	STUDENT_ID,ADMIN,PERIOD_ID
	FROM " . DBEscapeIdentifier( $table ) .
	" WHERE SCHOOL_DATE='" . $date . "'" . $extra_sql;

//FJ days numbered
//FJ multiple school periods for a course period

if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
{
	$current_schedule_Q = "SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,cp.HALF_DAY
	FROM SCHEDULE s,COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND s.STUDENT_ID='__student_id__'
	AND s.SYEAR='" . UserSyear() . "'
	AND s.SCHOOL_ID='" . UserSchool() . "'
	AND cp.COURSE_PERIOD_ID = s.COURSE_PERIOD_ID
	AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0
	AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
	AND position(substring('MTWHFSU' FROM cast(
		(SELECT CASE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
		FROM attendance_calendar
		WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<='" . $date . "' AND end_date>='" . $date . "' AND mp='QTR' AND SCHOOL_ID=s.SCHOOL_ID)
		AND school_date<='" . $date . "'
		AND SCHOOL_ID=s.SCHOOL_ID)
	AS INT) FOR 1) IN cpsp.DAYS)>0
	AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
	ORDER BY s.START_DATE ASC";
}
else
{
	$current_schedule_Q = "SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,cp.HALF_DAY
	FROM SCHEDULE s,COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND s.STUDENT_ID='__student_id__'
	AND s.SYEAR='" . UserSyear() . "'
	AND s.SCHOOL_ID='" . UserSchool() . "'
	AND cp.COURSE_PERIOD_ID = s.COURSE_PERIOD_ID
	AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0
	AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
	AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM cast('" . $date . "' AS DATE)) AS INT)+1 FOR 1) IN cpsp.DAYS)>0
	AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
	ORDER BY s.START_DATE ASC";
}

// TODO: can be optimized? Remove PERIOD_ID index.
$current_RET = DBGet( $current_Q, array(), array( 'STUDENT_ID', 'PERIOD_ID' ) );

if ( $_REQUEST['attendance']
	&& $_POST['attendance']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['attendance'] as $student_id => $values )
	{
		if ( ! $current_schedule_RET[$student_id] )
		{
			$current_schedule_RET[$student_id] = DBGet( str_replace( '__student_id__', $student_id, $current_schedule_Q ), array(), array( 'PERIOD_ID' ) );

			if ( ! $current_schedule_RET[$student_id] )
			{
				$current_schedule_RET[$student_id] = true;
			}
		}

		foreach ( (array) $values as $period_id => $columns )
		{
			if ( $current_RET[$student_id][$period_id] )
			{
				$sql = "UPDATE " . DBEscapeIdentifier( $table ) .
					" SET ADMIN='Y',
					COURSE_PERIOD_ID='" . $current_schedule_RET[$student_id][$period_id][1]['COURSE_PERIOD_ID'] . "',";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE SCHOOL_DATE='" . $date . "'
					AND PERIOD_ID='" . $period_id . "'
					AND STUDENT_ID='" . $student_id . "'" .
					$extra_sql;

				DBQuery( $sql );
			}
			else
			{
				$sql = "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

				$fields = 'STUDENT_ID,SCHOOL_DATE,PERIOD_ID,MARKING_PERIOD_ID,ADMIN,COURSE_PERIOD_ID,';
				$values = "'" . $student_id . "','" . $date . "','" . $period_id . "','" . $current_mp . "','Y','" . $current_schedule_RET[$student_id][$period_id][1]['COURSE_PERIOD_ID'] . "',";

				if ( $table == 'LUNCH_PERIOD' )
				{
					$fields .= 'TABLE_NAME,';
					$values .= "'" . $_REQUEST['table'] . "',";
				}

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

		UpdateAttendanceDaily(
			$student_id,
			$date,
			( $_REQUEST['attendance_day'][$student_id]['COMMENT'] ?
				$_REQUEST['attendance_day'][$student_id]['COMMENT'] :
				false
			)
		);

		unset( $_REQUEST['attendance_day'][$student_id] );
	}

	// TODO: can be optimized? Remove PERIOD_ID index.
	$current_RET = DBGet( $current_Q, array(), array( 'STUDENT_ID', 'PERIOD_ID' ) );

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

$codes_RET = DBGet( "SELECT ID,SHORT_NAME,TITLE,STATE_CODE FROM ATTENDANCE_CODES WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' AND TABLE_NAME='" . $_REQUEST['table'] . "'" );

$periods_RET = DBGet( "SELECT PERIOD_ID,SHORT_NAME,TITLE
FROM SCHOOL_PERIODS
WHERE SCHOOL_ID='" . UserSchool() . "'
AND SYEAR='" . UserSyear() . "'
AND EXISTS (SELECT '' FROM COURSE_PERIODS WHERE PERIOD_ID=SCHOOL_PERIODS.PERIOD_ID AND position('," . $_REQUEST['table'] . ",' IN DOES_ATTENDANCE)>0)
ORDER BY SORT_ORDER" );

$categories_RET = DBGet( "SELECT ID,TITLE FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "'" );

if ( ! empty( $categories_RET ) )
{
	$tmp_PHP_SELF = PreparePHP_SELF( $_REQUEST, array( 'table', 'codes' ) );

	$headerl .= '<a href="' . $tmp_PHP_SELF . '&amp;table=0"><b>' . _( 'Attendance' ) . '</b></a>';

	foreach ( (array) $categories_RET as $category )
	{
		$headerl .= ' - <a href="' . $tmp_PHP_SELF . '&amp;table=' . $category['ID'] . '"><b>' .
			$category['TITLE'] . '</b></a>';
	}
}

if ( isset( $_REQUEST['student_id'] ) && $_REQUEST['student_id'] !== 'new' )
{
	if ( UserStudentID() != $_REQUEST['student_id'] )
	{
		SetUserStudentID( $_REQUEST['student_id'] );
	}

	$functions = array( 'ATTENDANCE_CODE' => '_makeCodePulldown', 'ATTENDANCE_TEACHER_CODE' => '_makeCode', 'ATTENDANCE_REASON' => '_makeReasonInput', 'COMMENT' => '_makeReason' );

	//FJ days numbered
	//FJ multiple school periods for a course period

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$schedule_RET = DBGet( "SELECT
		s.STUDENT_ID,c.TITLE AS COURSE,cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,p.TITLE AS PERIOD_TITLE,
		s.STUDENT_ID AS ATTENDANCE_CODE,s.STUDENT_ID AS ATTENDANCE_TEACHER_CODE,s.STUDENT_ID AS ATTENDANCE_REASON,s.STUDENT_ID AS COMMENT
		FROM SCHEDULE s,COURSES c,COURSE_PERIODS cp,SCHOOL_PERIODS p,ATTENDANCE_CALENDAR ac, COURSE_PERIOD_SCHOOL_PERIODS cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
		s.SYEAR='" . UserSyear() . "' AND s.SCHOOL_ID='" . UserSchool() . "' AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
		AND s.COURSE_ID=c.COURSE_ID
		AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=p.PERIOD_ID AND position(',$_REQUEST[table],' IN cp.DOES_ATTENDANCE)>0
		AND s.STUDENT_ID='" . $_REQUEST['student_id'] . "' AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
		AND position(substring('MTWHFSU' FROM cast(
			(SELECT CASE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<='" . $date . "' AND end_date>='" . $date . "' AND mp='QTR' AND SCHOOL_ID=s.SCHOOL_ID)
			AND school_date<='" . $date . "' AND SCHOOL_ID=s.SCHOOL_ID) AS INT) FOR 1)
		IN cpsp.DAYS)>0
		AND ac.CALENDAR_ID=cp.CALENDAR_ID AND ac.SCHOOL_DATE='" . $date . "' AND ac.MINUTES!='0'
		ORDER BY p.SORT_ORDER", $functions );
	}
	else
	{
		$schedule_RET = DBGet( "SELECT
		s.STUDENT_ID,c.TITLE AS COURSE,cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,p.TITLE AS PERIOD_TITLE,
		s.STUDENT_ID AS ATTENDANCE_CODE,s.STUDENT_ID AS ATTENDANCE_TEACHER_CODE,s.STUDENT_ID AS ATTENDANCE_REASON,s.STUDENT_ID AS COMMENT
		FROM SCHEDULE s,COURSES c,COURSE_PERIODS cp,SCHOOL_PERIODS p,ATTENDANCE_CALENDAR ac, COURSE_PERIOD_SCHOOL_PERIODS cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
		s.SYEAR='" . UserSyear() . "' AND s.SCHOOL_ID='" . UserSchool() . "'
		AND s.MARKING_PERIOD_ID IN (" . $all_mp . ")
		AND s.COURSE_ID=c.COURSE_ID
		AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=p.PERIOD_ID AND position(',$_REQUEST[table],' IN cp.DOES_ATTENDANCE)>0
		AND s.STUDENT_ID='" . $_REQUEST['student_id'] . "'
		AND ('" . $date . "' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '" . $date . "'>=s.START_DATE))
		AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM cast('" . $date . "' AS DATE)) AS INT)+1 FOR 1) IN cpsp.DAYS)>0
		AND ac.CALENDAR_ID=cp.CALENDAR_ID AND ac.SCHOOL_DATE='" . $date . "' AND ac.MINUTES!='0'
		ORDER BY p.SORT_ORDER", $functions );
	}

	$columns = array(
		'PERIOD_TITLE' => _( 'Period' ),
		'COURSE' => _( 'Course' ),
		'ATTENDANCE_CODE' => _( 'Attendance Code' ),
		'ATTENDANCE_TEACHER_CODE' => _( 'Teacher\'s Entry' ),
		'ATTENDANCE_REASON' => _( 'Office Comment' ),
		'COMMENT' => _( 'Teacher Comment' ),
	);

	echo '<form action="' .
	PreparePHP_SELF( $_REQUEST ) .
		'" method="POST">';

	DrawHeader(
		PrepareDate( $date, '_date', false, array( 'submit' => true ) ),
		SubmitButton( _( 'Update' ) )
	);

	$headerr = '<a href="' . PreparePHP_Self( $_REQUEST, array( 'student_id' ) ) . '">' .
	_( 'Student List' ) . '</a>';

	DrawHeader( $headerl, $headerr );

	ListOutput( $schedule_RET, $columns, 'Course', 'Courses' );
	echo '</form>';
}
else
{
	if ( $_REQUEST['expanded_view'] != 'true' )
	{
		$extra['WHERE'] = $extra2['WHERE'] = " AND EXISTS (SELECT '' FROM " . DBEscapeIdentifier( $table ) . " ap,ATTENDANCE_CODES ac
			WHERE ap.SCHOOL_DATE='" . $date . "'
			AND ap.STUDENT_ID=ssm.STUDENT_ID
			AND ap.ATTENDANCE_CODE=ac.ID
			AND ac.SCHOOL_ID=ssm.SCHOOL_ID
			AND ac.SYEAR=ssm.SYEAR " . str_replace( 'TABLE_NAME', 'ac.TABLE_NAME', $extra_sql );
	}
	else
	{
		$extra['WHERE'] = " AND EXISTS (SELECT '' FROM " . DBEscapeIdentifier( $table ) . " ap,ATTENDANCE_CODES ac
			WHERE ap.SCHOOL_DATE='" . $date . "'
			AND ap.STUDENT_ID=ssm.STUDENT_ID
			AND ap.ATTENDANCE_CODE=ac.ID
			AND ac.SCHOOL_ID=ssm.SCHOOL_ID
			AND ac.SYEAR=ssm.SYEAR " . str_replace( 'TABLE_NAME', 'ac.TABLE_NAME', $extra_sql );
	}

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
		$extra['WHERE'] .= "AND ac.ID IN (";

		foreach ( (array) $REQ_codes as $code )
		{
			$extra['WHERE'] .= "'" . $code . "',";
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
		$RET = DBGet( "SELECT ID FROM ATTENDANCE_CODES WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL) AND TABLE_NAME='" . $_REQUEST['table'] . "'" );

		if ( ! empty( $RET ) )
		{
			$extra['WHERE'] .= "AND ac.ID IN (";

			foreach ( (array) $RET as $code )
			{
				$extra['WHERE'] .= "'" . $code['ID'] . "',";
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
		$extra2['FROM'] .= ',ADDRESS a,STUDENTS_JOIN_ADDRESS sja LEFT OUTER JOIN STUDENTS_JOIN_PEOPLE sjp ON (sja.STUDENT_ID=sjp.STUDENT_ID AND sja.ADDRESS_ID=sjp.ADDRESS_ID AND (sjp.CUSTODY=\'Y\' OR sjp.EMERGENCY=\'Y\')) LEFT OUTER JOIN PEOPLE p ON (p.PERSON_ID=sjp.PERSON_ID) LEFT OUTER JOIN PEOPLE_JOIN_CONTACTS pjc ON (pjc.PERSON_ID=p.PERSON_ID) ';
		$extra2['WHERE'] .= ' AND a.ADDRESS_ID=sja.ADDRESS_ID AND sja.STUDENT_ID=ssm.STUDENT_ID ';
		$extra2['ORDER_BY'] .= 'COALESCE(sjp.CUSTODY,\'N\') DESC';
		$extra2['group'] = array( 'STUDENT_ID', 'PERSON_ID' );

		$contacts_RET = GetStuList( $extra2 );
		$extra['columns_before']['PHONE'] = button( 'down_phone' );
	}

	$columns = array();
	$extra['SELECT'] .= ',s.STUDENT_ID AS PHONE';
	$extra['functions']['PHONE'] = 'makeContactInfo';

	if ( $_REQUEST['table'] == '0' )
	{
		$extra['SELECT'] .= ",(SELECT STATE_VALUE FROM ATTENDANCE_DAY WHERE STUDENT_ID=ssm.STUDENT_ID AND SCHOOL_DATE='" . $date . "') AS STATE_VALUE";
		$extra['SELECT'] .= ",(SELECT COMMENT FROM ATTENDANCE_DAY WHERE STUDENT_ID=ssm.STUDENT_ID AND SCHOOL_DATE='" . $date . "') AS DAILY_COMMENT";
		$extra['functions']['STATE_VALUE'] = '_makeStateValue';
		$extra['functions']['DAILY_COMMENT'] = '_makeStateValue';
//FJ add translation
		$extra['columns_after']['STATE_VALUE'] = _( 'Present' );
		$extra['columns_after']['DAILY_COMMENT'] = _( 'Day Comment' );
	}

	// $extra['link']['FULL_NAME']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&month_date='.$_REQUEST['month_date'].'&day_date='.$_REQUEST['day_date'].'&year_date='.$_REQUEST['year_date'].'&table='.$_REQUEST['table'];
	$extra['link']['FULL_NAME']['link'] = PreparePHP_SELF( $_REQUEST );
	$extra['link']['FULL_NAME']['variables'] = array( 'student_id' => 'STUDENT_ID' );
	$extra['BackPrompt'] = false;
	$extra['Redirect'] = false;
	$extra['new'] = true;

	foreach ( (array) $periods_RET as $period )
	{
		$extra['SELECT'] .= ",s.STUDENT_ID AS PERIOD_" . $period['PERIOD_ID'];
		$extra['functions']['PERIOD_' . $period['PERIOD_ID']] = '_makeCodePulldown';
		$extra['columns_after']['PERIOD_' . $period['PERIOD_ID']] = $period['SHORT_NAME'];
	}

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

	echo '<form action="' .
	PreparePHP_SELF( $_REQUEST ) .
		'" method="POST">';

	DrawHeader(
		PrepareDate( $date, '_date', false, array( 'submit' => true ) ),
		SubmitButton( _( 'Update' ) )
	);

	if ( UserStudentID() )
	{
		$current_student_link = '<a href="' .
		PreparePHP_Self( $_REQUEST, array(), array( 'student_id' => UserStudentID() ) ) . '">' .
		_( 'Current Student' ) . '</a></td><td>';
	}

	$headerr = '<table style="float: right;"><tr><td class="align-right">' .
	button(
		'add',
		'',
		'"#" onclick=\'javascript:addHTML("' . str_replace( '"', '\"', _makeCodeSearch() ) .
		'","code_pulldowns"); return false;\''
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

	if ( ! $current_schedule_RET[$value] )
	{
		$current_schedule_RET[$value] = DBGet( str_replace( '__student_id__', $value, $current_schedule_Q ), array(), array( 'PERIOD_ID' ) );

		if ( ! $current_schedule_RET[$value] )
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
		foreach ( (array) $codes_RET as $code )
		{
			if ( $current_schedule_RET[$value][$period_id][1]['HALF_DAY'] != 'Y' || $code['STATE_CODE'] != 'H' ) // prune half day codes for half day courses
			{
				$options[$code['ID']] = $code[$code_title];
			}
		}

		$val = $current_RET[$value][$period_id][1]['ATTENDANCE_CODE'];

		return SelectInput( $val, 'attendance[' . $value . '][' . $period_id . '][ATTENDANCE_CODE]', '', $options );
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
		if ( $current_RET[$value][$THIS_RET['PERIOD_ID']][1]['ATTENDANCE_TEACHER_CODE'] == $code['ID'] )
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

	$val = $current_RET[$value][$THIS_RET['PERIOD_ID']][1]['ATTENDANCE_REASON'];

	return TextInput( $val, 'attendance[' . $value . '][' . $THIS_RET['PERIOD_ID'] . '][ATTENDANCE_REASON]', '', $options );
}

/**
 * @param $value
 * @param $title
 * @return mixed
 */
function _makeReason( $value, $title )
{
	global $THIS_RET, $current_RET;

	return $current_RET[$value][$THIS_RET['PERIOD_ID']][1]['COMMENT'];
}

/**
 * @param $value
 * @return mixed
 */
function _makeCodeSearch( $value = '' )
{
	global $codes_RET, $code_search_selected;

	$return = '<select name=codes[]><option value="">' . _( 'All' ) . '</option>';

	if ( $_REQUEST['table'] == '0' )
	{
		$return .= '<option value="A"' . (  ( $value == 'A' ) ? ' selected' : '' ) . '>' . _( 'Not Present' ) . '</option>';
	}

	if ( ! empty( $codes_RET ) )
	{
		foreach ( (array) $codes_RET as $code )
		{
			$return .= '<option value="' . $code['ID'] . '"' . ( $value == $code['ID'] ? ' selected' : '' ) . '>' . $code['TITLE'] . '</option>';
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
	global $THIS_RET;

	if ( $name == 'STATE_VALUE' )
	{
		if ( $value == '0.0' )
//FJ add translation

		{
			return _( 'None' );
		}
		elseif ( $value == '0.5' )
		{
			return _( 'Half Day' );
		}
		else
		{
			return _( 'Full Day' );
		}
	}
	else
	{
		return TextInput( $value, 'attendance_day[' . $THIS_RET['STUDENT_ID'] . '][COMMENT]' );
	}
}
