<?php
//modif Francois: add School Configuration, move the Modules config.inc.php to the database table 'config'

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update')
{
	if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
	{
		if ((empty($_REQUEST['values']['ATTENDANCE_EDIT_DAYS_BEFORE']) || is_numeric($_REQUEST['values']['ATTENDANCE_EDIT_DAYS_BEFORE'])) && (empty($_REQUEST['values']['ATTENDANCE_EDIT_DAYS_AFTER']) || is_numeric($_REQUEST['values']['ATTENDANCE_EDIT_DAYS_AFTER'])) && (empty($_REQUEST['values']['SCHOOL_NUMBER_DAYS_ROTATION']) || is_numeric($_REQUEST['values']['SCHOOL_NUMBER_DAYS_ROTATION'])))
		{
			$sql = '';
			foreach($_REQUEST['values'] as $column=>$value)
			{
				$sql .= "UPDATE CONFIG SET ";
				$sql .= "CONFIG_VALUE='".$value."' WHERE TITLE='".$column."'";
				$sql .= " AND SCHOOL_ID='".UserSchool()."';";
			}
			DBQuery($sql);
			
			$note[] = '<IMG SRC="assets/check.png" class="alignImg">&nbsp;'._('The school configuration has been modified.');
				
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
	
	echo '<FIELDSET><legend><b>'._('School').'</b></legend><TABLE>';
//modif Francois: school year over one/two calendar years format
	echo '<TR style="text-align:left;"><TD>'.CheckboxInput(Config('SCHOOL_SYEAR_OVER_2_YEARS'),'values[SCHOOL_SYEAR_OVER_2_YEARS]',_('School year over two calendar years'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
	echo '</TABLE></FIELDSET>';
	
	echo '<BR /><FIELDSET><legend><b>'._('Students').'</b></legend><TABLE>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.CheckboxInput(Config('STUDENTS_USE_MAILING'),'values[STUDENTS_USE_MAILING]',_('Display Mailing Address'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.CheckboxInput(Config('STUDENTS_USE_BUS'),'values[STUDENTS_USE_BUS]',_('Check Bus Pickup / Dropoff by default'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.CheckboxInput(Config('STUDENTS_USE_CONTACT'),'values[STUDENTS_USE_CONTACT]',_('Enable Legacy Contact Information'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
	echo '</TABLE></FIELDSET>';
	
	echo '<BR /><FIELDSET><legend><b>'._('Grades').'</b></legend><TABLE>';
	$options = array('-1' => _('Use letter grades only'), '0' => _('Use letter and percent grades'), '1' => _('Use percent grades only'));
    echo '<TR style="text-align:left;"><TD colspan="3">'.SelectInput(Config('GRADES_DOES_LETTER_PERCENT'),'values[GRADES_DOES_LETTER_PERCENT]',_('Grades'),$options,false).'</TD></TR>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.CheckboxInput(Config('GRADES_HIDE_NON_ATTENDANCE_COMMENT'),'values[GRADES_HIDE_NON_ATTENDANCE_COMMENT]',_('Hide grade comment except for attendance period courses'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.CheckboxInput(Config('GRADES_TEACHER_ALLOW_EDIT'),'values[GRADES_TEACHER_ALLOW_EDIT]',_('Allow Teachers to edit grades after grade posting period'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.CheckboxInput(Config('GRADES_DO_STATS_STUDENTS_PARENTS'),'values[GRADES_DO_STATS_STUDENTS_PARENTS]',_('Enable Anonymous Grade Statistics for Parents and Students'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
    echo '<TR style="text-align:left;"><TD colspan="3">'.CheckboxInput(Config('GRADES_DO_STATS_ADMIN_TEACHERS'),'values[GRADES_DO_STATS_ADMIN_TEACHERS]',_('Enable Anonymous Grade Statistics for Administrators and Teachers'),'',false,'<img src="assets/check.png" height="15" />&nbsp;','<img src="assets/x.png" height="15" />&nbsp;').'</TD></TR>';
	echo '</TABLE></FIELDSET>';

	echo '<BR /><FIELDSET><legend><b>'._('Attendance').'</b></legend><TABLE>';
	echo '<TR style="text-align:left;"><TD>'.TextInput(Config('ATTENDANCE_FULL_DAY_MINUTES'),'values[ATTENDANCE_FULL_DAY_MINUTES]',_('Minutes in a Full School Day'),'maxlength=3 size=3 min=0').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD>'.TextInput(Config('ATTENDANCE_EDIT_DAYS_BEFORE'),'values[ATTENDANCE_EDIT_DAYS_BEFORE]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank to always allow').'">'._('Number of days before the school date teachers can edit attendance').'*</SPAN>','maxlength=2 size=2 min=0').'</TD></TR>';
	echo '<TR style="text-align:left;"><TD>'.TextInput(Config('ATTENDANCE_EDIT_DAYS_AFTER'),'values[ATTENDANCE_EDIT_DAYS_AFTER]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank to always allow').'">'._('Number of days after the school date teachers can edit attendance').'*</SPAN>','maxlength=2 size=2 min=0').'</TD></TR>';
	echo '</TABLE></FIELDSET>';

	PopTable('footer');
	if(AllowEdit())
		echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';

}
?>