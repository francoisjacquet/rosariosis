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
		$absences_RET = DBGet( "SELECT ad.SCHOOL_DATE,
		SUM(CASE WHEN ad.STATE_VALUE='0.0' THEN 1 END) AS ABSENT,
		SUM(CASE WHEN ad.STATE_VALUE='0.5' THEN 1 END) AS HALF_DAY
		FROM attendance_day ad,student_enrollment ssm
		WHERE ad.SYEAR='" . UserSyear() . "'
		AND ad.SYEAR=ssm.SYEAR
		AND ad.STUDENT_ID=ssm.STUDENT_ID
		AND ssm.SCHOOL_ID='" . UserSchool() . "'
		AND ad.SCHOOL_DATE<=CURRENT_DATE
		AND (ad.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE
			OR (ssm.END_DATE IS NULL AND ssm.START_DATE<=ad.SCHOOL_DATE))
		GROUP BY ad.SCHOOL_DATE
		ORDER BY ad.SCHOOL_DATE DESC
		LIMIT 7" );

		if ( ! empty( $absences_RET[1] )
			&& $absences_RET[1]['SCHOOL_DATE'] === DBDate() )
		{
			// Absences today.
			$absences_today = (int) $absences_RET[1]['ABSENT'];

			if ( $absences_RET[1]['HALF_DAY'] )
			{
				$absences_today .= ' <span class="size-1">&mdash; ' . _( 'Half Day' ) . ' ' . (int) $absences_RET[1]['HALF_DAY'] . '</span>';
			}
		}

		$absences_data = [
			_( 'Absences' ) => $absences_today,
		];

		foreach ( (array) $absences_RET as $absences )
		{
			$proper_date = ProperDate( $absences['SCHOOL_DATE'], 'short' );

			// Referrals by month.
			$absences_data[$proper_date] = (int) $absences['ABSENT'];

			if ( $absences['HALF_DAY'] )
			{
				$absences_data[$proper_date] .= ' <span class="size-1">&mdash; ' . _( 'Half Day' ) . ' ' . (int) $absences['HALF_DAY'] . '</span>';
			}
		}

		$data = [];

		if ( $absences_today
			|| count( $absences_data ) > 1 )
		{
			$data = $absences_data;
		}

		return $data;
	}
}
