<?php

require_once 'modules/Grades/includes/ClassRank.inc.php';
require_once 'modules/Grades/includes/Grades.fnc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';

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

DrawHeader( ProgramTitle() );

// Get all the MP's associated with the current MP
$all_mp_ids = explode( "','", trim( GetAllMP( 'PRO', UserMP() ), "'" ) );

if ( ! empty( $_REQUEST['mp'] )
	&& ! in_array( $_REQUEST['mp'], $all_mp_ids ) )
{
	// Requested MP not found, reset.
	RedirectURL( 'mp' );
}

if ( empty( $_REQUEST['mp'] ) )
{
	$_REQUEST['mp'] = UserMP();
}

$course_period_id = UserCoursePeriod();

if ( empty( $course_period_id ) )
{
	ErrorMessage( [ _( 'You cannot enter grades for this course period.' ) ], 'fatal' );
}

//FJ add CLASS_RANK
//FJ add Credit Hours
//FJ add explicit type cast
//$course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE as COURSE_NAME, cp.TITLE, cp.GRADE_SCALE_ID, credit($course_period_id, '".$_REQUEST['mp']."') AS CREDITS, (SELECT ATTENDANCE FROM school_periods WHERE PERIOD_ID=cp.PERIOD_ID) AS ATTENDANCE FROM course_periods cp, courses c WHERE cp.COURSE_ID = c.COURSE_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."'" );
$course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE AS COURSE_NAME,cp.TITLE,
	cp.GRADE_SCALE_ID,credit('" . (int) $course_period_id . "','" . (int) $_REQUEST['mp'] . "') AS CREDITS,
	DOES_CLASS_RANK AS CLASS_RANK,c.CREDIT_HOURS
	FROM course_periods cp,courses c
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

if ( ! $course_RET[1]['GRADE_SCALE_ID'] )
{
	ErrorMessage( [ _( 'You cannot enter grades for this course period.' ) ], 'fatal' );
}

$course_title = $course_RET[1]['TITLE'];
$grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];
$course_id = $course_RET[1]['COURSE_ID'];

$current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
	g.REPORT_CARD_COMMENT_ID,g.COMMENT
	FROM student_report_card_grades g,course_periods cp
	WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
	AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
	AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'", [], [ 'STUDENT_ID' ] );

