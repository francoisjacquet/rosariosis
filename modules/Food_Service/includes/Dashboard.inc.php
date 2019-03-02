<?php
/**
 * Food Service Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Food Service module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultFoodService()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardFoodServiceAdmin();
	}

	return DashboardModule( 'Food_Service', $data );
}

if ( ! function_exists( 'DashboardFoodServiceAdmin' ) )
{
	/**
	 * Dashboard data
	 * Food Service module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardFoodServiceAdmin()
	{
		$meals_today = 0;

		// Meals served.
		$meals_RET = DBGet( "SELECT
			COUNT(DISTINCT STUDENT_ID) AS PARTICIPATED,
			TO_CHAR(" . DBEscapeIdentifier( 'TIMESTAMP' ) . ",'YYYY-MM-DD') AS TRANSACTION_DATE
			FROM FOOD_SERVICE_TRANSACTIONS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY TRANSACTION_DATE,STUDENT_ID
			ORDER BY TRANSACTION_DATE DESC
			LIMIT 7", array(), array( 'TRANSACTION_DATE' ) );

		$meals_staff_RET = DBGet( "SELECT
			COUNT(DISTINCT STAFF_ID) AS PARTICIPATED,
			TO_CHAR(" . DBEscapeIdentifier( 'TIMESTAMP' ) . ",'YYYY-MM-DD') AS TRANSACTION_DATE
			FROM FOOD_SERVICE_STAFF_TRANSACTIONS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY TRANSACTION_DATE,STAFF_ID
			ORDER BY TRANSACTION_DATE DESC
			LIMIT 7", array(), array( 'TRANSACTION_DATE' ) );

		if ( ! empty( $meals_RET[date( 'Y-m-d' )] ) )
		{
			// Meals today.
			$meals_today = (int) $meals_RET[date( 'Y-m-d' )][1]['PARTICIPATED'];

			if ( ! empty( $meals_staff_RET[date( 'Y-m-d' )] ) )
			{
				$meals_today += (int) $meals_staff_RET[date( 'Y-m-d' )][1]['PARTICIPATED'];
			}
		}

		$meals_data = array(
			_( 'Participated' ) => $meals_today,
		);

		foreach ( (array) $meals_RET as $transaction_date => $meals )
		{
			$proper_date = ProperDate( $transaction_date );

			// Meals by day.
			$meals_data[$proper_date] = $meals[1]['PARTICIPATED'];

			if ( ! empty( $meals_staff_RET[$transaction_date] ) )
			{
				$staff_particpated = $meals_staff_RET[$transaction_date][1]['PARTICIPATED'];

				$meals_data[$proper_date] .= ' + ' . sprintf(
					'%d %s',
					$staff_particpated,
					ngettext( 'User', 'Users', $staff_particpated )
				);
			}
		}

		$data = array();

		if ( $meals_today
			|| count( $meals_data ) > 1 )
		{
			$data = $meals_data;
		}

		return $data;
	}
}
