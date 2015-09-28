<?php

include( 'ProgramFunctions/_makeLetterGrade.fnc.php' );

DrawHeader( ProgramTitle() );

if ( !isset( $_REQUEST['assignment_id'] )
	|| empty( $_REQUEST['assignment_id'] ) )
	$_REQUEST['assignment_id'] = 'totals';

//FJ fix errors relation «course_weights» doesnt exist & columns c.grad_subject_id & cp.does_grades & cp.does_gpa do not exist
//$course_id = DBGet(DBQuery("SELECT c.GRAD_SUBJECT_ID,cp.COURSE_ID,cp.TITLE,c.TITLE AS COURSE_TITLE,c.SHORT_NAME AS COURSE_NUM,cw.CREDITS,cw.GPA_MULTIPLIER,cp.DOES_GRADES,cp.GRADE_SCALE_ID,cp.DOES_GPA as AFFECTS_GPA FROM COURSE_PERIODS cp,COURSES c,COURSE_WEIGHTS cw WHERE cw.COURSE_ID=cp.COURSE_ID AND cw.COURSE_WEIGHT=cp.COURSE_WEIGHT AND c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
$course_id = DBGet( DBQuery( "SELECT cp.COURSE_ID,cp.TITLE,c.TITLE AS COURSE_TITLE,c.SHORT_NAME AS COURSE_NUM,cp.GRADE_SCALE_ID
	FROM COURSE_PERIODS cp,COURSES c
	WHERE c.COURSE_ID=cp.COURSE_ID
	AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" ) );

if ( !isset( $course_id[1]['GRADE_SCALE_ID'] ) )
	ErrorMessage( array( _( 'This course is not graded.' ) ), 'fatal' );

$grade_scale_id = $course_id[1]['GRADE_SCALE_ID'];

$course_id = $course_id[1]['COURSE_ID'];

//FJ fix error column scale_id doesnt exist
//$grades_RET = DBGet(DBQuery("SELECT ID,TITLE FROM REPORT_CARD_GRADES WHERE SCALE_ID='".$grade_scale_id."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
$grades_RET = DBGet( DBQuery( "SELECT ID,TITLE,GPA_VALUE
	FROM REPORT_CARD_GRADES
	WHERE GRADE_SCALE_ID='" . $grade_scale_id . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER DESC" ) );

$grades = array();

foreach( (array)$grades_RET as $grade )
{
	$grades[] = array( 'TITLE' => $grade['TITLE'], 'GPA_VALUE' => $grade['GPA_VALUE'] );
}


//FJ fix error column USERNAME doesnt exist
//$config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USERNAME='".User('USERNAME')."' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
$config_RET = DBGet( DBQuery( "SELECT TITLE,VALUE
	FROM PROGRAM_USER_CONFIG
	WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
	AND PROGRAM='Gradebook'" ), array(), array( 'TITLE' ) );

if( count( $config_RET ) )
{
	foreach( (array)$config_RET as $title => $value )
		$programconfig[$title] = $value[1]['VALUE'];
}

$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE
	FROM GRADEBOOK_ASSIGNMENT_TYPES
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND COURSE_ID='" . $course_id . "'
	ORDER BY TITLE";

$types_RET = DBGet( DBQuery( $sql ) );

$assignments_RET = DBGet( DBQuery( "SELECT ASSIGNMENT_ID,TITLE,POINTS 
	FROM GRADEBOOK_ASSIGNMENTS 
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "' 
	AND ((COURSE_ID='" . $course_id . "' 
	AND STAFF_ID='".User('STAFF_ID')."') OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "') 
	AND MARKING_PERIOD_ID='" . UserMP() . "' 
	ORDER BY " . Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) . " DESC" ) );

$assignment_select = '<script>var assignment_idonchange = document.createElement("a"); assignment_idonchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&assignment_id="; assignment_idonchange.target = "body";</script>';

$assignment_select .= '<SELECT name="assignment_id" id="assignment_id" onchange="assignment_idonchange.href += this.options[selectedIndex].value; ajaxLink(assignment_idonchange);">';

$assignment_select .= '<OPTION value="totals"' . ( $_REQUEST['assignment_id'] === 'totals' ? ' SELECTED' : '' ) . '>' .
	_( 'Totals' ) .
'</OPTION>';

// Assignment Types
foreach( (array)$types_RET as $type )
{
	$selected = '';

	if ( $_REQUEST['assignment_id'] === ( 'totals' . $type['ASSIGNMENT_TYPE_ID'] ) )
	{
		$title = $type['TITLE'];

		$selected = ' SELECTED';
	}

	$assignment_select .= '<OPTION value="totals' . $type['ASSIGNMENT_TYPE_ID'] . '"' . $selected . '>' .
		$type['TITLE'] .
	'</OPTION>';

}

// Assignments
foreach( (array)$assignments_RET as $assignment )
{
	$selected = '';

	if ( $_REQUEST['assignment_id'] === $assignment['ASSIGNMENT_ID'] )
	{
		$title = $assignment['TITLE'];

		$selected = ' SELECTED';
	}

	$assignment_select .= '<OPTION value="' . $assignment['ASSIGNMENT_ID'] . '"'. $selected . '>' .
		$assignment['TITLE'] .
	'</OPTION>';
}

$assignment_select .= '</SELECT>';

$extra['SELECT_ONLY'] .= "ssm.STUDENT_ID,'' AS LETTER_GRADE";

$extra['functions'] = array( 'LETTER_GRADE' => '_makeGrade' );

// Totals
if ( $_REQUEST['assignment_id'] === 'totals' )
{
	$title = _( 'Grade' );

	$current_RET = DBGet( DBQuery( "SELECT g.STUDENT_ID,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'g.POINTS' ) ) . ") AS POINTS,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'a.POINTS' ) ) . ") AS TOTAL_POINTS 
		FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a 
		WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID 
		AND a.MARKING_PERIOD_ID='" . UserMP() . "' 
		AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' 
		AND (a.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' OR a.COURSE_ID='" . $course_id . "') 
		GROUP BY g.STUDENT_ID"), array(), array( 'STUDENT_ID' ) );

	if ( Preferences( 'WEIGHT', 'Gradebook' ) === 'Y' )
		$percent_RET = DBGet( DBQuery( "SELECT gt.ASSIGNMENT_TYPE_ID,gg.STUDENT_ID," .
			db_case(array(
				"sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . ")",
				"'0'",
				"'0'",
				"(sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ) ) . ")
					* gt.FINAL_GRADE_PERCENT / sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . "))"
			))." AS PARTIAL_PERCENT 
			FROM GRADEBOOK_GRADES gg, GRADEBOOK_ASSIGNMENTS ga, GRADEBOOK_ASSIGNMENT_TYPES gt 
			WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID 
			AND ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID 
			AND ga.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ") 
			AND gg.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' 
			AND gt.COURSE_ID='" . $course_id . "' 
			GROUP BY gg.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT"),
		array(),
		array( 'STUDENT_ID', 'ASSIGNMENT_TYPE_ID' ) );

	foreach( (array)$assignments_RET as $assignment )
		$total_points[$assignment['ASSIGNMENT_ID']] = $assignment['POINTS'];
}
// Assignment Type
elseif ( !is_numeric( $_REQUEST['assignment_id'] ) )
{
	$type_id = mb_substr( $_REQUEST['assignment_id'], 6 );

	$current_RET = DBGet( DBQuery( "SELECT g.STUDENT_ID,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'g.POINTS' ) ) . ") AS POINTS,
		sum(" . db_case( array( 'g.POINTS', "'-1'", "'0'", 'a.POINTS' ) ) . ") AS TOTAL_POINTS 
		FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a 
		WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID 
		AND a.MARKING_PERIOD_ID='" . UserMP() . "' 
		AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' 
		AND (a.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' OR a.COURSE_ID='" . $course_id . "') 
		AND a.ASSIGNMENT_TYPE_ID='" . $type_id . "' 
		GROUP BY g.STUDENT_ID" ), array(), array( 'STUDENT_ID' ) );

	if ( Preferences( 'WEIGHT', 'Gradebook' ) === 'Y' )
		$percent_RET = DBGet( DBQuery( "SELECT gt.ASSIGNMENT_TYPE_ID,gg.STUDENT_ID," .
			db_case( array(
				"sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . ")",
				"'0'",
				"'0'",
				"(sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ) ) . ")
					/ sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) ."))"
			) ) . " AS PARTIAL_PERCENT 
			FROM GRADEBOOK_GRADES gg, GRADEBOOK_ASSIGNMENTS ga, GRADEBOOK_ASSIGNMENT_TYPES gt 
			WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID 
			AND ga.ASSIGNMENT_TYPE_ID='" . $type_id . "' 
			AND ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID 
			AND ga.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ") 
			AND gg.COURSE_PERIOD_ID='" . UserCoursePeriod() . "' 
			AND gt.COURSE_ID='" . $course_id . "' 
			GROUP BY gg.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT"),
		array(),
		array( 'STUDENT_ID', 'ASSIGNMENT_TYPE_ID' ) );

	foreach( (array)$assignments_RET as $assignment )
		$total_points[$assignment['ASSIGNMENT_ID']] = $assignment['POINTS'];	
}
// Assignment
elseif ( $_REQUEST['assignment_id'] )
{
	$total_points = DBGet( DBQuery( "SELECT POINTS
		FROM GRADEBOOK_ASSIGNMENTS
		WHERE ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'" ) );

	$total_points = $total_points[1]['POINTS'];

	$current_RET = DBGet( DBQuery( "SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID
		FROM GRADEBOOK_GRADES
		WHERE ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'
		AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" ),
	array(),
	array( 'STUDENT_ID', 'ASSIGNMENT_ID' ) );
}

if ( !isset( $_REQUEST['chart_type'] ) )
	$_REQUEST['chart_type'] = 'column';
	
$stu_RET = GetStuList( $extra );

foreach( (array)$stu_RET as $stu )
	$RET[$stu['LETTER_GRADE']]++;

$chart['chart_data'][1] = array();

foreach( (array)$grades as $option )
{
	$chart['chart_data'][0][] = $option['GPA_VALUE'];

	$chart['chart_data'][1][] = ( empty( $RET[$option['TITLE']] ) ? 0 : $RET[$option['TITLE']] );

	// Add Grade Title (only to Pie Chart & List)
	$chart['chart_data'][2][] = $option['TITLE'];
}

if ( empty( $_REQUEST['modfunc'] ) )
{
	echo '<FORM action="' . PreparePHP_SELF( $_REQUEST, array( 'chart_type' ), array( 'chart_type' => $_REQUEST['chart_type'] ) ) . '" method="POST">';

	$RET = GetStuList();

	if ( !count( $RET ) )
		echo ErrorMessage( array( _( 'No Students were found.' ) ), 'fatal' );

	DrawHeader( $assignment_select . $advanced_link, SubmitButton( _( 'Go' ) ) );

	echo '<BR />';

	if ( $_REQUEST['assignment_id'] )
	{
		$tabs = array(
			array(
				'title' => _( 'Line' ),
				'link' => PreparePHP_SELF( $_REQUEST, array( 'chart_type' ), array( 'chart_type' => 'column' ) ),
			),
			array(
				'title' => _( 'Pie' ),
				'link' => PreparePHP_SELF( $_REQUEST, array( 'chart_type' ), array( 'chart_type' => 'pie' ) ),
			),
			array(
				'title' => _( 'List' ),
				'link' => PreparePHP_SELF( $_REQUEST, array( 'chart_type' ), array( 'chart_type' => 'list' ) ),
			)
		);

		$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $tabs );

//var_dump($chart['chart_data']);

		// List
		if ( $_REQUEST['chart_type'] === 'list' )
		{
			$chart_data = array( '0' => '' );

			foreach( (array)$chart['chart_data'][1] as $key => $value )
				$chart_data[] = array(
					'TITLE' => $chart['chart_data'][2][$key],
					'GPA' => $chart['chart_data'][0][$key],
					'VALUE' => $value
				);

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
?>
<script src="assets/js/jqplot/jquery.jqplot.min.js"></script>
<link rel="stylesheet" type="text/css" href="assets/js/jqplot/jquery.jqplot.min.css" />

<script src="assets/js/jquery.jqplottocolorbox.js"></script>

<script>
	var saveImgText = <?php echo json_encode( _( 'Right Click to Save Image As...' ) ); ?>;
	var chartTitle = <?php echo json_encode( sprintf( _( '%s Breakdown' ), $title ) ); ?>;

	/*FJ responsive labels: limit label to 20 char max.*/
	if (screen.width<768)
	{
		if (window.datapie)
			for(i=0; i<datapie.length; i++)
				datapie[i][0] = datapie[i][0].substr(0, 20);
	}
</script>

<div id="chart"></div>
<?php
			$jsData = '';

			if ( $_REQUEST['chart_type'] === 'column' )
			{
				$jsData = array();

				$chart_data_count = count( $chart['chart_data'][0] );

				for ( $i = 0; $i < $chart_data_count; $i++ )
				{
					$jsData[] = '[' . $chart['chart_data'][0][$i] . ', ' . $chart['chart_data'][1][$i] . ']';
				}

				$jsData = 'var datacolumn = [' . implode( ',', $jsData ) . "];\n";

?>
<script src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
<script>
	$(document).ready(function(){
		<?php echo $jsData . $jsDataTicks; ?>

		var plotcolumn = $.jqplot('chart', [datacolumn], {
			axesDefaults: {
				pad: 0 //start axes at 0
			},
			highlighter: {
				show: true,
				tooltipAxes: 'both',
				formatString:'<span style="font-size:larger;font-weight:bold;">%s; %s</span>',
			},
			title: chartTitle
		});

		jqplotToColorBox();
	});
</script>
<?php
			}
			else //pie chart
			{
				// Specific Pie Chart JS
				$jsData = 'var datapie = [';

				$chart_data_count = count( $chart['chart_data'][0] );

				for ( $i = 0; $i < $chart_data_count; $i++ )
				{
					//remove empty slices not to overload the legends
					if ( $chart['chart_data'][1][$i] > 0 )
						$jsData .= "[" . json_encode( $chart['chart_data'][2][$i] . ', ' . $chart['chart_data'][0][$i] ) . ", " .
							$chart['chart_data'][1][$i] . "],";
				}
				$jsData = mb_substr( $jsData, 0, -1 );

				$jsData .= "];\n";

?>		
<script src="assets/js/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
<script>
	$(document).ready(function(){ 
		<?php echo $jsData; ?>

		var plotpie = $.jqplot('chart', [datapie], {
			seriesDefaults:{
				renderer:$.jqplot.PieRenderer,
				rendererOptions: {
					showDataLabels: true,
				},
			},
			legend:{show:true},
			title: chartTitle
		});

		jqplotToColorBox();
	});
</script>
<?php
			}

			unset( $_REQUEST['_ROSARIO_PDF'] );
		}

		PopTable('footer');
	}

	echo '</FORM>';
}


/**
 * Make Letter Grade
 *
 * Local function
 *
 * @param  string $value  ''
 * @param  string $column 'LETTER_GRADE'
 *
 * @return string         Letter Grade
 */
function _makeGrade( $value, $column )
{
	global $THIS_RET,
		$total_points,
		$current_RET,
		$percent_RET;

	// Totals or Assignment Type
	if ( !is_numeric( $_REQUEST['assignment_id'] )
		&& !$_REQUEST['student_id'] )
	{
		if ( Preferences( 'WEIGHT', 'Gradebook' ) === 'Y'
			&& count( $percent_RET[$THIS_RET['STUDENT_ID']] ) )
		{
			$total = 0;

			foreach( (array)$percent_RET[$THIS_RET['STUDENT_ID']] as $type_id => $type )
				$total += $type[1]['PARTIAL_PERCENT'];
		}
		elseif( $current_RET[$THIS_RET['STUDENT_ID']][1]['TOTAL_POINTS'] )
		{
			$total = $current_RET[$THIS_RET['STUDENT_ID']][1]['POINTS'] / $current_RET[$THIS_RET['STUDENT_ID']][1]['TOTAL_POINTS'];
		}
		else
			$total = 0;

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
			return _( 'N/A' );
	}
}
