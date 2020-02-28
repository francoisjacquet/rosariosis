<?php
//FJ Moodle integrator

//enrol_manual_unenrol_users function
function enrol_manual_unenrol_users_object()
{
	//first, gather the necessary variables
	global $student_id, $_SESSION;

	//then, convert variables for the Moodle object:
	/*
	list of (
		object {
			userid int   //The user that is going to be unenrolled
			courseid int   //The course to unenrol the user from
			roleid int  Optional //The user role
		}
	)*/
	//gather the Moodle user ID
	$userid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='" . $student_id . "'
		AND \"column\"='student_id'" );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle course period ID
	$courseid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='" . $_SESSION['MassDrops.php']['course_period_id'] . "'
		AND \"column\"='course_period_id'" );

	if ( empty( $courseid ) )
	{
		return null;
	}

	//student roleid = student = 5
	$roleid = 5;

	$enrolments = array(
		array(
			'userid' => $userid,
			'courseid' => $courseid,
			'roleid' => $roleid,
		),
	);

	return array( $enrolments );
}

/**
 * @param $response
 */
function enrol_manual_unenrol_users_response( $response )
{
	return null;
}
