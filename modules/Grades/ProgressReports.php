<?php
require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

$course_period_id = UserCoursePeriod();
$course_id = DBGet( "SELECT cp.COURSE_ID,c.TITLE FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'" );
$course_title = $course_id[1]['TITLE'];
$course_id = $course_id[1]['COURSE_ID'];

if ( $_REQUEST['modfunc'] === 'save' )
{
	$gradebook_config = ProgramUserConfig( 'Gradebook' );

	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		Widgets( 'mailing_labels' );

		$RET = GetStuList( $extra );

		if ( ! empty( $RET ) )
		{
			$LO_columns = array( 'TITLE' => _( 'Assignment' ) );

			if ( $_REQUEST['assigned_date'] == 'Y' )
			{
				$LO_columns += array( 'ASSIGNED_DATE' => _( 'Assigned Date' ) );
			}

			if ( $_REQUEST['due_date'] == 'Y' )
			{
				$LO_columns += array( 'DUE_DATE' => _( 'Due Date' ) );
			}

			// modif Francois: display percent grade according to Configuration
			$LO_columns += array( 'POINTS' => _( 'Points' ) );

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
			$LO_columns += array( 'COMMENT' => _( 'Comment' ) );

			$extra2['SELECT_ONLY'] = "ga.TITLE,ga.ASSIGNED_DATE,ga.DUE_DATE,gt.ASSIGNMENT_TYPE_ID,gg.POINTS,ga.POINTS AS TOTAL_POINTS,gt.FINAL_GRADE_PERCENT,gg.COMMENT,gg.POINTS AS PERCENT_GRADE,gg.POINTS AS LETTER_GRADE,CASE WHEN (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)) THEN 'Y' ELSE NULL END AS DUE,gt.TITLE AS CATEGORY_TITLE";
			$extra2['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON ((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) AND ga.MARKING_PERIOD_ID='" . UserMP() . "') LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";
			$extra2['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=cp.COURSE_ID AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";
			$extra2['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";

			if ( $_REQUEST['exclude_notdue'] == 'Y' )
			{
				$extra2['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";
			}

			if ( $_REQUEST['exclude_ec'] == 'Y' )
			{
				$extra2['WHERE'] .= " AND (ga.POINTS!='0' OR gg.POINTS IS NOT NULL AND gg.POINTS!='-1')";
			}

			$extra2['ORDER_BY'] = "ga.ASSIGNMENT_ID";

			if ( $_REQUEST['by_category'] == 'Y' )
			{
				$extra2['group'] = $LO_group = array( 'ASSIGNMENT_TYPE_ID' );
			}
			else
			{
				$LO_group = array();
			}

			$extra2['functions'] = array( 'ASSIGNED_DATE' => '_removeSpaces', 'DUE_DATE' => '_removeSpaces', 'TITLE' => '_removeSpaces', 'POINTS' => '_makeExtra', 'PERCENT_GRADE' => '_makeExtra', 'LETTER_GRADE' => '_makeExtra' );

			$handle = PDFStart();

			foreach ( (array) $RET as $student )
			{
				unset( $_ROSARIO['DrawHeader'] );

				if ( $_REQUEST['mailing_labels'] == 'Y' )
				{
					echo '<br /><br /><br />';
				}

				DrawHeader( _( 'Progress Report' ) );
				DrawHeader( $student['FULL_NAME'], $student['STUDENT_ID'] );
				DrawHeader( $student['GRADE_ID'], SchoolInfo( 'TITLE' ) );
				DrawHeader( $course_title, GetMP( UserMP() ) );
				DrawHeader( ProperDate( DBDate() ) );

				if ( $_REQUEST['mailing_labels'] == 'Y' )
				{
					echo '<br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td><td>' . $student['MAILING_LABEL'] . '</td></tr></table><br />';
				}

				$extra = $extra2;
				$extra['WHERE'] .= " AND s.STUDENT_ID='" . $student['STUDENT_ID'] . "'";
				$student_points = $total_points = $percent_weights = array();
				$grades_RET = GetStuList( $extra );

				$sum_student_points = $sum_total_points = 0;
				$sum_points = $sum_percent = 0;

				foreach ( (array) $percent_weights as $assignment_type_id => $percent )
				{
					$sum_student_points += $student_points[$assignment_type_id];
					$sum_total_points += $total_points[$assignment_type_id];
					$sum_points += $student_points[$assignment_type_id] * ( $gradebook_config['WEIGHT'] == 'Y' ? $percent / $total_points[$assignment_type_id] : 1 );
					$sum_percent += ( $gradebook_config['WEIGHT'] == 'Y' ? $percent : $total_points[$assignment_type_id] );
				}

				if ( $sum_percent > 0 )
				{
					$sum_points /= $sum_percent;
				}
				else
				{
					$sum_points = 0;
				}

				if ( $_REQUEST['by_category'] == 'Y' )
				{
					foreach ( (array) $grades_RET as $assignment_type_id => $grades )
					{
//FJ remove LO_field
						$grades_RET[$assignment_type_id][] = array( 'TITLE' => _removeSpaces( '<b>' . $grades[1]['CATEGORY_TITLE'] . ' ' . _( 'Total' ) . '</b>' . ( $gradebook_config['WEIGHT'] == 'Y' && $sum_percent > 0 ? ' (' . sprintf( _( '%s of grade' ), _Percent( $percent_weights[$assignment_type_id] / $sum_percent ) ) . ')' : '' ), 'TITLE' ),
							'ASSIGNED_DATE' => '&nbsp;', 'DUE_DATE' => '&nbsp;',
							'POINTS' => '<table class="cellspacing-0"><tr><td><span class="size-1"><b>' . $student_points[$assignment_type_id] . '</b></span></td><td><span class="size-1">&nbsp;<b>/</b>&nbsp;</span></td><td><span class="size-1"><b>' . $total_points[$assignment_type_id] . '</b></span></td></tr></table>',
							'PERCENT_GRADE' => $total_points[$assignment_type_id] ? '<b>' . _Percent( $student_points[$assignment_type_id] / $total_points[$assignment_type_id] ) . '</b>' : '&nbsp;' );
					}
				}

				$link['add']['html'] = array( 'TITLE' => '<b>Total</b>',
					'POINTS' => '<table class="cellspacing-0"><tr><td><span class="size-1"><b>' . $sum_student_points . '</b></span></td><td><span class="size-1">&nbsp;<b>/</b>&nbsp;</span></td><td><span class="size-1"><b>' . $sum_total_points . '</b></span></td></tr></table>',
					'PERCENT_GRADE' => '<b>' . _Percent( $sum_points ) . '</b>', 'LETTER_GRADE' => '<b>' . _makeLetterGrade( $sum_points ) . '</b>' );
				$link['add']['html']['ASSIGNED_DATE'] = $link['add']['html']['DUE_DATE'] = $link['add']['html']['COMMENT'] = ' &nbsp; ';

//FJ add translation

				if ( $_REQUEST['by_category'] == 'Y' )
				{
					ListOutput( $grades_RET, $LO_columns, 'Assignment Type', 'Assignment Types', $link, $LO_group, array( 'center' => false, 'add' => true ) );
				}
				else
				{
					ListOutput( $grades_RET, $LO_columns, 'Assignment', 'Assignments', $link, $LO_group, array( 'center' => false, 'add' => true ) );
				}

				echo '<div style="page-break-after: always;"></div>';
			}

			PDFStop( $handle );
		}
		else
		{
			BackPrompt( _( 'No Students were found.' ) );
		}
	}
	else
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( _( 'Gradebook' ) . ' - ' . ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' ) // || UserStudentID())
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] . '&_ROSARIO_PDF=true" method="POST">';

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

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) );
	$extra['options']['search'] = false;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' .
		Buttons( _( 'Create Progress Reports for Selected Students' ) ) .
			'</div></form>';
	}
}

