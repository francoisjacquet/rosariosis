<?php
//FJ Moodle integrator


//core_role_unassign_roles function
function core_role_unassign_roles_object()
{
	//first, gather the necessary variables
	global $course_period_id;


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
		WHERE rosario_id='".UserStudentID()."'
		AND \"column\"='student_id'" );

	if (empty($userid))
	{
		return null;
	}

	//gather the Moodle course period ID
	$courseperiodid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$course_period_id."'
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


//enrol_manual_enrol_users function
function enrol_manual_enrol_users_object()
{
	//first, gather the necessary variables
	global $_REQUEST, $date;


	//then, convert variables for the Moodle object:
/*
list of (
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be enrolled
		courseid int   //The course to enrol the user role in
		timestart int  Optionnel //Timestamp when the enrolment start
		timeend int  Optionnel //Timestamp when the enrolment end
		suspend int  Optionnel //set to 1 to suspend the enrolment
	}
)*/

	//student's roleid = student = 5
	$roleid = 5;

	//get the Moodle user ID
	$userid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".UserStudentID()."'
		AND \"column\"='student_id'" );

	if (empty($userid))
	{
		return null;
	}

	//gather the Moodle course ID
	$courseid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$_REQUEST['course_period_id']."'
		AND \"column\"='course_period_id'" );

	if (empty($courseid))
	{
		return null;
	}

	//convert YYYY-MM-DD to timestamp
	$timestart = strtotime($date);

	$enrolments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'courseid' => $courseid,
							'timestart' => $timestart,
						)
					);

	return array($enrolments);
}


function enrol_manual_enrol_users_response($response)
{
	return null;
}
