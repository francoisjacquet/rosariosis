<?php
//FJ Moodle integrator


//core_role_assign_roles function
function core_role_assign_roles_object()
{
	//first, gather the necessary variables
	global $staff_id;


	//then, convert variables for the Moodle object:
/*
list of (
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int  Optional //The context to assign the user role in
		contextlevel string  Optional //The context level to assign the user role in
				                      (block, course, coursecat, system, user, module)
		instanceid int  Optional //The Instance id of item where the role needs to be assigned
	}
)*/

	//gather the Moodle user ID
	$userid = DBGet( "SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$staff_id."' AND \"column\"='staff_id'" );
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	//gather the Moodle student ID
	$studentid = DBGet( "SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".UserStudentID()."' AND \"column\"='student_id'" );
	if (count($studentid))
	{
		$studentid = (int)$studentid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	$contextlevel = 'user';
	$roleid = MOODLE_PARENT_ROLE_ID;
	$instanceid = $studentid;

	$assignments = array(
				array(
					'roleid' => $roleid,
					'userid' => $userid,
					'contextlevel' => $contextlevel,
					'instanceid' => $instanceid,
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
		contextid int  Optional //The context to unassign the user role from
		contextlevel string  Optional //The context level to unassign the user role in
		+                                    (block, course, coursecat, system, user, module)
		instanceid int  Optional //The Instance id of item where the role needs to be unassigned
	}
)*/

	//gather the Moodle user ID
	$userid = DBGet( "SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['staff_id_remove']."' AND \"column\"='staff_id'" );
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	//gather the Moodle student ID
	$studentid = DBGet( "SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".UserStudentID()."' AND \"column\"='student_id'" );
	if (count($studentid))
	{
		$studentid = (int)$studentid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	$roleid = MOODLE_PARENT_ROLE_ID;
	$contextlevel = 'user';
	$instanceid = $studentid;

	$unassignments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'contextlevel' => $contextlevel,
							'instanceid' => $instanceid,
						)
					);

	return array($unassignments);
}


function core_role_unassign_roles_response($response)
{
	return null;
}