/**
 * @param $value
 * @param $column
 */
function _makeExtra( $value, $column )
{
	global $THIS_RET, $student_points, $total_points, $percent_weights;

	if ( $column == 'POINTS' )
	{
		if ( $THIS_RET['TOTAL_POINTS'] != '0' )
		{
			if ( $value != '-1' )
			{
				if ( $THIS_RET['DUE'] || $value != '' )
				{
					$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;
					$total_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $THIS_RET['TOTAL_POINTS'];
					$percent_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] = $THIS_RET['FINAL_GRADE_PERCENT'];
				}

				return ( rtrim( rtrim( $value, '0' ), '.' ) + 0 ) .
					'&nbsp;/&nbsp;' . $THIS_RET['TOTAL_POINTS'];
			}
			else
			{
				return _( 'Excused' );
			}
		}
		else
		{
			$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;

			return ( rtrim( rtrim( $value, '0' ), '.' ) + 0 ) .
				'&nbsp;/&nbsp;' . $THIS_RET['TOTAL_POINTS'];
		}
	}
	elseif ( $column == 'PERCENT_GRADE' )
	{
		if ( $THIS_RET['TOTAL_POINTS'] != '0' )
		{
			if ( $value != '-1' )
			{
				if ( $THIS_RET['DUE'] || $value != '' )
				{
					return _Percent( $value / $THIS_RET['TOTAL_POINTS'], 1 );
				}
				else
				{
					return _( 'Not due' );
				}
			}
			else
			{
				return _( 'N/A' );
			}
		}
		else
		{
			return _( 'E/C' );
		}
	}
	elseif ( $column == 'LETTER_GRADE' )
	{
		if ( $THIS_RET['TOTAL_POINTS'] != '0' )
		{
			if ( $value != '-1' )
			{
				if ( $THIS_RET['DUE'] || $value != '' )
				{
					return _makeLetterGrade( $value / $THIS_RET['TOTAL_POINTS'] );
				}
				else
				{
					return _( 'Not due' );
				}
			}
			else
			{
				return _( 'N/A' );
			}
		}
		else
		{
			return _( 'E/C' );
		}
	}
}

/**
 * @param $value
 * @param $column
 */
function _removeSpaces( $value, $column )
{
	if ( $column == 'ASSIGNED_DATE' || $column == 'DUE_DATE' )
	{
		$value = '' . ProperDate( $value ) . '';
	}

	return str_replace( ' ', '&nbsp;', str_replace( '&', '&amp;', $value ) );
}

/**
 * @param $num
 * @param $decimals
 */
function _Percent( $num, $decimals = 2 )
{
	return number_format( $num * 100, 2 ) . '%';
}
