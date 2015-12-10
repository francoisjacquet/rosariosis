<?php

require_once 'modules/Accounting/functions.inc.php';
if (User('PROFILE')=='teacher')//limit to teacher himself
	$_REQUEST['staff_id'] = User('STAFF_ID');

if ( !$_REQUEST['print_statements'])
{
	DrawHeader(ProgramTitle());
	
	Search('staff_id',$extra);
}

if ( $_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	if (count($_REQUEST['month_']))
	{
		foreach ( (array) $_REQUEST['month_'] as $id => $columns)
		{
			foreach ( (array) $columns as $column => $value)
			{
				if ( $_REQUEST['day_'][ $id ][ $column ] && $_REQUEST['month_'][ $id ][ $column ] && $_REQUEST['year_'][ $id ][ $column ])
					$_REQUEST['values'][ $id ][ $column ] = $_REQUEST['day_'][ $id ][ $column ].'-'.$_REQUEST['month_'][ $id ][ $column ].'-'.$_REQUEST['year_'][ $id ][ $column ];
			}
		}
	}

	foreach ( (array) $_REQUEST['values'] as $id => $columns)
	{
		if ( $id!='new')
		{
			$sql = "UPDATE ACCOUNTING_SALARIES SET ";
							
			foreach ( (array) $columns as $column => $value)
			{
				$sql .= $column."='".$value."',";
			}
			$sql = mb_substr($sql,0,-1) . " WHERE STAFF_ID='".UserStaffID()."' AND ID='".$id."'";
			DBQuery($sql);
		}
		else
		{
			$sql = "INSERT INTO ACCOUNTING_SALARIES ";

			$fields = 'ID,STAFF_ID,SCHOOL_ID,SYEAR,ASSIGNED_DATE,';
			$values = db_seq_nextval('ACCOUNTING_SALARIES_SEQ').",'".UserStaffID()."','".UserSchool()."','".UserSyear()."','".DBDate()."',";
			
			$go = 0;
			foreach ( (array) $columns as $column => $value)
			{
				if ( !empty($value) || $value=='0')
				{
					if ( $column=='AMOUNT')
						$value = preg_replace('/[^0-9.-]/','',$value);
					$fields .= $column.',';
					$values .= "'".$value."',";
					$go = true;
				}
			}
			$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
			
			if ( $go)
				DBQuery($sql);
		}
	}
	unset($_REQUEST['values']);
}

if ( $_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if (DeletePrompt(_('Salary')))
	{
		DBQuery("DELETE FROM ACCOUNTING_SALARIES WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

if (UserStaffID() && !$_REQUEST['modfunc'])
{
	$salaries_total = 0;
	$functions = array('REMOVE' => '_makeSalariesRemove','ASSIGNED_DATE' => 'ProperDate','DUE_DATE' => '_makeSalariesDateInput','COMMENTS' => '_makeSalariesTextInput','AMOUNT' => '_makeSalariesAmount');
	$salaries_RET = DBGet(DBQuery("SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,f.DUE_DATE,f.COMMENTS,f.AMOUNT FROM ACCOUNTING_SALARIES f WHERE f.STAFF_ID='".UserStaffID()."' AND f.SYEAR='".UserSyear()."' ORDER BY f.ASSIGNED_DATE"),$functions);
	$i = 1;
	$RET = array();
	foreach ( (array) $salaries_RET as $salary)
	{
		$RET[ $i ] = $salary;
		$i++;
	}
	
	if (count($RET) && !$_REQUEST['print_statements'] && AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
		$columns = array('REMOVE' => '');
	else
		$columns = array();

	$columns += array('TITLE' => _('Salary'),'AMOUNT' => _('Amount'),'ASSIGNED_DATE' => _('Assigned'),'DUE_DATE' => _('Due'),'COMMENTS' => _('Comment'));
	if ( !$_REQUEST['print_statements'])
		$link['add']['html'] = array('REMOVE'=>button('add'),'TITLE'=>_makeSalariesTextInput('','TITLE'),'AMOUNT'=>_makeSalariesTextInput('','AMOUNT'),'ASSIGNED_DATE'=>ProperDate(DBDate()),'DUE_DATE'=>_makeSalariesDateInput('','DUE_DATE'),'COMMENTS'=>_makeSalariesTextInput('','COMMENTS'));
	if ( !$_REQUEST['print_statements'])
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
		if (AllowEdit())
			DrawHeader('',SubmitButton(_('Save')));
		$options = array();
	}
	else
		$options = array('center'=>false);

	ListOutput($RET,$columns,'Salary','Salaries',$link,array(),$options);

	if ( !$_REQUEST['print_statements'] && AllowEdit())
		echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
	echo '<br />';

	if ( !$_REQUEST['print_statements'])
	{
		$payments_total = DBGet(DBQuery("SELECT SUM(p.AMOUNT) AS TOTAL FROM ACCOUNTING_PAYMENTS p WHERE p.STAFF_ID='".UserStaffID()."' AND p.SYEAR='".UserSyear()."'"));

		$table = '<table class="align-right"><tr><td>'._('Total from Salaries').': '.'</td><td>'.Currency($salaries_total).'</td></tr>';

		$table .= '<tr><td>'._('Less').': '._('Total from Staff Payments').': '.'</td><td>'.Currency($payments_total[1]['TOTAL']).'</td></tr>';

		$table .= '<tr><td>'._('Balance').': <b>'.'</b></td><td><b>'.Currency(($salaries_total-$payments_total[1]['TOTAL']),'CR').'</b></td></tr></table>';

		DrawHeader('','',$table);

		echo '</form>';
	}
}
