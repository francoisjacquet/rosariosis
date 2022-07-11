<?php
//FJ Moodle integrator

//core_course_create_courses function
function core_course_create_courses_object()
{
	//first, gather the necessary variables
	global $rolled_course_period, $next_syear;

	//then, convert variables for the Moodle object:
	/*
	list of (
	object {
	fullname string   //full name
	shortname string   //course short name
	categoryid int   //category id
	idnumber string  Optionnel //id number
	summary string  Optionnel //summary
	summaryformat int  Défaut pour « 1 » //summary format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	format string  Défaut pour « weeks » //course format: weeks, topics, social, site,..
	showgrades int  Défaut pour « 1 » //1 if grades are shown, otherwise 0
	newsitems int  Défaut pour « 5 » //number of recent items appearing on the course page
	startdate int  Optionnel //timestamp when the course start
	numsections int  Défaut pour « 10 » //number of weeks/topics
	maxbytes int  Défaut pour « 8388608 » //largest size of file that can be uploaded into the course
	showreports int  Défaut pour « 0 » //are activity report shown (yes = 1, no =0)
	visible int  Optionnel //1: available to student, 0:not available
	hiddensections int  Défaut pour « 0 » //How the hidden sections in the course are displayed to students
	groupmode int  Défaut pour « 0 » //no group, separate, visible
	groupmodeforce int  Défaut pour « 0 » //1: yes, 0: no
	defaultgroupingid int  Défaut pour « 0 » //default grouping id
	enablecompletion int  Optionnel //Enabled, control via completion and activity settings. Disabled,
	not shown in activity settings.
	completionstartonenrol int  Optionnel //1: begin tracking a student's progress in course completion after
	course enrolment. 0: does not
	completionnotify int  Optionnel //1: yes 0: no
	lang string  Optionnel //forced course language
	forcetheme string  Optionnel //name of the force theme
	}
	)
	 */

	$mp_short_name = '';

	//if marking period != full year, add short name

	if ( GetMP( $rolled_course_period['MARKING_PERIOD_ID'], 'MP' ) != 'FY' )
	{
		$mp_short_name = ' - ' . GetMP( $rolled_course_period['MARKING_PERIOD_ID'], 'SHORT_NAME' );
	}

	//add the year to the course name
	$fullname = FormatSyear( $next_syear, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . $mp_short_name . ' - ' .
		$rolled_course_period['SHORT_NAME'];

	$shortname = $rolled_course_period['SHORT_NAME'];

	//get the Moodle category
	$categoryid = MoodleXRosarioGet( 'course_id', $rolled_course_period['COURSE_ID'] );

	if ( empty( $categoryid ) )
	{
		return null;
	}

	$idnumber = (string) $rolled_course_period['COURSE_PERIOD_ID'];
	$summaryformat = 1;
	$format = 'weeks';
	$showgrades = 1;
	$newsitems = 5;
	//convert YYYY-MM-DD to timestamp
	$startdate = strtotime( GetMP( $rolled_course_period['MARKING_PERIOD_ID'], 'START_DATE' ) );
	$numsections = 10;
	$maxbytes = 8388608;
	$showreports = 1;
	$hiddensections = 0;
	$groupmode = 0;
	$groupmodeforce = 0;
	$defaultgroupingid = 0;

	$courses = [
		[
			'fullname' => $fullname,
			'shortname' => $shortname,
			'categoryid' => $categoryid,
			'idnumber' => $idnumber,
			'format' => $format,
			'summaryformat' => $summaryformat,
			'showgrades' => $showgrades,
			'newsitems' => $newsitems,
			'startdate' => $startdate,
			'numsections' => $numsections,
			'maxbytes' => $maxbytes,
			'showreports' => $showreports,
			'hiddensections' => $hiddensections,
			'groupmode' => $groupmode,
			'groupmodeforce' => $groupmodeforce,
			'defaultgroupingid' => $defaultgroupingid,
		],
	];

	return [ $courses ];
}

/**
 * @param $response
 */
function core_course_create_courses_response( $response )
{
	//first, gather the necessary variables
	global $rolled_course_period;

	//then, save the ID in the moodlexrosario cross-reference table:
	/*
	list of (
	object {
	id int   //course id
	shortname string   //short name
	}
	)*/

	DBQuery( "INSERT INTO moodlexrosario (" . DBEscapeIdentifier( 'column' ) . ",rosario_id,moodle_id)
		VALUES('course_period_id','" . $rolled_course_period['COURSE_PERIOD_ID'] . "'," . $response[0]['id'] . ")" );

	return null;
}

//core_role_assign_roles function
function core_role_assign_roles_object()
{
	//first, gather the necessary variables
	global $rolled_course_period;

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

	//teacher's roleid = teacher = 3
	$roleid = 3;

	//get the Moodle user ID
	$userid = MoodleXRosarioGet( 'staff_id', $rolled_course_period['TEACHER_ID'] );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle course ID
	$courseid = MoodleXRosarioGet( 'course_period_id', $rolled_course_period['COURSE_PERIOD_ID'] );

	if ( empty( $courseid ) )
	{
		return null;
	}

	$contextlevel = 'course';
	$instanceid = $courseid;

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
	global $cp_moodle_id, $cp_teacher_id;

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
	$userid = MoodleXRosarioGet( 'staff_id', $cp_teacher_id );

	if ( empty( $userid ) )
	{
		return null;
	}

	//teacher's roleid = teacher = 3
	$roleid = 3;

	//gather the Moodle course period ID
	$courseperiodid = $cp_moodle_id;

	$contextlevel = 'course';
	$instanceid = $courseperiodid;

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

//core_course_delete_courses function
function core_course_delete_courses_object()
{
	global $cp_moodle_id;

	//gather the Moodle course ID
	$id = $cp_moodle_id;

	//then, convert variables for the Moodle object:
	/*
	list of (
	int   //course ID
	)*/

	$courses = [ $id ];

	return [ $courses ];
}

/**
 * @param $response
 */
function core_course_delete_courses_response( $response )
{
	global $cp_moodle_id;

	//delete the reference the moodlexrosario cross-reference table:
	DBQuery( "DELETE FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'column' ) . "='course_period_id'
		AND moodle_id='" . $cp_moodle_id . "'" );

	return null;
}
