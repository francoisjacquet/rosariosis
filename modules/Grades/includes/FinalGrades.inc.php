<?php
/**
 * Final Grades functions & AJAX modfunc.
 *
 * @see InputFinalGrades.php, Grades.php, Assignments.php & Grades Import module
 *
 * @package RosarioSIS
 */

if ( $_REQUEST['modfunc'] === 'final_grades_all_mp_save_ajax' )
{
	// Note: no need to call RedirectURL() & unset $_REQUEST params here as we die just after.
	$cp_ids = empty( $_REQUEST['cp_id'] ) ? '0' : $_REQUEST['cp_id'];
	$qtr_id = empty( $_REQUEST['qtr_id'] ) ? '0' : $_REQUEST['qtr_id'];

	foreach ( (array) $cp_ids as $cp_id )
	{
		FinalGradesAllMPSave( $cp_id, $qtr_id );
	}

	die( 1 );
}

/**
 * Automatically calculate & save Course Period's Final Grades using Gradebook Grades
 * Does not include Inactive Students.
 *
 * Call FinalGradesAllMPSave() using 'final_grades_all_mp_save_ajax' modfunc.
 * The benefit of calling FinalGradesAllMPSave() in AJAX is to not make the user wait for the response.
 * Anyway, the user does not need to see the result immediately / on the same screen.
 * The AJAX call runs in the background.
 *
 * @see Grades.php: after Gradebook Grades insert or update.
 * @see Grades Import module: after Gradebook Grades insert or update.
 * @see Assignments.php:
 *      - after Assignment Type's "Percent of Final Grade" update.
 *      - after Assignment insert or "Points"/"Weight" update.
 *      - after Assignment delete.
 * @see MassCreateAssignments.php: after Assignments insert.
 *
 * @since 11.8
 *
 * @param int|array $cp_id  Course Period ID or Course Period IDs array.
 * @param int       $qtr_id Quarter ID.
 *
 * @return boolean True.
 */
function FinalGradesAllMPSaveAJAX( $cp_id, $qtr_id )
{
	$url = PreparePHP_SELF( [
		'modname' => $_REQUEST['modname'],
		'modfunc' => 'final_grades_all_mp_save_ajax',
		'cp_id' => $cp_id,
		'qtr_id' => $qtr_id,
		// 'include_inactive' => 'Y',
	] );

	// Call FinalGradesAllMPSave() using 'final_grades_all_mp_save_ajax' modfunc.
	?>
	<script>
		$.ajax( <?php echo json_encode( $url ); ?> );
	</script>
	<?php

	return true;
}

/**
 * Automatically calculate & save Course Period's Final Grades using Gradebook Grades
 * (all graded Marking Periods, Semester and Full Year only if percentages are set)
 *
 * @uses FinalGradesQtrOrProCalculate()
 * @uses FinalGradesSemOrFYCalculate()
 * @uses FinalGradesSave()
 *
 * @since 11.8
 * @since 11.8.6 Automatic Class Rank calculation.
 * @since 11.8.6 SQL INSERT INTO grades_completed so "These grades are complete." is displayed on the Input Final Grades program
 *
 * @param int $cp_id  Course Period ID.
 * @param int $qtr_id Quarter ID.
 *
 * @return bool True if Final Grades saved, else false.
 */