$current_completed = count( (array) DBGet( "SELECT 1
	FROM grades_completed
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'" ) );

$grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM report_card_grades rcg, report_card_grade_scales gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . (int) $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER IS NULL,rcg.SORT_ORDER", [], [ 'ID' ] );

// Fix PostgreSQL error invalid ORDER BY, only result column names can be used
// Do not use ORDER BY SORT_ORDER IS NULL,SORT_ORDER (nulls last) in UNION.
// Fix MySQL 5.6 syntax error when WHERE without FROM clause, use dual table
// Fix MySQL 5.6 syntax error in ORDER BY use report_card_comments table instead of dual
$categories_RET = DBGet( "SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER
	FROM report_card_comment_categories rc
	WHERE rc.COURSE_ID='" . (int) $course_id . "'
	AND (SELECT count(1)
		FROM report_card_comments
		WHERE COURSE_ID=rc.COURSE_ID
		AND CATEGORY_ID=rc.ID)>0
	UNION
	SELECT 0,'" . DBEscapeString( _( 'All Courses' ) ) . "',NULL,2,NULL
	FROM report_card_comments
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND COURSE_ID='0'
	AND SYEAR='" . UserSyear() . "'
	UNION
	SELECT -1,'" . DBEscapeString( _( 'General' ) ) . "',NULL,3,NULL
	FROM report_card_comments
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND COURSE_ID IS NULL
	AND SYEAR='" . UserSyear() . "'
	ORDER BY 4,SORT_ORDER", [], [ 'ID' ] );

if ( ! isset( $_REQUEST['tab_id'] )
	|| $_REQUEST['tab_id'] == ''
	|| ! $categories_RET[$_REQUEST['tab_id']] )
{
	$_REQUEST['tab_id'] = key( $categories_RET ) . '';
}

$comment_codes_RET = DBGet( "SELECT SCALE_ID,TITLE,SHORT_NAME,COMMENT
	FROM report_card_comment_codes
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER,ID", [], [ 'SCALE_ID' ] );

$commentsA_select = [];

foreach ( (array) $comment_codes_RET as $scale_id => $codes )
{
	foreach ( (array) $codes as $code )
	{
		$commentsA_select[$scale_id][$code['TITLE']] = $code['SHORT_NAME'] ?
		[ $code['TITLE'], $code['SHORT_NAME'] ] :
		$code['TITLE'];
	}
}

$max_current_commentsB = 0;

if ( $_REQUEST['tab_id'] == '-1' )
{
	$commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM report_card_comments
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID IS NULL
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

	$current_commentsB_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID
		FROM student_report_card_comments g,course_periods cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID
			FROM report_card_comments
			WHERE COURSE_ID IS NULL)", [], [ 'STUDENT_ID' ] );

	foreach ( (array) $current_commentsB_RET as $comments )
	{
		if ( count( $comments ) > $max_current_commentsB )
		{
			$max_current_commentsB = count( $comments );
		}
	}
}
elseif ( $_REQUEST['tab_id'] == '0' )
{
	$commentsA_RET = DBGet( "SELECT ID,TITLE,SCALE_ID
		FROM report_card_comments
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID='0'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$current_commentsA_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM student_report_card_comments g,course_periods cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID
			FROM report_card_comments WHERE COURSE_ID='0')", [], [ 'STUDENT_ID', 'REPORT_CARD_COMMENT_ID' ] );
}
elseif ( ! empty( $_REQUEST['tab_id'] ) )
{
	$commentsA_RET = DBGet( "SELECT ID,TITLE,SCALE_ID
		FROM report_card_comments
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID='" . (int) $course_id . "'
		AND CATEGORY_ID='" . (int) $_REQUEST['tab_id'] . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$current_commentsA_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM student_report_card_comments g,course_periods cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID
			FROM report_card_comments
			WHERE CATEGORY_ID='" . (int) $_REQUEST['tab_id'] . "')", [], [ 'STUDENT_ID', 'REPORT_CARD_COMMENT_ID' ] );
}

$grades_select = [ '' => '' ];

foreach ( (array) $grades_RET as $key => $grade )
{
	$grade = $grade[1];
	$grades_select += [ $grade['ID'] => [ $grade['TITLE'], '<b>' . $grade['TITLE'] . '</b>' ] ];
}

$commentsB_select = [];

if ( 0 )
{
	foreach ( (array) $commentsB_RET as $id => $comment )
	{
		$commentsB_select += [ $id => [ $comment[1]['SORT_ORDER'], $comment[1]['TITLE'] ] ];
	}
}
elseif ( ! empty( $commentsB_RET ) )
{
	foreach ( (array) $commentsB_RET as $id => $comment )
	{
		$commentsB_select += [ $id => [ $comment[1]['SORT_ORDER'] . ' - ' . ( mb_strlen( $comment[1]['TITLE'] ) > 99 + 3 ? mb_substr( $comment[1]['TITLE'], 0, 99 ) . '...' : $comment[1]['TITLE'] ), $comment[1]['TITLE'] ] ];
	}
}

if ( $_REQUEST['modfunc'] === 'gradebook' )
{
	if ( ! empty( $_REQUEST['mp'] ) )
	{
		$gradebook_config = ProgramUserConfig( 'Gradebook' );

		$_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet( "SELECT DOES_BREAKOFF,GRADE_SCALE_ID
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

		require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

		if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'QTR' || GetMP( $_REQUEST['mp'], 'MP' ) == 'PRO' )
		{
			// Note: The 'active assignment' determination is not fully correct.  It would be easy to be fully correct here but the same determination
			// as in Grades.php is used to avoid apparent inconsistencies in the grade calculations.  See also the note at top of Grades.php.
			$extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(" .
			db_case( [ 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ] ) . ") AS PARTIAL_POINTS,sum(" .
			db_case( [ 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ] ) . ") AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";

			if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
			{
				// @since 11.0 Add Weight Assignments option
				$extra['SELECT_ONLY'] .= ",sum(" . db_case( [ 'ga.WEIGHT', "''", "'0'", "ga.WEIGHT" ] ) . ") AS PARTIAL_WEIGHT,
					sum((gg.POINTS/ga.POINTS)*ga.WEIGHT) AS PARTIAL_WEIGHTED_GRADE";
			}

			$extra['FROM'] = " JOIN gradebook_assignments ga ON
				(((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						OR ga.COURSE_ID=cp.COURSE_ID)
						AND ga.STAFF_ID=cp.TEACHER_ID)
					AND ga.MARKING_PERIOD_ID='" . UserMP() . "')
				LEFT OUTER JOIN gradebook_grades gg ON
				(gg.STUDENT_ID=s.STUDENT_ID
					AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
					AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),gradebook_assignment_types gt";

			// Check Current date.
			$extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
				AND gt.COURSE_ID=cp.COURSE_ID
				AND (gg.POINTS IS NOT NULL
					OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
					OR CURRENT_DATE>(SELECT END_DATE
						FROM school_marking_periods
						WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";

			// Check Student enrollment.
			$extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL
				OR ga.DUE_DATE IS NULL
				OR ((ga.DUE_DATE>=ss.START_DATE
					AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE))
				AND (ga.DUE_DATE>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";

			if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
			{
				// @since 11.0 Add Weight Assignments option
				// Exclude Extra Credit assignments.
				$extra['WHERE'] .= " AND ga.POINTS>0";
			}

			if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'PRO' )
			{
				// FJ: limit Assignments to the ones due during the Progress Period.
				$extra['WHERE'] .= " AND ((ga.ASSIGNED_DATE IS NULL OR (SELECT END_DATE
					FROM school_marking_periods
					WHERE MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "')>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL
						OR (SELECT END_DATE
							FROM school_marking_periods
							WHERE MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "')>=ga.DUE_DATE
						AND (SELECT START_DATE
							FROM school_marking_periods
							WHERE MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "')<=ga.DUE_DATE))";
			}

			$extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";

			$extra['group'] = [ 'STUDENT_ID' ];

			$points_RET = GetStuList( $extra );
			//echo '<pre>'; var_dump($points_RET); echo '</pre>';

			unset( $extra );

			if ( ! empty( $points_RET ) )
			{
				foreach ( (array) $points_RET as $student_id => $student )
				{
					$total = $total_percent = $total_weighted_grade = $total_weights = 0;

					foreach ( (array) $student as $partial_points )
					{
						/**
						 * Do not include Extra Credit assignments
						 * when Total Points is 0 for the Type
						 * if the Gradebook is configured to Weight Grades:
						 * Division by zero is impossible.
						 */
						if ( $partial_points['PARTIAL_TOTAL'] != 0
							|| empty( $gradebook_config['WEIGHT'] ) )
						{
							$total += $partial_points['PARTIAL_POINTS'] * ( ! empty( $gradebook_config['WEIGHT'] ) ?
								$partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] :
								1
							);

							$total_percent += ( ! empty( $gradebook_config['WEIGHT'] ) ?
								$partial_points['FINAL_GRADE_PERCENT'] :
								$partial_points['PARTIAL_TOTAL']
							);

							if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
							{
								// @since 11.0 Add Weight Assignments option
								$total_weighted_grade += ( ! empty( $gradebook_config['WEIGHT'] ) ?
									$partial_points['FINAL_GRADE_PERCENT'] * $partial_points['PARTIAL_WEIGHTED_GRADE'] :
									$partial_points['PARTIAL_WEIGHTED_GRADE'] );

								$total_weights += ( ! empty( $gradebook_config['WEIGHT'] ) ?
									$partial_points['FINAL_GRADE_PERCENT'] * $partial_points['PARTIAL_WEIGHT'] :
									$partial_points['PARTIAL_WEIGHT'] );
							}
						}
					}

					if ( $total_percent != 0 )
					{
						$total /= $total_percent;
					}

					if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] )
						&& $total_weights > 0 )
					{
						// @since 11.0 Add Weight Assignments option
						$total = $total_weighted_grade / $total_weights;
					}

					$import_RET[$student_id] = [
						1 => [
							'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total, $course_period_id, 0, 'ID' ),
							'GRADE_PERCENT' => round( 100 * $total, 1 ),
						],
					];
				}
			}
		}
		elseif ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' || GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' )
		{
			if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' )
			{
				$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM school_marking_periods
				WHERE MP='QTR'
				AND PARENT_ID='" . (int) $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM school_marking_periods
				WHERE MP='SEM'
				AND MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'" );
				$prefix = 'SEM-';
			}
			else
			{
				$mp_RET = DBGet( "SELECT q.MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM school_marking_periods q,school_marking_periods s
				WHERE q.MP='QTR'
				AND s.MP='SEM'
				AND q.PARENT_ID=s.MARKING_PERIOD_ID
				AND s.PARENT_ID='" . (int) $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,DOES_GRADES
				FROM school_marking_periods
				WHERE MP='SEM'
				AND PARENT_ID='" . (int) $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM school_marking_periods
				WHERE MP='FY'
				AND MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'" );
				$prefix = 'FY-';
			}

			$mps = '';

			foreach ( (array) $mp_RET as $mp )
			{
				if ( $mp['DOES_GRADES'] === 'Y' )
				{
					$mps .= "'" . $mp['MARKING_PERIOD_ID'] . "',";
				}
			}

			$mps = mb_substr( $mps, 0, -1 );

			$percents_RET = DBGet( "SELECT STUDENT_ID,GRADE_PERCENT,MARKING_PERIOD_ID
				FROM student_report_card_grades
				WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'
				AND MARKING_PERIOD_ID IN (" . $mps . ")", [], [ 'STUDENT_ID' ] );

			foreach ( (array) $percents_RET as $student_id => $percents )
			{
				$total = $total_percent = 0;

				foreach ( (array) $percents as $percent )
				{
					$total += $percent['GRADE_PERCENT'] * $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];

					$total_percent += $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
				}

				if ( $total_percent != 0 )
				{
					$total /= $total_percent;
				}

				$import_RET[$student_id] = [
					1 => [
						'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total / 100, $course_period_id, 0, 'ID' ),
						'GRADE_PERCENT' => round( $total, 1 ),
					],
				];

				// FJ automatic comment on yearly grades.

				if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY' )
				{
					// FJ use Report Card Grades comments.
					$comment = _makeLetterGrade( $total / 100, $course_period_id, 0, 'COMMENT' );
					$import_comments_RET[$student_id][1]['COMMENT'] = $comment;
				}
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( [ 'modfunc' ] );
}

if ( $_REQUEST['modfunc'] === 'grades' )
{
	if ( ! empty( $_REQUEST['prev_mp'] ) )
	{
		require_once 'ProgramFunctions/_makePercentGrade.fnc.php';

		$import_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT
			FROM student_report_card_grades g,course_periods cp
			WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
			AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
			AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['prev_mp'] . "'", [], [ 'STUDENT_ID' ] );

		foreach ( (array) $import_RET as $student_id => $grade )
		{
			$import_RET[$student_id][1]['GRADE_PERCENT'] = _makePercentGrade(
				$grade[1]['REPORT_CARD_GRADE_ID'],
				$course_period_id
			);

			$import_RET[$student_id][1]['REPORT_CARD_GRADE_ID'] = $grade[1]['REPORT_CARD_GRADE_ID'];
		}
	}

	// Unset modfunc & prev MP & redirect URL.
	RedirectURL( [ 'modfunc', 'prev_mp' ] );
}

if ( $_REQUEST['modfunc'] === 'comments' )
{
	if ( ! empty( $_REQUEST['prev_mp'] ) )
	{
		$import_comments_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM student_report_card_grades g
		WHERE g.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['prev_mp'] . "'", [], [ 'STUDENT_ID' ] );

		$import_commentsA_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM student_report_card_comments g
		WHERE g.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['prev_mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NOT NULL)", [], [ 'STUDENT_ID', 'REPORT_CARD_COMMENT_ID' ] );

		//echo '<pre>'; var_dump($import_commentsA_RET); echo '</pre>';
		$import_commentsB_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID
		FROM student_report_card_comments g
		WHERE g.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['prev_mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NULL)", [], [ 'STUDENT_ID' ] );

		foreach ( (array) $import_commentsB_RET as $comments )
		{
			if ( count( $comments ) > $max_current_commentsB )
			{
				$max_current_commentsB = count( $comments );
			}
		}
	}

	// Unset modfunc & prev MP & redirect URL.
	RedirectURL( [ 'modfunc', 'prev_mp' ] );
}

if ( $_REQUEST['modfunc'] === 'clearall' )
{
	foreach ( (array) $current_RET as $student_id => $prev )
	{
		$current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'] = '';
		$current_RET[$student_id][1]['GRADE_PERCENT'] = '';
		$current_RET[$student_id][1]['COMMENT'] = '';
	}

	if ( isset( $current_commentsA_RET ) && is_array( $current_commentsA_RET ) )
	{
		foreach ( (array) $current_commentsA_RET as $student_id => $comments )
		{
			foreach ( (array) $comments as $id => $comment )
			{
				$current_commentsA_RET[$student_id][$id][1]['COMMENT'] = '';
			}
		}
	}

	if ( isset( $current_commentsB_RET ) && is_array( $current_commentsB_RET ) )
	{
		foreach ( (array) $current_commentsB_RET as $student_id => $comment )
		{
			foreach ( (array) $comment as $i => $comment )
			{
				$current_commentsB_RET[$student_id][$i] = '';
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values'] )
{
	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
	require_once 'ProgramFunctions/_makePercentGrade.fnc.php';

	$completed = true;

	//FJ add precision to year weighted GPA if not year course period.
	$course_period_mp = DBGetOne( "SELECT MP
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

	foreach ( (array) $_REQUEST['values'] as $student_id => $columns )
	{
		if ( ! empty( $current_RET[$student_id] ) )
		{
			$update_columns = [];

			if ( isset( $columns['percent'] )
				&& $columns['percent'] != '' )
			{
				// FJ bugfix SQL error invalid input syntax for type numeric.
				$percent = trim( $columns['percent'], '%' );

				if ( ! is_numeric( $percent ) )
				{
					$percent = (float) $percent;
				}

				if ( $percent > 999.9 )
				{
					$percent = '999.9';
				}
				elseif ( $percent < 0 )
				{
					$percent = '0';
				}

				if ( $columns['grade']
					|| $percent != '' )
				{
					$grade = ( $columns['grade'] ?
						$columns['grade'] :
						_makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' )
					);

					$letter = $grades_RET[$grade][1]['TITLE'];
					$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
					$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

					// FJ add precision to year weighted GPA if not year course period.

					if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
						&& $course_period_mp !== 'FY' )
					{
						$weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
					}

					$scale = $grades_RET[$grade][1]['GP_SCALE'];

					$gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
				}
				else
				{
					$grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
				}

				$update_columns = [
					'GRADE_PERCENT' => $percent,
					'REPORT_CARD_GRADE_ID' => $grade,
					'GRADE_LETTER' => $letter,
					'WEIGHTED_GP' => $weighted,
					'UNWEIGHTED_GP' => $unweighted,
					'GP_SCALE' => $scale,
					'COURSE_TITLE' => DBEscapeString( $course_RET[1]['COURSE_NAME'] ),
					'CREDIT_ATTEMPTED' => $course_RET[1]['CREDITS'],
					'CREDIT_EARNED' => ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ),
				];
			}
			elseif ( ! empty( $columns['grade'] ) )
			{
				$percent = _makePercentGrade( $columns['grade'], $course_period_id );
				$grade = $columns['grade'];
				$letter = $grades_RET[$grade][1]['TITLE'];
				$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

				// FJ add precision to year weighted GPA if not year course period.

				if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
					&& $course_period_mp !== 'FY' )
				{
					$weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
				}

				$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

				$scale = $grades_RET[$grade][1]['GP_SCALE'];

				$gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];

				$update_columns = [
					'GRADE_PERCENT' => $percent,
					'REPORT_CARD_GRADE_ID' => $grade,
					'GRADE_LETTER' => $letter,
					'WEIGHTED_GP' => $weighted,
					'UNWEIGHTED_GP' => $unweighted,
					'GP_SCALE' => $scale,
					'COURSE_TITLE' => DBEscapeString( $course_RET[1]['COURSE_NAME'] ),
					'CREDIT_ATTEMPTED' => $course_RET[1]['CREDITS'],
					'CREDIT_EARNED' => ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ),
				];
			}
			elseif ( isset( $columns['percent'] )
				|| isset( $columns['grade'] ) )
			{
				$percent = $grade = '';

				$update_columns = [
					'GRADE_PERCENT' => '',
					'REPORT_CARD_GRADE_ID' => '',
					'GRADE_LETTER' => '',
					'WEIGHTED_GP' => '',
					'UNWEIGHTED_GP' => '',
					'GP_SCALE' => '',
					'COURSE_TITLE' => DBEscapeString( $course_RET[1]['COURSE_NAME'] ),
					'CREDIT_ATTEMPTED' => $course_RET[1]['CREDITS'],
					'CREDIT_EARNED' => '0',
				];
			}
			else
			{
				$percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
				$grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
			}

			if ( isset( $columns['comment'] ) )
			{
				$update_columns['COMMENT'] = $columns['comment'];
			}

			if ( $update_columns )
			{
				// Reset Class Rank based on current CP Does Class Rank parameter.
				$update_columns['CLASS_RANK'] = $course_RET[1]['CLASS_RANK'];

				DBUpdate(
					'student_report_card_grades',
					$update_columns,
					[
						'STUDENT_ID' => (int) $student_id,
						'COURSE_PERIOD_ID' => (int) $course_period_id,
						'MARKING_PERIOD_ID' => (int) $_REQUEST['mp'],
					]
				);
			}
		}
		elseif ( ( isset( $columns['percent'] ) && $columns['percent'] != '' )
			|| $columns['grade']
			|| $columns['comment'] )
		{
			if ( isset( $columns['percent'] ) && $columns['percent'] != '' )
			{
				// FJ bugfix SQL error invalid input syntax for type numeric.
				$percent = trim( $columns['percent'], '%' );

				if ( ! is_numeric( $percent ) )
				{
					$percent = (float) $percent;
				}

				if ( $percent > 999.9 )
				{
					$percent = '999.9';
				}
				elseif ( $percent < 0 )
				{
					$percent = '0';
				}

				if ( $columns['grade'] || $percent != '' )
				{
					$grade = ( $columns['grade'] ? $columns['grade'] : _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' ) );
					$letter = $grades_RET[$grade][1]['TITLE'];
					$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
					$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

					//FJ add precision to year weighted GPA if not year course period

					if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
					{
						$weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
					}

					$scale = $grades_RET[$grade][1]['GP_SCALE'];

					$gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
				}
				else
				{
					$grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
				}
			}
			elseif ( $columns['grade'] )
			{
				$percent = _makePercentGrade( $columns['grade'], $course_period_id );
				$grade = $columns['grade'];
				$letter = $grades_RET[$grade][1]['TITLE'];
				$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

				//FJ add precision to year weighted GPA if not year course period

				if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
				{
					$weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
				}

				$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

				$scale = $grades_RET[$grade][1]['GP_SCALE'];

				$gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
			}
			else
			{
				$percent = $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
			}

			DBInsert(
				'student_report_card_grades',
				[
					'SYEAR' => UserSyear(),
					'SCHOOL_ID' => UserSchool(),
					'STUDENT_ID' => (int) $student_id,
					'COURSE_PERIOD_ID' => (int) $course_period_id,
					'MARKING_PERIOD_ID' => (int) $_REQUEST['mp'],
					'REPORT_CARD_GRADE_ID' => $grade,
					'GRADE_PERCENT' => $percent,
					'COMMENT' => $columns['comment'],
					'GRADE_LETTER' => DBEscapeString( $letter ),
					'WEIGHTED_GP' => $weighted,
					'UNWEIGHTED_GP' => $unweighted,
					'GP_SCALE' => $scale,
					'COURSE_TITLE' => DBEscapeString( $course_RET[1]['COURSE_NAME'] ),
					'CREDIT_ATTEMPTED' => $course_RET[1]['CREDITS'],
					'CREDIT_EARNED' => ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ),
					'CLASS_RANK' => $course_RET[1]['CLASS_RANK'],
					'CREDIT_HOURS' => $course_RET[1]['CREDIT_HOURS'],
				]
			);
		}
		else
		{
			$percent = $grade = '';
		}

		//DBQuery("DELETE FROM student_report_card_grades WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."'");

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
		{
			$completed = (bool) $grade;
		}
		elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
		{
			$completed = $percent != '';
		}
		else
		{
			$completed = $percent != '' && $grade;
		}

		/*if ( !( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 ? $grade : ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 ? $percent != '' : $percent != '' && $grade ) ) )
		$completed = false;*/

		if ( isset( $columns['commentsA'] ) && is_array( $columns['commentsA'] ) )
		{
			foreach ( (array) $columns['commentsA'] as $id => $comment )
			{
				if ( ! empty( $current_commentsA_RET[$student_id][$id] ) )
				{
					if ( $comment )
					{
						DBUpdate(
							'student_report_card_comments',
							[ 'COMMENT' => $comment ],
							[
								'STUDENT_ID' => (int) $student_id,
								'COURSE_PERIOD_ID' => (int) $course_period_id,
								'MARKING_PERIOD_ID' => (int) $_REQUEST['mp'],
								'REPORT_CARD_COMMENT_ID' => (int) $id,
							]
						);
					}
					else
					{
						DBQuery( "DELETE FROM student_report_card_comments
							WHERE STUDENT_ID='" . (int) $student_id . "'
							AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'
							AND MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
							AND REPORT_CARD_COMMENT_ID='" . (int) $id . "'" );
					}
				}
				elseif ( $comment )
				{
					DBInsert(
						'student_report_card_comments',
						[
							'SYEAR' => UserSyear(),
							'SCHOOL_ID' => UserSchool(),
							'STUDENT_ID' => (int) $student_id,
							'COURSE_PERIOD_ID' => (int) $course_period_id,
							'MARKING_PERIOD_ID' => (int) $_REQUEST['mp'],
							'REPORT_CARD_COMMENT_ID' => (int) $id,
							'COMMENT' => $comment,
						]
					);
				}
			}
		}

		// create mapping for current
		$old = [];

		if ( isset( $current_commentsB_RET[$student_id] ) && is_array( $current_commentsB_RET[$student_id] ) )
		{
			foreach ( (array) $current_commentsB_RET[$student_id] as $i => $comment )
			{
				$old[$comment['REPORT_CARD_COMMENT_ID']] = $i;
			}
		}

		// create change list
		$change = [];

		if ( isset( $columns['commentsB'] ) && is_array( $columns['commentsB'] ) )
		{
			foreach ( (array) $columns['commentsB'] as $i => $comment )
			{
				$change[$i] = [ 'REPORT_CARD_COMMENT_ID' => 0 ];
			}
		}

		// prune changes already in current set and reserve if in change list

		if ( isset( $columns['commentsB'] ) && is_array( $columns['commentsB'] ) )
		{
			foreach ( (array) $columns['commentsB'] as $i => $comment )
			{
				if ( $comment )
				{
					if ( ! empty( $old[$comment] ) )
					{
						if ( $change[$old[$comment]] )
						{
							$change[$old[$comment]]['REPORT_CARD_COMMENT_ID'] = $comment;
						}

						$columns['commentsB'][$i] = false;
					}
				}
			}
		}

		// assign changes at their index if possible
		$new = [];

		if ( isset( $columns['commentsB'] ) && is_array( $columns['commentsB'] ) )
		{
			foreach ( (array) $columns['commentsB'] as $i => $comment )
			{
				if ( $comment )
				{
					if ( empty( $new[$comment] ) )
					{
						if ( ! $change[$i]['REPORT_CARD_COMMENT_ID'] )
						{
							$change[$i]['REPORT_CARD_COMMENT_ID'] = $comment;
							$new[$comment] = $i;
							$columns['commentsB'][$i] = false;
						}
					}
					else
					{
						$columns['commentsB'][$i] = false;
					}
				}
			}
		}

		// assign remaining changes to first available
		reset( $change );

		if ( isset( $columns['commentsB'] ) && is_array( $columns['commentsB'] ) )
		{
			foreach ( (array) $columns['commentsB'] as $i => $comment )
			{
				if ( $comment )
				{
					if ( ! $new[$comment] )
					{
						while ( $change[key( $change )]['REPORT_CARD_COMMENT_ID'] )
						{
							next( $change );
						}

						$change[key( $change )]['REPORT_CARD_COMMENT_ID'] = $comment;
						$new[$comment] = key( $change );
					}

					$columns['commentsB'][$i] = false;
				}
			}
		}

		// update the db

		foreach ( (array) $change as $i => $comment )
		{
			if ( ! empty( $current_commentsB_RET[$student_id][$i] ) )
			{
				if ( $comment['REPORT_CARD_COMMENT_ID'] )
				{
					if ( $comment['REPORT_CARD_COMMENT_ID'] != $current_commentsB_RET[$student_id][$i]['REPORT_CARD_COMMENT_ID'] )
					{
						DBUpdate(
							'student_report_card_comments',
							[ 'REPORT_CARD_COMMENT_ID' => (int) $comment['REPORT_CARD_COMMENT_ID'] ],
							[
								'STUDENT_ID' => (int) $student_id,
								'COURSE_PERIOD_ID' => (int) $course_period_id,
								'MARKING_PERIOD_ID' => (int) $_REQUEST['mp'],
								'REPORT_CARD_COMMENT_ID' => (int) $current_commentsB_RET[$student_id][$i]['REPORT_CARD_COMMENT_ID'],
							]
						);
					}
				}
				else
				{
					DBQuery( "DELETE FROM student_report_card_comments
					WHERE STUDENT_ID='" . (int) $student_id . "'
					AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'
					AND MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
					AND REPORT_CARD_COMMENT_ID='" . (int) $current_commentsB_RET[$student_id][$i]['REPORT_CARD_COMMENT_ID'] . "'" );
				}
			}
			elseif ( $comment['REPORT_CARD_COMMENT_ID'] )
			{
				DBInsert(
					'student_report_card_comments',
					[
						'SYEAR' => UserSyear(),
						'SCHOOL_ID' => UserSchool(),
						'STUDENT_ID' => (int) $student_id,
						'COURSE_PERIOD_ID' => (int) $course_period_id,
						'MARKING_PERIOD_ID' => (int) $_REQUEST['mp'],
						'REPORT_CARD_COMMENT_ID' => (int) $comment['REPORT_CARD_COMMENT_ID'],
					]
				);
			}
		}
	}

	// @since 4.7 Automatic Class Rank calculation.
	ClassRankCalculateAddMP( $_REQUEST['mp'] );

	if ( $completed )
	{
		if ( ! $current_completed )
		{
			DBInsert(
				'grades_completed',
				[
					'STAFF_ID' => User( 'STAFF_ID' ),
					'MARKING_PERIOD_ID' => (int) $_REQUEST['mp'],
					'COURSE_PERIOD_ID' => (int) $course_period_id,
				]
			);
		}
	}
	elseif ( $current_completed )
	{
		DBQuery( "DELETE FROM grades_completed
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
			AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );
	}

	$current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
		g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM student_report_card_grades g
		WHERE g.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'", [], [ 'STUDENT_ID' ] );

	if ( $_REQUEST['tab_id'] == '-1' )
	{
		$current_commentsB_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID
		FROM student_report_card_comments g,course_periods cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NULL)", [], [ 'STUDENT_ID' ] );

		$max_current_commentsB = 0;

		foreach ( (array) $current_commentsB_RET as $comments )
		{
			if ( count( $comments ) > $max_current_commentsB )
			{
				$max_current_commentsB = count( $comments );
			}
		}
	}
	elseif ( $_REQUEST['tab_id'] == '0' )
	{
		$current_commentsA_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM student_report_card_comments g,course_periods cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID='0')", [], [ 'STUDENT_ID', 'REPORT_CARD_COMMENT_ID' ] );
	}
	elseif ( ! empty( $_REQUEST['tab_id'] ) )
	{
		$current_commentsA_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM student_report_card_comments g,course_periods cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE CATEGORY_ID='" . (int) $_REQUEST['tab_id'] . "')", [], [ 'STUDENT_ID', 'REPORT_CARD_COMMENT_ID' ] );
	}

	$current_completed = count( (array) DBGet( "SELECT 1
		FROM grades_completed
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'" ) );

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

$mps_onchange_URL = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] .
	'&include_inactive=' . $_REQUEST['include_inactive'] . "&mp=" );

$mps_select = '<select name="mp_select" id="mp_select" onchange="' .
	AttrEscape( 'ajaxLink(' . json_encode( $mps_onchange_URL ) . ' + this.value);' ) . '">';

$allow_edit = false;

foreach ( (array) $all_mp_ids as $mp_id )
{
	if ( $_REQUEST['mp'] == $mp_id
		&& GetMP( $mp_id, 'POST_START_DATE' )
		&& DBDate() >= GetMP( $mp_id, 'POST_START_DATE' )
		&& DBDate() <= GetMP( $mp_id, 'POST_END_DATE' ) )
	{
		$allow_edit = true;
	}

	if ( GetMP( $mp_id, 'DOES_GRADES' ) == 'Y' || $mp_id == UserMP() )
	{
		$mps_select .= '<option value="' . AttrEscape( $mp_id ) . '"' .
			( $mp_id == $_REQUEST['mp'] ? ' selected' : '' ) . '>' . GetMP( $mp_id ) . '</option>';
	}
}

$mps_select .= '</select><label for="mp_select" class="a11y-hidden">' . _( 'Marking Period' ) . '</label>';

// If running as a teacher program then rosario[allow_edit] will already be set according to admin permissions.

if ( User( 'PROFILE' ) === 'teacher'
	&& mb_strpos( $_REQUEST['modname'], 'TeacherPrograms' ) === false )
{
	$is_after_grade_post_start_date = DBGetOne( "SELECT 1
		FROM school_marking_periods
		WHERE MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND (POST_START_DATE IS NULL OR POST_START_DATE<=CURRENT_DATE)" );

	$_ROSARIO['allow_edit'] = ( ProgramConfig( 'grades', 'GRADES_TEACHER_ALLOW_EDIT' )
		&& $is_after_grade_post_start_date )
	|| $allow_edit;
}

$extra['SELECT'] = ",ssm.STUDENT_ID AS REPORT_CARD_GRADE";

$extra['functions'] = [
	'FULL_NAME' => 'makePhotoTipMessage',
	'REPORT_CARD_GRADE' => '_makeLetterPercent',
];

if ( GetMP( $_REQUEST['mp'], 'DOES_COMMENTS' ) == 'Y' )
{
	//FJ fix error Warning: Invalid argument supplied for foreach()

	if ( isset( $commentsA_RET ) )
	{
		foreach ( (array) $commentsA_RET as $value )
		{
			$extra['SELECT'] .= ',\'' . $value['ID'] . '\' AS CA' . $value['ID'] . ',\'' . $value['SCALE_ID'] . '\' AS CAC' . $value['ID'];
			$extra['functions'] += [ 'CA' . $value['ID'] => '_makeCommentsA' ];
		}
	}

	for ( $i = 1; $i <= $max_current_commentsB; $i++ )
	{
		$extra['SELECT'] .= ',\'' . $i . '\' AS CB' . $i;
		$extra['functions'] += [ 'CB' . $i => '_makeCommentsB' ];
	}

	if ( ! empty( $commentsB_select ) && AllowEdit() )
	{
		$extra['SELECT'] .= ',\'' . $i . '\' AS CB' . $i;
		$extra['functions'] += [ 'CB' . $i => '_makeCommentsB' ];
	}
}

$extra['SELECT'] .= ",'' AS COMMENTS,'' AS COMMENT";
$extra['functions'] += [ 'COMMENT' => '_makeComment' ];
$extra['MP'] = UserMP();
$extra['DATE'] = GetMP( $_REQUEST['mp'], 'END_DATE' );

$stu_RET = GetStuList( $extra );

/**
 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
 * Then teacher submits the form, data would be saved for CP B...
 *
 * Must be used in combination with
 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
 */
echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
	( ! empty( $categories_RET ) && GetMP( $_REQUEST['mp'], 'DOES_COMMENTS' ) == 'Y' ?
		'&tab_id=' . $_REQUEST['tab_id'] : '' ) .
	'&mp=' . $_REQUEST['mp'] . '&period=' . $course_period_id  ) . '" method="POST">';

if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	$tipmessage = '';

	if ( ! empty( $commentsB_RET )
		&& GetMP( $_REQUEST['mp'], 'DOES_COMMENTS' ) == 'Y' )
	{
		$tipmsg = '';

		foreach ( (array) $commentsB_RET as $comment )
		{
			$tipmsg .= $comment[1]['SORT_ORDER'] . ' - ' . $comment[1]['TITLE'] . '<br />';
		}

		$tipmessage = makeTipMessage(
			$tipmsg,
			_( 'Report Card Comments' ),
			button( 'comment', _( 'Comment Codes' ) )
		);
	}

	// Add All Courses & Course-specific comments scales tipmessage.
	elseif ( ! empty( $commentsA_RET )
		&& GetMP( $_REQUEST['mp'], 'DOES_COMMENTS' ) == 'Y' )
	{
		// Course-specific.
		$where = " AND CATEGORY_ID='" . (int) $_REQUEST['tab_id'] . "'";

		if ( $_REQUEST['tab_id'] == '0' )
		{
			// All Courses.
			$where = " AND COURSE_ID='" . (int) $_REQUEST['tab_id'] . "'";
		}

		$commentsAbis_RET = DBGet( "SELECT ID,TITLE,SCALE_ID
			FROM report_card_comments
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" .
			$where . "
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'SCALE_ID' ] );

		foreach ( (array) $commentsAbis_RET as $scale_id => $commentsAbis )
		{
			$tipmsg = '';

			$tiplabel = [];

			if ( isset( $comment_codes_RET[$scale_id] ) )
			{
				foreach ( (array) $comment_codes_RET[$scale_id] as $comment )
				{
					$tipmsg .= $comment['TITLE'] . ': ' . $comment['COMMENT'] . '<br />';
				}
			}

			foreach ( (array) $commentsAbis as $commentAbis )
			{
				$tiplabel[] = $commentAbis['TITLE'];
			}

			$tipmessage .= ' &nbsp; ' . makeTipMessage(
				$tipmsg,
				_( 'Comment Codes' ),
				button( 'comment', implode( ' / ', $tiplabel ) )
			);
		}
	}

	DrawHeader(
		$mps_select,
		SubmitButton(),
		CheckBoxOnclick( 'include_inactive', _( 'Include Inactive Students' ) )
	);

	$grade_posting_dates_text = '';

	if ( GetMP( $_REQUEST['mp'], 'POST_START_DATE' ) )
	{
		$grade_posting_dates_text = ' ' . sprintf(
			_( 'Grade Posting dates: %s - %s' ),
			ProperDate( GetMP( $_REQUEST['mp'], 'POST_START_DATE' ) ),
			ProperDate( GetMP( $_REQUEST['mp'], 'POST_END_DATE' ) )
		);
	}

	DrawHeader(
		( $current_completed ?
			'<span style="color:green">' . _( 'These grades are complete.' ) . '</span>' :
			'<span style="color:red">' . _( 'These grades are NOT complete.' ) . '</span>' ) .
		( AllowEdit() ?
			// CSS .rseparator responsive separator: hide text separator & break line
			'<span class="rseparator"> | </span><span style="color:green">' .
				_( 'You can edit these grades.' ) . $grade_posting_dates_text . '</span>' :
			'<span class="rseparator"> | </span><span style="color:red">' .
				_( 'You can not edit these grades.' ) . $grade_posting_dates_text . '</span>' )
	);

	$gb_header = [];

	if ( AllowEdit() )
	{
		$gb_header[] = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&include_inactive=' . $_REQUEST['include_inactive'] .
			'&modfunc=gradebook&mp=' . $_REQUEST['mp'] ) . '">' . _( 'Get Gradebook Grades.' ) . '</a>';

		$prev_mp = DBGet( "SELECT MARKING_PERIOD_ID,TITLE,START_DATE
			FROM school_marking_periods
			WHERE MP='" . GetMP( $_REQUEST['mp'], 'MP' ) . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND START_DATE<'" . GetMP( $_REQUEST['mp'], 'START_DATE' ) . "'
			ORDER BY START_DATE DESC LIMIT 1" );

		$prev_mp = issetVal( $prev_mp[1], [] );

		// Remove Get previous MP Grades & Comments if course period's marking period is a quarter.
		$mp_is_quarter = DBGetOne( "SELECT 1
			FROM course_periods
			WHERE MP='QTR'
			AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

		if ( $prev_mp && ! $mp_is_quarter )
		{
			$gb_header[] = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&include_inactive=' . $_REQUEST['include_inactive'] .
				'&modfunc=grades&tab_id=' . $_REQUEST['tab_id'] . '&mp=' . $_REQUEST['mp'] .
				'&prev_mp=' . $prev_mp['MARKING_PERIOD_ID'] ) . '">' .
				sprintf( _( 'Get %s Grades' ), $prev_mp['TITLE'] ) . '</a>';

			$gb_header[] = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&include_inactive=' . $_REQUEST['include_inactive'] .
				'&modfunc=comments&tab_id=' . $_REQUEST['tab_id'] . '&mp=' . $_REQUEST['mp'] .
				'&prev_mp=' . $prev_mp['MARKING_PERIOD_ID'] ) . '">' .
				sprintf( _( 'Get %s Comments' ), $prev_mp['TITLE'] ) . '</a>';
		}

		$gb_header[] = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&include_inactive=' . $_REQUEST['include_inactive'] .
			'&modfunc=clearall&tab_id=' . $_REQUEST['tab_id'] . '&mp=' . $_REQUEST['mp'] ) . '">' .
			_( 'Clear All' ) . '</a>';
	}

	// CSS .rseparator responsive separator: hide text separator & break line
	DrawHeader( implode( '<span class="rseparator"> | </span>', $gb_header ) );
	DrawHeader( '', $tipmessage );
}
else
{
	DrawHeader( $course_title );
	DrawHeader( GetMP( UserMP() ) );
}

$LO_columns = [ 'FULL_NAME' => _( 'Student' ), 'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ) ];

if ( $_REQUEST['include_inactive'] == 'Y' )
{
	$LO_columns += [ 'ACTIVE' => _( 'School Status' ), 'ACTIVE_SCHEDULE' => _( 'Course Status' ) ];
}

$LO_columns += [
	'REPORT_CARD_GRADE' => ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 ? _( 'Letter' ) :
		( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 ? _( 'Percent' ) :
			'<span class="nobr">' . _( 'Letter' ) . ' ' . _( 'Percent' ) . '</span>' ) ),
];

if ( GetMP( $_REQUEST['mp'], 'DOES_COMMENTS' ) == 'Y' )
{
	//FJ fix error Warning: Invalid argument supplied for foreach()

	if ( isset( $commentsA_RET ) )
	{
		foreach ( (array) $commentsA_RET as $value )
		{
			$LO_columns += [ 'CA' . $value['ID'] => $value['TITLE'] ];
		}
	}

	for ( $i = 1; $i <= $max_current_commentsB; $i++ )
	{
		$LO_columns += [ 'CB' . $i => sprintf( _( 'Comment %d' ), $i ) ];
	}

	if ( ! empty( $commentsB_select ) && AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$LO_columns += [ 'CB' . $i => _( 'Add Comment' ) ];
	}
}

if ( ! ProgramConfig( 'grades', 'GRADES_HIDE_NON_ATTENDANCE_COMMENT' ) )
{
	$LO_columns += [ 'COMMENT' => _( 'Comment' ) ];
}

foreach ( (array) $categories_RET as $id => $category )
{
	$tabs[] = [
		'title' => $category[1]['TITLE'],
		'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp=' . $_REQUEST['mp'] . '&tab_id=' . $id,
	] + ( $category[1]['COLOR'] ? [ 'color' => $category[1]['COLOR'] ] : [] );
}

$LO_options = [ 'save' => false, 'search' => false ];

if ( ! empty( $categories_RET ) && GetMP( $_REQUEST['mp'], 'DOES_COMMENTS' ) == 'Y' )
{
	$LO_options['header'] = WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp=' . $_REQUEST['mp'] . '&tab_id=' . $_REQUEST['tab_id'] );

	if ( $categories_RET[$_REQUEST['tab_id']][1]['COLOR'] )
	{
		$LO_options['header_color'] = $categories_RET[$_REQUEST['tab_id']][1]['COLOR'];
	}
}

$link = [];

if ( $stu_RET )
{
	// @since 9.1 Add Class average.
	$link['add']['html'] = [
		'FULL_NAME' => '<b>' . _( 'Class average' ) . '</b>',
		'REPORT_CARD_GRADE' => GetClassAverage(
			$course_period_id,
			$_REQUEST['mp'],
			ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' )
		),
	];
}

echo '<br />';

ListOutput( $stu_RET, $LO_columns, 'Student', 'Students', $link, [], $LO_options );

// @since 4.6 Navigate form inputs vertically using tab key.
// @link https://stackoverflow.com/questions/38575817/set-tabindex-in-vertical-order-of-columns
?>
<script>
function fixVerticalTabindex(selector) {
	var tabindex = 1;
	$(selector).each(function(i, tbl) {
		$(tbl).find('tr').first().find('td').each(function(clmn, el) {
			$(tbl).find('tr td:nth-child(' + (clmn + 1) + ') input,tr td:nth-child(' + (clmn + 1) + ') select').each(function(j, input) {
				$(input).attr('tabindex', tabindex++);
			});
		});
	});
}

fixVerticalTabindex('.list-wrapper .list tbody');
</script>
<?php

echo '<br /><div class="center">' . SubmitButton() . '</div>';
echo '</form>';

/**
 * @param $student_id
 * @param $column
 * @return mixed
 */
function _makeLetterPercent( $student_id, $column )
{
	global $current_RET, $import_RET, $grades_select, $student_count;

	if ( ! empty( $import_RET[$student_id] ) )
	{
		$select_percent = $import_RET[$student_id][1]['GRADE_PERCENT'];
		$select_grade = $import_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
		$div = false;
	}
	else
	{
		if ( empty( $current_RET[$student_id][1] ) )
		{
			$select_percent = $select_grade = '';
		}
		else
		{
			$select_percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
			$select_grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
		}

		$div = true;
	}

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$student_count++;

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
		{
			$return = SelectInput(
				$select_grade,
				'values[' . $student_id . '][grade]',
				'',
				$grades_select,
				false,
				'',
				$div
			);
		}
		elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
		{
			$return = TextInput(
				$select_percent == '' ? '' : $select_percent . '%',
				'values[' . $student_id . '][percent]',
				'',
				'size=5',
				$div
			);
		}
		else
		{
			if ( AllowEdit()
				&& $div
				&& $select_percent != ''
				&& $select_grade )
			{
				$id = $student_id;

				$select_html = '<span class="nobr">' . SelectInput(
					$select_grade,
					'values[' . $student_id . '][grade]',
					'',
					$grades_select,
					false,
					'',
					false
				) . ' ' . TextInput(
					$select_percent != '' ? $select_percent . '%' : '',
					'values[' . $student_id . '][percent]',
					'',
					'size="5"',
					false
				) . '</span>';

				$return = InputDivOnclick(
					$id,
					$select_html,
					'<span class="nobr">' . ( isset( $grades_select[$select_grade] ) ?
						$grades_select[$select_grade][1] :
						'<span style="color:red">' . $select_grade . '</span>' ) .
					' ' . $select_percent . '%</span>',
					''
				);
			}
			else
			{
				$return = '<span class="nobr">' . SelectInput(
					$select_grade,
					'values[' . $student_id . '][grade]',
					'',
					$grades_select,
					false,
					'',
					false
				) . ' ' . TextInput(
					$select_percent != '' ? $select_percent . '%' : ( $select_grade ? '%' : '' ),
					'values[' . $student_id . '][percent]',
					'',
					'size="5"',
					false
				) . '</span>';
			}
		}
	}
	else
	{
		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
		{
			$return = ( $grades_select[$select_grade] ? $grades_select[$select_grade][1] : '<span style="color:red">' . $select_grade . '</span>' );
		}
		elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
		{
			$return = $select_percent . '%';
		}
		else
		{
			$return = '<span class="nobr">' . ( $grades_select[$select_grade] ? $grades_select[$select_grade][1] : '<span style="color:red">' . $select_grade . '</span>' ) . ' ' . $select_percent . '%' . '</span>';
		}
	}

	if ( $select_percent != '' )
	{
		// Add Percent grade inside HTML comment so we can accurately sort by Grade column.
		$return = '<!--' . $select_percent . '-->' . $return;
	}

	return $return;
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _makeComment( $value, $column )
{
	global $THIS_RET, $current_RET, $import_comments_RET;

	if ( ! empty( $import_comments_RET[$THIS_RET['STUDENT_ID']] ) )
	{
		$select = $import_comments_RET[$THIS_RET['STUDENT_ID']][1]['COMMENT'];
		$div = false;
	}
	else
	{
		$select = isset( $current_RET[$THIS_RET['STUDENT_ID']][1]['COMMENT'] ) ?
			$current_RET[$THIS_RET['STUDENT_ID']][1]['COMMENT'] :
			'';

		$div = true;
	}

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$return = TextInput(
			$select,
			'values[' . $THIS_RET['STUDENT_ID'] . '][comment]',
			'',
			'size=20 maxlength=500',
			$div
		);

		if ( mb_strlen( (string) $select ) > 60 )
		{
			// Comments length > 60 chars, responsive table ColorBox.
			$return = '<div id="divInputFinalGradesComment' . $THIS_RET['STUDENT_ID']. '" class="rt2colorBox">' .
				$return . '</div>';
		}
	}
	else
	{
		$return = $select;
	}

	return $return;
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _makeCommentsA( $value, $column )
{
	global $THIS_RET, $current_commentsA_RET, $import_commentsA_RET, $commentsA_select;

	if ( ! empty( $import_commentsA_RET[$THIS_RET['STUDENT_ID']][$value] ) )
	{
		$select = $import_commentsA_RET[$THIS_RET['STUDENT_ID']][$value][1]['COMMENT'];
		$div = false;
	}
	else
	{
		if ( empty( $current_commentsA_RET[$THIS_RET['STUDENT_ID']][$value][1]['COMMENT'] )
			&& ! $import_commentsA_RET
			&& AllowEdit() )
		{
			$select = Preferences( 'COMMENT_' . $THIS_RET['CAC' . $value], 'Gradebook' );
			$div = false;
		}
		else
		{
			$select = issetVal( $current_commentsA_RET[$THIS_RET['STUDENT_ID']][$value][1]['COMMENT'] );
			$div = true;
		}
	}

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$return = SelectInput(
			$select,
			'values[' . $THIS_RET['STUDENT_ID'] . '][commentsA][' . $value . ']',
			'',
			issetVal( $commentsA_select[$THIS_RET['CAC' . $value]], [] ),
			_( 'N/A' ),
			'',
			$div
		);
	}
	else
	{
		$return = $select != ' ' ? $select : 'o';
	}

	return $return;
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _makeCommentsB( $value, $column )
{
	global $THIS_RET,
	$current_commentsB_RET,
	$import_commentsB_RET,
	$commentsB_RET,
	$max_current_commentsB,
	$commentsB_select;

	$select = null;

	if ( ! empty( $import_commentsB_RET[$THIS_RET['STUDENT_ID']][$value] ) )
	{
		if ( isset( $import_commentsB_RET[$THIS_RET['STUDENT_ID']][$value]['REPORT_CARD_COMMENT_ID'] ) )
		{
			$select = $import_commentsB_RET[$THIS_RET['STUDENT_ID']][$value]['REPORT_CARD_COMMENT_ID'];
		}

		$div = false;
	}
	else
	{
		if ( isset( $current_commentsB_RET[$THIS_RET['STUDENT_ID']][$value]['REPORT_CARD_COMMENT_ID'] ) )
		{
			$select = $current_commentsB_RET[$THIS_RET['STUDENT_ID']][$value]['REPORT_CARD_COMMENT_ID'];
		}

		$div = true;
	}

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		if ( $value > $max_current_commentsB )
		{
			$return = SelectInput(
				'',
				'values[' . $THIS_RET['STUDENT_ID'] . '][commentsB][' . $value . ']',
				'',
				$commentsB_select,
				_( 'N/A' ),
				'style="width:200px"'
			);
		}
		elseif ( ! empty( $import_commentsB_RET[$THIS_RET['STUDENT_ID']][$value] )
			|| isset( $current_commentsB_RET[$THIS_RET['STUDENT_ID']][$value] ) )
		{
			$return = SelectInput(
				$select,
				'values[' . $THIS_RET['STUDENT_ID'] . '][commentsB][' . $value . ']',
				'',
				$commentsB_select,
				_( 'N/A' ),
				'style="width:200px"',
				$div
			);
		}
		else
		{
			$return = '';
		}
	}
	else
	{
		$return = issetVal( $commentsB_RET[$select][1]['TITLE'] );
	}

	return $return;
}
