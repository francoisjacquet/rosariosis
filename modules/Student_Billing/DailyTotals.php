<?php

DrawHeader( ProgramTitle() );

// set start date
if ( isset( $_REQUEST['day_start'] )
	&& isset( $_REQUEST['month_start'] )
	&& isset( $_REQUEST['year_start'] ) )
{
	$start_date = RequestedDate(
		$_REQUEST['day_start'],
		$_REQUEST['month_start'],
		$_REQUEST['year_start']
	);
}

if ( empty( $start_date ) )
	$start_date = '01-' . mb_strtoupper( date( 'M-Y' ) );

// set end date
if ( isset( $_REQUEST['day_end'] )
	&& isset( $_REQUEST['month_end'] )
	&& isset( $_REQUEST['year_end'] ) )
{
	$end_date = RequestedDate(
		$_REQUEST['day_end'],
		$_REQUEST['month_end'],
		$_REQUEST['year_end']
	);
}

if ( empty( $end_date ) )
	$end_date = DBDate();

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
DrawHeader(' &nbsp; &nbsp; <B>'._('Report Timeframe').': </B>'.PrepareDate($start_date,'_start').' - '.PrepareDate($end_date,'_end'),SubmitButton(_('Go')));
echo '</FORM>';

$billing_payments = DBGet(DBQuery("SELECT sum(AMOUNT) AS AMOUNT FROM BILLING_PAYMENTS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND PAYMENT_DATE BETWEEN '".$start_date."' AND '".$end_date."'"));

$billing_fees = DBGet(DBQuery("SELECT sum(f.AMOUNT) AS AMOUNT FROM BILLING_FEES f WHERE AND f.SCHOOL_ID='".UserSchool()."' AND f.ASSIGNED_DATE BETWEEN '".$start_date."' AND '".$end_date."'"));

PopTable('header',_('Totals'));

echo '<TABLE class="cellspacing-0 align-right">';
echo '<TR><TD>'._('Payments').': '.'</TD><TD>'.Currency($billing_payments[1]['AMOUNT']).'</TD></TR>';

echo '<TR><TD>'._('Less').': '._('Fees').': '.'</TD><TD>'.Currency($billing_fees[1]['AMOUNT']).'</TD></TR>';

echo '<TR><TD><B>'._('Total').': '.'</B></TD><TD><B>'.Currency(($billing_payments[1]['AMOUNT']-$billing_fees[1]['AMOUNT'])).'</B></TD></TR>';
echo '</TABLE>';

PopTable('footer');
