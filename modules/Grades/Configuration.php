<?php

DrawHeader( _( 'Gradebook' ) . ' - ' . ProgramTitle() );

if ( ! empty( $_REQUEST['values'] ) )
{
	ProgramUserConfig(
		'Gradebook',
		0,
		$_REQUEST['values']
	);

	$note[] = button( 'check' ) . '&nbsp;' . _( 'The gradebook configuration has been modified.' );
}

echo ErrorMessage( $note, 'note' );

$gradebook_config = ProgramUserConfig( 'Gradebook' );

$grades = DBGet( "SELECT cp.TITLE AS CP_TITLE,c.TITLE AS COURSE_TITLE,cp.COURSE_PERIOD_ID,rcg.TITLE,rcg.ID
FROM REPORT_CARD_GRADES rcg,COURSE_PERIODS cp,COURSES c
WHERE cp.COURSE_ID=c.COURSE_ID
AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
AND cp.SCHOOL_ID=rcg.SCHOOL_ID
AND cp.SYEAR=rcg.SYEAR
AND cp.SYEAR='" . UserSyear() . "'
AND rcg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
AND cp.GRADE_SCALE_ID IS NOT NULL
AND DOES_BREAKOFF='Y'
ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER DESC", array(), array( 'COURSE_PERIOD_ID' ) );

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

DrawHeader( '', Buttons( _( 'Save' ) ) );

echo '<br />';

PopTable( 'header', _( 'Configuration' ) );

echo '<fieldset>';
//FJ add translation
//FJ css WPadmin
echo '<legend>' . _( 'Assignments' ) . '</legend>';
echo '<table>';

if ( count( $grades ) )
{
	//if ( ! $gradebook_config['ROUNDING'])
	//	$gradebook_config['ROUNDING'] = 'NORMAL';
	//FJ add <label> on radio
	echo '<tr><td><table><tr><td colspan="4"><b>' . _( 'Score Rounding' ) . '</b></td></tr><tr><td><label><input type="radio" name="values[ROUNDING]" value=UP' . (  ( $gradebook_config['ROUNDING'] == 'UP' ) ? ' checked' : '' ) . '>&nbsp;' . _( 'Up' ) . '</label></td><td><label><input type="radio" name="values[ROUNDING]" value=DOWN' . (  ( $gradebook_config['ROUNDING'] == 'DOWN' ) ? ' checked' : '' ) . '>&nbsp;' . _( 'Down' ) . '</label></td><td><label><input type="radio" name="values[ROUNDING]" value="NORMAL"' . (  ( $gradebook_config['ROUNDING'] == 'NORMAL' ) ? ' checked' : '' ) . '>&nbsp;' . _( 'Normal' ) . '</label></td><td><label><input type="radio" name="values[ROUNDING]" value="' . (  ( $gradebook_config['ROUNDING'] == '' ) ? ' checked' : '' ) . '">&nbsp;' . _( 'None' ) . '</label></td></tr></table></td></tr>';
}

if ( empty( $gradebook_config['ASSIGNMENT_SORTING'] ) )
{
	$gradebook_config['ASSIGNMENT_SORTING'] = 'ASSIGNMENT_ID';
}

echo '<tr><td><table><tr><td colspan="3"><b>' . _( 'Assignment Sorting' ) .
'</b></td></tr><tr class="st"><td><label><input type="radio" name="values[ASSIGNMENT_SORTING]" value="ASSIGNMENT_ID"' .
( $gradebook_config['ASSIGNMENT_SORTING'] == 'ASSIGNMENT_ID' ? ' checked' : '' ) . '>&nbsp;' .
_( 'Newest First' ) .
'</label></td><td><label><input type="radio" name="values[ASSIGNMENT_SORTING]" value="DUE_DATE"' .
(  $gradebook_config['ASSIGNMENT_SORTING'] == 'DUE_DATE' ? ' checked' : '' ) . '>&nbsp;' .
_( 'Due Date' ) .
'</label></td><td><label><input type="radio" name="values[ASSIGNMENT_SORTING]" value=ASSIGNED_DATE' .
( $gradebook_config['ASSIGNMENT_SORTING'] == 'ASSIGNED_DATE' ? ' checked' : '' ) . '>&nbsp;' .
_( 'Assigned Date' ) . '</label></td></tr></table></td></tr>';

//FJ add <label> on checkbox
echo '<tr><td><label><input type="checkbox" name="values[WEIGHT]" value="Y"' .
( ! empty( $gradebook_config['WEIGHT'] ) ? ' checked' : '' ) . '> ' .
_( 'Weight Grades' ) . '</label></td></tr>';

echo '<tr><td><label><input type="checkbox" name="values[DEFAULT_ASSIGNED]" value="Y"' .
( ! empty( $gradebook_config['DEFAULT_ASSIGNED'] ) ? ' checked' : '' ) . '> ' .
_( 'Assigned Date defaults to today' ) . '</label></td></tr>';

echo '<tr><td><label><input type="checkbox" name="values[DEFAULT_DUE]" value="Y"' .
( ! empty( $gradebook_config['DEFAULT_DUE'] ) ? ' checked' : '' ) . '> ' . _( 'Due Date defaults to today' ) .
'</label></td></tr>';

echo '<tr><td><label><input type="checkbox" name="values[LETTER_GRADE_ALL]" value="Y"' .
( ! empty( $gradebook_config['LETTER_GRADE_ALL'] ) ? ' checked' : '' ) . '> ' .
_( 'Hide letter grades for all gradebook assignments' ) . '</label></td></tr>';

echo '<tr><td><input type="text" name="values[LETTER_GRADE_MIN]" value="' .
$gradebook_config['LETTER_GRADE_MIN'] . '" size="3" maxlength="3" /> ' .
_( 'Minimum assignment points for letter grade' ) . '</td></tr>';

echo '<tr><td><input type="text" name="values[ANOMALOUS_MAX]" value="' .
( ! empty( $gradebook_config['ANOMALOUS_MAX'] ) ? $gradebook_config['ANOMALOUS_MAX'] : '100' ) . '" size="3" maxlength="3" /> % ' .
_( 'Allowed maximum percent in Anomalous grades' ) . '</td></tr>';

echo '<tr><td><input type="text" name="values[LATENCY]" value="' .
round( $gradebook_config['LATENCY'] ) . '" size="3" maxlength="3" /> ' .
_( 'Days until ungraded assignment grade appears in Parent/Student gradebook views' ) . '</td></tr>';

echo '</table>';
echo '</fieldset><br />';

if ( $RosarioModules['Eligibility'] )
{
	echo '<fieldset>';
	echo '<legend>' . _( 'Eligibility' ) . '</legend>';
	echo '<table>';
	echo '<tr><td><label><input type="checkbox" name="values[ELIGIBILITY_CUMULITIVE]" value="Y"' .
	( ! empty( $gradebook_config['ELIGIBILITY_CUMULITIVE'] ) ? ' checked' : '' ) . '>&nbsp;' .
	_( 'Calculate Eligibility using Cumulative Semester Grades' ) . '</label></td></tr>';
	echo '</table>';
	echo '</fieldset><br />';
}

$comment_codes_RET = DBGet( "SELECT rccs.ID,rccs.TITLE,rccc.TITLE AS CODE_TITLE
FROM REPORT_CARD_COMMENT_CODE_SCALES rccs,REPORT_CARD_COMMENT_CODES rccc
WHERE rccs.SCHOOL_ID='" . UserSchool() . "'
AND rccc.SCALE_ID=rccs.ID
ORDER BY rccc.SORT_ORDER,rccs.SORT_ORDER,rccs.ID,rccc.ID", array(), array( 'ID' ) );

if ( $comment_codes_RET )
{
	echo '<fieldset>';
	echo '<legend>' . _( 'Final Grades' ) . '</legend>';
	echo '<table class="col1-align-right">';

	foreach ( (array) $comment_codes_RET as $id => $comments )
	{
		echo '<tr><td><select name="values[COMMENT_' . $id . ']><option value="">' . _( 'N/A' ) . '';

		foreach ( (array) $comments as $key => $val )
		{
			echo '<option value="' . $val['CODE_TITLE'] . '"' .
			( $val['CODE_TITLE'] == $gradebook_config['COMMENT_' . $id] ? ' selected' : '' ) . '>' .
			$val['CODE_TITLE'];
		}

		echo '</select></td><td>' . sprintf( _( 'Default %s comment code' ), $comments[1]['TITLE'] ) . '</td></tr>';
	}

	echo '</table>';
	echo '</fieldset><br />';
}

/*
foreach ( (array) $grades as $course_period_id => $cp_grades)
{
$cp_grades_total = count($cp_grades);
for ( $i=1;$i<=$cp_grades_total;$i++)
$grades[ $course_period_id ][ $i ] = $grades[ $course_period_id ][ $i ]['TITLE'];
}
 */

//$grades = array('A+','A','A-','B+','B','B-','C+','C','C-','D+','D','D-','F');

if ( count( $grades ) )
{
	echo '<fieldset>';
	echo '<legend>' . _( 'Score Breakoff Points' ) . '</legend>';
	echo '<table><tr><td>';

	foreach ( (array) $grades as $course_period_id => $cp_grades )
	{
		$table = '<table class="cellpadding-5">';
		$table .= '<tr><td colspan="9">' . $cp_grades[1]['COURSE_TITLE'] . ' - ' .
		mb_substr(
			$cp_grades[1]['CP_TITLE'],
			0,
			mb_strrpos( str_replace( ' - ', ' ^ ', $cp_grades[1]['CP_TITLE'] ), '^' )
		) . '</td></tr><tr class="st">';

		$i = 0;

		foreach ( (array) $cp_grades as $grade )
		{
			$i++;
			$table .= '<td>&nbsp;<b>' . $grade['TITLE'] . '</b><br />';
			$table .= '<span class="nobr">
				<input type="text" name="values[' . $course_period_id . '-' . $grade['ID'] . ']" value="' .
				$gradebook_config[$course_period_id . '-' . $grade['ID']] .
				'" size="2" maxlength="5" />%</span></td>';

			if ( $i % 9 == 0 )
			{
				$table .= '</tr><tr class="st">';
			}
		}

		$table .= '</tr>';
		$table .= '</table>';
		echo $table;
		echo '</td></tr><tr><td>';
	}

	echo '</td></tr></table>';
	echo '</fieldset><br />';
}

$year = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,DOES_GRADES
	FROM SCHOOL_MARKING_PERIODS
	WHERE MP='FY'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER" );

$semesters = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,DOES_GRADES
	FROM SCHOOL_MARKING_PERIODS
	WHERE MP='SEM'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER" );

$quarters = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,PARENT_ID,DOES_GRADES
	FROM SCHOOL_MARKING_PERIODS
	WHERE MP='QTR'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER", array(), array( 'PARENT_ID' ) );

echo '<fieldset>';
echo '<legend>' . _( 'Final Grading Percentages' ) . '</legend>';
echo '<table>';

foreach ( (array) $semesters as $sem )
{
	if ( $sem['DOES_GRADES'] === 'Y' )
	{
		$table = '<table>';
		$table .= '<tr class="st"><td><span class="legend-gray">' . $sem['TITLE'] . '</span>&nbsp;</td>';
		$total = 0;

		foreach ( (array) $quarters[$sem['MARKING_PERIOD_ID']] as $qtr )
		{
			$table .= '<td><span class="nobr">' . $qtr['TITLE'] . '&nbsp;</span><br />';

			$table .= '<span class="nobr">
				<input type="text" name="values[SEM-' . $qtr['MARKING_PERIOD_ID'] . ']" value="' .
				$gradebook_config['SEM-' . $qtr['MARKING_PERIOD_ID']] .
				'" size="3" maxlength="6" />%</span></td>';

			$total += $gradebook_config['SEM-' . $qtr['MARKING_PERIOD_ID']];
		}

		if ( $total != 100 )
		{
			$table .= '<td><span class="legend-red">' . _( 'Total' ) . ' &#8800; 100%!</span></td>';
		}

		$table .= '</tr></table>';
		echo '<tr><td>' . $table . '</td></tr>';
	}
}

if ( $year[1]['DOES_GRADES'] === 'Y' )
{
	$table = '<table>';
	$table .= '<tr class="st"><td><span class="legend-gray;">' . $year[1]['TITLE'] . '</span>&nbsp;</td>';
	$total = 0;

	foreach ( (array) $semesters as $sem )
	{
		foreach ( (array) $quarters[$sem['MARKING_PERIOD_ID']] as $qtr )
		{
			$table .= '<td><span class="nobr">' . $qtr['TITLE'] . '&nbsp;</span><br />';

			$table .= '<span class="nobr">
				<input type="text" name="values[FY-' . $qtr['MARKING_PERIOD_ID'] . ']" value="' .
				$gradebook_config['FY-' . $qtr['MARKING_PERIOD_ID']] .
				'" size="3" maxlength="6" />%</span></td>';

			$total += $gradebook_config['FY-' . $qtr['MARKING_PERIOD_ID']];
		}

		if ( $sem['DOES_GRADES'] == 'Y' )
		{
			$table .= '<td><span class="nobr">' . $sem['TITLE'] . '&nbsp;</span><br />';
			$table .= '<input type="text" name="values[FY-' . $sem['MARKING_PERIOD_ID'] . ']" value="' .
			$gradebook_config['FY-' . $sem['MARKING_PERIOD_ID']] . '" size="3" maxlength="6" />%</td>';

			$total += $gradebook_config['FY-' . $sem['MARKING_PERIOD_ID']];
		}
	}

	if ( $total != 100 )
	{
		$table .= '<td><span class="legend-red">' . _( 'Total' ) . ' &#8800; 100%!</span></td>';
	}

	$table .= '</tr></table>';
	echo '<tr><td>' . $table . '</td></tr>';
}

echo '</table>';
echo '</fieldset>';

PopTable( 'footer' );

echo '<br /><div class="center">' . Buttons( _( 'Save' ) ) . '</div></form>';
