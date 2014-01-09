<?php
if(User('PROFILE')!='admin' && User('PROFILE')!='teacher' && $_REQUEST['staff_id'] && $_REQUEST['staff_id']!=User('STAFF_ID') && $_REQUEST['staff_id']!='new')
{
	if(User('USERNAME'))
		//modif Francois: create HackingLog function to centralize code
		HackingLog();
		
	exit;
}

if(!$_REQUEST['include'])
{
	$_REQUEST['include'] = 'General_Info';
	$_REQUEST['category_id'] = '1';
}
elseif(!$_REQUEST['category_id'])
{
	if($_REQUEST['include']=='General_Info')
		$_REQUEST['category_id'] = '1';
	elseif($_REQUEST['include']=='Schedule')
		$_REQUEST['category_id'] = '2';
	elseif($_REQUEST['include']!='Other_Info')
	{
		$include = DBGet(DBQuery("SELECT ID FROM STAFF_FIELD_CATEGORIES WHERE INCLUDE='$_REQUEST[include]'"));
		$_REQUEST['category_id'] = $include[1]['ID'];
	}
}
	
if(User('PROFILE')!='admin')
{
	if(User('PROFILE_ID'))
		$can_edit_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND MODNAME='Users/User.php&category_id=$_REQUEST[category_id]' AND CAN_EDIT='Y'"));
	else
		$can_edit_RET = DBGet(DBQuery("SELECT MODNAME FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND MODNAME='Users/User.php&category_id=$_REQUEST[category_id]' AND CAN_EDIT='Y'"),array(),array('MODNAME'));
	if($can_edit_RET)
		$_ROSARIO['allow_edit'] = true;
}

