<?php
/**
 * Courses functions
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Check for Course Period Teacher conflict
 *
 * @since 4.8
 *
 * @param int $teacher_id Teacher ID.
 *
 * @return boolean True if confliciting days for the same period, else false.
 */
function CoursePeriodTeacherConflictCheck( $teacher_id )
{
	if ( ! $teacher_id )
	{
		return false;
	}

	// Get school periods for Teacher course periods.
	$school_periods_RET = DBGet( "SELECT cpsp.PERIOD_ID,cpsp.DAYS
		FROM COURSE_PERIOD_SCHOOL_PERIODS cpsp,COURSE_PERIODS cp
		WHERE cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND TEACHER_ID='" . $teacher_id . "'" );

	if ( empty( $school_periods_RET )
		|| count( $school_periods_RET ) < 2 )
	{
		return false;
	}

	$school_periods = array();

	foreach ( (array) $school_periods_RET as $school_period )
	{
		if ( isset( $school_periods[ $school_period['PERIOD_ID'] ] ) )
		{
			$days_array = str_split( $school_periods[ $school_period['PERIOD_ID'] ] );

			$days_array2 = str_split( $school_period['DAYS'] );

			$common_days = array_intersect( $days_array, $days_array2 );

			if ( $common_days )
			{
				return true;
			}
		}
		else
		{
			$school_periods[ $school_period['PERIOD_ID'] ] = '';
		}

		$school_periods[ $school_period['PERIOD_ID'] ] .= $school_period['DAYS'];
	}

	return false;
}
