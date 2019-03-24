<?php

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="GET">';

DrawHeader( '<b>' . _( 'Report Timeframe' ) . ': </b>' . PrepareDate( $start_date, '_start', false ) .
	' - ' . PrepareDate( $end_date, '_end', false ) . Buttons( _( 'Go' ) ) );

echo '</form>';

// sort by date since the list is two lists merged and not already properly sorted

if ( empty( $_REQUEST['LO_sort'] ) )
{
	$_REQUEST['LO_sort'] = 'DATE';
}

//$RET = DBGet( "SELECT s.LAST_NAME||', '||s.FIRST_NAME||' '||COALESCE(s.MIDDLE_NAME,' ') AS FULL_NAME,f.AMOUNT AS DEBIT,'' AS CREDIT,f.TITLE||' '||COALESCE(f.COMMENTS,' ') AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID FROM BILLING_FEES f,STUDENTS s WHERE f.STUDENT_ID=s.STUDENT_ID AND f.SYEAR='".UserSyear()."' AND f.SCHOOL_ID='".UserSchool()."' AND f.ASSIGNED_DATE BETWEEN '".$start_date."' AND '".$end_date."' UNION SELECT s.LAST_NAME||', '||s.FIRST_NAME||' '||COALESCE(s.MIDDLE_NAME,' ') AS FULL_NAME,'' AS DEBIT,p.AMOUNT AS CREDIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID FROM BILLING_PAYMENTS p,STUDENTS s WHERE p.STUDENT_ID=s.STUDENT_ID AND p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."' AND p.PAYMENT_DATE BETWEEN '".$start_date."' AND '".$end_date."' ORDER BY DATE",$functions);

$extra['functions'] = array( 'DEBIT' => '_makeCurrency', 'CREDIT' => '_makeCurrency', 'DATE' => 'ProperDate' );
$fees_extra = $extra;
$fees_extra['SELECT'] .= ",f.AMOUNT AS DEBIT,'' AS CREDIT,f.TITLE||' '||COALESCE(f.COMMENTS,' ') AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID";
$fees_extra['FROM'] .= ',BILLING_FEES f';
$fees_extra['WHERE'] .= " AND f.STUDENT_ID=s.STUDENT_ID AND f.SYEAR=ssm.SYEAR
	AND f.SCHOOL_ID=ssm.SCHOOL_ID AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

$RET = GetStuList( $fees_extra );

$payments_extra = $extra;
$payments_extra['SELECT'] .= ",'' AS DEBIT,p.AMOUNT AS CREDIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID";
$payments_extra['FROM'] .= ',BILLING_PAYMENTS p';
$payments_extra['WHERE'] .= " AND p.STUDENT_ID=s.STUDENT_ID AND p.SYEAR=ssm.SYEAR AND p.SCHOOL_ID=ssm.SCHOOL_ID AND p.PAYMENT_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

$payments_RET = GetStuList( $payments_extra );

if ( ! empty( $payments_RET ) )
{
	$i = count( $RET ) + 1;

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[$i++] = $payment;
	}
}

$columns = array(
	'FULL_NAME' => _( 'Student' ),
	'DEBIT' => _( 'Fee' ),
	'CREDIT' => _( 'Payment' ),
	'DATE' => _( 'Date' ),
	'EXPLANATION' => _( 'Comment' ),
);

$link['add']['html'] = array(
	'FULL_NAME' => '<b>' . _( 'Total' ) . '</b>',
	'DEBIT' => '<b>' . Currency( $totals['DEBIT'] ) . '</b>',
	'CREDIT' => '<b>' . Currency( $totals['CREDIT'] ) . '</b>',
	'DATE' => '&nbsp;',
	'EXPLANATION' => '&nbsp;',
);

ListOutput( $RET, $columns, 'Transaction', 'Transactions', $link );
//$payments_RET = DBGet( "SELECT s.LAST_NAME||', '||s.FIRST_NAME||' '||COALESCE(s.MIDDLE_NAME,' ') AS FULL_NAME,'' AS DEBIT,p.AMOUNT AS CREDIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE FROM BILLING_PAYMENTS p,STUDENTS s WHERE p.STUDENT_ID=s.STUDENT_ID AND p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."' AND p.ASSIGNED_DATE BETWEEN '".$start_date."' AND '".$end_date."'" );

/**
 * @param $value
 * @param $column
 */
function _makeCurrency( $value, $column )
{
	global $totals;

	$totals[$column] += $value;

	if ( ! empty( $value ) || $value == '0' )
	{
		return Currency( $value );
	}
}
