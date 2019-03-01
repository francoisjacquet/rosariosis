<?php
/**
 * Attendance Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Attendance module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultAttendance()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardAttendanceAdmin();
	}

	return DashboardModule( 'Attendance', $data );
}

if ( ! function_exists( 'DashboardAttendanceAdmin' ) )
{
	/**
	 * Dashboard data
	 * Attendance module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardAttendanceAdmin()
	{
		$absences_today = 0;

		// Absences by day.
		$absences_RET = DBGet( "SELECT SCHOOL_DATE,
		SUM(CASE WHEN STATE_VALUE='0.0' THEN 1 END) AS ABSENT,
		SUM(CASE WHEN STATE_VALUE='0.5' THEN 1 END) AS HALF_DAY
		FROM ATTENDANCE_DAY ad,STUDENT_ENROLLMENT ssm
		WHERE ad.SYEAR='" . UserSyear() . "'
		AND ad.SYEAR=ssm.SYEAR
		AND ad.STUDENT_ID=ssm.STUDENT_ID
		AND ssm.SCHOOL_ID='" . UserSchool() . "'
		AND SCHOOL_DATE>=ssm.START_DATE
		AND ssm.END_DATE IS NULL OR SCHOOL_DATE<=ssm.END_DATE
		GROUP BY SCHOOL_DATE
		ORDER BY SCHOOL_DATE DESC
		LIMIT 7" );

		if ( ! empty( $absences_RET[1] )
			&& $absences_RET[1]['SCHOOL_DATE'] === DBDate() )
		{
			// Absences today.
			$absences_today = (int) $absences_RET[1]['ABSENT'];

			if ( $absences_RET[1]['HALF_DAY'] )
			{
				$absences_today .= ' &mdash; ' . _( 'Half Day' ) . ' ' . (int) $absences_RET[1]['HALF_DAY'];
			}
		}

		$absences_data = array(
			_( 'Absences' ) => $absences_today,
		);

		foreach ( (array) $absences_RET as $absences )
		{
			$proper_date = ProperDate( $absences['SCHOOL_DATE'] );

			// Referrals by month.
			$absences_data[$proper_date] = (int) $absences['ABSENT'];

			if ( $absences['HALF_DAY'] )
			{
				$absences_data[$proper_date] .= _( 'Half Day' ) . (int) $absences['HALF_DAY'];
			}
		}

		$data = array();

		if ( $absences_today
			|| count( $absences_data ) > 1 )
		{
			$data = $absences_data;
		}

		return $data;
	}
}
