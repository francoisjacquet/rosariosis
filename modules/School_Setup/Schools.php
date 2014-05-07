<?php
unset($_SESSION['_REQUEST_vars']['values']);unset($_SESSION['_REQUEST_vars']['modfunc']);
DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update' && $_REQUEST['button']==_('Save'))
{
	if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
	{
		if (empty($_REQUEST['values']['NUMBER_DAYS_ROTATION']) || is_numeric($_REQUEST['values']['NUMBER_DAYS_ROTATION']))
		{
			if($_REQUEST['new_school']!='true')
			{
				$sql = "UPDATE SCHOOLS SET ";

				$fields_RET = DBGet(DBQuery("SELECT ID,TYPE FROM SCHOOL_FIELDS ORDER BY SORT_ORDER"), array(), array('ID'));
				
				$go = 0;
				
				foreach($_REQUEST['values'] as $column=>$value)
				{
					if($value)
					{
						//modif Francois: check numeric fields
						if ($fields_RET[str_replace('CUSTOM_','',$column)][1]['TYPE'] == 'numeric' && !is_numeric($value))
						{
							$error_numeric = true;
							break;
						}
						
						$sql .= $column."='".$value."',";
						$go = true;
					}
				}
				$sql = mb_substr($sql,0,-1) . " WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'";
				if ($go)
				{
					DBQuery($sql);
					echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
					$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('This school has been modified.');
				}
			}
			else
			{
				$fields = $values = '';

				foreach($_REQUEST['values'] as $column=>$value)
					if($column!='ID' && $value)
					{
						$fields .= ','.$column;
						$values .= ",'".$value."'";
					}

				if($fields && $values)
				{
					$id = DBGet(DBQuery("SELECT ".db_seq_nextval('SCHOOLS_SEQ')." AS ID".FROM_DUAL));
					$id = $id[1]['ID'];
					$sql = "INSERT INTO SCHOOLS (ID,SYEAR$fields) values('$id','".UserSyear()."'$values)";
					DBQuery($sql);
					DBQuery("UPDATE STAFF SET SCHOOLS=rtrim(SCHOOLS,',')||',$id,' WHERE STAFF_ID='".User('STAFF_ID')."' AND SCHOOLS IS NOT NULL");
				
//modif Francois: copy School Configuration
					$sql = "INSERT INTO CONFIG (SCHOOL_ID,CONFIG_VALUE,TITLE) SELECT '".$id."' AS SCHOOL_ID,CONFIG_VALUE,TITLE FROM CONFIG WHERE SCHOOL_ID='".UserSchool()."';";
					DBQuery($sql);
					$sql = "INSERT INTO PROGRAM_CONFIG (SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE) SELECT '".$id."' AS SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."';";
					DBQuery($sql);
					
					$_SESSION['UserSchool'] = $id;
					
					echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
					unset($_REQUEST['new_school']);
				}
			}
			UpdateSchoolArray(UserSchool());
		}
		else
		{
			$error[] = _('Please enter valid Numeric data.');
		}
	}

	if ($error_numeric)
		$error[] = _('Please enter valid Numeric data.');
		
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if($_REQUEST['modfunc']=='update' && $_REQUEST['button']==_('Delete') && User('PROFILE')=='admin')
{
	if(DeletePrompt(_('School')))
	{
		DBQuery("DELETE FROM SCHOOLS WHERE ID='".UserSchool()."'");
		DBQuery("DELETE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("DELETE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("DELETE FROM SCHOOL_MARKING_PERIODS WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID=NULL WHERE CURRENT_SCHOOL_ID='".UserSchool()."'");
		DBQuery("UPDATE STAFF SET SCHOOLS=replace(SCHOOLS,',".UserSchool().",',',')");
//modif Francois: add School Configuration
		DBQuery("DELETE FROM CONFIG WHERE SCHOOL_ID='".UserSchool()."'");
		DBQuery("DELETE FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."'");

		unset($_SESSION['UserSchool']);
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
		//unset($_REQUEST);
		//$_REQUEST['modname'] = "School_Setup/Schools.php&new_school=true";
		$_REQUEST['new_school'] = 'true';
		unset($_REQUEST['modfunc']);
        UpdateSchoolArray(UserSchool());
	}
}

if(empty($_REQUEST['modfunc']))
{
	if (!empty($note))
		echo ErrorMessage($note, 'note');
	if (!empty($error))
		echo ErrorMessage($error, 'error');

	if(!$_REQUEST['new_school'])
	{
		$schooldata = DBGet(DBQuery("SELECT ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,SCHOOL_NUMBER,REPORTING_GP_SCALE,SHORT_NAME,NUMBER_DAYS_ROTATION FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
		$schooldata = $schooldata[1];
		$school_name = GetSchool(UserSchool());
	}
	else
		$school_name = _('Add a School');

	echo '<FORM ACTION="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&new_school='.$_REQUEST['new_school'].'" METHOD="POST">';
//modif Francois: fix bug: no save button if no admin
	if(User('PROFILE')=='admin' && AllowEdit())
		DrawHeader('',SubmitButton(_('Save'), 'button').(($_REQUEST['new_school']!='true')?SubmitButton(_('Delete'), 'button'):''));
	echo '<BR />';
	PopTable('header',$school_name);
	echo '<FIELDSET><TABLE>';

	if ($_REQUEST['new_school']!='true')
		echo '<TR style="text-align:left;"><TD colspan="3">'.(file_exists('assets/school_logo_'.UserSchool().'.jpg') ? '<img src="assets/school_logo_'.UserSchool().'.jpg" style="max-width:225px; max-height:225px;" /><br /><span class="legend-gray">'._('School logo').'</span>' : '').'</TD></TR>';
//modif Francois: school name field required
	echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['TITLE'],'values[TITLE]',(!$schooldata['TITLE']?'<span class="legend-red">':'')._('School Name').(!$schooldata['TITLE']?'</span>':''),'required maxlength=100').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['ADDRESS'],'values[ADDRESS]',_('Address'),'maxlength=100').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD>'.TextInput($schooldata['CITY'],'values[CITY]',_('City'),'maxlength=100').'</TD><TD>'.TextInput($schooldata['STATE'],'values[STATE]',_('State'),'maxlength=10').'</TD>';
	echo '<TD>'.TextInput($schooldata['ZIPCODE'],'values[ZIPCODE]',_('Zip'),'maxlength=10').'</TD></TR>';

	echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['PHONE'],'values[PHONE]',_('Phone'),'maxlength=30').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['PRINCIPAL'],'values[PRINCIPAL]',_('Principal of School'),'maxlength=100').'</TD></TR>';
	if(AllowEdit() || !$schooldata['WWW_ADDRESS'])
		echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['WWW_ADDRESS'],'values[WWW_ADDRESS]',_('Website'),'maxlength=100').'</TD></TR>';
	else
		echo '<TR style="text-align:left;"><TD colspan="3"><A HREF="http://'.$schooldata['WWW_ADDRESS'].'" target="_blank">'.$schooldata['WWW_ADDRESS'].'</A><BR /><span class="legend-gray">'._('Website')."</span></TD></TR>";
    echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['SHORT_NAME'],'values[SHORT_NAME]',_('Short Name'),'maxlength=25').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['SCHOOL_NUMBER'],'values[SCHOOL_NUMBER]',_('School Number'),'maxlength=100').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['REPORTING_GP_SCALE'],'values[REPORTING_GP_SCALE]',_('Base Grading Scale'),'maxlength=10 required').'</TD></TR>';
	if (AllowEdit())
		echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['NUMBER_DAYS_ROTATION'],'values[NUMBER_DAYS_ROTATION]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank if the school does not use a Rotation of Numbered Days').'">'._('Number of Days for the Rotation').'*</SPAN>','maxlength=1 size=1 min=1').'</TD></TR>';
	elseif (!empty($schooldata['NUMBER_DAYS_ROTATION'])) //do not show if no rotation set
		echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['NUMBER_DAYS_ROTATION'],'values[NUMBER_DAYS_ROTATION]',_('Number of Days for the Rotation'),'maxlength=1 size=1 min=1').'</TD></TR>';

	//modif Francois: add School Fields
	$fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE,DEFAULT_SELECTION,REQUIRED FROM SCHOOL_FIELDS ORDER BY SORT_ORDER,TITLE"));
	$fields_RET = ParseMLArray($fields_RET,'TITLE');
	
	if(count($fields_RET))
		echo '<TR style="text-align:left;"><TD colspan="3"><hr /></TD></TR>';
		
	foreach($fields_RET as $field)
	{
		$value_custom = DBGet(DBQuery("SELECT CUSTOM_".$field['ID']." FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
		switch($field['TYPE'])
		{
			case 'text':
				echo '<TR style="text-align:left;"><TD colspan="3">';
				echo TextInput($value_custom[1]['CUSTOM_'.$field['ID']],'values[CUSTOM_'.$field['ID'].']',$field['TITLE']);
				echo '</TD></TR>';
				break;

			case 'numeric':
				echo '<TR style="text-align:left;"><TD colspan="3">';
				echo TextInput($value_custom[1]['CUSTOM_'.$field['ID']],'values[CUSTOM_'.$field['ID'].']',$field['TITLE'],'size=5 maxlength=10');
				echo '</TD></TR>';
				break;

			case 'date':
				echo '<TR style="text-align:left;"><TD colspan="3">';
				echo DateInput($value_custom[1]['CUSTOM_'.$field['ID']],'values[CUSTOM_'.$field['ID'].']',$field['TITLE']);
				echo '</TD></TR>';
				break;
		}
		if($field['TYPE']=='textarea')
		{
			echo '<TR style="text-align:left;"><TD colspan="3">';
			echo TextAreaInput($value_custom[1]['CUSTOM_'.$field['ID']],'values[CUSTOM_'.$field['ID'].']',$field['TITLE']);
			echo '</TD></TR>';
		}
	}
	
	echo '</TABLE></FIELDSET>';
	PopTable('footer');
	if(User('PROFILE')=='admin' && AllowEdit())
		echo '<span class="center">'.SubmitButton(_('Save'), 'button').'</span>';
	echo '</FORM>';
}
?>