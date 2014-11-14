<?php
//modif Francois: add School Configuration
//move the Modules config.inc.php to the database table
// 'config' if the value is needed in multiple modules
// 'program_config' if the value is needed in one module

DrawHeader(ProgramTitle());

$configuration_link = '<a href="Modules.php?modname='.$_REQUEST['modname'].'"><b>'._('Configuration').'</b></a>';
$modules_link = '<a href="Modules.php?modname='.$_REQUEST['modname'].'&tab=modules"><b>'._('Modules').'</b></a>';
$plugins_link = '<a href="Modules.php?modname='.$_REQUEST['modname'].'&tab=plugins"><b>'._('Plugins').'</b></a>';
if(AllowEdit())
	DrawHeader($configuration_link.' | '.$modules_link.' | '.$plugins_link);

if (isset($_REQUEST['tab']) && $_REQUEST['tab']=='modules')
	include('modules/School_Setup/includes/Modules.inc.php');

elseif (isset($_REQUEST['tab']) && $_REQUEST['tab']=='plugins')
	include('modules/School_Setup/includes/Plugins.inc.php');
else
{

	include('ProgramFunctions/FileUpload.fnc.php');

	if($_REQUEST['modfunc']=='update')
	{
		//modif Francois: upload school logo
		if ($_FILES['LOGO_FILE'] && AllowEdit())
			FileUpload('LOGO_FILE', 'assets'.'/', array('.jpg', '.jpeg'), 2, $error, '.jpg', 'school_logo_'.UserSchool());

		if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
		{
			if ((empty($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_BEFORE']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_BEFORE'])) && (empty($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_AFTER']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_AFTER'])) && (!isset($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_WARNING']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_WARNING'])) && (!isset($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_MINIMUM']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_MINIMUM'])) && (!isset($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_TARGET']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_TARGET'])) && (empty($_REQUEST['values']['CONFIG']['SCHOOL_NUMBER_DAYS_ROTATION']) || is_numeric($_REQUEST['values']['CONFIG']['SCHOOL_NUMBER_DAYS_ROTATION'])) && (empty($_REQUEST['values']['CONFIG']['MOODLE_PARENT_ROLE_ID']) || is_numeric($_REQUEST['values']['CONFIG']['MOODLE_PARENT_ROLE_ID'])) && (empty($_REQUEST['values']['CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID']) || is_numeric($_REQUEST['values']['CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID'])))
			{
				$sql = '';
				if (isset($_REQUEST['values']['CONFIG']) && is_array($_REQUEST['values']['CONFIG']))
					foreach($_REQUEST['values']['CONFIG'] as $column=>$value)
					{
						$sql .= "UPDATE CONFIG SET ";
						$sql .= "CONFIG_VALUE='".$value."' WHERE TITLE='".$column."'";
					
						$school_independant_values = array('TITLE','NAME','THEME'); //Program Title, Program Name, Default Theme
						if (in_array($column,$school_independant_values))
							$sql .= " AND SCHOOL_ID='0';";
						else
							$sql .= " AND SCHOOL_ID='".UserSchool()."';";
					}
				if (isset($_REQUEST['values']['PROGRAM_CONFIG']) && is_array($_REQUEST['values']['PROGRAM_CONFIG']))
					foreach($_REQUEST['values']['PROGRAM_CONFIG'] as $column=>$value)
					{
						$sql .= "UPDATE PROGRAM_CONFIG SET ";
						$sql .= "VALUE='".$value."' WHERE TITLE='".$column."'";
						$sql .= " AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."';";
					}
				if ($sql != '')
				{
					DBQuery($sql);
					$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('The school configuration has been modified.');
				}
				
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
		
		echo '<FORM ACTION="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" METHOD="POST" enctype="multipart/form-data" onsubmit="if (document.getElementById(\'LOGO_FILE\').value) document.getElementById(\'loading\').innerHTML=\'<img src=assets/spinning.gif />\';">';
	
		if(AllowEdit())
			DrawHeader('',SubmitButton(_('Save')));
		
		echo '<BR />';
		PopTable('header',SchoolInfo('TITLE'));
	
		$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"),array(),array('TITLE'));
	
		echo '<FIELDSET><legend><b>'.ParseMLField(Config('TITLE')).'</b></legend><TABLE>';
		echo '<TR style="text-align:left;"><TD>'.MLTextInput(Config('TITLE'),'values[CONFIG][TITLE]',_('Program Title')).'</TD></TR>';
		echo '<TR style="text-align:left;"><TD>'.TextInput(Config('NAME'),'values[CONFIG][NAME]',_('Program Name'),'required').'</TD></TR>';

		//modif Francois: add Default Theme to Configuration
		echo '<TR style="text-align:left;"><TD><TABLE><TR>';
		$themes = scandir('assets/themes/');
		foreach ($themes as $theme)
		{
			//filter directories
			if ( is_dir('assets/themes/'.$theme) && $theme != '.' && $theme != '..' )
			{
					echo '<TD><label><INPUT type="radio" name="values[CONFIG][THEME]" value="'.$theme.'"'.((Preferences('THEME')==$theme)?' checked':'').'> '.$theme.'</label></TD>';
					$count++;
					if($count%3==0)
						echo '</TR><TR class="st">';			
			}
		}
		echo '</TR></TABLE></TD></TR>';
		echo '<TR style="text-align:left;"><TD><span class="legend-gray">'._('Default Theme').'</span></TD></TR>';
		echo '</TABLE></FIELDSET>';

		echo '<BR /><FIELDSET><legend><b>'._('School').'</b></legend><TABLE>';
	//modif Francois: school year over one/two calendar years format
		echo '<TR style="text-align:left;"><TD>'.CheckboxInput(Config('SCHOOL_SYEAR_OVER_2_YEARS'),'values[CONFIG][SCHOOL_SYEAR_OVER_2_YEARS]',_('School year over two calendar years'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
		//modif Francois: upload school logo
		echo '<TR style="text-align:left;"><TD>'.(file_exists('assets/school_logo_'.UserSchool().'.jpg') ? '<br /><img src="assets/school_logo_'.UserSchool().'.jpg?cache_killer='.rand().'" style="max-width:225px; max-height:225px;" /><br />' : '').'<input type="file" id="LOGO_FILE" name="LOGO_FILE" size="14" accept="image/jpeg" /><span id="loading"></span><br /><span class="legend-gray">'._('School logo').' (.jpg)</span></TD></TR>';
		echo '</TABLE></FIELDSET>';
	
		if ($RosarioModules['Students'])
		{
			echo '<BR /><FIELDSET><legend><b>'._('Students').'</b></legend><TABLE>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput(Config('STUDENTS_USE_MAILING'),'values[CONFIG][STUDENTS_USE_MAILING]',_('Display Mailing Address'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['STUDENTS_USE_BUS'][1]['VALUE'],'values[PROGRAM_CONFIG][STUDENTS_USE_BUS]',_('Check Bus Pickup / Dropoff by default'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['STUDENTS_USE_CONTACT'][1]['VALUE'],'values[PROGRAM_CONFIG][STUDENTS_USE_CONTACT]',_('Enable Legacy Contact Information'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE'],'values[PROGRAM_CONFIG][STUDENTS_SEMESTER_COMMENTS]',_('Use Semester Comments instead of Quarter Comments'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '</TABLE></FIELDSET>';
		}
	
		if ($RosarioModules['Grades'])
		{
			echo '<BR /><FIELDSET><legend><b>'._('Grades').'</b></legend><TABLE>';
			$options = array('-1' => _('Use letter grades only'), '0' => _('Use letter and percent grades'), '1' => _('Use percent grades only'));
			echo '<TR style="text-align:left;"><TD>'.SelectInput($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_DOES_LETTER_PERCENT]',_('Grades'),$options,false).'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_HIDE_NON_ATTENDANCE_COMMENT'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_HIDE_NON_ATTENDANCE_COMMENT]',_('Hide grade comment except for attendance period courses'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_TEACHER_ALLOW_EDIT'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_TEACHER_ALLOW_EDIT]',_('Allow Teachers to edit grades after grade posting period'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_DO_STATS_STUDENTS_PARENTS'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_DO_STATS_STUDENTS_PARENTS]',_('Enable Anonymous Grade Statistics for Parents and Students'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.CheckboxInput($program_config['GRADES_DO_STATS_ADMIN_TEACHERS'][1]['VALUE'],'values[PROGRAM_CONFIG][GRADES_DO_STATS_ADMIN_TEACHERS]',_('Enable Anonymous Grade Statistics for Administrators and Teachers'),'',false,'<img src="assets/check_button.png" height="15" />&nbsp;','<img src="assets/x_button.png" height="15" />&nbsp;').'</TD></TR>';
			echo '</TABLE></FIELDSET>';
		}

		if ($RosarioModules['Attendance'])
		{
			echo '<BR /><FIELDSET><legend><b>'._('Attendance').'</b></legend><TABLE>';
			echo '<TR style="text-align:left;"><TD>'.TextInput(Config('ATTENDANCE_FULL_DAY_MINUTES'),'values[CONFIG][ATTENDANCE_FULL_DAY_MINUTES]',_('Minutes in a Full School Day'),'maxlength=3 size=3 min=0').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['ATTENDANCE_EDIT_DAYS_BEFORE'][1]['VALUE'],'values[PROGRAM_CONFIG][ATTENDANCE_EDIT_DAYS_BEFORE]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank to always allow').'">'._('Number of days before the school date teachers can edit attendance').'*</SPAN>','maxlength=2 size=2 min=0').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['ATTENDANCE_EDIT_DAYS_AFTER'][1]['VALUE'],'values[PROGRAM_CONFIG][ATTENDANCE_EDIT_DAYS_AFTER]','<SPAN style="cursor:help" class="legend-gray" title="'._('Leave the field blank to always allow').'">'._('Number of days after the school date teachers can edit attendance').'*</SPAN>','maxlength=2 size=2 min=0').'</TD></TR>';
			echo '</TABLE></FIELDSET>';
		}

		if ($RosarioModules['Food_Service'])
		{
			echo '<BR /><FIELDSET><legend><b>'._('Food Service').'</b></legend><TABLE>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'],'values[PROGRAM_CONFIG][FOOD_SERVICE_BALANCE_WARNING]',_('Food Service Balance minimum amount for warning'),'maxlength=10 size=5 required').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'],'values[PROGRAM_CONFIG][FOOD_SERVICE_BALANCE_MINIMUM]',_('Food Service Balance minimum amount'),'maxlength=10 size=5 required').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['FOOD_SERVICE_BALANCE_TARGET'][1]['VALUE'],'values[PROGRAM_CONFIG][FOOD_SERVICE_BALANCE_TARGET]',_('Food Service Balance target amount'),'maxlength=10 size=5 required').'</TD></TR>';
			echo '</TABLE></FIELDSET>';
		}

		if (MOODLE_INTEGRATOR)
		{
			echo '<BR /><FIELDSET><legend><b>'._('Moodle').'</b></legend><TABLE>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_URL'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_URL]',_('Moodle URL'),'size=38 placeholder=http://localhost/moodle').'</TD></TR>';
		
			if (!empty($program_config['MOODLE_TOKEN'][1]['VALUE']) && !AllowEdit()) //obfuscate token as it is sensitive data
				$program_config['MOODLE_TOKEN'][1]['VALUE'] = mb_strimwidth($program_config['MOODLE_TOKEN'][1]['VALUE'], 0, 19, "...");
			
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_TOKEN'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_TOKEN]',_('Moodle Token'),'maxlength=32 size=38 placeholder=d6c51ea6ffd9857578722831bcb070e1').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_PARENT_ROLE_ID'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_PARENT_ROLE_ID]',_('Moodle Parent Role ID'),'maxlength=2 size=2 min=0 placeholder=10').'</TD></TR>';
			echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['ROSARIO_STUDENTS_EMAIL_FIELD_ID'][1]['VALUE'],'values[PROGRAM_CONFIG][ROSARIO_STUDENTS_EMAIL_FIELD_ID]',sprintf(_('%s Student email field ID'),Config('NAME')),'maxlength=2 size=2 min=0 placeholder=11').'</TD></TR>';
			echo '</TABLE></FIELDSET>';
		}

		PopTable('footer');
		if(AllowEdit())
			echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
		echo '</FORM>';

	}
}
?>
