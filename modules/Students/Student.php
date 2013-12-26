<?php
if(!$_REQUEST['include'])
{
	$_REQUEST['include'] = 'General_Info';
	$_REQUEST['category_id'] = '1';
}
elseif(!$_REQUEST['category_id'])
	if($_REQUEST['include']== 'General_Info')
		$_REQUEST['category_id'] = '1';
	elseif($_REQUEST['include']== 'Address')
		$_REQUEST['category_id'] = '3';
	elseif($_REQUEST['include']== 'Medical')
		$_REQUEST['category_id'] = '2';
	elseif($_REQUEST['include']== 'Comments')
		$_REQUEST['category_id'] = '4';
	elseif($_REQUEST['include']!= 'Other_Info')
	{
		$include = DBGet(DBQuery("SELECT ID FROM STUDENT_FIELD_CATEGORIES WHERE INCLUDE='$_REQUEST[include]'"));
		$_REQUEST['category_id'] = $include[1]['ID'];
	}

//if(mb_strpos($_REQUEST['modname'],'?include='))
//	$_REQUEST['modname'] = mb_substr($_REQUEST['modname'],0,mb_strpos($_REQUEST['modname'],'?include='));

if(User('PROFILE')!='admin')
{
	if(User('PROFILE')!='student')
		if(User('PROFILE_ID'))
			$can_edit_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND MODNAME='Students/Student.php&category_id=$_REQUEST[category_id]' AND CAN_EDIT='Y'"));
		else
			$can_edit_RET = DBGet(DBQuery("SELECT MODNAME FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND MODNAME='Students/Student.php&category_id=$_REQUEST[category_id]' AND CAN_EDIT='Y'"),array(),array('MODNAME'));
	else
		$can_edit_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='0' AND MODNAME='Students/Student.php&category_id=$_REQUEST[category_id]' AND CAN_EDIT='Y'"));
	if($can_edit_RET)
		$_ROSARIO['allow_edit'] = true;
}

