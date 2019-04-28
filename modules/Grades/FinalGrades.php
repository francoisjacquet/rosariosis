<?php

require_once 'modules/Grades/includes/ReportCards.fnc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Final Grade' ) ) )
	{
		$delete_sql = "DELETE FROM STUDENT_REPORT_CARD_GRADES
			WHERE SYEAR='" . UserSyear() . "'
			AND STUDENT_ID='" . $_REQUEST['student_id'] . "'
			AND COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'
			AND MARKING_PERIOD_ID='" . $_REQUEST['marking_period_id'] . "';";

		$delete_sql .= "DELETE FROM STUDENT_REPORT_CARD_COMMENTS
			WHERE SYEAR='" . UserSyear() . "'
			AND STUDENT_ID='" . $_REQUEST['student_id'] . "'
			AND COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'
			AND MARKING_PERIOD_ID='" . $_REQUEST['marking_period_id'] . "';";

		DBQuery( $delete_sql );

		$_REQUEST['modfunc'] = 'save';
	}
}
elseif ( $_REQUEST['delete_cancel']
	&& AllowEdit() )
{
	$_REQUEST['modfunc'] = 'save';
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['mp_arr'] )
		&& ! empty( $_REQUEST['st'] ) )
	{
		$mp_list = "'" . implode( "','", $_REQUEST['mp_arr'] ) . "'";

		$st_list = "'" . implode( "','", $_REQUEST['st'] ) . "'";

		$last_mp = end( $_REQUEST['mp_arr'] );

		$extra = GetReportCardsExtra( $mp_list, $st_list );

		/*$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		$extra['SELECT'] .= ",sg1.GRADE_LETTER as GRADE_TITLE,sg1.GRADE_PERCENT,sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,c.TITLE as COURSE_TITLE,rc_cp.TITLE AS TEACHER,sp.SORT_ORDER";

		if ( $_REQUEST['elements']['period_absences']=='Y')
		//modif: SQL error fix: operator does not exist: character varying = integer, add explicit type casts
		$extra['SELECT'] .= ",rc_cp.DOES_ATTENDANCE,
		(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
		WHERE ac.ID=ap.ATTENDANCE_CODE
		AND ac.STATE_CODE='A'
		AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
		AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
		(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
		WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE='A'
		AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
		AND cast(sg1.MARKING_PERIOD_ID as integer)=ap.MARKING_PERIOD_ID
		AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES";

		if ( $_REQUEST['elements']['comments']=='Y')
		$extra['SELECT'] .= ',sg1.MARKING_PERIOD_ID AS COMMENTS_RET';

		//FJ multiple school periods for a course period
		/*$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1 LEFT OUTER JOIN REPORT_CARD_GRADES rpg ON (rpg.ID=sg1.REPORT_CARD_GRADE_ID),
		COURSE_PERIODS rc_cp,COURSES c,SCHOOL_PERIODS sp";*/
		/*$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1,
		COURSE_PERIODS rc_cp,COURSES c,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp";

		/*$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
		AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND c.COURSE_ID = rc_cp.COURSE_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=rc_cp.PERIOD_ID";*/
		/*$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
		AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
		AND c.COURSE_ID = rc_cp.COURSE_ID
		AND sg1.STUDENT_ID=ssm.STUDENT_ID
		AND sp.PERIOD_ID=cpsp.PERIOD_ID
		AND rc_cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID";

		$extra['ORDER'] .= ",sg1.COURSE_TITLE,sp.SORT_ORDER,c.TITLE";

		$extra['group'] = array('STUDENT_ID');
		$extra['group'][] = 'COURSE_PERIOD_ID';
		$extra['group'][] = 'MARKING_PERIOD_ID';

		$extra['functions']['TEACHER'] = '_makeTeacher';

		// Parent: associated students.
		$extra['ASSOCIATED'] = User( 'STAFF_ID' );*/

		/*if ( $_REQUEST['elements']['comments']=='Y')
		$extra['functions']['COMMENTS_RET'] = '_makeComments';*/

		$RET = GetStuList( $extra );

		// GET THE COMMENTS

		if ( $_REQUEST['elements']['comments'] == 'Y' )
		{
			//$comments_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'",array(),array('ID'));
			//FJ get color for Course specific categories & get comment scale
			$comments_RET = DBGet( "SELECT c.ID,c.TITLE,c.SORT_ORDER,cc.COLOR,cs.TITLE AS SCALE_TITLE
			FROM REPORT_CARD_COMMENTS c
			LEFT OUTER JOIN REPORT_CARD_COMMENT_CATEGORIES cc ON (cc.SYEAR=c.SYEAR AND cc.SCHOOL_ID=c.SCHOOL_ID AND cc.ID=c.CATEGORY_ID)
			LEFT OUTER JOIN REPORT_CARD_COMMENT_CODE_SCALES cs ON (cs.SCHOOL_ID=c.SCHOOL_ID AND cs.ID=c.SCALE_ID)
			WHERE c.SCHOOL_ID='" . UserSchool() . "'
			AND c.SYEAR='" . UserSyear() . "'", array(), array( 'ID' ) );

			//FJ add columns for All Courses comments
			$all_commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' AND COURSE_ID IS NOT NULL AND COURSE_ID='0' ORDER BY SORT_ORDER,ID", array(), array( 'ID' ) );
		}

		if ( ! empty( $RET ) )
		{
			$columns = array( 'FULL_NAME' => _( 'Student' ), 'COURSE_TITLE' => _( 'Course' ) );

			if ( $_REQUEST['elements']['teacher'] === 'Y' )
			{
				$columns += array( 'TEACHER_ID' => _( 'Teacher' ) );
			}

			if ( $_REQUEST['elements']['period_absences'] == 'Y' )
			{
				$columns['ABSENCES'] = _( 'Abs<br />YTD / MP' );
			}

			foreach ( (array) $_REQUEST['mp_arr'] as $mp )
			{
				if ( $_REQUEST['elements']['percents'] == 'Y' )
				{
					$columns[$mp . '%'] = '%';
				}

				$columns[$mp] = GetMP( $mp );
			}

			if ( $_REQUEST['elements']['comments'] == 'Y' )
			{
				//FJ add columns for All Courses comments

				foreach ( (array) $all_commentsA_RET as $comment )
				{
					$columns['C' . $comment[1]['ID']] = $comment[1]['TITLE'];
				}

				$columns['COMMENT'] = _( 'Comments' );
			}

			$i = 0;

			foreach ( (array) $RET as $student_id => $course_periods )
			{
				$name_tipmessage = '';

				// Marking Period-by-period absences.

				if ( $_REQUEST['elements']['mp_absences'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetMPAbsences( $st_list, $last_mp, $student_id ) . '</div>';
				}

				// Year-to-date Daily Absences.

				if ( $_REQUEST['elements']['ytd_absences'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetYTDAbsences( $st_list, $last_mp, $student_id ) . '</div>';
				}

				// Marking Period Tardies.

				if ( $_REQUEST['elements']['mp_tardies'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetMPTardies( $st_list, $last_mp, $student_id ) . '</div>';
				}

				// Year to Date Tardies.

				if ( $_REQUEST['elements']['ytd_tardies'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetYTDTardies( $st_list, $student_id ) . '</div>';
				}

				// Optimization: Student Full Name.
				$student_full_name = DBGetOne( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME
					FROM STUDENTS s
					WHERE s.STUDENT_ID='" . $student_id . "'
					LIMIT 1" );

				$grades_RET[$i + 1]['FULL_NAME'] = $student_full_name;

				if ( $name_tipmessage )
				{
					$grades_RET[$i + 1]['FULL_NAME'] = MakeTipMessage(
						$name_tipmessage,
						$grades_RET[$i + 1]['FULL_NAME'],
						$grades_RET[$i + 1]['FULL_NAME']
					);
				}

				$grades_RET[$i + 1]['bgcolor'] = 'FFFFFF';

				foreach ( (array) $course_periods as $course_period_id => $mps )
				{
					$i++;
					$grades_RET[$i]['STUDENT_ID'] = $student_id;
					$grades_RET[$i]['COURSE_PERIOD_ID'] = $course_period_id;
					$grades_RET[$i]['MARKING_PERIOD_ID'] = key( $mps );

					$grades_RET[$i]['COURSE_TITLE'] = $mps[key( $mps )][1]['COURSE_TITLE'];
					$grades_RET[$i]['TEACHER_ID'] = GetTeacher( $mps[$last_mp][1]['TEACHER_ID'] );

					foreach ( (array) $_REQUEST['mp_arr'] as $mp )
					{
						if ( $mps[$mp] )
						{
							$grades_RET[$i][$mp] = $mps[$mp][1]['GRADE_TITLE'];

							if ( $_REQUEST['elements']['percents'] == 'Y' && $mps[$mp][1]['GRADE_PERCENT'] > 0 )
							{
								$grades_RET[$i][$mp . '%'] = $mps[$mp][1]['GRADE_PERCENT'] . '%';
							}

							$last_mp = $mp;
						}
					}

					if ( $_REQUEST['elements']['period_absences'] == 'Y' )
					{
						if ( mb_strpos( $mps[$last_mp][1]['DOES_ATTENDANCE'], ',0,' ) !== false )
						{
							$grades_RET[$i]['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'] . ' / ' . $mps[$last_mp][1]['MP_ABSENCES'];
						}
						else
						{
							$grades_RET[$i]['ABSENCES'] = _( 'N/A' );
						}
					}

					if ( $_REQUEST['elements']['comments'] == 'Y' )
					{
						//FJ add comments for each MP
						$sep = '; ';
						$sep_mp = ' | ';

						foreach ( (array) $mps as $mp )
						{
							if ( ! empty( $grades_RET[$i]['COMMENT'] ) )
							{
								$grades_RET[$i]['COMMENT'] = $grades_RET[$i]['COMMENT'] . $sep_mp;
							}

							foreach ( (array) $mp[1]['COMMENTS_RET'] as $comment )
							{
								if ( $all_commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']] )
								{
									$grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] .= $comment['COMMENT'] != ' ' ? ( empty( $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] ) ? '' : $sep_mp ) . $comment['COMMENT'] : ( empty( $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] ) ? '' : $sep_mp ) . '&middot;';
								}
								else
								{
									$sep_tmp = empty( $grades_RET[$i]['COMMENT'] ) || mb_substr( $grades_RET[$i]['COMMENT'], -3 ) == $sep_mp ? '' : $sep;

									$color = $comments_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['COLOR'];

									if ( $color )
									{
										$color_html = '<span style="color:' . $color . '">';
									}
									else
									{
										$color_html = '';
									}

									$grades_RET[$i]['COMMENT'] .= $sep_tmp . $color_html . $comments_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];

									if ( $comment['COMMENT'] )
									{
										$grades_RET[$i]['COMMENT'] .= '(' . ( $comment['COMMENT'] != ' ' ? $comment['COMMENT'] : '&middot;' ) . ')' . ( $color_html ? '</span>' : '' );
									}
								}
							}

							if ( $mp[1]['COMMENT_TITLE'] )
							{
								$grades_RET[$i]['COMMENT'] .= ( empty( $grades_RET[$i]['COMMENT'] ) || mb_substr( $grades_RET[$i]['COMMENT'], -3 ) == $sep_mp ? '' : $sep ) . $mp[1]['COMMENT_TITLE'];
							}
						}
					}
				}
			}

			if ( count( (array) $_REQUEST['mp_arr'] ) == 1 && AllowEdit() )
			{
				$link['remove']['link'] = PreparePHP_SELF( $_REQUEST, array( 'delete_cancel' ), array( 'modfunc' => 'delete' ) );
				$link['remove']['variables'] = array( 'student_id' => 'STUDENT_ID',
					'course_period_id' => 'COURSE_PERIOD_ID',
					'marking_period_id' => 'MARKING_PERIOD_ID' );
			}

			//Display comment codes tooltips

			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) && $_REQUEST['elements']['comments'] == 'Y' )
			{
				$commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' AND COURSE_ID IS NULL ORDER BY SORT_ORDER", array(), array( 'ID' ) );

				if ( $commentsB_RET )
				{
					$tipmessage = '';

					foreach ( (array) $commentsB_RET as $comment )
					{
						$tipmessage .= $comment[1]['SORT_ORDER'] . ' - ' .
							$comment[1]['TITLE'] . '<br />';
					}

					DrawHeader(
						_( 'General Comments' ),
						makeTipMessage(
							$tipmessage,
							_( 'Comment Codes' ),
							button( 'comment', _( 'Comment Codes' ) )
						)
					);
				}

				$cp_list = array();

				foreach ( (array) $grades_RET as $grade )
				{
					$cp_list[] = $grade['COURSE_PERIOD_ID'];
				}

				$cp_list = "'" . implode( "','", $cp_list ) . "'";

				//FJ limit comment scales to the ones used in students' courses
				$students_comment_scales_RET = DBGet( "SELECT cs.ID
				FROM REPORT_CARD_COMMENT_CODE_SCALES cs
				WHERE cs.ID IN
					(SELECT c.SCALE_ID
					FROM REPORT_CARD_COMMENTS c
					WHERE (c.COURSE_ID IN(SELECT COURSE_ID FROM SCHEDULE WHERE STUDENT_ID IN (" . $st_list . ") AND COURSE_PERIOD_ID IN(" . $cp_list . ")) OR c.COURSE_ID=0)
					AND c.SCHOOL_ID=cs.SCHOOL_ID
					AND c.SYEAR='" . UserSyear() . "')
				AND cs.SCHOOL_ID='" . UserSchool() . "'", array(), array( 'ID' ) );
				$students_comment_scales = array_keys( $students_comment_scales_RET );

				//FJ add Comment Scales tipmessage
				$comment_codes_RET = null;

				if ( ! empty( $students_comment_scales ) )
				{
					$comment_codes_RET = DBGet( "SELECT cc.SCALE_ID,cc.TITLE,cc.SHORT_NAME,cc.COMMENT,cs.TITLE AS SCALE_TITLE
					FROM REPORT_CARD_COMMENT_CODES cc, REPORT_CARD_COMMENT_CODE_SCALES cs
					WHERE cs.ID IN (" . implode( $students_comment_scales, ',' ) . ")
					AND cs.ID=cc.SCALE_ID
					ORDER BY cs.SORT_ORDER", array(), array( 'SCALE_ID' ) );
				}

				if ( $comment_codes_RET )
				{
					$tipmessage = '';

					foreach ( (array) $comment_codes_RET as $scale_id => $codes )
					{
						$tipmsg = '';

						foreach ( (array) $codes as $code )
						{
							$tipmsg .= $code['TITLE'] . ': ' . $code['COMMENT'] . '<br />';
						}

						$tipmessage .= makeTipMessage(
							$tipmsg,
							_( 'Comment Codes' ),
							button( 'comment', $codes[1]['SCALE_TITLE'] )
						);
					}

					DrawHeader( _( 'Comment Scales' ), $tipmessage );
				}

				//FJ add Course-specific comments tipmessage
				$commentsA_RET = DBGet( "SELECT cs.TITLE AS SCALE_TITLE,c.TITLE,c.SORT_ORDER,COLOR,co.COURSE_ID,co.TITLE AS COURSE_TITLE
				FROM REPORT_CARD_COMMENTS c, REPORT_CARD_COMMENT_CATEGORIES cc, COURSES co, REPORT_CARD_COMMENT_CODE_SCALES cs
				WHERE (c.COURSE_ID IN(SELECT COURSE_ID FROM SCHEDULE WHERE STUDENT_ID IN (" . $st_list . ") AND COURSE_PERIOD_ID IN(" . $cp_list . ")))
				AND c.SYEAR='" . UserSyear() . "'
				AND c.SCHOOL_ID='" . UserSchool() . "'
				AND c.CATEGORY_ID=cc.ID
				AND co.COURSE_ID=c.COURSE_ID
				AND c.SCALE_ID=cs.ID
				ORDER BY c.SORT_ORDER", array(), array( 'COURSE_ID' ) );

				if ( $commentsA_RET )
				{
					$tipmessage = '';

					foreach ( (array) $commentsA_RET as $course_id => $commentsA )
					{
						$tipmsg = '';

						foreach ( (array) $commentsA as $commentA )
						{
							$color = $commentA['COLOR'];

							if ( $color )
							{
								$color_html = '<span style="color:' . $color . '">';
							}
							else
							{
								$color_html = '';
							}

							$comment_scale_txt = '&nbsp;&nbsp;&nbsp;&nbsp;(' . _( 'Comment Scale' ) . ': ' .
								$commentA['SCALE_TITLE'] . ')';

							$tipmsg .= $color_html . $commentA['SORT_ORDER'] . ': ' .
								$commentA['TITLE'] . ( $color_html ? '</span>' : '' ) . '<br />' .
								$comment_scale_txt . '<br />';
						}

						$tipmessage .= ' ' . makeTipMessage(
							$tipmsg,
							_( 'Comments' ),
							button( 'comment', $commentsA[1]['COURSE_TITLE'] )
						);
					}

					DrawHeader( _( 'Course-specific Comments' ), $tipmessage );
				}
			}

			ListOutput( $grades_RET, $columns, '.', '.', $link );
		}
		else
		{
			$error[] = _( 'No Students were found.' );

			// Unset modfunc & redirect URL.
			RedirectURL( 'modfunc' );
		}
	}
	else
	{
		$error[] = _( 'You must choose at least one student and one marking period.' );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		$_ROSARIO['allow_edit'] = true;

		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] . '" method="GET">';

		$extra['header_right'] = SubmitButton( _( 'Create Grade Lists for Selected Students' ) );

		$extra['extra_header_left'] = ReportCardsIncludeForm( _( 'Include on Grade List' ), false );

		/*$extra['extra_header_left'] = '<table>';
	$extra['extra_header_left'] .= '<tr><td colspan="2"><b>'._('Include on Grade List').':</b></td></tr>';

	$extra['extra_header_left'] .= '<tr class="st"><td></td><td><table>';

	$extra['extra_header_left'] .= '<tr><td><label><input type="checkbox" name="elements[teacher]" value="Y" checked /> '._('Teacher').'</label></td>';
	$extra['extra_header_left'] .= '<td></td></tr>';

	$extra['extra_header_left'] .= '<tr><td><label><input type="checkbox" name="elements[comments]" value="Y" checked /> '._('Comments').'</label></td>';
	$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="elements[percents]" value="Y"> '._('Percents').'</label></td></tr>';

	$extra['extra_header_left'] .= '<tr><td><label><input type="checkbox" name="elements[ytd_absences]" value="Y" checked /> '._('Year-to-date Daily Absences').'</label></td>';
	$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="elements[mp_absences]" value="Y"'.(GetMP(UserMP(),'SORT_ORDER')!=1?' checked':'').'> '._('Daily Absences this quarter').'</label></td></tr>';

	$extra['extra_header_left'] .= '<tr><td><label><input type="checkbox" name="elements[ytd_tardies]" value="Y"> '._('Other Attendance Year-to-date').':</label> <select name="ytd_tardies_code">';

	foreach ( (array) $attendance_codes as $code)
	$extra['extra_header_left'] .= '<option value="'.$code['ID'].'">'.$code['TITLE'].'</option>';

	$extra['extra_header_left'] .= '</select></td>';
	$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="elements[mp_tardies]" value="Y"> '._('Other Attendance Year-to-date').':</label> <select name="mp_tardies_code">';

	foreach ( (array) $attendance_codes as $code)
	$extra['extra_header_left'] .= '<option value="'.$code['ID'].'">'.$code['TITLE'].'</option>';

	$extra['extra_header_left'] .= '</select></td></tr>';

	$extra['extra_header_left'] .= '<tr><td><label><input type="checkbox" name="elements[period_absences]" value="Y"> '._('Period-by-period absences').'</label></td>';
	$extra['extra_header_left'] .= '<td></td></tr>';

	$extra['extra_header_left'] .= '</table></td></tr>';

	//FJ get the title instead of the short marking period name
	$mps_RET = DBGet( "SELECT PARENT_ID,MARKING_PERIOD_ID,SHORT_NAME,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER",array(),array('PARENT_ID'));

	$extra['extra_header_left'] .= '<tr class="st"><td>'._('Marking Periods').':</td><td><table><tr><td><table>';

	foreach ( (array) $mps_RET as $sem => $quarters)
	{
	$extra['extra_header_left'] .= '<tr class="st">';
	foreach ( (array) $quarters as $qtr)
	{
	$pro = GetChildrenMP('PRO',$qtr['MARKING_PERIOD_ID']);
	if ( $pro)
	{
	$pros = explode(',',str_replace("'",'',$pro));
	foreach ( (array) $pros as $pro)
	if (GetMP($pro,'DOES_GRADES')=='Y')
	$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="mp_arr[]" value="'.$pro.'"> '.GetMP($pro,'TITLE').'</label></td>';
	}
	$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="mp_arr[]" value="'.$qtr['MARKING_PERIOD_ID'].'"> '.$qtr['TITLE'].'</label></td>';
	}

	if (GetMP($sem,'DOES_GRADES')=='Y')
	$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="mp_arr[]" value="'.$sem.'"> '.GetMP($sem,'TITLE').'</label></td>';

	$extra['extra_header_left'] .= '</tr>';
	}

	$extra['extra_header_left'] .= '</table></td>';

	if ( $sem)
	{
	$fy = GetParentMP('FY',$sem);
	$extra['extra_header_left'] .= '<td><table><tr>';

	if (GetMP($fy,'DOES_GRADES')=='Y')
	$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="mp_arr[]" value="'.$fy.'"> '.GetMP($fy,'TITLE').'</label></td>';

	$extra['extra_header_left'] .= '</tr></table></td>';
	}

	$extra['extra_header_left'] .= '</td></tr></table></tr></table>';*/
	}

	$extra['new'] = true;

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st' ) );
	$extra['options']['search'] = false;

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	//Widgets('course');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Create Grade Lists for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}

/**
 * @param $value
 * @param $column
 */
function _makeComments( $value, $column )
{
	global $THIS_RET;

	return DBGet( "SELECT COURSE_PERIOD_ID,REPORT_CARD_COMMENT_ID,COMMENT,
	(SELECT SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE REPORT_CARD_COMMENT_ID=ID) AS SORT_ORDER
	FROM STUDENT_REPORT_CARD_COMMENTS
	WHERE STUDENT_ID='" . $THIS_RET['STUDENT_ID'] . "'
	AND COURSE_PERIOD_ID='" . $THIS_RET['COURSE_PERIOD_ID'] . "'
	AND MARKING_PERIOD_ID='" . $value . "' ORDER BY SORT_ORDER" );
}
