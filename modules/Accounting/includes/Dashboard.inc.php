<?php
/**
 * Accounting Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Accounting module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultAccounting()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardAccountingAdmin();
	}

	return DashboardModule( 'Accounting', $data );
}

if ( ! function_exists( 'DashboardAccountingAdmin' ) )
{
	/**
	 * Dashboard data
	 * Accounting module & admin profile
	 *
	 * @since 4.0
	 * @since 9.3 SQL use CAST(X AS char(X)) instead of to_char() for MySQL compatibility
	 *
	 * @return array Dashboard data
	 */
	function DashboardAccountingAdmin()
	{
		$general_balance = 0;

		$incomes_RET = DBGet( "SELECT CAST(ASSIGNED_DATE AS char(7)) AS YEAR_MONTH_DATE,
			SUM(AMOUNT) AS TOTAL_INCOMES
			FROM accounting_incomes
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH_DATE
			ORDER BY YEAR_MONTH_DATE DESC
			LIMIT 2", [], [ 'YEAR_MONTH_DATE' ] );

		$expenses_RET = DBGet( "SELECT CAST(PAYMENT_DATE AS char(7)) AS YEAR_MONTH_DATE,
			SUM(CASE WHEN STAFF_ID IS NULL THEN AMOUNT END) AS TOTAL_EXPENSES
			FROM accounting_payments
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH_DATE
			ORDER BY YEAR_MONTH_DATE DESC
			LIMIT 2", [], [ 'YEAR_MONTH_DATE' ] );

		$staff_payments_RET = DBGet( "SELECT CAST(PAYMENT_DATE AS char(7)) AS YEAR_MONTH_DATE,
			SUM(CASE WHEN STAFF_ID IS NOT NULL THEN AMOUNT END) AS TOTAL_STAFF
			FROM accounting_payments
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH_DATE
			ORDER BY YEAR_MONTH_DATE DESC
			LIMIT 2", [], [ 'YEAR_MONTH_DATE' ] );

		$student_payments_RET = DBGet( "SELECT CAST(PAYMENT_DATE AS char(7)) AS YEAR_MONTH_DATE,
			SUM(AMOUNT) AS TOTAL_STUDENT_PAYMENTS
			FROM billing_payments
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH_DATE
			ORDER BY YEAR_MONTH_DATE DESC
			LIMIT 2", [], [ 'YEAR_MONTH_DATE' ] );

		$accounting_RET = array_replace_recursive( $incomes_RET, $expenses_RET, $staff_payments_RET, $student_payments_RET );

		krsort( $accounting_RET );

		if ( ! empty( $accounting_RET[date( 'Y-m' )] ) )
		{
			$accounting_month = $accounting_RET[date( 'Y-m' )][1];

			// Accounting General Balance.

			if ( ! empty( $accounting_month['TOTAL_INCOMES'] ) )
			{
				$general_balance = $accounting_month['TOTAL_INCOMES'];
			}

			if ( ! empty( $accounting_month['TOTAL_EXPENSES'] ) )
			{
				$general_balance -= $accounting_month['TOTAL_EXPENSES'];
			}

			if ( ! empty( $accounting_month['TOTAL_STAFF'] ) )
			{
				$general_balance -= $accounting_month['TOTAL_STAFF'];
			}

			if ( ! empty( $accounting_month['TOTAL_STUDENT_PAYMENTS'] ) )
			{
				$general_balance += $accounting_month['TOTAL_STUDENT_PAYMENTS'];
			}
		}

		$accounting_data[_( 'General Balance' )] = Currency( $general_balance );

		foreach ( (array) $accounting_RET as $year_month => $accounting )
		{
			// Remove dummy day from proper date.
			// @since 9.0 Fix PHP8.1 deprecated strftime() use strftime_compat() instead
			$proper_month_year = ucfirst( strftime_compat(
				trim( str_replace( [ '%d', '//' ], [ '', '/'], Preferences( 'DATE' ) ), '-./ ' ),
				strtotime( $year_month . '-28' )
			) );

			$month_balance = 0;

			$accounting_data_month = null;

			if ( ! empty( $accounting[1]['TOTAL_INCOMES'] ) )
			{
				$month_balance = $accounting[1]['TOTAL_INCOMES'];

				// Incomes by month.
				$accounting_data_month .= NoInput(
					Currency( $accounting[1]['TOTAL_INCOMES'] ),
					_( 'Incomes' )
				) . '<br />';
			}

			if ( ! empty( $accounting[1]['TOTAL_EXPENSES'] ) )
			{
				$month_balance -= $accounting[1]['TOTAL_EXPENSES'];

				// Student Payments by month.
				$accounting_data_month .= NoInput(
					Currency( $accounting[1]['TOTAL_EXPENSES'] ),
					_( 'Expenses' )
				) . '<br />';
			}

			if ( ! empty( $accounting[1]['TOTAL_STAFF'] ) )
			{
				$month_balance -= $accounting[1]['TOTAL_STAFF'];

				// Student Payments by month.
				$accounting_data_month .= NoInput(
					Currency( $accounting[1]['TOTAL_STAFF'] ),
					_( 'Staff Payments' )
				) . '<br />';
			}

			if ( ! empty( $accounting[1]['TOTAL_STUDENT_PAYMENTS'] ) )
			{
				$month_balance += $accounting[1]['TOTAL_STUDENT_PAYMENTS'];

				// Student Payments by month.
				$accounting_data_month .= NoInput(
					Currency( $accounting[1]['TOTAL_STUDENT_PAYMENTS'] ),
					_( 'Student Payments' )
				) . '<br />';
			}

			$month_key = NoInput( Currency( $month_balance ), $proper_month_year );

			// Remove last <br />.
			$accounting_data[$month_key] = mb_substr( $accounting_data_month, 0, ( mb_strlen( $accounting_data_month ) - 6 ) );
		}

		$data = [];

		if ( $general_balance
			|| count( $accounting_data ) > 1 )
		{
			$data = $accounting_data;
		}

		return $data;
	}
}
