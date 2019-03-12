<?php
//FJ Moodle integrator


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
		contextid int  Optional //The context to unassign the user role from
		contextlevel string  Optional //The context level to unassign the user role in
		+                                    (block, course, coursecat, system, user, module)
		instanceid int  Optional //The Instance id of item where the role needs to be unassigned
	}
)*/
	//gather the Moodle user ID
	$userid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$student_id."'
		AND \"column\"='student_id'" );

	if (empty($userid))
	{
		return null;
	}

	//student's roleid = student = 5
	$roleid = 5;

	//gather the Moodle course period ID
	$courseperiodid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$_SESSION['MassDrops.php']['course_period_id']."'
		AND \"column\"='course_period_id'" );

	if (empty($courseperiodid))
	{
		return null;
	}

	//student's roleid = student = 5
	$roleid = 5;

	$contextlevel = 'course';
	$instanceid = $courseperiodid;

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
