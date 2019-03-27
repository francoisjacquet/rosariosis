<?php
/**
 * Discipline Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Discipline module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultDiscipline()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardDisciplineAdmin();
	}

	return DashboardModule( 'Discipline', $data );
}

if ( ! function_exists( 'DashboardDisciplineAdmin' ) )
{
	/**
	 * Dashboard data
	 * Discipline module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardDisciplineAdmin()
	{
		$referrals_nb = 0;

		$referrals_RET = DBGet( "SELECT TO_CHAR(ENTRY_DATE,'YYYY-MM') AS YEAR_MONTH,
		COUNT(ID) AS REFERRALS_NB
		FROM DISCIPLINE_REFERRALS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		GROUP BY YEAR_MONTH
		ORDER BY YEAR_MONTH DESC
		LIMIT 10" );

		if ( ! empty( $referrals_RET[1] )
			&& $referrals_RET[1]['YEAR_MONTH'] === date( 'Y-m' ) )
		{
			// Referrals this month.
			$referrals_nb = (int) $referrals_RET[1]['REFERRALS_NB'];
		}

		$referrals_data = array(
			_( 'Referrals' ) => $referrals_nb,
		);

		foreach ( (array) $referrals_RET as $referrals )
		{
			$proper_date = ProperDate( $referrals['YEAR_MONTH'] . '-28' );

			// Remove dummy day from proper date.
			$proper_month_year = str_replace( array( '/28', ' 28' ), '', $proper_date );

			// Referrals by month.
			$referrals_data[$proper_month_year] = $referrals['REFERRALS_NB'];
		}

		$data = array();

		if ( $referrals_nb
			|| count( $referrals_data ) > 1 )
		{
			$data = $referrals_data;
		}

		return $data;
	}
}
