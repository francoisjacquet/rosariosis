<?php
$food_service_config = ProgramConfig( 'food_service' );

$target = $food_service_config['FOOD_SERVICE_BALANCE_TARGET'][1]['VALUE'];
$warning = $food_service_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'];
$warning_note = _('Your lunch account is getting low.  Please send in at least %P with your reminder slip.  THANK YOU!');
$negative_note = _('You now have a <b>negative balance</b> in your lunch account. Please send in the negative balance plus %T.  THANK YOU!');
$minimum = $food_service_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'];
$minimum_note = _('You now have a <b>negative balance</b> below the allowed minimum.  Please send in the negative balance plus %T.  THANK YOU!');

if ( $_REQUEST['staff_id'] )
{
	// Unset staff ID & redirect URL.
	RedirectURL( 'staff_id' );
}

if ( UserStaffID() )
{
	unset( $_SESSION['staff_id'] );
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	if (count($_REQUEST['st_arr']))
	{
		$st_list = "'".implode("','",$_REQUEST['st_arr'])."'";

		$school = SchoolInfo( 'TITLE' );

		$staffs = DBGet(DBQuery("SELECT s.STAFF_ID,s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.PROFILE,fsa.STATUS,fsa.BALANCE FROM STAFF s,FOOD_SERVICE_STAFF_ACCOUNTS fsa WHERE s.STAFF_ID IN (".$st_list.") AND fsa.STAFF_ID=s.STAFF_ID"));
		$handle = PDFStart();
		foreach ( (array) $staffs as $staff)
		{
			$last_deposit = DBGet(DBQuery("SELECT
			(SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			to_char(fst.TIMESTAMP,'YYYY-MM-DD') AS DATE
			FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst
			WHERE fst.SHORT_NAME='DEPOSIT'
			AND fst.STAFF_ID='".$staff['STAFF_ID']."'
			AND SYEAR='".UserSyear()."'
			ORDER BY fst.TRANSACTION_ID DESC LIMIT 1"),array('DATE' => 'ProperDate'));
			$last_deposit = $last_deposit[1];

			if ( $staff['BALANCE'] < $minimum)
				reminder($staff,$school,$target,$last_deposit,$minimum_note);
			elseif ( $staff['BALANCE'] < 0)
				reminder($staff,$school,$target,$last_deposit,$negative_note);
			elseif ( $staff['BALANCE'] < $warning)
				reminder($staff,$school,$target,$last_deposit,$warning_note);

			echo '<!-- NEED 3in -->';
		}
		PDFStop($handle);
	}
	else
		BackPrompt(_('You must choose at least one user'));
}

if (! $_REQUEST['modfunc'] || $_REQUEST['search_modfunc']=='list')
{
	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&_ROSARIO_PDF=true" method="POST">';
		DrawHeader('',SubmitButton(_('Create Reminders for Selected Users')));
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STAFF_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX' => '</a><input type="checkbox" value="Y" checked name="controller" onclick="checkAll(this.form,this.checked,\'st_arr\');"><A>');
	$extra['new'] = true;
	$extra['options']['search'] = false;

	StaffWidgets('fsa_balance_warning');
	StaffWidgets('fsa_status');
	StaffWidgets('fsa_exists_Y');

	$status = DBEscapeString( _( 'Active' ) );

	$extra['SELECT'] .= ",coalesce(fsa.STATUS,'" . $status . "') AS STATUS,fsa.BALANCE";
	$extra['SELECT'] .= ",(SELECT 'Y' WHERE fsa.BALANCE < '" . $warning . "' AND fsa.BALANCE >= 0) AS WARNING";
	$extra['SELECT'] .= ",(SELECT 'Y' WHERE fsa.BALANCE < 0 AND fsa.BALANCE >= '" . $minimum . "') AS NEGATIVE";
	$extra['SELECT'] .= ",(SELECT 'Y' WHERE fsa.BALANCE < '" . $minimum . "') AS MINIMUM";

	if ( !mb_strpos($extra['FROM'],'fsa'))
	{
		$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fsa';
		$extra['WHERE'] .= ' AND fsa.STAFF_ID=s.STAFF_ID';
	}
	$extra['functions'] += array('BALANCE' => 'red','WARNING' => 'x','NEGATIVE' => 'x','MINIMUM' => 'x');
	$extra['columns_after'] = array('BALANCE' => _('Balance'),'STATUS' => _('Status'),'WARNING' => _('Warning').'<br />&lt; '.$warning,'NEGATIVE' => _('Negative'),'MINIMUM' => _('Minimum').'<br />'.$minimum);

	Search('staff_id',$extra);
	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<br /><div class="center">' . SubmitButton(_('Create Reminders for Selected Users')) . '</div>';
		echo '</form>';
	}
}

function reminder($staff,$school,$target,$last_deposit,$note)
{
	$payment = $target - $staff['BALANCE'];
	if ( $payment < 0)
		return;;
	$payment = number_format($payment,2);

	echo '<table class="width-100p">';
	echo '<tr><td colspan="3" class="center"><span class="sizep1"><i><b>'._('Payment Reminder').'</b></i></span></td></tr>';
	echo '<tr><td colspan="3" class="center"><b>'.$school.'</b></td></tr>';

	echo '<tr><td style="width:33%;">';
	echo $staff['FIRST_NAME'].' '.$staff['MIDDLE_NAME'].' '.$staff['LAST_NAME'].'<br />';
	echo ''.$staff['STAFF_ID'].'';
	echo '</td><td style="width:33%;">';
	echo '&nbsp;<br />';
	echo '&nbsp;';
	echo '</td><td style="width:33%;">';
	echo '&nbsp;<br />';
	echo '&nbsp;';
	echo '</td></tr>';

	echo '<tr><td style="width:33%;">';
	echo ProperDate(DBDate()).'<br />';
	echo ''._('Today\'s Date').'';
	echo '</td><td style="width:34%;">';
	echo ($last_deposit ? $last_deposit['DATE'] : _('None')).'<br />';
	echo ''._('Date of Last Deposit').'';
	echo '</td><td style="width:33%;">';
	echo ($last_deposit ? $last_deposit['AMOUNT'] : _('None')).'<br />';
	echo ''._('Amount of Last Deposit').'';
	echo '</td></tr>';

	echo '<tr><td style="width:33%;">';
	echo ($staff['BALANCE']<0 ? '<b>'.Currency($staff['BALANCE']).'</b>' : Currency($staff['BALANCE'])).'<br />';
	echo ''._('Balance').'';
	echo '</td><td style="width:33%;">';
	echo '<b>'.Currency($payment).'</b><br />';
	echo '<b>'._('Mimimum Payment').'</b>';
	echo '</td><td style="width:33%;">';
	echo ucfirst($staff['PROFILE']).'<br />';
	echo ''._('Profile').'';
	echo '</td></tr>';

	$note = str_replace('%F',$staff['FIRST_NAME'],$note);
//	$note = str_replace('%P',money_format('%i',$payment),$note);
	$note = str_replace('%P',Currency($payment),$note);
	$note = str_replace('%T',$target,$note);

	echo '<tr><td colspan="3">';
	echo '<br />'.$note.'<br />';
	echo '</td></tr>';
	echo '<tr><td colspan="3"><br /><br /><hr /><br /><br /></td></tr></table>';
}
