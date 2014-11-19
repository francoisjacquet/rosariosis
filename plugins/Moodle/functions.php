<?php

require_once('plugins/Moodle/config.inc.php');

//modif Francois: convert Moodle integrator to plugin

//check Moodle plugin configuration options are set
if (MOODLE_URL && MOODLE_TOKEN && MOODLE_PARENT_ROLE_ID && ROSARIO_STUDENTS_EMAIL_FIELD_ID)
{
	//Register plugin functions to be hooked
	add_action('Students/Student.php|create_student', 'MoodleTriggered', 1);
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
	$action = $exploded[1]

	$moodle_functionname = $modname = '';

	switch($hook_tag)
	{
		case 'Students/Student.php|update_student':
			if($_REQUEST['moodle_create_student'])
			{
				Moodle($modname, 'core_user_create_users');
				//relate parent if exist
				Moodle($modname, 'core_role_assign_roles');
			}
			else
				Moodle($modname, 'core_user_update_users');

		break;

		case 'Students/Student.php|create_student':
			if($_REQUEST['moodle_create_student'])
			{
				Moodle($modname, 'core_user_create_users');
				//relate parent if exist
				Moodle($modname, 'core_role_assign_roles');
			}

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

?>
