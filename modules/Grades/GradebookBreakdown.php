<?php

require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

require_once 'ProgramFunctions/Charts.fnc.php';

DrawHeader( ProgramTitle() );

// Set Assignment ID

if ( ! isset( $_REQUEST['assignment_id'] )
	|| empty( $_REQUEST['assignment_id'] ) )
{
	$_REQUEST['assignment_id'] = 'totals';
}

$chart_types = array( 'line', 'pie', 'list' );

// set Chart Type

if ( ! isset( $_REQUEST['chart_type'] )
	|| ! in_array( $_REQUEST['chart_type'], $chart_types ) )
{
	$_REQUEST['chart_type'] = 'line';
}

//FJ fix errors relation «course_weights» doesnt exist & columns c.grad_subject_id & cp.does_grades & cp.does_gpa do not exist
//$course_id = DBGet( "SELECT c.GRAD_SUBJECT_ID,cp.COURSE_ID,cp.TITLE,c.TITLE AS COURSE_TITLE,c.SHORT_NAME AS COURSE_NUM,cw.CREDITS,cw.GPA_MULTIPLIER,cp.DOES_GRADES,cp.GRADE_SCALE_ID,cp.DOES_GPA as AFFECTS_GPA FROM COURSE_PERIODS cp,COURSES c,COURSE_WEIGHTS cw WHERE cw.COURSE_ID=cp.COURSE_ID AND cw.COURSE_WEIGHT=cp.COURSE_WEIGHT AND c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."'" );
$course_id = DBGet( "SELECT cp.COURSE_ID,cp.TITLE,c.TITLE AS COURSE_TITLE,c.SHORT_NAME AS COURSE_NUM,cp.GRADE_SCALE_ID
	FROM COURSE_PERIODS cp,COURSES c
	WHERE c.COURSE_ID=cp.COURSE_ID
	AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

if ( ! isset( $course_id[1]['GRADE_SCALE_ID'] ) )
{
	ErrorMessage( array( _( 'This course is not graded.' ) ), 'fatal' );
}

$grade_scale_id = $course_id[1]['GRADE_SCALE_ID'];

$course_id = $course_id[1]['COURSE_ID'];

//FJ fix error column scale_id doesnt exist
//$grades_RET = DBGet( "SELECT ID,TITLE FROM REPORT_CARD_GRADES WHERE SCALE_ID='".$grade_scale_id."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER" );
$grades_RET = DBGet( "SELECT ID,TITLE,GPA_VALUE
	FROM REPORT_CARD_GRADES
	WHERE GRADE_SCALE_ID='" . $grade_scale_id . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER DESC" );

$grades = array();

foreach ( (array) $grades_RET as $grade )
{
	$grades[] = array( 'TITLE' => $grade['TITLE'], 'GPA_VALUE' => $grade['GPA_VALUE'] );
}

$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE
	FROM GRADEBOOK_ASSIGNMENT_TYPES
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND COURSE_ID='" . $course_id . "'
	ORDER BY TITLE";

$types_RET = DBGet( $sql );

$assignments_RET = DBGet( "SELECT ASSIGNMENT_ID,TITLE,POINTS
	FROM GRADEBOOK_ASSIGNMENTS
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND ((COURSE_ID='" . $course_id . "'
	AND STAFF_ID='" . User( 'STAFF_ID' ) . "') OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
	AND MARKING_PERIOD_ID='" . UserMP() . "'
	ORDER BY " . Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) . " DESC" );

$assignment_select .= '<select name="assignment_id" id="assignment_id" onchange="ajaxPostForm(this.form, true)">';

$assignment_select .= '<option value="totals"' . ( $_REQUEST['assignment_id'] === 'totals' ? ' selected' : '' ) . '>' .
_( 'Totals' ) .
	'</option>';

// Assignment Types

foreach ( (array) $types_RET as $type )
{
	$selected = '';

	if ( $_REQUEST['assignment_id'] === ( 'totals' . $type['ASSIGNMENT_TYPE_ID'] ) )
	{
		$title = $type['TITLE'];

		$selected = ' selected';
	}

	$assignment_select .= '<option value="totals' . $type['ASSIGNMENT_TYPE_ID'] . '"' . $selected . '>' .
		$type['TITLE'] .
		'</option>';
}

// Assignments

foreach ( (array) $assignments_RET as $assignment )
{
	$selected = '';

	if ( $_REQUEST['assignment_id'] === $assignment['ASSIGNMENT_ID'] )
	{
		$title = $assignment['TITLE'];

		$selected = ' selected';
	}

	$assignment_select .= '<option value="' . $assignment['ASSIGNMENT_ID'] . '"' . $selected . '>' .
		$assignment['TITLE'] .
		'</option>';
}

$assignment_select .= '</select>
	<label for="assignment_id" class="a11y-hidden">' . _( 'Assignments' ) . '</label>';

$extra['SELECT_ONLY'] .= "ssm.STUDENT_ID,'' AS LETTER_GRADE";

$extra['functions'] = array( 'LETTER_GRADE' => '_makeGrade' );

// Totals

if ( $_REQUEST['assignment_id'] === 'totals' )
{
	$title = _( 'Grade' );

	$current_RET = DBGet( "SELECT g.STUDENT_ID,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'g.POINTS' ) ) . ") AS POINTS,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'a.POINTS' ) ) . ") AS TOTAL_POINTS
		FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a
		WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
		AND a.MARKING_PERIOD_ID='" . UserMP() . "'
		AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		AND (a.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' OR a.COURSE_ID='" . $course_id . "')
		GROUP BY g.STUDENT_ID", array(), array( 'STUDENT_ID' ) );

	if ( Preferences( 'WEIGHT', 'Gradebook' ) === 'Y' )
	{
		$percent_RET = DBGet( "SELECT gt.ASSIGNMENT_TYPE_ID,gg.STUDENT_ID," .
			db_case( array(
				"sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . ")",
				"'0'",
				"'0'",
				"(sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ) ) . ")
					* gt.FINAL_GRADE_PERCENT / sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . "))",
			) ) . " AS PARTIAL_PERCENT
			FROM GRADEBOOK_GRADES gg, GRADEBOOK_ASSIGNMENTS ga, GRADEBOOK_ASSIGNMENT_TYPES gt
			WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
			AND ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID
			AND ga.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
			AND gg.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
			AND gt.COURSE_ID='" . $course_id . "'
			GROUP BY gg.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT",
			array(),
			array( 'STUDENT_ID', 'ASSIGNMENT_TYPE_ID' ) );
	}

	foreach ( (array) $assignments_RET as $assignment )
	{
		$total_points[$assignment['ASSIGNMENT_ID']] = $assignment['POINTS'];
	}
}

// Assignment Type
elseif ( ! is_numeric( $_REQUEST['assignment_id'] ) )
{
	$type_id = mb_substr( $_REQUEST['assignment_id'], 6 );

	$current_RET = DBGet( "SELECT g.STUDENT_ID,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'g.POINTS' ) ) . ") AS POINTS,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'a.POINTS' ) ) . ") AS TOTAL_POINTS
		FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a
		WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
		AND a.MARKING_PERIOD_ID='" . UserMP() . "'
		AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		AND (a.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' OR a.COURSE_ID='" . $course_id . "')
		AND a.ASSIGNMENT_TYPE_ID='" . $type_id . "'
		GROUP BY g.STUDENT_ID", array(), array( 'STUDENT_ID' ) );

	if ( Preferences( 'WEIGHT', 'Gradebook' ) === 'Y' )
	{
		$percent_RET = DBGet( "SELECT gt.ASSIGNMENT_TYPE_ID,gg.STUDENT_ID," .
			db_case( array(
				"sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . ")",
				"'0'",
				"'0'",
				"(sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ) ) . ")
					/ sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . "))",
			) ) . " AS PARTIAL_PERCENT
			FROM GRADEBOOK_GRADES gg, GRADEBOOK_ASSIGNMENTS ga, GRADEBOOK_ASSIGNMENT_TYPES gt
			WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
			AND ga.ASSIGNMENT_TYPE_ID='" . $type_id . "'
			AND ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID
			AND ga.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
			AND gg.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
			AND gt.COURSE_ID='" . $course_id . "'
			GROUP BY gg.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT",
			array(),
			array( 'STUDENT_ID', 'ASSIGNMENT_TYPE_ID' ) );
	}

	foreach ( (array) $assignments_RET as $assignment )
	{
		$total_points[$assignment['ASSIGNMENT_ID']] = $assignment['POINTS'];
	}
}

