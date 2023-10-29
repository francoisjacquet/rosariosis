<?php

require_once 'modules/Grades/includes/ReportCards.fnc.php';

require_once 'ProgramFunctions/TipMessage.fnc.php';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Final Grade' ) ) )
	{
		$delete_sql = "DELETE FROM student_report_card_grades
			WHERE SYEAR='" . UserSyear() . "'
			AND STUDENT_ID='" . (int) $_REQUEST['student_id'] . "'
			AND COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'
			AND MARKING_PERIOD_ID='" . (int) $_REQUEST['marking_period_id'] . "';";

		$delete_sql .= "DELETE FROM student_report_card_comments
			WHERE SYEAR='" . UserSyear() . "'
			AND STUDENT_ID='" . (int) $_REQUEST['student_id'] . "'
			AND COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'
			AND MARKING_PERIOD_ID='" . (int) $_REQUEST['marking_period_id'] . "';";

		DBQuery( $delete_sql );

		$_REQUEST['modfunc'] = 'save';
	}
}
elseif ( ! empty( $_REQUEST['delete_cancel'] )
	&& AllowEdit() )
{
	$_REQUEST['modfunc'] = 'save';
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['st'] ) )
	{
		// Fix Apache 414 Request-URI Too Long, move st from $_REQUEST to $_SESSION.
		$_SESSION['FinalGrades.php']['st'] = $_REQUEST['st'];

		// Unset st & redirect URL.
		// @since 11.2 Add $_POST elements, ytd_tardies_code, mp_tardies_code & mp_arr to URL.
		RedirectURL( 'st', [ 'elements', 'ytd_tardies_code', 'mp_tardies_code', 'mp_arr' ] );
	}

	if ( ! empty( $_REQUEST['mp_arr'] )
		&& ! empty( $_SESSION['FinalGrades.php']['st'] ) )
	{
		$mp_list = "'" . implode( "','", $_REQUEST['mp_arr'] ) . "'";

		$st_list = "'" . implode( "','", $_SESSION['FinalGrades.php']['st'] ) . "'";

		$last_mp = end( $_REQUEST['mp_arr'] );

		$extra = GetReportCardsExtra( $mp_list, $st_list );

		/*$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		$extra['SELECT'] .= ",sg1.GRADE_LETTER as GRADE_TITLE,sg1.GRADE_PERCENT,sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,c.TITLE as COURSE_TITLE,rc_cp.TITLE AS TEACHER,sp.SORT_ORDER";

		if ( $_REQUEST['elements']['period_absences']=='Y')
		//modif: SQL error fix: operator does not exist: character varying = integer, add explicit type casts
		$extra['SELECT'] .= ",rc_cp.DOES_ATTENDANCE,
		(SELECT count(*) FROM attendance_period ap,attendance_codes ac
		WHERE ac.ID=ap.ATTENDANCE_CODE
		AND ac.STATE_CODE='A'
		AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
		AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
		(SELECT count(*) FROM attendance_period ap,attendance_codes ac
		WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE='A'
		AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
		AND sg1.MARKING_PERIOD_ID=ap.MARKING_PERIOD_ID
		AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES";*/

		//FJ multiple school periods for a course period
		/*$extra['FROM'] .= ",student_report_card_grades sg1 LEFT OUTER JOIN report_card_grades rpg ON (rpg.ID=sg1.REPORT_CARD_GRADE_ID),
		course_periods rc_cp,courses c,school_periods sp";*/
		/*$extra['FROM'] .= ",student_report_card_grades sg1,
		course_periods rc_cp,courses c,school_periods sp,course_period_school_periods cpsp";

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

		if ( isset( $_REQUEST['elements']['comments'] )
			&& $_REQUEST['elements']['comments'] == 'Y' )
		{
			$rc_comments_RET = GetReportCardsComments( $st_list, $mp_list );
		}

		if ( ! GetMP( $last_mp ) )
		{
			/**
			 * Fail if Marking Periods are not in current School Year
			 * Happens when user switched School Year in left menu
			 * & then requests Report Cards from a previous tab.
			 *
			 * @since 11.3
			 */
			$RET = [];
		}
		else
		{
			$RET = GetStuList( $extra );
		}

		// GET THE COMMENTS

		if ( isset( $_REQUEST['elements']['comments'] )
			&& $_REQUEST['elements']['comments'] == 'Y' )
		{
			//$comments_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'",array(),array('ID'));
			//FJ get color for Course specific categories & get comment scale
			$comments_RET = DBGet( "SELECT c.ID,c.TITLE,c.SORT_ORDER,cc.COLOR,cs.TITLE AS SCALE_TITLE
			FROM report_card_comments c
			LEFT OUTER JOIN report_card_comment_categories cc ON (cc.SYEAR=c.SYEAR AND cc.SCHOOL_ID=c.SCHOOL_ID AND cc.ID=c.CATEGORY_ID)
			LEFT OUTER JOIN report_card_comment_code_scales cs ON (cs.SCHOOL_ID=c.SCHOOL_ID AND cs.ID=c.SCALE_ID)
			WHERE c.SCHOOL_ID='" . UserSchool() . "'
			AND c.SYEAR='" . UserSyear() . "'", [], [ 'ID' ] );

			//FJ add columns for All Courses comments
			$all_commentsA_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' AND COURSE_ID IS NOT NULL AND COURSE_ID='0' ORDER BY SORT_ORDER IS NULL,SORT_ORDER,ID", [], [ 'ID' ] );
		}

		if ( ! empty( $RET ) )
		{
			$columns = [ 'FULL_NAME' => _( 'Student' ), 'COURSE_TITLE' => _( 'Course' ) ];

			if ( isset( $_REQUEST['elements']['teacher'] )
				&& $_REQUEST['elements']['teacher'] === 'Y' )
			{
				$columns += [ 'TEACHER_ID' => _( 'Teacher' ) ];
			}

			if ( isset( $_REQUEST['elements']['period_absences'] )
				&& $_REQUEST['elements']['period_absences'] == 'Y' )
			{
				$columns['ABSENCES'] = _( 'Abs<br />YTD / MP' );
			}

			foreach ( (array) $_REQUEST['mp_arr'] as $mp )
			{
				if ( isset( $_REQUEST['elements']['percents'] )
					&& $_REQUEST['elements']['percents'] == 'Y' )
				{
					$columns[$mp . '%'] = '%';
				}

				$columns[$mp] = GetMP( $mp );
			}

			if ( isset( $_REQUEST['elements']['comments'] )
				&& $_REQUEST['elements']['comments'] == 'Y' )
			{
				//FJ add columns for All Courses comments

				foreach ( (array) $all_commentsA_RET as $comment )
				{
					$columns['C' . $comment[1]['ID']] = $comment[1]['TITLE'];
				}

				$columns['COMMENT'] = _( 'Comments' );
			}

			$i = 0;

			$course_periods_all = [];

			foreach ( (array) $RET as $student_id => $course_periods )
			{
				$name_tipmessage = '';

				// Marking Period-by-period absences.

				if ( isset( $_REQUEST['elements']['mp_absences'] )
					&& $_REQUEST['elements']['mp_absences'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetMPAbsences( $st_list, $last_mp, $student_id ) . '</div>';
				}

				// Year-to-date Daily Absences.

				if ( isset( $_REQUEST['elements']['ytd_absences'] )
					&& $_REQUEST['elements']['ytd_absences'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetYTDAbsences( $st_list, $last_mp, $student_id ) . '</div>';
				}

				// Marking Period Tardies.

				if ( isset( $_REQUEST['elements']['mp_tardies'] )
					&& $_REQUEST['elements']['mp_tardies'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetMPTardies( $st_list, $last_mp, $student_id ) . '</div>';
				}

				// Year to Date Tardies.

				if ( isset( $_REQUEST['elements']['ytd_tardies'] )
					&& $_REQUEST['elements']['ytd_tardies'] === 'Y' )
				{
					$name_tipmessage .= '<div>' .
					GetYTDTardies( $st_list, $student_id ) . '</div>';
				}

				// Optimization: Student Full Name.
				$student_full_name = DBGetOne( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME
					FROM students s
					WHERE s.STUDENT_ID='" . (int) $student_id . "'
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

				foreach ( (array) $course_periods as $course_period_id => $mps )
				{
					$i++;
					$grades_RET[$i]['STUDENT_ID'] = $student_id;
					$grades_RET[$i]['COURSE_PERIOD_ID'] = $course_period_id;
					$grades_RET[$i]['MARKING_PERIOD_ID'] = key( $mps );

					$grades_RET[$i]['COURSE_TITLE'] = $mps[key( $mps )][1]['COURSE_TITLE'];
					$grades_RET[$i]['TEACHER_ID'] = GetTeacher( $mps[key( $mps )][1]['TEACHER_ID'] );

					foreach ( (array) $_REQUEST['mp_arr'] as $mp )
					{
						if ( ! empty( $mps[$mp] ) )
						{
							$grades_RET[$i][$mp] = $mps[$mp][1]['GRADE_TITLE'];

							if ( isset( $_REQUEST['elements']['percents'] )
								&& $_REQUEST['elements']['percents'] == 'Y'
								&& $mps[$mp][1]['GRADE_PERCENT'] > 0 )
							{
								$grades_RET[$i][$mp . '%'] = $mps[$mp][1]['GRADE_PERCENT'] . '%';
							}

							$last_mp = $mp;
						}
					}

					if ( isset( $_REQUEST['elements']['period_absences'] )
						&& $_REQUEST['elements']['period_absences'] == 'Y' )
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

					if ( isset( $_REQUEST['elements']['comments'] )
						&& $_REQUEST['elements']['comments'] == 'Y' )
					{
						//FJ add comments for each MP
						$sep = '; ';
						$sep_mp = ' | ';

						if ( ! isset( $grades_RET[$i]['COMMENT'] ) )
						{
							$grades_RET[$i]['COMMENT'] = '';
						}

						foreach ( (array) $mps as $mp_id => $mp )
						{
							if ( ! empty( $grades_RET[$i]['COMMENT'] ) )
							{
								$grades_RET[$i]['COMMENT'] = $grades_RET[$i]['COMMENT'] . $sep_mp;
							}

							$mp_comments = isset( $rc_comments_RET[ $student_id ][ $course_period_id ][ $mp_id ] ) ?
								$rc_comments_RET[ $student_id ][ $course_period_id ][ $mp_id ] :
								[];

							foreach ( (array) $mp_comments as $comment )
							{
								if ( !empty( $all_commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']] ) )
								{
									if ( ! isset( $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] ) )
									{
										$grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] = '';
									}

									$grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] .= $comment['COMMENT'] != ' ' ? ( empty( $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] ) ? '' : $sep_mp ) . $comment['COMMENT'] : ( empty( $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] ) ? '' : $sep_mp ) . '&middot;';
								}
								else
								{
									$sep_tmp = empty( $grades_RET[$i]['COMMENT'] ) || mb_substr( $grades_RET[$i]['COMMENT'], -3 ) == $sep_mp ? '' : $sep;

									$color = $comments_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['COLOR'];

									$color_html = '';

									if ( $color )
									{
										$color_html = '<span style="color:' . $color . '">';
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

						if ( mb_strlen( $grades_RET[$i]['COMMENT'] ) > 60
							&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
						{
							// Comments length > 60 chars, responsive table ColorBox.
							$grades_RET[$i]['COMMENT'] = '<div id="divFinalGradesComments' . $i . '" class="rt2colorBox">' .
								$grades_RET[$i]['COMMENT'] . '</div>';
						}
					}
				}

				$course_periods_all = array_replace( $course_periods_all, $course_periods );
			}

			if ( ! empty( $_REQUEST['elements']['minmax_grades'] ) )
			{
				// @since 5.0 Add Min. and Max. Grades.
				$min_max_grades = GetReportCardMinMaxGrades( $course_periods_all );

				$grades_RET = AddReportCardMinMaxGrades(
					$min_max_grades,
					$grades_RET,
					$columns
				);
			}

			$link = [];

			if ( count( (array) $_REQUEST['mp_arr'] ) == 1 && AllowEdit() )
			{
				$link['remove']['link'] = PreparePHP_SELF(
					$_REQUEST, [ 'delete_cancel' ],
					[ 'modfunc' => 'delete' ]
				);

				$link['remove']['variables'] = [ 'student_id' => 'STUDENT_ID',
					'course_period_id' => 'COURSE_PERIOD_ID',
					'marking_period_id' => 'MARKING_PERIOD_ID' ];
			}

			//Display comment codes tooltips

			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
				&& isset( $_REQUEST['elements']['comments'] )
				&& $_REQUEST['elements']['comments'] == 'Y' )
			{
				$commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
					FROM report_card_comments
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . UserSyear() . "'
					AND COURSE_ID IS NULL
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

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

				$cp_list = [];

				foreach ( (array) $grades_RET as $grade )
				{
					$cp_list[] = $grade['COURSE_PERIOD_ID'];
				}

				$cp_list = "'" . implode( "','", $cp_list ) . "'";

				//FJ limit comment scales to the ones used in students' courses
				$students_comment_scales_RET = DBGet( "SELECT cs.ID
				FROM report_card_comment_code_scales cs
				WHERE cs.ID IN
					(SELECT c.SCALE_ID
					FROM report_card_comments c
					WHERE (c.COURSE_ID IN(SELECT COURSE_ID FROM schedule WHERE STUDENT_ID IN (" . $st_list . ") AND COURSE_PERIOD_ID IN(" . $cp_list . ")) OR c.COURSE_ID=0)
					AND c.SCHOOL_ID=cs.SCHOOL_ID
					AND c.SYEAR='" . UserSyear() . "')
				AND cs.SCHOOL_ID='" . UserSchool() . "'", [], [ 'ID' ] );
				$students_comment_scales = array_keys( $students_comment_scales_RET );

				//FJ add Comment Scales tipmessage
				$comment_codes_RET = null;

				if ( ! empty( $students_comment_scales ) )
				{
					$comment_codes_RET = DBGet( "SELECT cc.SCALE_ID,cc.TITLE,cc.SHORT_NAME,cc.COMMENT,cs.TITLE AS SCALE_TITLE
					FROM report_card_comment_codes cc, report_card_comment_code_scales cs
					WHERE cs.ID IN (" . implode( ',', $students_comment_scales ) . ")
					AND cs.ID=cc.SCALE_ID
					ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER", [], [ 'SCALE_ID' ] );
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

						$tipmessage .= ' &nbsp; ' . makeTipMessage(
							$tipmsg,
							_( 'Comment Codes' ),
							button( 'comment', $codes[1]['SCALE_TITLE'] )
						);
					}

					DrawHeader( _( 'Comment Scales' ), $tipmessage );
				}

				//FJ add Course-specific comments tipmessage
				$commentsA_RET = DBGet( "SELECT cs.TITLE AS SCALE_TITLE,c.TITLE,c.SORT_ORDER,COLOR,co.COURSE_ID,co.TITLE AS COURSE_TITLE
				FROM report_card_comments c, report_card_comment_categories cc, courses co, report_card_comment_code_scales cs
				WHERE (c.COURSE_ID IN(SELECT COURSE_ID FROM schedule WHERE STUDENT_ID IN (" . $st_list . ") AND COURSE_PERIOD_ID IN(" . $cp_list . ")))
				AND c.SYEAR='" . UserSyear() . "'
				AND c.SCHOOL_ID='" . UserSchool() . "'
				AND c.CATEGORY_ID=cc.ID
				AND co.COURSE_ID=c.COURSE_ID
				AND c.SCALE_ID=cs.ID
				ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER", [], [ 'COURSE_ID' ] );

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

							$comment_scale_txt = ' <span class="size-1">(' . $commentA['SCALE_TITLE'] . ')</span>';

							$tipmsg .= $color_html . $commentA['SORT_ORDER'] . ': ' .
								$commentA['TITLE'] . ( $color_html ? '</span>' : '' ) . '<br />' .
								$comment_scale_txt . '<br />';
						}

						$tipmessage .= ' &nbsp; ' . makeTipMessage(
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
			$error[] = sprintf(
				_( 'No %s were found.' ),
				mb_strtolower( ngettext( 'Grade', 'Grades', 0 ) )
			);

			unset( $extra );

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

		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=save&include_inactive=' .
			issetVal( $_REQUEST['include_inactive'], '' ) .
			'' ) . '" method="POST">';

		$extra['header_right'] = SubmitButton( _( 'Create Grade Lists for Selected Students' ) );

		$extra['extra_header_left'] = ReportCardsIncludeForm( _( 'Include on Grade List' ), false );
	}

	$extra['new'] = true;

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st' ) ];
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
