<?php
//FJ Moodle integrator

//core_role_assign_roles function
function core_role_assign_roles_object()
{
	//first, gather the necessary variables
	global $student_id;

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
	$userid = MoodleXRosarioGet( 'staff_id', UserStaffID() );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle student ID
	$studentid = MoodleXRosarioGet( 'student_id', $student_id );

	if ( empty( $studentid ) )
	{
		return null;
	}

	$contextlevel = 'user';
	$roleid = MOODLE_PARENT_ROLE_ID;
	$instanceid = $studentid;

	$assignments = [
		[
			'roleid' => $roleid,
			'userid' => $userid,
			'contextlevel' => $contextlevel,
			'instanceid' => $instanceid,
		],
	];

	return [ $assignments ];
}

/**
 * @param $response
 */
function core_role_assign_roles_response( $response )
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
	$userid = MoodleXRosarioGet( 'staff_id', UserStaffID() );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle student ID
	$studentid = MoodleXRosarioGet( 'student_id', $_REQUEST['student_id_remove'] );

	if ( empty( $studentid ) )
	{
		return null;
	}

	$roleid = MOODLE_PARENT_ROLE_ID;
	$contextlevel = 'user';
	$instanceid = $studentid;

	$unassignments = [
		[
			'roleid' => $roleid,
			'userid' => $userid,
			'contextlevel' => $contextlevel,
			'instanceid' => $instanceid,
		],
	];

	return [ $unassignments ];
}

/**
 * @param $response
 */
function core_role_unassign_roles_response( $response )
{
	return null;
}
