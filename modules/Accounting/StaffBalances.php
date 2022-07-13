<?php
DrawHeader( ProgramTitle() );

$extra['SELECT'] = ",(COALESCE(
	(SELECT SUM(f.AMOUNT)
		FROM accounting_salaries f
		WHERE f.STAFF_ID=s.STAFF_ID
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.SYEAR=s.SYEAR),0)
	-COALESCE(
	(SELECT SUM(p.AMOUNT)
		FROM accounting_payments p
		WHERE p.STAFF_ID=s.STAFF_ID
		AND p.SCHOOL_ID='" . UserSchool() . "'
		AND p.SYEAR=s.SYEAR)
	,0)) AS BALANCE";

$extra['columns_after'] = [ 'BALANCE' => _( 'Balance' ) ];

$extra['link']['FULL_NAME'] = false;
$extra['new'] = true;
$extra['functions'] = [ 'BALANCE' => '_makeCurrency' ];

if ( User( 'PROFILE' ) === 'parent' || User( 'PROFILE' ) === 'teacher' )
{
	$_REQUEST['search_modfunc'] = 'list';
}

// Fix SQL error table name "sam" specified more than once
$extra2 = $extra;

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	// Call GetStaffList() only so we calculate the $total.
	GetStaffList( $extra );
}

// @since 10.0 Add Total sum of balances.
$extra2['link']['add']['html'] = [
	'FULL_NAME' => '<b>' . _( 'Total' ) . '</b>',
	'BALANCE' => '<b>' . Currency( ( isset( $total ) ? $total * -1 : 0 ) ) . '</b>',
];

Search( 'staff_id', $extra2 );

/**
 * @param $value
 * @param $column
 */
function _makeCurrency( $value, $column )
{
	global $total;

	if ( ! isset( $total ) )
	{
		$total = 0;
	}

	$total += (float) $value;

	return Currency( $value * -1 );
}
