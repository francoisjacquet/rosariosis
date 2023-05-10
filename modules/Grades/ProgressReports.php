<?php
require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

if ( ! empty( $_REQUEST['period'] ) )
{
	// @since 10.9 Set current User Course Period before Secondary Teacher logic.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

$_REQUEST['include_inactive'] = issetVal( $_REQUEST['include_inactive'], '' );

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( empty( $_REQUEST['st_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}

	$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	Widgets( 'mailing_labels' );

	$RET = GetStuList( $extra );

	if ( empty( $RET ) )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	$LO_columns = [ 'TITLE' => _( 'Assignment' ) ];

	if ( isset( $_REQUEST['assigned_date'] )
		&& $_REQUEST['assigned_date'] == 'Y' )
	{
		$LO_columns += [ 'ASSIGNED_DATE' => _( 'Assigned Date' ) ];
	}

	if ( isset( $_REQUEST['due_date'] )
		&& $_REQUEST['due_date'] == 'Y' )
	{
		$LO_columns += [ 'DUE_DATE' => _( 'Due Date' ) ];
	}

	// modif Francois: display percent grade according to Configuration
	$LO_columns += [ 'POINTS' => _( 'Points' ) ];

	if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 )
	{
		$LO_columns['PERCENT_GRADE'] = _( 'Percent' );
	}

	// modif Francois: display letter grade according to Configuration

	if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
	{
		$LO_columns['LETTER_GRADE'] = _( 'Letter' );
	}

	//$LO_columns += array('POINTS' => _('Points'),'PERCENT_GRADE' => _('Percent'),'LETTER_GRADE' => _('Letter'),'COMMENT' => _('Comment'));
	$LO_columns += [ 'COMMENT' => _( 'Comment' ) ];

	$extra2['SELECT_ONLY'] = "ga.ASSIGNMENT_ID,ga.TITLE,ga.ASSIGNED_DATE,ga.DUE_DATE,gt.ASSIGNMENT_TYPE_ID,gg.POINTS,ga.POINTS AS TOTAL_POINTS,gt.FINAL_GRADE_PERCENT,gg.COMMENT,gg.POINTS AS PERCENT_GRADE,gg.POINTS AS LETTER_GRADE,ga.WEIGHT,CASE WHEN (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)) THEN 'Y' ELSE NULL END AS DUE,gt.TITLE AS CATEGORY_TITLE";

	$extra2['FROM'] = '';

	$extra2['WHERE'] = " AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";

	if ( isset( $_REQUEST['exclude_notdue'] )
		&& $_REQUEST['exclude_notdue'] == 'Y' )
	{
		$extra2['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";
	}

	if ( isset( $_REQUEST['exclude_ec'] )
		&& $_REQUEST['exclude_ec'] == 'Y' )
	{
		$extra2['WHERE'] .= " AND (ga.POINTS!='0' OR gg.POINTS IS NOT NULL AND gg.POINTS!='-1')";
	}

	// Parent: associated students.
	$extra2['ASSOCIATED'] = User( 'STAFF_ID' );

	$extra2['ORDER_BY'] = "ga.DUE_DATE DESC,ga.ASSIGNMENT_ID";

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$extra2['ORDER_BY'] = "ga." . DBEscapeIdentifier( Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) ) . " DESC";
	}

	$LO_group = [];

	if ( isset( $_REQUEST['by_category'] )
		&& $_REQUEST['by_category'] == 'Y' )
	{
		$extra2['group'] = $LO_group = [ 'ASSIGNMENT_TYPE_ID' ];
	}

	$extra2['functions'] = [
		'TITLE' => '_makeTitle',
		'CATEGORY_TITLE' => '_makeTitle',
		'ASSIGNED_DATE' => 'ProperDate',
		'DUE_DATE' => 'ProperDate',
		'POINTS' => '_makeExtraPoints',
		'PERCENT_GRADE' => '_makeExtraGrade',
		'LETTER_GRADE' => '_makeExtraGrade',
	];

	$handle = PDFStart();

	foreach ( (array) $RET as $student )
	{
		unset( $_ROSARIO['DrawHeader'] );

		if ( isset( $_REQUEST['mailing_labels'] )
			&& $_REQUEST['mailing_labels'] == 'Y' )
		{
			echo '<br /><br /><br />';
		}

		DrawHeader( _( 'Progress Report' ) );
		DrawHeader( $student['FULL_NAME'], $student['STUDENT_ID'] );
		DrawHeader( $student['GRADE_ID'], SchoolInfo( 'TITLE' ) );
		DrawHeader( ProperDate( DBDate() ), GetMP( UserMP() ) );

		if ( isset( $_REQUEST['mailing_labels'] )
			&& $_REQUEST['mailing_labels'] == 'Y' )
		{
			echo '<br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td><td>' .
				$student['MAILING_LABEL'] . '</td></tr></table><br />';
		}

		if ( User( 'PROFILE' ) !== 'teacher' )
		{
			// @since 5.4 Is Parent or Student: display all Course Period Assignments.
			$cp_RET = DBGet( "SELECT ss.COURSE_PERIOD_ID
				FROM schedule ss,course_periods cp
				WHERE ss.STUDENT_ID='" . (int) $student['STUDENT_ID'] . "'
				AND ss.SYEAR='" . UserSyear() . "'
				AND ss.START_DATE<'" . GetMP( UserMP(), 'END_DATE' ) . "'
				AND (ss.END_DATE IS NULL OR ss.END_DATE>='" . GetMP( UserMP(), 'START_DATE' ) . "')
				AND cp.MARKING_PERIOD_ID IN(" . GetAllMP( 'QTR', UserMP() ) . ")
				AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID", [], [ 'COURSE_PERIOD_ID' ] );

			$extra2['FROM'] = " JOIN schedule ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='" . UserSyear() . "')";
		}
		else
		{
			// Course Period is eacher current CP.
			$cp_RET = [ UserCoursePeriod() => [] ];
		}

		foreach ( $cp_RET as $cp_id => $cp )
		{
			$course_RET = DBGet( "SELECT c.TITLE,c.COURSE_ID,cp.TEACHER_ID
				FROM course_periods cp,courses c
				WHERE c.COURSE_ID=cp.COURSE_ID
				AND cp.COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

			$course_id = $course_RET[1]['COURSE_ID'];

			$teacher_id = $course_RET[1]['TEACHER_ID'];

			$extra = $extra2;

			$extra['FROM'] .= " JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID='" . (int) $cp_id . "' OR ga.COURSE_ID='" . (int) $course_id . "' AND ga.STAFF_ID='" . (int) $teacher_id . "') AND ga.MARKING_PERIOD_ID='" . UserMP() . "')
				LEFT OUTER JOIN gradebook_grades gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID='" . (int) $cp_id . "'),gradebook_assignment_types gt";

			$extra['WHERE'] .= " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID='" . (int) $course_id . "' AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";

			$extra['WHERE'] .= " AND s.STUDENT_ID='" . (int) $student['STUDENT_ID'] . "'";

			$extra['WHERE'] .= " AND ss.COURSE_PERIOD_ID='" . (int) $cp_id . "'";

			$student_points = $weighted_grade = $total_points = $percent_weights = $total_weights = [];

			$grades_RET = GetStuList( $extra );

			if ( empty( $grades_RET ) )
			{
				continue;
			}

			DrawHeader( $course_RET[1]['TITLE'] );

			$gradebook_config = ProgramUserConfig( 'Gradebook', $teacher_id );

			$sum_student_points = $sum_weighted_grade = $sum_total_points = $sum_points = $sum_percent = $sum_weights = 0;

			foreach ( (array) $percent_weights as $assignment_type_id => $percent )
			{
				$sum_student_points += $student_points[$assignment_type_id];
				$sum_total_points += $total_points[$assignment_type_id];
				$sum_points += $student_points[$assignment_type_id] * ( ! empty( $gradebook_config['WEIGHT'] ) && $percent ? $percent / $total_points[$assignment_type_id] : 1 );
				$sum_percent += ( ! empty( $gradebook_config['WEIGHT'] ) && $percent ? $percent : $total_points[$assignment_type_id] );

				// @since 11.0 Add Weight Assignments option
				$sum_weighted_grade += ( ! empty( $gradebook_config['WEIGHT'] ) && $percent ?
					$percent * $weighted_grade[$assignment_type_id] :
					$weighted_grade[$assignment_type_id] );

				$sum_weights += ( ! empty( $gradebook_config['WEIGHT'] ) && $percent ?
					$percent * $total_weights[$assignment_type_id] :
					$total_weights[$assignment_type_id] );
			}

			$sum_grade = 0;

			if ( $sum_percent > 0 )
			{
				$sum_grade = $sum_points / $sum_percent;
			}

			if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
			{
				// @since 11.0 Add Weight Assignments option
				if ( $sum_weights > 0 )
				{
					$sum_grade = $sum_weighted_grade / $sum_weights;
				}

				foreach ( $grades_RET as $assignment_type_id => $grades )
				{
					if ( isset( $_REQUEST['by_category'] )
						&& $_REQUEST['by_category'] == 'Y' )
					{
						foreach ( $grades as $grade_i => $grade )
						{
							$assignment_weight = ' <span class="size-1">(' .
								_( 'Weight' ) . ' ' . (int) $grades['WEIGHT'] . ')</span>';

							$grades_RET[$assignment_type_id][$grade_i]['TITLE'] .= $assignment_weight;
						}
					}
					else
					{
						$assignment_weight = ' <span class="size-1">(' .
							_( 'Weight' ) . ' ' . (int) $grades['WEIGHT'] . ')</span>';

						$grades_RET[$assignment_type_id]['TITLE'] .= $assignment_weight;
					}
				}
			}

			if ( isset( $_REQUEST['by_category'] )
				&& $_REQUEST['by_category'] == 'Y' )
			{
				foreach ( (array) $grades_RET as $assignment_type_id => $grades )
				{
					$percent_of_grade = '';

					if ( ! empty( $gradebook_config['WEIGHT'] ) && $sum_percent > 0 )
					{
						$percent_of_grade = ' (' . sprintf(
							_( '%s of grade' ),
							_Percent( ( $percent_weights[$assignment_type_id] / $sum_percent ) * 100 )
						) . ')';
					}

					$type_percent = ! empty( $total_points[$assignment_type_id] ) ?
						$student_points[$assignment_type_id] / $total_points[$assignment_type_id] :
						'';

					if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
					{
						// @since 11.0 Add Weight Assignments option
						$type_percent = ! empty( $total_weights[$assignment_type_id] ) ?
							$weighted_grade[$assignment_type_id] / $total_weights[$assignment_type_id] :
							'';
					}

					$percent_grade = $letter_grade = '&nbsp;';

					if ( $type_percent )
					{
						$percent_grade = _makeLetterGrade( $type_percent, $cp_id, $teacher_id, '%' );

						$percent_grade = '<b>' . _Percent( $percent_grade ) . '</b>';

						$letter_grade = '<b>' . _makeLetterGrade( $type_percent, $cp_id, $teacher_id ) . '</b>';
					}

					$grades_RET[$assignment_type_id][] = [
						'TITLE' => $grades[1]['CATEGORY_TITLE'] . ' &mdash; <b>' . _( 'Total' ) . '</b>' .
							$percent_of_grade,
						'ASSIGNED_DATE' => '&nbsp;',
						'DUE_DATE' => '&nbsp;',
						'POINTS' => '<b>' . $student_points[$assignment_type_id] .
							'&nbsp;/&nbsp;' . $total_points[$assignment_type_id] . '</b>',
						'PERCENT_GRADE' => $percent_grade,
						'LETTER_GRADE' => $letter_grade,
						'COMMENT' => '&nbsp;',
					];
				}
			}

			$percent = _makeLetterGrade( $sum_grade, $cp_id, $teacher_id, '%' );

			// Do not add Total to $link['add']['html']: PDF and no AllowEdit().
			$total_last_row = [
				'TITLE' => '<b>' . _( 'Total' ) . '</b>',
				'ASSIGNED_DATE' => '&nbsp;',
				'DUE_DATE' => '&nbsp;',
				'POINTS' => '<b>' . $sum_student_points . '&nbsp;/&nbsp;' . $sum_total_points . '</b>',
				'PERCENT_GRADE' => '<b>' . _Percent( $percent ) . '</b>',
				'LETTER_GRADE' => '<b>' . _makeLetterGrade( $sum_grade, $cp_id, $teacher_id ) . '</b>',
				'COMMENT' => '&nbsp;',
			];

			if ( isset( $_REQUEST['by_category'] )
				&& $_REQUEST['by_category'] == 'Y' )
			{
				$grades_RET[$assignment_type_id][] = $total_last_row;

				ListOutput(
					$grades_RET,
					$LO_columns,
					'Assignment Type',
					'Assignment Types',
					[],
					$LO_group,
					[ 'center' => false, 'add' => true ]
				);
			}
			else
			{
				$grades_RET[] = $total_last_row;

				ListOutput(
					$grades_RET,
					$LO_columns,
					'Assignment',
					'Assignments',
					[],
					$LO_group,
					[ 'center' => false, 'add' => true ]
				);
			}
		}

		echo '<div style="page-break-after: always;"></div>';
	}

	PDFStop( $handle );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( _( 'Gradebook' ) . ' - ' . ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list_st' || UserStudentID() )
	{
		/**
		 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
		 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
		 * Then teacher submits the form, data would be saved for CP B...
		 *
		 * Must be used in combination with
		 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
		 */
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] .
			'&period=' . UserCoursePeriod() . '&_ROSARIO_PDF=true' ) . '" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Progress Reports for Selected Students' ) );

		$extra['extra_header_left'] = '<table class="cellpadding-5 align-right">';

		$extra['extra_header_left'] .= '<tr class="st"><td colspan="2">
			<label>' . _( 'Assigned Date' ) .
			'&nbsp;<input type="checkbox" value="Y" name="assigned_date" /></label></td>';

		$extra['extra_header_left'] .= '<td>
			<label>' . _( 'Exclude Ungraded E/C Assignments' ) .
			'&nbsp;<input type="checkbox" value="Y" name="exclude_ec" checked /></label></td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td colspan="2">
			<label>' . _( 'Due Date' ) .
			'&nbsp;<input type="checkbox" value="Y" name="due_date" checked /></label></td>';

		$extra['extra_header_left'] .= '<td>
			<label>' . _( 'Exclude Ungraded Assignments Not Due' ) .
			'&nbsp;<input type="checkbox" value="Y" name="exclude_notdue" /></label></td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td colspan="2">
			<label>' . _( 'Group by Assignment Category' ) .
			'&nbsp;<input type="checkbox" value="Y" name="by_category" /></label></td>';

		Widgets( 'mailing_labels' );

		$extra['extra_header_left'] .= $extra['search'];

		$extra['search'] = '';

		$extra['extra_header_left'] .= '</table>';
		//$extra['old'] = true; // proceed to 'list' if UserStudentID()
	}

	$extra['new'] = true;

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) ];
	$extra['options']['search'] = false;

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	// Fix for Teacher Programs: conflict with Search Teacher list.
	$extra['action'] = '&search_modfunc=list_st';

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list_st' || UserStudentID() )
	{
		echo '<br /><div class="center">' .
		Buttons( _( 'Create Progress Reports for Selected Students' ) ) .
			'</div></form>';
	}
}

