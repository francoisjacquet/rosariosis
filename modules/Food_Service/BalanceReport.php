<?php

$date = RequestedDate( 'date', DBDate(), 'set' );

if ( ! empty( $_REQUEST['type'] ) )
{
	$_SESSION['FSA_type'] = $_REQUEST['type'];
}
else
{
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];
}

if ( $_REQUEST['type'] == 'staff' )
{
	$tabcolor_s = '#DFDFDF';
	$textcolor_s = '#999999';
	$tabcolor_u = Preferences( 'HEADER' );
	$textcolor_u = '#FFFFFF';
}
else
{
	$tabcolor_s = Preferences( 'HEADER' );
	$textcolor_s = '#FFFFFF';
	$tabcolor_u = '#DFDFDF';
	$textcolor_u = '#999999';
}

$header = '<a href="Modules.php?modname=' . $_REQUEST['modname'] .
	'&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] .
	'&year_date=' . $_REQUEST['year_date'] . '&type=student">' .
	( ! isset( $_REQUEST['type'] ) || $_REQUEST['type'] === 'student' ?
	'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

$header .= ' | <a href="Modules.php?modname=' . $_REQUEST['modname'] .
	'&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] .
	'&year_date=' . $_REQUEST['year_date'] . '&type=staff">' .
	( isset( $_REQUEST['type'] ) && $_REQUEST['type'] === 'staff' ?
	'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

DrawHeader(  ( $_SESSION['FSA_type'] == 'staff' ? _( 'User' ) : _( 'Student' ) ) . ' &minus; ' . ProgramTitle() );
User( 'PROFILE' ) === 'student' ? '' : DrawHeader( $header );

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	$PHP_tmp_SELF = PreparePHP_SELF();
	echo '<form action="' . $PHP_tmp_SELF . '" method="POST">';
	DrawHeader( PrepareDate( $date, '_date' ) . ' : <input type="submit" value="' . _( 'Go' ) . '" />' );
	echo '</form>';

	require_once 'modules/Food_Service/' . ( $_REQUEST['type'] == 'staff' ? 'Users' : 'Students' ) . '/BalanceReport.php';
}

$extra['new'] = true;

$extra['SELECT'] = ",fsa.ACCOUNT_ID,fst.BALANCE";
//$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_TRANSACTIONS WHERE ACCOUNT_ID=fsa.ACCOUNT_ID AND TIMESTAMP<date '".$date."'+1 ORDER BY TIMESTAMP DESC LIMIT 1) AS BALANCE";
$extra['FROM'] = ",FOOD_SERVICE_STUDENT_ACCOUNTS fsa,FOOD_SERVICE_TRANSACTIONS fst";
$extra['WHERE'] = " AND fsa.STUDENT_ID=ssm.STUDENT_ID AND fst.ACCOUNT_ID=fsa.ACCOUNT_ID AND fst.BALANCE>'0' AND fst.TRANSACTION_ID=(SELECT TRANSACTION_ID FROM FOOD_SERVICE_TRANSACTIONS WHERE ACCOUNT_ID=fsa.ACCOUNT_ID AND TIMESTAMP<date '" . $date . "'+1 ORDER BY TIMESTAMP DESC LIMIT 1)";
$extra['functions'] = array( 'ACCOUNT_ID' => '_total' );
$extra['columns_before'] = array( 'ACCOUNT_ID' => _( 'Account ID' ) );
$extra['columns_after'] = array( 'BALANCE' => _( 'Balance' ) );
$extra['group'] = $extra['LO_group'] = array( 'ACCOUNT_ID' );
$extra['link'] = array( 'FULL_NAME' => false );

Search( 'student_id', $extra );

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	DrawHeader( _( 'Total of Balances' ) . ' = ' . number_format( $total, 2 ) );
}

/**
 * @param $value
 * @return mixed
 */
function _total( $value )
{
	global $THIS_RET, $account_id, $total;

	if ( ! $account_id[$value] )
	{
		$total += $THIS_RET['BALANCE'];
		$account_id[$value] = true;
	}

	return $value;
}
