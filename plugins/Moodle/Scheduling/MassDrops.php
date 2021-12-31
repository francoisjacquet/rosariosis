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
	$userid = MoodleXRosarioGet( 'student_id', $student_id );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle course period ID
	$courseid = MoodleXRosarioGet( 'course_period_id', $_SESSION['MassDrops.php']['course_period_id'] );

	if ( empty( $courseid ) )
	{
		return null;
	}

	//student roleid = student = 5
	$roleid = 5;

	$enrolments = [
		[
			'userid' => $userid,
			'courseid' => $courseid,
			'roleid' => $roleid,
		],
	];

	return [ $enrolments ];
}

/**
 * @param $response
 */
function enrol_manual_unenrol_users_response( $response )
{
	return null;
}
