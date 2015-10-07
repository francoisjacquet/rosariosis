<?php
include_once('modules/Accounting/functions.inc.php');
if(!$_REQUEST['print_statements'])
	DrawHeader(ProgramTitle());

if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{
		if($id!='new')
		{
			$sql = "UPDATE ACCOUNTING_PAYMENTS SET ";
							
			foreach($columns as $column=>$value)
			{
				$sql .= $column."='".$value."',";
			}
			$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
			DBQuery($sql);
		}
		else
		{
			$id = DBGet(DBQuery("SELECT ".db_seq_nextval('ACCOUNTING_PAYMENTS_SEQ').' AS ID'.FROM_DUAL));
			$id = $id[1]['ID'];

			$sql = "INSERT INTO ACCOUNTING_PAYMENTS ";

			$fields = 'ID,SYEAR,SCHOOL_ID,PAYMENT_DATE,';
			$values = "'".$id."','".UserSyear()."','".UserSchool()."','".DBDate()."',";
			
			$go = 0;
			foreach($columns as $column=>$value)
			{
				if(!empty($value) || $value=='0')
				{
					if($column=='AMOUNT')
					{
						$value = preg_replace('/[^0-9.]/','',$value);
//FJ fix SQL bug invalid amount
						if (!is_numeric($value))
							$value = 0;
					}
					$fields .= $column.',';
					$values .= "'".$value."',";
					$go = true;
				}
			}
			$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
			
			if($go)
				DBQuery($sql);
		}
	}
	unset($_REQUEST['values']);
}

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if(DeletePrompt(_('Payment')))
	{
		DBQuery("DELETE FROM ACCOUNTING_PAYMENTS WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

if(!$_REQUEST['modfunc'])
{
	$payments_total = 0;
	$functions = array('REMOVE'=>'_makePaymentsRemove','AMOUNT'=>'_makePaymentsAmount','PAYMENT_DATE'=>'ProperDate','COMMENTS'=>'_makePaymentsTextInput');
	$payments_RET = DBGet(DBQuery("SELECT '' AS REMOVE,ID,AMOUNT,PAYMENT_DATE,COMMENTS FROM ACCOUNTING_PAYMENTS WHERE SYEAR='".UserSyear()."' AND STAFF_ID IS NULL ORDER BY ID"),$functions);
	$i = 1;
	$RET = array();
	foreach($payments_RET as $payment)
	{
		$RET[$i] = $payment;
		$i++;
	}

	if(count($RET) && !$_REQUEST['print_statements'] && AllowEdit())
		$columns = array('REMOVE'=>'');
	else
		$columns = array();
	
	$columns += array('AMOUNT'=>_('Amount'),'PAYMENT_DATE'=>_('Date'),'COMMENTS'=>_('Comment'));
	if(!$_REQUEST['print_statements'] && AllowEdit())
		$link['add']['html'] = array('REMOVE'=>button('add'),'AMOUNT'=>_makePaymentsTextInput('','AMOUNT'),'PAYMENT_DATE'=>ProperDate(DBDate()),'COMMENTS'=>_makePaymentsTextInput('','COMMENTS'));
	if(!$_REQUEST['print_statements'] && AllowEdit())
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
		DrawHeader('',SubmitButton(_('Save')));
		$options = array();
	}
	else
		$options = array('center'=>false,'add'=>false);

	ListOutput($RET,$columns,'Expense','Expenses',$link,array(),$options);

	if(!$_REQUEST['print_statements'] && AllowEdit())
		echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';

	echo '<BR />';

	$incomes_total = DBGet(DBQuery("SELECT SUM(f.AMOUNT) AS TOTAL FROM ACCOUNTING_INCOMES f WHERE f.SYEAR='".UserSyear()."'"));

	$table = '<TABLE class="align-right"><TR><TD>'._('Total from Incomes').': '.'</TD><TD>'.Currency($incomes_total[1]['TOTAL']).'</TD></TR>';

	$table .= '<TR><TD>'._('Less').': '._('Total from Expenses').': '.'</TD><TD>'.Currency($payments_total).'</TD></TR>';

	$table .= '<TR><TD>'._('Balance').': <b>'.'</b></TD><TD><b id="update_balance">'.Currency(($incomes_total[1]['TOTAL']-$payments_total)).'</b></TD></TR>';
	
	//add General Balance
	$table .= '<TR><TD colspan="2"><hr /></TD></TR><TR><TD>'._('Total from Incomes').': '.'</TD><TD>'.Currency($incomes_total[1]['TOTAL']).'</TD></TR>';
	
	if($RosarioModules['Student_Billing'])
	{
		$student_payments_total = DBGet(DBQuery("SELECT SUM(p.AMOUNT) AS TOTAL FROM BILLING_PAYMENTS p WHERE p.SYEAR='".UserSyear()."'"));

		$table .= '<TR><TD>& '._('Total from Student Payments').': '.'</TD><TD>'.Currency($student_payments_total[1]['TOTAL']).'</TD></TR>';
	}
	else
		$student_payments_total[1]['TOTAL'] = 0;
		
	$table .= '<TR><TD>'._('Less').': '._('Total from Expenses').': '.'</TD><TD>'.Currency($payments_total).'</TD></TR>';

	$Staff_payments_total = DBGet(DBQuery("SELECT SUM(p.AMOUNT) AS TOTAL FROM ACCOUNTING_PAYMENTS p WHERE p.STAFF_ID IS NOT NULL AND p.SYEAR='".UserSyear()."'"));

	$table .= '<TR><TD>& '._('Total from Staff Payments').': '.'</TD><TD>'.Currency($Staff_payments_total[1]['TOTAL']).'</TD></TR>';

	$table .= '<TR><TD>'._('General Balance').': <b>'.'</b></TD><TD><b id="update_balance">'.Currency(($incomes_total[1]['TOTAL']+$student_payments_total[1]['TOTAL']-$payments_total-$Staff_payments_total[1]['TOTAL'])).'</b></TD></TR></TABLE>';

	if(!$_REQUEST['print_statements'])
		DrawHeader('','',$table);
	else
		DrawHeader($table,'','',null,null,true);
	
	if(!$_REQUEST['print_statements'] && AllowEdit())
		echo '</FORM>';
}
