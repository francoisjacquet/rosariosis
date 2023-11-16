<?php

require_once 'ProgramFunctions/Charts.fnc.php';

DrawHeader( ProgramTitle() );

// Get all the MP's associated with the current MP
$all_mp_ids = explode( "','", trim( GetAllMP( 'PRO', UserMP() ), "'" ) );

if ( ! empty( $_REQUEST['mp_id'] )
	&& ! in_array( $_REQUEST['mp_id'], $all_mp_ids ) )
{
	// Requested MP not found, reset.
	RedirectURL( 'mp_id' );
}

if ( empty( $_REQUEST['mp_id'] ) )
{
	$_REQUEST['mp_id'] = UserMP();
}

$chart_types = [ 'line', 'bar', 'list' ];

// Set Chart Type.
if ( ! isset( $_REQUEST['chart_type'] )
	|| ! in_array( $_REQUEST['chart_type'], $chart_types ) )
{
	$_REQUEST['chart_type'] = 'line';
}

echo '<form action="' . URLEscape( 'Modules.php?modname='.$_REQUEST['modname'].'' ) . '" method="GET">';

$mp_select = '<select name="mp_id" id="mp_id" onchange="ajaxPostForm(this.form,true);">';

foreach ( (array) $all_mp_ids as $mp_id )
{
	if ( GetMP( $mp_id, 'DOES_GRADES' ) == 'Y' || $mp_id == UserMP() )
	{
		$mp_select .= '<option value="' . AttrEscape( $mp_id ) . '"' .
			( $mp_id == $_REQUEST['mp_id'] ? ' selected' : '' ) . '>' . GetMP( $mp_id ) . '</option>';

		if ( $mp_id === $_REQUEST['mp_id'] )
		{
			$user_mp_title = GetMP( $mp_id );
		}
	}
}

$mp_select .= '</select>
	<label for="mp_id" class="a11y-hidden">' . _( 'Marking Period' ) . '</label>';

DrawHeader( $mp_select );

echo '</form>';

$grouped_sql = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,s.STAFF_ID,g.REPORT_CARD_GRADE_ID
	FROM student_report_card_grades g,staff s,course_periods cp
	WHERE g.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND cp.TEACHER_ID=s.STAFF_ID
	AND cp.SYEAR=s.SYEAR
	AND cp.SYEAR=g.SYEAR
	AND cp.SYEAR='" . UserSyear() . "'
	AND g.REPORT_CARD_GRADE_ID IS NOT NULL
	AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp_id'] . "'
	ORDER BY FULL_NAME";

$grouped_RET = DBGet( $grouped_sql, [], [ 'STAFF_ID', 'REPORT_CARD_GRADE_ID' ] );

// @since 11.0 SQL select Grading Scales by Teacher, only the ones having student grades.
$grades_RET = [];

foreach ( (array) $grouped_RET as $staff_id => $grades )
{
	$report_card_grade_ids = array_keys( $grades );

	$grades_RET[ $staff_id ] = DBGet( "SELECT rg.ID,rg.TITLE,rg.GPA_VALUE
		FROM report_card_grades rg,report_card_grade_scales rs
		WHERE rg.SCHOOL_ID='" . UserSchool() . "'
		AND rg.SYEAR='" . UserSyear() . "'
		AND rs.ID=rg.GRADE_SCALE_ID
		AND rg.GRADE_SCALE_ID IN (SELECT GRADE_SCALE_ID
			FROM report_card_grades
			WHERE ID IN('" . implode( "','", $report_card_grade_ids ) . "'))
		ORDER BY rs.SORT_ORDER IS NULL,rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER IS NULL,rg.SORT_ORDER" );
}

// Chart.js charts.
if ( $grouped_RET )
{
	echo '<br />';

	$tabs = [
		[
			'title' => _( 'Line' ),
			'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'line' ] ),
		]
	];

	// Allow bar chart only if grades count <=42 (allows for Grading Scale from 0 to 20 with half points).
	$grades_count = 0;

	foreach ( $grades_RET as $staff_id => $grades )
	{
		if ( count( $grades ) > $grades_count )
		{
			$grades_count = count( $grades );
		}
	}

	if ( $grades_count <= 42 )
	{
		$tabs[] = [
			'title' => _( 'Column' ),
			'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'bar' ] ),
		];
	}

	$tabs[] = [
		'title' => _( 'List' ),
		'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'list' ] ),
	];

	$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

	PopTable( 'header', $tabs );

	// List.
	if ( $_REQUEST['chart_type'] === 'list' )
	{
		$LO_columns = [ 'GRADES' => _( 'Grades' ) ];

		$teachers_RET = [];

		foreach ( (array) $grades_RET as $staff_id => $grades )
		{
			foreach ( (array) $grades as $grade )
			{
				$teachers_RET[ $grade['ID'] ]['GRADES'] = $grade['TITLE'];
			}
		}

		foreach ( (array) $grouped_RET as $staff_id => $grades )
		{
			$LO_columns[ $staff_id ] = $grades[key( $grades )][1]['FULL_NAME'];

			foreach ( (array) $grades_RET[ $staff_id ] as $grade )
			{
				$teachers_RET[ $grade['ID'] ][ $staff_id ] = empty( $grades[$grade['ID']] ) ?
					0 : count( $grades[$grade['ID']] );
			}
		}

		// Reset $teachers_RET array keys.
		$teachers_RET = array_values( $teachers_RET );

		// Start with key 1 for ListOutput().
		array_unshift( $teachers_RET, 'dummy' );
		unset( $teachers_RET[0] );

		$LO_options['responsive'] = false;

		ListOutput( $teachers_RET, $LO_columns, 'Grade', 'Grades', [], [], $LO_options );
	}
	// Chart.js charts.
	else
	{
		foreach ( (array) $grouped_RET as $staff_id => $grades )
		{
			$chart_data = [];

			$chart_title = $grades[key($grades)][1]['FULL_NAME'] . ' - ' . $user_mp_title . ' - ' . _( 'Grade Breakdown' );

			foreach ( (array) $grades_RET[ $staff_id ] as $grade )
			{
				if ( $_REQUEST['chart_type'] === 'bar' )
				{
					$chart_data[0][] = $grade['TITLE'];
				}
				else
				{
					$chart_data[0][] = $grade['GPA_VALUE'];
				}

				$chart_data[1][] = empty( $grades[$grade['ID']] ) ?
					0 : count( $grades[$grade['ID']] );
			}

			echo ChartjsChart(
				$_REQUEST['chart_type'],
				$chart_data,
				$chart_title
			);

			echo '<br />';
		}
	}

	PopTable( 'footer' );
}
else
{
	echo '<br /><div class="center"><b>' . sprintf(
		_( 'No %s were found.' ),
		mb_strtolower( ngettext( 'Teacher', 'Teachers', 0 ) )
	) . '</div></b>';
}
