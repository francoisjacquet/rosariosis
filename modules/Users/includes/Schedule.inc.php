<?php

$_REQUEST['category_id'] = '2';
require_once 'modules/Users/includes/Other_Info.inc.php';

$_REQUEST['all_schools'] = issetVal( $_REQUEST['all_schools'] );

if ( GetTeacher( UserStaffID(), 'PROFILE', false ) === 'teacher' )
{
	if ( $PopTable_opened )
	{
		PopTable( 'footer' );
	}

	$all_schools_onclick_URL = "'" . ( $_REQUEST['all_schools'] == 'Y' ?
		PreparePHP_SELF( $_REQUEST, [], [ 'all_schools' => '' ] ) :
		PreparePHP_SELF( $_REQUEST, [], [ 'all_schools' => 'Y' ] ) ) . "'";

	$input_all_schools = '<input type="checkbox" name="all_schools" value="Y" onclick="ajaxLink(' . $all_schools_onclick_URL . ');"' . ( $_REQUEST['all_schools'] == 'Y' ? 'checked' : '' ) . ' />';

	DrawHeader( '<label>' . $input_all_schools . ' ' . _( 'List Courses For All Schools' ) . '</label>' );

	if ( $_REQUEST['all_schools'] == 'Y' )
	{
		// Preload GetMP cache with all schools.
		$_ROSARIO['GetMP'] = DBGet( "SELECT MARKING_PERIOD_ID,TITLE,POST_START_DATE,POST_END_DATE,
			MP,SORT_ORDER,SHORT_NAME,START_DATE,END_DATE,DOES_GRADES,DOES_COMMENTS
			FROM SCHOOL_MARKING_PERIODS
			WHERE SYEAR='" . UserSyear() . "'",
			[],
			[ 'MARKING_PERIOD_ID' ]
		);
	}

	$columns = [
		'TITLE' => _( 'Course' ),
		'COURSE_PERIOD' => _( 'Course Period' ),
		'ROOM' => _( 'Room' ),
		'MARKING_PERIOD_ID' => _( 'Marking Period' ),
	];

	$group = [];

	if ( $_REQUEST['all_schools'] == 'Y' )
	{
		$columns += [ 'SCHOOL' => _( 'School' ) ];
		$group = [ 'SCHOOL_ID' ];
	}

	/*$schedule_RET = DBGet( "SELECT cp.PERIOD_ID,cp.ROOM,c.TITLE,cp.MARKING_PERIOD_ID,cp.SCHOOL_ID,s.TITLE AS SCHOOL FROM COURSE_PERIODS cp,COURSES c,SCHOOLS s WHERE cp.COURSE_ID=c.COURSE_ID AND cp.TEACHER_ID='".UserStaffID()."' AND cp.SYEAR='".UserSyear()."'".($_REQUEST['all_schools']=='Y'?'':" AND cp.SCHOOL_ID='".UserSchool()."'")." AND s.ID=cp.SCHOOL_ID AND s.SYEAR=cp.SYEAR ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID)",array('PERIOD_ID' => 'GetPeriod','MARKING_PERIOD_ID' => 'GetMP'),$group);*/
	$schedule_RET = DBGet( "SELECT cp.TITLE AS COURSE_PERIOD,cp.ROOM,c.TITLE,cp.MARKING_PERIOD_ID,cp.SCHOOL_ID,s.TITLE AS SCHOOL
	FROM COURSE_PERIODS cp,COURSES c,SCHOOLS s
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND cp.TEACHER_ID='" . UserStaffID() . "'
	AND cp.SYEAR='" . UserSyear() . "'" .
		( $_REQUEST['all_schools'] == 'Y' ? '' : " AND cp.SCHOOL_ID='" . UserSchool() . "'" ) . "
	AND s.ID=cp.SCHOOL_ID
	AND s.SYEAR=cp.SYEAR
	ORDER BY cp.SHORT_NAME,cp.TITLE", [ 'MARKING_PERIOD_ID' => 'GetMP' ], $group );

	if ( $_REQUEST['all_schools'] == 'Y' )
	{
		ListOutput(
			$schedule_RET,
			$columns,
			_( 'School' ),
			_( 'Schools' ),
			false,
			$group
		);
	}
	else
	{
		ListOutput(
			$schedule_RET,
			$columns,
			'Course Period',
			'Course Periods',
			false,
			$group
		);
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		echo '<div style="page-break-after: always;"></div>';

		$_SESSION['orientation'] = 'landscape';
	}
	else
	{
		echo '<hr />';
	}

	if ( ! UserMP() )
	{
		// Fix SQL error when no quarters MP are setup yet.
		ErrorMessage( [ _( 'No quarters found' ) ], 'fatal' );
	}

	$schedule_table_days = [
		'U' => false,
		'M' => false,
		'T' => false,
		'W' => false,
		'H' => false,
		'F' => false,
		'S' => false,
	];
	//FJ days display to locale
	$days_convert = [
		'U' => _( 'Sunday' ),
		'M' => _( 'Monday' ),
		'T' => _( 'Tuesday' ),
		'W' => _( 'Wednesday' ),
		'H' => _( 'Thursday' ),
		'F' => _( 'Friday' ),
		'S' => _( 'Saturday' ),
	];
	//FJ days numbered

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$days_convert = [
			'U' => _( 'Day' ) . ' 7',
			'M' => _( 'Day' ) . ' 1',
			'T' => _( 'Day' ) . ' 2',
			'W' => _( 'Day' ) . ' 3',
			'H' => _( 'Day' ) . ' 4',
			'F' => _( 'Day' ) . ' 5',
			'S' => _( 'Day' ) . ' 6',
		];
	}

	$schedule_table_RET = DBGet( "SELECT cp.ROOM,cp.SHORT_NAME,c.TITLE,sp.TITLE AS SCHOOL_PERIOD,cpsp.DAYS
	FROM COURSE_PERIODS cp,COURSES c,SCHOOLS s,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND cp.TEACHER_ID='" . UserStaffID() . "'
	AND cp.SYEAR='" . UserSyear() . "'
	AND s.ID=cp.SCHOOL_ID
	AND s.ID='" . UserSchool() . "'
	AND s.SYEAR=cp.SYEAR
	AND sp.PERIOD_ID=cpsp.PERIOD_ID
	AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND cp.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
	AND sp.LENGTH<=" . ( Config( 'ATTENDANCE_FULL_DAY_MINUTES' ) / 2 ) . "
	ORDER BY sp.SORT_ORDER", [ 'DAYS' => '_GetDays' ], [ 'SCHOOL_PERIOD' ] );
	// FJ note the "sp.LENGTH<=(Config('ATTENDANCE_FULL_DAY_MINUTES') / 2)" condition to remove Full Day school periods from the schedule table!

	$columns = [ 'SCHOOL_PERIOD' => _( 'Periods' ) ];

	foreach ( $schedule_table_days as $day => $true )
	{
		if ( $true )
		{
			$columns[$day] = $days_convert[$day];
		}
	}

	$schedule_table_RET = _schedule_table_RET( $schedule_table_RET );

	ListOutput(
		$schedule_table_RET,
		$columns,
		'Period',
		'Periods',
		false,
		[],
		[ 'save' => false ]
	);

	if ( $PopTable_opened )
	{
		echo '<table><tr><td>';
	}
}

//FJ add schedule table
/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _GetDays( $value, $column )
{
	global $schedule_table_days;

	$days_array = str_split( $value );

	foreach ( $days_array as $day )
	{
		$schedule_table_days[$day] = true;
	}

	return $days_array;
}

/**
 * @param $schedule_table_RET
 * @return mixed
 */
function _schedule_table_RET( $schedule_table_RET )
{
	$schedule_table_body = [];
	$i = 1;

	foreach ( (array) $schedule_table_RET as $period => $course_periods )
	{
		$schedule_table_body[$i]['SCHOOL_PERIOD'] = $period;

		foreach ( $course_periods as $course_period )
		{
			foreach ( $course_period['DAYS'] as $course_period_day )
			{
				if ( empty( $schedule_table_body[$i][$course_period_day] ) )
				{
					$schedule_table_body[$i][$course_period_day] = [];
				}

				$schedule_table_body[$i][$course_period_day][] = '<div style="display:table-cell;">' . $course_period['TITLE'] . ' ' . ( empty( $course_period['SHORT_NAME'] ) ? '' : '<span style="font-size:smaller;">(' . $course_period['SHORT_NAME'] ) . ')' . ( empty( $course_period['ROOM'] ) ? '' : ' ' . _( 'Room' ) . ': ' . $course_period['ROOM'] . '</span>' ) . '&nbsp;</div>';
			}
		}

		$j = 0;

		foreach ( $schedule_table_body[$i] as $day_key => $schedule_table_day )
		{
			$j++;

			if ( $j == 1 ) // skip SCHOOL_PERIOD column
			{
				continue;
			}

			if ( count( $schedule_table_day ) == 1 )
			{
				$schedule_table_body[$i][$day_key] = str_replace( [ '<div style="display:table-cell;">', '</div>' ], '', $schedule_table_day[0] );
			}
			else
			{
				$schedule_table_body[$i][$day_key] = implode( $schedule_table_day );
			}
		}

		$i++;
	}

	return $schedule_table_body;
}
