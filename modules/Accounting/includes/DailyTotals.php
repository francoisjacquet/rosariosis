<?php
/**
 * Daily Totals program
 *
 * @package RosarioSIS
 * @subpackage modules
 */

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

DrawHeader( _programMenu( 'totals' ) );

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&program=totals&accounting=' ) . '" method="GET">';

$header_checkboxes = '<label><input type="checkbox" value="true" name="accounting" id="accounting" ' .
	( ! isset( $_REQUEST['accounting'] )
		|| $_REQUEST['accounting'] == 'true' ? 'checked ' : '' ) . '/> ' .
	_( 'Income' ) . ' & ' . _( 'Expense' ) . '</label>&nbsp; ';

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

DrawHeader( _( 'Report Timeframe' ) . ': ' .
	PrepareDate( $start_date, '_start', false ) . ' &nbsp; ' . _( 'to' ) . ' &nbsp; ' .
	PrepareDate( $end_date, '_end', false ) . ' ' . Buttons( _( 'Go' ) ) );

echo '</form>';

// Accounting.
if ( ! isset( $_REQUEST['accounting'] )
	|| $_REQUEST['accounting'] == 'true' )
{
	$accounting_payments = DBGetOne( "SELECT sum(AMOUNT) AS AMOUNT
		FROM accounting_payments
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND PAYMENT_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		AND STAFF_ID IS NULL" );

	$accounting_incomes = DBGetOne( "SELECT sum(f.AMOUNT) AS AMOUNT
		FROM accounting_incomes f
		WHERE f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'" );
}

// Staff salaries.
if ( ! empty( $_REQUEST['staff_payroll'] ) )
{
	$staffpayroll_payments = DBGetOne( "SELECT sum(p.AMOUNT) AS AMOUNT
		FROM accounting_payments p, staff s
		WHERE p.SYEAR='" . UserSyear() . "'
		AND s.SYEAR=p.SYEAR
		AND p.SCHOOL_ID='" . UserSchool() . "'
		AND p.PAYMENT_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		AND p.STAFF_ID=s.STAFF_ID
		AND p.SYEAR=s.SYEAR" );

	$staffpayroll_incomes = DBGetOne( "SELECT sum(f.AMOUNT) AS AMOUNT
		FROM accounting_salaries f, staff s
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
	$billing_payments = DBGetOne( "SELECT sum(AMOUNT) AS AMOUNT
		FROM billing_payments
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND PAYMENT_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'" );

	$billing_fees = DBGetOne( "SELECT sum(f.AMOUNT) AS AMOUNT
		FROM billing_fees f
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
	echo '<tr><td>' . _( 'Incomes' ) . ': ' .
		'</td><td>' . Currency( $accounting_incomes ) . '</td></tr>';

	echo '<tr><td>' . _( 'Less' ) . ': ' . _( 'Expenses' ) . ': ' .
		'</td><td>' . Currency( $accounting_payments ) . '</td></tr>';

	$total += $accounting_incomes - $accounting_payments;
}


// Staff salaries.
if ( ! empty( $_REQUEST['staff_payroll'] ) )
{
	echo '<tr><td>' . _( 'Salaries' ) . ': ' .
		'</td><td>' . Currency( $staffpayroll_incomes ) . '</td></tr>';

	echo '<tr><td>' . _( 'Less' ) . ': ' . _( 'Staff Payments' ) . ': ' .
		'</td><td>' . Currency( $staffpayroll_payments ) . '</td></tr>';

	$total += $staffpayroll_incomes - $staffpayroll_payments;
}

// Student Billing.
if ( ! empty( $_REQUEST['student_billing'] )
	&& $RosarioModules['Student_Billing'] )
{
	echo '<tr><td>' . _( 'Student Payments' ) . ': ' .
		'</td><td>' . Currency( $billing_payments ) . '</td></tr>';

	echo '<tr><td>' . _( 'Less' ) . ': ' . _( 'Fees' ) . ': ' .
		'</td><td>' . Currency( $billing_fees ) . '</td></tr>';

	$total += $billing_payments - $billing_fees;
}

echo '<tr><td><b>' . _( 'Total' ) . ': ' . '</b></td>' .
	'<td><b>' . Currency( $total ) . '</b></td></tr>';

echo '</table>';

PopTable( 'footer' );
