<?php

//modif Francois: fix error Warning: Missing argument 2 for StaffWidgets()
//function StaffWidgets($item,&$myextra)
function StaffWidgets($item,&$myextra=NULL)
{	global $extra,$_ROSARIO,$RosarioModules;

	if(isset($myextra))
		$extra =& $myextra;

	if(!is_array($_ROSARIO['StaffWidgets']))
		$_ROSARIO['StaffWidgets'] = array();

	if(!is_array($extra['functions']))
		$extra['functions'] = array();

	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && !$_ROSARIO['StaffWidgets'][$item])
	{
		switch($item)
		{
			case 'all':
//modif Francois: css WPadmin
//				$extra['search'] .= '<TR><TD>';
				$extra['search'] .= '<TR><TD><TABLE style="border-collapse:separate; border-spacing:2px" class="width-100p cellpadding-2">';

				if($RosarioModules['Users'] && (!$_ROSARIO['StaffWidgets']['permissions']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'users_table\');" href="#"><IMG SRC="assets/arrow_right.gif" id="users_table_arrow" height="12"> <B>'._('Users').'</B></A><BR /><TABLE id="users_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					StaffWidgets('permissions',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				if($RosarioModules['Food_Service'] && (!$_ROSARIO['StaffWidgets']['fsa_balance'] || !$_ROSARIO['StaffWidgets']['fsa_status'] || !$_ROSARIO['StaffWidgets']['fsa_barcode']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'food_service_table\');" href="#"><IMG SRC="assets/arrow_right.gif" id="food_service_table_arrow" height="12"> <B>'._('Food Service').'</B></A><BR /><TABLE id="food_service_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					StaffWidgets('fsa_balance',$extra);
					StaffWidgets('fsa_status',$extra);
					StaffWidgets('fsa_barcode',$extra);
					StaffWidgets('fsa_exists',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				$extra['search'] .= '</TABLE></TD></TR>';
			break;

			case 'user':
				$widgets_RET = DBGet(DBQuery("SELECT TITLE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='StaffWidgetsSearch'".(count($_ROSARIO['StaffWidgets'])?" AND TITLE NOT IN ('".implode("','",array_keys($_ROSARIO['StaffWidgets']))."')":'')));
				foreach($widgets_RET as $widget)
					StaffWidgets($widget['TITLE'],$extra);
			break;

			case 'permissions_Y':
			case 'permissions_N':
				$value = mb_substr($item,12);
				$item = 'permissions';
			case 'permissions':
				if($RosarioModules['Users'])
				{
				if($_REQUEST['permissions'])
				{
					$extra['WHERE'] .= " AND s.PROFILE_ID IS ".($_REQUEST['permissions']=='Y'?'NOT':'')." NULL AND s.PROFILE!='none'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.Localize('colon',_('Permissions')).' </b></span>'.($_REQUEST['permissions']=='Y'?_('Profile'):_('Custom')).'<BR />';
				}
//modif Francois: add <label> on radio
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Permissions').'</TD><TD><label><INPUT type="radio" name="permissions" value=""'.(!$value?' checked':'').'> '._('All').'</label> &nbsp;<label><INPUT type="radio" name="permissions" value="Y"'.($value=='Y'?' checked':'').'> '._('Profile').'</label> &nbsp;<label><INPUT type="radio" name="permissions" value="N"'.($value=='N'?' checked':'').'> '._('Custom').'</label></TD></TR>';
				}
			break;

			case 'fsa_balance_warning':
				$value = $GLOBALS['warning'];
				$item = 'fsa_balance';
			case 'fsa_balance':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_balance']!='')
				{
					if (!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}
					$extra['WHERE'] .= " AND fssa.BALANCE".($_REQUEST['fsa_bal_gt']=='Y'?'>=':'<')."'".round($_REQUEST['fsa_balance'],2)."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.Localize('colon',_('Food Service Balance')).' </b></span><span class="sizep2">'.($_REQUEST['fsa_bal_ge']=='Y'?'&ge;':'&lt;').'</span>'.number_format($_REQUEST['fsa_balance'],2).'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Balance').'</TD><TD><table class="cellpadding-0 cellspacing-0"><tr><td><label><span class="sizep2">&lt;</span> <INPUT type="radio" name="fsa_bal_ge" value="" checked /></label></td><td rowspan="2"><INPUT type="text" name="fsa_balance" size="10"'.($value?' value="'.$value.'"':'').'></td></tr><tr><td><label><span class="sizep2">&ge;</span> <INPUT type="radio" name="fsa_bal_ge" value="Y"></label></td></tr></table></TD></TR>';
				}
			break;

			case 'fsa_status_active':
				$value = 'active';
				$item = 'fsa_status';
			case 'fsa_status':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_status'])
				{
					if (!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}
					if($_REQUEST['fsa_status']=='Active')
						$extra['WHERE'] .= ' AND fssa.STATUS IS NULL';
					else
						$extra['WHERE'] .= ' AND fssa.STATUS=\''.$_REQUEST['fsa_status'].'\'';
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.Localize('colon',_('Food Service Status')).' </b></span>'.$_REQUEST['fsa_status'].'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Account Status').'</TD><TD><SELECT name="fsa_status"><OPTION value="">'._('Not Specified').'</OPTION><OPTION value="Active"'.($value=='active'?' SELECTED="SELECTED"':'').'>'._('Active').'</OPTION><OPTION value="Inactive">'._('Inactive').'</OPTION><OPTION value="Disabled">'._('Disabled').'</OPTION><OPTION value="Closed">'._('Closed').'</OPTION></SELECT></TD></TR>';
				}
			break;

			case 'fsa_barcode':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_barcode'])
				{
					if (!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}
					$extra['WHERE'] .= ' AND fssa.BARCODE=\''.$_REQUEST['fsa_barcode'].'\'';
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.Localize('colon',_('Food Service Barcode')).' </b></span>'.$_REQUEST['fsa_barcode'].'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Barcode').'</TD><TD><INPUT type="text" name="fsa_barcode" size="15"></TD></TR>';
				}
			break;

			case 'fsa_exists_N':
			case 'fsa_exists_Y':
				$value = mb_substr($item,11);
				$item = 'fsa_exists';
			case 'fsa_exists':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_exists'])
				{
					$extra['WHERE'] .= ' AND '.($_REQUEST['fsa_exists']=='N'?'NOT ':'').'EXISTS (SELECT \'exists\' FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID)';
					if(!$extra['NoSearchTerms'])
//modif Francois: add translation
						$_ROSARIO['SearchTerms'] .= Localize('colon',_('Food Service Account Exists')).' '.($_REQUEST['fsa_exists']=='Y'?_('Yes'):_('No')).'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Has Account').'</TD><TD><label><INPUT type="radio" name="fsa_exists" value=""'.(!$value?' checked':'').'>'._('All').'</label> <label><INPUT type="radio" name="fsa_exists" value="Y"'.($value=='Y'?' checked':'').'>'._('Yes').'</label> <label><INPUT type="radio" name="fsa_exists" value="N"'.($value=='N'?' checked':'').'>'._('No').'</label></TD></TR>';
				}
			break;
		}
		$_ROSARIO['StaffWidgets'][$item] = true;
	}
}
?>
