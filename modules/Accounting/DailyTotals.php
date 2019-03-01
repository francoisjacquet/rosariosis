<?php
/**
 * Daily Totals program
 *
 * @package RosarioSIS
 * @subpackage modules
 */

DrawHeader( ProgramTitle() );

// Set start date.
if ( isset( $_REQUEST['day_start'] )
	&& isset( $_REQUEST['month_start'] )
	&& isset( $_REQUEST['year_start'] ) )
{
	$start_date = RequestedDate(
		$_REQUEST['year_start'],
		$_REQUEST['month_start'],
		$_REQUEST['day_start']
	);
}

if ( empty( $start_date ) )
{
	$start_date = date( 'Y-m' ) . '-01';
}

// Set end date.
if ( isset( $_REQUEST['day_end'] )
	&& isset( $_REQUEST['month_end'] )
	&& isset( $_REQUEST['year_end'] ) )
{
	$end_date = RequestedDate(
		$_REQUEST['year_end'],
		$_REQUEST['month_end'],
		$_REQUEST['day_end']
	);
}

if ( empty( $end_date ) )
{
	$end_date = DBDate();
}

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&accounting=" method="GET">';


$header_checkboxes = '<label><input type="checkbox" value="true" name="accounting" id="accounting" ' .
	( ! isset( $_REQUEST['accounting'] )
		|| $_REQUEST['accounting'] == 'true' ? 'checked ' : '' ) . '/> ' .
	_( 'Expense' ) . ' & ' . _( 'Income' ) . '</label>&nbsp; ';

$header_checkboxes .= '<label><input type="checkbox" value="true" name="staff_payroll" id="staff_payroll" ' .
	( ! empty( $_REQUEST['staff_payroll'] ) ? 'checked ' : '' ) . '/> ' .
	_( 'Staff Payroll' ) . '</label>&nbsp; ';

if ( $RosarioModules['Student_Billing'] )
{
	$header_checkboxes .= '<label><input type="checkbox" value="true" name="student_billing" id="student_billing" ' .
		( ! empty( $_REQUEST['student_billing'] ) ? 'checked ' : '' ) . '/> ' .
		_( 'Student Billing' ) . '</label>';
}

DrawHeader( $header_checkboxes, '' );

DrawHeader( '<b>' . _( 'Report Timeframe' ) . ': </b>' .
	PrepareDate( $start_date, '_start' ) . ' - ' .
	PrepareDate( $end_date, '_end' ), SubmitButton( _( 'Go' ) ) );

echo '</form>';

// Accounting.
if ( ! isset( $_REQUEST['accounting'] )
	|| $_REQUEST['accounting'] == 'true' )
{
	$accounting_payments = DBGet( "SELECT sum(AMOUNT) AS AMOUNT
		FROM ACCOUNTING_PAYMENTS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND PAYMENT_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		AND STAFF_ID IS NULL" );

	$accounting_incomes = DBGet( "SELECT sum(f.AMOUNT) AS AMOUNT
		FROM ACCOUNTING_INCOMES f
		WHERE f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'" );
}

// Staff salaries.
if ( ! empty( $_REQUEST['staff_payroll'] ) )
{
	$staffpayroll_payments = DBGet( "SELECT sum(p.AMOUNT) AS AMOUNT
		FROM ACCOUNTING_PAYMENTS p, STAFF s
		WHERE p.SYEAR='" . UserSyear() . "'
		AND s.SYEAR=p.SYEAR
		AND p.SCHOOL_ID='" . UserSchool() . "'
		AND p.PAYMENT_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		AND p.STAFF_ID=s.STAFF_ID
		AND p.SYEAR=s.SYEAR" );

	$staffpayroll_incomes = DBGet( "SELECT sum(f.AMOUNT) AS AMOUNT
		FROM ACCOUNTING_SALARIES f, STAFF s
		WHERE f.SYEAR='" . UserSyear() . "'
		AND s.SYEAR=f.SYEAR
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		AND f.STAFF_ID=s.STAFF_ID
		AND f.SYEAR=s.SYEAR" );
}

// Student Billing.
if ( ! empty( $_REQUEST['student_billing'] )
	&& $RosarioModules['Student_Billing'] )
{
	$billing_payments = DBGet( "SELECT sum(AMOUNT) AS AMOUNT
		FROM BILLING_PAYMENTS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND PAYMENT_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'" );

	$billing_fees = DBGet( "SELECT sum(f.AMOUNT) AS AMOUNT
		FROM BILLING_FEES f
		WHERE f.SCHOOL_ID='" . UserSchool() . "'
		AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'" );
}

echo '<br />';

PopTable( 'header', _( 'Totals' ) );

echo '<table class="cellspacing-5 align-right">';

$total = 0;

// Accounting.
if ( ! isset( $_REQUEST['accounting'] )
	|| $_REQUEST['accounting'] == 'true' )
{
	echo '<tr><td>' . _( 'Expenses' ) . ': ' .
		'</td><td>' . Currency( $accounting_payments[1]['AMOUNT'] ) . '</td></tr>';

	echo '<tr><td>' . _( 'Less' ) . ': ' . _( 'Incomes' ) . ': ' .
		'</td><td>' . Currency( $accounting_incomes[1]['AMOUNT'] ) . '</td></tr>';

	$total += $accounting_payments[1]['AMOUNT'] - $accounting_incomes[1]['AMOUNT'];
}


// Staff salaries.
if ( ! empty( $_REQUEST['staff_payroll'] ) )
{
	echo '<tr><td>' . _( 'Salaries' ) . ': ' .
		'</td><td>' . Currency( $staffpayroll_payments[1]['AMOUNT'] ) . '</td></tr>';

	echo '<tr><td>' . _( 'Less' ) . ': ' . _( 'Staff Payments' ) . ': ' .
		'</td><td>' . Currency( $staffpayroll_incomes[1]['AMOUNT'] ) . '</td></tr>';

	$total += $staffpayroll_payments[1]['AMOUNT'] - $staffpayroll_incomes[1]['AMOUNT'];
}

// Student Billing.
if ( ! empty( $_REQUEST['student_billing'] )
	&& $RosarioModules['Student_Billing'] )
{
	echo '<tr><td>' . _( 'Student Payments' ) . ': ' .
		'</td><td>' . Currency( $billing_payments[1]['AMOUNT'] ) . '</td></tr>';

	echo '<tr><td>' . _( 'Less' ) . ': ' . _( 'Fees' ) . ': ' .
		'</td><td>' . Currency( $billing_fees[1]['AMOUNT'] ) . '</td></tr>';

	$total += $billing_payments[1]['AMOUNT'] - $billing_fees[1]['AMOUNT'];
}

echo '<tr><td><b>' . _( 'Total' ) . ': ' . '</b></td>' .
	'<td><b>' . Currency( $total ) . '</b></td></tr>';

echo '</table>';

PopTable( 'footer' );
