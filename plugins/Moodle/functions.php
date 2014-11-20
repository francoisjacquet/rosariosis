<?php

require_once('plugins/Moodle/config.inc.php');

//modif Francois: convert Moodle integrator to plugin

//check Moodle plugin configuration options are set
if (MOODLE_URL && MOODLE_TOKEN && MOODLE_PARENT_ROLE_ID && ROSARIO_STUDENTS_EMAIL_FIELD_ID)
{
	//Register plugin functions to be hooked
	add_action('Students/Student.php|header', 'MoodleTriggered', 1);

	add_action('Students/Student.php|create_student_checks', 'MoodleTriggered', 1);

	add_action('Students/Student.php|create_student', 'MoodleTriggered', 1);

	add_action('Students/Student.php|update_student_checks', 'MoodleTriggered', 1);

	add_action('Students/Student.php|update_student', 'MoodleTriggered', 1);

	add_action('Students/Student.php|upload_student_photo', 'MoodleTriggered', 1);


	add_action('Users/User.php|header', 'MoodleTriggered', 1);

	add_action('Users/User.php|create_user_checks', 'MoodleTriggered', 1);

	add_action('Users/User.php|create_user', 'MoodleTriggered', 1);

	add_action('Users/User.php|update_user_checks', 'MoodleTriggered', 1);

	add_action('Users/User.php|update_user', 'MoodleTriggered', 1);

	add_action('Users/User.php|upload_user_photo', 'MoodleTriggered', 1);

}


