<?php

//modif Francois: Moodle integrator

//The function {moodle_functionname}_object() is in charge of creating the object
//The function moodle_xmlrpc_call() sends the object to Moodle via XML-RPC
//returns a message: Error or Success
function Moodle($modname, $moodle_functionname)
{
	if (!MOODLE_INTEGRATOR)
		return '';
		
	$message = '';
	
	require_once('modules/Moodle/'.$modname);
	require_once('modules/Moodle/client.php');
	
	//first, get the right object corresponding to the web service
	$object = call_user_func($moodle_functionname.'_object');
	
	//finally, send the object
	$message = moodle_xmlrpc_call($moodle_functionname, $object);

	return $message;
}

//modif Francois: Moodle integrator / password
//The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character
function MoodlePasswordCheck($password)
{
	if (MOODLE_INTEGRATOR)
	{
		if (mb_strlen($password)<8 || !preg_match('/[^a-zA-Z0-9]+/', $password) || !preg_match('/[a-z]+/', $password) || !preg_match('/[A-Z]+/', $password) || !preg_match('/[0-9]+/', $password))
		{
			return false;
		}
	}
	return true;
}
?>