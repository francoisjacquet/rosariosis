<?php

include('ProgramFunctions/FileUpload.fnc.php');
if(User('PROFILE')!='admin' && User('PROFILE')!='teacher' && $_REQUEST['staff_id'] && $_REQUEST['staff_id']!=User('STAFF_ID') && $_REQUEST['staff_id']!='new')
{
	if(User('USERNAME'))
	{
		include('ProgramFunctions/HackingLog.fnc.php');
		HackingLog();
	}
		
	exit;
}

$categories = array('1'=>'General_Info', '2'=>'Schedule', 'Other_Info'=>'Other_Info');

if(!isset($_REQUEST['category_id']))
{
	$category_id = '1';
	$include = 'General_Info';
}
else
{
	$category_id = $_REQUEST['category_id'];

	if(in_array($_REQUEST['category_id'], array_keys($categories)))
	{
		$include = $categories[$category_id];
	}
	else
	{
		$category_include = DBGet(DBQuery("SELECT INCLUDE FROM STAFF_FIELD_CATEGORIES WHERE ID='".$_REQUEST['category_id']."'"));

		if(count($category_include))
		{
			$include = $category_include[1]['INCLUDE'];

			if ( empty( $include ) )
				$include = $categories['Other_Info'];
		}
		//FJ Prevent $_REQUEST['category_id'] hacking
		else
		{
			$category_id = '1';
			$include = 'General_Info';
		}
	}
}

if(User('PROFILE')!='admin')
{
	if(User('PROFILE_ID'))
		$can_edit_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND MODNAME='Users/User.php&category_id=".$category_id."' AND CAN_EDIT='Y'"));
	else
		$can_edit_RET = DBGet(DBQuery("SELECT MODNAME FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND MODNAME='Users/User.php&category_id=".$category_id."' AND CAN_EDIT='Y'"),array(),array('MODNAME'));
	if($can_edit_RET)
		$_ROSARIO['allow_edit'] = true;
}

