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
		global $extra;

		$other_attendance_codes = _getOtherAttendanceCodes();

		if ( $title === 'Include on Report Card' )
		{
			$title = _( 'Include on Report Card' );
		}

		// Open table.
		$return = '<table class="width-100p"><tr><td colspan="2"><b>' . $include_on_title .
			'</b></td></tr><tr><td colspan="2"><table class="cellpadding-5"><tr class="st">';

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

		// Year-to-date Daily Absences.
		$return .= '<td><label><input type="checkbox" name="elements[ytd_absences]" value="Y" checked /> ' .
		_( 'Year-to-date Daily Absences' ) . '</label></td>';

		$return .= '</tr><tr class="st">';

		// Daily Absences this quarter.
		$return .= '<td><label><input type="checkbox" name="elements[mp_absences]" value="Y"' .
		( GetMP( UserMP(), 'SORT_ORDER' ) != 1 ? ' checked' : '' ) . ' /> ' .
		_( 'Daily Absences this quarter' ) . '</label></td>';

		// Period-by-period absences.
		$return .= '<td><label><input type="checkbox" name="elements[period_absences]" value="Y" /> ' .
		_( 'Period-by-period absences' ) . '</label></td>';

		$return .= '</tr><tr class="st">';

		// Other Attendance Year-to-date.
		$return .= '<td><label><input type="checkbox" name="elements[ytd_tardies]" value="Y" /> ' .
		_( 'Other Attendance Year-to-date' ) . ':</label> <select name="ytd_tardies_code" id="ytd_tardies_code">';

		foreach ( (array) $other_attendance_codes as $code )
		{
			$return .= '<option value="' . $code[1]['ID'] . '">' . $code[1]['TITLE'] . '</option>';
		}

		$return .= '</select>
			<label for="ytd_tardies_code" class="a11y-hidden">' . _( 'Attendance Codes' ) . '</label></td>';
		// Other Attendance this quarter.
		$return .= '<td><label><input type="checkbox" name="elements[mp_tardies]" value="Y" /> ' .
		_( 'Other Attendance this quarter' ) . ':</label> <select name="mp_tardies_code" id="mp_tardies_code">';

		foreach ( (array) $other_attendance_codes as $code )
		{
			$return .= '<option value="' . $code[1]['ID'] . '">' . $code[1]['TITLE'] . '</option>';
		}

		$return .= '</select>
			<label for="mp_tardies_code" class="a11y-hidden">' . _( 'Attendance Codes' ) . '</label></td></tr>';

		$return .= '</tr></table></td></tr>';

		// FJ get the title instead of the short marking period name.
		$mps_RET = DBGet( "SELECT PARENT_ID,MARKING_PERIOD_ID,SHORT_NAME,TITLE
			FROM SCHOOL_MARKING_PERIODS
			WHERE MP='QTR'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER", array(), array( 'PARENT_ID' ) );

		// Marking Periods.
		$return .= '<tr class="st"><td colspan="2"><hr /><table class="cellpadding-5">';

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
								<input type="checkbox" name="mp_arr[]" value="' . $pro . '" /> ' .
							GetMP( $pro, 'TITLE' ) . '</label></td>';
						}
					}
				}

				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . $qtr['MARKING_PERIOD_ID'] . '" /> ' .
					$qtr['TITLE'] . '</label></td>';
			}

			if ( GetMP( $sem, 'DOES_GRADES' ) === 'Y' )
			{
				$return .= '<td><label>
					<input type="checkbox" name="mp_arr[]" value="' . $sem . '" /> ' .
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
					<input type="checkbox" name="mp_arr[]" value="' . $fy . '" /> ' .
				GetMP( $fy, 'TITLE' ) . '</label></td>';
			}

			$return .= '</tr>';
		}

		$return .= '</table>' .
			FormatInputTitle( _( 'Marking Periods' ), '', false, '' ) .
			'<hr /></td></tr>';

		if ( $mailing_labels )
		{
			// Mailing Labels.
			Widgets( 'mailing_labels' );
		}

		if ( $extra['search'] )
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
	 * @todo Divide in smaller functions: ReportCardComments...
	 *
	 * @example $report_cards = ReportCardsGenerate( $_REQUEST['st_arr'], $_REQUEST['mp_arr'] );
	 *
	 * @since 4.0 Define your custom function in your addon module or plugin.
	 * @since 4.5 Add Report Cards PDF header action hook.
	 *
	 * @uses _makeTeacher() see below
	 *
	 * @param  array         $student_array Students IDs
	 * @param  array         $mp_array      Marking Periods IDs
	 * @return boolean|array False if No Students or Report Cards associative array (key = $student_id)
	 */
	function ReportCardsGenerate( $student_array, $mp_array )
	{
		global $_ROSARIO,
			$count_lines;

		if ( empty( $student_array )
			|| empty( $mp_array ) )
		{
			return false;
		}

		$mp_list = "'" . implode( "','", $mp_array ) . "'";

		$st_list = "'" . implode( "','", $student_array ) . "'";

		$last_mp = end( $mp_array );

		$extra = GetReportCardsExtra( $mp_list, $st_list );

		// Comments.

		if ( $_REQUEST['elements']['comments'] === 'Y' )
		{
			// Gender field.
			$gender_field_RET = DBGet( "SELECT ID,TYPE
			FROM CUSTOM_FIELDS
			WHERE ID=200000000", array(), array( 'ID' ) );

			if ( $gender_field_RET
				&& $gender_field_RET['200000000'][1]['TYPE'] === 'select' )
			{
				$extra['SELECT'] .= ',s.CUSTOM_200000000 AS GENDER';
			}
			else
			{
				$extra['SELECT'] .= ",'" . _( 'None' ) . "' AS GENDER";
			}
		}

		$student_RET = GetStuList( $extra );

		if ( empty( $student_RET ) )
		{
			return false;
		}

		// Comments.

		if ( $_REQUEST['elements']['comments'] === 'Y' )
		{
			// GET THE COMMENTS.
			unset( $extra );

			$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

			// FJ order General Comments first.
			$extra['SELECT_ONLY'] = "s.STUDENT_ID,sc.COURSE_PERIOD_ID,sc.MARKING_PERIOD_ID,
			sc.REPORT_CARD_COMMENT_ID,sc.COMMENT,
			(SELECT SORT_ORDER
				FROM REPORT_CARD_COMMENTS
				WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER,
			(SELECT COALESCE(SCALE_ID, 0)
				FROM REPORT_CARD_COMMENTS
				WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER2";

			$extra['FROM'] = ",STUDENT_REPORT_CARD_COMMENTS sc";

			// FJ get the comments of all MPs.
			//$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID='".$last_mp."'";
			$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID IN (" . $mp_list . ")";

			$extra['ORDER_BY'] = 'SORT_ORDER,SORT_ORDER2';

			$extra['group'] = array( 'STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' );

			// Parent: associated students.
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );

			$comments_RET = GetStuList( $extra );

			//echo '<pre>'; print_r($comments_RET); echo '</pre>'; exit;

			$all_commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
			FROM REPORT_CARD_COMMENTS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND COURSE_ID IS NOT NULL
			AND COURSE_ID='0'
			ORDER BY SORT_ORDER,ID", array(), array( 'ID' ) );

			// FJ get color for Course specific categories & get comment scale.
			//$commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NOT NULL AND COURSE_ID!='0'",array(),array('ID'));
			$commentsA_RET = DBGet( "SELECT c.ID,c.TITLE,c.SORT_ORDER,cc.COLOR,
				cs.TITLE AS SCALE_TITLE
			FROM REPORT_CARD_COMMENTS c, REPORT_CARD_COMMENT_CATEGORIES cc,
				REPORT_CARD_COMMENT_CODE_SCALES cs
			WHERE c.SCHOOL_ID='" . UserSchool() . "'
			AND c.SYEAR='" . UserSyear() . "'
			AND c.COURSE_ID IS NOT NULL
			AND c.COURSE_ID!='0'
			AND cc.SYEAR=c.SYEAR
			AND cc.SCHOOL_ID=c.SCHOOL_ID
			AND cc.COURSE_ID=c.COURSE_ID
			AND cc.ID=c.CATEGORY_ID
			AND cs.SCHOOL_ID=c.SCHOOL_ID
			AND cs.ID=c.SCALE_ID", array(), array( 'ID' ) );

			$commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
			FROM REPORT_CARD_COMMENTS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND COURSE_ID IS NULL", array(), array( 'ID' ) );
		}

		// Mailing Labels.

		if ( $_REQUEST['mailing_labels'] === 'Y' )
		{
			// GET THE ADDRESSES.
			unset( $extra );

			$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

			$extra['SELECT'] = 's.STUDENT_ID';

			Widgets( 'mailing_labels' );

			$extra['SELECT_ONLY'] = $extra['SELECT'];

			$extra['SELECT'] = '';

			$extra['group'] = array( 'STUDENT_ID', 'ADDRESS_ID' );

			// Parent: associated students.
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );

			$addresses_RET = GetStuList( $extra );
		}

		//FJ limit code scales to the ones in current SYEAR in REPORT_CARD_COMMENTS
		//$comment_codes_RET = DBGet( "SELECT cc.TITLE,cc.COMMENT,cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT FROM REPORT_CARD_COMMENT_CODES cc, REPORT_CARD_COMMENT_CODE_SCALES cs WHERE cc.SCHOOL_ID='".UserSchool()."' AND cs.ID=cc.SCALE_ID ORDER BY cs.SORT_ORDER,cs.ID,cc.SORT_ORDER,cc.ID" );
		$comment_codes_RET = DBGet( "SELECT cs.ID AS SCALE_ID,cc.TITLE,cc.COMMENT,
			cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT
		FROM REPORT_CARD_COMMENT_CODES cc, REPORT_CARD_COMMENT_CODE_SCALES cs
		WHERE cc.SCHOOL_ID='" . UserSchool() . "'
		AND cs.ID=cc.SCALE_ID
		AND cc.SCALE_ID IN (SELECT DISTINCT c.SCALE_ID
			FROM REPORT_CARD_COMMENTS c
			WHERE c.SYEAR='" . UserSyear() . "'
			AND c.SCHOOL_ID=cc.SCHOOL_ID
			AND c.SCALE_ID IS NOT NULL)
		ORDER BY cs.SORT_ORDER,cs.ID,cc.SORT_ORDER,cc.ID" );

		// ListOutput columns.
		$LO_columns = array( 'COURSE_TITLE' => _( 'Course' ) );

		if ( $_REQUEST['elements']['teacher'] === 'Y' )
		{
			$LO_columns['TEACHER_ID'] = _( 'Teacher' );
		}

		if ( $_REQUEST['elements']['period_absences'] === 'Y' )
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

		if ( $_REQUEST['elements']['comments'] === 'Y' )
		{
			foreach ( (array) $all_commentsA_RET as $comment )
			{
				$LO_columns['C' . $comment[1]['ID']] = $comment[1]['TITLE'];
			}

			$LO_columns['COMMENT'] = _( 'Comments' );
		}

		// Report Cards array.
		$report_cards = array();

		foreach ( (array) $student_RET as $student_id => $course_periods )
		{
			// Start buffer.
			ob_start();

			$comments_arr = array();

			$comments_arr_key = ! empty( $all_commentsA_RET );

			unset( $grades_RET );

			$i = 0;

			// Course Periods.

			foreach ( (array) $course_periods as $course_period_id => $mps )
			{
				$i++;

				$grades_RET[$i]['COURSE_TITLE'] = $mps[key( $mps )][1]['COURSE_TITLE'];

				$grades_RET[$i]['TEACHER_ID'] = GetTeacher( $mps[key( $mps )][1]['TEACHER_ID'] );

				foreach ( (array) $mp_array as $mp )
				{
					if ( ! isset( $mps[$mp] ) )
					{
						continue;
					}

					$grades_RET[$i][$mp] = '<B>' . $mps[$mp][1]['GRADE_TITLE'] . '</B>';

					if ( $_REQUEST['elements']['percents'] === 'Y'
						&& $mps[$mp][1]['GRADE_PERCENT'] > 0 )
					{
						$grades_RET[$i][$mp] .= '&nbsp;' . $mps[$mp][1]['GRADE_PERCENT'] . '%';
					}

					// Comments.

					if ( $_REQUEST['elements']['comments'] === 'Y' )
					{
						$sep = '; ';

						$sep_mp = ' | ';

						$grades_RET[$i]['COMMENT'] .= ( empty( $grades_RET[$i]['COMMENT'] ) ? '' : $sep_mp );

						$temp_grades_COMMENTS = $grades_RET[$i]['COMMENT'];

						// FJ fix error Invalid argument supplied for foreach().

						foreach ( (array) $comments_RET[$student_id][$course_period_id][$mp] as $comment )
						{
							if ( $all_commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']] )
							{
								$grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] .= $comment['COMMENT'] != ' ' ?
								( empty( $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] ) ?
									'' :
									$sep_mp ) .
								$comment['COMMENT'] :
								( empty( $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] ) ?
									'' :
									$sep_mp ) .
									'&middot;';
							}
							else
							{
								$sep_tmp = empty( $grades_RET[$i]['COMMENT'] )
								|| mb_substr( $grades_RET[$i]['COMMENT'], -3 ) == $sep_mp ?
								'' :
								$sep;

								if ( $commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']] )
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

									$grades_RET[$i]['COMMENT'] .= $sep_tmp . $color_html .
										$commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];

									$grades_RET[$i]['COMMENT'] .= '(' . ( $comment['COMMENT'] != ' ' ?
										$comment['COMMENT'] :
										'&middot;' ) .
										')' . ( $color_html ? '</span>' : '' );

									$comments_arr_key = true;
								}
								else
								{
									$grades_RET[$i]['COMMENT'] .= $sep_tmp .
										$commentsB_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];
								}

								$comments_arr[$grades_RET[$i]['COURSE_TITLE']][$comment['REPORT_CARD_COMMENT_ID']] = $comment['SORT_ORDER'];
							}
						}

						if ( $mps[$mp][1]['COMMENT_TITLE'] )
						{
							$grades_RET[$i]['COMMENT'] .= ( empty( $grades_RET[$i]['COMMENT'] )
								|| mb_substr( $grades_RET[$i]['COMMENT'], -3 ) == $sep_mp ?
								'' :
								$sep ) .
								$mps[$mp][1]['COMMENT_TITLE'];
						}

						if ( $grades_RET[$i]['COMMENT'] == $temp_grades_COMMENTS )
						{
							$grades_RET[$i]['COMMENT'] .= ( empty( $grades_RET[$i]['COMMENT'] )
								|| mb_substr( $grades_RET[$i]['COMMENT'], -3 ) == $sep_mp ?
								'' :
								$sep ) .
							_( 'None' );
						}
					}

					$last_mp = $mp;
				}

				// Period-by-period absences.

				if ( $_REQUEST['elements']['period_absences'] === 'Y' )
				{
					if ( $mps[$last_mp][1]['DOES_ATTENDANCE'] )
					{
						$grades_RET[$i]['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'] . ' / ' .
							$mps[$last_mp][1]['MP_ABSENCES'];
					}
					else
					{
						$grades_RET[$i]['ABSENCES'] = _( 'N/A' );
					}
				}
			}

			asort( $comments_arr, SORT_NUMERIC );

			// Mailing Labels.

			if ( $_REQUEST['mailing_labels'] === 'Y' )
			{
				if ( ! empty( $addresses_RET[$student_id] ) )
				{
					$addresses = $addresses_RET[$student_id];
				}
				else
				{
					$addresses = array( 0 => array( 1 => array(
						'STUDENT_ID' => $student_id,
						'ADDRESS_ID' => '0',
						'MAILING_LABEL' => '<BR /><BR />',
					) ) );
				}
			}
			else
			{
				$addresses = array( 0 => array() );
			}

			// Optimization: Student Full Name & Grade Level.
			$student_name_grade_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME,ssm.GRADE_ID
			FROM STUDENTS s JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID)
			WHERE s.STUDENT_ID='" . $student_id . "'
			AND ssm.SYEAR='" . UserSyear() . "'
			LIMIT 1" );

			$student_full_name = $student_name_grade_RET[1]['FULL_NAME'];

			$student_grade_level = GetGrade( $student_name_grade_RET[1]['GRADE_ID'] );

			foreach ( (array) $addresses as $address )
			{
				unset( $_ROSARIO['DrawHeader'] );

				if ( $_REQUEST['mailing_labels'] === 'Y' )
				{
					echo '<BR /><BR /><BR />';
				}

				// FJ add school logo.
				$logo_pic = 'assets/school_logo_' . UserSchool() . '.jpg';

				$picwidth = 120;

				if ( file_exists( $logo_pic ) )
				{
					echo '<table class="width-100p"><tr>
					<td style="width:' . $picwidth . 'px;">
						<img src="' . $logo_pic . '" width="' . $picwidth . '" />
					</td>
					<td>';
				}

				// Headers.
				DrawHeader( _( 'Report Card' ) );

				// TOCHECK! test headers.
				DrawHeader( $student_full_name, $student_id );

				DrawHeader( $student_grade_level, SchoolInfo( 'TITLE' ) );

				// FJ add school year.
				DrawHeader( _( 'School Year' ) . ': ' .
					FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) );

				$count_lines = 4;

				$mp_absences = '';

				// Marking Period-by-period absences.

				if ( $_REQUEST['elements']['mp_absences'] === 'Y' )
				{
					$mp_absences = GetMPAbsences( $st_list, $last_mp, $student_id );
				}

				// Year-to-date Daily Absences.

				if ( $_REQUEST['elements']['ytd_absences'] === 'Y' )
				{
					DrawHeader( GetYTDAbsences( $st_list, $last_mp, $student_id ), $mp_absences );

					$count_lines++;
				}
				elseif ( $_REQUEST['elements']['mp_absences'] === 'Y' )
				{
					DrawHeader( $mp_absences );

					$count_lines++;
				}

				$mp_tardies = '';

				// Marking Period Tardies.

				if ( $_REQUEST['elements']['mp_tardies'] === 'Y' )
				{
					$mp_tardies = GetMPTardies( $st_list, $last_mp, $student_id );
				}

				// Year to Date Tardies.

				if ( $_REQUEST['elements']['ytd_tardies'] === 'Y' )
				{
					DrawHeader( GetYTDTardies( $st_list, $student_id ), $mp_tardies );

					$count_lines++;
				}
				elseif ( $_REQUEST['elements']['mp_tardies'] === 'Y' )
				{
					DrawHeader( $mp_tardies );

					$count_lines++;
				}

				// @since 4.5 Add Report Cards PDF header action hook.
				do_action( 'Grades/includes/ReportCards.fnc.php|pdf_header', $student_id );

				// FJ add school logo.

				if ( file_exists( $logo_pic ) )
				{
					echo '</td></tr></table>';

					$count_lines++;
				}

				// Mailing Labels.

				if ( $_REQUEST['mailing_labels'] === 'Y' )
				{
					DrawHeader( ProperDate( DBDate() ) );

					$count_lines++;

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

				ListOutput( $grades_RET, $LO_columns, '.', '.', array(), array(), array( 'print' => false ) );

				// Comments.

				if ( $_REQUEST['elements']['comments'] === 'Y'
					&& ( $comments_arr_key
						|| ! empty( $comments_arr ) ) )
				{
					$gender = mb_substr( $mps[key( $mps )][1]['GENDER'], 0, 1 );

					$personalizations = array(
						'^n' => ( $mps[key( $mps )][1]['FIRST_NAME'] ),
						'^s' => ( $gender == 'M' ? _( 'his' ) :
							( $gender == 'F' ? _( 'her' ) : _( 'his/her' ) ) ) );

					// FJ limit comment scales to the ones used in student's courses.
					$course_periods_list = implode( array_keys( $course_periods ), ',' );

					$student_comment_scales_RET = DBGet( "SELECT cs.ID
					FROM REPORT_CARD_COMMENT_CODE_SCALES cs
					WHERE cs.ID IN
						(SELECT c.SCALE_ID
						FROM REPORT_CARD_COMMENTS c
						WHERE (c.COURSE_ID IN(SELECT COURSE_ID
							FROM SCHEDULE
							WHERE STUDENT_ID='" . $student_id . "'
							AND COURSE_PERIOD_ID IN(" . $course_periods_list . "))
							OR c.COURSE_ID=0)
						AND c.SCHOOL_ID=cs.SCHOOL_ID
						AND c.SYEAR='" . UserSyear() . "')
					AND cs.SCHOOL_ID='" . UserSchool() . "'", array(), array( 'ID' ) );

					$student_comment_scales = array_keys( $student_comment_scales_RET );

					$comment_sc_display = false;

					$comment_sc_txt = _( 'Comment Scales' ) . '<BR /><ul>';

					$i = 0;

					$scale_title = '';

					if ( $comments_arr_key )
					{
						foreach ( (array) $comment_codes_RET as $comment )
						{
							// FJ limit comment scales to the ones used in student's courses.

							if ( in_array( $comment['SCALE_ID'], $student_comment_scales ) )
							{
								if ( $i++ % 3 == 0
									|| $scale_title != $comment['SCALE_TITLE'] )
								{
									if ( $scale_title != $comment['SCALE_TITLE'] )
									{
										if ( $i > 1 )
										{
											$comment_sc_txt .= '</tr></table></li>';
										}

										$comment_sc_txt .= '<li>' . $comment['SCALE_TITLE'] .
											( ! empty( $comment['SCALE_COMMENT'] ) ?
											', ' . $comment['SCALE_COMMENT'] :
											'' ) .
											'<BR /><table class="width-100p"><tr>';

										$i = 4;
									}
									else
									{
										$comment_sc_txt .= '</tr><tr>';
									}
								}

								$comment_sc_txt .= '<td>(' . $comment['TITLE'] . ') ' . $comment['COMMENT'] . '</td>';

								$comment_sc_display = true;

								$scale_title = $comment['SCALE_TITLE'];
							}
						}
					}

					$comment_sc_txt .= '</tr></table></li></ul>';

					$course_title = '';

					$i = $j = 0;

					$commentsA_display = $commentsB_display = false;

					$commentsB_displayed = array();

					$commentsB_txt = _( 'General Comments' ) . '<BR /><table class="width-100p"><tr>';

					$commentsA_txt = _( 'Course-specific Comments' ) . '<BR /><ul>';

					foreach ( (array) $comments_arr as $comment_course_title => $comments )
					{
						foreach ( (array) $comments as $comment => $sort_order )
						{
							if ( $commentsA_RET[$comment] )
							{
								if ( $i++ % 2 == 0
									|| $course_title != $comment_course_title )
								{
									if ( $course_title != $comment_course_title )
									{
										if ( $i > 1 )
										{
											$commentsA_txt .= '</tr></table></li>';
										}

										$commentsA_txt .= '<li>' . $comment_course_title .
											'<BR /><table class="width-100p"><tr>';

										$i = 3;
									}
									else
									{
										$commentsA_txt .= '</tr><tr>';
									}
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

								$commentsA_txt .= '<td style="width:50%;">' . $color_html .
								$commentsA_RET[$comment][1]['SORT_ORDER'] . ': ' .
								str_replace(
									array_keys( $personalizations ),
									$personalizations,
									$commentsA_RET[$comment][1]['TITLE']
								) .
								( $color_html ? '</span>' : '' ) .
								' (' . _( 'Comment Scale' ) . ': ' .
									$commentsA_RET[$comment][1]['SCALE_TITLE'] . ')' . '</td>';

								$commentsA_display = true;

								$course_title = $comment_course_title;
							}

							if ( $commentsB_RET[$comment]
								&& ! in_array( $commentsB_RET[$comment][1]['SORT_ORDER'], $commentsB_displayed ) )
							{
								if ( $j++ % 2 == 0 )
								{
									$commentsB_txt .= '</tr><tr>';
								}

								$commentsB_txt .= '<td style="width:50%;">' .
								$commentsB_RET[$comment][1]['SORT_ORDER'] . ': ' .
								str_replace(
									array_keys( $personalizations ),
									$personalizations,
									$commentsB_RET[$comment][1]['TITLE']
								) . '</td>';

								$commentsB_display = true;

								$commentsB_displayed[] = $commentsB_RET[$comment][1]['SORT_ORDER'];
							}
						}
					}

					$commentsB_txt .= '</tr></table>';

					$commentsA_txt .= '</tr></table></li></ul>';

					echo '<b>' . _( 'Explanation of Comment Codes' ) . '</b>';

					if ( $comment_sc_display )
					{
						DrawHeader( $comment_sc_txt );
					}

					if ( $commentsA_display )
					{
						DrawHeader( $commentsA_txt );
					}

					if ( $commentsB_display )
					{
						DrawHeader( $commentsB_txt );
					}
				}
			}

			// Add buffer to Report Cards array.
			$report_cards[$student_id] = ob_get_clean();
		}

		return $report_cards;
	}
}

