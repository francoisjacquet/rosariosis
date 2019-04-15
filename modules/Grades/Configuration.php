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
AND cp.SCHOOL_ID='" . UserSchool() . "'
AND cp.SCHOOL_ID=rcg.SCHOOL_ID
AND cp.SYEAR=rcg.SYEAR
AND cp.SYEAR='" . UserSyear() . "'
AND rcg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
AND cp.GRADE_SCALE_ID IS NOT NULL
AND cp.DOES_BREAKOFF='Y'
ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER DESC", array(), array( 'COURSE_PERIOD_ID' ) );

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

DrawHeader( '', Buttons( _( 'Save' ) ) );

echo '<br />';

PopTable( 'header', _( 'Configuration' ) );

// Allow Edit fields for teachers.
$_ROSARIO['allow_edit'] = true;

echo '<fieldset><legend>' . _( 'Assignments' ) . '</legend><table>';

if ( ! empty( $grades ) )
{
	//if ( ! $gradebook_config['ROUNDING'])
	//	$gradebook_config['ROUNDING'] = 'NORMAL';

	$rounding_options = array(
		'UP' => _( 'Up' ),
		'DOWN' => _( 'Down' ),
		'NORMAL' => _( 'Normal' ),
	);

	echo '<tr><td>' . RadioInput(
		$gradebook_config['ROUNDING'],
		'values[ROUNDING]',
		_( 'Score Rounding' ),
		$rounding_options,
		_( 'None' )
	) . '</td></tr>';
}

if ( empty( $gradebook_config['ASSIGNMENT_SORTING'] ) )
{
	$gradebook_config['ASSIGNMENT_SORTING'] = 'ASSIGNMENT_ID';
}

$sorting_options = array(
	'ASSIGNMENT_ID' => _( 'Newest First' ),
	'DUE_DATE' => _( 'Due Date' ),
	'ASSIGNED_DATE' => _( 'Assigned Date' ),
);

echo '<tr><td>' . RadioInput(
	$gradebook_config['ASSIGNMENT_SORTING'],
	'values[ASSIGNMENT_SORTING]',
	_( 'Assignment Sorting' ),
	$sorting_options,
	false
) . '</td></tr>';

echo '<tr><td><hr />' . CheckboxInput(
	$gradebook_config['WEIGHT'],
	'values[WEIGHT]',
	_( 'Weight Grades' )
) . '</td></tr>';

echo '<tr><td>' . CheckboxInput(
	$gradebook_config['DEFAULT_ASSIGNED'],
	'values[DEFAULT_ASSIGNED]',
	_( 'Assigned Date defaults to today' )
) . '</td></tr>';

echo '<tr><td>' . CheckboxInput(
	$gradebook_config['DEFAULT_DUE'],
	'values[DEFAULT_DUE]',
	_( 'Due Date defaults to today' )
) . '</td></tr>';

if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
{
	// Global Config allows for Letter grades.
	echo '<tr><td>' . CheckboxInput(
		$gradebook_config['LETTER_GRADE_ALL'],
		'values[LETTER_GRADE_ALL]',
		_( 'Hide letter grades for all gradebook assignments' )
	) . '</td></tr>';
}

echo '<tr><td>' . CheckboxInput(
	$gradebook_config['HIDE_PREVIOUS_ASSIGNMENT_TYPES'],
	'values[HIDE_PREVIOUS_ASSIGNMENT_TYPES]',
	_( 'Hide previous quarters assignment types' )
) . '</td></tr>';

echo '<tr><td><hr />' . TextInput(
	$gradebook_config['LETTER_GRADE_MIN'],
	'values[LETTER_GRADE_MIN]',
	_( 'Minimum assignment points for letter grade' ),
	'size="3" maxlength="3"'
) . '</td></tr>';

$anomalous_max_value = ( ! empty( $gradebook_config['ANOMALOUS_MAX'] ) ? $gradebook_config['ANOMALOUS_MAX'] : '100' );

$anomalous_max_value = array(
	$anomalous_max_value,
	$anomalous_max_value . '%'
);

echo '<tr><td>' . TextInput(
	$anomalous_max_value,
	'values[ANOMALOUS_MAX]',
	_( 'Allowed maximum percent in Anomalous grades' ),
	'size="3" maxlength="3"'
) . '</td></tr>';

echo '<tr><td>' . TextInput(
	(string) round( $gradebook_config['LATENCY'] ),
	'values[LATENCY]',
	_( 'Days until ungraded assignment grade appears in Parent/Student gradebook views' ),
	'size="3" maxlength="3"'
) . '</td></tr>';


echo '</table></fieldset><br />';

