<?php
/**
 * Eligibility Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Eligibility module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultEligibility()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardEligibilityAdmin();
	}

	return DashboardModule( 'Eligibility', $data );
}

if ( ! function_exists( 'DashboardEligibilityAdmin' ) )
{
	/**
	 * Dashboard data
	 * Eligibility module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardEligibilityAdmin()
	{
		$activities_nb = DBGetOne( "SELECT COUNT(ID) AS ACTIVITIES_NB
		FROM eligibility_activities
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND START_DATE<=CURRENT_DATE
		AND END_DATE>=CURRENT_DATE" );

		$data = [
			_( 'Activities' ) => $activities_nb,
		];

		// Activities this week.
		$activities_nb = (int) $activities_nb;

		if ( ! $activities_nb )
		{
			return [];
		}

		$activity_students_RET = DBGet( "SELECT TITLE,
		COUNT(sea.STUDENT_ID) AS STUDENTS_NB
		FROM eligibility_activities ea, student_eligibility_activities sea
		WHERE ea.SYEAR='" . UserSyear() . "'
		AND ea.SCHOOL_ID='" . UserSchool() . "'
		AND ea.START_DATE<=CURRENT_DATE
		AND ea.END_DATE>=CURRENT_DATE
		AND sea.SYEAR=ea.SYEAR
		AND ea.ID=sea.ACTIVITY_ID
		GROUP BY ea.TITLE
		ORDER BY STUDENTS_NB
		LIMIT 10" );

		foreach ( (array) $activity_students_RET as $activity )
		{
			$data[$activity['TITLE']] = sprintf(
				'%d %s',
				$activity['STUDENTS_NB'],
				ngettext( 'Student', 'Students', $activity['STUDENTS_NB'] )
			);
		}

		return $data;
	}
}
