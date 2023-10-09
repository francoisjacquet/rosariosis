<?php
if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

DrawHeader( _( 'Gradebook' ) . ' - ' . ProgramTitle() );

if ( User( 'PROFILE' ) === 'admin'
	&& isset( $_REQUEST['GRADEBOOK_CONFIG_ADMIN_OVERRIDE'] ) )
{
	Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE', $_REQUEST['GRADEBOOK_CONFIG_ADMIN_OVERRIDE'] );

	if ( ! Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE' ) )
	{
		// Delete admin configuration (Staff ID = -1).
		DBQuery( "DELETE FROM program_user_config
			WHERE PROGRAM='Gradebook'
			AND USER_ID='-1'" );

		// Do not save values after deleting them!
		RedirectURL( 'values' );
	}
}

if ( ! empty( $_REQUEST['values'] ) )
{
	ProgramUserConfig(
		'Gradebook',
		// @since 5.8 Admin can override teachers gradebook configuration: use -1 as Staff ID.
		( User( 'PROFILE' ) === 'admin' ? -1 : 0 ),
		$_REQUEST['values']
	);

	$note[] = button( 'check' ) . '&nbsp;' . _( 'The gradebook configuration has been modified.' );
}

if ( User( 'PROFILE' ) === 'teacher' )
{
	// Allow Edit fields for teachers.
	$_ROSARIO['allow_edit'] = ! Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE' );

	if ( Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE' ) )
	{
		$warning[] = _( 'The gradebook configuration is defined by administrators.' );
	}
}

echo ErrorMessage( $warning, 'warning' );

echo ErrorMessage( $note, 'note' );

// @since 5.8 Admin can override teachers gradebook configuration: use -1 as Staff ID.
$gradebook_config = ProgramUserConfig( 'Gradebook', ( User( 'PROFILE' ) === 'admin' ? -1 : 0 ) );

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

if ( User( 'PROFILE' ) === 'admin' )
{
	// @since 5.8 Admin can override teachers gradebook configuration: checkbox.
	$checkbox_override = CheckboxInput(
		Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE' ),
		'GRADEBOOK_CONFIG_ADMIN_OVERRIDE',
		_( 'Override individual teacher configuration?' ),
		'',
		true
	);

	DrawHeader( $checkbox_override );
}

DrawHeader( '', Buttons( _( 'Save' ) ) );

if ( User( 'PROFILE' ) === 'admin' && ! Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE' ) )
{
	// @since 5.8 Admin can override teachers gradebook configuration: do not show form yet.
	echo '</form>';

	// Use return instead of exit. Allows Warehouse( 'footer' ) to run.
	return;
}

echo '<br />';

PopTable( 'header', _( 'Configuration' ) );

$grades = DBGet( "SELECT cp.TITLE AS CP_TITLE,c.TITLE AS COURSE_TITLE,cp.COURSE_PERIOD_ID,rcg.TITLE,rcg.ID
FROM report_card_grades rcg,course_periods cp,courses c
WHERE cp.COURSE_ID=c.COURSE_ID
AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
AND cp.SCHOOL_ID='" . UserSchool() . "'
AND cp.SCHOOL_ID=rcg.SCHOOL_ID
AND cp.SYEAR=rcg.SYEAR
AND cp.SYEAR='" . UserSyear() . "'
AND rcg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
AND cp.GRADE_SCALE_ID IS NOT NULL
AND cp.DOES_BREAKOFF='Y'
ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER IS NULL,rcg.SORT_ORDER DESC", [], [ 'COURSE_PERIOD_ID' ] );

echo '<fieldset><legend>' . _( 'Assignments' ) . '</legend><table class="cellpadding-5">';

if ( ! empty( $grades ) )
{
	// Allow Edit fields for teachers.
	$_ROSARIO['allow_edit'] = true;

	$rounding_options = [
		'UP' => _( 'Up' ),
		'DOWN' => _( 'Down' ),
		'NORMAL' => _( 'Normal' ),
	];

	echo '<tr><td>' . RadioInput(
		issetVal( $gradebook_config['ROUNDING'] ),
		'values[ROUNDING]',
		_( 'Score Rounding' ),
		$rounding_options,
		_( 'None' )
	) . '</td></tr>';

	// Allow Edit fields for teachers.
	$_ROSARIO['allow_edit'] = ! Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE' );
}

if ( empty( $gradebook_config['ASSIGNMENT_SORTING'] ) )
{
	$gradebook_config['ASSIGNMENT_SORTING'] = 'ASSIGNMENT_ID';
}

$sorting_options = [
	'ASSIGNMENT_ID' => _( 'Newest First' ),
	'DUE_DATE' => _( 'Due Date' ),
	'ASSIGNED_DATE' => _( 'Assigned Date' ),
];

echo '<tr><td>' . RadioInput(
	$gradebook_config['ASSIGNMENT_SORTING'],
	'values[ASSIGNMENT_SORTING]',
	_( 'Assignment Sorting' ),
	$sorting_options,
	false
) . '</td></tr>';

echo '<tr><td><hr>' . CheckboxInput(
	( array_key_exists( 'WEIGHT', $gradebook_config ) ? $gradebook_config['WEIGHT'] : '' ),
	'values[WEIGHT]',
	_( 'Weight Assignment Categories' ),
	'',
	( ! array_key_exists( 'WEIGHT', $gradebook_config ) )
) . '</td></tr>';

// @since 11.0 Add Weight Assignments option
echo '<tr><td>' . CheckboxInput(
	( array_key_exists( 'WEIGHT_ASSIGNMENTS', $gradebook_config ) ? $gradebook_config['WEIGHT_ASSIGNMENTS'] : '' ),
	'values[WEIGHT_ASSIGNMENTS]',
	_( 'Weight Assignments' ),
	'',
	( ! array_key_exists( 'WEIGHT', $gradebook_config ) )
) . '</td></tr>';

echo '<tr><td>' . CheckboxInput(
	( array_key_exists( 'DEFAULT_ASSIGNED', $gradebook_config ) ? $gradebook_config['DEFAULT_ASSIGNED'] : '' ),
	'values[DEFAULT_ASSIGNED]',
	_( 'Assigned Date defaults to today' ),
	'',
	( ! array_key_exists( 'DEFAULT_ASSIGNED', $gradebook_config ) )
) . '</td></tr>';

echo '<tr><td>' . CheckboxInput(
	( array_key_exists( 'DEFAULT_DUE', $gradebook_config ) ? $gradebook_config['DEFAULT_DUE'] : '' ),
	'values[DEFAULT_DUE]',
	_( 'Due Date defaults to today' ),
	'',
	( ! array_key_exists( 'DEFAULT_DUE', $gradebook_config ) )
) . '</td></tr>';

if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
{
	// Global Config allows for Letter grades.
	echo '<tr><td>' . CheckboxInput(
		( array_key_exists( 'LETTER_GRADE_ALL', $gradebook_config ) ? $gradebook_config['LETTER_GRADE_ALL'] : '' ),
		'values[LETTER_GRADE_ALL]',
		_( 'Hide letter grades for all gradebook assignments' ),
		'',
		( ! array_key_exists( 'LETTER_GRADE_ALL', $gradebook_config ) )
	) . '</td></tr>';
}

echo '<tr><td>' . CheckboxInput(
	( array_key_exists( 'HIDE_PREVIOUS_ASSIGNMENT_TYPES', $gradebook_config ) ? $gradebook_config['HIDE_PREVIOUS_ASSIGNMENT_TYPES'] : '' ),
	'values[HIDE_PREVIOUS_ASSIGNMENT_TYPES]',
	_( 'Hide previous quarters assignment types' ),
	'',
	( ! array_key_exists( 'HIDE_PREVIOUS_ASSIGNMENT_TYPES', $gradebook_config ) )
) . '</td></tr>';

$anomalous_max_value = ( ! empty( $gradebook_config['ANOMALOUS_MAX'] ) ? $gradebook_config['ANOMALOUS_MAX'] : '100' );

$anomalous_max_value = [
	$anomalous_max_value,
	$anomalous_max_value . '%'
];

echo '<tr><td>' . TextInput(
	$anomalous_max_value,
	'values[ANOMALOUS_MAX]',
	_( 'Allowed maximum percent in Anomalous grades' ),
	'type="number" min="1" max="999"',
	( array_key_exists( 'ANOMALOUS_MAX', $gradebook_config ) )
) . '</td></tr>';

echo '<tr><td>' . TextInput(
	(string) issetVal( $gradebook_config['LATENCY'] ),
	'values[LATENCY]',
	_( 'Days until ungraded assignment grade appears in Parent/Student gradebook views' ),
	'type="number" min="-99" max="99"',
	( isset( $gradebook_config['LATENCY'] ) )
) . '</td></tr>';


echo '</table></fieldset><br />';

if ( $RosarioModules['Eligibility'] )
{
	echo '<fieldset><legend>' . _( 'Eligibility' ) . '</legend><table>';

	echo '<tr><td>' . CheckboxInput(
		( array_key_exists( 'ELIGIBILITY_CUMULITIVE', $gradebook_config ) ? $gradebook_config['ELIGIBILITY_CUMULITIVE'] : '' ),
		'values[ELIGIBILITY_CUMULITIVE]',
		_( 'Calculate Eligibility using Cumulative Semester Grades' ),
		'',
		( ! array_key_exists( 'ELIGIBILITY_CUMULITIVE', $gradebook_config ) )
	) . '</td></tr>';

	echo '</table></fieldset><br />';
}

$comment_codes_RET = DBGet( "SELECT rccs.ID,rccs.TITLE,rccc.TITLE AS CODE_TITLE
FROM report_card_comment_code_scales rccs,report_card_comment_codes rccc
WHERE rccs.SCHOOL_ID='" . UserSchool() . "'
AND rccc.SCALE_ID=rccs.ID
ORDER BY rccc.SORT_ORDER IS NULL,rccc.SORT_ORDER IS NULL,rccc.SORT_ORDER,rccs.SORT_ORDER IS NULL,rccs.SORT_ORDER,rccs.ID,rccc.ID", [], [ 'ID' ] );

if ( $comment_codes_RET )
{
	echo '<fieldset><legend>' . _( 'Final Grades' ) . '</legend><table>';

	foreach ( (array) $comment_codes_RET as $id => $comments )
	{
		$select_options = [];

		$value = '';

		foreach ( (array) $comments as $comment )
		{
			$select_options[ $comment['CODE_TITLE'] ] = $comment['CODE_TITLE'];

			if ( isset( $gradebook_config['COMMENT_' . $id] )
				&& $comment['CODE_TITLE'] == $gradebook_config['COMMENT_' . $id] )
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
	// Allow Edit fields for teachers.
	$_ROSARIO['allow_edit'] = true;

	echo '<fieldset><legend>' . _( 'Score Breakoff Points' ) . '</legend>';

	foreach ( (array) $grades as $course_period_id => $cp_grades )
	{
		$table = '<table class="cellpadding-5">';
		$table .= '<tr><td colspan="6">' . $cp_grades[1]['COURSE_TITLE'] . ' &mdash; ' .
		mb_substr(
			$cp_grades[1]['CP_TITLE'],
			0,
			mb_strrpos( str_replace( ' - ', ' ^ ', $cp_grades[1]['CP_TITLE'] ), '^' )
		) . '</td></tr><tr class="st">';

		$i = 0;

		foreach ( (array) $cp_grades as $grade )
		{
			$input_name = 'values[' . $course_period_id . '-' . $grade['ID'] . ']';

			$input_id = GetInputID( $input_name );

			$table .= '<td><span class="nobr">
				<input name="' . AttrEscape( $input_name ) . '" id="' . $input_id . '" value="' .
				AttrEscape( issetVal( $gradebook_config[$course_period_id . '-' . $grade['ID']], '' ) ) .
				'" size="4" type="number" min=0 max=100 step=0.01 />%</span>' .
				FormatInputTitle(
					'&nbsp;' . $grade['TITLE'],
					$input_id
				) . '</td>';

			if ( ++$i % 6 == 0 )
			{
				$table .= '</tr><tr class="st">';
			}
		}

		$table .= '</tr></table>';

		echo $table;
	}

	echo '</fieldset><br />';

	// Allow Edit fields for teachers.
	$_ROSARIO['allow_edit'] = ! Config( 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE' );
}

// @since 11.1 SQL Use GetFullYearMP() & GetChildrenMP() functions to limit Marking Periods
$year = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,DOES_GRADES
	FROM school_marking_periods
	WHERE MP='FY'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND MARKING_PERIOD_ID='" . GetFullYearMP() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

$semesters = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,DOES_GRADES
	FROM school_marking_periods
	WHERE MP='SEM'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND MARKING_PERIOD_ID IN(" . ( GetChildrenMP( 'FY' ) ? GetChildrenMP( 'FY' ) : '0' ) . ")
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

$quarters = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,PARENT_ID,DOES_GRADES
	FROM school_marking_periods
	WHERE MP='QTR'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND MARKING_PERIOD_ID IN(" . ( GetChildrenMP( 'FY' ) ? GetChildrenMP( 'FY' ) : '0' ) . ")
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'PARENT_ID' ] );

echo '<fieldset><legend>' . _( 'Final Grading Percentages' ) . '</legend><table>';

$has_final_grading_percentages = false;

foreach ( (array) $semesters as $sem )
{
	if ( $sem['DOES_GRADES'] === 'Y' )
	{
		$has_final_grading_percentages = true;

		$table = '<table class="cellpadding-5">';
		$table .= '<tr class="st"><td><span class="legend-gray"><b>' .
			$sem['TITLE'] . '</b></span>&nbsp;</td>';

		$total = 0;

		if ( empty( $quarters[$sem['MARKING_PERIOD_ID']] ) )
		{
			$table .= '<td><span class="legend-red">' .
				_( 'Error' ) . ': ' . _( 'No quarters found' ) . '</span></td>';
		}
		else
		{
			foreach ( (array) $quarters[$sem['MARKING_PERIOD_ID']] as $qtr )
			{
				$gradebook_config_sem_qtr = isset( $gradebook_config['SEM-' . $qtr['MARKING_PERIOD_ID']] ) ?
					$gradebook_config['SEM-' . $qtr['MARKING_PERIOD_ID']] :
					null;

				$value = [
					$gradebook_config_sem_qtr,
					$gradebook_config_sem_qtr . '%'
				];

				$table .= '<td>' . TextInput(
					$value,
					'values[SEM-' . $qtr['MARKING_PERIOD_ID'] . ']',
					$qtr['TITLE'],
					'size="4" required type="number" min=0 max=100 step=0.01'
				) . '</td>';

				$total += $gradebook_config_sem_qtr;
			}

			if ( $total != 100 )
			{
				$table .= '<td><span class="legend-red">' .
					_( 'Total' ) . ' &#8800; 100%!</span></td>';
			}
		}

		$table .= '</tr></table>';

		echo '<tr><td>' . $table . '</td></tr>';
	}
}

if ( $year[1]['DOES_GRADES'] === 'Y' )
{
	$has_final_grading_percentages = true;

	$table = '<table class="cellpadding-5">';
	$table .= '<tr class="st"><td><span class="legend-gray"><b>' .
		$year[1]['TITLE'] . '</b></span>&nbsp;</td>';

	$total = 0;

	foreach ( (array) $semesters as $sem )
	{
		foreach ( (array) $quarters[$sem['MARKING_PERIOD_ID']] as $qtr )
		{
			$gradebook_config_fy_qtr = isset( $gradebook_config['FY-' . $qtr['MARKING_PERIOD_ID']] ) ?
				$gradebook_config['FY-' . $qtr['MARKING_PERIOD_ID']] :
				null;

			$value = [
				$gradebook_config_fy_qtr,
				$gradebook_config_fy_qtr . '%'
			];

			$table .= '<td>' . TextInput(
				$value,
				'values[FY-' . $qtr['MARKING_PERIOD_ID'] . ']',
				$qtr['TITLE'],
				'size="4" required type="number" min=0 max=100 step=0.01'
			) . '</td>';

			$total += $gradebook_config_fy_qtr;
		}

		if ( $sem['DOES_GRADES'] == 'Y' )
		{
			$gradebook_config_fy_sem = isset( $gradebook_config['FY-' . $sem['MARKING_PERIOD_ID']] ) ?
				$gradebook_config['FY-' . $sem['MARKING_PERIOD_ID']] :
				null;

			$value = [
				$gradebook_config_fy_sem,
				$gradebook_config_fy_sem . '%'
			];

			$table .= '<td>' . TextInput(
				$value,
				'values[FY-' . $sem['MARKING_PERIOD_ID'] . ']',
				$sem['TITLE'],
				'size="4" required type="number" min=0 max=100 step=0.01'
			) . '</td>';

			$total += $gradebook_config_fy_sem;
		}
	}

	if ( $total != 100 )
	{
		$table .= '<td><span class="legend-red">' .
			_( 'Total' ) . ' &#8800; 100%!</span></td>';
	}

	$table .= '</tr></table>';

	echo '<tr><td>' . $table . '</td></tr>';
}

if ( ! $has_final_grading_percentages )
{
	echo ErrorMessage( [
		_( 'Year and Semester marking periods are not graded.' )
	], 'note' );
}

echo '</table></fieldset>';

PopTable( 'footer' );

echo '<br /><div class="center">' . Buttons( _( 'Save' ) ) . '</div></form>';
