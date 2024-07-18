<?php
/**
 * Daily Transactions program
 *
 * @package RosarioSIS
 * @subpackage modules
 */

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

if ( User( 'PROFILE' ) === 'admin' )
{
	DrawHeader( _programMenu( 'transactions' ) );
}

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&program=transactions'  ) . '" method="GET">';

if ( ! isset( $_REQUEST['expanded_view'] )
	|| $_REQUEST['expanded_view'] !== 'true' )
{
	$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'true' ] ) . '">' .
	_( 'Expanded View' ) . '</a>';
}
else
{
	$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'false' ] ) . '">' .
	_( 'Original View' ) . '</a>';
}

DrawHeader( _( 'Report Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) .
	' ' . _( 'to' ) . ' ' . PrepareDate( $end_date, '_end', false ) . ' ' . Buttons( _( 'Go' ) ),
	$expanded_view_header );

echo '</form>';

// sort by date since the list is two lists merged and not already properly sorted

if ( empty( $_REQUEST['LO_sort'] ) )
{
	$_REQUEST['LO_sort'] = 'DATE';
}

//$RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,f.AMOUNT AS DEBIT,'' AS CREDIT,CONCAT(f.TITLE,' ',COALESCE(f.COMMENTS,' ')) AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID FROM billing_fees f,students s WHERE f.STUDENT_ID=s.STUDENT_ID AND f.SYEAR='".UserSyear()."' AND f.SCHOOL_ID='".UserSchool()."' AND f.ASSIGNED_DATE BETWEEN '".$start_date."' AND '".$end_date."' UNION SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,'' AS DEBIT,p.AMOUNT AS CREDIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID FROM billing_payments p,students s WHERE p.STUDENT_ID=s.STUDENT_ID AND p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."' AND p.PAYMENT_DATE BETWEEN '".$start_date."' AND '".$end_date."' ORDER BY DATE",$functions);

$totals = [ 'DEBIT' => 0, 'CREDIT' => 0 ];

$extra['functions'] = [
	'DEBIT' => '_makeCurrency',
	'CREDIT' => '_makeCurrency',
	'DATE' => 'ProperDate',
	'CREATED_AT' => 'ProperDateTime',
];

$fees_extra = $extra;

$fees_extra['SELECT'] = issetVal( $fees_extra['SELECT'], '' );
$fees_extra['SELECT'] .= ",f.AMOUNT AS DEBIT,'' AS CREDIT,CONCAT(f.TITLE, ' ', COALESCE(f.COMMENTS,'')) AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID,f.CREATED_BY,f.CREATED_AT";

$fees_extra['FROM'] = issetVal( $fees_extra['FROM'], '' );
$fees_extra['FROM'] .= ',billing_fees f';

$fees_extra['WHERE'] = issetVal( $fees_extra['WHERE'], '' );
$fees_extra['WHERE'] .= " AND f.STUDENT_ID=s.STUDENT_ID AND f.SYEAR=ssm.SYEAR
	AND f.SCHOOL_ID=ssm.SCHOOL_ID AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

$RET = GetStuList( $fees_extra );

$fees_sql_count = $_ROSARIO['SQLLimitForList']['sql_count'];

$payments_extra = $extra;

$payments_extra['SELECT'] = issetVal( $payments_extra['SELECT'], '' );
$payments_extra['SELECT'] .= ",'' AS DEBIT,p.AMOUNT AS CREDIT,COALESCE(p.COMMENTS,'') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID,p.CREATED_BY,p.CREATED_AT";

$payments_extra['FROM'] = issetVal( $payments_extra['FROM'], '' );
$payments_extra['FROM'] .= ',billing_payments p';

$payments_extra['WHERE'] = issetVal( $payments_extra['WHERE'], '' );
$payments_extra['WHERE'] .= " AND p.STUDENT_ID=s.STUDENT_ID AND p.SYEAR=ssm.SYEAR AND p.SCHOOL_ID=ssm.SCHOOL_ID AND p.PAYMENT_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

$payments_RET = GetStuList( $payments_extra );

$payments_sql_count = $_ROSARIO['SQLLimitForList']['sql_count'];

if ( ! empty( $_ROSARIO['SQLLimitForList']['sql_count'] ) )
{
	/**
	 * Fix no results if more than 1000 fees but 0 payments for timeframe.
	 * Results made of multiple GetStuList() / GetStaffList() calls.
	 * Sum SQL queries to COUNT total results before ListOutput().
	 *
	 * @since 11.7.4
	 */
	$_ROSARIO['SQLLimitForList']['sql_count'] = "SELECT (" . $fees_sql_count . ") + (" . $payments_sql_count . ")
		AS SUM FROM DUAL";
}

if ( ! empty( $payments_RET ) )
{
	$i = count( $RET ) + 1;

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[$i++] = $payment;
	}
}

$columns = [
	'FULL_NAME' => _( 'Student' ),
	'DEBIT' => _( 'Fee' ),
	'CREDIT' => _( 'Payment' ),
	'DATE' => _( 'Date' ),
	'EXPLANATION' => _( 'Comment' ),
];

if ( isset( $_REQUEST['expanded_view'] )
	&& $_REQUEST['expanded_view'] === 'true' )
{
	// @since 11.2 Expanded View: Add Created by & Created at columns.
	$columns += [
		'CREATED_BY' => _( 'Created by' ),
		'CREATED_AT' => _( 'Created at' ),
	];
}

$link['add']['html'] = [
	'FULL_NAME' => _( 'Total' ) . ': ' .
		'<b>' . Currency( $totals['CREDIT'] - $totals['DEBIT'] ) . '</b>',
	'DEBIT' => '<b>' . Currency( $totals['DEBIT'] ) . '</b>',
	'CREDIT' => '<b>' . Currency( $totals['CREDIT'] ) . '</b>',
];

// Force display of $link['add'] on PDF or if not allowed to edit
$options['add'] = true;

// @since 11.8 Add pagination for list > 1000 results
$options['pagination'] = true;

ListOutput( $RET, $columns, 'Transaction', 'Transactions', $link, [], $options );
//$payments_RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,'' AS DEBIT,p.AMOUNT AS CREDIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE FROM billing_payments p,students s WHERE p.STUDENT_ID=s.STUDENT_ID AND p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."' AND p.ASSIGNED_DATE BETWEEN '".$start_date."' AND '".$end_date."'" );

/**
 * @param $value
 * @param $column
 */
function _makeCurrency( $value, $column )
{
	global $totals;

	if ( ! isset( $totals[$column] ) )
	{
		$totals[$column] = 0;
	}

	$totals[$column] += (float) $value;

	if ( ! empty( $value ) || $value == '0' )
	{
		return Currency( $value );
	}
}
