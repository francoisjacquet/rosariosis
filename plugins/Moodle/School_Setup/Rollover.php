<?php
//FJ Moodle integrator

//core_course_create_courses function
function core_course_create_courses_object()
{
	//first, gather the necessary variables
	global $rolled_course_period;

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

	$next_syear = UserSyear() + 1;

	//add the year to the course name
	$fullname = FormatSyear( $next_syear, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' - ' . $rolled_course_period['TITLE'];
	// Fix Moodle error Short name is already used for another course
	$shortname = FormatSyear( $next_syear, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' ' . $rolled_course_period['SHORT_NAME'];

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

	$marking_period_RET = DBGet( "SELECT START_DATE,END_DATE
		FROM school_marking_periods
		WHERE MARKING_PERIOD_ID='" . (int) $rolled_course_period['MARKING_PERIOD_ID'] . "'
		AND SYEAR='" . $next_syear . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	//convert YYYY-MM-DD to timestamp
	$startdate = strtotime( $marking_period_RET[1]['START_DATE'] );
	//convert YYYY-MM-DD to timestamp
	$enddate = strtotime( $marking_period_RET[1]['END_DATE'] );
	$numsections = 10;
	$maxbytes = 0;
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
			'enddate' => $enddate,
			'numsections' => $numsections,
			'maxbytes' => $maxbytes,
			'showreports' => $showreports,
			'hiddensections' => $hiddensections,
			'groupmode' => $groupmode,
			'groupmodeforce' => $groupmodeforce,
			'defaultgroupingid' => $defaultgroupingid,
		],
	];

	return [ 'courses' => $courses ];
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

	DBInsert(
		'moodlexrosario',
		[
			'COLUMN' => 'course_period_id',
			'ROSARIO_ID' => (int) $rolled_course_period['COURSE_PERIOD_ID'],
			'MOODLE_ID' => (int) $response[0]['id'],
		]
	);

	return null;
}

//enrol_manual_enrol_users function
function enrol_manual_enrol_users_object()
{
	//first, gather the necessary variables
	global $rolled_course_period;

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

	//convert YYYY-MM-DD to timestamp
	$timestart = strtotime( DBDate() );

	$enrolments = [
		[
			'roleid' => $roleid,
			'userid' => $userid,
			'courseid' => $courseid,
			'timestart' => $timestart,
		],
	];

	return [ 'enrolments' => $enrolments ];
}

/**
 * @param $response
 */
function enrol_manual_enrol_users_response( $response )
{
	return null;
}

//enrol_manual_unenrol_users function
function enrol_manual_unenrol_users_object()
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
	$courseid = $cp_moodle_id;

	$enrolments = [
		[
			'roleid' => $roleid,
			'userid' => $userid,
			'courseid' => $courseid,
		],
	];

	return [ 'enrolments' => $enrolments ];
}

/**
 * @param $response
 */
function enrol_manual_unenrol_users_response( $response )
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

	$courseids = [ $id ];

	return [ 'courseids' => $courseids ];
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
		AND moodle_id='" . (int) $cp_moodle_id . "'" );

	return null;
}
