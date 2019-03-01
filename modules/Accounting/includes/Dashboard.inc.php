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
	 *
	 * @return array Dashboard data
	 */
	function DashboardAccountingAdmin()
	{
		$general_balance = 0;

		$incomes_RET = DBGet( "SELECT TO_CHAR(ASSIGNED_DATE,'YYYY-MM') AS YEAR_MONTH,
			SUM(AMOUNT) AS TOTAL_INCOMES
			FROM ACCOUNTING_INCOMES
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH
			ORDER BY YEAR_MONTH DESC
			LIMIT 3", array(), array( 'YEAR_MONTH' ) );

		$expenses_RET = DBGet( "SELECT TO_CHAR(PAYMENT_DATE,'YYYY-MM') AS YEAR_MONTH,
			SUM(CASE WHEN STAFF_ID IS NULL THEN AMOUNT END) AS TOTAL_EXPENSES
			FROM ACCOUNTING_PAYMENTS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH
			ORDER BY YEAR_MONTH DESC
			LIMIT 3", array(), array( 'YEAR_MONTH' ) );

		$staff_payments_RET = DBGet( "SELECT TO_CHAR(PAYMENT_DATE,'YYYY-MM') AS YEAR_MONTH,
			SUM(CASE WHEN STAFF_ID IS NOT NULL THEN AMOUNT END) AS TOTAL_STAFF
			FROM ACCOUNTING_PAYMENTS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH
			ORDER BY YEAR_MONTH DESC
			LIMIT 3", array(), array( 'YEAR_MONTH' ) );

		$student_payments_RET = DBGet( "SELECT TO_CHAR(PAYMENT_DATE,'YYYY-MM') AS YEAR_MONTH,
			SUM(AMOUNT) AS TOTAL_STUDENT_PAYMENTS
			FROM BILLING_PAYMENTS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			GROUP BY YEAR_MONTH
			ORDER BY YEAR_MONTH DESC
			LIMIT 3", array(), array( 'YEAR_MONTH' ) );

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
			$proper_date = ProperDate( $year_month . '-29' );

			// Remove dummy day from proper date.
			$proper_month_year = str_replace( array( '/29', ' 29' ), '', $proper_date );

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

		$data = array();

		if ( $general_balance
			|| count( $accounting_data ) > 1 )
		{
			$data = $accounting_data;
		}

		return $data;
	}
}
