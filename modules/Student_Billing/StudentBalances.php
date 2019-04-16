<?php

DrawHeader( ProgramTitle() );

$extra['SELECT'] = ',(COALESCE((SELECT SUM(f.AMOUNT) FROM BILLING_FEES f WHERE f.STUDENT_ID=ssm.STUDENT_ID AND f.SYEAR=ssm.SYEAR),0)-COALESCE((SELECT SUM(p.AMOUNT) FROM BILLING_PAYMENTS p WHERE p.STUDENT_ID=ssm.STUDENT_ID AND p.SYEAR=ssm.SYEAR),0)) AS BALANCE';

$extra['columns_after'] = array( 'BALANCE' => _( 'Balance' ) );

$extra['link']['FULL_NAME'] = false;
$extra['new'] = true;
$extra['functions'] = array( 'BALANCE' => '_makeCurrency' );

if ( User( 'PROFILE' ) === 'parent' || User( 'PROFILE' ) === 'student' )
{
	$_REQUEST['search_modfunc'] = 'list';
}

Search( 'student_id', $extra );

/**
 * @param $value
 * @param $column
 */
function _makeCurrency( $value, $column )
{
	return Currency( $value * -1 );
}
