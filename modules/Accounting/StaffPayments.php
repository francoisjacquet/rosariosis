<?php
include_once('modules/Accounting/functions.inc.php');
if(!$_REQUEST['print_statements'])
	DrawHeader(ProgramTitle());
	
if(User('PROFILE')=='teacher')//limit to teacher himself
	$_REQUEST['staff_id'] = $_SESSION['STAFF_ID'];
		
//Widgets('all');
Search('staff_id',$extra);

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
			$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
			DBQuery($sql);
		}
		else
		{
			$id = DBGet(DBQuery("SELECT ".db_seq_nextval('ACCOUNTING_PAYMENTS_SEQ').' AS ID'.FROM_DUAL));
			$id = $id[1]['ID'];

			$sql = "INSERT INTO ACCOUNTING_PAYMENTS ";

			$fields = 'ID,STAFF_ID,SYEAR,SCHOOL_ID,PAYMENT_DATE,';
			$values = "'$id','".UserStaffID()."','".UserSyear()."','".UserSchool()."','".DBDate()."',";
			
			$go = 0;
			foreach($columns as $column=>$value)
			{
				if(!empty($value) || $value=='0')
				{
					if($column=='AMOUNT')
					{
						$value = preg_replace('/[^0-9.]/','',$value);
//modif Francois: fix SQL bug invalid amount
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

if(UserStaffID() && (!$_REQUEST['modfunc'] || $_REQUEST['modfunc']=='search_fnc'))
{
	$payments_total = 0;
	$functions = array('REMOVE'=>'_makePaymentsRemove','AMOUNT'=>'_makePaymentsAmount','PAYMENT_DATE'=>'ProperDate','COMMENTS'=>'_makePaymentsTextInput');
	$payments_RET = DBGet(DBQuery("SELECT '' AS REMOVE,ID,AMOUNT,PAYMENT_DATE,COMMENTS FROM ACCOUNTING_PAYMENTS WHERE STAFF_ID='".UserStaffID()."' AND SYEAR='".UserSyear()."' ORDER BY ID"),$functions);
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
	ListOutput($RET,$columns,'Payment','Payments',$link,array(),$options);
	if(!$_REQUEST['print_statements'] && AllowEdit())
		echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '<BR />';
	$salaries_total = DBGet(DBQuery("SELECT SUM(f.AMOUNT) AS TOTAL FROM ACCOUNTING_SALARIES f WHERE f.STAFF_ID='".UserStaffID()."' AND f.SYEAR='".UserSyear()."'"));
	$table = '<TABLE><TR><TD style="text-align:right">'._('Total from Salaries').': '.'</TD><TD style="text-align:right">'.Currency($salaries_total[1]['TOTAL']).'</TD></TR>';
	$table .= '<TR><TD style="text-align:right">'._('Less').': '._('Total from Staff Payments').': '.'</TD><TD style="text-align:right">'.Currency($payments_total).'</TD></TR>';
	$table .= '<TR><TD style="text-align:right">'._('Balance').': <b>'.'</b></TD><TD style="text-align:right"><b>'.Currency(($salaries_total[1]['TOTAL']-$payments_total),'CR').'</b></TD></TR></TABLE>';

	if(!$_REQUEST['print_statements'])
		DrawHeader('','',$table);
	else
		DrawHeader($table,'','',null,null,true);
	
	if(!$_REQUEST['print_statements'] && AllowEdit())
		echo '</FORM>';
}
?>