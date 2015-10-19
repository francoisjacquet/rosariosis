<?php
//FJ add School Configuration
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
		//FJ upload school logo
		if ($_FILES['LOGO_FILE'] && AllowEdit())
			FileUpload('LOGO_FILE', 'assets'.'/', array('.jpg', '.jpeg'), 2, $error, '.jpg', 'school_logo_'.UserSchool());

		if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
		{
			if ((empty($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_BEFORE'])
				|| is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_BEFORE']))
			&& (empty($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_AFTER'])
				|| is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ATTENDANCE_EDIT_DAYS_AFTER']))
			&& (!isset($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_WARNING'])
				|| is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_WARNING']))
			&& (!isset($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_MINIMUM'])
				|| is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_MINIMUM']))
			&& (!isset($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_TARGET'])
				|| is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['FOOD_SERVICE_BALANCE_TARGET'])))
			{
				$sql = '';
				if (isset($_REQUEST['values']['CONFIG']) && is_array($_REQUEST['values']['CONFIG']))
					foreach($_REQUEST['values']['CONFIG'] as $column=>$value)
					{
						$sql .= "UPDATE CONFIG SET
							CONFIG_VALUE='" . $value . "'
							WHERE TITLE='" . $column . "'";
					
						//Program Title, Program Name, Default Theme, Create User Account, Create Student Account
						$school_independant_values = array('TITLE','NAME','THEME', 'CREATE_USER_ACCOUNT', 'CREATE_STUDENT_ACCOUNT');

						if (in_array($column,$school_independant_values))
							$sql .= " AND SCHOOL_ID='0';";
						else
							$sql .= " AND SCHOOL_ID='" . UserSchool() . "';";
					}

				if (isset($_REQUEST['values']['PROGRAM_CONFIG']) && is_array($_REQUEST['values']['PROGRAM_CONFIG']))
					foreach($_REQUEST['values']['PROGRAM_CONFIG'] as $column=>$value)
					{
						$sql .= "UPDATE PROGRAM_CONFIG SET 
							VALUE='" . $value . "'
							WHERE TITLE='" . $column . "'
							AND SCHOOL_ID='" . UserSchool() . "'
							AND SYEAR='" . UserSyear() . "';";
					}

				if ($sql != '')
				{
					DBQuery($sql);

					$note[] = button('check') .'&nbsp;'._('The school configuration has been modified.');
				}

				unset( $_ROSARIO['Config'] ); // update Config var

				unset( $_ROSARIO['ProgramConfig'] ); // update ProgramConfig var
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
		echo '<FORM ACTION="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" METHOD="POST" enctype="multipart/form-data">';

		if(AllowEdit())
			DrawHeader('',SubmitButton(_('Save')));

		if (!empty($note))
			echo ErrorMessage($note, 'note');

		if (!empty($error))
			echo ErrorMessage($error, 'error');

		echo '<BR />';
		PopTable('header',SchoolInfo('TITLE'));
	
		echo '<FIELDSET><legend>'.ParseMLField(Config('TITLE')).'</legend><TABLE>';

		echo '<TR><TD>'.MLTextInput(Config('TITLE'),'values[CONFIG][TITLE]',_('Program Title')).'</TD></TR>';

		echo '<TR><TD>'.TextInput(Config('NAME'),'values[CONFIG][NAME]',_('Program Name'),'required').'</TD></TR>';

		//FJ add Default Theme to Configuration
		echo '<TR><TD><TABLE><TR>';

		$themes = glob('assets/themes/*', GLOB_ONLYDIR);
		foreach ($themes as $theme)
		{
			$theme_name = str_replace('assets/themes/', '', $theme);

			echo '<TD><label><INPUT type="radio" name="values[CONFIG][THEME]" value="'.$theme_name.'"'.((Config('THEME')==$theme_name)?' checked':'').'> '.$theme_name.'</label></TD>';

			if($count++%3==0)
				echo '</TR><TR class="st">';
		}
		echo '</TR></TABLE></TD></TR>';
		echo '<TR><TD><span class="legend-gray">'._('Default Theme').'</span></TD></TR>';

		//FJ add Registration to Configuration
		echo '<TR><TD><FIELDSET><legend>'._('Registration').'</legend><TABLE>';

		echo '<TR><TD>'.CheckboxInput(Config('CREATE_USER_ACCOUNT'), 'values[CONFIG][CREATE_USER_ACCOUNT]', '<SPAN style="cursor:help" title="'._('New users will be added with the No Access profile').'">'._('Create User Account').'*</SPAN>', '', false, button('check'), button('x')).'</TD></TR>';

		echo '<TR><TD>'.CheckboxInput(Config('CREATE_STUDENT_ACCOUNT'), 'values[CONFIG][CREATE_STUDENT_ACCOUNT]', '<SPAN style="cursor:help" title="'._('New students will be added as Inactive students').'">'._('Create Student Account').'*</SPAN>', '', false, button('check'), button('x')).'</TD></TR>';

		echo '</TD></TR></TABLE></FIELDSET>';

		echo '</TABLE></FIELDSET>';

		echo '<BR /><FIELDSET><legend>'._('School').'</legend><TABLE>';

		//FJ school year over one/two calendar years format
		echo '<TR><TD>'.CheckboxInput(Config('SCHOOL_SYEAR_OVER_2_YEARS'), 'values[CONFIG][SCHOOL_SYEAR_OVER_2_YEARS]', _('School year over two calendar years'), '', false, button('check'), button('x')).'</TD></TR>';

		//FJ upload school logo
		echo '<TR><TD>'.(file_exists('assets/school_logo_'.UserSchool().'.jpg') ? '<br /><img src="assets/school_logo_'.UserSchool().'.jpg?cache_killer='.rand().'" style="max-width:225px; max-height:225px;" /><br />' : '').'<input type="file" id="LOGO_FILE" name="LOGO_FILE" size="14" accept="image/jpeg" /><span id="loading"></span><br /><span class="legend-gray">'._('School logo').' (.jpg)</span></TD></TR>';

		//FJ currency
		echo '<TR><TD>'.TextInput(Config('CURRENCY'),'values[CONFIG][CURRENCY]',_('Currency Symbol'),'maxlength=3 size=3').'</TD></TR>';

		echo '</TABLE></FIELDSET>';
	
		if ($RosarioModules['Students'])
		{
			echo '<BR /><FIELDSET><legend>'._('Students').'</legend><TABLE>';

			echo '<TR><TD>'.CheckboxInput(Config('STUDENTS_USE_MAILING'), 'values[CONFIG][STUDENTS_USE_MAILING]',_('Display Mailing Address'), '', false, button('check'), button('x')).'</TD></TR>';

			echo '<TR><TD>'.CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_USE_BUS' ),
				'values[PROGRAM_CONFIG][STUDENTS_USE_BUS]',
				_( 'Check Bus Pickup / Dropoff by default' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</TD></TR>';

			echo '<TR><TD>'.CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_USE_CONTACT' ),
				'values[PROGRAM_CONFIG][STUDENTS_USE_CONTACT]',
				_( 'Enable Legacy Contact Information' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</TD></TR>';

			echo '<TR><TD>'.CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_SEMESTER_COMMENTS' ),
				'values[PROGRAM_CONFIG][STUDENTS_SEMESTER_COMMENTS]',
				_( 'Use Semester Comments instead of Quarter Comments' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</TD></TR>';

			echo '</TABLE></FIELDSET>';
		}
	
		if ($RosarioModules['Grades'])
		{
			echo '<BR /><FIELDSET><legend>'._('Grades').'</legend><TABLE>';
			$options = array('-1' => _('Use letter grades only'), '0' => _('Use letter and percent grades'), '1' => _('Use percent grades only'));

			echo '<TR><TD>'.SelectInput(
				ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ),
				'values[PROGRAM_CONFIG][GRADES_DOES_LETTER_PERCENT]',
				_( 'Grades' ),
				$options,
				false
			) . '</TD></TR>';

			echo '<TR><TD>'.CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_HIDE_NON_ATTENDANCE_COMMENT' ),
				'values[PROGRAM_CONFIG][GRADES_HIDE_NON_ATTENDANCE_COMMENT]',
				_( 'Hide grade comment except for attendance period courses' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</TD></TR>';

			echo '<TR><TD>'.CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_TEACHER_ALLOW_EDIT' ),
				'values[PROGRAM_CONFIG][GRADES_TEACHER_ALLOW_EDIT]',
				_( 'Allow Teachers to edit grades after grade posting period' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</TD></TR>';

			echo '<TR><TD>'.CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_DO_STATS_STUDENTS_PARENTS' ),
				'values[PROGRAM_CONFIG][GRADES_DO_STATS_STUDENTS_PARENTS]',
				_( 'Enable Anonymous Grade Statistics for Parents and Students' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</TD></TR>';

			echo '<TR><TD>'.CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_DO_STATS_ADMIN_TEACHERS' ),
				'values[PROGRAM_CONFIG][GRADES_DO_STATS_ADMIN_TEACHERS]',
				_( 'Enable Anonymous Grade Statistics for Administrators and Teachers' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</TD></TR>';

			echo '</TABLE></FIELDSET>';
		}

		if ($RosarioModules['Attendance'])
		{
			echo '<BR /><FIELDSET><legend>'._('Attendance').'</legend><TABLE>';

			echo '<TR><TD>'.TextInput(Config('ATTENDANCE_FULL_DAY_MINUTES'),'values[CONFIG][ATTENDANCE_FULL_DAY_MINUTES]',_('Minutes in a Full School Day'),'maxlength=3 size=3 min=0').'</TD></TR>';

			echo '<TR><TD>'.TextInput(
				ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_BEFORE' ),
				'values[PROGRAM_CONFIG][ATTENDANCE_EDIT_DAYS_BEFORE]',
				'<SPAN style="cursor:help" class="legend-gray" title="' . _( 'Leave the field blank to always allow' ) . '">' .
					_( 'Number of days before the school date teachers can edit attendance' ) . '*</SPAN>',
				'maxlength=2 size=2 min=0'
			) . '</TD></TR>';

			echo '<TR><TD>'.TextInput(
				ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_AFTER' ),
				'values[PROGRAM_CONFIG][ATTENDANCE_EDIT_DAYS_AFTER]',
				'<SPAN style="cursor:help" class="legend-gray" title="' . _( 'Leave the field blank to always allow' ) . '">' .
					_( 'Number of days after the school date teachers can edit attendance' ) . '*</SPAN>',
				'maxlength=2 size=2 min=0'
			) . '</TD></TR>';

			echo '</TABLE></FIELDSET>';
		}

		if ($RosarioModules['Food_Service'])
		{
			echo '<BR /><FIELDSET><legend>'._('Food Service').'</legend><TABLE>';

			echo '<TR><TD>'.TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_WARNING' ),
				'values[PROGRAM_CONFIG][FOOD_SERVICE_BALANCE_WARNING]',
				_( 'Food Service Balance minimum amount for warning' ),
				'maxlength=10 size=5 required'
			) . '</TD></TR>';

			echo '<TR><TD>'.TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_MINIMUM' ),
				'values[PROGRAM_CONFIG][FOOD_SERVICE_BALANCE_MINIMUM]',
				_( 'Food Service Balance minimum amount' ),
				'maxlength=10 size=5 required'
			) . '</TD></TR>';

			echo '<TR><TD>'.TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_TARGET' ),
				'values[PROGRAM_CONFIG][FOOD_SERVICE_BALANCE_TARGET]',
				_( 'Food Service Balance target amount' ),
				'maxlength=10 size=5 required'
			) . '</TD></TR>';

			echo '</TABLE></FIELDSET>';
		}

		PopTable('footer');
		if(AllowEdit())
			echo '<BR /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
		echo '</FORM>';

	}
}
