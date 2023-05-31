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

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&program=totals'  ) . '" method="GET">';

DrawHeader( _( 'Report Timeframe' ) . ': ' .
	PrepareDate( $start_date, '_start', false ) . ' ' . _( 'to' ) . ' ' .
	PrepareDate( $end_date, '_end', false ) . ' ' . Buttons( _( 'Go' ) ) );

echo '</form>';

$billing_payments = DBGetOne( "SELECT sum(AMOUNT) AS AMOUNT
	FROM billing_payments
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND PAYMENT_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'" );

$billing_fees = DBGetOne( "SELECT sum(f.AMOUNT) AS AMOUNT
	FROM billing_fees f
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