if($_REQUEST['modfunc']=='update')
{
	if(count($_REQUEST['month_staff']))
	{
		foreach($_REQUEST['month_staff'] as $column=>$value)
		{
			$_REQUEST['staff'][$column] = $_REQUEST['day_staff'][$column].'-'.$_REQUEST['month_staff'][$column].'-'.$_REQUEST['year_staff'][$column];
			//modif Francois: bugfix SQL bug when incomplete or non-existent date
			//if($_REQUEST['staff'][$column]=='--')
			if(mb_strlen($_REQUEST['staff'][$column]) < 11)
				$_REQUEST['staff'][$column] = '';
			else
			{
				while(!VerifyDate($_REQUEST['staff'][$column]))
				{
					$_REQUEST['day_staff'][$column]--;
					$_REQUEST['staff'][$column] = $_REQUEST['day_staff'][$column].'-'.$_REQUEST['month_staff'][$column].'-'.$_REQUEST['year_staff'][$column];
				}
			}
		}
	}
	unset($_REQUEST['day_staff']); unset($_REQUEST['month_staff']); unset($_REQUEST['year_staff']);

	if($_REQUEST['staff']['SCHOOLS'])
	{
		foreach($_REQUEST['staff']['SCHOOLS'] as $school_id=>$yes)
			$schools .= ','.$school_id;
		$_REQUEST['staff']['SCHOOLS'] = $schools.',';
	}
/*	else
		$_REQUEST['staff']['SCHOOLS'] = $_POST['staff'] = '';*/

	if(count($_POST['staff']) && (User('PROFILE')=='admin' || basename($_SERVER['PHP_SELF'])=='index.php'))
	{
		//modif Francois: Moodle integrator / password
		if (!MoodlePasswordCheck($_REQUEST['staff']['PASSWORD']))
		{
			$error[] = _('Please enter a valid password');
			//goto error_exit; //modif Francois: goto avail. in PHP 5.3
		}
			
		if(UserStaffID() && $_REQUEST['staff_id']!='new' && !isset($error))
		{
			$profile_RET = DBGet(DBQuery("SELECT PROFILE,PROFILE_ID,USERNAME FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));

			if(isset($_REQUEST['staff']['PROFILE']) && $_REQUEST['staff']['PROFILE']!=$profile_RET[1]['PROFILE_ID'])
			{
				if($_REQUEST['staff']['PROFILE']=='admin')
					$_REQUEST['staff']['PROFILE_ID'] = '1';
				elseif($_REQUEST['staff']['PROFILE']=='teacher')
					$_REQUEST['staff']['PROFILE_ID'] = '2';
				elseif($_REQUEST['staff']['PROFILE']=='parent')
					$_REQUEST['staff']['PROFILE_ID'] = '3';
			}

			if($_REQUEST['staff']['PROFILE_ID'])
				DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='".UserStaffID()."'");
			elseif(isset($_REQUEST['staff']['PROFILE_ID']) && $profile_RET[1]['PROFILE_ID'])
			{
				DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='".UserStaffID()."'");
				DBQuery("INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT) SELECT s.STAFF_ID,e.MODNAME,e.CAN_USE,e.CAN_EDIT FROM STAFF s,PROFILE_EXCEPTIONS e WHERE s.STAFF_ID='".UserStaffID()."' AND s.PROFILE_ID=e.PROFILE_ID");
			}

			// CHANGE THE USERNAME
			if($_REQUEST['staff']['USERNAME'] && $_REQUEST['staff']['USERNAME']!=$profile_RET[1]['USERNAME'])
			{
				$existing_staff = DBGet(DBQuery("SELECT 'exists' FROM STAFF WHERE USERNAME='".$_REQUEST['staff']['USERNAME']."' AND SYEAR='".UserSyear()."'"));
				if(count($existing_staff))
				{
					$error[] = _('A user with that username already exists for the current school year. Choose a different username and try again.');
					//goto error_exit; //modif Francois: goto avail. in PHP 5.3
				}
			}
			
			if (!isset($error))
			{
				$sql = "UPDATE STAFF SET ";
				$go = false;
				foreach($_REQUEST['staff'] as $column_name=>$value)
				{
	//modif Francois: add password encryption
					if ($column_name!=='PASSWORD')
					{
						$sql .= "$column_name='".$value."',";
						$go = true;
					}
					if ($column_name=='PASSWORD' && !empty($value) && $value!==str_repeat('*',8))
					{
						$value = str_replace("''","'",$value);
						$sql .= "$column_name='".encrypt_password($value)."',";
						$go = true;
					}
				}
				$sql = mb_substr($sql,0,-1) . " WHERE STAFF_ID='".UserStaffID()."'";
				if(User('PROFILE')=='admin' && $go)
					DBQuery($sql);

	//modif Francois: Moodle integrator
				$moodleError = Moodle($_REQUEST['modname'], 'core_user_update_users');
			}
		}
		elseif (!isset($error))
		{
			//modif Francois: fix SQL bug FIRST_NAME, LAST_NAME, PROFILE is null
			if (empty($_REQUEST['staff']['FIRST_NAME']) || empty($_REQUEST['staff']['LAST_NAME']) || empty($_REQUEST['staff']['PROFILE']))
			{
				$error[] = _('Please fill in the required fields');
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3
			}
			//modif Francois: Moodle integrator
			//username, password, email required
			if (MOODLE_INTEGRATOR && (empty($_REQUEST['staff']['USERNAME']) || empty($_REQUEST['staff']['PASSWORD']) || empty($_REQUEST['staff']['EMAIL'])) && !isset($error))
			{
				$error[] = _('Please fill in the required fields');
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3
			}
			if($_REQUEST['staff']['PROFILE']=='admin')
				$_REQUEST['staff']['PROFILE_ID'] = '1';
			elseif($_REQUEST['staff']['PROFILE']=='teacher')
				$_REQUEST['staff']['PROFILE_ID'] = '2';
			elseif($_REQUEST['staff']['PROFILE']=='parent')
				$_REQUEST['staff']['PROFILE_ID'] = '3';

			$existing_staff = DBGet(DBQuery("SELECT 'exists' FROM STAFF WHERE USERNAME='".$_REQUEST['staff']['USERNAME']."' AND SYEAR='".UserSyear()."'"));
			if(count($existing_staff))
			{
				$error[] = _('A user with that username already exists for the current school year. Choose a different username and try again.');
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3
			}
			
			if (!isset($error))
			{
				$staff_id = DBGet(DBQuery('SELECT '.db_seq_nextval('STAFF_SEQ').' AS STAFF_ID'.FROM_DUAL));
				$staff_id = $staff_id[1]['STAFF_ID'];

				$sql = "INSERT INTO STAFF ";
				$fields = 'SYEAR,STAFF_ID,';
				$values = "'".UserSyear()."','".$staff_id."',";

				if(basename($_SERVER['PHP_SELF'])=='index.php')
				{
					$fields .= 'PROFILE,';
					$values = "'".Config('SYEAR')."'".mb_substr($values,mb_strpos($values,','))."'none',";
				}

				foreach($_REQUEST['staff'] as $column=>$value)
				{
					if($value)
					{
						$fields .= $column.',';
	//modif Francois: add password encryption
						if ($column!=='PASSWORD')
							$values .= "'".$value."',";
						else
						{
							$value = str_replace("''","'",$value);
							$values .= "'".encrypt_password($value)."',";
						}
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
				DBQuery($sql);
				
	//modif Francois: Moodle integrator
				$moodleError = Moodle($_REQUEST['modname'], 'core_user_create_users');
				$moodleError .= Moodle($_REQUEST['modname'], 'core_role_assign_roles');
				
				$_REQUEST['staff_id'] = $staff_id;
			}
		}
		//error_exit: //modif Francois: goto avail. in PHP 5.3
		if ($error && !UserStaffID())
			$_REQUEST['staff_id'] = 'new';
	}

	if($_REQUEST['include']!='General_Info' && $_REQUEST['include']!='Schedule' && $_REQUEST['include']!='Other_Info')
		if(!mb_strpos($_REQUEST['include'],'/'))
			include('modules/Users/includes/'.$_REQUEST['include'].'.inc.php');
		else
			include('modules/'.$_REQUEST['include'].'.inc.php');

	unset($_REQUEST['staff']);
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['staff']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);

	if(User('STAFF_ID')==$_REQUEST['staff_id'])
	{
		unset($_ROSARIO['User']);
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
	}
}

if(basename($_SERVER['PHP_SELF'])!='index.php')
{
	if($_REQUEST['staff_id']=='new')
	{
		$_ROSARIO['HeaderIcon'] = 'Users.png';
		DrawHeader(_('Add a User'));
	}
	else
		DrawHeader(ProgramTitle());
		Search('staff_id',$extra);
}
else
	DrawHeader('Create Account');
	
//modif Francois: Moodle integrator
echo $moodleError;

echo ErrorMessage($error);

if($_REQUEST['modfunc']=='delete' && basename($_SERVER['PHP_SELF'])!='index.php' && AllowEdit())
{
	if(DeletePrompt(_('User')))
	{
		DBQuery("DELETE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".UserStaffID()."'");
		DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='".UserStaffID()."'");
		DBQuery("DELETE FROM STUDENTS_JOIN_USERS WHERE STAFF_ID='".UserStaffID()."'");
		DBQuery("DELETE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'");
//modif Francois: Moodle integrator
		$moodleError = Moodle($_REQUEST['modname'], 'core_user_delete_users');
		unset($_SESSION['staff_id']);
		unset($_REQUEST['staff_id']);
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['staff_id']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
		Search('staff_id',$extra);
	}
}

if((UserStaffID() || $_REQUEST['staff_id']=='new') && ((basename($_SERVER['PHP_SELF'])!='index.php') || !$_REQUEST['staff']['USERNAME']) && $_REQUEST['modfunc']!='delete')
{
	if($_REQUEST['staff_id']!='new')
	{
		$sql = "SELECT s.STAFF_ID,s.TITLE,s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.NAME_SUFFIX,
						s.USERNAME,s.PASSWORD,s.SCHOOLS,s.PROFILE,s.PROFILE_ID,s.PHONE,s.EMAIL,s.LAST_LOGIN,s.SYEAR,s.ROLLOVER_ID
				FROM STAFF s WHERE s.STAFF_ID='".UserStaffID()."'";
		$staff = DBGet(DBQuery($sql));
		$staff = $staff[1];
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&category_id='.$_REQUEST['category_id'].'&modfunc=update" method="POST">';
	}
	elseif(basename($_SERVER['PHP_SELF'])!='index.php')
		echo '<FORM name="staff" action="Modules.php?modname='.$_REQUEST['modname'].'&include='.$_REQUEST['include'].'&category_id='.$_REQUEST['category_id'].'&modfunc=update" method="POST">';
	else
		echo '<FORM action="index.php?modfunc=create_account" METHOD="POST">';

	if(basename($_SERVER['PHP_SELF'])!='index.php')
	{
		if(UserStaffID() && UserStaffID()!=User('STAFF_ID') && UserStaffID()!=$_SESSION['STAFF_ID'] && User('PROFILE')=='admin' && AllowEdit())
		{
			$delete_button = '<script type="text/javascript">var delete_link = document.createElement("a"); delete_link.href = "Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete"; delete_link.target = "body";</script>';
			$delete_button .= '<INPUT type="button" value="'._('Delete').'" onClick="javascript:ajaxLink(delete_link);" />';
		}
	}

	if($_REQUEST['staff_id']!='new')
	{
		//modif Francois: add translation
		$titles_array = array('Mr'=>_('Mr'),'Mrs'=>_('Mrs'),'Ms'=>_('Ms'),'Miss'=>_('Miss'),'Dr'=>_('Dr'));
		$suffixes_array = array('Jr'=>_('Jr'),'Sr'=>_('Sr'),'II'=>_('II'),'III'=>_('III'),'IV'=>_('IV'),'V'=>_('V'));
		
		$name = $titles_array[$staff['TITLE']].' '.$staff['FIRST_NAME'].' '.$staff['MIDDLE_NAME'].' '.$staff['LAST_NAME'].' '.$suffixes_array[$staff['NAME_SUFFIX']].' - '.$staff['STAFF_ID'];
	}
	DrawHeader($name,$delete_button.SubmitButton(_('Save')));

	if(User('PROFILE_ID'))
		$can_use_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));
	else
		$can_use_RET = DBGet(DBQuery("SELECT MODNAME FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));
	$profile = DBGet(DBQuery("SELECT PROFILE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
	$profile = $profile[1]['PROFILE'];
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE,INCLUDE FROM STAFF_FIELD_CATEGORIES WHERE ".($profile?mb_strtoupper($profile).'=\'Y\'':'ID=\'1\'')." ORDER BY SORT_ORDER,TITLE"));

	foreach($categories_RET as $category)
	{
		if($can_use_RET['Users/User.php&category_id='.$category['ID']])
		{
				if($category['ID']=='1')
					$include = 'General_Info';
				elseif($category['ID']=='2')
					$include = 'Schedule';
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
	PopTable('header',$tabs,'width="100%"');

	if ($can_use_RET['Users/User.php&category_id='.$_REQUEST['category_id']])
	{
		if(!mb_strpos($_REQUEST['include'],'/'))
			include('modules/Users/includes/'.$_REQUEST['include'].'.inc.php');
		else
		{
			include('modules/'.$_REQUEST['include'].'.inc.php');
			$separator = '<HR>';
			include('modules/Users/includes/Other_Info.inc.php');
		}
	}
	PopTable('footer');
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';

	//modif Francois: user photo upload using jQuery form
	//move this form into General_Info.inc.php's form via Javascript
	if (AllowEdit() && $moveFormUserPhotoHere && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		?>
		<form method="POST" enctype="multipart/form-data" id="formUserPhoto" action="modules/misc/PhotoUpload.php" style="display:none;">
			<br />
			<input type="file" id="photo" name="photo" accept="image/*" />
			<input type="hidden" id="userId" name="userId" value="<?php echo UserStaffID(); ?>" />
			<input type="hidden" id="sYear" name="sYear" value="<?php echo UserSyear(); ?>" />
			<input type="hidden" id="photoPath" name="photoPath" value="<?php echo $UserPicturesPath; ?>" />
			<input type="hidden" id="modname" name="modname" value="<?php echo $_REQUEST['modname']; ?>" />
			<input type="hidden" id="Error1" name="Error1" value="<?php echo _('Error').': '._('File not uploaded'); ?>" />
			<input type="hidden" id="Error2" name="Error2" value="<?php echo _('Error').': '._('Wrong file type: %s (JPG required)'); ?>" />
			<input type="hidden" id="Error3" name="Error3" value="<?php echo _('Error').': '._('File size > %01.2fMb: %01.2fMb'); ?>" />
			<input type="hidden" id="Error4" name="Error4" value="<?php echo _('Error').': '._('Folder not created').': %s'; ?>" />
			<input type="hidden" id="Error5" name="Error5" value="<?php echo _('Error').': '._('Folder not writable').': %s'; ?>" />
			<BR /><span class="legend-gray"><?php echo _('User Photo'); ?> (.jpg)</span>

			<BR /><div style="float: right;"><input type="submit" value="<?php echo _('Submit'); ?>" style="margin-right:2px;" /></div>
			<BR /><span id="outputUserPhoto"></span>
		</form>
		<?php
	}
}
?>