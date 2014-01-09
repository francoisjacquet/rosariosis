<?php
DrawHeader(ProgramTitle());

if($_REQUEST['values'] && $_POST['values'])
{
	if($_REQUEST['tab']=='password')
	{
		$current_password = str_replace("''","'",$_REQUEST['values']['current']);
		$new_password = str_replace("''","'",$_REQUEST['values']['new']);
		$verifiy_password = str_replace("''","'",$_REQUEST['values']['verify']);
		
		if(mb_strtolower($new_password)!=mb_strtolower($verifiy_password))
			$error = _('Your new passwords did not match.');
		//modif Francois: Moodle integrator / password
		elseif (!MoodlePasswordCheck($new_password))
			$error = _('Please enter a valid password');				
		else
		{
//modif Francois: enable password change for students
			if (User('PROFILE')=='student')
				$password_RET = DBGet(DBQuery("SELECT PASSWORD FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'"));
			else
				$password_RET = DBGet(DBQuery("SELECT PASSWORD FROM STAFF WHERE STAFF_ID='".User('STAFF_ID')."' AND SYEAR='".UserSyear()."'"));
			
//modif Francois: add password encryption
//			if(mb_strtolower($password_RET[1]['PASSWORD'])!=mb_strtolower($current_password))
			if(!match_password($password_RET[1]['PASSWORD'],$current_password))
				$error = _('Your current password was incorrect.');
			else
			{

//				DBQuery("UPDATE STAFF SET PASSWORD='".$new_password."' WHERE STAFF_ID='".User('STAFF_ID')."' AND SYEAR='".UserSyear()."'");
				if (User('PROFILE')=='student')
					DBQuery("UPDATE STUDENTS SET PASSWORD='".encrypt_password($new_password)."' WHERE STUDENT_ID='".UserStudentID()."'");
				else
					DBQuery("UPDATE STAFF SET PASSWORD='".encrypt_password($new_password)."' WHERE STAFF_ID='".User('STAFF_ID')."' AND SYEAR='".UserSyear()."'");
				$note = _('Your new password was saved.');
				
				//modif Francois: Moodle integrator
				$moodleError = Moodle($_REQUEST['modname'], 'core_user_update_users');
			}
		}
	}
	else
	{
		$current_RET = DBGet(DBQuery("SELECT TITLE,VALUE,PROGRAM FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM IN ('Preferences','StudentFieldsSearch','StudentFieldsView','WidgetsSearch','StaffFieldsSearch','StaffFieldsView','StaffWidgetsSearch')"),array(),array('PROGRAM','TITLE'));

		if($_REQUEST['tab']=='student_listing' && $_REQUEST['values']['Preferences']['SEARCH']!='Y')
			$_REQUEST['values']['Preferences']['SEARCH'] = 'N';
		if($_REQUEST['tab']=='student_listing' && User('PROFILE')=='admin' && $_REQUEST['values']['Preferences']['DEFAULT_FAMILIES']!='Y')
			$_REQUEST['values']['Preferences']['DEFAULT_FAMILIES'] = 'N';
		if($_REQUEST['tab']=='student_listing' && User('PROFILE')=='admin' && $_REQUEST['values']['Preferences']['DEFAULT_ALL_SCHOOLS']!='Y')
			$_REQUEST['values']['Preferences']['DEFAULT_ALL_SCHOOLS'] = 'N';
		if($_REQUEST['tab']=='display_options' && $_REQUEST['values']['Preferences']['HIDE_ALERTS']!='Y')
			$_REQUEST['values']['Preferences']['HIDE_ALERTS'] = 'N';
		if($_REQUEST['tab']=='display_options' && $_REQUEST['values']['Preferences']['HIDDEN']!='Y')
			$_REQUEST['values']['Preferences']['HIDDEN'] = 'N';
		if($_REQUEST['tab']=='display_options' && $_REQUEST['values']['Preferences']['SCROLL_TOP']!='Y')
			$_REQUEST['values']['Preferences']['SCROLL_TOP'] = 'N';
		if($_REQUEST['tab']=='display_options' && $_REQUEST['values']['Preferences']['THEME']!=$current_RET['Preferences']['THEME'][1]['VALUE'])
		{
			echo '<script type="text/javascript">';
			echo 'var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);';
			echo '</script>';
		}
		if($_REQUEST['tab']=='student_fields' || $_REQUEST['tab']=='widgets' || $_REQUEST['tab']=='staff_fields' || $_REQUEST['tab']=='staff_widgets')
		{
			DBQuery("DELETE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM".($_REQUEST['tab']=='student_fields'?" IN ('StudentFieldsSearch','StudentFieldsView')":($_REQUEST['tab']=='widgets'?"='WidgetsSearch'":($_REQUEST['tab']=='staff_fields'?" IN ('StaffFieldsSearch','StaffFieldsView')":"='StaffWidgetsSearch'"))));

			foreach($_REQUEST['values'] as $program=>$values)
			{
				foreach($values as $name=>$value)
				{
					if(isset($value))
						DBQuery("INSERT INTO PROGRAM_USER_CONFIG (USER_ID,PROGRAM,TITLE,VALUE) values('".User('STAFF_ID')."','$program','$name','$value')");
				}
			}
		}
		else
		{
			foreach($_REQUEST['values'] as $program=>$values)
			{
				foreach($values as $name=>$value)
				{
					if(!$current_RET[$program][$name] && $value!='')
						DBQuery("INSERT INTO PROGRAM_USER_CONFIG (USER_ID,PROGRAM,TITLE,VALUE) values('".User('STAFF_ID')."','$program','$name','$value')");
					elseif($value!='')
						DBQuery("UPDATE PROGRAM_USER_CONFIG SET VALUE='$value' WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='$program' AND TITLE='$name'");
					else
						DBQuery("DELETE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='$program' AND TITLE='$name'");
				}
			}
		}

		// So Preferences() will get the new values
		unset($_ROSARIO['Preferences']);
	}
	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
}

