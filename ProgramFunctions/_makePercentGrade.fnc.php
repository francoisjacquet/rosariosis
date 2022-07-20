<?php
/**
 * Make Percent Grade function
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Make Percent Grade
 * From Grade ID or TITLE:
 * = (Grade breakoff + Previous Grade breakoff) / 2
 *
 * @since 5.5 Use Grade Scale value if Teacher Grade Scale Breakoff value is not set.
 *
 * @example _makePercentGrade( $grade[1]['REPORT_CARD_GRADE_ID'], $course_period_id )
 *
 * @global $_ROSARIO uses $_ROSARIO['_makeLetterGrade']
 * @see _makeLetterGrade()
 *
 * @param  string  $grade_id_or_title Grade ID or TITLE.
 * @param  integer $course_period_id  Course Period ID (optional). Defaults to UserCoursePeriod().
 * @param  integer $staff_id          Staff ID (optional). Defaults to User( 'STAFF_ID' ).
 *
 * @return float Percent Grade or 0 if not found.
 */
function _makePercentGrade( $grade_id_or_title, $course_period_id = 0, $staff_id = 0 )
{
	global $_ROSARIO;

	if ( ! $grade_id_or_title )
	{
		return 0;
	}

	$course_period_id = $course_period_id ? $course_period_id : UserCoursePeriod();

	$staff_id = $staff_id ? $staff_id : User( 'STAFF_ID' );

	$gradebook_config = ProgramUserConfig( 'Gradebook', $staff_id );

	if ( ! isset( $_ROSARIO['_makeLetterGrade']['courses'][ $course_period_id ] ) )
	{
		$_ROSARIO['_makeLetterGrade']['courses'][ $course_period_id ] = DBGet( "SELECT DOES_BREAKOFF,GRADE_SCALE_ID
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );
	}

	$does_breakoff = $_ROSARIO['_makeLetterGrade']['courses'][ $course_period_id ][1]['DOES_BREAKOFF'];

	$grade_scale_id = $_ROSARIO['_makeLetterGrade']['courses'][ $course_period_id ][1]['GRADE_SCALE_ID'];

	if ( ! isset( $_ROSARIO['_makeLetterGrade']['grades'][ $grade_scale_id ] ) )
	{
		$_ROSARIO['_makeLetterGrade']['grades'][ $grade_scale_id ] = DBGet( "SELECT TITLE,ID,BREAK_OFF
			FROM report_card_grades
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND GRADE_SCALE_ID='" . (int) $grade_scale_id . "'
			ORDER BY BREAK_OFF IS NOT NULL DESC,BREAK_OFF DESC,SORT_ORDER IS NULL,SORT_ORDER" );
	}
	//$grades = array('A+','A','A-','B+','B','B-','C+','C','C-','D+','D','D-','F');

	foreach ( (array) $_ROSARIO['_makeLetterGrade']['grades'][ $grade_scale_id ] as $grade )
	{
		$prev = issetVal( $crnt, 0 );

		$crnt = ( $does_breakoff === 'Y'
			// Use Grade Scale value if Teacher Grade Scale Breakoff value is not set.
			&& isset( $gradebook_config[ $course_period_id . '-' . $grade['ID'] ] ) ?
			$gradebook_config[ $course_period_id . '-' . $grade['ID'] ] :
			$grade['BREAK_OFF'] );

		if ( is_numeric( $grade_id_or_title ) ?
				$grade_id_or_title == $grade['ID'] :
				mb_strtoupper( $grade_id_or_title ) == mb_strtoupper( $grade['TITLE'] ) )
		{
			return ( $crnt + ( $crnt > $prev ? 100 : $prev ) ) / 2;
		}
	}

	return 0;
}