/**
 * Get $extra var for Report Cards.
 * To be used by GetStuList().
 *
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

	$extra['SELECT_ONLY'] .= ",sg1.GRADE_LETTER as GRADE_TITLE,sg1.GRADE_PERCENT,
		sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,
		sg1.COURSE_TITLE as COURSE_TITLE,rc_cp.TEACHER_ID,sp.SORT_ORDER";

	// Period-by-period absences.

	if ( $_REQUEST['elements']['period_absences'] === 'Y' )
	{
		$extra['SELECT_ONLY'] .= ",rc_cp.DOES_ATTENDANCE,
			(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
				WHERE ac.ID=ap.ATTENDANCE_CODE
				AND ac.STATE_CODE='A'
				AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
				AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
			(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
				WHERE ac.ID=ap.ATTENDANCE_CODE
				AND ac.STATE_CODE='A'
				AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
				AND sg1.MARKING_PERIOD_ID=cast(ap.MARKING_PERIOD_ID as text)
				AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES";
	}

	// FJ multiple school periods for a course period.
	//$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1,ATTENDANCE_CODES ac,COURSE_PERIODS rc_cp,SCHOOL_PERIODS sp";
	$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1,ATTENDANCE_CODES ac,COURSE_PERIODS rc_cp,
		SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp";

	/*$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
	AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=rc_cp.PERIOD_ID";*/
	$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (" . $mp_list . ")
					AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
					AND sg1.STUDENT_ID=ssm.STUDENT_ID
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND rc_cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID";

	$extra['ORDER'] .= ",sg1.COURSE_TITLE,sp.SORT_ORDER,ac.TITLE";

	$extra['group'] = array( 'STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID' );

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	return $extra;
}

