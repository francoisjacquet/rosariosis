<?php
//modif Francois: Moodle integrator


//core_role_assign_roles function
function core_role_assign_roles_object()
{
	//first, gather the necessary variables
	global $staff_id, $_REQUEST;
	
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int   //The context to assign the user role in
	} 
)*/

	//gather the Moodle user ID
	$userid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$staff_id."' AND \"column\"='staff_id'"));
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	//get the contextid
	global $moodle_contextlevel, $moodle_instance;
	$moodle_contextlevel = CONTEXT_USER;
	//gather the Moodle user ID
	$moodle_instance = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".UserStudentID()."' AND \"column\"='student_id'"));
	if (count($moodle_instance))
	{
		$moodle_instance = (int)$moodle_instance[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	$contexts = Moodle('Global/functions.php', 'local_getcontexts_get_contexts');
	
	$contextid = $contexts[0]['id'];
	$roleid = MOODLE_PARENT_ROLE_ID;

	$assignments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'contextid' => $contextid,
						)
					);
	
	return array($assignments);
}


function core_role_assign_roles_response($response)
{
	return null;
}



//core_role_unassign_roles function
function core_role_unassign_roles_object()
{
	//first, gather the necessary variables
	global $_REQUEST;
	
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int   //The context to unassign the user role from
	} 
)*/

	//gather the Moodle user ID
	$userid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['staff_id']."' AND \"column\"='staff_id'"));
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	//get the contextid
	global $moodle_contextlevel, $moodle_instance;
	$moodle_contextlevel = CONTEXT_USER;
	//gather the Moodle user ID
	$moodle_instance = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".UserStudentID()."' AND \"column\"='student_id'"));
	if (count($moodle_instance))
	{
		$moodle_instance = (int)$moodle_instance[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	$contexts = Moodle('Global/functions.php', 'local_getcontexts_get_contexts');
	
	$contextid = $contexts[0]['id'];
	$roleid = MOODLE_PARENT_ROLE_ID;

	$unassignments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'contextid' => $contextid,
						)
					);
	
	return array($unassignments);
}


function core_role_unassign_roles_response($response)
{
	return null;
}
?>