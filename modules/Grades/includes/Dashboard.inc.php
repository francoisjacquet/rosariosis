<?php
/**
 * Grades Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Grades module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultGrades()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardGradesAdmin();
	}

	return DashboardModule( 'Grades', $data );
}

if ( ! function_exists( 'DashboardGradesAdmin' ) )
{
	/**
	 * Dashboard data
	 * Grades module & admin profile
	 *
	 * You have to Caluclate GPA for the Quarter first!
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardGradesAdmin()
	{
		$gpa_RET = DBGet( DBQuery( "SELECT ROUND(AVG(CUM_WEIGHTED_GPA)) AS CUM_WEIGHTED_GPA,
		ROUND(AVG(UNWEIGHTED_GPA)) AS CUM_UNWEIGHTED_GPA
		FROM TRANSCRIPT_GRADES
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND MARKING_PERIOD_ID='" . UserMP() . "'" ) );

		// GPA for MP, if graded.
		$gpa = 0;

		if ( ! isset( $gpa_RET[1]['CUM_WEIGHTED_GPA'] ) )
		{
			return array();
		}

		$gpa = $gpa_RET[1]['CUM_WEIGHTED_GPA'];

		$label = _( 'GPA' ) . ' &mdash; ' . GetMP( UserMP(), 'SHORT_NAME' );

		$gpa_data = array(
			$label => ( $gpa ? number_format( $gpa, 2 ) : _( 'N/A' ) ),
		);

		$gpa_gradelevel_RET = DBGet( DBQuery( "SELECT ROUND(AVG(CUM_WEIGHTED_GPA)) AS CUM_WEIGHTED_GPA,
		ROUND(AVG(UNWEIGHTED_GPA)) AS CUM_UNWEIGHTED_GPA,
		GRADE_LEVEL_SHORT
		FROM TRANSCRIPT_GRADES
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND MARKING_PERIOD_ID='" . UserMP() . "'
		GROUP BY GRADE_LEVEL_SHORT" ), array(), array( 'GRADE_LEVEL_SHORT' ) );

		foreach ( (array) $gpa_gradelevel_RET as $gradelevel => $gpa_gradelevel )
		{
			if ( empty( $gpa_gradelevel[1]['CUM_WEIGHTED_GPA'] ) )
			{
				continue;
			}

			// GPA detail by Grade Level.
			$gpa_data[ $gradelevel ] = number_format( $gpa_gradelevel[1]['CUM_WEIGHTED_GPA'], 2 );
		}

		if ( ! $gpa
			&& ! $gpa_data )
		{
			return array();
		}

		return $gpa_data;
	}
}
