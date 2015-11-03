<?php

//***********************************************
// TODO: follow Widgets() function formatting !!
// **********************************************

//FJ fix error Warning: Missing argument 2 for StaffWidgets()
//function StaffWidgets($item,&$myextra)
function StaffWidgets($item,&$myextra=NULL)
{	global $extra,$_ROSARIO,$RosarioModules;

	if (isset($myextra))
		$extra =& $myextra;

	if ( !isset($_ROSARIO['StaffWidgets']) || !is_array($_ROSARIO['StaffWidgets']))
		$_ROSARIO['StaffWidgets'] = array();

	if ( !isset($extra['functions']) || !is_array($extra['functions']))
		$extra['functions'] = array();

	if ((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && !$_ROSARIO['StaffWidgets'][$item])
	{
		switch ( $item)
		{
			case 'all':

				//FJ regroup widgets wrap
				$widget_wrap_header = function($title)
				{
					return '<a onclick="switchMenu(this); return false;" href="#" class="switchMenu"><b>'.$title.'</b></a>
					<br />
					<table class="widefat width-100p cellspacing-0 col1-align-right hide">';
				};

				$widget_wrap_footer = '</table>';

				if ( $RosarioModules['Users'] && (!$_ROSARIO['StaffWidgets']['permissions']))
				{
					$extra['search'] .= $widget_wrap_header(_('Users'));

					StaffWidgets('permissions',$extra);

					$extra['search'] .= $widget_wrap_footer;
				}

				if ( $RosarioModules['Food_Service'] && (!$_ROSARIO['StaffWidgets']['fsa_balance'] || !$_ROSARIO['StaffWidgets']['fsa_status'] || !$_ROSARIO['StaffWidgets']['fsa_barcode']))
				{
					$extra['search'] .= $widget_wrap_header(_('Food Service'));

					StaffWidgets('fsa_balance',$extra);
					StaffWidgets('fsa_status',$extra);
					StaffWidgets('fsa_barcode',$extra);
					StaffWidgets('fsa_exists',$extra);

					$extra['search'] .= $widget_wrap_footer;
				}

				if ( $RosarioModules['Accounting'] && (!$_ROSARIO['Widgets']['staff_balance']) && AllowUse('Accounting/StaffBalances.php'))
				{
					$extra['search'] .= $widget_wrap_header(_('Accounting'));

					StaffWidgets('staff_balance',$extra);

					$extra['search'] .= $widget_wrap_footer;
				}

			break;

			case 'user':
				$widgets_RET = DBGet(DBQuery("SELECT TITLE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='StaffWidgetsSearch'".(count($_ROSARIO['StaffWidgets'])?" AND TITLE NOT IN ('".implode("','",array_keys($_ROSARIO['StaffWidgets']))."')":'')));

				foreach ( (array)$widgets_RET as $widget)
					StaffWidgets($widget['TITLE'],$extra);
			break;

			case 'permissions_Y':
			case 'permissions_N':
				$value = mb_substr($item,12);
				$item = 'permissions';

			case 'permissions':
				if ( $RosarioModules['Users'])
				{
				if ( $_REQUEST['permissions'])
				{
					$extra['WHERE'] .= " AND s.PROFILE_ID IS ".($_REQUEST['permissions']=='Y'?'NOT':'')." NULL AND s.PROFILE!='none'";

					if ( !$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Permissions').': </b>'.($_REQUEST['permissions']=='Y'?_('Profile'):_('Custom')).'<br />';
				}

				$extra['search'] .= '<tr class="st"><td>
				'._('Permissions').'
				</td><td>
				<label><input type="radio" name="permissions" value=""'.(!$value?' checked':'').'> '._('All').'</label> &nbsp;
				<label><input type="radio" name="permissions" value="Y"'.($value=='Y'?' checked':'').'> '._('Profile').'</label> &nbsp;
				<label><input type="radio" name="permissions" value="N"'.($value=='N'?' checked':'').'> '._('Custom').'</label>
				</td></tr>';
				}
			break;

			case 'fsa_balance_warning':
				$value = $GLOBALS['warning'];
				$item = 'fsa_balance';

			case 'fsa_balance':
				if ( $RosarioModules['Food_Service'])
				{
				if ( $_REQUEST['fsa_balance']!='')
				{
					if ( !mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}

					$extra['WHERE'] .= " AND fssa.BALANCE".($_REQUEST['fsa_bal_gt']=='Y'?'>=':'<')."'".round($_REQUEST['fsa_balance'],2)."'";

					if ( !$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Food Service Balance').': </b><span class="sizep2">'.($_REQUEST['fsa_bal_ge']=='Y'?'&ge;':'&lt;').'</span>'.number_format($_REQUEST['fsa_balance'],2).'<br />';
				}

				$extra['search'] .= '<tr class="st"><td>
				'._('Balance').'
				</td><td>
				<table class="cellspacing-0"><tr><td>
				<label><span class="sizep2">&lt;</span> <input type="radio" name="fsa_bal_ge" value="" checked /></label>
				</td><td rowspan="2">
				<input type="text" name="fsa_balance" size="10"'.($value?' value="'.$value.'"':'').'>
				</td></tr><tr><td>
				<label><span class="sizep2">&ge;</span> <input type="radio" name="fsa_bal_ge" value="Y"></label>
				</td></tr></table>
				</td></tr>';
				}
			break;

			case 'fsa_status_active':
				$value = 'active';
				$item = 'fsa_status';

			case 'fsa_status':
				if ( $RosarioModules['Food_Service'])
				{
				if ( $_REQUEST['fsa_status'])
				{
					if ( !mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}

					if ( $_REQUEST['fsa_status']=='Active')
						$extra['WHERE'] .= ' AND fssa.STATUS IS NULL';
					else
						$extra['WHERE'] .= ' AND fssa.STATUS=\''.$_REQUEST['fsa_status'].'\'';

					if ( !$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Food Service Status').': </b>'.$_REQUEST['fsa_status'].'<br />';
				}

				$extra['search'] .= '<tr class="st"><td>
				'._('Account Status').'
				</td><td>
				<select name="fsa_status">
				<option value="">'._('Not Specified').'</option>
				<option value="Active"'.($value=='active'?' SELECTED':'').'>'._('Active').'</option>
				<option value="Inactive">'._('Inactive').'</option>
				<option value="Disabled">'._('Disabled').'</option>
				<option value="Closed">'._('Closed').'</option>
				</select>
				</td></tr>';
				}
			break;

			case 'fsa_barcode':
				if ( $RosarioModules['Food_Service'])
				{
				if ( $_REQUEST['fsa_barcode'])
				{
					if ( !mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
					}

					$extra['WHERE'] .= ' AND fssa.BARCODE=\''.$_REQUEST['fsa_barcode'].'\'';

					if ( !$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Food Service Barcode').': </b>'.$_REQUEST['fsa_barcode'].'<br />';
				}

				$extra['search'] .= '<tr class="st"><td>
				'._('Barcode').'
				</td><td>
				<input type="text" name="fsa_barcode" size="15" />
				</td></tr>';
				}
			break;

			case 'fsa_exists_N':
			case 'fsa_exists_Y':
				$value = mb_substr($item,11);
				$item = 'fsa_exists';

			case 'fsa_exists':
				if ( $RosarioModules['Food_Service'])
				{
				if ( $_REQUEST['fsa_exists'])
				{
					$extra['WHERE'] .= ' AND '.($_REQUEST['fsa_exists']=='N'?'NOT ':'').'EXISTS (SELECT \'exists\' FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID)';

					if ( !$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= _('Food Service Account Exists').': '.($_REQUEST['fsa_exists']=='Y'?_('Yes'):_('No')).'<br />';
				}

				$extra['search'] .= '<tr class="st"><td>
				'._('Has Account').'
				</td><td><label><input type="radio" name="fsa_exists" value=""'.(!$value?' checked':'').'> '._('All').'</label> 
				<label><input type="radio" name="fsa_exists" value="Y"'.($value=='Y'?' checked':'').'> '._('Yes').'</label> 
				<label><input type="radio" name="fsa_exists" value="N"'.($value=='N'?' checked':'').'> '._('No').'</label>
				</td></tr>';
				}
			break;
			
			case 'staff_balance':
				if ( $RosarioModules['Accounting'] && AllowUse('Accounting/StaffBalances.php'))
				{
				if (is_numeric($_REQUEST['balance_low']) && is_numeric($_REQUEST['balance_high']))
				{
					if ( $_REQUEST['balance_low'] > $_REQUEST['balance_high'])
					{
						$temp = $_REQUEST['balance_high'];
						$_REQUEST['balance_high'] = $_REQUEST['balance_low'];
						$_REQUEST['balance_low'] = $temp;
					}

					$extra['WHERE'] .= " AND (coalesce((SELECT sum(p.AMOUNT) FROM ACCOUNTING_PAYMENTS p WHERE p.STAFF_ID=s.STAFF_ID AND p.SYEAR=s.SYEAR),0)-coalesce((SELECT sum(f.AMOUNT) FROM ACCOUNTING_SALARIES f WHERE f.STAFF_ID=s.STAFF_ID AND f.SYEAR=s.SYEAR),0)) BETWEEN '".$_REQUEST['balance_low']."' AND '".$_REQUEST['balance_high']."' ";

					if ( !$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Staff Payroll Balance').': </b>'._('Between').' '.$_REQUEST['balance_low'].' &amp; '.$_REQUEST['balance_high'].'<br />';
				}

				$extra['search'] .= '<tr class="st"><td>
				'._('Staff Payroll Balance').'
				</td><td>
				'._('Between').' <input type="text" name="balance_low" size="5" maxlength="10"> &amp; <input type="text" name="balance_high" size="5" maxlength="10">
				</td></tr>';
				}
			break;
		}
		$_ROSARIO['StaffWidgets'][$item] = true;
	}
}
