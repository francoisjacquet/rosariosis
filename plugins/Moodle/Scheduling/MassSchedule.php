<?php
//FJ Moodle integrator

//enrol_manual_enrol_users function
function enrol_manual_enrol_users_object()
{
	//first, gather the necessary variables
	global $student_id, $cp_id, $start_date;

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
	$userid = MoodleXRosarioGet( 'student_id', $student_id );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle course ID
	$courseid = MoodleXRosarioGet( 'course_period_id', $cp_id );

	if ( empty( $courseid ) )
	{
		return null;
	}

	//convert YYYY-MM-DD to timestamp
	$timestart = strtotime( $start_date );

	$enrolments = [
		[
			'roleid' => $roleid,
			'userid' => $userid,
			'courseid' => $courseid,
			'timestart' => $timestart,
		],
	];

	if ( MOODLE_API_PROTOCOL === 'rest' )
	{
		return [ 'enrolments' => $enrolments ];
	}

	return [ $enrolments ];
}

/**
 * @param $response
 */
function enrol_manual_enrol_users_response( $response )
{
	return null;
}
