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
		'COURSE_PERIOD_ID' => ( $mode === 'total' ? '-2' : '-1' ),
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
 * @example $grades_RET[$i + 4] = GetClassRankRow( $student_id, $mp_array );
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
	if ( ! $mp_array )
	{
		return [];
	}

	$mp_list = "'" . implode( "','", $mp_array ) . "'";

	$class_rank_RET = DBGet( "SELECT MARKING_PERIOD_ID,
		CLASS_SIZE,CUM_RANK
		FROM transcript_grades
		WHERE SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID IN(" . $mp_list . ")
		AND STUDENT_ID='" . (int) $student_id . "'" );

	$class_rank_row = [
		'COURSE_PERIOD_ID' => '-4',
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
 * Get Class Average row
 * Used by ReportCardsGenerate().
 *
 * @since 10.7 Add Class Average row.
 *
 * @example $grades_RET[$i + 3] = GetClassAverageRow( $course_periods );
 *
 * @uses GetClassAveragePercent()
 *
 *
 * @param array $course_periods Course Periods array, with MPs array.
 *
 * @return array Class Rank row.
 */
function GetClassAverageRow( $course_periods )
{
	static $class_averages = [];

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

	$class_average_row = [
		'COURSE_PERIOD_ID' => '-3',
		'COURSE_TITLE' => _( 'Class average' ),
	];

	if ( ! isset( $class_averages[$cp_list][$mp_list] ) )
	{
		$credits = $class_average = [];

		foreach ( (array) $course_periods as $course_period_id => $mps )
		{
			foreach ( (array) $mps as $mp )
			{
				$mp_id = $mp[1]['MARKING_PERIOD_ID'];

				if ( ! isset( $class_average[ $mp_id ] ) )
				{
					$class_average[ $mp_id ] = $credits[ $mp_id ] = 0;
				}

				$cp_credits = DBGetOne( "SELECT CREDITS
					FROM course_periods
					WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

				$class_average[ $mp_id ] += GetClassAveragePercent( $course_period_id, $mp_id ) * $cp_credits;

				$credits[ $mp_id ] += $cp_credits;
			}
		}

		foreach ( $class_average as $mp_id => $class_av )
		{
			$class_av = $class_av / $credits[ $mp_id ];

			$class_av = ( $class_av / 100 ) * SchoolInfo( 'REPORTING_GP_SCALE' );

			$class_av = '<B>' . number_format( $class_av, 2, '.', '' ) . '</B> /' .
				(float) SchoolInfo( 'REPORTING_GP_SCALE' );

			$class_average_row[ $mp_id ] = $class_av;

			if ( ! empty( $_REQUEST['elements']['minmax_grades'] ) )
			{
				$class_average_row[ $mp_id ] = '<div class="center">' . $class_average_row[ $mp_id ] . '</div>';
			}
		}

		$class_averages[$cp_list][$mp_list] = $class_average_row;
	}

	return $class_averages[$cp_list][$mp_list];
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
 * @since 11.0 Cache Class average percent
 *
 * @param int $course_period_id  Course Period ID.
 * @param int $marking_period_id Marking Period ID.
 *
 * @return float Percent average.
 */
function GetClassAveragePercent( $course_period_id, $marking_period_id )
{
	static $percent_averages = [];

	if ( isset( $percent_averages[ $course_period_id ][ $marking_period_id ] ) )
	{
		return $percent_averages[ $course_period_id ][ $marking_period_id ];
	}

	$extra['SELECT_ONLY'] = "sg1.GRADE_PERCENT";

	$extra['FROM'] = ",student_report_card_grades sg1,course_periods rc_cp";

	$extra['WHERE'] = " AND sg1.MARKING_PERIOD_ID='" . (int) $marking_period_id . "'
		AND rc_cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID
		AND sg1.STUDENT_ID=ssm.STUDENT_ID
		AND sg1.GRADE_PERCENT IS NOT NULL";

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

	$percent_averages[ $course_period_id ][ $marking_period_id ] = $percent_average;

	return $percent_average;
}


/**
 * Get Class Rank (for Course Period)
 * Used by ReportCardsGenerate().
 *
 * @example $grades_RET[$i][$mp] .= '<br />' . GetClassRank( $student_id, $course_period_id, $mp );
 *
 * @since 11.0
 *
 * @param int   $student_id Student ID.
 * @param int   $course_period_id  Course Period ID.
 * @param int   $marking_period_id Marking Period ID.
 * @param bool  $add_class_size    Display Class Size. Optional, defaults to true.
 *
 * @return string Class Rank.
 */
function GetClassRank( $student_id, $course_period_id, $marking_period_id, $add_class_size = true )
{
	$class_rank_RET = DBGet( "SELECT sg.STUDENT_ID,sg.COURSE_PERIOD_ID,mp.MARKING_PERIOD_ID,
	(SELECT COUNT(*)+1
		FROM student_report_card_grades sg2
		WHERE sg2.GRADE_PERCENT>sg.GRADE_PERCENT
		AND sg2.MARKING_PERIOD_ID=mp.MARKING_PERIOD_ID
		AND sg2.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
		AND sg2.STUDENT_ID IN (SELECT DISTINCT sg3.STUDENT_ID
			FROM student_report_card_grades sg3,student_enrollment se2
			WHERE sg3.STUDENT_ID=se2.STUDENT_ID
			AND sg3.MARKING_PERIOD_ID=mp.MARKING_PERIOD_ID
			AND sg3.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
			AND sg3.SYEAR=mp.SYEAR)) AS CLASS_RANK,
	(SELECT COUNT(*)
		FROM student_report_card_grades sg4
		WHERE sg4.MARKING_PERIOD_ID=mp.MARKING_PERIOD_ID
		AND sg4.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
		AND sg4.STUDENT_ID IN (SELECT DISTINCT sg5.STUDENT_ID
			FROM student_report_card_grades sg5,student_enrollment se3
			WHERE sg5.STUDENT_ID=se3.STUDENT_ID
			AND sg5.MARKING_PERIOD_ID=mp.MARKING_PERIOD_ID
			AND sg5.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
			AND sg5.SYEAR=mp.SYEAR)) AS CLASS_SIZE
	FROM student_enrollment se,student_report_card_grades sg,marking_periods mp
	WHERE se.STUDENT_ID=sg.STUDENT_ID
	AND se.STUDENT_ID='" . (int) $student_id . "'
	AND sg.MARKING_PERIOD_ID=mp.MARKING_PERIOD_ID
	AND mp.MARKING_PERIOD_ID='" . (int) $marking_period_id . "'
	AND sg.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
	AND se.SYEAR=mp.SYEAR
	AND sg.SYEAR=mp.SYEAR
	AND NOT sg.GRADE_PERCENT IS NULL" );

	if ( ! $class_rank_RET )
	{
		return '';
	}

	$class_rank = $class_rank_RET[1]['CLASS_RANK'];

	if ( $add_class_size )
	{
		$class_rank .= ' / ' . $class_rank_RET[1]['CLASS_SIZE'];
	}

	return $class_rank;
}