if($_REQUEST['modfunc']=='update' && AllowEdit())
{
	if ( isset( $_POST['day_staff'] )
		&& isset( $_POST['month_staff'] )
		&& isset( $_POST['year_staff'] ) )
	{
		foreach( (array)$_REQUEST['month_staff'] as $column => $value )
		{
			$_REQUEST['staff'][$column] =
			$_POST['staff'][$column] = RequestedDate(
				$_REQUEST['day_staff'][$column],
				$value,
				$_REQUEST['year_staff'][$column]
			);
		}
	}

	if($_REQUEST['staff']['SCHOOLS'])
	{
		$current_schools = ',';
		if ($_REQUEST['staff_id']!='new')
		{
			$current_schools_RET = DBGet(DBQuery("SELECT SCHOOLS FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
			if (!empty($current_schools_RET[1]['SCHOOLS']))
				$current_schools = $current_schools_RET[1]['SCHOOLS'];
		}

		$schools = $current_schools;
		foreach($_REQUEST['staff']['SCHOOLS'] as $school_id=>$yes)
		{
			if ($yes == 'Y' && mb_strpos($current_schools, ','.$school_id.',')===false)
				$schools .= $school_id.',';
			elseif ($yes != 'Y' && mb_strpos($current_schools, ','.$school_id.',')!==false)
				$schools = str_replace($school_id.',', '', $schools);
		}

		//FJ remove Schools for Parents
		if(isset($_REQUEST['staff']['PROFILE']) && $_REQUEST['staff']['PROFILE']=='parent')
			$_REQUEST['staff']['SCHOOLS'] = '';
		else
			$_REQUEST['staff']['SCHOOLS'] = ($schools == ',' ? '' : $schools);

		//FJ reset current school if updating self schools
		if(User('STAFF_ID') == UserStaffID())
			unset($_SESSION['UserSchool']);
	}
/*	else
		$_REQUEST['staff']['SCHOOLS'] = $_POST['staff'] = '';*/

	if( isset( $_POST['staff'] )
		&& count( $_POST['staff'] )
		&& ( User( 'PROFILE' ) === 'admin'
			|| basename( $_SERVER['PHP_SELF'] ) === 'index.php' ) )
	{
		$required_error = false;

		//FJ fix SQL bug FIRST_NAME, LAST_NAME is null
		if ((isset($_REQUEST['staff']['FIRST_NAME']) && empty($_REQUEST['staff']['FIRST_NAME'])) || (isset($_REQUEST['staff']['LAST_NAME']) && empty($_REQUEST['staff']['LAST_NAME'])))
			$required_error = true;

		//FJ other fields required
		$others_required_RET = DBGet(DBQuery("SELECT ID FROM STAFF_FIELDS WHERE CATEGORY_ID='".$category_id."' AND REQUIRED='Y'"));
		if (count($others_required_RET))
			foreach($others_required_RET as $other_required)
				if (isset($_REQUEST['staff']['CUSTOM_'.$other_required['ID']]) && empty($_REQUEST['staff']['CUSTOM_'.$other_required['ID']]))
					$required_error = true;

		//FJ create account
		if (basename($_SERVER['PHP_SELF'])=='index.php')
		{
			//username & password required
			if (empty($_REQUEST['staff']['USERNAME']) || empty($_REQUEST['staff']['PASSWORD']))
				$required_error = true;

			//check if trying to hack profile (would result in an SQL error)
			if (isset($_REQUEST['staff']['PROFILE']))
			{
				include('ProgramFunctions/HackingLog.fnc.php');
				HackingLog();
			}
		}

		if ($required_error)
			$error[] = _('Please fill in the required fields');

		//check username unicity
		$existing_username = DBGet(DBQuery("SELECT 'exists' FROM STAFF WHERE USERNAME='".$_REQUEST['staff']['USERNAME']."' AND SYEAR='".UserSyear()."' AND STAFF_ID!='".UserStaffID()."' UNION SELECT 'exists' FROM STUDENTS WHERE USERNAME='".$_REQUEST['staff']['USERNAME']."'"));
		if(count($existing_username))
		{
			$error[] = _('A user with that username already exists. Choose a different username and try again.');
		}

		if(UserStaffID() && !isset($error))
		{

			//hook
			do_action('Users/User.php|update_user_checks');

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

			if (!isset($error))
			{
				$sql = "UPDATE STAFF SET ";
				$fields_RET = DBGet(DBQuery("SELECT ID,TYPE FROM STAFF_FIELDS ORDER BY SORT_ORDER"), array(), array('ID'));
				$go = false;
				foreach($_REQUEST['staff'] as $column_name=>$value)
				{
					if(1)//!empty($value) || $value=='0')
					{
						//FJ check numeric fields
						if ($fields_RET[str_replace('CUSTOM_','',$column_name)][1]['TYPE'] == 'numeric' && $value!='' && !is_numeric($value))
						{
							$error[] = _('Please enter valid Numeric data.');
							continue;
						}
						
						//FJ add password encryption
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
				}
				$sql = mb_substr($sql,0,-1) . " WHERE STAFF_ID='".UserStaffID()."'";

				if($go)
				{
					DBQuery($sql);
					
					//hook
					do_action('Users/User.php|update_user');
				}
			}
		
		}
		elseif (!isset($error)) //new user
		{

			//hook
			do_action('Users/User.php|create_user_checks');

			if($_REQUEST['staff']['PROFILE']=='admin')
				$_REQUEST['staff']['PROFILE_ID'] = '1';
			elseif($_REQUEST['staff']['PROFILE']=='teacher')
				$_REQUEST['staff']['PROFILE_ID'] = '2';
			elseif($_REQUEST['staff']['PROFILE']=='parent')
				$_REQUEST['staff']['PROFILE_ID'] = '3';

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

				$fields_RET = DBGet(DBQuery("SELECT ID,TYPE FROM STAFF_FIELDS ORDER BY SORT_ORDER"), array(), array('ID'));
				foreach($_REQUEST['staff'] as $column=>$value)
				{
					if(!empty($value) || $value=='0')
					{
						//FJ check numeric fields
						if ($fields_RET[str_replace('CUSTOM_','',$column)][1]['TYPE'] == 'numeric' && $value!='' && !is_numeric($value))
						{
							$error[] = _('Please enter valid Numeric data.');
							break;
						}
						
						$fields .= $column.',';

						//FJ add password encryption
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
				
				SetUserStaffID($_REQUEST['staff_id'] = $staff_id);

				//hook
				do_action('Users/User.php|create_user');

				//Notify the network admin that a new admin has been created
				if ( $_REQUEST['staff']['PROFILE_ID'] == 1
					&& filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
				{
					//FJ add SendEmail function
					include_once('ProgramFunctions/SendEmail.fnc.php');

					$to = $RosarioNotifyAddress;

					$admin_name = $_REQUEST['staff']['FIRST_NAME'].' '.$_REQUEST['staff']['LAST_NAME'];
					$subject = sprintf('New Admin Added: %s', $admin_name);

					$admin_username = empty($_REQUEST['staff']['USERNAME']) ? '[no username]' : $_REQUEST['staff']['USERNAME'];

					if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
						$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					else
						$ip = $_SERVER['REMOTE_ADDR'];

					$message = sprintf('New User: %s
Added by: %s
Remote IP: %s', $admin_username, User('NAME'), $ip);

					SendEmail($to, $subject, $message);
				}
			}
		}

		if (UserStaffID() && $_FILES['photo'])
		{
			$new_photo_file = FileUpload('photo', $UserPicturesPath.UserSyear().'/', array('.jpg', '.jpeg'), 2, $error, '.jpg', UserStaffID());

			//hook
			do_action('Users/User.php|upload_user_photo');
		}

	}

	if(!in_array($include, $categories))
		if(!mb_strpos($include,'/'))
			include('modules/Users/includes/'.$include.'.inc.php');
		else
			include('modules/'.$include.'.inc.php');

	if ($error && !UserStaffID())
		$_REQUEST['staff_id'] = 'new';

	unset( $_REQUEST['staff'] );
	unset( $_REQUEST['modfunc'] );
	unset( $_SESSION['_REQUEST_vars']['staff'] );
	unset( $_SESSION['_REQUEST_vars']['modfunc'] );

	if ( User('STAFF_ID') == $_REQUEST['staff_id'] )
		unset( $_ROSARIO['User'] );
}

if(basename($_SERVER['PHP_SELF'])!='index.php')
{
	if($_REQUEST['staff_id']=='new')
	{
		$_ROSARIO['HeaderIcon'] = 'modules/Users/icon.png';
		DrawHeader(_('Add a User'));
	}
	else
		DrawHeader(ProgramTitle());
		Search('staff_id',$extra);
}
//FJ create account
elseif(!UserStaffID())
{
	$_ROSARIO['HeaderIcon'] = 'modules/Users/icon.png';
	DrawHeader(_('Create User Account'));
}
//account created, return to index
else
{
?>
	<script>window.location.href = "index.php?modfunc=logout&reason=account_created";</script>
<?php
	exit;
}

	
if(isset($error))
	echo ErrorMessage($error);

if($_REQUEST['modfunc']=='delete' && basename($_SERVER['PHP_SELF'])!='index.php' && AllowEdit())
{
	if(DeletePrompt(_('User')))
	{
		DBQuery("DELETE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".UserStaffID()."'");
		DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='".UserStaffID()."'");
		DBQuery("DELETE FROM STUDENTS_JOIN_USERS WHERE STAFF_ID='".UserStaffID()."'");
		DBQuery("DELETE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'");

		//hook
		do_action('Users/User.php|delete_user');

		unset($_SESSION['staff_id']);
		unset($_REQUEST['staff_id']);
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['staff_id']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		Search('staff_id',$extra);
	}
}

if((UserStaffID() || $_REQUEST['staff_id']=='new') && $_REQUEST['modfunc']!='delete')
{
	if($_REQUEST['staff_id']!='new')
	{
		$sql = "SELECT s.STAFF_ID,s.TITLE,s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.NAME_SUFFIX,
						s.USERNAME,s.PASSWORD,s.SCHOOLS,s.PROFILE,s.PROFILE_ID,s.PHONE,s.EMAIL,s.LAST_LOGIN,s.SYEAR,s.ROLLOVER_ID
				FROM STAFF s WHERE s.STAFF_ID='".UserStaffID()."'";
		$staff = DBGet(DBQuery($sql));
		$staff = $staff[1];
	}

	if(basename($_SERVER['PHP_SELF'])!='index.php')
		echo '<FORM name="staff" action="Modules.php?modname='.$_REQUEST['modname'].'&category_id='.$category_id.'&modfunc=update" method="POST" enctype="multipart/form-data">';
	else
		echo '<FORM action="index.php?create_account=user&staff_id=new&modfunc=update" METHOD="POST" enctype="multipart/form-data">';

	if(basename($_SERVER['PHP_SELF'])!='index.php')
	{
		if(UserStaffID() && UserStaffID()!=User('STAFF_ID') && UserStaffID()!=$_SESSION['STAFF_ID'] && User('PROFILE')=='admin' && AllowEdit())
		{
			$delete_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
				"&modfunc=delete'";

			$delete_button = '<INPUT type="button" value="' . _( 'Delete' ) . '" onClick="javascript:ajaxLink(' . $delete_URL . ');" />';
		}
	}

	if($_REQUEST['staff_id']!='new')
	{
		//FJ add translation
		$titles_array = array('Mr'=>_('Mr'),'Mrs'=>_('Mrs'),'Ms'=>_('Ms'),'Miss'=>_('Miss'),'Dr'=>_('Dr'));
		$suffixes_array = array('Jr'=>_('Jr'),'Sr'=>_('Sr'),'II'=>_('II'),'III'=>_('III'),'IV'=>_('IV'),'V'=>_('V'));
		
		$name = $titles_array[$staff['TITLE']].' '.$staff['FIRST_NAME'].' '.$staff['MIDDLE_NAME'].' '.$staff['LAST_NAME'].' '.$suffixes_array[$staff['NAME_SUFFIX']].' - '.$staff['STAFF_ID'];
	}

	DrawHeader($name,$delete_button.SubmitButton(_('Save')));

	//hook
	do_action('Users/User.php|header');

	if(User('PROFILE_ID'))
		$can_use_RET = DBGet(DBQuery("SELECT MODNAME FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));
	else
		$can_use_RET = DBGet(DBQuery("SELECT MODNAME FROM STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."' AND CAN_USE='Y'"),array(),array('MODNAME'));

	//FJ create account
	if(basename($_SERVER['PHP_SELF'])=='index.php')
		$can_use_RET['Users/User.php&category_id=1'] = true;

	$profile = DBGet(DBQuery("SELECT PROFILE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
	$profile = $profile[1]['PROFILE'];
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE,INCLUDE FROM STAFF_FIELD_CATEGORIES WHERE ".($profile?mb_strtoupper($profile).'=\'Y\'':'ID=\'1\'')." ORDER BY SORT_ORDER,TITLE"));

	foreach($categories_RET as $category)
	{
		if($can_use_RET['Users/User.php&category_id='.$category['ID']])
		{
			//FJ Remove $_REQUEST['include']
			/*if($category['ID']=='1')
				$include = 'General_Info';
			elseif($category['ID']=='2')
				$include = 'Schedule';
			elseif($category['INCLUDE'])
				$include = $category['INCLUDE'];
			else
				$include = 'Other_Info';*/

			$tabs[] = array('title'=>$category['TITLE'],'link'=>($_REQUEST['staff_id']!='new' ? 'Modules.php?modname='.$_REQUEST['modname'].'&category_id='.$category['ID'] : ''));
		}
	}

	$_ROSARIO['selected_tab'] = 'Modules.php?modname='.$_REQUEST['modname'].'&category_id='.$category_id;

	echo '<BR />';
	PopTable('header',$tabs,'width="100%"');
	$PopTable_opened = true;

	if ($can_use_RET['Users/User.php&category_id='.$category_id])
	{
		if(!mb_strpos($include,'/'))
			include('modules/Users/includes/'.$include.'.inc.php');
		else
		{
			include('modules/'.$include.'.inc.php');
			$separator = '<HR>';
			include('modules/Users/includes/Other_Info.inc.php');
		}
	}

	PopTable('footer');

	echo '<BR /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
	echo '</FORM>';
}
