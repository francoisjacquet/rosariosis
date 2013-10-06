<?php

$target = '19.00';
$warning = '5.00';
$warning_note = _('Your lunch account is getting low.  Please send in at least %P with your reminder slip.  THANK YOU!');
$negative_note = _('You now have a <B>negative balance</B> in your lunch account. Please send in the negative balance plus %T.  THANK YOU!');
$minimum = '-40.00';
$minimum_note = _('You now have a <b>negative balance</b> below the allowed minimum.  Please send in the negative balance plus %T.  THANK YOU!');

if($_REQUEST['staff_id'])
	unset($_REQUEST['staff_id']);
if($_SESSION['staff_id'])
{
	unset($_SESSION['staff_id']);
	echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; ajaxLink(menu_link);</script>';
}

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = "'".implode("','",$_REQUEST['st_arr'])."'";

		$school = DBGet(DBQuery("SELECT TITLE FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
		$school = $school[1]['TITLE'];

		$staffs = DBGet(DBQuery("SELECT s.STAFF_ID,s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.PROFILE,fsa.STATUS,fsa.BALANCE FROM STAFF s,FOOD_SERVICE_STAFF_ACCOUNTS fsa WHERE s.STAFF_ID IN (".$st_list.") AND fsa.STAFF_ID=s.STAFF_ID"));
		$handle = PDFStart();
		foreach($staffs as $staff)
		{
			$last_deposit = DBGet(DBQuery("SELECT (SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,to_char(fst.TIMESTAMP,'YYYY-MM-DD') AS DATE FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst WHERE fst.SHORT_NAME='DEPOSIT' AND fst.STAFF_ID='".$staff['STAFF_ID']."' AND SYEAR='".UserSyear()."' ORDER BY fst.TRANSACTION_ID DESC LIMIT 1"),array('DATE'=>'ProperDate'));
			$last_deposit = $last_deposit[1];

			if($staff['BALANCE'] < $minimum)
				reminder($staff,$school,$target,$last_deposit,$minimum_note);
			elseif($staff['BALANCE'] < 0)
				reminder($staff,$school,$target,$last_deposit,$negative_note);
			elseif($staff['BALANCE'] < $warning)
				reminder($staff,$school,$target,$last_deposit,$warning_note);

			echo '<!-- NEED 3in -->';
		}
		PDFStop($handle);
	}
	else
		BackPrompt(_('You must choose at least one user'));
}

if(empty($_REQUEST['modfunc']) || $_REQUEST['search_modfunc']=='list')
{
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&_ROSARIO_PDF=true" method="POST">';
		DrawHeader('',SubmitButton(_('Create Reminders for Selected Users')));
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STAFF_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" checked name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['new'] = true;
	$extra['options']['search'] = false;

	StaffWidgets('fsa_balance_warning');
	StaffWidgets('fsa_status');
	StaffWidgets('fsa_exists_Y');

	$extra['SELECT'] .= ',coalesce(fsa.STATUS,\'Active\') AS STATUS,fsa.BALANCE';
	$extra['SELECT'] .= ',(SELECT \'Y\' WHERE fsa.BALANCE < \''.$warning.'\' AND fsa.BALANCE >= 0) AS WARNING';
	$extra['SELECT'] .= ',(SELECT \'Y\' WHERE fsa.BALANCE < 0 AND fsa.BALANCE >= \''.$minimum.'\') AS NEGATIVE';
	$extra['SELECT'] .= ',(SELECT \'Y\' WHERE fsa.BALANCE < \''.$minimum.'\') AS MINIMUM';
	if(!mb_strpos($extra['FROM'],'fsa'))
	{
		$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fsa';
		$extra['WHERE'] .= ' AND fsa.STAFF_ID=s.STAFF_ID';
	}
	$extra['functions'] += array('BALANCE'=>'red','WARNING'=>'x','NEGATIVE'=>'x','MINIMUM'=>'x');
	$extra['columns_after'] = array('BALANCE'=>_('Balance'),'STATUS'=>_('Status'),'WARNING'=>_('Warning').'<BR />&lt; '.$warning,'NEGATIVE'=>_('Negative'),'MINIMUM'=>_('Minimum').'<BR />'.$minimum);

	Search('staff_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Create Reminders for Selected Users')).'</span>';
		echo '</FORM>';
	}
}

function reminder($staff,$school,$target,$last_deposit,$note)
{
	$payment = $target - $staff['BALANCE'];
	if($payment < 0)
		return;;
	$payment = number_format($payment,2);

	echo '<TABLE class="width-100p">';
	echo '<TR><TD colspan="3" class="center"><span class="sizep1"><I><B>'._('Payment Reminder').'</B></I></span></TD></TR>';
	echo '<TR><TD colspan="3" class="center"><B>'.$school.'</B></TD></TR>';

	echo '<TR><TD style="width:33%;">';
	echo $staff['FIRST_NAME'].' '.$staff['MIDDLE_NAME'].' '.$staff['LAST_NAME'].'<BR />';
	echo ''.$staff['STAFF_ID'].'';
	echo '</TD><TD style="width:33%;">';
	echo '&nbsp;<BR />';
	echo '&nbsp;';
	echo '</TD><TD style="width:33%;">';
	echo '&nbsp;<BR />';
	echo '&nbsp;';
	echo '</TD></TR>';

	echo '<TR><TD style="width:33%;">';
	echo ProperDate(DBDate()).'<BR />';
	echo ''._('Today\'s Date').'';
	echo '</TD><TD style="width:34%;">';
	echo ($last_deposit ? $last_deposit['DATE'] : _('None')).'<BR />';
	echo ''._('Date of Last Deposit').'';
	echo '</TD><TD style="width:33%;">';
	echo ($last_deposit ? $last_deposit['AMOUNT'] : _('None')).'<BR />';
	echo ''._('Amount of Last Deposit').'';
	echo '</TD></TR>';

	echo '<TR><TD style="width:33%;">';
	echo ($staff['BALANCE']<0 ? '<B>'.Currency($staff['BALANCE']).'</B>' : Currency($staff['BALANCE'])).'<BR />';
	echo ''._('Balance').'';
	echo '</TD><TD style="width:33%;">';
	echo '<B>'.Currency($payment).'</B><BR />';
	echo '<B>'._('Mimimum Payment').'</B>';
	echo '</TD><TD style="width:33%;">';
	echo ucfirst($staff['PROFILE']).'<BR />';
	echo ''._('Profile').'';
	echo '</TD></TR>';

	$note = str_replace('%F',$staff['FIRST_NAME'],$note);
//	$note = str_replace('%P',money_format('%i',$payment),$note);
	$note = str_replace('%P',Currency($payment),$note);
	$note = str_replace('%T',$target,$note);

	echo '<TR><TD colspan="3">';
	echo '<BR />'.$note.'<BR />';
	echo '</TD></TR>';
	echo '<TR><TD colspan="3"><BR /><BR /><HR><BR /><BR /></TD></TR></TABLE>';
}
?>
