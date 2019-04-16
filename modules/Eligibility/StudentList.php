<?php
// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY
$eligibility_config = ProgramConfig( 'eligibility' );

foreach ( (array) $eligibility_config as $value )
{
	${$value[1]['TITLE']} = $value[1]['VALUE'];
}

switch ( date( 'D' ) )
{
	case 'Mon':
		$today = 1;
		break;
	case 'Tue':
		$today = 2;
		break;
	case 'Wed':
		$today = 3;
		break;
	case 'Thu':
		$today = 4;
		break;
	case 'Fri':
		$today = 5;
		break;
	case 'Sat':
		$today = 6;
		break;
	case 'Sun':
		$today = 7;
		break;
}

$start = time() - ( $today - $START_DAY ) * 60 * 60 * 24;

if ( empty( $_REQUEST['start_date'] ) )
{
	$start_time = $start;

	$start_date = date( 'Y-m-d', $start_time );

	$end_date = date( 'Y-m-d', DBDate() );
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

	$begin_year = DBGetOne( "SELECT min(date_part('epoch',SCHOOL_DATE)) AS SCHOOL_DATE
		FROM ATTENDANCE_CALENDAR
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( is_null( $begin_year ) )
	{
		ErrorMessage( array( _( 'There are no calendars yet setup.' ) ), 'fatal' );
	}

	$date_select = '<option value="' . $start . '">' . ProperDate( date( 'Y-m-d', $start ) ) . ' - ' . ProperDate( DBDate() ) . '</option>';

	for ( $i = $start - ( 60 * 60 * 24 * 7 ); $i >= $begin_year; $i -= ( 60 * 60 * 24 * 7 ) )
	{
		$date_select .= '<option value="' . $i . '"' . (  ( $i + 86400 >= $start_time && $i - 86400 <= $start_time ) ? ' selected' : '' ) . '>' . ProperDate( date( 'Y-m-d', $i ) ) . ' - ' . ProperDate( date( 'Y-m-d', ( $i + 1 + (  ( $END_DAY - $START_DAY ) ) * 60 * 60 * 24 ) ) ) . '</option>';
	}

	$date_select = '<select name="start_date">' . $date_select . '</select>';

	DrawHeader( $date_select . ' ' . Buttons( _( 'Go' ) ) );

	echo '</form>';
}

$extra['SELECT'] = ",e.ELIGIBILITY_CODE,c.TITLE as COURSE_TITLE";
$extra['FROM'] = ",ELIGIBILITY e,COURSES c,COURSE_PERIODS cp";
$extra['WHERE'] = "AND e.STUDENT_ID=ssm.STUDENT_ID AND e.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.COURSE_ID=c.COURSE_ID AND e.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

$extra['functions'] = array( 'ELIGIBILITY_CODE' => '_makeLower' );
$extra['group'] = array( 'STUDENT_ID' );

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

	$columns = array(
		'FULL_NAME' => _( 'Student' ),
		'COURSE_TITLE' => _( 'Course' ),
		'ELIGIBILITY_CODE' => _( 'Grade' ),
	);

	ListOutput(
		$student_RET,
		$columns,
		'Student',
		'Students',
		array(),
		array( 'STUDENT_ID' => array( 'FULL_NAME', 'STUDENT_ID' ) )
	);
}

/**
 * @param $word
 */
function _makeLower( $word )
{
	return ucwords( mb_strtolower( $word ) );
}