if ( $RosarioModules['Eligibility'] )
{
	echo '<fieldset><legend>' . _( 'Eligibility' ) . '</legend><table>';

	echo '<tr><td>' . CheckboxInput(
		$gradebook_config['ELIGIBILITY_CUMULITIVE'],
		'values[ELIGIBILITY_CUMULITIVE]',
		_( 'Calculate Eligibility using Cumulative Semester Grades' )
	) . '</td></tr>';

	echo '</table></fieldset><br />';
}

$comment_codes_RET = DBGet( "SELECT rccs.ID,rccs.TITLE,rccc.TITLE AS CODE_TITLE
FROM REPORT_CARD_COMMENT_CODE_SCALES rccs,REPORT_CARD_COMMENT_CODES rccc
WHERE rccs.SCHOOL_ID='" . UserSchool() . "'
AND rccc.SCALE_ID=rccs.ID
ORDER BY rccc.SORT_ORDER,rccs.SORT_ORDER,rccs.ID,rccc.ID", array(), array( 'ID' ) );

if ( $comment_codes_RET )
{
	echo '<fieldset><legend>' . _( 'Final Grades' ) . '</legend><table>';

	foreach ( (array) $comment_codes_RET as $id => $comments )
	{
		$select_options = array();

		$value = '';

		foreach ( (array) $comments as $comment )
		{
			$select_options[ $comment['CODE_TITLE'] ] = $comment['CODE_TITLE'];

			if ( $comment['CODE_TITLE'] == $gradebook_config['COMMENT_' . $id] )
			{
				$value = $comment['CODE_TITLE'];
			}
		}

		echo '<tr><td>' . SelectInput(
			$value,
			'values[COMMENT_' . $id . ']',
			sprintf( _( 'Default %s comment code' ), $comments[1]['TITLE'] ),
			$select_options,
			'N/A'
		) . '</td></tr>';
	}

	echo '</table></fieldset><br />';
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

if ( ! empty( $grades ) )
{
	echo '<fieldset><legend>' . _( 'Score Breakoff Points' ) . '</legend><table>';

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

		$table .= '</tr></table>';

		echo '<tr><td>' . $table . '</td></tr>';
	}

	echo '</table></fieldset><br />';
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

echo '<fieldset><legend>' . _( 'Final Grading Percentages' ) . '</legend><table>';

foreach ( (array) $semesters as $sem )
{
	if ( $sem['DOES_GRADES'] === 'Y' )
	{
		$table = '<table class="cellpadding-5">';
		$table .= '<tr class="st"><td style="vertical-align: bottom;"><span class="legend-gray">' .
			$sem['TITLE'] . '</span>&nbsp;</td>';

		$total = 0;

		foreach ( (array) $quarters[$sem['MARKING_PERIOD_ID']] as $qtr )
		{
			$value = array(
				$gradebook_config['SEM-' . $qtr['MARKING_PERIOD_ID']],
				$gradebook_config['SEM-' . $qtr['MARKING_PERIOD_ID']] . '%'
			);

			$table .= '<td>' . TextInput(
				$value,
				'values[SEM-' . $qtr['MARKING_PERIOD_ID'] . ']',
				$qtr['TITLE'],
				'size="3" maxlength="6"'
			) . '</td>';

			$total += $gradebook_config['SEM-' . $qtr['MARKING_PERIOD_ID']];
		}

		if ( $total != 100 )
		{
			$table .= '<td style="vertical-align: bottom;"><span class="legend-red">' .
				_( 'Total' ) . ' &#8800; 100%!</span></td>';
		}

		$table .= '</tr></table>';

		echo '<tr><td>' . $table . '</td></tr>';
	}
}

if ( $year[1]['DOES_GRADES'] === 'Y' )
{
	$table = '<table class="cellpadding-5">';
	$table .= '<tr class="st"><td style="vertical-align: bottom;"><span class="legend-gray">' .
		$year[1]['TITLE'] . '</span>&nbsp;</td>';

	$total = 0;

	foreach ( (array) $semesters as $sem )
	{
		foreach ( (array) $quarters[$sem['MARKING_PERIOD_ID']] as $qtr )
		{
			$value = array(
				$gradebook_config['FY-' . $qtr['MARKING_PERIOD_ID']],
				$gradebook_config['FY-' . $qtr['MARKING_PERIOD_ID']] . '%'
			);

			$table .= '<td>' . TextInput(
				$value,
				'values[SEM-' . $qtr['MARKING_PERIOD_ID'] . ']',
				$qtr['TITLE'],
				'size="3" maxlength="6"'
			) . '</td>';

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
		$table .= '<td style="vertical-align: bottom;"><span class="legend-red">' .
			_( 'Total' ) . ' &#8800; 100%!</span></td>';
	}

	$table .= '</tr></table>';

	echo '<tr><td>' . $table . '</td></tr>';
}

echo '</table></fieldset>';

PopTable( 'footer' );

echo '<br /><div class="center">' . Buttons( _( 'Save' ) ) . '</div></form>';