if($_REQUEST['modfunc']=='update' && AllowEdit())
{
	if(count($_REQUEST['month_students']))
	{
		foreach($_REQUEST['month_students'] as $column=>$value)
		{
			$_REQUEST['students'][$column] = $_REQUEST['day_students'][$column].'-'.$_REQUEST['month_students'][$column].'-'.$_REQUEST['year_students'][$column];
			//modif Francois: bugfix SQL bug when incomplete or non-existent date
			//if($_REQUEST['students'][$column]=='--')
			if(mb_strlen($_REQUEST['students'][$column]) < 11)
				$_REQUEST['students'][$column] = '';
			else
			{
				while(!VerifyDate($_REQUEST['students'][$column]))
				{
					$_REQUEST['day_students'][$column]--;
					$_REQUEST['students'][$column] = $_REQUEST['day_students'][$column].'-'.$_REQUEST['month_students'][$column].'-'.$_REQUEST['year_students'][$column];
				}
			}
		}
	}
	unset($_REQUEST['day_students']); unset($_REQUEST['month_students']); unset($_REQUEST['year_students']);

	if((count($_REQUEST['students']) || count($_REQUEST['values'])) && AllowEdit())
	{
		//modif Francois: Moodle integrator / password
		if ($_REQUEST['moodle_create_student'] && !MoodlePasswordCheck($_REQUEST['students']['PASSWORD']))
		{
			$error[] = _('Please enter a valid password');
			//goto error_exit; //modif Francois: goto avail. in PHP 5.3
		}
			
		if(UserStudentID() && $_REQUEST['student_id']!='new' && !isset($error))
		{
			//modif Francois: Moodle integrator / password
			$old_student_in_moodle = DBGet(DBQuery("SELECT 1 FROM moodlexrosario WHERE rosario_id='".$_REQUEST['student_id']."' AND \"column\"='student_id'"));
			if ($old_student_in_moodle && !empty($_REQUEST['students']['PASSWORD']) && !MoodlePasswordCheck($_REQUEST['students']['PASSWORD']))
			{
				$error[] = _('Please enter a valid password');
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3
			}
				
			if(count($_REQUEST['students']) && !isset($error))
			{
				$sql = "UPDATE STUDENTS SET ";
				foreach($_REQUEST['students'] as $column=>$value)
				{
					if($column=='USERNAME' && $value)
						if(DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENTS WHERE USERNAME='".$value."' AND STUDENT_ID<>'".UserStudentID()."'")))
							$value = '';
					if(!is_array($value))
					{
//modif Francois: add password encryption
						if ($column!=='PASSWORD')
							$sql .= "$column='".str_replace('&#39;',"''",$value)."',";
						if ($column=='PASSWORD' && !empty($value) && $value!==str_repeat('*',8))
						{
							$value = str_replace("''","'",$value);
							$sql .= "$column='".encrypt_password($value)."',";
						}
					}
					else
					{
//modif Francois: fix bug none selected not saved
						$sql .= $column."='";
						$sql_multiple_input = '';
						foreach($value as $val)
						{
							if($val)
								$sql_multiple_input .= str_replace('&quot;','"',$val).'||';
						}
						if (!empty($sql_multiple_input))
							$sql .= "||".$sql_multiple_input;
						$sql .= "',";
					}
				}
				$sql = mb_substr($sql,0,-1) . " WHERE STUDENT_ID='".UserStudentID()."'";
				DBQuery($sql);
//modif Francois: Moodle integrator
				if ($_REQUEST['moodle_create_student'])
				{
					$moodleError = Moodle($_REQUEST['modname'], 'core_user_create_users');
					//relate parent if exist
					$moodleError .= Moodle($_REQUEST['modname'], 'core_role_assign_roles');
				}
				else
					$moodleError = Moodle($_REQUEST['modname'], 'core_user_update_users');
			}

			if(count($_REQUEST['values']['STUDENT_ENROLLMENT'][UserStudentID()]) && !isset($error))
			{
				$sql = "UPDATE STUDENT_ENROLLMENT SET ";
				foreach($_REQUEST['values']['STUDENT_ENROLLMENT'][UserStudentID()] as $column=>$value)
					$sql .= "$column='".str_replace('&#39;',"''",$value)."',";
				$sql = mb_substr($sql,0,-1) . " WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'";
				DBQuery($sql);
			}
		}
		elseif (!isset($error))
		{
			if($_REQUEST['assign_student_id'])
			{
				$student_id = $_REQUEST['assign_student_id'];
				if(count(DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENTS WHERE STUDENT_ID='$student_id'"))))
				{
					$error[] = _('That RosarioSIS ID is already taken. Please select a different one.');
					//goto error_exit; //modif Francois: goto avail. in PHP 5.3
				}
			}
			//modif Francois: fix SQL bug FIRST_NAME, LAST_NAME, GRADE_ID is null
			elseif (empty($_REQUEST['students']['FIRST_NAME']) || empty($_REQUEST['students']['LAST_NAME']) || empty($_REQUEST['values']['STUDENT_ENROLLMENT']['new']['GRADE_ID']))
			{
				$error[] = _('Please fill in the required fields');
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3
			}
			//modif Francois: Moodle integrator
			//username, password, (email) required
			elseif ($_REQUEST['moodle_create_student'] && empty($_REQUEST['students']['USERNAME']))
			{
				$error[] = _('Please fill in the required fields');
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3
			}
			elseif (!isset($error))
			{
				do
				{
					$student_id = DBGet(DBQuery('SELECT '.db_seq_nextval('STUDENTS_SEQ').' AS STUDENT_ID '.FROM_DUAL));
					$student_id = $student_id[1]['STUDENT_ID'];
				}
				while(count(DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENTS WHERE STUDENT_ID='".$student_id."'"))));
			}
			
			if (!isset($error))
			{
				$sql = "INSERT INTO STUDENTS ";
				$fields = 'STUDENT_ID,';
				$values = "'".$student_id."',";

				foreach($_REQUEST['students'] as $column=>$value)
				{
					if($column=='USERNAME' && $value)
						if(DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENTS WHERE USERNAME='".$value."'")))
							$value = '';
					if($value)
					{
						$fields .= $column.',';
						if(!is_array($value))
						{
	//modif Francois: add password encryption
							if ($column!=='PASSWORD')
								$values .= "'".$value."',";
							else
							{
								$value = str_replace("''","'",$value);
								$values .= "'".encrypt_password($value)."',";
							}
						}
						else
						{
							$values .= "'||";
							foreach($value as $val)
							{
								if($val)
									$values .= $val.'||';
							}
							$values .= "',";
						}
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
				DBQuery($sql);
				
	//modif Francois: Moodle integrator
				if ($_REQUEST['moodle_create_student'])
					$moodleError = Moodle($_REQUEST['modname'], 'core_user_create_users');

				$sql = "INSERT INTO STUDENT_ENROLLMENT ";
				$fields = 'ID,STUDENT_ID,SYEAR,SCHOOL_ID,';
				$values = "".db_seq_nextval('STUDENT_ENROLLMENT_SEQ').",'".$student_id."','".UserSyear()."','".UserSchool()."',";

				$_REQUEST['values']['STUDENT_ENROLLMENT']['new']['START_DATE'] = $_REQUEST['day_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['year_values']['STUDENT_ENROLLMENT']['new']['START_DATE'];

				foreach($_REQUEST['values']['STUDENT_ENROLLMENT']['new'] as $column=>$value)
				{
					if($value)
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
				DBQuery($sql);

				// create default food service account for this student
				$sql = "INSERT INTO FOOD_SERVICE_ACCOUNTS (ACCOUNT_ID,BALANCE,TRANSACTION_ID) values('".$student_id."','0.00','0')";
				DBQuery($sql);

				// associate with default food service account and assign other defaults
				$sql = "INSERT INTO FOOD_SERVICE_STUDENT_ACCOUNTS (STUDENT_ID,DISCOUNT,BARCODE,ACCOUNT_ID) values('".$student_id."','','','".$student_id."')";
				DBQuery($sql);

				$_SESSION['student_id'] = $_REQUEST['student_id'] = $student_id;
				$new_student = true;
			}
		}
		//error_exit: //modif Francois: goto avail. in PHP 5.3
		if ($error && !UserStudentID())
			$_REQUEST['student_id'] = 'new';
	}

	if($_REQUEST['values'] && $_REQUEST['include']== 'Medical')
		SaveData(array('STUDENT_MEDICAL_ALERTS'=>"ID='__ID__'",'STUDENT_MEDICAL'=>"ID='__ID__'",'STUDENT_MEDICAL_VISITS'=>"ID='__ID__'",'fields'=>array('STUDENT_MEDICAL'=>'ID,STUDENT_ID,','STUDENT_MEDICAL_ALERTS'=>'ID,STUDENT_ID,','STUDENT_MEDICAL_VISITS'=>'ID,STUDENT_ID,'),'values'=>array('STUDENT_MEDICAL'=>db_seq_nextval('STUDENT_MEDICAL_SEQ').",'".UserStudentID()."',",'STUDENT_MEDICAL_ALERTS'=>db_seq_nextval('STUDENT_MEDICAL_ALERTS_SEQ').",'".UserStudentID()."',",'STUDENT_MEDICAL_VISITS'=>db_seq_nextval('STUDENT_MEDICAL_VISITS_SEQ').",'".UserStudentID()."',")));

	if($_REQUEST['include']!= 'General_Info' && $_REQUEST['include']!= 'Address' && $_REQUEST['include']!= 'Medical' && $_REQUEST['include']!= 'Other_Info')
		if(!mb_strpos($_REQUEST['include'],'/'))
			include('modules/Students/includes/'.$_REQUEST['include'].'.inc.php');
		else
			include('modules/'.$_REQUEST['include'].'.inc.php');

	unset($_REQUEST['modfunc']);
	// SHOULD THIS BE HERE???
	if(!UserStudentID())
		unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_SESSION['_REQUEST_vars']['values']);
}

if($_REQUEST['student_id']=='new')
{
	$_ROSARIO['HeaderIcon'] = 'Students.png';
	DrawHeader(_('Add a Student'));
}
else
	DrawHeader(ProgramTitle());

//modif Francois: Moodle integrator
echo $moodleError;

echo ErrorMessage($error);

Search('student_id');

if(UserStudentID() || $_REQUEST['student_id']=='new')
{
	if(User('PROFILE')!='student')
		if(User('PROFILE_ID'))
			$can_use_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));
		else
			$can_use_RET = DBGet(DBQuery("SELECT MODNAME FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));
	else
		$can_use_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='0' AND CAN_USE='Y'"),array(),array('MODNAME'));
//modif Francois: General_Info only for new student
	 //$categories_RET = DBGet(DBQuery("SELECT ID,TITLE,INCLUDE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
	 $categories_RET = DBGet(DBQuery("SELECT ID,TITLE,INCLUDE FROM STUDENT_FIELD_CATEGORIES WHERE ".($_REQUEST['student_id']!='new'?'TRUE':'ID=\'1\'')." ORDER BY SORT_ORDER,TITLE"));

	if($_REQUEST['modfunc']!='delete' || $_REQUEST['delete_ok']=='1')
	{
		if($_REQUEST['student_id']!='new')
		{
			$sql = "SELECT s.STUDENT_ID,s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.NAME_SUFFIX,s.USERNAME,s.PASSWORD,s.LAST_LOGIN,
						(SELECT SCHOOL_ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND STUDENT_ID=s.STUDENT_ID ORDER BY START_DATE DESC,END_DATE DESC LIMIT 1) AS SCHOOL_ID,
						(SELECT GRADE_ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND STUDENT_ID=s.STUDENT_ID ORDER BY START_DATE DESC,END_DATE DESC LIMIT 1) AS GRADE_ID
					FROM STUDENTS s
					WHERE s.STUDENT_ID='".UserStudentID()."'";
			$student = DBGet(DBQuery($sql));
			$student = $student[1];
			$school = DBGet(DBQuery("SELECT SCHOOL_ID,GRADE_ID FROM STUDENT_ENROLLMENT WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND ('".DBDate()."' BETWEEN START_DATE AND END_DATE OR END_DATE IS NULL)"));
			echo '<FORM name="student" action="Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&category_id='.$_REQUEST['category_id'].'&modfunc=update" method="POST">';
		}
		else
			echo '<FORM name="student" action="Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&modfunc=update" method="POST">';

		if($_REQUEST['student_id']!='new')
			$name = $student['FIRST_NAME'].' '.$student['MIDDLE_NAME'].' '.$student['LAST_NAME'].' '.$student['NAME_SUFFIX'].' - '.$student['STUDENT_ID'];
		DrawHeader($name,SubmitButton(_('Save')));
		
//modif Francois: Moodle integrator
		//propose to create student in Moodle: if 1) this is a creation, 2) this is an already created student but not in Moodle yet
		
		if (MOODLE_INTEGRATOR && AllowEdit())
		{
			//2) verifiy if the student is in Moodle:
			$old_student_in_moodle = false;
			if (!empty($student['STUDENT_ID']))
				$old_student_in_moodle = DBGet(DBQuery("SELECT 1 FROM moodlexrosario WHERE rosario_id='".$student['STUDENT_ID']."' AND \"column\"='student_id'"));
			
			if ($_REQUEST['student_id']=='new' || !$old_student_in_moodle)
				DrawHeader('<label>'.CheckBoxOnclick('moodle_create_student').'&nbsp;'._('Create Student in Moodle').'</label>');
		}

		foreach($categories_RET as $category)
		{
			if($can_use_RET['Students/Student.php&category_id='.$category['ID']])
			{
				if($category['ID']=='1')
					$include = 'General_Info';
				elseif($category['ID']=='3')
					$include = 'Address';
				elseif($category['ID']=='2')
					$include = 'Medical';
				elseif($category['ID']=='4')
					$include = 'Comments';
				elseif($category['INCLUDE'])
					$include = $category['INCLUDE'];
				else
					$include = 'Other_Info';

				$tabs[] = array('title'=>$category['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&include=$include&category_id=".$category['ID']);
			}
		}

		$_ROSARIO['selected_tab'] = "Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]";
		if($_REQUEST['category_id'])
			$_ROSARIO['selected_tab'] .= '&category_id='.$_REQUEST['category_id'];

		echo '<BR />';
		echo PopTable('header',$tabs,'width="100%"');
		$PopTable_opened = true;

		if ($can_use_RET['Students/Student.php&category_id='.$_REQUEST['category_id']])
		{
			if(!mb_strpos($_REQUEST['include'],'/'))
				include('modules/Students/includes/'.$_REQUEST['include'].'.inc.php');
			else
			{
				include('modules/'.$_REQUEST['include'].'.inc.php');
				$separator = '<HR>';
				include('modules/Students/includes/Other_Info.inc.php');
			}
		}
		echo PopTable('footer');
		echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
		echo '</FORM>';
		
//modif Francois: student photo upload using jQuery form
		//move this form into General_Info.inc.php's form via Javascript
		if (AllowEdit() && $moveFormStudentPhotoHere && !isset($_REQUEST['_ROSARIO_PDF']))
		{
			?>
			<form method="POST" enctype="multipart/form-data" id="formStudentPhoto" action="modules/misc/PhotoUpload.php" style="display:none;">
				<br />
				<input type="file" id="photo" name="photo" accept="image/*" />
				<input type="hidden" id="userId" name="userId" value="<?php echo UserStudentID(); ?>" />
				<input type="hidden" id="sYear" name="sYear" value="<?php echo UserSyear(); ?>" />
				<input type="hidden" id="photoPath" name="photoPath" value="<?php echo $StudentPicturesPath; ?>" />
				<input type="hidden" id="modname" name="modname" value="<?php echo $_REQUEST['modname']; ?>" />
				<input type="hidden" id="Error1" name="Error1" value="<?php echo _('Error').': '._('File not uploaded'); ?>" />
				<input type="hidden" id="Error2" name="Error2" value="<?php echo _('Error').': '._('Wrong file type: %s (JPG required)'); ?>" />
				<input type="hidden" id="Error3" name="Error3" value="<?php echo _('Error').': '._('File size > %01.2fMb: %01.2fMb'); ?>" />
				<input type="hidden" id="Error4" name="Error4" value="<?php echo _('Error').': '._('Folder not created').': %s'; ?>" />
				<input type="hidden" id="Error5" name="Error5" value="<?php echo _('Error').': '._('Folder not writable').': %s'; ?>" />
				<BR /><span class="legend-gray"><?php echo _('Student Photo'); ?> (.jpg)</span>

				<BR /><div style="float: right;"><input type="submit" value="<?php echo _('Submit'); ?>" style="margin-right:2px;" /></div>
				<BR /><span id="outputStudentPhoto"></span>
			</form>
			<?php
		}

	}
	elseif ($can_use_RET['Students/Student.php&category_id='.$_REQUEST['category_id']])
		if(!mb_strpos($_REQUEST['include'],'/'))
			include('modules/Students/includes/'.$_REQUEST['include'].'.inc.php');
		else
		{
			include('modules/'.$_REQUEST['include'].'.inc.php');
			$separator = '<HR>';
			include('modules/Students/includes/Other_Info.inc.php');
		}
}
?>