<?php
/**
 * Schedule Report
 *
 * Included in ScheduleReport.php
 *
 * @package RosarioSIS
 * @subpackage Scheduling
 */

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

if ( $_REQUEST['modfunc'] !== 'students' )
{
	DrawHeader( CheckBoxOnclick(
		'include_child_mps',
		_( 'Show Child Marking Period Details' )
	) );
}

// Check if Subject ID is valid for current school & syear!

if ( $_REQUEST['subject_id'] )
{
	$subject_RET = DBGet( "SELECT SUBJECT_ID
		FROM course_subjects
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'" );

	if ( ! $subject_RET )
	{
		// Unset modfunc & subject ID & course ID & course period ID & students & redirect URL.
		RedirectURL( [ 'modfunc', 'subject_id', 'course_id', 'course_period_id', 'students' ] );
	}
}

if ( $_REQUEST['subject_id'] )
{
	$subject_title = DBGetOne( "SELECT TITLE
		FROM course_subjects
		WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'" );

	$header = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&include_child_mps=' . $_REQUEST['include_child_mps'] ) . '">' . _( 'Top' ) . '</a>
		&rsaquo; <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=courses&subject_id=' . $_REQUEST['subject_id'] .
		'&include_child_mps=' . $_REQUEST['include_child_mps'] ) . '">' .
		$subject_title . '</a>';

	if ( $_REQUEST['course_id'] )
	{
		$location = 'courses';

		$course_RET = DBGet( "SELECT TITLE
			FROM courses
			WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'" );

		$course_link_url = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=students&subject_id=' . $_REQUEST['subject_id'] .
			'&course_id=' . $_REQUEST['course_id'] .
			'&include_child_mps=' . $_REQUEST['include_child_mps'];

		$header .= ' &rsaquo; <a href="' . URLEscape( $course_link_url ) . '">' .
			$course_RET[1]['TITLE'] . '</a>';

		$list_students = _( 'List Students' );

		if ( ! empty( $_REQUEST['students'] )
			&& empty( $_REQUEST['unscheduled'] ) )
		{
			// HTML Link is selected: bold.
			$list_students = '<b>' . $list_students . '</b>';
		}

		$list_unscheduled_students = _( 'List Unscheduled Students' );

		if ( ! empty( $_REQUEST['students'] )
			&& ! empty( $_REQUEST['unscheduled'] ) )
		{
			// HTML Link is selected: bold.
			$list_unscheduled_students = '<b>' . $list_unscheduled_students . '</b>';
		}

		$header2 = '<a href="' . URLEscape( $course_link_url . '&students=' . $location ) . '">' .
		$list_students . '</a> | <a href="' . URLEscape( $course_link_url .
		'&unscheduled=true&students=' . $location ) . '">' . $list_unscheduled_students . '</a>';

		DrawHeader( $header );

		DrawHeader( $header2 );
	}
	else
	{
		DrawHeader( $header );
	}
}

echo '</form>';

// SUBJECTS ----
$subject_RET = DBGet( "SELECT s.SUBJECT_ID,s.TITLE
	FROM course_subjects s
	WHERE s.SYEAR='" . UserSyear() . "'
	AND s.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY s.SORT_ORDER IS NULL,s.SORT_ORDER,s.TITLE" );

if ( ! empty( $subject_RET ) && $_REQUEST['subject_id'] )
{
	foreach ( (array) $subject_RET as $key => $value )
	{
		if ( $value['SUBJECT_ID'] == $_REQUEST['subject_id'] )
		{
			$subject_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
		}
	}
}

$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&modfunc=courses&include_child_mps=' . $_REQUEST['include_child_mps'];

$link['TITLE']['variables'] = [ 'subject_id' => 'SUBJECT_ID' ];

$LO_options = [
	'save' => false,
	'search' => false,
	'print' => false,
	'responsive' => false,
];

echo '<div class="st">';

ListOutput(
	$subject_RET,
	[ 'TITLE' => _( 'Subject' ) ],
	'Subject',
	'Subjects',
	$link,
	[],
	$LO_options
);

echo '</div>';

// Now, Course & Course periods Lists are responsive (multiple columns).
$LO_options['responsive'] = true;

// courses ----

if ( $_REQUEST['modfunc'] === 'courses'
	|| $_REQUEST['modfunc'] === 'course_periods'
	|| $_REQUEST['modfunc'] === 'students' )
{
	$_RET = DBGet( "SELECT c.COURSE_ID,c.TITLE,cp.TOTAL_SEATS,cp.COURSE_PERIOD_ID,
		cp.MARKING_PERIOD_ID,cp.MP,cp.CALENDAR_ID,
		(SELECT count(*) FROM schedule_requests sr WHERE sr.COURSE_ID=c.COURSE_ID) AS COUNT_REQUESTS
		FROM courses c,course_periods cp
		WHERE c.SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'
		AND c.COURSE_ID=cp.COURSE_ID
		AND c.SYEAR='" . UserSyear() . "'
		AND c.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY c.TITLE", [], [ 'COURSE_ID' ] );

	$RET = calcSeats( $_RET, [ 'COURSE_ID', 'TITLE', 'COUNT_REQUESTS' ] );

	if ( ! empty( $RET ) && $_REQUEST['course_id'] )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['COURSE_ID'] == $_REQUEST['course_id'] )
			{
				$RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=course_periods&subject_id=' . $_REQUEST['subject_id'] . '&include_child_mps=' . $_REQUEST['include_child_mps'];

	$link['TITLE']['variables'] = [ 'course_id' => 'COURSE_ID' ];

	$columns = [ 'TITLE' => _( 'Course' ), 'COUNT_REQUESTS' => _( 'Requests' ) ];

	if ( $_REQUEST['include_child_mps'] )
	{
		$OFT_string = mb_substr( _( 'Open' ), 0, 1 ) . '&#124;' . mb_substr( _( 'Filled' ), 0, 1 ) . '&#124;' . mb_substr( _( 'Total' ), 0, 1 );

		// FJ fix error Missing argument 1.

		foreach ( explode( ',', GetAllMP( '' ) ) as $mp )
		{
			$mp = trim( $mp, "'" );

			$columns += [ 'OFT_' . $mp => ( GetMP( $mp, 'SHORT_NAME' ) ?
				GetMP( $mp, 'SHORT_NAME' ) :
				GetMP( $mp ) ) . '<br />' . $OFT_string ];
		}
	}
	else
	{
		$columns += [ 'OPEN_SEATS' => _( 'Open' ), 'FILLED_SEATS' => _( 'Filled' ), 'TOTAL_SEATS' => _( 'Total' ) ];
	}

	echo '<div class="st">';

	ListOutput( $RET, $columns, 'Course', 'Courses', $link, [], $LO_options );

	echo '</div>';
}

// COURSE PERIODS ----

if ( $_REQUEST['modfunc'] === 'course_periods'
	|| isset( $_REQUEST['students'] ) && $_REQUEST['students'] === 'course_periods' )
{
	//FJ multiple school periods for a course period
	//$QI = DBQuery("SELECT COURSE_PERIOD_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,TOTAL_SEATS FROM course_periods cp WHERE COURSE_ID='".$_REQUEST['course_id']."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY (SELECT SORT_ORDER FROM school_periods WHERE PERIOD_ID=cp.PERIOD_ID),TITLE");
	$RET = DBGet( "SELECT COURSE_PERIOD_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,TOTAL_SEATS
		FROM course_periods cp
		WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SHORT_NAME,TITLE" );

	foreach ( (array) $RET as $key => $period )
	{
		$value = [];

		if ( $_REQUEST['include_child_mps'] )
		{
			$total_seats = $filled_seats = [];
		}
		else
		{
			$total_seats = $filled_seats = 0;
		}

		calcSeats1( $period, $total_seats, $filled_seats );

		if ( $_REQUEST['include_child_mps'] )
		{
			foreach ( (array) $total_seats as $mp => $total )
			{
				$value += [
					'OFT_' . $mp => ( $total !== false ?
						( $filled_seats[$mp] !== false ? $total - $filled_seats[$mp] : '' ) :
						_( 'N/A' ) ) . '|' . ( $filled_seats[$mp] !== false ? $filled_seats[$mp] : '' ) . '|' .
							( $total !== false ? $total : _( 'N/A' ) ),
				];
			}
		}
		else
		{
			$value += [
				'OPEN_SEATS' => ( $total_seats !== false ?
					( $filled_seats !== false ? $total_seats - $filled_seats : '' ) :
					_( 'N/A' ) ),
				'FILLED_SEATS' => ( $filled_seats !== false ? $filled_seats : '' ),
				'TOTAL_SEATS' => ( $total_seats !== false ? $total_seats : _( 'N/A' ) ),
			];
		}

		$RET[$key] += $value;
	}

	if ( ! empty( $RET ) && $_REQUEST['course_period_id'] )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['COURSE_PERIOD_ID'] == $_REQUEST['course_period_id'] )
			{
				$RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$link = [];

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=students&students=course_periods&subject_id=' . $_REQUEST['subject_id'] .
		'&course_id=' . $_REQUEST['course_id'] . '&include_child_mps=' . $_REQUEST['include_child_mps'];

	$link['TITLE']['variables'] = [ 'course_period_id' => 'COURSE_PERIOD_ID' ];

	$columns = [ 'TITLE' => _( 'Period' ) . ' ' . _( 'Days' ) . ' - ' . _( 'Short Name' ) . ' - ' . _( 'Teacher' ) ];

	if ( $_REQUEST['include_child_mps'] )
	{
		// FJ fix error Missing argument 1.

		foreach ( explode( ',', GetAllMP( '' ) ) as $mp )
		{
			$mp = trim( $mp, "'" );

			$OFT_string = mb_substr( _( 'Open' ), 0, 1 ) . '&#124;' . mb_substr( _( 'Filled' ), 0, 1 ) . '&#124;' . mb_substr( _( 'Total' ), 0, 1 );

			$columns += [
				'OFT_' . $mp => ( GetMP( $mp, 'SHORT_NAME' ) ? GetMP( $mp, 'SHORT_NAME' ) : GetMP( $mp ) ) .
					'<br />' . $OFT_string,
			];
		}
	}
	else
	{
		$columns += [
			'OPEN_SEATS' => _( 'Open' ),
			'FILLED_SEATS' => _( 'Filled' ),
			'TOTAL_SEATS' => _( 'Total' ),
		];
	}

	echo '<div class="st">';

	ListOutput( $RET, $columns, 'Course Period', 'Course Periods', $link, [], $LO_options );

	echo '</div>';
}

// LIST STUDENTS ----

if ( $_REQUEST['modfunc'] == 'students' )
{
	$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
		FROM custom_fields
		WHERE ID=200000004
		AND TYPE='date'", [], [ 'ID' ] );

	$sql_birthdate = '';

	$function_birthdate = $column_birthdate = [];

	if ( $custom_fields_RET['200000004'] )
	{
		$sql_birthdate = ',s.CUSTOM_200000004';

		$function_birthdate = [ 'CUSTOM_200000004' => 'ProperDate' ];

		$column_birthdate = [
			'CUSTOM_200000004' => ParseMLField( $custom_fields_RET['200000004'][1]['TITLE'] ),
		];
	}

	if ( ! empty( $_REQUEST['unscheduled'] ) )
	{
		$sql = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.STUDENT_ID" . $sql_birthdate . ",ssm.GRADE_ID
			FROM schedule_requests sr,students s,student_enrollment ssm
			WHERE (('" . DBDate() . "' BETWEEN ssm.START_DATE
			AND ssm.END_DATE OR ssm.END_DATE IS NULL))
			AND s.STUDENT_ID=sr.STUDENT_ID
			AND s.STUDENT_ID=ssm.STUDENT_ID
			AND ssm.SYEAR='" . UserSyear() . "'
			AND ssm.SCHOOL_ID='" . UserSchool() . "' ";

		if ( $_REQUEST['course_id'] )
		{
			$sql .= "AND sr.COURSE_ID='" . (int) $_REQUEST['course_id'] . "' ";
		}
		elseif ( $_REQUEST['subject_id'] )
		{
			$sql .= "AND sr.SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "' ";
		}

		$sql .= "AND NOT EXISTS (SELECT ''
			FROM schedule ss
			WHERE ss.COURSE_ID=sr.COURSE_ID
			AND ss.STUDENT_ID=sr.STUDENT_ID
			AND ('" . DBDate() . "' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL))";
	}
	else
	{
		$sql = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.STUDENT_ID" . $sql_birthdate . ",ssm.GRADE_ID
			FROM schedule ss,students s,student_enrollment ssm
			WHERE ('" . DBDate() . "' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL)
			AND (('" . DBDate() . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL))
			AND s.STUDENT_ID=ss.STUDENT_ID
			AND s.STUDENT_ID=ssm.STUDENT_ID
			AND ssm.SYEAR='" . UserSyear() . "'
			AND ssm.SCHOOL_ID='" . UserSchool() . "' ";

		if ( $_REQUEST['course_period_id'] )
		{
			$sql .= "AND ss.COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'";
		}
		elseif ( $_REQUEST['course_id'] )
		{
			$sql .= "AND ss.COURSE_ID='" . (int) $_REQUEST['course_id'] . "'";
		}
	}

	$sql .= ' ORDER BY FULL_NAME';

	$RET = DBGet( $sql, [ 'GRADE_ID' => 'GetGrade' ] + $function_birthdate );

	$link = [];

	if ( AllowUse( 'Scheduling/Schedule.php' ) )
	{
		$link['FULL_NAME']['link'] = "Modules.php?modname=Scheduling/Schedule.php";
		$link['FULL_NAME']['variables'] = [ 'student_id' => 'STUDENT_ID' ];
	}

	echo '<div style="clear: both;">';

	if ( ! empty( $_REQUEST['unscheduled'] ) )
	{
		ListOutput(
			$RET,
			[
				'FULL_NAME' => _( 'Student' ),
				'GRADE_ID' => _( 'Grade Level' ),
			] + $column_birthdate,
			'Unscheduled Student',
			'Unscheduled Students',
			$link,
			[],
			$LO_options
		);
	}
	else
	{
		ListOutput(
			$RET,
			[
				'FULL_NAME' => _( 'Student' ),
				'GRADE_ID' => _( 'Grade Level' ),
			] + $column_birthdate,
			'Student',
			'Students',
			$link,
			[],
			$LO_options
		);
	}
}

/**
 * @param $period
 * @param $total_seats
 * @param $filled_seats
 */
function calcSeats1( $period, &$total_seats, &$filled_seats )
{
	if ( $_REQUEST['include_child_mps'] )
	{
		$mps = GetChildrenMP( $period['MP'], $period['MARKING_PERIOD_ID'] );

		if ( $period['MP'] == 'FY' || $period['MP'] == 'SEM' )
		{
			$mps = "'" . $period['MARKING_PERIOD_ID'] . "'" . ( $mps ? ',' . $mps : '' );
		}
	}
	else
	{
		$mps = "'" . $period['MARKING_PERIOD_ID'] . "'";
	}

	foreach ( explode( ',', $mps ) as $mp )
	{
		$mp = trim( $mp, "'" );

		// Fix SQL error if MP was deleted.
		if ( ! GetMP( $mp, 'MP' ) )
		{
			// Skip MP, does not exist, silently fail?
			continue;
		}

		$seats = DBGet( "SELECT max((SELECT count(1)
			FROM schedule ss
			JOIN student_enrollment sem ON (sem.STUDENT_ID=ss.STUDENT_ID AND sem.SYEAR=ss.SYEAR)
			WHERE ss.COURSE_PERIOD_ID='" . (int) $period['COURSE_PERIOD_ID'] . "'
			AND (ss.MARKING_PERIOD_ID='" . (int) $mp . "'
				OR ss.MARKING_PERIOD_ID IN (" . GetAllMP( GetMP( $mp, 'MP' ), $mp ) . "))
			AND (ac.SCHOOL_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ac.SCHOOL_DATE<=ss.END_DATE))
			AND (ac.SCHOOL_DATE>=sem.START_DATE AND (sem.END_DATE IS NULL OR ac.SCHOOL_DATE<=sem.END_DATE)))) AS FILLED_SEATS
		FROM attendance_calendar ac
		WHERE ac.CALENDAR_ID='" . (int) $period['CALENDAR_ID'] . "'
		AND ac.SCHOOL_DATE BETWEEN " . db_case( [
			"(CURRENT_DATE>'" . GetMP( $mp, 'END_DATE' ) . "')",
			'TRUE',
			"'" . GetMP( $mp, 'START_DATE' ) . "'",
			'CURRENT_DATE',
		] ) . " AND '" . GetMP( $mp, 'END_DATE' ) . "'" );

		if ( $_REQUEST['include_child_mps'] )
		{
			if ( ! isset( $total_seats[$mp] ) || $total_seats[$mp] !== false )
			{
				if ( $period['TOTAL_SEATS'] )
				{
					if ( ! isset( $total_seats[$mp] ) )
					{
						$total_seats[$mp] = null;
					}

					$total_seats[$mp] += $period['TOTAL_SEATS'];
				}
				else
				{
					$total_seats[$mp] = false;
				}
			}

			if ( $filled_seats !== false )
			{
				if ( $seats[1]['FILLED_SEATS'] != '' )
				{
					if ( ! isset( $filled_seats[$mp] ) )
					{
						$filled_seats[$mp] = null;
					}

					$filled_seats[$mp] += $seats[1]['FILLED_SEATS'];
				}
				else
				{
					$filled_seats[$mp] = false;
				}
			}
		}
		else
		{
			if ( $total_seats !== false )
			{
				if ( $period['TOTAL_SEATS'] )
				{
					$total_seats += $period['TOTAL_SEATS'];
				}
				else
				{
					$total_seats = false;
				}
			}

			if ( $filled_seats !== false )
			{
				if ( $seats[1]['FILLED_SEATS'] != '' )
				{
					$filled_seats += $seats[1]['FILLED_SEATS'];
				}
				else
				{
					$filled_seats = false;
				}
			}
		}
	}
}

/**
 * @param $_RET
 * @param $columns
 * @return mixed
 */
function calcSeats( &$_RET, $columns )
{
	$RET = [ 0 => [] ];

	foreach ( (array) $_RET as $periods )
	{
		$value = [];

		foreach ( (array) $columns as $column )
		{
			$value += [ $column => $periods[key( $periods )][$column] ];
		}

		if ( $_REQUEST['include_child_mps'] )
		{
			$total_seats = $filled_seats = [];
		}
		else
		{
			$total_seats = $filled_seats = 0;
		}

		foreach ( (array) $periods as $period )
		{
			calcSeats1( $period, $total_seats, $filled_seats );
		}

		if ( $_REQUEST['include_child_mps'] )
		{
			foreach ( (array) $total_seats as $mp => $total )
			{
				$filled = $filled_seats[$mp];
				$value += [
					'OFT_' . $mp => ( $total !== false ?
						( $filled !== false ? $total - $filled : '' ) :
						_( 'N/A' ) ) . '|' . ( $filled !== false ? $filled : '' ) . '|' .
						( $total !== false ? $total : _( 'N/A' ) ),
				];
			}
		}
		else
		{
			$value += [
				'OPEN_SEATS' => ( $total_seats !== false ? ( $filled_seats !== false ? $total_seats - $filled_seats : '' ) : _( 'N/A' ) ),
				'FILLED_SEATS' => ( $filled_seats !== false ? $filled_seats : '' ),
				'TOTAL_SEATS' => ( $total_seats !== false ? $total_seats : _( 'N/A' ) ),
			];
		}

		$RET[] = $value;
	}

	unset( $RET[0] );

	return $RET;
}
