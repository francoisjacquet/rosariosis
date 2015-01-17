<?php
//plugin configuration interface
//verify the script is called by the right program & plugin is activated
if ($_REQUEST['modname'] == 'School_Setup/Configuration.php' && $RosarioPlugins['Moodle'] && $_REQUEST['modfunc'] == 'config')
{
	//note: no need to call ProgramTitle()

	if($_REQUEST['save']=='true')
	{
		if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
		{
			//update the PROGRAM_CONFIG table
			if ((empty($_REQUEST['values']['PROGRAM_CONFIG']['MOODLE_PARENT_ROLE_ID']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['MOODLE_PARENT_ROLE_ID'])) && (empty($_REQUEST['values']['PROGRAM_CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID'])))
			{
				$sql = '';
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
					$note[] = button('check') .'&nbsp;'._('The plugin configuration has been modified.');
				}
				
				unset($_ROSARIO['Config']);//update Config var
			}
			else
			{
				$error[] = _('Please enter valid Numeric data.');
			}
		}

		unset($_REQUEST['save']);
		unset($_SESSION['_REQUEST_vars']['values']);
		unset($_SESSION['_REQUEST_vars']['save']);
	}

	if(empty($_REQUEST['save']))
	{
		if (!empty($note))
			echo ErrorMessage($note, 'note');
		if (!empty($error))
			echo ErrorMessage($error, 'error');
		
		echo '<FORM ACTION="Modules.php?modname='.$_REQUEST['modname'].'&tab=plugins&modfunc=config&plugin=Moodle&save=true" METHOD="POST">';
	
		DrawHeader('',SubmitButton(_('Save')));
		
		echo '<BR />';
		PopTable('header',_('Moodle'));

		//get config values from PROGRAM_CONFIG table
		$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='moodle'"),array(),array('TITLE'));

		echo '<FIELDSET><legend><b>'._('Moodle').'</b></legend><TABLE>';
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_URL'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_URL]',_('Moodle URL'),'size=38 placeholder=http://localhost/moodle').'</TD></TR>';
	
		if (!empty($program_config['MOODLE_TOKEN'][1]['VALUE']) && !AllowEdit()) //obfuscate token as it is sensitive data
			$program_config['MOODLE_TOKEN'][1]['VALUE'] = mb_strimwidth($program_config['MOODLE_TOKEN'][1]['VALUE'], 0, 19, "...");
		
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_TOKEN'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_TOKEN]',_('Moodle Token'),'maxlength=32 size=38 placeholder=d6c51ea6ffd9857578722831bcb070e1').'</TD></TR>';
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['MOODLE_PARENT_ROLE_ID'][1]['VALUE'],'values[PROGRAM_CONFIG][MOODLE_PARENT_ROLE_ID]',_('Moodle Parent Role ID'),'maxlength=2 size=2 min=0 placeholder=10').'</TD></TR>';
		echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['ROSARIO_STUDENTS_EMAIL_FIELD_ID'][1]['VALUE'],'values[PROGRAM_CONFIG][ROSARIO_STUDENTS_EMAIL_FIELD_ID]',sprintf(_('%s Student email field ID'),Config('NAME')),'maxlength=2 size=2 min=0 placeholder=11').'</TD></TR>';
		echo '</TABLE></FIELDSET>';

		PopTable('footer');
		echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
		echo '</FORM>';
	}
}
else
{
	$error[] = _('You\'re not allowed to use this program!');
	echo ErrorMessage($error, 'fatal');
}
?>