/**
 * Make Teacher
 * DBGet callback
 * Local function
 *
 * @deprecated since 3.4.3. Use Teacher ID instead of extracting Teacher name from CP title.
 *
 * @param  string $teacher  Teacher
 * @param  string $column   'TEACHER'
 * @return string Formatted Teacher
 */
function _makeTeacher( $teacher, $column )
{
	return mb_substr( $teacher, mb_strrpos( str_replace( ' - ', ' ^ ', $teacher ), '^' ) + 2 );
}

/**
 * Marking Period-by-period absences.
 *
 * @uses _getAttendanceDayRET()
 *
 * @param  string $st_list    Student List
 * @param  string $last_mp    Last MP
 * @param  string $student_id Student ID
 * @return string "Absences in [last MP]: x"
 */
function GetMPAbsences( $st_list, $last_mp, $student_id )
{
	$attendance_day_RET = _getAttendanceDayRET( $st_list, $last_mp );

	$count = 0;

	foreach ( (array) $attendance_day_RET[$student_id][$last_mp] as $abs )
	{
		$count += 1 - $abs['STATE_VALUE'];
	}

	return sprintf( _( 'Absences in %s' ), GetMP( $last_mp, 'TITLE' ) ) . ': ' . $count;
}

/**
 * Year-to-date Daily Absences.
 *
 * @uses _getAttendanceDayRET()
 *
 * @param  string $st_list    Student List
 * @param  string $last_mp    Last MP
 * @param  string $student_id Student ID
 * @return string "Absences this year: x"
 */