/**
 * Make Points
 * And sum totals.
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 5.4
 *
 * @param string $value  Points.
 * @param string $column POINTS.
 *
 * @return string E/C, N/A, Not due, or Letter / Percent grade.
 */
function _makeExtraPoints( $value, $column )
{
	global $THIS_RET, $student_points, $total_points, $percent_weights, $total_weights;

	if ( $THIS_RET['TOTAL_POINTS'] == '0' )
	{
		if ( ! isset( $student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] ) )
		{
			$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] = 0;
		}

		$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;

		return (float) $value . '&nbsp;/&nbsp;' . $THIS_RET['TOTAL_POINTS'];
	}

	if ( $value == '-1' )
	{
		return _( 'Excused' );
	}

	if ( ! empty( $THIS_RET['DUE'] )
		|| $value != '' )
	{
		if ( ! isset( $student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] ) )
		{
			$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] = 0;
		}

		$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;

		if ( ! isset( $total_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] ) )
		{
			$total_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] = 0;
		}

		$total_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $THIS_RET['TOTAL_POINTS'];

		$percent_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] = $THIS_RET['FINAL_GRADE_PERCENT'];

		if ( ! isset( $total_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] ) )
		{
			$total_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] = 0;
		}

		// @since 11.0 Add Weight Assignments option
		$total_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $THIS_RET['WEIGHT'];
	}

	return (float) $value . '&nbsp;/&nbsp;' . $THIS_RET['TOTAL_POINTS'];
}

