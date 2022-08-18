<?php
/**
 * Student Billing Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Student Billing module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultStudentBilling()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardStudentBillingAdmin();
	}

	return DashboardModule( 'Student_Billing', $data );
}

if ( ! function_exists( 'DashboardStudentBillingAdmin' ) )
{
	/**
	 * Dashboard data
	 * Student Billing module & admin profile
	 *
	 * @since 4.0
	 * @since 9.3 SQL use CAST(X AS char(X)) instead of to_char() for MySQL compatibility
	 *
	 * @return array Dashboard data
	 */
	function DashboardStudentBillingAdmin()
	{
		$balance = 0;

		// Limit Results to Months between User MP Start & End Date.
		$fees_RET = DBGet( "SELECT CAST(ASSIGNED_DATE AS char(7)) AS YEAR_MONTH_DATE,
			SUM(AMOUNT) AS TOTAL_FEES
			FROM billing_fees
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH_DATE
			ORDER BY YEAR_MONTH_DATE DESC
			LIMIT 3", [], [ 'YEAR_MONTH_DATE' ] );

		$payments_RET = DBGet( "SELECT CAST(PAYMENT_DATE AS char(7)) AS YEAR_MONTH_DATE,
			SUM(AMOUNT) AS TOTAL_PAYMENTS
			FROM billing_payments
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH_DATE
			ORDER BY YEAR_MONTH_DATE DESC
			LIMIT 3", [], [ 'YEAR_MONTH_DATE' ] );

		$fees_payments_RET = array_replace_recursive( $fees_RET, $payments_RET );

		krsort( $fees_payments_RET );

		if ( ! empty( $fees_payments_RET[date( 'Y-m' )] ) )
		{
			$fees_payments_month = $fees_payments_RET[date( 'Y-m' )][1];

			// Student Billing Balance.

			if ( ! empty( $fees_payments_month['TOTAL_FEES'] ) )
			{
				$balance = $fees_payments_month['TOTAL_FEES'];
			}

			if ( ! empty( $fees_payments_month['TOTAL_PAYMENTS'] ) )
			{
				$balance -= $fees_payments_month['TOTAL_PAYMENTS'];
			}
		}

		$billing_data[_( 'Balance' )] = Currency( $balance, 'CR' );

		foreach ( (array) $fees_payments_RET as $year_month => $fees_payments )
		{
			// Remove dummy day from proper date.
			// @since 9.0 Fix PHP8.1 deprecated strftime() use strftime_compat() instead
			$proper_month_year = ucfirst( strftime_compat(
				trim( str_replace( [ '%d', '//' ], [ '', '/'], Preferences( 'DATE' ) ), '-./ ' ),
				strtotime( $year_month . '-28' )
			) );

			$month_balance = 0;

			$billing_data_month = null;

			if ( ! empty( $fees_payments[1]['TOTAL_FEES'] ) )
			{
				$month_balance = $fees_payments[1]['TOTAL_FEES'];

				// Fees by month.
				$billing_data_month .= NoInput(
					Currency( $fees_payments[1]['TOTAL_FEES'] ),
					_( 'Fees' )
				) . '<br />';
			}

			if ( ! empty( $fees_payments[1]['TOTAL_PAYMENTS'] ) )
			{
				$month_balance -= $fees_payments[1]['TOTAL_PAYMENTS'];

				// Payments by month.
				$billing_data_month .= NoInput(
					Currency( $fees_payments[1]['TOTAL_PAYMENTS'] ),
					_( 'Payments' )
				) . '<br />';
			}

			// Month Balance.
			$month_key = NoInput( Currency( $month_balance, 'CR' ), $proper_month_year );

			// Remove last <br />.
			$billing_data[$month_key] = mb_substr( $billing_data_month, 0, ( mb_strlen( $billing_data_month ) - 6 ) );
		}

		$data = [];

		if ( $balance
			|| count( $billing_data ) > 1 )
		{
			$data = $billing_data;
		}

		return $data;
	}
}
