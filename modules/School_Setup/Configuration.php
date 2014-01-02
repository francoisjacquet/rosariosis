<?php
//modif Francois: add School Configuration
//move the Modules config.inc.php to the database table
// 'config' if the value is needed in multiple modules
// 'program_config' if the value is needed in one module

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update')
{
	if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
	{
		if ((empty($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_BEFORE']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_BEFORE'])) && (empty($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_AFTER']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_AFTER'])) && (empty($_REQUEST['values']['CONFIG']['SCHOOL_NUMBER_DAYS_ROTATION']) || is_numeric($_REQUEST['values']['CONFIG']['SCHOOL_NUMBER_DAYS_ROTATION'])) && (empty($_REQUEST['values']['CONFIG']['MOODLE_PARENT_ROLE_ID']) || is_numeric($_REQUEST['values']['CONFIG']['MOODLE_PARENT_ROLE_ID'])) && (empty($_REQUEST['values']['CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID']) || is_numeric($_REQUEST['values']['CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID'])))
		{
			$sql = '';
			foreach($_REQUEST['values']['CONFIG'] as $column=>$value)
			{
				$sql .= "UPDATE CONFIG SET ";
				$sql .= "CONFIG_VALUE='".$value."' WHERE TITLE='".$column."'";
				
				$school_independant_values = array('TITLE'); //Program Title
				if (in_array($column,$school_independant_values))
					$sql .= " AND SCHOOL_ID='0';";
				else
					$sql .= " AND SCHOOL_ID='".UserSchool()."';";
			}
			foreach($_REQUEST['values']['PROGRAM_CONFIG'] as $column=>$value)
			{
				$sql .= "UPDATE PROGRAM_CONFIG SET ";
				$sql .= "VALUE='".$value."' WHERE TITLE='".$column."'";
				$sql .= " AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."';";
			}
			DBQuery($sql);
			
			$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('The school configuration has been modified.');
				
			unset($_ROSARIO['Config']);//update Config var
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

if(empty($_REQUEST['modfunc']))
{
	if (!empty($note))
		echo ErrorMessage($note, 'note');
	if (!empty($error))
		echo ErrorMessage($error, 'error');
		
	echo '<FORM ACTION="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" METHOD="POST">';
	if(AllowEdit())
		DrawHeader('',SubmitButton(_('Save')));
	echo '<BR />';
	PopTable('header',SchoolInfo('TITLE'));
	
	$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"),array(),array('TITLE'));
	
	echo '<FIELDSET><legend><b>'.ParseMLField(Config('TITLE')).'</b></legend><TABLE>';
	echo '<TR style="text-align:left;"><TD>'.MLTextInput(Config('TITLE'),'values[CONFIG][TITLE]',_('Program Title'),'required').'</TD></TR>';
	echo '</TABLE></FIELDSET>';

	echo '<BR /><FIELDSET><legend><b>'._('School').'</b></legend><TABLE>';
//modif Francois: school year over one/two calendar years format
	echo '<TR style="text-align:left;"><TD>'.CheckboxInput(Config('SCHOOL_SYEAR_OVER_2_YEARS'),'values[CONFIG][SCHOOL_SYEAR_OVER_2_YEARS]',_('School year over two calendar years'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
	echo '</TABLE></FIELDSET>';
	
	echo '<BR /><FIELDSET><legend><b>'._('Students').'</b></legend><TABLE>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput(Config('STUDENTS_USE_MAILING'),'values[CONFIG][STUDENTS_USE_MAILING]',_('Display Mailing Address'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['STUDENTS_USE_BUS'][1]['VALUE'],'values[PROGRAM_CONFIG][STUDENTS_USE_BUS]',_('Check Bus Pickup / Dropoff by default'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['STUDENTS_USE_CONTACT'][1]['VALUE'],'values[PROGRAM_CONFIG][STUDENTS_USE_CONTACT]',_('Enable Legacy Contact Information'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE'],'values[PROGRAM_CONFIG][STUDENTS_SEMESTER_COMMENTS]',_('Use Semester Comments instead of Quarter Comments'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
	echo '</TABLE></FIELDSET>';
	
	echo '<BR /><FIELDSET><legend><b>'._('Grades').'</b></legend><TABLE>';
	$options = array('-1' => _('Use letter grades only'), '0' => _('Use letter and percent grades'), '1' => _('Use percent grades only'));
    echo '<TR style="text-align:left;"><TD>'.SelectInput($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_DOES_LETTER_PERCENT]',_('Grades'),$options,false).'</TD></TR>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_HIDE_NON_ATTENDANCE_COMMENT'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_HIDE_NON_ATTENDANCE_COMMENT]',_('Hide grade comment except for attendance period courses'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_TEACHER_ALLOW_EDIT'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_TEACHER_ALLOW_EDIT]',_('Allow Teachers to edit grades after grade posting period'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_DO_STATS_STUDENTS_PARENTS'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_DO_STATS_STUDENTS_PARENTS]',_('Enable Anonymous Grade Statistics for Parents and Students'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_DO_STATS_ADMIN_TEACHERS'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_DO_STATS_ADMIN_TEACHERS]',_('Enable Anonymous Grade Statistics for Administrators and Teachers'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
	echo '</TABLE></FIELDSET>';

	echo '<BR /><FIELDSET><legend><b>'._('Attendance').'</b></legend><TABLE>';
	echo '<TR style="text-align:left;"><TD>'.TextInput(Config('ATTENDANCE_FULL_DAY_MINUTES'),'values[CONFIG][ATTENDANCE_FULL_DAY_MINUTES]',_('Minutes in a Full School Day'),'maxlength=3 size=3 min=0').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['ATTENDANCE_EDIT_DAYS_BEFORE'][1]['VALUE'],'values[PROGRAM_CONFIG][ATTENDANCE_EDIT_DAYS_BEFORE]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank to always allow').'">'._('Number of days before the school date teachers can edit attendance').'*</SPAN>','maxlength=2 size=2 min=0').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['ATTENDANCE_EDIT_DAYS_AFTER'][1]['VALUE'],'values[PROGRAM_CONFIG][ATTENDANCE_EDIT_DAYS_AFTER]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank to always allow').'">'._('Number of days after the school date teachers can edit attendance').'*</SPAN>','maxlength=2 size=2 min=0').'</TD></TR>';
	echo '</TABLE></FIELDSET>';

	if (MOODLE_INTEGRATOR)
	{
		echo '<BR /><FIELDSET><legend><b>'._('Moodle').'</b></legend><TABLE>';
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_URL'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_URL]',_('Moodle URL'),'size=38 placeholder=http://localhost/moodle').'</TD></TR>';
		
		if (!empty($program_config['MOODLE_TOKEN'][1]['VALUE']) && !AllowEdit()) //obfuscate token as it is sensitive data
			$program_config['MOODLE_TOKEN'][1]['VALUE'] = mb_strimwidth($program_config['MOODLE_TOKEN'][1]['VALUE'], 0, 19, "...");
			
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_TOKEN'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_TOKEN]',_('Moodle Token'),'maxlength=32 size=38 placeholder=d6c51ea6ffd9857578722831bcb070e1').'</TD></TR>';
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_PARENT_ROLE_ID'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_PARENT_ROLE_ID]',_('Moodle Parent Role ID'),'maxlength=2 size=2 min=0 placeholder=10').'</TD></TR>';
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['ROSARIO_STUDENTS_EMAIL_FIELD_ID'][1]['VALUE'],'values[PROGRAM_CONFIG][ROSARIO_STUDENTS_EMAIL_FIELD_ID]',_('RosarioSIS Student email field ID'),'maxlength=2 size=2 min=0 placeholder=11').'</TD></TR>';
		echo '</TABLE></FIELDSET>';
	}

	PopTable('footer');
	if(AllowEdit())
		echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';

}
?>