/**
 * Make Grade
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 5.4
 *
 * @param string $value  Grade.
 * @param string $column LETTER_GRADE or PERCENT_GRADE.
 *
 * @return string E/C, N/A, Not due, or Letter / Percent grade.
 */
function _makeExtraGrade( $value, $column )
{
	global $THIS_RET, $cp_id, $teacher_id, $weighted_grade;

	if ( isset( $THIS_RET['TOTAL_POINTS'] )
		&& $THIS_RET['TOTAL_POINTS'] == '0' )
	{
		// Extra Credit.
		return _( 'E/C' );
	}

	if ( $value == '-1' )
	{
		return _( 'N/A' );
	}

	if ( empty( $THIS_RET['DUE'] )
		&& $value != '' )
	{
		return _( 'Not due' );
	}

	if ( $column == 'LETTER_GRADE' )
	{
		return _makeLetterGrade( $value / $THIS_RET['TOTAL_POINTS'], $cp_id, $teacher_id );
	}

	$percent = _makeLetterGrade( $value / $THIS_RET['TOTAL_POINTS'], $cp_id, $teacher_id, '%' );

	if ( ! isset( $weighted_grade[$THIS_RET['ASSIGNMENT_TYPE_ID']] ) )
	{
		$weighted_grade[$THIS_RET['ASSIGNMENT_TYPE_ID']] = 0;
	}

	// @since 11.0 Add Weight Assignments option
	$weighted_grade[$THIS_RET['ASSIGNMENT_TYPE_ID']] += ( $value / $THIS_RET['TOTAL_POINTS'] ) * $THIS_RET['WEIGHT'];

	return _Percent( $percent, 2 );
}

/**
 * @param $num
 * @param $decimals
 */
function _Percent( $num, $decimals = 2 )
{
	// Fix trim 0 (float) when percent > 1,000: do not use comma for thousand separator.
	return (float) number_format( $num, $decimals, '.', '' ) . '%';
}

/**
 * Make Assignment Title
 * Truncate Assignment title to 36 chars only if has words > 36 chars
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 10.5.2
 *
 * @param  string $value  Title value.
 * @param  string $column Column. Defaults to 'TITLE'.
 *
 * @return string         Assignment title truncated to 36 chars.
 */
function _makeTitle( $value, $column = 'TITLE' )
{
	// Split on spaces.
	$title_words = explode( ' ', $value );

	$truncate = false;

	foreach ( $title_words as $title_word )
	{
		if ( mb_strlen( $title_word ) > 36 )
		{
			$truncate = true;

			break;
		}
	}

	$title = ! $truncate ?
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}