function FinalGradesAllMPSave( $cp_id, $qtr_id )
{
	if ( ! $cp_id
		|| GetMP( $qtr_id, 'MP' ) !== 'QTR' )
	{
		return false;
	}

	// First, calculate Final Grades for Quarter.
	$final_grades = FinalGradesQtrOrProCalculate( $cp_id, $qtr_id );

	if ( ! $final_grades )
	{
		return false;
	}

	FinalGradesSave( $cp_id, $qtr_id, $final_grades );

	$final_grade_mps[] = $qtr_id;

	$pro = GetChildrenMP( 'PRO', $qtr_id );

	$pro = explode( ',', $pro );

	foreach ( $pro as $pro_id )
	{
		$pro_id = trim( $pro_id, "'" ); // Remove single quotes around ID.

		if ( ! GetMP( $pro_id, 'DOES_GRADES' ) )
		{
			continue;
		}

		// Then, calculate Final Grades for Progress Periods (only if graded).
		$final_grades = FinalGradesQtrOrProCalculate( $cp_id, $pro_id );

		if ( $final_grades )
		{
			FinalGradesSave( $cp_id, $pro_id, $final_grades );

			$final_grade_mps[] = $pro_id;
		}
	}

	$sem_id = GetParentMP( 'SEM', $qtr_id );

	if ( GetMP( $sem_id, 'DOES_GRADES' ) )
	{
		// Then, calculate Final Grades for Semester (only if graded & Final Grading Percentages set).
		$final_grades = FinalGradesSemOrFYCalculate( $cp_id, $sem_id, 'fail' );

		if ( $final_grades )
		{
			FinalGradesSave( $cp_id, $sem_id, $final_grades );

			$final_grade_mps[] = $sem_id;
		}
	}

	$fy_id = GetParentMP( 'FY', $sem_id );

	if ( GetMP( $fy_id, 'DOES_GRADES' ) )
	{
		// Then, calculate Final Grades for Full Year (only if graded & Final Grading Percentages set).
		$final_grades = FinalGradesSemOrFYCalculate( $cp_id, $fy_id, 'fail' );

		if ( $final_grades )
		{
			FinalGradesSave( $cp_id, $fy_id, $final_grades );

			$final_grade_mps[] = $fy_id;
		}
	}

	require_once 'modules/Grades/includes/ClassRank.inc.php';

	$teacher_id = DBGetOne( "SELECT TEACHER_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	foreach ( $final_grade_mps as $mp_id )
	{
		$current_completed = (bool) DBGetOne( "SELECT 1
			FROM grades_completed
			WHERE STAFF_ID='" . (int) $teacher_id . "'
			AND MARKING_PERIOD_ID='" . (int) $mp_id . "'
			AND COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

		if ( ! $current_completed )
		{
			DBInsert(
				'grades_completed',
				[
					'STAFF_ID' => (int) $teacher_id,
					'MARKING_PERIOD_ID' => (int) $mp_id,
					'COURSE_PERIOD_ID' => (int) $cp_id,
				]
			);
		}

		ClassRankCalculateAddMP( $mp_id );
	}

	return true;
}


/**
 * Automatically calculate Course Period's Final Grades using Gradebook Grades
 * (Quarter or Progress Period)
 * Include Inactive Students
 *
 * @uses FinalGradesGetAssignmentsPoints()
 * @uses _makeLetterGrade()
 *
 * @since 11.8
 * @since 11.8.5 Fix Final Grade calculation when both "Weight Assignments" & "Weight Assignment Categories" checked
 *
 * @param int    $cp_id Course Period ID.
 * @param int    $mp_id Marking Period ID.
 *
 * @return array Final Grades, else empty.
 */
function FinalGradesQtrOrProCalculate( $cp_id, $mp_id )
{
	$mp = GetMP( $mp_id, 'MP' );

	if ( ! $cp_id
		|| ! in_array( $mp, [ 'QTR', 'PRO' ] ) )
	{
		return [];
	}

	// First, check Course Period exists and is graded.
	$grade_scale_id = DBGetOne( "SELECT GRADE_SCALE_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	if ( ! $grade_scale_id )
	{
		return [];
	}

	// Then, check Course Period has assignments and points.
	$points_RET = FinalGradesGetAssignmentsPoints( $cp_id, $mp_id );

	if ( ! $points_RET )
	{
		return [];
	}

	$teacher_id = DBGetOne( "SELECT TEACHER_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	$gradebook_config = ProgramUserConfig( 'Gradebook', $teacher_id );

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

	$import_RET = [];

	foreach ( (array) $points_RET as $student_id => $student )
	{
		$total = $total_percent = $total_weighted = $total_weights = 0;

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
					$total_weighted += ( ! empty( $gradebook_config['WEIGHT'] ) && $partial_points['PARTIAL_WEIGHT'] ?
						$partial_points['FINAL_GRADE_PERCENT'] *
							( $partial_points['PARTIAL_WEIGHTED_GRADE'] / $partial_points['PARTIAL_WEIGHT'] ) :
						$partial_points['PARTIAL_WEIGHTED_GRADE'] );

					$total_weights += $partial_points['PARTIAL_WEIGHT'];
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
			$total = $total_weighted / $total_weights;

			if ( ! empty( $gradebook_config['WEIGHT'] ) )
			{
				$total = $total_weighted / $total_percent;
			}
		}

		$import_RET[$student_id] = [
			1 => [
				'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total, $cp_id, 0, 'ID' ),
				'GRADE_PERCENT' => round( 100 * $total, 1 ),
			],
		];
	}

	return $import_RET;
}


/**
 * Automatically calculate Course Period's Final Grades using Gradebook Grades
 * (Semester or Full Year)
 *
 * @uses _makeLetterGrade()
 *
 * @since 11.8
 *
 * @global $warning Warning: Add "Final Grading Percentages are not configured."
 *
 * @param int    $cp_id Course Period ID.
 * @param int    $mp_id Marking Period ID.
 * @param string $mode  Mode: continue or fail. Fail: return empty if warning.
 *
 * @return array Final Grades, else empty.
 */
function FinalGradesSemOrFYCalculate( $cp_id, $mp_id, $mode = 'continue' )
{
	global $warning;

	$mp = GetMP( $mp_id, 'MP' );

	if ( ! $cp_id
		|| ! in_array( $mp, [ 'SEM', 'FY' ] ) )
	{
		return false;
	}

	if ( GetMP( $mp_id, 'MP' ) == 'SEM' )
	{
		$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,'Y' AS DOES_GRADES
		FROM school_marking_periods
		WHERE MP='QTR'
		AND PARENT_ID='" . (int) $mp_id . "'
		UNION
		SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
		FROM school_marking_periods
		WHERE MP='SEM'
		AND MARKING_PERIOD_ID='" . (int) $mp_id . "'" );
		$prefix = 'SEM-';
	}
	else
	{
		$mp_RET = DBGet( "SELECT q.MARKING_PERIOD_ID,'Y' AS DOES_GRADES
		FROM school_marking_periods q,school_marking_periods s
		WHERE q.MP='QTR'
		AND s.MP='SEM'
		AND q.PARENT_ID=s.MARKING_PERIOD_ID
		AND s.PARENT_ID='" . (int) $mp_id . "'
		UNION
		SELECT MARKING_PERIOD_ID,DOES_GRADES
		FROM school_marking_periods
		WHERE MP='SEM'
		AND PARENT_ID='" . (int) $mp_id . "'
		UNION
		SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
		FROM school_marking_periods
		WHERE MP='FY'
		AND MARKING_PERIOD_ID='" . (int) $mp_id . "'" );
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
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND MARKING_PERIOD_ID IN (" . $mps . ")", [], [ 'STUDENT_ID' ] );

	$teacher_id = DBGetOne( "SELECT TEACHER_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	$gradebook_config = ProgramUserConfig( 'Gradebook', $teacher_id );

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

	$import_RET = [];

	foreach ( (array) $percents_RET as $student_id => $percents )
	{
		$total = $total_percent = 0;

		foreach ( (array) $percents as $percent )
		{
			if ( ! isset( $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']] ) )
			{
				// @since 11.5 Add "Final Grading Percentages are not configured." warning
				$warning['config_percent'] = _( 'Final Grading Percentages are not configured.' );

				if ( AllowUse( 'Grades/Configuration.php' ) )
				{
					$warning['config_percent'] .= ' <a href="Modules.php?modname=Grades/Configuration.php">' .
						_( 'Configuration' ) . '</a>';
				}

				if ( $mode === 'fail' )
				{
					return [];
				}
			}

			$total += $percent['GRADE_PERCENT'] *
				issetVal( $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']] );

			$total_percent += $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
		}

		if ( $total_percent != 0 )
		{
			$total /= $total_percent;
		}

		$import_RET[$student_id] = [
			1 => [
				'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total / 100, $cp_id, 0, 'ID' ),
				'GRADE_PERCENT' => round( $total, 1 ),
			],
		];
	}

	return $import_RET;
}


/**
 * Get Assignments Points in order to calculate Course Period's Final Grades
 * (Quarter or Progress Period)
 *
 * @since 11.8
 * @since 11.8.5 Fix Final Grade calculation when "Weight Assignments" checked & excused
 *
 * @global $_ROSARIO['User'] if we need to impersonate Teacher (when admin & outside Teacher Programs)
 *
 * @param int $cp_id Course Period ID.
 * @param int $mp_id Marking Period ID.
 *
 * @return array Points or empty.
 */
function FinalGradesGetAssignmentsPoints( $cp_id, $mp_id )
{
	global $_ROSARIO;

	$mp = GetMP( $mp_id, 'MP' );

	if ( ! $cp_id
		|| ! in_array( $mp, [ 'QTR', 'PRO' ] ) )
	{
		return [];
	}

	$teacher_id = DBGetOne( "SELECT TEACHER_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	$gradebook_config = ProgramUserConfig( 'Gradebook', $teacher_id );

	// Note: The 'active assignment' determination is not fully correct.  It would be easy to be fully correct here but the same determination
	// as in Grades.php is used to avoid apparent inconsistencies in the grade calculations.  See also the note at top of Grades.php.
	$extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(" .
	db_case( [ 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ] ) . ") AS PARTIAL_POINTS,sum(" .
	db_case( [ 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ] ) . ") AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";

	if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
	{
		// @since 11.0 Add Weight Assignments option
		$extra['SELECT_ONLY'] .= ",sum(" . db_case( [ 'gg.POINTS', "'-1'", "'0'",
			db_case( [ 'ga.WEIGHT', "''", "'0'", "ga.WEIGHT" ] ) ] ) . ") AS PARTIAL_WEIGHT,
			sum(" . db_case( [ 'gg.POINTS', "'-1'", "'0'", '(gg.POINTS/ga.POINTS)*ga.WEIGHT' ] ) . ") AS PARTIAL_WEIGHTED_GRADE";
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

	if ( GetMP( $mp_id, 'MP' ) === 'PRO' )
	{
		// FJ: limit Assignments to the ones due during the Progress Period.
		$extra['WHERE'] .= " AND ((ga.ASSIGNED_DATE IS NULL OR (SELECT END_DATE
			FROM school_marking_periods
			WHERE MARKING_PERIOD_ID='" . (int) $mp_id . "')>=ga.ASSIGNED_DATE)
			AND (ga.DUE_DATE IS NULL
				OR (SELECT END_DATE
					FROM school_marking_periods
					WHERE MARKING_PERIOD_ID='" . (int) $mp_id . "')>=ga.DUE_DATE
				AND (SELECT START_DATE
					FROM school_marking_periods
					WHERE MARKING_PERIOD_ID='" . (int) $mp_id . "')<=ga.DUE_DATE))";
	}

	$extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";

	$extra['group'] = [ 'STUDENT_ID' ];

	$is_teacher = User( 'PROFILE' ) === 'teacher';

	if ( ! $is_teacher )
	{
		// Fix SQL error, run GetStuList() as Teacher. For example in MassCreateAssignments.php
		UserImpersonateTeacher( $teacher_id );

		$_SESSION['UserCoursePeriod'] = $cp_id;
	}

	$points_RET = GetStuList( $extra );

	if ( ! $is_teacher )
	{
		// Undo UserImpersonateTeacher().
		$_ROSARIO['User'][1] = $_ROSARIO['User'][0];

		unset( $_SESSION['UserCoursePeriod'] );
	}

	return $points_RET;
}

/**
 * Save Final Grades to database
 * Adapted for call after FinalGradesSemOrFYCalculate() or FinalGradesQtrOrProCalculate()
 * Should work even for a Course Period not in current School / Year.
 *
 * @since 11.8
 *
 * @param int   $cp_id        Course Period ID.
 * @param int   $mp_id        Marking Period ID.
 * @param array $final_grades Final Grades array, with student ID as key.
 *
 * @return bool True if saved, else false.
 */
function FinalGradesSave( $cp_id, $mp_id, $final_grades )
{
	static $course_period_RET = [];

	if ( ! $cp_id
		|| ! GetMP( $mp_id )
		|| ! $final_grades )
	{
		return false;
	}

	if ( empty( $course_period_RET[ $cp_id ] ) )
	{
		$course_period_RET[ $cp_id ] = DBGet( "SELECT SYEAR,SCHOOL_ID,MP
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );
	}

	$cp = $course_period_RET[ $cp_id ][1];

	$current_RET = DBGet( "SELECT STUDENT_ID
		FROM student_report_card_grades
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND MARKING_PERIOD_ID='" . (int) $mp_id . "'", [], [ 'STUDENT_ID' ] );

	$course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE AS COURSE_NAME,cp.TITLE,
		cp.GRADE_SCALE_ID,credit('" . (int) $cp_id . "','" . (int) $mp_id . "') AS CREDITS,
		DOES_CLASS_RANK AS CLASS_RANK,c.CREDIT_HOURS
		FROM course_periods cp,courses c
		WHERE cp.COURSE_ID=c.COURSE_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	$grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];

	$grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
		rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE,rcg.COMMENT
		FROM report_card_grades rcg, report_card_grade_scales gs
		WHERE rcg.GRADE_SCALE_ID=gs.ID
		AND rcg.SYEAR='" . $cp['SYEAR'] . "'
		AND rcg.SCHOOL_ID='" . (int) $cp['SCHOOL_ID'] . "'
		AND rcg.GRADE_SCALE_ID='" . (int) $grade_scale_id . "'
		ORDER BY rcg.BREAK_OFF IS NULL,rcg.BREAK_OFF DESC,rcg.SORT_ORDER IS NULL,rcg.SORT_ORDER", [], [ 'ID' ] );

	if ( ! $grade_scale_id )
	{
		return false;
	}

	foreach ( (array) $final_grades as $student_id => $final_grade )
	{
		if ( empty( $final_grade[1]['REPORT_CARD_GRADE_ID'] )
			|| ! isset( $final_grade[1]['GRADE_PERCENT'] ) )
		{
			continue;
		}

		$grade = $final_grade[1]['REPORT_CARD_GRADE_ID'];
		$letter = $grades_RET[$grade][1]['TITLE'];
		$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
		$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];
		$scale = $grades_RET[$grade][1]['GP_SCALE'];
		$gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];

		if ( GetMP( $mp_id, 'MP' ) === 'FY'
			&& $cp['MP'] !== 'FY' )
		{
			// Add precision to year weighted GPA if not year course period.
			$weighted = $percent / 100 * $scale;
		}

		$columns = [
			'REPORT_CARD_GRADE_ID' => $grade,
			'GRADE_PERCENT' => $final_grade[1]['GRADE_PERCENT'],
			'GRADE_LETTER' => DBEscapeString( $letter ),
			'WEIGHTED_GP' => $weighted,
			'UNWEIGHTED_GP' => $unweighted,
			'GP_SCALE' => $scale,
			'COURSE_TITLE' => DBEscapeString( $course_RET[1]['COURSE_NAME'] ),
			'CLASS_RANK' => $course_RET[1]['CLASS_RANK'],
			'CREDIT_HOURS' => $course_RET[1]['CREDIT_HOURS'],
			'CREDIT_ATTEMPTED' => $course_RET[1]['CREDITS'],
			'CREDIT_EARNED' => ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ),
		];

		$where_columns = [
			'SYEAR' => $cp['SYEAR'],
			'SCHOOL_ID' => (int) $cp['SCHOOL_ID'],
			'COURSE_PERIOD_ID' => (int) $cp_id,
			'MARKING_PERIOD_ID' => (int) $mp_id,
			'STUDENT_ID' => (int) $student_id,
		];

		DBUpsert(
			'student_report_card_grades',
			$columns,
			$where_columns,
			( empty( $current_RET[ $student_id ][1] ) ? 'insert' : 'update' )
		);
	}

	return true;
}