function GetYTDAbsences( $st_list, $last_mp, $student_id )
{
	$attendance_day_RET = _getAttendanceDayRET( $st_list, $last_mp );

	$count = 0;

	foreach ( (array) $attendance_day_RET[$student_id] as $mp_abs )
	{
		foreach ( (array) $mp_abs as $abs )
		{
			$count += 1 - $abs['STATE_VALUE'];
		}
	}

	return _( 'Absences this year' ) . ': ' . $count;
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

		$extra['FROM'] = ",ATTENDANCE_DAY ad";

		$extra['WHERE'] .= " AND ad.STUDENT_ID=ssm.STUDENT_ID
			AND ad.SYEAR=ssm.SYEAR
			AND (ad.STATE_VALUE='0.0' OR ad.STATE_VALUE='.5')
			AND ad.SCHOOL_DATE<='" . GetMP( $last_mp, 'END_DATE' ) . "'";

		$extra['group'] = array( 'STUDENT_ID', 'MARKING_PERIOD_ID' );

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

	foreach ( (array) $attendance_RET[$student_id][$_REQUEST['mp_tardies_code']][$last_mp] as $abs )
	{
		$count++;
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

	foreach ( (array) $attendance_RET[$student_id][$_REQUEST['ytd_tardies_code']] as $mp_abs )
	{
		foreach ( (array) $mp_abs as $abs )
		{
			$count++;
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

		$extra['FROM'] = ",ATTENDANCE_CODES ac,ATTENDANCE_PERIOD ap";

		$extra['WHERE'] .= " AND ac.ID=ap.ATTENDANCE_CODE
			AND (ac.DEFAULT_CODE!='Y' OR ac.DEFAULT_CODE IS NULL)
			AND ac.SYEAR=ssm.SYEAR
			AND ap.STUDENT_ID=ssm.STUDENT_ID";

		$extra['group'] = array( 'STUDENT_ID', 'ATTENDANCE_CODE', 'MARKING_PERIOD_ID' );

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
			FROM ATTENDANCE_CODES
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL)
			AND TABLE_NAME='0'", array(), array( 'ID' ) );
	}

	return $other_attendance_codes;
}
