<?php
/**
 * Daily Totals program
 *
 * @package RosarioSIS
 * @subpackage modules
 */

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="GET">';

DrawHeader( '<b>' . _( 'Report Timeframe' ) . ': </b>' .
	PrepareDate( $start_date, '_start' ) . ' - ' .
	PrepareDate( $end_date, '_end' ), SubmitButton( _( 'Go' ) ) );

echo '</form>';

$billing_payments = DBGetOne( "SELECT sum(AMOUNT) AS AMOUNT
	FROM BILLING_PAYMENTS
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND PAYMENT_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'" );

$billing_fees = DBGetOne( "SELECT sum(f.AMOUNT) AS AMOUNT
	FROM BILLING_FEES f
	WHERE  f.SYEAR='" . UserSyear() . "'
	AND f.SCHOOL_ID='" . UserSchool() . "'
	AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'" );

echo '<br />';

PopTable( 'header', _( 'Totals' ) );

echo '<table class="cellspacing-5 align-right">';

echo '<tr><td>' . _( 'Payments' ) . ': ' .
	'</td><td>' . Currency( $billing_payments ) . '</td></tr>';

echo '<tr><td>' . _( 'Less' ) . ': ' . _( 'Fees' ) . ': ' .
	'</td><td>' . Currency( $billing_fees ) . '</td></tr>';

echo '<tr><td><b>' . _( 'Total' ) . ': ' . '</b></td>' .
	'<td><b>' . Currency( ( $billing_payments - $billing_fees ) ) . '</b></td></tr>';

echo '</table>';

PopTable( 'footer' );
