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
		return array();
	}

	$gpa_row = array(
		'COURSE_TITLE' => ( $mode === 'total' ? _( 'Total' ) : _( 'GPA' ) ),
	);

	foreach ( (array) $grades_total as $mp => $grades_total_mp )
	{
		if ( $mode === 'total' )
		{
			$gpa_row[$mp] = '<B>' . $grades_total_mp . '</B>';
		}
		else
		{
			$cumulative_gpa = DBGetOne( "SELECT CUM_WEIGHTED_GPA
				FROM TRANSCRIPT_GRADES
				WHERE STUDENT_ID='" . $student_id . "'
				AND MARKING_PERIOD_ID='" . $mp . "'" );

			$gpa_row[$mp] = '<B>' . number_format( $cumulative_gpa, 2 ) . '</B> / ' .
				(float) SchoolInfo( 'REPORTING_GP_SCALE' );
		}

		if ( ! empty( $_REQUEST['elements']['minmax_grades'] ) )
		{
			$gpa_row[$mp] = '<div style="text-align: center;">' . $gpa_row[$mp] . '</div>';
		}
	}

	return $gpa_row;
}
