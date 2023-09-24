<?php

$_REQUEST['list_by_day'] = issetVal( $_REQUEST['list_by_day'], '' );

$_REQUEST['_search_all_schools'] = issetVal( $_REQUEST['_search_all_schools'], '' );

DrawHeader( ProgramTitle() );

$report_link = PreparePHP_SELF(
	[],
	[ 'list_by_day', 'search_modfunc', 'next_modname' ]
) . '&list_by_day=';

$report_select = SelectInput(
	$_REQUEST['list_by_day'],
	'list_by_day',
	'',
	[
		'' => _( 'Average Daily Attendance' ),
		'true' => _( 'Average Attendance by Day' ),
	],
	false,
	'onchange="' . AttrEscape( 'ajaxLink(' . json_encode( $report_link ) . ' + this.value);' ) . '" autocomplete="off"',
	false
);

DrawHeader( $report_select );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01', 'set' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate(), 'set' );

// Advanced Search.

if ( $_REQUEST['modfunc'] === 'search' )
{
	$extra['new'] = true;

	$extra['search_title'] = _( 'Advanced' );

	$extra['action'] = '&list_by_day=' . $_REQUEST['list_by_day'] .
		'&day_start=' . $_REQUEST['day_start'] .
		'&day_end=' . $_REQUEST['day_end'] .
		'&month_start=' . $_REQUEST['month_start'] .
		'&month_end=' . $_REQUEST['month_end'] .
		'&year_start=' . $_REQUEST['year_start'] .
		'&year_end=' . $_REQUEST['year_end'] .
		'&modfunc=&search_modfunc=';

	Search( 'student_id', $extra );
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( ! isset( $extra ) )
	{
		$extra = [];
	}

	// Advanced Search.
	Widgets( 'all', $extra );

	// Fix SQL error Unknown column 'a.ADDRESS' in 'where clause'
	// Use GetStuList() instead of appendSQL() + CustomFields()
	GetStuList( $extra );

	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="GET">';

	DrawHeader(
		_( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) .
		' &nbsp; ' . _( 'to' ) . ' &nbsp; ' . PrepareDate( $end_date, '_end', false ) . ' ' .
		SubmitButton( _( 'Go' ) ),
		'<a href="' . PreparePHP_SELF( $_REQUEST, [ 'search_modfunc' ], [
			'modfunc' => 'search',
			'include_top' => 'false',
		] ) . '">' . _( 'Advanced' ) . '</a>'
	);

	echo '</form>';

	if ( $_ROSARIO['SearchTerms'] )
	{
		DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );
	}

	if ( $_REQUEST['list_by_day'] == 'true' )
	{
		$cal_days = 1;

		$student_days_absent = DBGet( "SELECT ad.SCHOOL_DATE,ssm.GRADE_ID,COALESCE(sum(ad.STATE_VALUE-1)*-1,0) AS STATE_VALUE
		FROM attendance_day ad,students s,student_enrollment ssm" . $extra['FROM'] . "
		WHERE s.STUDENT_ID=ssm.STUDENT_ID
		AND ad.STUDENT_ID=ssm.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ad.SYEAR=ssm.SYEAR
		AND ssm.SCHOOL_ID='" . UserSchool() . "'
		AND ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		AND (ad.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE OR (ssm.END_DATE IS NULL AND ssm.START_DATE <= ad.SCHOOL_DATE))
		" . $extra['WHERE'] . "
		GROUP BY ad.SCHOOL_DATE,ssm.GRADE_ID", [ '' ], [ 'SCHOOL_DATE', 'GRADE_ID' ] );

		$student_days_possible = DBGet( "SELECT ac.SCHOOL_DATE,ssm.GRADE_ID,'' AS DAYS_POSSIBLE,count(*) AS ATTENDANCE_POSSIBLE,count(*) AS STUDENTS,'' AS PRESENT,'' AS ABSENT,'' AS ADA,'' AS AVERAGE_ATTENDANCE,'' AS AVERAGE_ABSENT
		FROM attendance_calendar ac,students s,student_enrollment ssm" . $extra['FROM'] . "
		WHERE s.STUDENT_ID=ssm.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ac.SYEAR=ssm.SYEAR
		AND ssm.SCHOOL_ID='" . UserSchool() . "'
		AND ssm.SCHOOL_ID=ac.SCHOOL_ID
		AND (ac.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE OR (ssm.END_DATE IS NULL AND ssm.START_DATE <= ac.SCHOOL_DATE))
		AND ac.SCHOOL_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		" . $extra['WHERE'] . "
		GROUP BY ac.SCHOOL_DATE,ssm.GRADE_ID
		ORDER BY ac.SCHOOL_DATE",
			[
				'SCHOOL_DATE' => 'ProperDate',
				'GRADE_ID' => 'GetGrade',
				'STUDENTS' => '_makeByDay',
				'PRESENT' => '_makeByDay',
				'ABSENT' => '_makeByDay',
				'ADA' => '_makeByDay',
				'AVERAGE_ATTENDANCE' => '_makeByDay',
				'AVERAGE_ABSENT' => '_makeByDay',
				'DAYS_POSSIBLE' => '_makeByDay',
			]
		);

		$columns = [
			'SCHOOL_DATE' => _( 'Date' ),
			'GRADE_ID' => _( 'Grade Level' ),
			'STUDENTS' => _( 'Students' ),
			'DAYS_POSSIBLE' => _( 'Days Possible' ),
			'PRESENT' => _( 'Present' ),
			'ABSENT' => _( 'Absent' ),
			'ADA' => _( 'ADA' ),
			'AVERAGE_ATTENDANCE' => _( 'Average Attendance' ),
			'AVERAGE_ABSENT' => _( 'Average Absent' ),
		];

		ListOutput( $student_days_possible, $columns, 'School Day', 'School Days' );
	}
	else
	{
		$cal_days = DBGet( "SELECT count(*) AS COUNT,CALENDAR_ID
			FROM attendance_calendar
			WHERE " . ( $_REQUEST['_search_all_schools'] != 'Y' ? "SCHOOL_ID='" . UserSchool() . "' AND " : '' ) .
			" SYEAR='" . UserSyear() . "'
			AND SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
			GROUP BY CALENDAR_ID", [], [ 'CALENDAR_ID' ] );

		$calendars_RET = DBGet( "SELECT CALENDAR_ID,TITLE
			FROM attendance_calendars
			WHERE SYEAR='" . UserSyear() . "' " .
			( $_REQUEST['_search_all_schools'] != 'Y' ? " AND SCHOOL_ID='" . UserSchool() . "'" : '' ), [], [ 'CALENDAR_ID' ] );

		$extra['WHERE'] .= " GROUP BY ssm.GRADE_ID,ssm.CALENDAR_ID";

		$student_days_absent = DBGet( "SELECT ssm.GRADE_ID,ssm.CALENDAR_ID,COALESCE(sum(ad.STATE_VALUE-1)*-1,0) AS STATE_VALUE
		FROM attendance_day ad,students s,student_enrollment ssm" . $extra['FROM'] . "
		WHERE s.STUDENT_ID=ssm.STUDENT_ID
		AND ad.STUDENT_ID=ssm.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ad.SYEAR=ssm.SYEAR
		AND ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		AND (ad.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE OR (ssm.END_DATE IS NULL AND ssm.START_DATE <= ad.SCHOOL_DATE))
		" . $extra['WHERE'], [ '' ], [ 'GRADE_ID', 'CALENDAR_ID' ] );

		$student_days_possible = DBGet( "SELECT ssm.GRADE_ID,ssm.CALENDAR_ID,'' AS DAYS_POSSIBLE,
			count(*) AS ATTENDANCE_POSSIBLE,count(*) AS STUDENTS,'' AS PRESENT,'' AS ABSENT,
			'' AS ADA,'' AS AVERAGE_ATTENDANCE,'' AS AVERAGE_ABSENT
		FROM attendance_calendar ac,students s,student_enrollment ssm" . $extra['FROM'] . "
		WHERE s.STUDENT_ID=ssm.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ac.SYEAR=ssm.SYEAR
		AND ac.CALENDAR_ID=ssm.CALENDAR_ID
		AND " . ( $_REQUEST['_search_all_schools'] != 'Y' ? "ssm.SCHOOL_ID='" . UserSchool() . "' AND " : '' ) . " ssm.SCHOOL_ID=ac.SCHOOL_ID
		AND (ac.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE OR (ssm.END_DATE IS NULL AND ssm.START_DATE <= ac.SCHOOL_DATE))
		AND ac.SCHOOL_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		" . $extra['WHERE'],
			[
				'GRADE_ID' => '_make',
				'STUDENTS' => '_make',
				'PRESENT' => '_make',
				'ABSENT' => '_make',
				'ADA' => '_make',
				'AVERAGE_ATTENDANCE' => '_make',
				'AVERAGE_ABSENT' => '_make',
				'DAYS_POSSIBLE' => '_make',
			]
		);

		$columns = [
			'GRADE_ID' => _( 'Grade Level' ),
			'STUDENTS' => _( 'Students' ),
			'DAYS_POSSIBLE' => _( 'Days Possible' ),
			'PRESENT' => _( 'Present' ),
			'ABSENT' => _( 'Absent' ),
			'ADA' => _( 'ADA' ),
			'AVERAGE_ATTENDANCE' => _( 'Average Attendance' ),
			'AVERAGE_ABSENT' => _( 'Average Absent' ),
		];

		$link['add']['html'] = [
			'GRADE_ID' => '<b>' . _( 'Total' ) . '</b>',
			'STUDENTS' => round( issetVal( $sum['STUDENTS'], 0 ), 1 ),
			'DAYS_POSSIBLE' => issetVal( $cal_days[key( $cal_days )][1]['COUNT'], 0 ),
			'PRESENT' => issetVal( $sum['PRESENT'] ),
			'ADA' => _Percent(  (  ( $sum['PRESENT'] + issetVal( $sum['ABSENT'] ) ) > 0 ? ( $sum['PRESENT'] ) / ( $sum['PRESENT'] + $sum['ABSENT'] ) : 0 ) ),
			'ABSENT' => $sum['ABSENT'],
			'AVERAGE_ATTENDANCE' => round( issetVal( $sum['AVERAGE_ATTENDANCE'], 0 ), 1 ),
			'AVERAGE_ABSENT' => round( issetVal( $sum['AVERAGE_ABSENT'], 0 ), 1 ),
		];

		ListOutput(
			$student_days_possible,
			$columns,
			'Grade Level',
			'Grade Levels',
			$link
		);
	}
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _make( $value, $column )
{
	global $THIS_RET,
		$student_days_absent,
		$cal_days,
		$sum,
		$calendars_RET;

	if ( empty( $sum ) )
	{
		$sum = [
			'STUDENTS' => 0,
			'PRESENT' => 0,
			'ABSENT' => 0,
			'AVERAGE_ATTENDANCE' => 0,
			'AVERAGE_ABSENT' => 0,
		];
	}

	$student_days_absent_state_value = isset( $student_days_absent[$THIS_RET['GRADE_ID']][$THIS_RET['CALENDAR_ID']][1]['STATE_VALUE'] ) ?
		$student_days_absent[$THIS_RET['GRADE_ID']][$THIS_RET['CALENDAR_ID']][1]['STATE_VALUE'] : null;

	switch ( $column )
	{
		case 'STUDENTS':
			$sum['STUDENTS'] += $value / $cal_days[$THIS_RET['CALENDAR_ID']][1]['COUNT'];

			return round( $value / $cal_days[$THIS_RET['CALENDAR_ID']][1]['COUNT'], 1 );
			break;

		case 'DAYS_POSSIBLE':
			return $cal_days[$THIS_RET['CALENDAR_ID']][1]['COUNT'];
			break;

		case 'PRESENT':
			$sum['PRESENT'] += ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value );

			return $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value;
			break;
		case 'ABSENT':
			$sum['ABSENT'] += ( $student_days_absent_state_value );

			return $student_days_absent_state_value;
			break;

		case 'ADA':
			return _Percent( ( ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value ) ) / $THIS_RET['STUDENTS'] );
			break;

		case 'AVERAGE_ATTENDANCE':
			$sum['AVERAGE_ATTENDANCE'] += ( ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value ) / $cal_days[$THIS_RET['CALENDAR_ID']][1]['COUNT'] );

			return round( ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value ) / $cal_days[$THIS_RET['CALENDAR_ID']][1]['COUNT'], 1 );
			break;

		case 'AVERAGE_ABSENT':
			$sum['AVERAGE_ABSENT'] += ( $student_days_absent_state_value / $cal_days[$THIS_RET['CALENDAR_ID']][1]['COUNT'] );

			return round( $student_days_absent_state_value / $cal_days[$THIS_RET['CALENDAR_ID']][1]['COUNT'], 1 );
			break;

		case 'GRADE_ID':
			return GetGrade( $value ) . ( count( (array) $cal_days ) > 1 ? ' - ' . $calendars_RET[$THIS_RET['CALENDAR_ID']][1]['TITLE'] : '' );
	}
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _makeByDay( $value, $column )
{
	global $THIS_RET,
		$student_days_absent,
		$cal_days,
		$sum;

	if ( empty( $sum ) )
	{
		$sum = [
			'STUDENTS' => 0,
			'PRESENT' => 0,
			'ABSENT' => 0,
			'AVERAGE_ATTENDANCE' => 0,
			'AVERAGE_ABSENT' => 0,
		];
	}

	$student_days_absent_state_value = isset( $student_days_absent[$THIS_RET['SCHOOL_DATE']][$THIS_RET['GRADE_ID']][1]['STATE_VALUE'] ) ?
		$student_days_absent[$THIS_RET['SCHOOL_DATE']][$THIS_RET['GRADE_ID']][1]['STATE_VALUE'] : null;

	switch ( $column )
	{
		case 'STUDENTS':
			$sum['STUDENTS'] += $value / $cal_days;

			return round( $value / $cal_days, 1 );
			break;

		case 'DAYS_POSSIBLE':
			return $cal_days;
			break;

		case 'PRESENT':
			$sum['PRESENT'] += ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value );

			return $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value;
			break;

		case 'ABSENT':
			$sum['ABSENT'] += ( $student_days_absent_state_value );

			return $student_days_absent_state_value;
			break;

		case 'ADA':
			return _Percent(  (  ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value ) ) / $THIS_RET['STUDENTS'] );
			break;

		case 'AVERAGE_ATTENDANCE':
			$sum['AVERAGE_ATTENDANCE'] += (  ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value ) / $cal_days );

			return round(  ( $THIS_RET['ATTENDANCE_POSSIBLE'] - $student_days_absent_state_value ) / $cal_days, 1 );
			break;

		case 'AVERAGE_ABSENT':
			$sum['AVERAGE_ABSENT'] += ( $student_days_absent_state_value / $cal_days );

			return round( $student_days_absent_state_value / $cal_days, 1 );
			break;
	}
}

/**
 * @param $num
 * @param $decimals
 */
function _Percent( $num, $decimals = 2 )
{
	// Fix trim 0 (float) when percent > 1,000: do not use comma for thousand separator.
	return (float) number_format( $num * 100, $decimals, '.', '' ) . '%';
}
