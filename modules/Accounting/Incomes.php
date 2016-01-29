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
			$sql = "UPDATE ACCOUNTING_INCOMES SET ";
							
			foreach($columns as $column=>$value)
			{
				$sql .= $column."='".$value."',";
			}
			$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
			DBQuery($sql);
		}
		else
		{
			$sql = "INSERT INTO ACCOUNTING_INCOMES ";

			$fields = 'ID,SCHOOL_ID,SYEAR,ASSIGNED_DATE,';
			$values = db_seq_nextval('ACCOUNTING_INCOMES_SEQ').",'".UserSchool()."','".UserSyear()."','".DBDate()."',";
			
			$go = 0;
			foreach($columns as $column=>$value)
			{
				if(!empty($value) || $value=='0')
				{
					if($column=='AMOUNT')
						$value = preg_replace('/[^0-9.-]/','',$value);
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
	if(DeletePrompt(_('Income')))
	{
		DBQuery("DELETE FROM ACCOUNTING_INCOMES WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

if(!$_REQUEST['modfunc'])
{
	$incomes_total = 0;
	$functions = array('REMOVE'=>'_makeIncomesRemove','ASSIGNED_DATE'=>'ProperDate','COMMENTS'=>'_makeIncomesTextInput','AMOUNT'=>'_makeIncomesAmount');
	$incomes_RET = DBGet(DBQuery("SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,f.COMMENTS,f.AMOUNT FROM ACCOUNTING_INCOMES f WHERE f.SYEAR='".UserSyear()."' AND f.SCHOOL_ID='".UserSchool()."' ORDER BY f.ASSIGNED_DATE"),$functions);
	$i = 1;
	$RET = array();
	foreach($incomes_RET as $income)
	{
		$RET[$i] = $income;
		$i++;
	}
	
	if(count($RET) && !$_REQUEST['print_statements'] && AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
		$columns = array('REMOVE'=>'');
	else
		$columns = array();

	$columns += array('TITLE'=>_('Income'),'AMOUNT'=>_('Amount'),'ASSIGNED_DATE'=>_('Assigned'),'COMMENTS'=>_('Comment'));
	if(!$_REQUEST['print_statements'])
		$link['add']['html'] = array('REMOVE'=>button('add'),'TITLE'=>_makeIncomesTextInput('','TITLE'),'AMOUNT'=>_makeIncomesTextInput('','AMOUNT'),'ASSIGNED_DATE'=>ProperDate(DBDate()),'COMMENTS'=>_makeIncomesTextInput('','COMMENTS'));
	if(!$_REQUEST['print_statements'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
		if(AllowEdit())
			DrawHeader('',SubmitButton(_('Save')));
		$options = array();
	}
	else
		$options = array('center'=>false);
	ListOutput($RET,$columns,'Income','Incomes',$link,array(),$options);
	if(!$_REQUEST['print_statements'] && AllowEdit())
		echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '<BR />';

	$payments_total = DBGet(DBQuery("SELECT SUM(p.AMOUNT) AS TOTAL FROM ACCOUNTING_PAYMENTS p WHERE p.STAFF_ID IS NULL AND p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."'"));

	$table = '<TABLE class="align-right"><TR><TD>'._('Total from Incomes').': '.'</TD><TD>'.Currency($incomes_total).'</TD></TR>';

	$table .= '<TR><TD>'._('Less').': '._('Total from Expenses').': '.'</TD><TD>'.Currency($payments_total[1]['TOTAL']).'</TD></TR>';

	$table .= '<TR><TD>'._('Balance').': <b>'.'</b></TD><TD><b id="update_balance">'.Currency(($incomes_total-$payments_total[1]['TOTAL'])).'</b></TD></TR>';
	
	//add General Balance
	$table .= '<TR><TD colspan="2"><hr /></TD></TR><TR><TD>'._('Total from Incomes').': '.'</TD><TD>'.Currency($incomes_total).'</TD></TR>';
	
	if($RosarioModules['Student_Billing'])
	{
		$student_payments_total = DBGet(DBQuery("SELECT SUM(p.AMOUNT) AS TOTAL FROM BILLING_PAYMENTS p WHERE p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."'"));
		$table .= '<TR><TD>& '._('Total from Student Payments').': '.'</TD><TD>'.Currency($student_payments_total[1]['TOTAL']).'</TD></TR>';
	}
	else
		$student_payments_total[1]['TOTAL'] = 0;
		
	$table .= '<TR><TD>'._('Less').': '._('Total from Expenses').': '.'</TD><TD>'.Currency($payments_total[1]['TOTAL']).'</TD></TR>';

	$Staff_payments_total = DBGet(DBQuery("SELECT SUM(p.AMOUNT) AS TOTAL FROM ACCOUNTING_PAYMENTS p WHERE p.STAFF_ID IS NOT NULL AND p.SYEAR='".UserSyear()."' AND p.SCHOOL_ID='".UserSchool()."'"));
	$table .= '<TR><TD>& '._('Total from Staff Payments').': '.'</TD><TD>'.Currency($Staff_payments_total[1]['TOTAL']).'</TD></TR>';

	$table .= '<TR><TD>'._('General Balance').': <b>'.'</b></TD><TD><b id="update_balance">'.Currency(($incomes_total+$student_payments_total[1]['TOTAL']-$payments_total[1]['TOTAL']-$Staff_payments_total[1]['TOTAL'])).'</b></TD></TR></TABLE>';
		
	if(!$_REQUEST['print_statements'])
		DrawHeader('','',$table);
	else
		DrawHeader($table,'','',null,null,true);
	
	if(!$_REQUEST['print_statements'] && AllowEdit())
		echo '</FORM>';
}

?>
