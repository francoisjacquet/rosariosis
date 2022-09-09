<?php
/**
 * Grades functions
 */

/**
 * Get GPA or Total row
 * Used by TranscriptsGenerate() & ReportCardsGenerate().
 *
 * @example $grades_RET[$i + 1] = GetGpaOrTotalRow( $student_id, $grades_total, $i, $show['grades_or_total'] );
 *
 * @since 5.0 Add GPA or Total.
 *
 * @param int   $student_id     Student ID.
 * @param array $grades_total   Grades total points for each MP.
 * @param int   $courses_number Number of courses (rows).
 * @param bool  $percent        Show Percent grade.
 *
 * @return array GPA or Total row.
 */
function GetGpaOrTotalRow( $student_id, $grades_total, $course_number, $mode = 'gpa' )
{
	if ( ! is_array( $grades_total ) )
	{
		return [];
	}

	$gpa_row = [
		'COURSE_TITLE' => ( $mode === 'total' ? _( 'Total' ) : _( 'GPA' ) ),
		'COURSE_PERIOD_ID' => '-1',
	];

	foreach ( (array) $grades_total as $mp => $grades_total_mp )
	{
		if ( $mode === 'total' )
		{
			$gpa_row[$mp] = '<B>' . $grades_total_mp . '</B>';
		}
		else
		{
			$cumulative_gpa = DBGetOne( "SELECT CUM_WEIGHTED_GPA
				FROM transcript_grades
				WHERE STUDENT_ID='" . (int) $student_id . "'
				AND MARKING_PERIOD_ID='" . (int) $mp . "'" );

			$gpa_row[$mp] = '<B>' . number_format( $cumulative_gpa, 2, '.', '' ) . '</B> /' .
				(float) SchoolInfo( 'REPORTING_GP_SCALE' );
		}

		if ( ! empty( $_REQUEST['elements']['minmax_grades'] ) )
		{
			$gpa_row[$mp] = '<div class="center">' . $gpa_row[$mp] . '</div>';
		}
	}

	return $gpa_row;
}


/**
 * Get Class Rank row
 * Used by ReportCardsGenerate().
 *
 * @example $grades_RET[$i + 2] = GetClassRankRow( $student_id, $mp_list );
 *
 * @since 8.0 Add Class Rank row.
 *
 * @param int   $student_id Student ID.
 * @param array $mp_array   Marking Periods.
 *
 * @return array Class Rank row.
 */
function GetClassRankRow( $student_id, $mp_array )
{
	$mp_list = "'" . implode( "','", $mp_array ) . "'";

	$class_rank_RET = DBGet( "SELECT MARKING_PERIOD_ID,
		CLASS_SIZE,CUM_RANK
		FROM transcript_grades
		WHERE SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID IN(" . $mp_list . ")
		AND STUDENT_ID='" . (int) $student_id . "'" );

	$class_rank_row = [
		'COURSE_PERIOD_ID' => '-2',
		'COURSE_TITLE' => _( 'Class Rank' ),
	];

	foreach ( $class_rank_RET as $class_rank )
	{
		if ( ! $class_rank['CUM_RANK'] )
		{
			continue;
		}

		$mp_id = $class_rank['MARKING_PERIOD_ID'];

		$class_rank_row[ $mp_id ] = $class_rank['CUM_RANK'] . ' / ' . $class_rank['CLASS_SIZE'];

		if ( ! empty( $_REQUEST['elements']['minmax_grades'] ) )
		{
			$class_rank_row[ $mp_id ] = '<div class="center">' . $class_rank_row[ $mp_id ] . '</div>';
		}
	}

	return $class_rank_row;
}

/**
 * Get Class average
 * String formatted for display
 *
 * @since 9.1
 *
 * @uses GetClassAveragePercent()
 * @uses _makeLetterGrade()
 *
 * @example GetClassAverage( $course_period_id, $_REQUEST['mp'], ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) )
 *
 * @param int $course_period_id  Course Period ID.
 * @param int $marking_period_id Marking Period ID.
 * @param int $letter_or_percent Letter or percent or both grades.
 *
 * @return string Empty if no grades yet, else formatted average HTML (letter grade in bold followed by percent grade).
 */
function GetClassAverage( $course_period_id, $marking_period_id, $letter_or_percent = 0 )
{
	$percent_average = GetClassAveragePercent( $course_period_id, $marking_period_id );

	if ( ! $percent_average )
	{
		return '';
	}

	$percent = number_format( $percent_average, 1, '.', '' ) . '%';

	if ( $letter_or_percent > 0 )
	{
		return $percent;
	}

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

	$grade = _makeLetterGrade( ( $percent_average / 100 ), $course_period_id );

	if ( $letter_or_percent < 0 )
	{
		return $grade;
	}

	return '<b>' . $grade . '</b> ' . $percent;
}

/**
 * Get Class average percent
 * Get raw percent value
 *
 * @since 9.1
 *
 * @param int $course_period_id  Course Period ID.
 * @param int $marking_period_id Marking Period ID.
 *
 * @return float Percent average.
 */
function GetClassAveragePercent( $course_period_id, $marking_period_id )
{
	$extra['SELECT_ONLY'] = "sg1.GRADE_PERCENT";

	$extra['FROM'] = ",student_report_card_grades sg1,course_periods rc_cp";

	$extra['WHERE'] = " AND sg1.MARKING_PERIOD_ID='" . (int) $marking_period_id . "'
		AND rc_cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
		AND sg1.STUDENT_ID=ssm.STUDENT_ID";

	$students_RET = GetStuList( $extra );

	if ( ! $students_RET )
	{
		return 0.0;
	}

	$total_percent = $grades_i = 0;

	foreach ( $students_RET as $grade )
	{
		$grades_i++;

		$total_percent += $grade['GRADE_PERCENT'];
	}

	$percent_average = $total_percent / $grades_i;

	return $percent_average;
}
