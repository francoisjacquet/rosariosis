<?php
DrawHeader( ProgramTitle() );

$extra['SELECT'] = ",(COALESCE(
	(SELECT SUM(f.AMOUNT)
		FROM ACCOUNTING_SALARIES f
		WHERE f.STAFF_ID=s.STAFF_ID
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.SYEAR=s.SYEAR),0)
	-COALESCE(
	(SELECT SUM(p.AMOUNT)
		FROM ACCOUNTING_PAYMENTS p
		WHERE p.STAFF_ID=s.STAFF_ID
		AND p.SCHOOL_ID='" . UserSchool() . "'
		AND p.SYEAR=s.SYEAR)
	,0)) AS BALANCE";

$extra['columns_after'] = array( 'BALANCE' => _( 'Balance' ) );

$extra['link']['FULL_NAME'] = false;
$extra['new'] = true;
$extra['functions'] = array( 'BALANCE' => '_makeCurrency' );

if ( User( 'PROFILE' ) === 'parent' || User( 'PROFILE' ) === 'teacher' )
{
	$_REQUEST['search_modfunc'] = 'list';
}

Search( 'staff_id', $extra );

/**
 * @param $value
 * @param $column
 */
function _makeCurrency( $value, $column )
{
	return Currency( $value * -1 );
}