// Assignment
elseif ( ! empty( $_REQUEST['assignment_id'] ) )
{
	$total_points = DBGetOne( "SELECT POINTS
		FROM GRADEBOOK_ASSIGNMENTS
		WHERE ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'" );

	$current_RET = DBGet( "SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID
		FROM GRADEBOOK_GRADES
		WHERE ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'
		AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'",
		array(),
		array( 'STUDENT_ID', 'ASSIGNMENT_ID' ) );
}

$stu_RET = GetStuList( $extra );

foreach ( (array) $stu_RET as $stu )
{
	$RET[$stu['LETTER_GRADE']]++;
}

$chart['chart_data'][1] = array();

foreach ( (array) $grades as $option )
{
	$chart['chart_data'][0][] = $option['GPA_VALUE'];

	$chart['chart_data'][1][] = ( empty( $RET[$option['TITLE']] ) ? 0 : $RET[$option['TITLE']] );

	// Add Grade Title (only to Pie Chart & List)
	$chart['chart_data'][2][] = $option['TITLE'];
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="GET">';

	$RET = GetStuList();

	if ( empty( $RET ) )
	{
		echo ErrorMessage( array( _( 'No Students were found.' ) ), 'fatal' );
	}

	DrawHeader( $assignment_select, SubmitButton( _( 'Go' ) ) );

	echo '<br />';

	if ( ! empty( $_REQUEST['assignment_id'] ) )
	{
		$tabs = array(
			array(
				'title' => _( 'Line' ),
				'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'line' ) ),
			),
			array(
				'title' => _( 'Pie' ),
				'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'pie' ) ),
			),
			array(
				'title' => _( 'List' ),
				'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'list' ) ),
			),
		);

		$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $tabs );

//var_dump($chart['chart_data']);

		// List

		if ( $_REQUEST['chart_type'] === 'list' )
		{
			$chart_data = array( '0' => '' );

			foreach ( (array) $chart['chart_data'][1] as $key => $y )
			{
				$chart_data[] = array(
					'TITLE' => $chart['chart_data'][2][$key],
					'GPA' => $chart['chart_data'][0][$key],
					'VALUE' => $y,
				);
			}

			unset( $chart_data[0] );

			$LO_options['responsive'] = false;

			$LO_columns = array(
				'TITLE' => _( 'Title' ),
				'GPA' => _( 'GPA Value' ),
				'VALUE' => _( 'Number of Students' ),
			);

			ListOutput( $chart_data, $LO_columns, 'Grade', 'Grades', array(), array(), $LO_options );
		}

		//FJ jqplot charts
		else
		{
			$chartTitle = sprintf( _( '%s Breakdown' ), $title );

			if ( $_REQUEST['chart_type'] === 'line' )
			{
				echo jqPlotChart( 'line', $chart['chart_data'], $chartTitle );
			}
			else //pie chart
			{
				$chartData = array();

				foreach ( (array) $chart['chart_data'][0] as $i => $x )
				{
					//remove empty slices not to overload the legends

					if ( $chart['chart_data'][1][$i] > 0 )
					{
						$chartData[0][] = $chart['chart_data'][2][$i] . ', ' . $x;

						$chartData[1][] = $chart['chart_data'][1][$i];
					}
				}

				echo jqPlotChart( 'pie', $chartData, $chartTitle );
			}

			unset( $_REQUEST['_ROSARIO_PDF'] );
		}

		PopTable( 'footer' );
	}

	echo '</form>';
}

