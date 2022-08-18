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
	 * @since 9.3 SQL use CAST(X AS char(X)) instead of to_char() for MySQL compatibility
	 *
	 * @return array Dashboard data
	 */
	function DashboardDisciplineAdmin()
	{
		$referrals_nb = 0;

		$referrals_RET = DBGet( "SELECT CAST(ENTRY_DATE AS char(7)) AS YEAR_MONTH_DATE,
		COUNT(ID) AS REFERRALS_NB
		FROM discipline_referrals
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		GROUP BY YEAR_MONTH_DATE
		ORDER BY YEAR_MONTH_DATE DESC
		LIMIT 10" );

		if ( ! empty( $referrals_RET[1] )
			&& $referrals_RET[1]['YEAR_MONTH_DATE'] === date( 'Y-m' ) )
		{
			// Referrals this month.
			$referrals_nb = (int) $referrals_RET[1]['REFERRALS_NB'];
		}

		$referrals_data = [
			_( 'Referrals' ) => $referrals_nb,
		];

		foreach ( (array) $referrals_RET as $referrals )
		{
			// Remove dummy day from proper date.
			// @since 9.0 Fix PHP8.1 deprecated strftime() use strftime_compat() instead
			$proper_month_year = ucfirst( strftime_compat(
				trim( str_replace( [ '%d', '//' ], [ '', '/'], Preferences( 'DATE' ) ), '-./ ' ),
				strtotime( $referrals['YEAR_MONTH_DATE'] . '-28' )
			) );

			// Referrals by month.
			$referrals_data[$proper_month_year] = $referrals['REFERRALS_NB'];
		}

		$data = [];

		if ( $referrals_nb
			|| count( $referrals_data ) > 1 )
		{
			$data = $referrals_data;
		}

		return $data;
	}
}
