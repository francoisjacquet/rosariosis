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

				foreach($_REQUEST['values'] as $column=>$value)
				{
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'";
				DBQuery($sql);
				echo '<script type="text/javascript">parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
				$note[] = '<IMG SRC="assets/check.png" class="alignImg">&nbsp;'._('This school has been modified.');
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
					$_SESSION['UserSchool'] = $id;
				
//modif Francois: add School Configuration defaults
					$sql = "INSERT INTO config VALUES (".$id.", 'SCHOOL_SYEAR_OVER_2_YEARS', 'Y');
					INSERT INTO config VALUES (".$id.", 'ATTENDANCE_FULL_DAY_MINUTES', '300');
					INSERT INTO config VALUES (".$id.", 'ATTENDANCE_EDIT_DAYS_BEFORE', NULL);
					INSERT INTO config VALUES (".$id.", 'ATTENDANCE_EDIT_DAYS_AFTER', NULL);
					INSERT INTO config VALUES (".$id.", 'GRADES_DOES_LETTER_PERCENT', '0');
					INSERT INTO config VALUES (".$id.", 'GRADES_HIDE_NON_ATTENDANCE_COMMENT', NULL);
					INSERT INTO config VALUES (".$id.", 'GRADES_TEACHER_ALLOW_EDIT', NULL);
					INSERT INTO config VALUES (".$id.", 'GRADES_DO_STATS_STUDENTS_PARENTS', NULL);
					INSERT INTO config VALUES (".$id.", 'GRADES_DO_STATS_ADMIN_TEACHERS', 'Y');
					INSERT INTO config VALUES (".$id.", 'STUDENTS_USE_MAILING', NULL);
					INSERT INTO config VALUES (".$id.", 'STUDENTS_USE_BUS', 'Y');
					INSERT INTO config VALUES (".$id.", 'STUDENTS_USE_CONTACT', 'Y');";
					DBQuery($sql);
					
					echo '<script type="text/javascript">parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
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

		unset($_SESSION['UserSchool']);
		echo '<script type="text/javascript">parent.side.location="'.$_SESSION['Side_PHP_SELF'].'?modcat="+parent.side.document.forms[0].modcat.value;</script>';
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
    echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['REPORTING_GP_SCALE'],'values[REPORTING_GP_SCALE]',_('Base Grading Scale'),'maxlength=10').'</TD></TR>';
	if (AllowEdit())
		echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['NUMBER_DAYS_ROTATION'],'values[NUMBER_DAYS_ROTATION]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank if the school does not use a Rotation of Numbered Days').'">'._('Number of Days for the Rotation').'*</SPAN>','maxlength=1 size=1 min=1').'</TD></TR>';
	elseif (!empty($schooldata['NUMBER_DAYS_ROTATION'])) //do not show if no rotation set
		echo '<TR style="text-align:left;"><TD colspan="3">'.TextInput($schooldata['NUMBER_DAYS_ROTATION'],'values[NUMBER_DAYS_ROTATION]',_('Number of Days for the Rotation'),'maxlength=1 size=1 min=1').'</TD></TR>';

	echo '</TABLE></FIELDSET>';
	PopTable('footer');
	if(User('PROFILE')=='admin' && AllowEdit())
		echo '<span class="center">'.SubmitButton(_('Save'), 'button').'</span>';
	echo '</FORM>';
}
?>