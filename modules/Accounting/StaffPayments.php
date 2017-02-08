<?php
require_once 'modules/Accounting/functions.inc.php';

if (User('PROFILE')=='teacher')//limit to teacher himself
	$_REQUEST['staff_id'] = User('STAFF_ID');

if ( ! $_REQUEST['print_statements'])
{
	DrawHeader(ProgramTitle());

	Search('staff_id',$extra);
}

// Add eventual Dates to $_REQUEST['values'].
if ( isset( $_REQUEST['day_values'], $_REQUEST['month_values'], $_REQUEST['year_values'] ) )
{
	$requested_dates = RequestedDates(
		$_REQUEST['year_values'],
		$_REQUEST['month_values'],
		$_REQUEST['day_values']
	);

	$_REQUEST['values'] = array_replace_recursive( (array) $_REQUEST['values'], (array) $requested_dates );
}

if ( $_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns)
	{
		if ( $id!='new')
		{
			$sql = "UPDATE ACCOUNTING_PAYMENTS SET ";

			foreach ( (array) $columns as $column => $value)
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}
			$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
			DBQuery($sql);
		}
		elseif ( $columns['AMOUNT'] !== ''
			&& $columns['PAYMENT_DATE'] )
		{
			$id = DBGet(DBQuery("SELECT ".db_seq_nextval('ACCOUNTING_PAYMENTS_SEQ').' AS ID'));
			$id = $id[1]['ID'];

			$sql = "INSERT INTO ACCOUNTING_PAYMENTS ";

			$fields = 'ID,STAFF_ID,SYEAR,SCHOOL_ID,';
			$values = "'".$id."','".UserStaffID()."','".UserSyear()."','".UserSchool()."',";

			$go = 0;
			foreach ( (array) $columns as $column => $value)
			{
				if ( !empty($value) || $value=='0')
				{
					if ( $column=='AMOUNT')
					{
						$value = preg_replace('/[^0-9.-]/','',$value);

						//FJ fix SQL bug invalid amount
						if ( !is_numeric($value))
							$value = 0;
					}
					$fields .= DBEscapeIdentifier( $column ) . ',';
					$values .= "'" . $value . "',";
					$go = true;
				}
			}
			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

			if ( $go)
				DBQuery($sql);
		}
	}
	unset($_REQUEST['values']);
}

if ( $_REQUEST['modfunc'] === 'remove' && AllowEdit() )
{
	if ( DeletePrompt( _( 'Payment' ) ) )
	{
		DBQuery("DELETE FROM ACCOUNTING_PAYMENTS WHERE ID='" . $_REQUEST['id'] . "'");

		// Unset modfunc & ID.
		$_REQUEST['modfunc'] = false;
		$_SESSION['_REQUEST_vars']['modfunc'] = false;
		$_SESSION['_REQUEST_vars']['id'] = false;
	}
}

if (UserStaffID() && ! $_REQUEST['modfunc'])
{
	$payments_total = 0;

	$functions = array(
		'REMOVE' => '_makePaymentsRemove',
		'AMOUNT' => '_makePaymentsAmount',
		'PAYMENT_DATE' => 'ProperDate',
		'COMMENTS' => '_makePaymentsTextInput',
	);

	$payments_RET = DBGet(DBQuery("SELECT '' AS REMOVE,ID,AMOUNT,PAYMENT_DATE,COMMENTS FROM ACCOUNTING_PAYMENTS WHERE STAFF_ID='".UserStaffID()."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY ID"),$functions);
	$i = 1;
	$RET = array();
	foreach ( (array) $payments_RET as $payment)
	{
		$RET[ $i ] = $payment;
		$i++;
	}

	if (count($RET) && ! $_REQUEST['print_statements'] && AllowEdit())
		$columns = array('REMOVE' => '');
	else
		$columns = array();

	$columns += array(
		'AMOUNT' => _( 'Amount' ),
		'PAYMENT_DATE' => _( 'Date' ),
		'COMMENTS' => _( 'Comment' ),
	);

	if ( ! $_REQUEST['print_statements']
		&& AllowEdit() )
	{
		$link['add']['html'] = array(
			'REMOVE' => button( 'add' ),
			'AMOUNT' => _makePaymentsTextInput( '', 'AMOUNT' ),
			'PAYMENT_DATE' => _makePaymentsDateInput( DBDate(), 'PAYMENT_DATE' ),
			'COMMENTS' => _makePaymentsTextInput( '', 'COMMENTS' ),
		);
	}

	if ( ! $_REQUEST['print_statements'] && AllowEdit())
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
		DrawHeader('',SubmitButton(_('Save')));
		$options = array();
	}
	else
		$options = array('center'=>false,'add'=>false);

	ListOutput($RET,$columns,'Payment','Payments',$link,array(),$options);

	if ( ! $_REQUEST['print_statements'] && AllowEdit())
		echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';

	echo '<br />';

	$salaries_total = DBGet(DBQuery("SELECT SUM(f.AMOUNT) AS TOTAL FROM ACCOUNTING_SALARIES f WHERE f.STAFF_ID='".UserStaffID()."' AND f.SYEAR='".UserSyear()."' AND f.SCHOOL_ID='".UserSchool()."'"));

	$table = '<table class="align-right"><tr><td>'._('Total from Salaries').': '.'</td><td>'.Currency($salaries_total[1]['TOTAL']).'</td></tr>';

	$table .= '<tr><td>'._('Less').': '._('Total from Staff Payments').': '.'</td><td>'.Currency($payments_total).'</td></tr>';

	$table .= '<tr><td>'._('Balance').': <b>'.'</b></td><td><b>'.Currency(($salaries_total[1]['TOTAL']-$payments_total),'CR').'</b></td></tr></table>';

	if ( ! $_REQUEST['print_statements'])
		DrawHeader('','',$table);
	else
		DrawHeader($table,'','',null,null,true);

	if ( ! $_REQUEST['print_statements'] && AllowEdit())
		echo '</form>';
}
