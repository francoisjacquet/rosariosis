<?php

if ( isset( $_POST['day_values'] )
	&& isset( $_POST['month_values'] )
	&& isset( $_POST['year_values'] ) )
{
	$requested_dates = RequestedDates(
		$_REQUEST['day_values'],
		$_REQUEST['month_values'],
		$_REQUEST['year_values']
	);

	$_REQUEST['values'] = array_replace_recursive( $_REQUEST['values'], $requested_dates );

	$_POST['values'] = array_replace_recursive( $_POST['values'], $requested_dates );
}

if ( isset( $_POST['values'] )
	&& count( $_POST['values'] )
	&& AllowEdit() )
{
	$sql = "UPDATE DISCIPLINE_REFERRALS SET ";

	$go = 0;

	$categories_RET = DBGet(DBQuery("SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER"), array(), array('ID'));
	
	foreach ( (array)$_REQUEST['values'] as $column_name => $value)
	{
		if (1)//!empty($value) || $value=='0')
		{
			//FJ check numeric fields
			if ( $categories_RET[str_replace('CATEGORY_','',$column_name)][1]['DATA_TYPE'] == 'numeric' && $value!='' && !is_numeric($value))
			{
				$error[] = _('Please enter valid Numeric data.');
				continue;
			}

			if ( !is_array($value))
				$sql .= "$column_name='".str_replace("&rsquo;","''",$value)."',";
			else
			{
				$sql .= $column_name."='||";
				foreach ( (array)$value as $val)
				{
					if ( $val)
						$sql .= str_replace('&quot;','"',$val).'||';
				}
				$sql .= "',";
			}
			$go = true;
		}
	}
	$sql = mb_substr($sql,0,-1) . " WHERE ID='".$_REQUEST['referral_id']."'";

	if ( $go)
		DBQuery($sql);
	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
}

DrawHeader(ProgramTitle());

if ( $error)
	echo ErrorMessage(array(_('Please enter valid Numeric data.')));

