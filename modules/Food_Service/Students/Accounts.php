<?php

if($_REQUEST['modfunc']=='update')
{
    if(UserStudentID() && AllowEdit())
    {
        if(count($_REQUEST['food_service']))
        {
            if($_REQUEST['food_service']['BARCODE'])
            {
                $RET = DBGet(DBQuery("SELECT ACCOUNT_ID FROM FOOD_SERVICE_STUDENT_ACCOUNTS WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE'])."' AND STUDENT_ID!='".UserStudentID()."'"));
                if($RET)
                {
                    $student_RET = DBGet(DBQuery("SELECT s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa WHERE s.STUDENT_ID=fssa.STUDENT_ID AND fssa.ACCOUNT_ID='".$RET[1]['ACCOUNT_ID']."'"));
                    $question = _("Are you sure you want to assign that barcode?");
                    $message = sprintf(_("That barcode is already assigned to Student <B>%s</B>."),$student_RET[1]['FULL_NAME']).' '._("Hit OK to reassign it to the current student or Cancel to cancel all changes.");
                }
                else
                {
                    $RET = DBGet(DBQuery("SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE'])."'"));
                    if($RET)
                    {
                        $staff_RET = DBGet(DBQuery("SELECT FIRST_NAME||' '||LAST_NAME AS FULL_NAME FROM STAFF WHERE STAFF_ID='".$RET[1]['STAFF_ID']."'"));
                        $question = _("Are you sure you want to assign that barcode?");
                        $message = sprintf(_("That barcode is already assigned to User <B>%s</B>."),$staff_RET[1]['FULL_NAME']).' '._("Hit OK to reassign it to the current student or Cancel to cancel all changes.");
                    }
                }
            }
            if(!$RET || PromptX($title='Confirm',$question,$message))
            {
                if (is_numeric($_REQUEST['food_service']['ACCOUNT_ID']) && intval($_REQUEST['food_service']['ACCOUNT_ID'])>=0)
				{
					$sql = "UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET ";
					foreach($_REQUEST['food_service'] as $column_name=>$value)
					{
						$sql .= $column_name."='".trim($value)."',";
					}
					$sql = mb_substr($sql,0,-1)." WHERE STUDENT_ID='".UserStudentID()."'";
					if($_REQUEST['food_service']['BARCODE'])
					{
						DBQuery("UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE'])."'");
						DBQuery("UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE'])."'");
					}
					DBQuery($sql);
				}
				else
					$error = ErrorMessage(array(_('Please enter valid Numeric data.')));
                unset($_REQUEST['modfunc']);
                unset($_REQUEST['food_service']);
                unset($_SESSION['_REQUEST_vars']['food_service']);
            }
        }
    }
    else
    {
        unset($_REQUEST['modfunc']);
        unset($_REQUEST['food_service']);
        unset($_SESSION['_REQUEST_vars']['food_service']);
    }
}

Widgets('fsa_discount');
Widgets('fsa_status');
Widgets('fsa_barcode');
Widgets('fsa_account_id');

$extra['SELECT'] .= ",coalesce(fssa.STATUS,'"._('Active')."') AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE";
if(!mb_strpos($extra['FROM'],'fssa'))
{
	$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
	$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
}
$extra['functions'] += array('BALANCE'=>'red');
$extra['columns_after'] = array('BALANCE'=>_('Balance'),'STATUS'=>_('Status'));

Search('student_id',$extra);

if(!$_REQUEST['modfunc'] && UserStudentID())
{
	$student = DBGet(DBQuery("SELECT s.STUDENT_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME,fssa.ACCOUNT_ID,fssa.STATUS,fssa.DISCOUNT,fssa.BARCODE,(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa WHERE s.STUDENT_ID='".UserStudentID()."' AND fssa.STUDENT_ID=s.STUDENT_ID"));
	$student = $student[1];

	// find other students associated with the same account
	$xstudents = DBGet(DBQuery("SELECT s.STUDENT_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa WHERE fssa.ACCOUNT_ID='".$student['ACCOUNT_ID']."' AND s.STUDENT_ID=fssa.STUDENT_ID AND s.STUDENT_ID!='".UserStudentID()."'".($_REQUEST['include_inactive']?'':" AND exists(SELECT '' FROM STUDENT_ENROLLMENT WHERE STUDENT_ID=s.STUDENT_ID AND SYEAR='".UserSyear()."' AND (START_DATE<=CURRENT_DATE AND (END_DATE IS NULL OR CURRENT_DATE<=END_DATE)))")));

	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';

	DrawHeader('<label>'.CheckBoxOnclick('include_inactive').' '._('Include Inactive Students in Shared Account').'</label>',SubmitButton(_('Save')));

	echo '<BR />';
//modif Francois: fix SQL bug invalid numeric data
	if(isset($error)) echo $error;
	PopTable('header',_('Account Information'),'width="100%"');
	echo '<TABLE class="width-100p">';
	echo '<TR>';
	echo '<TD class="valign-top">';
	echo '<TABLE class="width-100p"><TR>';

	echo '<TD class="valign-top">'.NoInput($student['FULL_NAME'],'<b>'.$student['STUDENT_ID'].'</b>').'</TD>';
	echo '<TD class="valign-top">'.NoInput(red($student['BALANCE']),_('Balance')).'</TD>';

	echo '</TR></TABLE>';
	echo '</TD></TR></TABLE>';
	echo '<HR>';

	echo '<TABLE class="width-100p cellspacing-0 cellpadding-0">';
	echo '<TR><TD class="valign-top">';

	echo '<TABLE class="width-100p cellpadding-6">';
	echo '<TR>';
	echo '<TD>';
	// warn if account non-existent (balance query failed)
	if($student['BALANCE']=='')
	{
		//var_dump($student['ACCOUNT_ID']);
		echo TextInput(array($student['ACCOUNT_ID'],'<span style="color:red">'.$student['ACCOUNT_ID'].'</span>'),'food_service[ACCOUNT_ID]',_('Account ID'),'size=12 maxlength=10');
		$warning = _('Non-existent account!');
		echo button('warning','','"#" onMouseOver=\'stm(["'._('Warning').'","'.str_replace('"','\"',str_replace("'",'&#39;',$warning)).'"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'');
	}
	else
	 	echo TextInput($student['ACCOUNT_ID'],'food_service[ACCOUNT_ID]',_('Account ID'),'size=12 maxlength=10');
	// warn if other students associated with the same account
	if(count($xstudents))
	{
		$warning = Localize('colon',_('Other students associated with the same account')).'<BR />';
		foreach($xstudents as $xstudent)
			$warning .= '&nbsp;'.str_replace('\'','&#39;',$xstudent['FULL_NAME']).'<BR />';
		echo button('warning','','"#" onMouseOver=\'stm(["'._('Warning').'","'.str_replace('"','\"',str_replace("'",'&#39;',$warning)).'"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'');
	}
	echo '</TD>';
	$options = array('Inactive'=>_('Inactive'),'Disabled'=>_('Disabled'),'Closed'=>_('Closed'));
	echo '<TD>'.SelectInput($student['STATUS'],'food_service[STATUS]',_('Status'),$options,_('Active')).'</TD>';
	echo '</TR><TR>';
	$options = array('Reduced'=>_('Reduced'),'Free'=>_('Free'));
	echo '<TD>'.SelectInput($student['DISCOUNT'],'food_service[DISCOUNT]',_('Discount'),$options,_('Full')).'</TD>';
	echo '<TD>'.TextInput($student['BARCODE'],'food_service[BARCODE]',_('Barcode'),'size=12 maxlength=25').'</TD>';
	echo '</TR>';
	echo '</TABLE>';

	echo '</TD></TR>';
	echo '</TABLE>';
	PopTable('footer');
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}
?>