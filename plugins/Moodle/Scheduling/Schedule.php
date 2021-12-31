<?php
//FJ Moodle integrator

//enrol_manual_unenrol_users function
function enrol_manual_unenrol_users_object()
{
	//first, gather the necessary variables
	global $course_period_id;

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
	$userid = MoodleXRosarioGet( 'student_id', UserStudentID() );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle course period ID
	$courseid = MoodleXRosarioGet( 'course_period_id', $course_period_id );

	if ( empty( $courseid ) )
	{
		return null;
	}

	//student's roleid = student = 5
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
	$userid = MoodleXRosarioGet( 'student_id', UserStudentID() );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle course ID
	$courseid = MoodleXRosarioGet( 'course_period_id', $_REQUEST['course_period_id'] );

	if ( empty( $courseid ) )
	{
		return null;
	}

	//convert YYYY-MM-DD to timestamp
	$timestart = strtotime( $date );

	$enrolments = [
		[
			'roleid' => $roleid,
			'userid' => $userid,
			'courseid' => $courseid,
			'timestart' => $timestart,
		],
	];

	return [ $enrolments ];
}

/**
 * @param $response
 */
function enrol_manual_enrol_users_response( $response )
{
	return null;
}