if ( $_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if (DeletePrompt(_('Referral')))
	{
		DBQuery("DELETE FROM DISCIPLINE_REFERRALS WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

$categories_RET = DBGet(DBQuery("SELECT df.ID,du.TITLE FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE df.DATA_TYPE!='textarea' AND du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER"));

Widgets( 'reporter' );
Widgets( 'incident_date' );
Widgets( 'discipline_fields' );

$extra['SELECT'] = ',dr.*';
if (mb_strpos($extra['FROM'],'DISCIPLINE_REFERRALS')===false)
{
	$extra['FROM'] .= ',DISCIPLINE_REFERRALS dr ';
	$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SYEAR=ssm.SYEAR AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';
}

$extra['ORDER_BY'] = 'dr.ENTRY_DATE DESC,s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME';

$extra['columns_after'] = array('STAFF_ID' => _('Reporter'),'ENTRY_DATE' => _('Incident Date'));
$extra['functions'] = array('STAFF_ID' => 'GetTeacher','ENTRY_DATE' => 'ProperDate');

foreach ( (array)$categories_RET as $category)
{
	$extra['columns_after']['CATEGORY_'.$category['ID']] = $category['TITLE'];
	$extra['functions']['CATEGORY_'.$category['ID']] = '_make';
}

$extra['new'] = true;

$extra['singular'] = _('Referral');
$extra['plural'] = _('Referrals');
$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname='.$_REQUEST['modname'];
$extra['link']['FULL_NAME']['variables'] = array('referral_id' => 'ID');
$extra['link']['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove';
$extra['link']['remove']['variables'] = array('id' => 'ID');

if (empty($_REQUEST['modfunc']) && $_REQUEST['referral_id'])
{

	//FJ prevent referral ID hacking
	if (User('PROFILE')=='teacher')
		$where = " AND STUDENT_ID IN (SELECT STUDENT_ID FROM SCHEDULE
		WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'
		AND '".DBDate()."'>=START_DATE
		AND ('".DBDate()."'<=END_DATE OR END_DATE IS NULL))";
	elseif (User('PROFILE')=='admin')
		$where = " AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'";

	$RET = DBGet(DBQuery("SELECT * FROM DISCIPLINE_REFERRALS WHERE ID='".$_REQUEST['referral_id']."'" . $where));

	if (count($RET))
	{
		$RET = $RET[1];

		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&referral_id='.$_REQUEST['referral_id'].'" method="POST">';

		DrawHeader('',SubmitButton(_('Save')));

		echo '<br />';
		PopTable('header',_('Referral'));

		$categories_RET = DBGet(DBQuery("SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER"));

		echo '<table class="width-100p col1-align-right">';

		echo '<tr class="st"><td><span class="legend-gray">'._('Student').'</span></td><td>';
		$name = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX FROM STUDENTS WHERE STUDENT_ID='".$RET['STUDENT_ID']."'"));
		echo $name[1]['FIRST_NAME'].'&nbsp;'.($name[1]['MIDDLE_NAME']?$name[1]['MIDDLE_NAME'].' ':'').$name[1]['LAST_NAME'].'&nbsp;'.$name[1]['NAME_SUFFIX'];
		echo '</td></tr>';

		echo '<tr class="st"><td><span class="legend-gray">'._('Reporter').'</span></td><td>';
		$users_RET = DBGet(DBQuery("SELECT STAFF_ID,FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND SCHOOLS LIKE '%,".UserSchool().",%' AND PROFILE IN ('admin','teacher') ORDER BY LAST_NAME,FIRST_NAME,MIDDLE_NAME"));

		foreach ( (array)$users_RET as $user)
			$options[$user['STAFF_ID']] = $user['LAST_NAME'].', '.$user['FIRST_NAME'].' '.$user['MIDDLE_NAME'];

		echo SelectInput($RET['STAFF_ID'],'values[STAFF_ID]','',$options);
		echo '</td></tr>';

		echo '<tr class="st"><td><span class="legend-gray">'._('Incident Date').'</span></td><td>';
		echo DateInput($RET['ENTRY_DATE'],'values[ENTRY_DATE]');
		echo '</td></tr>';

		foreach ( (array)$categories_RET as $category)
		{
			echo '<tr class="st"><td><span class="legend-gray">'.$category['TITLE'].'</span></td><td>';

			switch ( $category['DATA_TYPE'])
			{
				case 'text':
					echo TextInput($RET['CATEGORY_'.$category['ID']],'values[CATEGORY_'.$category['ID'].']');
					//echo '<input type=TEXT name=values[CATEGORY_'.$category['ID'].'] value="'.$RET['CATEGORY_'.$category['ID']].'" maxlength=255>';
				break;

				case 'numeric':
					echo TextInput($RET['CATEGORY_'.$category['ID']],'values[CATEGORY_'.$category['ID'].']','','size=9 maxlength=18');
					//echo '<input type=TEXT name=values[CATEGORY_'.$category['ID'].'] value="'.$RET['CATEGORY_'.$category['ID']].'" size=4 maxlength=10>';
				break;

				case 'textarea':
					echo TextAreaInput($RET['CATEGORY_'.$category['ID']],'values[CATEGORY_'.$category['ID'].']','','maxlength=5000 rows=4 cols=30');
					//echo '<textarea name=values[CATEGORY_'.$category['ID'].'] rows=4 cols=30>'.$RET['CATEGORY_'.$category['ID']].'</textarea>';
				break;

				case 'checkbox':
					echo CheckboxInput($RET['CATEGORY_'.$category['ID']],'values[CATEGORY_'.$category['ID'].']');
					//echo '<input type=CHECKBOX name=values[CATEGORY_'.$category['ID'].'] value=Y'.($RET['CATEGORY_'.$category['ID']]=='Y'?' checked':'').'>';
				break;

				case 'date':
					echo DateInput($RET['CATEGORY_'.$category['ID']],'_values[CATEGORY_'.$category['ID'].']');
					//echo PrepareDate($RET['CATEGORY_'.$category['ID']],'_values[CATEGORY_'.$category['ID'].']');
				break;

				case 'multiple_checkbox':
					if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
					{
						$return = '<div id="divvalues[CATEGORY_'.$category['ID'].']"><div onclick=\'javascript:addHTML(htmlCATEGORY_'.$category['ID'];
						$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
						$options = explode("\r",$category['SELECT_OPTIONS']);

						$toEscape = '<table class="cellpadding-5"><tr class="st">';

						$i = 0;
						foreach ( (array)$options as $option)
						{
							$i++;
							if ( $i%3==0)
								$toEscape .= '</tr><tr class="st">';
							$toEscape .= '<td><label><input type="checkbox" name="values[CATEGORY_'.$category['ID'].'][]" value="'.htmlspecialchars($option,ENT_QUOTES).'"'.(mb_strpos($RET['CATEGORY_'.$category['ID']],$option)!==false?' checked':'').' />&nbsp;'.$option.'</label></td>';
						}

						$toEscape .= '</tr></table>';

						echo '<script>var htmlCATEGORY_'.$category['ID'].'='.json_encode($toEscape).';</script>'.$return;
						echo ',"divvalues[CATEGORY_'.$category['ID'].']'.'",true);\' >'.'<span class="underline-dots">'.(($RET['CATEGORY_'.$category['ID']]!='')?str_replace('||',', ',mb_substr($RET['CATEGORY_'.$category['ID']],2,-2)):'-').'</span>'.'</div></div>';
					}
					else
						echo (($RET['CATEGORY_'.$category['ID']]!='')?str_replace('||',', ',mb_substr($RET['CATEGORY_'.$category['ID']],2,-2)):'-');
				break;

				case 'multiple_radio':
					if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
					{
						$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
						$options = explode("\r",$category['SELECT_OPTIONS']);

						$multiple_html = '<table class="cellpadding-5"><tr class="st">';

						$i = 0;
						foreach ( (array)$options as $option)
						{
							$i++;
							if ( $i%3==0)
								$multiple_html .= '</tr><tr class="st">';
							$multiple_html .= '<td><label><input type="radio" name="values[CATEGORY_'.$category['ID'].']" value="'.htmlspecialchars($option,ENT_QUOTES).'"'.(($RET['CATEGORY_'.$category['ID']]==$option)?' checked':'').'>&nbsp;'.$option.'</label></td>';
						}

						$multiple_html .= '</tr></table>';

						$id = 'values[CATEGORY_' . $category['ID'] . ']';

						echo InputDivOnclick(
							$id,
							$multiple_html,
							$RET['CATEGORY_' . $category['ID']],
							''
						);
					}
					else
						echo (($RET['CATEGORY_'.$category['ID']]!='')?$RET['CATEGORY_'.$category['ID']]:'-');
				break;

				case 'select':
					$options = array();
					$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
					$select_options = explode("\r",$category['SELECT_OPTIONS']);

					foreach ( (array)$select_options as $option)
						$options[$option] = $option;

					echo SelectInput($RET['CATEGORY_'.$category['ID']],'values[CATEGORY_'.$category['ID'].']','',$options,'N/A');
					/*
					echo '<select name=values[CATEGORY_'.$category['ID'].']><option value="">N/A';
					foreach ( (array)$options as $option)
					{
						echo '<option value="'.str_replace('"','&quot;',$option).'"'.($RET['CATEGORY_'.$category['ID']]==str_replace('"','&quot;',$option)?' selected':'').'>'.$option.'</option>';
					}
					*/
				break;
			}
			echo '</td></tr>';
		}
		echo '</table>';

		echo PopTable('footer');

		if (AllowEdit())
			echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';

		echo '</form>';
	}
	else
	{
		$error[] = _('No Students were found.');
		$_REQUEST['referral_id'] = false;
	}
}

if (isset($error))
	echo ErrorMessage($error);

if ( !$_REQUEST['referral_id'] && !$_REQUEST['modfunc'])
	Search('student_id',$extra);

function _make($value,$column)
{
	if (mb_substr_count($value,'-')==2 && VerifyDate($value))
		$value = ProperDate($value);
	elseif (is_numeric($value))
		$value = ((mb_strpos($value,'.')===false)?$value:rtrim(rtrim($value,'0'),'.'));
	elseif ( $value == 'Y')
		$value = button('check');

	return str_replace('||',',<br />',trim($value,'|'));
}
