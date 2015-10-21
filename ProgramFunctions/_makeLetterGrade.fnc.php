<?php

/**
 * Calculate letter grade from percent
 * Determine letter grade with breakoff values:
 * percent >= breakoff
 * Take in account Teacher grade scale if any (DOES_BREAKOFF)
 *
 * used in:
 * Eligibility/EnterEligibility.php
 * Grades/GradebookBreakdown.php
 * Grades/Grades.php
 * Grades/InputFinalGrades.php
 * Grades/ProgressReports.php
 * Grades/StudentGrades.php
 *
 * TODO: remove global $programconfig!
 *
 * @global array   $_ROSARIO         Sets $_ROSARIO['_makeLetterGrade']
 *
 * @param  string  $percent          precent grade
 * @param  integer $course_period_id course period ID (optional)
 * @param  integer $staff_id         staff ID (optional)
 * @param  string  $ret              returned column (optional). Defaults to 'TITLE'
 *
 * @return string                    report card letter grade
 */
function _makeLetterGrade( $percent, $course_period_id = 0, $staff_id = 0, $ret = 'TITLE' )
{
	global $programconfig,
		$_ROSARIO;

	if ( !$course_period_id )
		$course_period_id = UserCoursePeriod();

	if ( !$staff_id )
		$staff_id = User( 'STAFF_ID' );

	if ( !$programconfig[$staff_id] )
	{
		$config_RET = DBGet( DBQuery( "SELECT TITLE,VALUE
			FROM PROGRAM_USER_CONFIG
			WHERE USER_ID='" . $staff_id . "'
			AND PROGRAM='Gradebook'" ), array(), array( 'TITLE' ) );

		if ( count( $config_RET ) )
			foreach ( (array)$config_RET as $title => $value )
				$programconfig[$staff_id][$title] = $value[1]['VALUE'];
		else
			$programconfig[$staff_id] = true;
	}

	// Save courses in $_ROSARIO['_makeLetterGrade']['courses'] global var
	if ( !$_ROSARIO['_makeLetterGrade']['courses'][$course_period_id])
		$_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet( DBQuery( "SELECT DOES_BREAKOFF,GRADE_SCALE_ID
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

	$does_breakoff = $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id][1]['DOES_BREAKOFF'];

	$grade_scale_id = $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id][1]['GRADE_SCALE_ID'];

	$percent *= 100;

	// If Teacher Grade Scale
	if ( $does_breakoff == 'Y'
		&& is_array( $programconfig[$staff_id] ) )
	{
		if ( $programconfig[$staff_id]['ROUNDING'] == 'UP' )
			$percent = ceil( $percent );

		elseif ( $programconfig[$staff_id]['ROUNDING'] == 'DOWN' )
			$percent = floor( $percent );

		elseif ( $programconfig[$staff_id]['ROUNDING'] == 'NORMAL' )
			$percent = round( $percent );
	}
	else
		$percent = round( $percent ); // school default

	if ( $ret == '%' )
		return $percent;

	// Save grades in $_ROSARIO['_makeLetterGrade']['grades'] global var
	if ( !$_ROSARIO['_makeLetterGrade']['grades'][$grade_scale_id] )
		$_ROSARIO['_makeLetterGrade']['grades'][$grade_scale_id] = DBGet( DBQuery( "SELECT TITLE,ID,BREAK_OFF,COMMENT
			FROM REPORT_CARD_GRADES
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND GRADE_SCALE_ID='" . $grade_scale_id . "'
			ORDER BY BREAK_OFF IS NOT NULL DESC,BREAK_OFF DESC,SORT_ORDER" ) );

	// Fix error invalid input syntax for type numeric
	// If Teacher Grade Scale
	if ( $does_breakoff == 'Y'
		&& is_array( $programconfig[$staff_id] ) )
	{
		foreach ( (array)$_ROSARIO['_makeLetterGrade']['grades'][$grade_scale_id] as $grade )
		{
			if ( is_numeric($programconfig[$staff_id][$course_period_id.'-'.$grade['ID']])
				&& $percent >= $programconfig[$staff_id][$course_period_id . '-' . $grade['ID']] )
				//FJ use Report Card Grades comments
				//return $ret=='ID' ? $grade['ID'] : $grade['TITLE'];
				return $grade[$ret];
		}
	}

	foreach ( (array)$_ROSARIO['_makeLetterGrade']['grades'][$grade_scale_id] as $grade )
	{
		if ( $percent >= $grade['BREAK_OFF'] )
			//FJ use Report Card Grades comments
			//return $ret=='ID' ? $grade['ID'] : $grade['TITLE'];
			return $grade[$ret];
	}
}