//Triggered function
//Will redirect to Moodle() function wit the right function name
function MoodleTriggered($hook_tag)
{
	//check Moodle plugin configuration options are set
	if (!MOODLE_URL || !MOODLE_TOKEN || !MOODLE_PARENT_ROLE_ID || !ROSARIO_STUDENTS_EMAIL_FIELD_ID)
		return false;

	$exploded = explode('|', $hook_tag);
	$modname = $exploded[0];
	$action = $exploded[1];

	$moodle_functionname = $modname = '';

	switch($hook_tag)
	{

/***************STUDENTS**/
		/*Students/Student.php*/
		case 'Students/Student.php|header':
			//propose to create student in Moodle: if 1) this is a creation, 2) this is an already created student but not in Moodle yet
			if (AllowEdit() && $_REQUEST['include']=='General_Info')
			{
				//2) verify the student is not in Moodle:
				if (UserStudentID())
					$old_student_in_moodle = IsMoodleStudent(UserStudentID());
			
				if ($_REQUEST['student_id']=='new' || !$old_student_in_moodle)
					DrawHeader('<label>'.CheckBoxOnclick('moodle_create_student').'&nbsp;'._('Create Student in Moodle').'</label>');
			}

		break;

		case 'Students/Student.php|create_student_checks':
			if ($_REQUEST['moodle_create_student'] && !MoodlePasswordCheck($_REQUEST['students']['PASSWORD']))
				$error[] = _('Please enter a valid password');

			//username, password, (email) required
			if ($_REQUEST['moodle_create_student'] && (empty($_REQUEST['students']['USERNAME']) || empty($_REQUEST['students']['CUSTOM_'.ROSARIO_STUDENTS_EMAIL_FIELD_ID])))
				$error[] = _('Please fill in the required fields');
			
		break;

		case 'Students/Student.php|create_student':
			if($_REQUEST['moodle_create_student'])
				Moodle($modname, 'core_user_create_users');

		break;

		case 'Students/Student.php|update_student_checks':
			if(!empty($_REQUEST['students']['PASSWORD'])
			{
				if (IsMoodleStudent(UserStudentID()) && !MoodlePasswordCheck($_REQUEST['students']['PASSWORD']))
					$error[] = _('Please enter a valid password');
			}

		break;

		case 'Students/Student.php|update_student':
			if($_REQUEST['moodle_create_student'])
			{
				Moodle($modname, 'core_user_create_users');
				//relate parent if exists
				Moodle($modname, 'core_role_assign_roles');
			}
			else
				Moodle($modname, 'core_user_update_users');

		break;

		case 'Students/Student.php|upload_student_photo':
			Moodle($modname, 'core_files_upload');

		break;

/***************USERS**/
		/*Users/User.php*/
		case 'Users/User.php|header':
			//propose to create user in Moodle: if 1) this is a creation, 2) this is an already created user but not in Moodle yet
			if (AllowEdit() && $_REQUEST['include']=='General_Info')
			{
				//2) verify the user is not in Moodle:
				if (UserStaffID())
					$old_user_in_moodle = IsMoodleUser(UserStaffID());
		
				if ($_REQUEST['staff_id']=='new' || !$old_user_in_moodle)
					DrawHeader('<label>'.CheckBoxOnclick('moodle_create_user').'&nbsp;'._('Create User in Moodle').'</label>');
			}

		break;

		case 'Users/User.php|create_user_checks':
			if ($_REQUEST['moodle_create_user'] && !MoodlePasswordCheck($_REQUEST['staff']['PASSWORD']))
				$error[] = _('Please enter a valid password');

			//username, email required
			if ($_REQUEST['moodle_create_user'] && (empty($_REQUEST['staff']['USERNAME']) || empty($_REQUEST['staff']['EMAIL'])))
			{
				$error[] = _('Please fill in the required fields');
			}

		break;

		case 'Users/User.php|create_user':
			if ($_REQUEST['moodle_create_user'])
			{
				Moodle($modname, 'core_user_create_users');
				Moodle($modname, 'core_role_assign_roles');
			}

		break;
			
		case 'Users/User.php|update_user_checks':
			if(!empty($_REQUEST['staff']['PASSWORD'])
			{
				if (IsMoodleUser(UserStaffID()) && !MoodlePasswordCheck($_REQUEST['staff']['PASSWORD']))
					$error[] = _('Please enter a valid password');
			}

		break;
			
		case 'Users/User.php|update_user':
			if ($_REQUEST['moodle_create_user'])
			{
				Moodle($modname, 'core_user_create_users');
				Moodle($modname, 'core_role_assign_roles');
			}
			else
			{
				Moodle($modname, 'core_user_update_users');
				Moodle($modname, 'core_role_unassign_roles');
				Moodle($modname, 'core_role_assign_roles');
			}

		break;

		case 'Users/User.php|upload_user_photo':
			Moodle($modname, 'core_files_upload');

		break;

		default:
			return false;
	}

	return true;
}

//modif Francois: Moodle integrator

//The function {moodle_functionname}_object() is in charge of creating the object
//The function moodle_xmlrpc_call() sends the object to Moodle via XML-RPC
function Moodle($modname, $moodle_functionname)
{
	require_once('plugins/Moodle/'.$modname);
	require_once('plugins/Moodle/client.php');

	//first, get the right object corresponding to the web service
	$object = call_user_func($moodle_functionname.'_object');

	//finally, send the object
	moodle_xmlrpc_call($moodle_functionname, $object);
}

//modif Francois: Moodle integrator / password
//The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character
function MoodlePasswordCheck($password)
{
	if (mb_strlen($password)<8 || !preg_match('/[^a-zA-Z0-9]+/', $password) || !preg_match('/[a-z]+/', $password) || !preg_match('/[A-Z]+/', $password) || !preg_match('/[0-9]+/', $password))
	{
		return false;
	}
	return true;
}

function IsMoodleStudent($student_id)
{
	return count(DBGet(DBQuery("SELECT 1 FROM moodlexrosario WHERE rosario_id='".$student_id."' AND \"column\"='student_id'")));
}

function IsMoodleUser($staff_id)
{
	return count(DBGet(DBQuery("SELECT 1 FROM moodlexrosario WHERE rosario_id='".$staff_id."' AND \"column\"='staff_id'")));
}

?>
