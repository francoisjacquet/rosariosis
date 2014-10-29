<?php
//modif Francois: Moodle integrator


//core_role_unassign_roles function
function core_role_unassign_roles_object()
{
	//first, gather the necessary variables
	global $student_id, $_SESSION;
	
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int   //The context to unassign the user role from
	} 
)
*/
	//gather the Moodle user ID
	$userid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$student_id."' AND \"column\"='student_id'"));
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	//student's roleid = student = 5
	$roleid = 5;

	//get contextid:
	global $moodle_contextlevel, $moodle_instance;
	$moodle_contextlevel = CONTEXT_COURSE;
	$rosario_id = $_SESSION['MassDrops.php']['course_period_id'];
	//gather the Moodle course ID
	$moodle_instance = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rosario_id."' AND \"column\"='course_period_id'"));
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