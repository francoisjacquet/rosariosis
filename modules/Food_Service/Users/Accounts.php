<?php

if($_REQUEST['modfunc']=='update')
{
    if(UserStaffID() && AllowEdit())
    {
        if($_REQUEST['submit']['delete'])
        {
//modif Francois: add translation
            if(DeletePromptX(_('User Account')))
                DBQuery('DELETE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID='.UserStaffID());
            //unset($_REQUEST['submit']);
        }
        else
        {
            if(count($_REQUEST['food_service']))
            {
                if($_REQUEST['food_service']['BARCODE'])
                {
                    $RET = DBGet(DBQuery("SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE'])."' AND STAFF_ID!='".UserStaffID()."'"));
                    if($RET)
                    {
                        $staff_RET = DBGet(DBQuery("SELECT FIRST_NAME||' '||LAST_NAME AS FULL_NAME FROM STAFF WHERE STAFF_ID='".$RET[1]['STAFF_ID']."'"));
                        $question = _("Are you sure you want to assign that barcode?");
                        $message = sprintf(_("That barcode is already assigned to User <B>%s</B>."),$staff_RET[1]['FULL_NAME']).' '._("Hit OK to reassign it to the current user or Cancel to cancel all changes.");
                    }
                    else
                    {
                        $RET = DBGet(DBQuery("SELECT ACCOUNT_ID FROM FOOD_SERVICE_STUDENT_ACCOUNTS WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE']))."'");
                        if($RET)
                        {
                            $student_RET = DBGet(DBQuery("SELECT s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa WHERE s.STUDENT_ID=fssa.STUDENT_ID AND fssa.ACCOUNT_ID='".$RET[1]['ACCOUNT_ID']."'"));
                            $question = _("Are you sure you want to assign that barcode?");
                            $message = sprintf(_("That barcode is already assigned to Student <B>%s</B>."),$student_RET[1]['FULL_NAME']).' '._("Hit OK to reassign it to the user student or Cancel to cancel all changes.");
                        }
                    }
                }
                if(!$RET || PromptX($title='Confirm',$question,$message))
                {
                    $sql = 'UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET ';
                    foreach($_REQUEST['food_service'] as $column_name=>$value)
                        $sql .= $column_name."='".trim($value)."',";
                    $sql = mb_substr($sql,0,-1)." WHERE STAFF_ID='".UserStaffID()."'";
                    if($_REQUEST['food_service']['BARCODE'])
                    {
                        DBQuery("UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE'])."'");
                        DBQuery("UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET BARCODE=NULL WHERE BARCODE='".trim($_REQUEST['food_service']['BARCODE'])."'");
                    }
                    DBQuery($sql);
                    unset($_REQUEST['modfunc']);
                    unset($_REQUEST['food_service']);
                    unset($_SESSION['_REQUEST_vars']['food_service']);
                }
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

if($_REQUEST['modfunc']=='create')
{
	if(UserStaffID() && AllowEdit())
	{
        $fields = 'STAFF_ID,BALANCE,TRANSACTION_ID,';
        $values = "'".UserStaffID()."','0.00','0',";
        foreach($_REQUEST['food_service'] as $column_name=>$value)
        {
            $fields .= $column_name.',';
            $values .= "'".trim($value)."',";
        }
        $sql = 'INSERT INTO FOOD_SERVICE_STAFF_ACCOUNTS ('.mb_substr($fields,0,-1).') values ('.mb_substr($values,0,-1).')';
        DBQuery($sql);
	}
	unset($_REQUEST['modfunc']);
}

StaffWidgets('fsa_balance');
StaffWidgets('fsa_status');
StaffWidgets('fsa_barcode');
StaffWidgets('fsa_exists_Y');

$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE";
$extra['SELECT'] .= ",(SELECT coalesce(STATUS,'"._('Active')."') FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS";
$extra['functions'] += array('BALANCE'=>'red');
$extra['columns_after'] = array('BALANCE'=>_('Balance'),'STATUS'=>_('Status'));

Search('staff_id',$extra);

if(!$_REQUEST['modfunc'] && UserStaffID())
{
	$staff = DBGet(DBQuery("SELECT s.STAFF_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME,(SELECT s.STAFF_ID FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,(SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE,(SELECT BARCODE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BARCODE FROM STAFF s WHERE s.STAFF_ID='".UserStaffID()."'"));
	$staff = $staff[1];

	if($staff['ACCOUNT_ID'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';
		DrawHeader('',SubmitButton(_('Save'),'submit[save]').($staff['BALANCE'] == 0 ? SubmitButton(_('Delete Account'),'submit[delete]') : ''));
	}
	else
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=create" method="POST">';
		DrawHeader('',SubmitButton(_('Create Account')));
	}

	echo '<BR />';
	PopTable('header',_('Account Information'),'width="100%"');
        echo '<TABLE class="width-100p">';
        echo '<TR>';
        echo '<TD class="valign-top">';
        echo '<TABLE class="width-100p"><TR>';

	echo '<TD class="valign-top">'.NoInput($staff['FULL_NAME'],$staff['STAFF_ID']);
	if(!$staff['ACCOUNT_ID'])
	{
		$warning = _('This user does not have a Meal Account.');
		echo '<BR />'.button('warning','','"#" onMouseOver=\'stm(["'._('Warning').'","'.str_replace('"','\"',str_replace("'",'&#39;',$warning)).'"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'');
	}
	echo '</TD>';

        echo '<TD class="valign-top">'.NoInput(red($staff['BALANCE']),_('Balance')).'</TD>';

        echo '</TR></TABLE>';
        echo '</TD></TR></TABLE>';
        echo '<HR>';

	echo '<TABLE class="width-100p cellspacing-0 cellpadding-0">';
	echo '<TR><TD class="valign-top">';

	echo '<TABLE class="width-100p cellpadding-6">';
	echo '<TR>';
	echo '<TD>';
	$options = array('Inactive'=>_('Inactive'),'Disabled'=>_('Disabled'),'Closed'=>_('Closed'));
	echo ($staff['ACCOUNT_ID']?SelectInput($staff['STATUS'],'food_service[STATUS]',_('Status'),$options,_('Active')):NoInput('-',_('Status')));
	echo '</TD>';
	echo '<TD>';
	echo ($staff['ACCOUNT_ID']?TextInput($staff['BARCODE'],'food_service[BARCODE]',_('Barcode'),'size=12 maxlength=25'):NoInput('-',_('Barcode')));
	echo '</TD>';
	echo '</TR>';
	echo '</TABLE>';

	echo '</TD></TR>';
	echo '</TABLE>';
	PopTable('footer');
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}
?>
