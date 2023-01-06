<?php

// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY
$eligibility_config = ProgramConfig( 'eligibility' );

foreach ( (array) $eligibility_config as $value )
{
	${$value[1]['TITLE']} = $value[1]['VALUE'];
}

// Day of the week: 1 (for Monday) through 7 (for Sunday).
$today = date( 'w' ) ? date( 'w' ) : 7;

$start = time() - ( $today - $START_DAY ) * 60 * 60 * 24;

if ( empty( $_REQUEST['start_date'] ) )
{
	$start_time = $start;

	$start_date = date( 'Y-m-d', $start_time );

	$end_date = date( 'Y-m-d' );
}
else
{
	$start_time = $_REQUEST['start_date'];

	$start_date = date( 'Y-m-d', $start_time );

	$end_date = date( 'Y-m-d', $start_time + 60 * 60 * 24 * 7 );
}

DrawHeader( ProgramTitle() );

if ( $_REQUEST['search_modfunc']
	|| User( 'PROFILE' ) === 'parent'
	|| User( 'PROFILE' ) === 'student' )
{
	$tmp_PHP_SELF = PreparePHP_SELF();
	echo '<form action="' . $tmp_PHP_SELF . '" method="POST">';

	$begin_year = DBGetOne( "SELECT min(" . _SQLUnixTimestamp( 'SCHOOL_DATE' ) . ") AS SCHOOL_DATE
		FROM attendance_calendar
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( is_null( $begin_year ) )
	{
		ErrorMessage( [ _( 'There are no calendars yet setup.' ) ], 'fatal' );
	}

	$date_select = '<option value="' . AttrEscape( $start ) . '">' . ProperDate( date( 'Y-m-d', $start ) ) . ' - ' . ProperDate( DBDate() ) . '</option>';

	for ( $i = $start - ( 60 * 60 * 24 * 7 ); $i >= $begin_year; $i -= ( 60 * 60 * 24 * 7 ) )
	{
		$date_select .= '<option value="' . AttrEscape( $i ) . '"' . (  ( $i + 86400 >= $start_time && $i - 86400 <= $start_time ) ? ' selected' : '' ) . '>' . ProperDate( date( 'Y-m-d', $i ) ) . ' - ' . ProperDate( date( 'Y-m-d', ( $i + 1 + (  ( $END_DAY - $START_DAY ) ) * 60 * 60 * 24 ) ) ) . '</option>';
	}

	$date_select = '<select name="start_date" id="start_date">' . $date_select . '</select>';

	DrawHeader( '<label for="start_date">' . _( 'Timeframe' ) . ':</label> ' . $date_select . ' ' .
		Buttons( _( 'Go' ) ) );

	echo '</form>';
}

$extra['SELECT'] = ",e.ELIGIBILITY_CODE,c.TITLE as COURSE_TITLE";
$extra['FROM'] = ",eligibility e,courses c,course_periods cp";
$extra['WHERE'] = " AND e.STUDENT_ID=ssm.STUDENT_ID AND e.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.COURSE_ID=c.COURSE_ID AND e.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

$extra['functions'] = [ 'ELIGIBILITY_CODE' => '_makeLower', 'FULL_NAME' => 'makePhotoTipMessage' ];
$extra['group'] = [ 'STUDENT_ID' ];

Widgets( 'eligibility' );
Widgets( 'activity' );
Widgets( 'course' );

if ( ! $_REQUEST['search_modfunc']
	&& User( 'PROFILE' ) !== 'parent'
	&& User( 'PROFILE' ) !== 'student' )
{
	$extra['new'] = true;

	Search( 'student_id', $extra );
}
else
{
	$student_RET = GetStuList( $extra );

	$columns = [
		'FULL_NAME' => _( 'Student' ),
		'COURSE_TITLE' => _( 'Course' ),
		'ELIGIBILITY_CODE' => _( 'Grade' ),
	];

	ListOutput(
		$student_RET,
		$columns,
		'Student',
		'Students',
		[],
		[ 'STUDENT_ID' => [ 'FULL_NAME', 'STUDENT_ID' ] ]
	);
}

/**
 * @param $word
 */
function _makeLower( $word )
{
	return ucwords( mb_strtolower( $word ) );
}

/**
 * SQL to extract Unix timestamp or epoch from date
 * Use UNIX_TIMESTAMP() for MySQL and extract(EPOCH) for PostgreSQL
 *
 * Local function
 *
 * @since 10.0
 *
 * @param  string $column Date column.
 *
 * @return string         MySQL or PostgreSQL function
 */
function _SQLUnixTimestamp( $column )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return "UNIX_TIMESTAMP(" . $column . ")";
	}

	return "extract(EPOCH FROM " . $column . ")";
}