unset($_REQUEST['search_modfunc']);
unset($_SESSION['_REQUEST_vars']['search_modfunc']);

//modif Francois: Moodle integrator
echo $moodleError;

if(empty($_REQUEST['modfunc']))

{
	$current_RET = DBGet(DBQuery("SELECT TITLE,VALUE,PROGRAM FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM IN ('Preferences','StudentFieldsSearch','StudentFieldsView','WidgetsSearch','StaffFieldsSearch','StaffFieldsView','StaffWidgetsSearch') "),array(),array('PROGRAM','TITLE'));

	if(!$_REQUEST['tab'])
	//modif Francois: enable password change for students
		//$_REQUEST['tab'] = 'display_options';
		$_REQUEST['tab'] = 'password';

	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&amp;tab='.$_REQUEST['tab'].'" method="POST">';
	DrawHeader('','<INPUT type="submit" value="'._('Save').'" />');
	echo '<BR />';

	if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
	{
		$tabs = array(array('title'=>_('Display Options'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=display_options"),array('title'=>_('Student Listing'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=student_listing"),array('title'=>_('Password'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=password"),array('title'=>_('Student Fields'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=student_fields"),array('title'=>_('Widgets'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=widgets"));
		if(User('PROFILE')=='admin')
		{
			$tabs[] = array('title'=>_('User Fields'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=staff_fields");
			$tabs[] = array('title'=>_('User Widgets'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=staff_widgets");
		}
	}
	elseif(User('PROFILE')=='parent')
	{
		$tabs = array(array('title'=>_('Display Options'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=display_options"),array('title'=>_('Password'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=password"),array('title'=>_('Student Fields'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=student_fields"));
	}
	//modif Francois: enable password change for students
	else
	{
		$tabs = array(array('title'=>_('Password'),'link'=>"Modules.php?modname=$_REQUEST[modname]&amp;tab=password"));
	}

	$_ROSARIO['selected_tab'] = "Modules.php?modname=$_REQUEST[modname]&amp;tab=".$_REQUEST['tab'];
	if (!in_array($_REQUEST['tab'], array('student_fields','staff_fields')))
		PopTable('header',$tabs);
	else //modif Francois: Responsive student/staff fields preferences
		$LO_options['header'] = WrapTabs($tabs,$_ROSARIO['selected_tab']);

	if($_REQUEST['tab']=='student_listing')
	{
		echo '<TABLE>';
//modif Francois: add <label> on radio
		echo '<TR class="st"><TD style="vertical-align: top;"><span style="color:gray">'._('Student Sorting').'</span></TD><TD><label><INPUT type="radio" name="values[Preferences][SORT]" value="Name"'.((Preferences('SORT')=='Name')?' checked':'').'> '._('Name').'</label><BR /><label><INPUT type="radio" name="values[Preferences][SORT]" value="Grade"'.((Preferences('SORT')=='Grade')?' checked':'').'> '._('Grade Level').', '.
		_('Name').'</label></TD></TR>';
		echo '<TR class="st"><TD style="vertical-align: top;"><span style="color:gray">'._('File Export Type').'</span></TD><TD><label><INPUT type="radio" name="values[Preferences][DELIMITER]" value="Tab"'.((Preferences('DELIMITER')=='Tab')?' checked':'').'> '._('Tab-Delimited (Excel)').'</label><BR /><label><INPUT type="radio" name="values[Preferences][DELIMITER]" value="CSV"'.((Preferences('DELIMITER')=='CSV')?' checked':'').'> CSV (OpenOffice)</label></TD></TR>';
		echo '<TR class="st"><TD style="vertical-align: top;"><span style="color:gray">'._('Date Export Format').'</span></TD><TD><label><INPUT type="radio" name="values[Preferences][E_DATE]" value=""'.((Preferences('E_DATE')=='')?' checked':'').'> '._('Display Options Format').'</label><BR /><label><INPUT type="radio" name="values[Preferences][E_DATE]" value="MM/DD/YYYY"'.((Preferences('E_DATE')=='MM/DD/YYYY')?' checked':'').'> MM/DD/YYYY</label></TD></TR>';
//modif Francois: add <label> on checkbox
		echo '<TR><TD><BR /></TD><TD><BR /></TD>';
		echo '<TR class="st"><TD></TD><TD><label><INPUT type="checkbox" name=values[Preferences][SEARCH] value="Y"'.((Preferences('SEARCH')=='Y')?' checked':'').'> '._('Display student search screen').'</label></TD></TR>';
		if(User('PROFILE')=='admin')
		{
			echo '<TR class="st"><TD></TD><TD><label><INPUT type="checkbox" name="values[Preferences][DEFAULT_FAMILIES]" value="Y"'.((Preferences('DEFAULT_FAMILIES')=='Y')?' checked':'').'> '._('Group by family by default').'</label></TD></TR>';
//modif Francois: if only one school, no Search All Schools option
			if (SchoolInfo('SCHOOLS_NB') > 1)
				echo '<TR class="st"><TD></TD><TD><label><INPUT type="checkbox" name="values[Preferences][DEFAULT_ALL_SCHOOLS]" value="Y"'.((Preferences('DEFAULT_ALL_SCHOOLS')=='Y')?' checked':'').'> '._('Search all schools by default').'</label></TD></TR>';
		}
		echo '</TABLE>';
	}

	if($_REQUEST['tab']=='display_options')
	{
		echo '<TABLE>';
		echo '<TR class="st"><TD style="vertical-align: top;"><span style="color:gray">'._('Theme').'</span></TD><TD><TABLE><TR>';
		//modif Francois: remove IgnoreFiles
		/*if($handle = opendir($RosarioPath.'assets/themes/'))
		{
			while(false !== ($file = readdir($handle)))
			{
				if($file != "." && $file != ".." && !in_array($file,$IgnoreFiles))
				{
					echo '<TD><label><INPUT type="radio" name="values[Preferences][THEME]" value="'.$file.'"'.((Preferences('THEME')==$file)?' checked':'').'> '.$file.'</label></TD>';
					$count++;
					if($count%3==0)
						echo '</TR><TR>';
				}
			}
			closedir($handle);
		}*/
		$themes = scandir('assets/themes/');
		foreach ($themes as $theme)
		{
			//filter directories
			if ( is_dir('assets/themes/'.$theme) && $theme != '.' && $theme != '..' )
			{
					echo '<TD><label><INPUT type="radio" name="values[Preferences][THEME]" value="'.$theme.'"'.((Preferences('THEME')==$theme)?' checked':'').'> '.$theme.'</label></TD>';
					$count++;
					if($count%3==0)
						echo '</TR><TR class="st">';			
			}
		}
		
		echo '</TR></TABLE></TD></TR>';
		$colors = array('#330099','#3366FF','#003333','#FF3300','#660000','#666666','#333366','#336633','purple','teal','firebrick','tan');
		echo '<TR class="st"><TD><span style="color:gray">'._('PDF List Header Color').'</span></TD><TD><TABLE><TR>';
		foreach($colors as $color)
			echo '<TD style="background-color:'.$color.';"><INPUT type="radio" name="values[Preferences][HEADER]" value="'.$color.'"'.((Preferences('HEADER')==$color)?' checked':'').'></TD>';
		echo '</TR></TABLE></TD></TR>';

//modif Francois: css WPadmin
//		$colors = array('#330099','#3366FF','#003333','#FF3300','#660000','#666666', '#FFFFFF');
		$colors = array('#330099','#3366FF','#003333','#FF3300','#660000','#666666', '#FFFFFF');
		echo '<TR class="st"><TD><span style="color:gray">'._('Highlight Color').'</span></TD><TD><TABLE><TR>';
		foreach($colors as $color)
			echo '<TD style="background-color:'.$color.';"><INPUT type="radio" name="values[Preferences][HIGHLIGHT]" value="'.$color.'"'.((Preferences('HIGHLIGHT')==$color)?' checked':'').'></TD>';
		echo '</TR></TABLE></TD></TR>';

//modif Francois: css WPadmin

		echo '<TR class="st"><TD><span style="color:gray">'._('Date Format').'</span></TD><TD><SELECT name="values[Preferences][MONTH]">';
		//modif Francois: display locale with strftime()
		$values = array('%B','%b','%m');
//		$values = array('F','M','m','n');
		foreach($values as $value)
			echo '<OPTION value="'.$value.'"'.((Preferences('MONTH')==$value)?' SELECTED="SELECTED"':'').'>'.mb_convert_case(iconv('','UTF-8',strftime($value)), MB_CASE_TITLE, "UTF-8").'</OPTION>';
//			echo '<OPTION value='.$value.((Preferences('MONTH')==$value)?' SELECTED':'').'>'.date($value).'</OPTION>';
		echo '</SELECT>';
		echo '<SELECT name="values[Preferences][DAY]">';
		$values = array('%d');
//		$values = array('d','j','jS');
		foreach($values as $value)
			echo '<OPTION value="'.$value.'"'.((Preferences('DAY')==$value)?' SELECTED="SELECTED"':'').'>'.strftime($value).'</OPTION>';
//			echo '<OPTION value='.$value.((Preferences('DAY')==$value)?' SELECTED':'').'>'.date($value=='d'?'[0]j':$value).'</OPTION>';
		echo '</SELECT>';
		echo '<SELECT name=values[Preferences][YEAR]>';
		$values = array('%Y','%y');
//		$values = array('Y','y','');
		foreach($values as $value)
			echo '<OPTION value="'.$value.'"'.((Preferences('YEAR')==$value || (!Preferences('YEAR') && !$value))?' SELECTED="SELECTED"':'').'>'.strftime($value).'</OPTION>';
//			echo '<OPTION value="'.$value.'"'.((Preferences('YEAR')==$value || (!Preferences('YEAR') && !$value))?' SELECTED':'').'>'.date($value).'</OPTION>';
		echo '</SELECT>';
		echo '</TD></TR>';
		echo '<TR class="st"><TD></TD><TD><label><INPUT type="checkbox" name="values[Preferences][HIDE_ALERTS]" value="Y"'.((Preferences('HIDE_ALERTS')=='Y')?' checked':'').'> '._('Disable login alerts').'</label></TD></TR>';
		echo '<TR class="st"><TD></TD><TD><label><INPUT type="checkbox" name="values[Preferences][HIDDEN]" value="Y"'.((Preferences('HIDDEN')=='Y')?' checked':'').'> '._('Display data using hidden fields').'</label></TD></TR>';
		echo '<TR class="st"><TD></TD><TD><label><INPUT type="checkbox" name="values[Preferences][SCROLL_TOP]" value="Y"'.((Preferences('SCROLL_TOP')=='Y')?' checked':'').'> '._('Automatically scroll to the top of the page').'</label></TD></TR>';
		echo '</TABLE>';
	}

	if($_REQUEST['tab']=='password')
	{
		if($error)
			echo ErrorMessage(array($error));
		if($note)
			echo ErrorMessage(array($note),'note');
//modif Francois: add translation
//modif Francois: password fields are required
//modif Francois: Moodle integrator / password
		echo '<TABLE><TR class="st"><TD><span style="color:gray">'._('Current Password').'</span></TD><TD><INPUT type="password" name="values[current]" required></TD></TR><TR class="st"><TD><span style="color:gray">'.(MOODLE_INTEGRATOR?'<SPAN title="'._('The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character').'">':'')._('New Password').(MOODLE_INTEGRATOR?'*</SPAN>':'').'</span></TD><TD><INPUT type="password" name="values[verify]" required></TD></TR><TR class="st"><TD><span style="color:gray">'._('Verify New Password').'</span></TD><TD><INPUT type="password" name="values[new]" required></TD></TR></TABLE>';
	}

	if($_REQUEST['tab']=='student_fields')
	{
		if(User('PROFILE_ID'))
			$custom_fields_RET = DBGet(DBQuery("SELECT sfc.TITLE AS CATEGORY,cf.ID,cf.TITLE,'' AS SEARCH,'' AS DISPLAY FROM CUSTOM_FIELDS cf,STUDENT_FIELD_CATEGORIES sfc WHERE sfc.ID=cf.CATEGORY_ID AND (SELECT CAN_USE FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND MODNAME='Students/Student.php&category_id='||cf.CATEGORY_ID)='Y' ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE"),array('SEARCH'=>'_make','DISPLAY'=>'_make'),array('CATEGORY'));
		else
			$custom_fields_RET = DBGet(DBQuery("SELECT sfc.TITLE AS CATEGORY,cf.ID,cf.TITLE,'' AS SEARCH,'' AS DISPLAY FROM CUSTOM_FIELDS cf,STUDENT_FIELD_CATEGORIES sfc WHERE sfc.ID=cf.CATEGORY_ID AND (SELECT CAN_USE FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND MODNAME='Students/Student.php&category_id='||cf.CATEGORY_ID)='Y' ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE"),array('SEARCH'=>'_make','DISPLAY'=>'_make'),array('CATEGORY'));
        foreach ($custom_fields_RET as &$category_RET)
            foreach ($category_RET as &$field) {
                $field['CATEGORY'] = '<b>'.ParseMLField($field['CATEGORY']).'</b>';
                $field['TITLE']    = ParseMLField($field['TITLE']); 
            }
		$THIS_RET['ID'] = 'CONTACT_INFO';
		$custom_fields_RET[-1][1] = array('CATEGORY'=>'<B>'._('Contact Information').'</B>','ID'=>'CONTACT_INFO','TITLE'=>'<IMG SRC="assets/down_phone_button.png" height="24"> '._('Contact Information'),'DISPLAY'=>_make('','DISPLAY'));
		$THIS_RET['ID'] = 'HOME_PHONE';
		$custom_fields_RET[-1][] = array('CATEGORY'=>'<B>'._('Contact Information').'</B>','ID'=>'HOME_PHONE','TITLE'=>_('Home Phone Number'),'DISPLAY'=>_make('','DISPLAY'));
		$THIS_RET['ID'] = 'GUARDIANS';
		$custom_fields_RET[-1][] = array('CATEGORY'=>'<B>'._('Contact Information').'</B>','ID'=>'GUARDIANS','TITLE'=>_('Guardians'),'DISPLAY'=>_make('','DISPLAY'));
		$THIS_RET['ID'] = 'ALL_CONTACTS';
		$custom_fields_RET[-1][] = array('CATEGORY'=>'<B>'._('Contact Information').'</B>','ID'=>'ALL_CONTACTS','TITLE'=>_('All Contacts'),'DISPLAY'=>_make('','DISPLAY'));

		$custom_fields_RET[0][1] = array('CATEGORY'=>'<B>'._('Addresses').'</B>','ID'=>'ADDRESS','TITLE'=>_('None'),'DISPLAY'=>_makeAddress(''));
		$custom_fields_RET[0][] = array('CATEGORY'=>'<B>'._('Addresses').'</B>','ID'=>'ADDRESS','TITLE'=>'<IMG SRC="assets/house_button.png" height="24"> '._('Residence'),'DISPLAY'=>_makeAddress('RESIDENCE'));
//modif Francois: disable mailing address display
		if (Config('STUDENTS_USE_MAILING'))
			$custom_fields_RET[0][] = array('CATEGORY'=>'<B>'._('Addresses').'</B>','ID'=>'ADDRESS','TITLE'=>'<IMG SRC="assets/mailbox_button.png" height="24"> '._('Mailing'),'DISPLAY'=>_makeAddress('MAILING'));
		$custom_fields_RET[0][] = array('CATEGORY'=>'<B>'._('Addresses').'</B>','ID'=>'ADDRESS','TITLE'=>'<IMG SRC="assets/bus_button.png" height="24"> '._('Bus Pickup'),'DISPLAY'=>_makeAddress('BUS_PICKUP'));
		$custom_fields_RET[0][] = array('CATEGORY'=>'<B>'._('Addresses').'</B>','ID'=>'ADDRESS','TITLE'=>'<IMG SRC="assets/bus_button.png" height="24"> '._('Bus Dropoff'),'DISPLAY'=>_makeAddress('BUS_DROPOFF'));

		if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
			$columns = array('CATEGORY'=>'','TITLE'=>_('Field'),'SEARCH'=>_('Search'),'DISPLAY'=>_('Expanded View'));
		else
			$columns = array('CATEGORY'=>'','TITLE'=>_('Field'),'DISPLAY'=>_('Expanded View'));
		ListOutput($custom_fields_RET,$columns,'.','.',array(),array(array('CATEGORY')),$LO_options);
	}

	if($_REQUEST['tab']=='widgets')
	{
		$widgets = array();
		if($RosarioModules['Students'])
			$widgets += array('calendar'=>_('Calendar'),'next_year'=>_('Next School Year'));
		if($RosarioModules['Scheduling'] && User('PROFILE')=='admin')
			$widgets = array('course'=>_('Course'),'request'=>_('Request'));
		if($RosarioModules['Attendance'])
			$widgets += array('absences'=>_('Days Absent'));
		if($RosarioModules['Grades'])
			$widgets += array('gpa'=>_('GPA'),'class_rank'=>_('Class Rank'),'letter_grade'=>_('Grade'));
		if($RosarioModules['Eligibility'])
			$widgets += array('eligibility'=>_('Eligibility'),'activity'=>_('Activity'));
		if($RosarioModules['Food_Service'])
			$widgets += array('fsa_balance'=>_('Food Service Balance'),'fsa_discount'=>_('Food Service Discount'),'fsa_status'=>_('Food Service Status'),'fsa_barcode'=>_('Food Service Barcode'));
		if($RosarioModules['Discipline'])
			$widgets += array('discipline'=>_('Discipline'));
		if($RosarioModules['Student_Billing'])
			$widgets += array('balance'=>_('Student Billing Balance'));

		$widgets_RET[0] = array();
		foreach($widgets as $widget=>$title)
		{
			$THIS_RET['ID'] = $widget;
			$widgets_RET[] = array('ID'=>$widget,'TITLE'=>$title,'WIDGET'=>_make('','WIDGET'));
		}
		unset($widgets_RET[0]);

		echo '<INPUT type="hidden" name="values[WidgetsSearch]" />';
		$columns = array('TITLE'=>_('Widget'),'WIDGET'=>_('Search'));
		//modif Francois: no responsive table
		$LO_options = array('responsive' => false);
		ListOutput($widgets_RET,$columns,'.','.',array(),array(),$LO_options);
	}

	if($_REQUEST['tab']=='staff_fields' && User('PROFILE')=='admin')
	{
		if(User('PROFILE_ID'))
			$custom_fields_RET = DBGet(DBQuery("SELECT sfc.TITLE AS CATEGORY,cf.ID,cf.TITLE,'' AS STAFF_SEARCH,'' AS STAFF_DISPLAY FROM STAFF_FIELDS cf,STAFF_FIELD_CATEGORIES sfc WHERE sfc.ID=cf.CATEGORY_ID AND (SELECT CAN_USE FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND MODNAME='Users/User.php&category_id='||cf.CATEGORY_ID)='Y' ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE"),array('STAFF_SEARCH'=>'_make','STAFF_DISPLAY'=>'_make'),array('CATEGORY'));
		else
			$custom_fields_RET = DBGet(DBQuery("SELECT sfc.TITLE AS CATEGORY,cf.ID,cf.TITLE,'' AS STAFF_SEARCH,'' AS STAFF_DISPLAY FROM STAFF_FIELDS cf,STAFF_FIELD_CATEGORIES sfc WHERE sfc.ID=cf.CATEGORY_ID AND (SELECT CAN_USE FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND MODNAME='Users/User.php&category_id='||cf.CATEGORY_ID)='Y' ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE"),array('STAFF_SEARCH'=>'_make','STAFF_DISPLAY'=>'_make'),array('CATEGORY'));

        foreach ($custom_fields_RET as &$category_RET)
            foreach ($category_RET as &$field) {
                $field['CATEGORY'] = '<b>'.ParseMLField($field['CATEGORY']).'</b>';
                $field['TITLE']    = ParseMLField($field['TITLE']); 
            }
		echo '<INPUT type="hidden" name="values[StaffFieldsSearch]" /><INPUT type="hidden" name="values[StaffFieldsView]" />';
		$columns = array('CATEGORY'=>'','TITLE'=>_('Field'),'STAFF_SEARCH'=>_('Search'),'STAFF_DISPLAY'=>_('Expanded View'));
		//modif Francois: no responsive table
		ListOutput($custom_fields_RET,$columns,'User Field','User Fields',array(),array(array('CATEGORY')),$LO_options);
	}

	if($_REQUEST['tab']=='staff_widgets' && User('PROFILE')=='admin')
	{
		$widgets = array();
		if($RosarioModules['Users'])
			$widgets += array('permissions'=>_('Permissions'));
		if($RosarioModules['Food_Service'])
			$widgets += array('fsa_balance'=>_('Food Service Balance'),'fsa_status'=>_('Food Service Status'),'fsa_barcode'=>_('Food Service Barcode'));

		$widgets_RET[0] = array();
		foreach($widgets as $widget=>$title)
		{
			$THIS_RET['ID'] = $widget;
			$widgets_RET[] = array('ID'=>$widget,'TITLE'=>$title,'STAFF_WIDGET'=>_make('','STAFF_WIDGET'));
		}
		unset($widgets_RET[0]);

		echo '<INPUT type="hidden" name="values[StaffWidgetsSearch]" />';
		$columns = array('TITLE'=>_('Widget'),'STAFF_WIDGET'=>_('Search'));
		//modif Francois: no responsive table
		$LO_options = array('responsive' => false);
		ListOutput($widgets_RET,$columns,'.','.',array(),array(),$LO_options);
	}

	if (!in_array($_REQUEST['tab'], array('student_fields','staff_fields')))
		PopTable('footer');

	echo '<span class="center"><INPUT type="submit" value="'._('Save').'" /></span>';
	echo '</FORM>';
}

function _make($value,$name)
{	global $THIS_RET,$current_RET;

	switch($name)
	{
		case 'SEARCH':
			if($current_RET['StudentFieldsSearch'][$THIS_RET['ID']])
				$checked = ' checked';
			return '<INPUT type="checkbox" name="values[StudentFieldsSearch]['.$THIS_RET['ID'].']" value="Y"'.$checked.' />';
		break;

		case 'DISPLAY':
			if($current_RET['StudentFieldsView'][$THIS_RET['ID']])
				$checked = ' checked';
			return '<INPUT type="checkbox" name="values[StudentFieldsView]['.$THIS_RET['ID'].']" value="Y"'.$checked.' />';
		break;

		case 'WIDGET':
			if($current_RET['WidgetsSearch'][$THIS_RET['ID']])
				$checked = ' checked';
			return '<INPUT type="checkbox" name="values[WidgetsSearch]['.$THIS_RET['ID'].']" value="Y"'.$checked.' />';
		break;

		case 'STAFF_SEARCH':
			if($current_RET['StaffFieldsSearch'][$THIS_RET['ID']])
				$checked = ' checked';
			return '<INPUT type="checkbox" name="values[StaffFieldsSearch]['.$THIS_RET['ID'].']" value="Y"'.$checked.' />';
		break;

		case 'STAFF_DISPLAY':
			if($current_RET['StaffFieldsView'][$THIS_RET['ID']])
				$checked = ' checked';
			return '<INPUT type="checkbox" name="values[StaffFieldsView]['.$THIS_RET['ID'].']" value="Y"'.$checked.' />';
		break;

		case 'STAFF_WIDGET':
			if($current_RET['StaffWidgetsSearch'][$THIS_RET['ID']])
				$checked = ' checked';
			return '<INPUT type="checkbox" name="values[StaffWidgetsSearch]['.$THIS_RET['ID'].']" value="Y"'.$checked.' />';
		break;
	}
}

function _makeAddress($value)
{	global $current_RET;

	if($current_RET['StudentFieldsView']['ADDRESS'][1]['VALUE']==$value || (!$current_RET['StudentFieldsView']['ADDRESS'][1]['VALUE'] && $value==''))
		$checked = ' checked';
	return '<INPUT type="radio" name="values[StudentFieldsView][ADDRESS]" value="'.$value.'"'.$checked.'>';
}
?>