/**
 * Make Letter Grade
 *
 * Local function
 *
 * @param  string $value  ''
 * @param  string $column 'LETTER_GRADE'
 * @return string Letter Grade
 */
function _makeGrade( $value, $column )
{
	global $THIS_RET,
	$total_points,
	$current_RET,
		$percent_RET;

	// Totals or Assignment Type

	if ( ! is_numeric( $_REQUEST['assignment_id'] )
		&& ! $_REQUEST['student_id'] )
	{
		if ( Preferences( 'WEIGHT', 'Gradebook' ) === 'Y'
			&& ! empty( $percent_RET[$THIS_RET['STUDENT_ID']] ) )
		{
			$total = 0;

			foreach ( (array) $percent_RET[$THIS_RET['STUDENT_ID']] as $type_id => $type )
			{
				$total += $type[1]['PARTIAL_PERCENT'];
			}
		}
		elseif ( $current_RET[$THIS_RET['STUDENT_ID']][1]['TOTAL_POINTS'] )
		{
			$total = $current_RET[$THIS_RET['STUDENT_ID']][1]['POINTS'] / $current_RET[$THIS_RET['STUDENT_ID']][1]['TOTAL_POINTS'];
		}
		else
		{
			$total = 0;
		}

		return _makeLetterGrade( $total, UserCoursePeriod() );
	}

	// Assignment
	else
	{
		// Not Excused, Not Extra Credit

		if ( $current_RET[$THIS_RET['STUDENT_ID']][$_REQUEST['assignment_id']][1]['POINTS'] !== '*'
			&& $total_points )
		{
			return _makeLetterGrade(
				$current_RET[$THIS_RET['STUDENT_ID']][$_REQUEST['assignment_id']][1]['POINTS'] / $total_points,
				UserCoursePeriod()
			);
		}
		else
		{
			return _( 'N/A' );
		}
	}
}
