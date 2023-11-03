<?php
/**
 * Report Cards functions
 */

if ( ! function_exists( 'ReportCardsIncludeForm' ) )
{
	/**
	 * Get Include on Report Card form
	 *
	 * @todo Use Inputs.php functions.
	 *
	 * @example $extra['extra_header_left'] = ReportCardsIncludeForm();
	 *
	 * @since 4.0 Define your custom function in your addon module or plugin.
	 * @since 5.0 Add GPA or Total row (only for Report Cards).
	 * @since 5.0 Add Min. and Max. Grades.
	 * @since 7.1 Add Credits (only for Report Cards).
	 * @since 10.7 Add Class Average row.
	 * @since 10.7 Add Student Photo
	 * @since 11.0 Add Class Average & Class Rank (Course Period)
	 *
	 * @global $extra Get $extra['search'] for Mailing Labels Widget
	 *
	 * @uses _getOtherAttendanceCodes()
	 *
	 * @param  string  $include_on_title Form title (optional). Defaults to 'Include on Report Card'.
	 * @param  boolean $mailing_labels   Include Mailing Labels widget (optional). Defaults to true.
	 * @return string  Include on Report Card form
	 */
	function ReportCardsIncludeForm( $include_on_title = 'Include on Report Card', $mailing_labels = true )
	{
		global $extra,
			$_ROSARIO;

		$other_attendance_codes = _getOtherAttendanceCodes();

		if ( $include_on_title === 'Include on Report Card' )
		{
			$include_on_title = _( 'Include on Report Card' );
		}

		// Open table.
		$return = '<table class="width-100p"><tr><td colspan="2"><b>' . $include_on_title .
			'</b></td></tr><tr><td colspan="2"><table class="cellpadding-5"><tr class="st">';

		if ( $_REQUEST['modname'] !== 'Grades/FinalGrades.php' )
		{
			// Student Photo.
			$return .= '<td colspan="2"><label><input type="checkbox" name="elements[studentpic]" value="Y"> ' .
				_( 'Student Photo' ) . '</label></td>';

			$return .= '</tr><tr class="st">';
		}

		// Teacher.
		$return .= '<td><label><input type="checkbox" name="elements[teacher]" value="Y" checked /> ' .
		_( 'Teacher' ) . '</label></td>';

		// Comments.
		$return .= '<td><label><input type="checkbox" name="elements[comments]" value="Y" checked /> ' .
		_( 'Comments' ) . '</label></td>';

		$return .= '</tr><tr class="st">';

		// Percents.
		$return .= '<td><label><input type="checkbox" name="elements[percents]" value="Y"> ' .
		_( 'Percents' ) . '</label></td>';

		// Add Min. and Max. Grades.
		$return .= '<td><label><input type="checkbox" name="elements[minmax_grades]" value="Y"> ' .
		_( 'Min. and Max. Grades' ) . '</label></td>';

		$return .= '</tr><tr class="st">';

		if ( $_REQUEST['modname'] !== 'Grades/FinalGrades.php' )
		{
			// Credits.
			$return .= '<td><label><input type="checkbox" name="elements[credits]" value="Y"> ' .
			_( 'Credits' ) . '</label></td>';

			// @since 11.0 Add Class Average (Course Period)
			$return .= '<td><label><input type="checkbox" name="elements[average]" value="Y"> ' .
			_( 'Class average' ) . '</label></td>';

			$return .= '</tr><tr class="st">';

			// @since 11.0 Add Class Rank (Course Period)
			$return .= '<td><label><input type="checkbox" name="elements[rank]" value="Y"> ' .
			_( 'Class Rank' ) . '</label></td>';
		}

		$return .= '</tr><tr class="st">';

		// Year-to-date Daily Absences.
		$return .= '<td><label><input type="checkbox" name="elements[ytd_absences]" value="Y" checked /> ' .
		_( 'Year-to-date Daily Absences' ) . '</label></td>';

		// Other Attendance Year-to-date.
		$return .= '<td><label><input type="checkbox" name="elements[ytd_tardies]" value="Y" /> ' .
		_( 'Other Attendance Year-to-date' ) . ':</label> <select name="ytd_tardies_code" id="ytd_tardies_code">';

		foreach ( (array) $other_attendance_codes as $code )
		{
			$return .= '<option value="' . AttrEscape( $code[1]['ID'] ) . '">' . $code[1]['TITLE'] . '</option>';
		}

		$return .= '</select>
			<label for="ytd_tardies_code" class="a11y-hidden">' . _( 'Attendance Codes' ) . '</label></td>';

		$return .= '</tr><tr class="st">';

		// Daily Absences this marking period.
		$return .= '<td><label><input type="checkbox" name="elements[mp_absences]" value="Y"' .
		( GetMP( UserMP(), 'SORT_ORDER' ) != 1 ? ' checked' : '' ) . ' /> ' .
		_( 'Daily Absences this marking period' ) . '</label></td>';

		// Other Attendance this marking period.
		$return .= '<td><label><input type="checkbox" name="elements[mp_tardies]" value="Y" /> ' .
		_( 'Other Attendance this marking period' ) . ':</label> <select name="mp_tardies_code" id="mp_tardies_code">';

		foreach ( (array) $other_attendance_codes as $code )
		{
			$return .= '<option value="' . AttrEscape( $code[1]['ID'] ) . '">' . $code[1]['TITLE'] . '</option>';
		}

		$return .= '</select>
			<label for="mp_tardies_code" class="a11y-hidden">' . _( 'Attendance Codes' ) . '</label></td>';

		$return .= '</tr><tr class="st">';

		// Period-by-period absences.
		$return .= '<td><label><input type="checkbox" name="elements[period_absences]" value="Y" /> ' .
		_( 'Period-by-period absences' ) . '</label></td>';

		if ( $_REQUEST['modname'] !== 'Grades/FinalGrades.php' )
		{
			$return .= '</tr><tr class="st">';

			// Add GPA and/or Total row.
			$gpa_or_total_options = [
				'total' => _( 'Total' ),
				'gpa' => _( 'GPA' ),
			];

			if ( User( 'PROFILE' ) !== 'admin' )
			{
				$_ROSARIO['allow_edit'] = true;
			}

			$return .= '<td>' . MultipleCheckboxInput( '', 'elements[last_row][]', _( 'Last row' ), $gpa_or_total_options ) . '</td>';

			// Class Rank and/or Average.
			// @since 10.7 Add Class Average row.
			$class_rank_or_average_options = [
				'average' => _( 'Class average' ),
				'rank' => _( 'Class Rank' ),
			];

			$return .= '<td>' . MultipleCheckboxInput( '', 'elements[last_row][]', _( 'Last row' ), $class_rank_or_average_options ) . '</td>';
		}

		$return .= '</tr></table></td></tr>';

		// Limit Free text to admin.

		if ( User( 'PROFILE' ) === 'admin'
			&& function_exists( 'GetTemplate' ) )
		{
			// Add Free text option.
			$field_SSECURITY = ParseMLArray( DBGet( "SELECT TITLE
				FROM custom_fields
				WHERE ID = 200000003" ), 'TITLE' );

			$return .= '<tr><td><label><input type="checkbox" name="elements[freetext]" autocomplete="off" value="1" onclick=\'javascript: document.getElementById("divfreetext").style.display="block"; document.getElementById("elements[freetext]").focus();\'> ' . _( 'Free Text' ) . '</label>';

			$return .= '<div id="divfreetext" style="display:none">';

			$return .= TinyMCEInput(
				GetTemplate( 'Grades/ReportCards.php' ),
				'inputfreetext',
				_( 'Free Text' )
			);

			$substitutions = [
				'__FULL_NAME__' => _( 'Display Name' ),
				'__LAST_NAME__' => _( 'Last Name' ),
				'__FIRST_NAME__' => _( 'First Name' ),
				'__MIDDLE_NAME__' =>  _( 'Middle Name' ),
				'__GRADE_ID__' => _( 'Grade Level' ),
				'__SCHOOL_ID__' => _( 'School' ),
				'__YEAR__' => _( 'School Year' ),
			];

			$substitutions += SubstitutionsCustomFields( 'STUDENT' );

			$return .= '<table><tr class="st"><td class="valign-top">' .
				SubstitutionsInput( $substitutions ) .
			'</td></tr>';

			$return .= '</table></div></td></tr>';
		}

		// Get the title instead of the short marking period name.
		// @since 11.1 SQL Use GetChildrenMP() function to limit Marking Periods
		$mps_RET = DBGet( "SELECT PARENT_ID,MARKING_PERIOD_ID,SHORT_NAME,TITLE
			FROM school_marking_periods
			WHERE MP='QTR'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND MARKING_PERIOD_ID IN(" . ( GetChildrenMP( 'FY' ) ? GetChildrenMP( 'FY' ) : '0' ) . ")
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,START_DATE", [], [ 'PARENT_ID' ] );

		// Marking Periods.
		$return .= '<tr class="st"><td colspan="2"><hr><table class="cellpadding-5">';

		foreach ( (array) $mps_RET as $sem => $quarters )
		{
			$return .= '<tr class="st">';

			foreach ( (array) $quarters as $qtr )
			{
				$pro = GetChildrenMP( 'PRO', $qtr['MARKING_PERIOD_ID'] );

				if ( $pro )
				{
					$pros = explode( ',', str_replace( "'", '', $pro ) );

					foreach ( (array) $pros as $pro )
					{
						if ( GetMP( $pro, 'DOES_GRADES' ) === 'Y' )
						{
							$return .= '<td><label>
								<input type="checkbox" name="mp_arr[]" value="' . AttrEscape( $pro ) . '" /> ' .
							GetMP( $pro, 'TITLE' ) . '</label></td>';
						}
					}
				}

				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . AttrEscape( $qtr['MARKING_PERIOD_ID'] ) . '" /> ' .
					$qtr['TITLE'] . '</label></td>';
			}

			if ( GetMP( $sem, 'DOES_GRADES' ) === 'Y' )
			{
				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . AttrEscape( $sem ) . '" /> ' .
				GetMP( $sem, 'TITLE' ) . '</label></td>';
			}

			$return .= '</tr>';
		}

		if ( $sem )
		{
			$fy = GetParentMP( 'FY', $sem );

			$return .= '<tr>';

			if ( GetMP( $fy, 'DOES_GRADES' ) === 'Y' )
			{
				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . AttrEscape( $fy ) . '" /> ' .
				GetMP( $fy, 'TITLE' ) . '</label></td>';
			}

			$return .= '</tr>';
		}

		$return .= '</table>' .
			FormatInputTitle( _( 'Marking Periods' ), '', false, '' ) .
			'<hr></td></tr>';

		if ( $mailing_labels )
		{
			// Mailing Labels.
			Widgets( 'mailing_labels' );
		}

		if ( ! empty( $extra['search'] ) )
		{
			$return .= '<tr><td><table>' . $extra['search'] . '</table></td></tr>';
		}

		$extra['search'] = '';

		$return .= '</table>';

		return $return;
	}
}

if ( ! function_exists( 'ReportCardsGenerate' ) )
{
	/**
	 * Report Cards generation
	 *
	 * @todo Divide in smaller functions
	 *
	 * @example $report_cards = ReportCardsGenerate( $_REQUEST['st_arr'], $_REQUEST['mp_arr'] );
	 *
	 * @since 4.0 Define your custom function in your addon module or plugin.
	 * @since 4.5 Add Report Cards PDF header action hook.
	 * @since 5.0 Add GPA or Total row.
	 * @since 5.0 Add Min. and Max. Grades.
	 * @since 7.5 Report Cards PDF footer action hook
	 * @since 8.0 Add Class Rank row.
	 * @since 10.7 Add Class Average row.
	 * @since 10.7 Add Student Photo
	 * @since 11.0 Add Class Average & Class Rank (Course Period)
	 * @since 11.3 Fail if Marking Periods are not in current School Year
	 *
	 * @param  array         $student_array Students IDs
	 * @param  array         $mp_array      Marking Periods IDs
	 * @return boolean|array False if No Students or Report Cards associative array (key = $student_id)
	 */
	function ReportCardsGenerate( $student_array, $mp_array )
	{
		global $_ROSARIO,
			$count_lines,
			$StudentPicturesPath;

		require_once 'modules/Grades/includes/Grades.fnc.php';

		if ( empty( $student_array )
			|| empty( $mp_array ) )
		{
			return false;
		}

		$last_mp = end( $mp_array );

		if ( ! GetMP( $last_mp ) )
		{
			/**
			 * Fail if Marking Periods are not in current School Year
			 * Happens when user switched School Year in left menu
			 * & then requests Report Cards from a previous tab.
			 *
			 * @since 11.3
			 */
			return false;
		}

		$mp_list = "'" . implode( "','", $mp_array ) . "'";

		$st_list = "'" . implode( "','", $student_array ) . "'";

		$extra = GetReportCardsExtra( $mp_list, $st_list );

		$student_RET = GetStuList( $extra );

		if ( empty( $student_RET ) )
		{
			return false;
		}

		// Comments.

		if ( isset( $_REQUEST['elements']['comments'] )
			&& $_REQUEST['elements']['comments'] === 'Y' )
		{
			$comments_RET = GetReportCardsComments( $st_list, $mp_list );

			$all_commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
			FROM report_card_comments
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND COURSE_ID IS NOT NULL
			AND COURSE_ID='0'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,ID", [], [ 'ID' ] );

			// FJ get color for Course specific categories & get comment scale.
			//$commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NOT NULL AND COURSE_ID!='0'",array(),array('ID'));
			$commentsA_RET = DBGet( "SELECT c.ID,c.TITLE,c.SORT_ORDER,cc.COLOR,
				cs.TITLE AS SCALE_TITLE
			FROM report_card_comments c, report_card_comment_categories cc,
				report_card_comment_code_scales cs
			WHERE c.SCHOOL_ID='" . UserSchool() . "'
			AND c.SYEAR='" . UserSyear() . "'
			AND c.COURSE_ID IS NOT NULL
			AND c.COURSE_ID!='0'
			AND cc.SYEAR=c.SYEAR
			AND cc.SCHOOL_ID=c.SCHOOL_ID
			AND cc.COURSE_ID=c.COURSE_ID
			AND cc.ID=c.CATEGORY_ID
			AND cs.SCHOOL_ID=c.SCHOOL_ID
			AND cs.ID=c.SCALE_ID
			ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER,c.ID", [], [ 'ID' ] );

			$commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
			FROM report_card_comments
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND COURSE_ID IS NULL", [], [ 'ID' ] );
		}

		// Mailing Labels.

		if ( isset( $_REQUEST['mailing_labels'] )
			&& $_REQUEST['mailing_labels'] === 'Y' )
		{
			// GET THE ADDRESSES.
			unset( $extra );

			$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

			$extra['SELECT'] = 's.STUDENT_ID,' . DisplayNameSQL( 's' ) . ' AS FULL_NAME';

			Widgets( 'mailing_labels', $extra );

			$extra['SELECT_ONLY'] = $extra['SELECT'];

			$extra['SELECT'] = '';

			$extra['group'] = [ 'STUDENT_ID', 'ADDRESS_ID' ];

			// Parent: associated students.
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );

			$addresses_RET = GetStuList( $extra );
		}

		// ListOutput columns.
		$LO_columns = [ 'COURSE_TITLE' => _( 'Course' ) ];

		if ( isset( $_REQUEST['elements']['teacher'] )
			&& $_REQUEST['elements']['teacher'] === 'Y' )
		{
			$LO_columns['TEACHER_ID'] = _( 'Teacher' );
		}

		if ( isset( $_REQUEST['elements']['period_absences'] )
			&& $_REQUEST['elements']['period_absences'] === 'Y' )
		{
			$LO_columns['ABSENCES'] = _( 'Absences' );
		}

		if ( count( (array) $mp_array ) > 2 )
		{
			$mp_TITLE = 'SHORT_NAME';
		}
		else
		{
			$mp_TITLE = 'TITLE';
		}

		foreach ( (array) $mp_array as $mp )
		{
			$LO_columns[$mp] = GetMP( $mp, $mp_TITLE );
		}

		if ( isset( $_REQUEST['elements']['credits'] )
			&& $_REQUEST['elements']['credits'] === 'Y' )
		{
			$LO_columns['CREDITS'] = _( 'Credits' );
		}

		if ( isset( $_REQUEST['elements']['comments'] )
			&& $_REQUEST['elements']['comments'] === 'Y' )
		{
			foreach ( (array) $all_commentsA_RET as $comment )
			{
				$LO_columns['C' . $comment[1]['ID']] = $comment[1]['TITLE'];
			}

			$LO_columns['COMMENT'] = _( 'Comments' );
		}

		// Report Cards array.
		$report_cards = [];

		foreach ( (array) $student_RET as $student_id => $course_periods )
		{
			// Start buffer.
			ob_start();

			$comments_arr = [];

			$comments_arr_key = ! empty( $all_commentsA_RET );

			unset( $grades_RET );

			$grades_total = [];

			$i = 0;

			// Course Periods.

			foreach ( (array) $course_periods as $course_period_id => $mps )
			{
				$i++;

				$grade_i = [];

				$grade_i['COURSE_TITLE'] = $mps[key( $mps )][1]['COURSE_TITLE'];
				$grade_i['COURSE_PERIOD_ID'] = $course_period_id;
				$grade_i['TEACHER_ID'] = GetTeacher( $mps[key( $mps )][1]['TEACHER_ID'] );

				if ( ! empty( $_REQUEST['elements']['average'] ) )
				{
					// @since 11.0 Add Class Average (Course Period)
					// Add "small" line below Course Title.
					$grade_i['COURSE_TITLE'] .= '<br /><span class="size-1">' . _( 'Class average' ) . '</span>';
				}

				if ( ! empty( $_REQUEST['elements']['rank'] ) )
				{
					// @since 11.0 Add Class Rank (Course Period)
					// Add "small" line below Course Title.
					$grade_i['COURSE_TITLE'] .= '<br /><span class="size-1">' . _( 'Class Rank' ) . '</span>';
				}

				foreach ( (array) $mp_array as $mp )
				{
					if ( ! isset( $mps[$mp] ) )
					{
						continue;
					}

					$grade = $mps[$mp][1];

					$grade_i[$mp] = '<B>' . issetVal( $grade['GRADE_TITLE'], '&nbsp;' ) . '</B>';

					$grade_i['CREDITS'] = (float) $grade['CREDITS'];

					if ( isset( $_REQUEST['elements']['percents'] )
						&& $_REQUEST['elements']['percents'] === 'Y'
						&& $grade['GRADE_PERCENT'] > 0 )
					{
						$grade_i[$mp] .= '&nbsp;&nbsp;' . (float) $grade['GRADE_PERCENT'] . '%';
					}

					if ( ! empty( $_REQUEST['elements']['average'] ) )
					{
						// @since 11.0 Add Class Average (Course Period)
						// Add "small" line below MP Grade.
						$grade_i[$mp] .= '<br /><span class="size-1"><b>' . GetClassAverage(
							$course_period_id,
							$mp,
							-1
						) . '</b>';

						if ( ! empty( $_REQUEST['elements']['percents'] ) )
						{
							$grade_i[$mp] .= '&nbsp;&nbsp;' . GetClassAverage(
								$course_period_id,
								$mp,
								1
							);
						}

						$grade_i[$mp] .= '</span>';
					}

					if ( ! empty( $_REQUEST['elements']['rank'] ) )
					{
						// @since 11.0 Add Class Rank (Course Period)
						// Add "small" line below MP Grade.
						$grade_i[$mp] .= '<br /><span class="size-1">' . GetClassRank(
							$student_id,
							$course_period_id,
							$mp
						) . '</span>';
					}

					// @since 5.0 Add GPA or Total row.
					if ( ! isset( $grades_total[$mp] ) )
					{
						$grades_total[$mp] = 0;
					}

					$grades_total[$mp] += $grade['WEIGHTED_GP'];

					// Comments.

					if ( isset( $_REQUEST['elements']['comments'] )
						&& $_REQUEST['elements']['comments'] === 'Y' )
					{
						$sep = '; ';

						$sep_mp = ' | ';

						if ( empty( $grade_i['COMMENT'] ) )
						{
							$grade_i['COMMENT'] = '';
						}
						else
						{
							$grade_i['COMMENT'] .= $sep_mp;
						}

						$temp_grades_COMMENTS = $grade_i['COMMENT'];

						$comments_RET[$student_id][$course_period_id][$mp] = issetVal( $comments_RET[$student_id][$course_period_id][$mp], [] );

						foreach ( (array) $comments_RET[$student_id][$course_period_id][$mp] as $comment )
						{
							if ( ! empty( $all_commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']] ) )
							{
								if ( empty( $grade_i['C' . $comment['REPORT_CARD_COMMENT_ID']] ) )
								{
									$grade_i['C' . $comment['REPORT_CARD_COMMENT_ID']] = $comment['COMMENT'] != ' ' ?$comment['COMMENT'] :
										'&middot;';
								}
								else
								{
									$grade_i['C' . $comment['REPORT_CARD_COMMENT_ID']] .= $comment['COMMENT'] != ' ' ?
										$sep_mp . $comment['COMMENT'] :
										$sep_mp . '&middot;';
								}
							}
							else
							{
								$sep_tmp = empty( $grade_i['COMMENT'] )
								|| mb_substr( $grade_i['COMMENT'], -3 ) == $sep_mp ?
								'' :
								$sep;

								if ( ! empty( $commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']] ) )
								{
									$color = $commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['COLOR'];

									if ( $color )
									{
										$color_html = '<span style="color:' . $color . '">';
									}
									else
									{
										$color_html = '';
									}

									$grade_i['COMMENT'] .= $sep_tmp . $color_html .
										$commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'] . '.';

									$grade_i['COMMENT'] .= '(' . ( $comment['COMMENT'] != ' ' ?
										$comment['COMMENT'] :
										'&middot;' ) .
										')' . ( $color_html ? '</span>' : '' );

									$comments_arr_key = true;
								}
								else
								{
									$grade_i['COMMENT'] .= $sep_tmp .
										$commentsB_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];
								}

								$comments_arr[$grade_i['COURSE_TITLE']][$comment['REPORT_CARD_COMMENT_ID']] = $comment['SORT_ORDER'];
							}
						}

						if ( $grade['COMMENT_TITLE'] )
						{
							$grade_i['COMMENT'] .= ( empty( $grade_i['COMMENT'] )
								|| mb_substr( $grade_i['COMMENT'], -3 ) == $sep_mp ?
								'' :
								$sep ) .
								$grade['COMMENT_TITLE'];
						}

						if ( $grade_i['COMMENT'] == $temp_grades_COMMENTS )
						{
							$grade_i['COMMENT'] .= ( empty( $grade_i['COMMENT'] )
								|| mb_substr( $grade_i['COMMENT'], -3 ) == $sep_mp ?
								'' :
								$sep ) .
							_( 'None' );
						}

						$grade_i['COMMENT'] = '<span class="size-1">' . $grade_i['COMMENT'] . '</span>';
					}

					$last_mp = $mp;
				}

				// Period-by-period absences.

				if ( isset( $_REQUEST['elements']['period_absences'] )
					&& $_REQUEST['elements']['period_absences'] === 'Y' )
				{
					if ( $mps[$last_mp][1]['DOES_ATTENDANCE'] )
					{
						$grade_i['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'] . ' / ' .
							$mps[$last_mp][1]['MP_ABSENCES'];
					}
					else
					{
						$grade_i['ABSENCES'] = _( 'N/A' );
					}
				}

				$grades_RET[ $i ] = $grade_i;
			}

			if ( ! empty( $_REQUEST['elements']['last_row'] ) )
			{
				$last_row_i = $i + 1;

				$credits_total_done = false;

				foreach ( $_REQUEST['elements']['last_row'] as $last_row )
				{
					if ( $last_row === 'total'
						|| $last_row === 'gpa' )
					{
						// @since 5.0 Add GPA or Total row.
						$grades_RET[$last_row_i++] = GetGpaOrTotalRow(
							$student_id,
							$grades_total,
							$i,
							$last_row
						);

						if ( ! empty( $_REQUEST['elements']['credits'] )
							&& empty( $credits_total_done ) )
						{
							$credits_total = 0;

							foreach ( $grades_RET as $grade_i )
							{
								if ( isset( $grade_i['CREDITS'] ) )
								{
									// @since 7.4 Add Total Credits.
									$credits_total += $grade_i['CREDITS'];
								}
							}

							$grades_RET[$last_row_i - 1]['CREDITS'] = (float) $credits_total;

							$credits_total_done = true;
						}
					}

					if ( $last_row === 'rank' )
					{
						// @since 8.0 Add Class Rank row.
						$grades_RET[$last_row_i++] = GetClassRankRow(
							$student_id,
							$mp_array
						);
					}

					if ( $last_row === 'average' )
					{
						// @since 10.6 Add Class Average row.
						$grades_RET[$last_row_i++] = GetClassAverageRow(
							$course_periods
						);
					}
				}
			}

			if ( ! empty( $_REQUEST['elements']['minmax_grades'] ) )
			{
				// @since 5.0 Add Min. and Max. Grades.
				$min_max_grades = GetReportCardMinMaxGrades( $course_periods );

				$grades_RET = AddReportCardMinMaxGrades(
					$min_max_grades,
					$grades_RET,
					$LO_columns
				);
			}

			asort( $comments_arr, SORT_NUMERIC );

			// Fix SQL error table name "sam" specified more than once (when Expanded View)
			$extra2 = [];

			// Student Info.
			$extra2['WHERE'] = " AND s.STUDENT_ID='" . (int) $student_id . "'";

			// SELECT s.* Custom Fields for Substitutions.
			$extra2['SELECT'] = ",s.*";

			if ( empty( $_REQUEST['_search_all_schools'] ) )
			{
				// School Title.
				$extra2['SELECT'] .= ",(SELECT sch.TITLE FROM schools sch
					WHERE ssm.SCHOOL_ID=sch.ID
					AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_TITLE";
			}

			$student = GetStuList( $extra2 );

			$student = $student[1];

			// Mailing Labels.

			if ( isset( $_REQUEST['mailing_labels'] )
				&& $_REQUEST['mailing_labels'] === 'Y' )
			{
				if ( ! empty( $addresses_RET[$student_id] ) )
				{
					$addresses = $addresses_RET[$student_id];
				}
				else
				{
					$addresses = [ 0 => [ 1 => [
						'STUDENT_ID' => $student_id,
						'ADDRESS_ID' => '0',
						'MAILING_LABEL' => '<BR /><BR />',
					] ] ];
				}
			}
			else
			{
				$addresses = [ 0 => [] ];
			}

			foreach ( (array) $addresses as $address )
			{
				unset( $_ROSARIO['DrawHeader'] );

				if ( isset( $_REQUEST['mailing_labels'] )
					&& $_REQUEST['mailing_labels'] === 'Y' )
				{
					echo '<BR /><BR /><BR />';
				}

				echo '<table class="width-100p"><tr>';

				// FJ add school logo.
				$logo_pic = 'assets/school_logo_' . UserSchool() . '.jpg';

				if ( file_exists( $logo_pic ) )
				{
					$picwidth = 120;

					echo '<td style="width:' . $picwidth . 'px;">
						<img src="' . URLEscape( $logo_pic ) . '" width="' . AttrEscape( $picwidth ) . '" />
					</td>';
				}

				echo '<td>';

				// Headers.
				DrawHeader( _( 'Report Card' ) );

				// TOCHECK! test headers.
				DrawHeader( $student['FULL_NAME'], $student_id );

				DrawHeader( $student['GRADE_ID'], $student['SCHOOL_TITLE'] );

				$syear = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );

				// FJ add school year.
				DrawHeader( _( 'School Year' ) . ': ' . $syear );

				$count_lines = 4;

				$mp_absences = '';

				// Marking Period-by-period absences.

				if ( isset( $_REQUEST['elements']['mp_absences'] )
					&& $_REQUEST['elements']['mp_absences'] === 'Y' )
				{
					$mp_absences = GetMPAbsences( $st_list, $last_mp, $student_id );
				}

				// Year-to-date Daily Absences.

				if ( isset( $_REQUEST['elements']['ytd_absences'] )
					&& $_REQUEST['elements']['ytd_absences'] === 'Y' )
				{
					DrawHeader( GetYTDAbsences( $st_list, $last_mp, $student_id ), $mp_absences );

					$count_lines++;
				}
				elseif ( isset( $_REQUEST['elements']['mp_absences'] )
					&& $_REQUEST['elements']['mp_absences'] === 'Y' )
				{
					DrawHeader( $mp_absences );

					$count_lines++;
				}

				$mp_tardies = '';

				// Marking Period Tardies.

				if ( isset( $_REQUEST['elements']['mp_tardies'] )
					&& $_REQUEST['elements']['mp_tardies'] === 'Y' )
				{
					$mp_tardies = GetMPTardies( $st_list, $last_mp, $student_id );
				}

				// Year to Date Tardies.

				if ( isset( $_REQUEST['elements']['ytd_tardies'] )
					&& $_REQUEST['elements']['ytd_tardies'] === 'Y' )
				{
					DrawHeader( GetYTDTardies( $st_list, $student_id ), $mp_tardies );

					$count_lines++;
				}
				elseif ( isset( $_REQUEST['elements']['mp_tardies'] )
					&& $_REQUEST['elements']['mp_tardies'] === 'Y' )
				{
					DrawHeader( $mp_tardies );

					$count_lines++;
				}

				if ( isset( $_REQUEST['mailing_labels'] )
					&& $_REQUEST['mailing_labels'] === 'Y' )
				{
					DrawHeader( ProperDate( DBDate() ) );

					$count_lines++;
				}

				// @since 4.5 Add Report Cards PDF header action hook.
				do_action( 'Grades/includes/ReportCards.fnc.php|pdf_header', $student_id );

				echo '</td>';

				if ( isset( $_REQUEST['elements']['studentpic'] )
					&& $_REQUEST['elements']['studentpic'] === 'Y' )
				{
					// @since 10.7 Add Student Photo.
					// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
					$picture_path = (array) glob( $StudentPicturesPath . '*/' . $student_id . '.*jpg' );

					$picture_path = end( $picture_path );

					if ( $picture_path )
					{
						$picwidth = 120;

						echo '<td style="width:' . $picwidth . 'px;">
							<img src="' . URLEscape( $picture_path ) . '" width="' . AttrEscape( $picwidth ) . '" />
						</td>';
					}
				}

				echo '</tr></table>';

				// Mailing Labels.

				if ( isset( $_REQUEST['mailing_labels'] )
					&& $_REQUEST['mailing_labels'] === 'Y' )
				{
					for ( $i = $count_lines; $i <= 6; $i++ )
					{
						echo '<BR />';
					}

					echo '<table><tr>
					<td style="width:50px;"> &nbsp; </td>
					<td style="width:300px;">' . $address[1]['MAILING_LABEL'] . '</td>
					</tr></table>';
				}

				echo '<BR />';

				ListOutput( $grades_RET, $LO_columns, '.', '.', [], [], [ 'count' => false ] );

				// Comments.

				if ( isset( $_REQUEST['elements']['comments'] )
					&& $_REQUEST['elements']['comments'] === 'Y'
					&& ( $comments_arr_key
						|| ! empty( $comments_arr ) ) )
				{
					echo _( 'Explanation of Comment Codes' ) . '<br />';

					if ( $comments_arr_key )
					{
						// FJ limit comment scales to the ones used in student's courses.
						$course_periods_list = implode( ',', array_keys( $course_periods ) );

						$comment_scales = GetReportCardCommentScales( $student_id, $course_periods_list );

						foreach ( (array) $comment_scales as $comment_scale )
						{
							echo '<div class="st">';

							DrawHeader( $comment_scale );

							echo '</div>';
						}
					}

					$general_comments = GetReportCardGeneralComments( $student_id, $comments_arr );

					if ( $general_comments )
					{
						echo '<div class="st">';

						DrawHeader( $general_comments );

						echo '</div>';
					}

					$course_specific_comments = GetReportCardCourseSpecificComments( $student_id, $comments_arr );

					if ( $course_specific_comments )
					{
						echo '<br style="clear:left;" /><br />' . _( 'Course-specific Comments' ) . '<br />';

						foreach ( $course_specific_comments as $specific_comments )
						{
							echo '<div class="st">';

							DrawHeader( $specific_comments );

							echo '</div>';
						}
					}

					echo '<br style="clear:left;" />';
				}
			}

			$freetext = '';

			if ( ! empty( $_REQUEST['elements']['freetext'] )
				&& function_exists( 'GetTemplate' ) )
			{
				$freetext_template = GetTemplate( 'Grades/ReportCards.php' );

				$substitutions = [
					'__FULL_NAME__' => $student['FULL_NAME'],
					'__LAST_NAME__' => $student['LAST_NAME'],
					'__FIRST_NAME__' => $student['FIRST_NAME'],
					'__MIDDLE_NAME__' => $student['MIDDLE_NAME'],
					'__GRADE_ID__' => $student['GRADE_ID'],
					'__SCHOOL_ID__' => $student['SCHOOL_TITLE'],
					'__YEAR__' => $syear,
				];

				$substitutions += SubstitutionsCustomFieldsValues( 'STUDENT', $student );

				$freetext = '<div class="report-card-free-text">' .
					SubstitutionsTextMake( $substitutions, $freetext_template ) . '</div>';
			}

			// @since 7.5 Report Cards PDF footer action hook.
			// Echo your custom text before "Free text" or append it to $freetext to display it after.
			do_action( 'Grades/includes/ReportCards.fnc.php|pdf_footer', [ $student_id, &$freetext ] );

			echo $freetext;

			// Add buffer to Report Cards array.
			$report_cards[$student_id] = ob_get_clean();
		}

		return $report_cards;
	}
}

if ( ! function_exists( 'GetReportCardsExtra' ) )
{
	/**
	 * Get $extra var for Report Cards.
	 * To be used by GetStuList().
	 *
	 * @since 5.7.4 Define your custom function in your addon module or plugin.
	 * @example $extra = GetReportCardsExtra( $mp_array, $student_array );
	 *
	 * @param  array $mp_list MPs list.
	 * @param  array $st_list Students list.
	 * @return array $extra
	 */
	function GetReportCardsExtra( $mp_list, $st_list )
	{
		// Student List Extra.
		$extra['WHERE'] = " AND s.STUDENT_ID IN ( " . $st_list . ")";

		// Student Details. TODO test if ReportCards needs GRADE_ID!!
		$extra['SELECT_ONLY'] = "DISTINCT s.FIRST_NAME,s.LAST_NAME,s.STUDENT_ID,ssm.SCHOOL_ID";

		$extra['SELECT_ONLY'] .= ",sg1.GRADE_LETTER as GRADE_TITLE,sg1.GRADE_PERCENT,WEIGHTED_GP,GP_SCALE,
			sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,
			sg1.COURSE_TITLE as COURSE_TITLE,rc_cp.TEACHER_ID,rc_cp.CREDITS";

		if ( isset( $_REQUEST['elements']['period_absences'] )
			&& $_REQUEST['elements']['period_absences'] === 'Y' )
		{
			// Period-by-period absences.
			$extra['SELECT_ONLY'] .= ",rc_cp.DOES_ATTENDANCE,
				(SELECT count(*) FROM attendance_period ap,attendance_codes ac
					WHERE ac.ID=ap.ATTENDANCE_CODE
					AND ac.STATE_CODE='A'
					AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
					AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
				(SELECT count(*) FROM attendance_period ap,attendance_codes ac
					WHERE ac.ID=ap.ATTENDANCE_CODE
					AND ac.STATE_CODE='A'
					AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
					AND sg1.MARKING_PERIOD_ID=ap.MARKING_PERIOD_ID
					AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES";
		}

		// Fix SQL drop order by School Period, allow Course Periods with no Periods
		// FJ multiple school periods for a course period.
		//$extra['FROM'] .= ",student_report_card_grades sg1,attendance_codes ac,course_periods rc_cp,school_periods sp";
		$extra['FROM'] = ",student_report_card_grades sg1,attendance_codes ac,course_periods rc_cp";

		/*$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
		AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=rc_cp.PERIOD_ID";*/
		$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (" . $mp_list . ")
			AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
			AND sg1.STUDENT_ID=ssm.STUDENT_ID";

		$extra['ORDER_BY'] = "s.LAST_NAME,s.FIRST_NAME,sg1.COURSE_TITLE";

		$extra['group'] = [ 'STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' ];

		// Parent: associated students.
		$extra['ASSOCIATED'] = User( 'STAFF_ID' );

		return $extra;
	}
}

/**
 * Marking Period Daily absences.
 *
 * @uses _getAttendanceDayRET()
 *
 * @param  string $st_list    Student List
 * @param  string $last_mp    Last MP
 * @param  string $student_id Student ID
 * @return string "Daily Absences in [last MP]: x"
 */
function GetMPAbsences( $st_list, $last_mp, $student_id )
{
	$attendance_day_RET = _getAttendanceDayRET( $st_list, $last_mp );

	$count = 0;

	if ( isset( $attendance_day_RET[$student_id][$last_mp] ) )
	{
		foreach ( (array) $attendance_day_RET[$student_id][$last_mp] as $abs )
		{
			$count += 1 - $abs['STATE_VALUE'];
		}
	}

	return sprintf( _( 'Daily Absences in %s' ), GetMP( $last_mp, 'TITLE' ) ) . ': ' . $count;
}

/**
 * Year-to-date Daily Absences.
 *
 * @uses _getAttendanceDayRET()
 *
 * @param  string $st_list    Student List
 * @param  string $last_mp    Last MP
 * @param  string $student_id Student ID
 * @return string "Daily Absences this year: x"
 */
function GetYTDAbsences( $st_list, $last_mp, $student_id )
{
	$attendance_day_RET = _getAttendanceDayRET( $st_list, $last_mp );

	$count = 0;

	if ( isset( $attendance_day_RET[$student_id] ) )
	{
		foreach ( (array) $attendance_day_RET[$student_id] as $mp_abs )
		{
			foreach ( (array) $mp_abs as $abs )
			{
				$count += 1 - $abs['STATE_VALUE'];
			}
		}
	}

	return _( 'Daily Absences this year' ) . ': ' . $count;
}

/**
 * Daily Absences this quarter or Year-to-date Daily Absences.
 * Local function.
 *
 * @param  string $st_list              Student List
 * @param  string $last_mp              Last MP
 * @return array  $attendance_day_RET
 */
function _getAttendanceDayRET( $st_list, $last_mp )
{
	/**
	 * @var mixed
	 */
	static $attendance_day_RET = null,
	$last_st_list,
		$last_last_mp;

	if ( ! $attendance_day_RET
		|| $last_st_list !== $st_list
		|| $last_last_mp !== $last_mp )
	{
		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		$extra['SELECT_ONLY'] = "ad.SCHOOL_DATE,ad.MARKING_PERIOD_ID,ad.STATE_VALUE,ssm.STUDENT_ID";

		$extra['FROM'] = ",attendance_day ad";

		$extra['WHERE'] .= " AND ad.STUDENT_ID=ssm.STUDENT_ID
			AND ad.SYEAR=ssm.SYEAR
			AND (ad.STATE_VALUE='0.0' OR ad.STATE_VALUE='.5')
			AND ad.SCHOOL_DATE<='" . GetMP( $last_mp, 'END_DATE' ) . "'";

		$extra['group'] = [ 'STUDENT_ID', 'MARKING_PERIOD_ID' ];

		// Parent: associated students.
		$extra['ASSOCIATED'] = User( 'STAFF_ID' );

		$attendance_day_RET = GetStuList( $extra );
	}

	$last_last_mp = $last_mp;
	$last_st_list = $st_list;

	return $attendance_day_RET;
}

/**
 * Marking Period Tardies.
 *
 * @uses _getAttendanceRET()
 * @uses _getOtherAttendanceCodes()
 *
 * @param  string $st_list     Student List
 * @param  string $last_mp     Last MP
 * @param  string $student_id  Student ID
 * @return string "[attendance code] in [last MP]: x"
 */
function GetMPTardies( $st_list, $last_mp, $student_id )
{
	// Other Attendance this quarter or Other Attendance Year-to-date.
	$attendance_RET = _getAttendanceRET( $st_list );

	// Get Other Attendance Codes.
	$other_attendance_codes = _getOtherAttendanceCodes();

	$count = 0;

	if ( ! empty( $attendance_RET[$student_id][$_REQUEST['mp_tardies_code']][$last_mp] ) )
	{
		foreach ( (array) $attendance_RET[$student_id][$_REQUEST['mp_tardies_code']][$last_mp] as $abs )
		{
			$count++;
		}
	}

	$tardies_code_title = $other_attendance_codes[$_REQUEST['mp_tardies_code']][1]['TITLE'];

	return sprintf( _( '%s in %s' ), $tardies_code_title, GetMP( $last_mp, 'TITLE' ) ) . ': ' .
		$count;
}

/**
 * Year to Date Tardies.
 *
 * @uses _getAttendanceRET()
 * @uses _getOtherAttendanceCodes()
 *
 * @param  string $st_list     Student List
 * @param  string $student_id  Student ID
 * @return string "[attendance code] this year: x"
 */
function GetYTDTardies( $st_list, $student_id )
{
	// Other Attendance this quarter or Other Attendance Year-to-date.
	$attendance_RET = _getAttendanceRET( $st_list );

	// Get Other Attendance Codes.
	$other_attendance_codes = _getOtherAttendanceCodes();

	$count = 0;

	if ( ! empty( $attendance_RET[$student_id][$_REQUEST['ytd_tardies_code']] ) )
	{
		foreach ( (array) $attendance_RET[$student_id][$_REQUEST['ytd_tardies_code']] as $mp_abs )
		{
			foreach ( (array) $mp_abs as $abs )
			{
				$count++;
			}
		}
	}

	$tardies_code_title = $other_attendance_codes[$_REQUEST['ytd_tardies_code']][1]['TITLE'];

	return sprintf( _( '%s this year' ), $tardies_code_title ) . ': ' . $count;
}

/**
 * Other Attendance this quarter or Other Attendance Year-to-date.
 * Local function.
 *
 * @param  string $st_list   Student List
 * @return array  Attendance RET
 */
function _getAttendanceRET( $st_list )
{
	/**
	 * @var mixed
	 */
	static $attendance_RET = null,
		$last_st_list;

	if ( ! $attendance_RET
		|| $last_st_list !== $st_list )
	{
		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		$extra['SELECT_ONLY'] = "ap.SCHOOL_DATE,ap.COURSE_PERIOD_ID,ac.ID AS ATTENDANCE_CODE,
			ap.MARKING_PERIOD_ID,ssm.STUDENT_ID";

		$extra['FROM'] = ",attendance_codes ac,attendance_period ap";

		$extra['WHERE'] .= " AND ac.ID=ap.ATTENDANCE_CODE
			AND (ac.DEFAULT_CODE!='Y' OR ac.DEFAULT_CODE IS NULL)
			AND ac.SYEAR=ssm.SYEAR
			AND ap.STUDENT_ID=ssm.STUDENT_ID";

		$extra['group'] = [ 'STUDENT_ID', 'ATTENDANCE_CODE', 'MARKING_PERIOD_ID' ];

		// Parent: associated students.
		$extra['ASSOCIATED'] = User( 'STAFF_ID' );

		$attendance_RET = GetStuList( $extra );
	}

	$last_st_list = $st_list;

	return $attendance_RET;
}

/**
 * Other Attendace Codes.
 * Local function.
 *
 * @return array
 */
function _getOtherAttendanceCodes()
{
	/**
	 * @var mixed
	 */
	static $other_attendance_codes = null;

	if ( ! $other_attendance_codes )
	{
		// Get Other Attendance Codes.
		$other_attendance_codes = DBGet( "SELECT SHORT_NAME,ID,TITLE
			FROM attendance_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL)
			AND TABLE_NAME='0'", [], [ 'ID' ] );
	}

	return $other_attendance_codes;
}


/**
 * Get Report Cards Comments
 *
 * @since 5.0
 *
 * @example $rc_comments_RET = GetReportCardsComments( $st_list, $mp_list );
 *
 * @param  array $st_list Students list.
 * @param  array $mp_list MPs list.
 *
 * @return array $rc_comments_RET
 */
function GetReportCardsComments( $st_list, $mp_list )
{
	// GET THE COMMENTS.
	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

	// Order General Comments first.
	$extra['SELECT_ONLY'] = "s.STUDENT_ID,sc.COURSE_PERIOD_ID,sc.MARKING_PERIOD_ID,
	sc.REPORT_CARD_COMMENT_ID,sc.COMMENT,
	(SELECT SORT_ORDER
		FROM report_card_comments
		WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER,
	(SELECT COALESCE(SCALE_ID, 0)
		FROM report_card_comments
		WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER2";

	$extra['FROM'] = ",student_report_card_comments sc";

	// Get the comments of all MPs.
	//$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID='".$last_mp."'";
	$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID IN (" . $mp_list . ")";

	$extra['ORDER_BY'] = 'SORT_ORDER,SORT_ORDER2';

	$extra['group'] = [ 'STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' ];

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	$rc_comments_RET = GetStuList( $extra );

	//echo '<pre>'; print_r($rc_comments_RET); echo '</pre>'; exit;

	return $rc_comments_RET;
}


/**
 * Get Course Comment Code Scales
 *
 * @example $comment_scales = GetReportCardCommentScales( $student_id, $course_periods_list );
 *
 * @since 5.0
 *
 * @param int    $student_id          Student ID.
 * @param string $course_periods_list Course Periods present on the Student Report Card list. Comma-separated list.
 *
 * @return array Course Comment Code Scales, 1 formatted string per scale.
 */
function GetReportCardCommentScales( $student_id, $course_periods_list )
{
	static $comment_codes_RET = null;

	if ( ! $comment_codes_RET )
	{
		// Limit code scales to the ones in current SYEAR in report_card_comments.
		//$comment_codes_RET = DBGet( "SELECT cc.TITLE,cc.COMMENT,cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT FROM report_card_comment_codes cc, report_card_comment_code_scales cs WHERE cc.SCHOOL_ID='".UserSchool()."' AND cs.ID=cc.SCALE_ID ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,cs.ID,cc.SORT_ORDER IS NULL,cc.SORT_ORDER,cc.ID" );
		$comment_codes_RET = DBGet( "SELECT cs.ID AS SCALE_ID,cc.TITLE,cc.COMMENT,
			cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT
		FROM report_card_comment_codes cc, report_card_comment_code_scales cs
		WHERE cc.SCHOOL_ID='" . UserSchool() . "'
		AND cs.ID=cc.SCALE_ID
		AND cc.SCALE_ID IN (SELECT DISTINCT c.SCALE_ID
			FROM report_card_comments c
			WHERE c.SYEAR='" . UserSyear() . "'
			AND c.SCHOOL_ID=cc.SCHOOL_ID
			AND c.SCALE_ID IS NOT NULL)
		ORDER BY cc.SORT_ORDER IS NULL,cc.SORT_ORDER,cc.ID" );
	}

	$student_comment_scales_RET = DBGet( "SELECT cs.ID
	FROM report_card_comment_code_scales cs
	WHERE cs.ID IN
		(SELECT c.SCALE_ID
		FROM report_card_comments c
		WHERE (c.COURSE_ID IN(SELECT COURSE_ID
			FROM schedule
			WHERE STUDENT_ID='" . (int) $student_id . "'
			AND COURSE_PERIOD_ID IN(" . $course_periods_list . "))
			OR c.COURSE_ID=0)
		AND c.SCHOOL_ID=cs.SCHOOL_ID
		AND c.SYEAR='" . UserSyear() . "')
	AND cs.SCHOOL_ID='" . UserSchool() . "'", [], [ 'ID' ] );

	$student_comment_scales = array_keys( $student_comment_scales_RET );

	$comments = [];

	$scale_titles = [];

	$scale_title = '';

	foreach ( (array) $comment_codes_RET as $comment )
	{
		// Limit comment scales to the ones used in student's courses.
		if ( ! in_array( $comment['SCALE_ID'], $student_comment_scales ) )
		{
			continue;
		}

		if ( $scale_title != $comment['SCALE_TITLE'] )
		{
			$scale_titles[ $comment['SCALE_ID'] ] = FormatInputTitle(
				$comment['SCALE_TITLE'] . ( ! empty( $comment['SCALE_COMMENT'] ) ?
					', ' . $comment['SCALE_COMMENT'] : '' )
			);
		}

		if ( ! isset( $comments[ $comment['SCALE_ID'] ] ) )
		{
			$comments[ $comment['SCALE_ID'] ] = [];
		}

		$comments[ $comment['SCALE_ID'] ][] = '(' . $comment['TITLE'] . ') ' . $comment['COMMENT'];

		$scale_title = $comment['SCALE_TITLE'];
	}

	$comments_scales = [];

	foreach ( $comments as $scale_id => $comments_array )
	{
		$comment_scales[] = implode( '<br />', $comments_array ) . $scale_titles[ $scale_id ];
	}

	return $comment_scales;
}


/**
 * Get General Comment Codes
 *
 * @example $general_comments = GetReportCardGeneralComments( $student_id, $comments_arr );
 *
 * @since 5.0
 *
 * @param int   $student_id     Student ID.
 * @param array $comments_array Student Comments array, as generated by ReportCardsGenerate().
 *
 * @return string General Comment Codes.
 */
function GetReportCardGeneralComments( $student_id, $comments_array )
{
	static $commentsB_RET = null;

	if ( ! $commentsB_RET )
	{
		$commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM report_card_comments
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID IS NULL", [], [ 'ID' ] );
	}

	$personalizations = _getReportCardCommentPersonalizations( $student_id );

	$commentsB_displayed = [];

	$general_comments = [];

	foreach ( (array) $comments_array as $comment_course_title => $comments )
	{
		foreach ( (array) $comments as $comment => $sort_order )
		{
			if ( empty( $commentsB_RET[$comment] )
				|| in_array( $commentsB_RET[$comment][1]['SORT_ORDER'], $commentsB_displayed ) )
			{
				continue;
			}

			$general_comments[] = $commentsB_RET[$comment][1]['SORT_ORDER'] . ': ' .
			str_replace(
				array_keys( $personalizations ),
				$personalizations,
				$commentsB_RET[$comment][1]['TITLE']
			);

			$commentsB_displayed[] = $commentsB_RET[$comment][1]['SORT_ORDER'];
		}
	}

	if ( ! $general_comments )
	{
		// No General Comments.
		$general_comments = '';
	}
	else
	{
		$general_comments = implode( '<br />', $general_comments );

		$general_comments .= FormatInputTitle( _( 'General Comments' ) );
	}

	return $general_comments;
}

/**
 * Get Course Specific Comment Code Scales
 *
 * @example $course_specific_comments = GetReportCardCourseSpecificComments( $student_id, $comments_arr );
 *
 * @since 5.0
 *
 * @param int   $student_id     Student ID.
 * @param array $comments_array Student Comments array, as generated by ReportCardsGenerate().
 *
 * @return array Course Specific Comment Code Scales, 1 formatted string per course.
 */
function GetReportCardCourseSpecificComments( $student_id, $comments_array )
{
	static $commentsA_RET = null;

	if ( ! $commentsA_RET )
	{
		// Get color for Course specific categories & get comment scale.
		//$commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NOT NULL AND COURSE_ID!='0'",array(),array('ID'));
		$commentsA_RET = DBGet( "SELECT c.ID,c.TITLE,c.SORT_ORDER,cc.COLOR,
			cs.TITLE AS SCALE_TITLE
		FROM report_card_comments c, report_card_comment_categories cc,
			report_card_comment_code_scales cs
		WHERE c.SCHOOL_ID='" . UserSchool() . "'
		AND c.SYEAR='" . UserSyear() . "'
		AND c.COURSE_ID IS NOT NULL
		AND c.COURSE_ID!='0'
		AND cc.SYEAR=c.SYEAR
		AND cc.SCHOOL_ID=c.SCHOOL_ID
		AND cc.COURSE_ID=c.COURSE_ID
		AND cc.ID=c.CATEGORY_ID
		AND cs.SCHOOL_ID=c.SCHOOL_ID
		AND cs.ID=c.SCALE_ID
		ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER,c.ID", [], [ 'ID' ] );
	}

	$personalizations = _getReportCardCommentPersonalizations( $student_id );

	$course_comments = [];

	$course_title = '';

	$i = 0;

	foreach ( (array) $comments_array as $comment_course_title => $comments )
	{
		$course_comments[ $comment_course_title ] = [];

		foreach ( (array) $comments as $comment => $sort_order )
		{
			if ( empty( $commentsA_RET[$comment] ) )
			{
				continue;
			}

			$color = $commentsA_RET[$comment][1]['COLOR'];

			if ( $color )
			{
				$color_html = '<span style="color:' . $color . '">';
			}
			else
			{
				$color_html = '';
			}

			$course_comments[ $comment_course_title ][] = $color_html .
			$commentsA_RET[$comment][1]['SORT_ORDER'] . '. ' .
			str_replace(
				array_keys( $personalizations ),
				$personalizations,
				$commentsA_RET[$comment][1]['TITLE']
			) .
			( $color_html ? '</span>' : '' ) .
			' <small>(' . $commentsA_RET[$comment][1]['SCALE_TITLE'] . ')</small>';
		}

		if ( $course_comments[ $comment_course_title ] )
		{
			$course_comments[ $comment_course_title ] = implode( '<br />', $course_comments[ $comment_course_title ] ) .
				FormatInputTitle( $comment_course_title );
		}
	}

	return $course_comments;
}


/**
 * Get Comment Personalizations
 * Replace ^n with Student first name
 * Replace ^s with Student gender.
 *
 * Local function
 *
 * @example $personalizations = _getReportCardCommentPersonalizations( $student_id );
 *
 * @since 5.0
 *
 * @param  int   $student_id Student ID.
 *
 * @return array Comment Personalizations
 */
function _getReportCardCommentPersonalizations( $student_id )
{
	static $gender_field_type = null;

	if ( ! $gender_field_type )
	{
		$gender_field_type = DBGetOne( "SELECT TYPE
		FROM custom_fields
		WHERE ID=200000000" );
	}

	$student_RET = DBGet( "SELECT CUSTOM_200000000 AS GENDER,FIRST_NAME
		FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	// Gender field.
	$gender = 'M';

	if ( $gender_field_type === 'select' )
	{
		if ( ! empty( $student_RET[1]['GENDER'] )
			&& mb_substr( $student_RET[1]['GENDER'], 0, 1 ) === 'F' )
		{
			$gender = 'F';
		}
	}

	$personalizations = [
		'^n' => ( $student_RET[1]['FIRST_NAME'] ),
		'^s' => ( $gender == 'M' ? _( 'his' ) :
			( $gender == 'F' ? _( 'her' ) : _( 'his/her' ) ) ) ];

	return $personalizations;
}


/**
 * Get Report Card Min. and Max. Grades
 *
 * @since 5.0
 * @since 8.8 Add Min. and Max. GPA to Last row
 *
 * @param array $course_periods Course Periods array, with MPs array.
 *
 * @return array Updated $grades_RET.
 */
function GetReportCardMinMaxGrades( $course_periods )
{
	static $min_max_grades = [];

	$mp_list = $cp_list = [];

	foreach ( (array) $course_periods as $course_period_id => $mps )
	{
		$cp_list[] = $course_period_id;

		foreach ( (array) $mps as $mp )
		{
			$mp_list[$mp[1]['MARKING_PERIOD_ID']] = $mp[1]['MARKING_PERIOD_ID'];
		}
	}

	$mp_list = "'" . implode( "','", $mp_list ) . "'";

	$cp_list = "'" . implode( "','", $cp_list ) . "'";

	if ( ! isset( $min_max_grades[$cp_list][$mp_list] ) )
	{
		// Get Min. Max. Grades for each CP, and each MP.
		$min_max_grades[$cp_list][$mp_list] = DBGet( "SELECT COURSE_PERIOD_ID,MARKING_PERIOD_ID,
			MIN(GRADE_PERCENT) AS GRADE_MIN,MAX(GRADE_PERCENT) AS GRADE_MAX
			FROM student_report_card_grades
			WHERE SYEAR='" . UserSyear() . "'
			AND COURSE_PERIOD_ID IN(" . $cp_list . ")
			AND MARKING_PERIOD_ID IN(" . $mp_list . ")
			GROUP BY COURSE_PERIOD_ID,MARKING_PERIOD_ID", [], [ 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' ] );

		if ( ! empty( $_REQUEST['elements']['last_row'] )
			&& in_array( 'gpa', $_REQUEST['elements']['last_row'] ) )
		{
			// @since 8.8 Add Min. and Max. GPA to Last row.
			$min_max_grades[$cp_list][$mp_list]['-1'] = DBGet( "SELECT '-1' AS COURSE_PERIOD_ID,MARKING_PERIOD_ID,
				MIN(CUM_WEIGHTED_GPA) AS GRADE_MIN,MAX(CUM_WEIGHTED_GPA) AS GRADE_MAX
				FROM transcript_grades
				WHERE SYEAR='" . UserSyear() . "'
				AND MARKING_PERIOD_ID IN(" . $mp_list . ")
				AND STUDENT_ID IN(SELECT STUDENT_ID
					FROM student_report_card_grades
					WHERE SYEAR='" . UserSyear() . "'
					AND COURSE_PERIOD_ID IN(" . $cp_list . ")
					AND MARKING_PERIOD_ID IN(" . $mp_list . "))
				GROUP BY MARKING_PERIOD_ID", [], [ 'MARKING_PERIOD_ID' ] );
		}
	}

	return $min_max_grades[$cp_list][$mp_list];
}


/**
 * Add Report Card Min. and Max. Grades before and after student Grade for each Course & each MP.
 * Update MP columns text: "Min. [MP] Max.".
 *
 * @since 5.0
 * @since 10.2.1 CSS Add .grade-minmax-wrap,.grade-minmax-min,.grade-minmax-grade & .grade-minmax-max classes & avoid breaking grades
 *
 * @param array $min_max_grades Min. and Max. Grades.
 * @param array $grades_RET     Student Report Card Grades list array.
 * @param array &$LO_columns    List columns.
 *
 * @return array Updated $grades_RET.
 */
function AddReportCardMinMaxGrades( $min_max_grades, $grades_RET, &$LO_columns )
{
	static $columns_done = array();

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

	$grades_loop = $grades_RET;

	foreach ( (array) $grades_loop as $i => $grade )
	{
		if ( empty( $grade['COURSE_PERIOD_ID'] ) )
		{
			continue;
		}

		$cp_id = $grade['COURSE_PERIOD_ID'];

		$min_max_grades_cp = issetVal( $min_max_grades[ $cp_id ] );

		foreach ( (array) $min_max_grades_cp as $mp_id => $min_max )
		{
			if ( ! isset( $grades_RET[$i][$mp_id] ) )
			{
				continue;
			}

			$min_grade = issetVal( $min_max[1]['GRADE_MIN'], '0' );
			$max_grade = issetVal( $min_max[1]['GRADE_MAX'], '0' );

			if ( $cp_id > 0 )
			{
				$min_grade = _makeLetterGrade( $min_grade / 100, $cp_id );
				$max_grade = _makeLetterGrade( $max_grade / 100, $cp_id );
			}
			elseif ( $cp_id === '-1' )
			{
				// CP ID=-1 is Total GPA, format float.
				$min_grade = number_format( $min_grade, 2, '.', '' );
				$max_grade = number_format( $max_grade, 2, '.', '' );
			}

			$grades_RET[$i][$mp_id] = '<div class="grade-minmax-wrap"><div class="grade-minmax-min">' . $min_grade . '</div>
				<div class="grade-minmax-grade">' . $grades_RET[$i][$mp_id] . '</div>
				<div class="grade-minmax-max">' . $max_grade . '</div></div>';

			if ( ! empty( $columns_done[$mp_id] ) )
			{
				continue;
			}

			$LO_columns[$mp_id] = '<div class="grade-minmax-wrap"><div class="grade-minmax-min">' . _( 'Min.' ) . '</div>
				<div class="grade-minmax-grade">' . GetMP( $mp_id, 'SHORT_NAME' ) . '</div>
				<div class="grade-minmax-max">' . _( 'Max.' ) . '</div></div>';

			$columns_done[$mp_id] = true;
		}
	}

	return $grades_RET;
}
