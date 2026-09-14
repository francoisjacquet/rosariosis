<?php
//FJ Moodle integrator

//core_course_create_categories function
// @since 11.5 Send Course description HTML to Moodle
function core_course_create_categories_object()
{
	//first, gather the necessary variables
	global $_REQUEST, $table_name;

	//then, convert variables for the Moodle object:
	/*
	list of (
	object {
	name string   //new category name
	parent int  Défaut pour « 0 » //the parent category id inside which the new category will be created
	- set to 0 for a root category
	idnumber string  Optionnel //the new category idnumber
	description string  Optionnel //the new category description
	descriptionformat int  Défaut pour « 1 » //description format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	theme string  Optionnel //the new category theme. This option must be enabled on moodle
	}
	)*/

	if ( $table_name == 'course_subjects' )
	{
		$subject_RET = DBGet( "SELECT TITLE
			FROM course_subjects
			WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( empty( $subject_RET[1] ) )
		{
			return null;
		}

		$subject = $subject_RET[1];

		// @since 12.3 Multilingual course title
		// @todo send to Moodle using https://docs.moodle.org/38/en/Multi-language_content_filter
		$name = ParseMLField( $subject['TITLE'] );

		// @since 5.8 Ability to set a Parent Category to Subjects. Used by Iomad plugin.
		$parent = ! empty( $_REQUEST['MOODLE_COURSE_SUBJECT_PARENT_CATEGORY'] ) ?
			$_REQUEST['MOODLE_COURSE_SUBJECT_PARENT_CATEGORY'] : 0;

		$description = '';
	}
	elseif ( $table_name == 'courses' )
	{
		$course_RET = DBGet( "SELECT TITLE,DESCRIPTION
			FROM courses
			WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( empty( $course_RET[1] ) )
		{
			return null;
		}

		$course = $course_RET[1];

		// @since 12.3 Multilingual course title
		// @todo send to Moodle using https://docs.moodle.org/38/en/Multi-language_content_filter
		$name = ParseMLField( $course['TITLE'] );

		//get the Moodle parent category
		$parent = MoodleXRosarioGet( 'subject_id', $_REQUEST['subject_id'] );

		if ( empty( $parent ) )
		{
			return null;
		}

		// @since 11.5 Send Course description to Moodle
		$description = $course['DESCRIPTION'];
	}
	else //error...
	{
		return null;
	}

	$descriptionformat = 1;

	$categories = [
		[
			'name' => $name,
			'parent' => $parent,
			//'idnumber' => $idnumber,
			'description' => $description,
			'descriptionformat' => $descriptionformat,
		],
	];

	return [ 'categories' => $categories ];
}

/**
 * @param $response
 */
function core_course_create_categories_response( $response )
{
	//first, gather the necessary variables
	global $_REQUEST, $table_name;

	//then, save the ID in the moodlexrosario cross-reference table:
	/*
	list of (
	object {
	id int   //new category id
	name string   //new category name
	}
	)
	 */

	if ( empty( $response[0]['id'] ) )
	{
		// Fix SQL error when no ID returned.
		return null;
	}

	if ( $table_name == 'course_subjects' )
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}
	elseif ( $table_name == 'courses' )
	{
		$column = 'course_id';
		$rosario_id = (string) $_REQUEST['course_id'];
	}

	DBInsert(
		'moodlexrosario',
		[
			'COLUMN' => $column,
			'ROSARIO_ID' => (int) $rosario_id,
			'MOODLE_ID' => (int) $response[0]['id'],
		]
	);

	return null;
}

//core_course_update_categories function
// @since 11.5 Send Course description HTML to Moodle
function core_course_update_categories_object()
{
	//first, gather the necessary variables
	global $_REQUEST, $table_name;

	//then, convert variables for the Moodle object:
	/*
	list of (
	object {
	id int   //course id
	name string  Optionnel //category name
	idnumber string  Optionnel //category id number
	parent int  Optionnel //parent category id
	description string  Optionnel //category description
	descriptionformat int  Défaut pour « 1 » //description format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	theme string  Optionnel //the category theme. This option must be enabled on moodle
	}
	)
	 */
	//gather the Moodle category ID

	if ( $table_name == 'courses' )
	{
		$column = 'course_id';
		$rosario_id = $_REQUEST['course_id'];
	}
	elseif ( $table_name == 'course_subjects' )
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}

	$id = MoodleXRosarioGet( $column, $rosario_id );

	if ( empty( $id ) )
	{
		return null;
	}

	if ( $table_name == 'course_subjects' )
	{
		$subject_RET = DBGet( "SELECT TITLE
			FROM course_subjects
			WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( empty( $subject_RET[1] ) )
		{
			return null;
		}

		$subject = $subject_RET[1];

		// @since 12.3 Multilingual course title
		// @todo send to Moodle using https://docs.moodle.org/38/en/Multi-language_content_filter
		$name = ParseMLField( $subject['TITLE'] );

		$description = '';
	}
	elseif ( $table_name == 'courses' )
	{
		$course_RET = DBGet( "SELECT TITLE,DESCRIPTION
			FROM courses
			WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( empty( $course_RET[1] ) )
		{
			return null;
		}

		$course = $course_RET[1];

		// @since 12.3 Multilingual course title
		// @todo send to Moodle using https://docs.moodle.org/38/en/Multi-language_content_filter
		$name = ParseMLField( $course['TITLE'] );

		// @since 11.5 Send Course description to Moodle
		$description = $course['DESCRIPTION'];
	}
	else //error...
	{
		return null;
	}

	$descriptionformat = 1;

	$categories = [
		[
			'id' => $id,
			'name' => $name,
			'description' => $description,
			'descriptionformat' => $descriptionformat,
		],
	];

	return [ 'categories' => $categories ];
}

/**
 * @param $response
 */
function core_course_update_categories_response( $response )
{
	return null;
}

//core_course_delete_categories function
function core_course_delete_categories_object()
{
	//gather the Moodle category ID

	if ( ! empty( $_REQUEST['course_id'] ) )
	{
		$column = 'course_id';
		$rosario_id = (string) $_REQUEST['course_id'];
	}
	elseif ( ! empty( $_REQUEST['subject_id'] ) )
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}

	$id = MoodleXRosarioGet( $column, $rosario_id );

	if ( empty( $id ) )
	{
		return null;
	}

	//then, convert variables for the Moodle object:
	/*
	list of (
	object {
	id int   //category id to delete
	newparent int  Optionnel //the parent category to move the contents to, if specified
	recursive int  Défaut pour « 0 » //1: recursively delete all contents inside this
	category, 0 (default): move contents to newparent or current parent category (except if parent is root)
	}
	)*/

	$recursive = 1;

	$categories = [
		[
			'id' => $id,
			'recursive' => $recursive,
		],
	];

	return [ 'categories' => $categories ];
}

/**
 * @param $response
 */
function core_course_delete_categories_response( $response )
{
	if ( ! empty( $_REQUEST['course_id'] ) )
	{
		$column = 'course_id';
		$rosario_id = (string) $_REQUEST['course_id'];
	}
	elseif ( ! empty( $_REQUEST['subject_id'] ) )
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}

	//delete the reference the moodlexrosario cross-reference table:
	DBQuery( "DELETE FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'column' ) . "='" . $column . "'
		AND rosario_id='" . $rosario_id . "'" );

	return null;
}

//core_course_create_courses function
// @since 11.4 Set Moodle course end date (enddate parameter)
function core_course_create_courses_object()
{
	//first, gather the necessary variables
	global $_REQUEST;

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
	enddate int  Optionnel //timestamp when the course end
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

	$course_period_RET = DBGet( "SELECT TITLE,SHORT_NAME,MARKING_PERIOD_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( empty( $course_period_RET[1] ) )
	{
		return null;
	}

	$course_period = $course_period_RET[1];

	//add the year to the course name
	$fullname = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' - ' . $course_period['TITLE'];
	$shortname = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' ' . $course_period['SHORT_NAME'];

	//get the Moodle category
	$categoryid = MoodleXRosarioGet( 'course_id', $_REQUEST['course_id'] );

	if ( empty( $categoryid ) )
	{
		return null;
	}

	$idnumber = (string) $_REQUEST['course_period_id'];
	$summaryformat = 1;
	$format = 'weeks';
	$showgrades = 1;
	$newsitems = 5;
	//convert YYYY-MM-DD to timestamp
	$startdate = strtotime( GetMP( $course_period['MARKING_PERIOD_ID'], 'START_DATE' ) );
	//convert YYYY-MM-DD to timestamp
	$enddate = strtotime( GetMP( $course_period['MARKING_PERIOD_ID'], 'END_DATE' ) );
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
	global $_REQUEST;

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
			'ROSARIO_ID' => (int) $_REQUEST['course_period_id'],
			'MOODLE_ID' => (int) $response[0]['id'],
		]
	);

	$_REQUEST['moodle_create_course_period'] = false;

	return null;
}

//enrol_manual_enrol_users function
function enrol_manual_enrol_users_object()
{
	//first, gather the necessary variables
	global $columns, $_REQUEST;

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
	$userid = MoodleXRosarioGet( 'staff_id', $columns['TEACHER_ID'] );

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

//core_course_delete_courses function
function core_course_delete_courses_object()
{
	//gather the Moodle course ID
	$id = MoodleXRosarioGet( 'course_period_id', $_REQUEST['course_period_id'] );

	if ( empty( $id ) )
	{
		return null;
	}

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
	//delete the reference the moodlexrosario cross-reference table:
	DBQuery( "DELETE FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'column' ) . "='course_period_id'
		AND rosario_id='" . (int) $_REQUEST['course_period_id'] . "'" );

	return null;
}

//enrol_manual_unenrol_users function
function enrol_manual_unenrol_users_object()
{
	//first, gather the necessary variables
	global $current_cp, $_REQUEST;

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
	$userid = MoodleXRosarioGet( 'staff_id', $current_cp[1]['TEACHER_ID'] );

	if ( empty( $userid ) )
	{
		return null;
	}

	//teacher's roleid = teacher = 3
	$roleid = 3;

	//gather the Moodle course period ID
	$courseperiodid = MoodleXRosarioGet( 'course_period_id', $_REQUEST['course_period_id'] );

	if ( empty( $courseperiodid ) )
	{
		return null;
	}

	$courseid = $courseperiodid;

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

//core_course_update_courses function
function core_course_update_courses_object()
{
	//first, gather the necessary variables
	global $columns, $_REQUEST, $base_title;

	//then, convert variables for the Moodle object:
	/*
	list of (
	object {
	id int   //ID of the course
	fullname string  Optional //full name
	shortname string  Optional //course short name
	categoryid int  Optional //category id
	idnumber string  Optional //id number
	summary string  Optional //summary
	summaryformat int  Optional //summary format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	format string  Optional //course format: weeks, topics, social, site,..
	showgrades int  Optional //1 if grades are shown, otherwise 0
	newsitems int  Optional //number of recent items appearing on the course page
	startdate int  Optional //timestamp when the course start
	numsections int  Optional //(deprecated, use courseformatoptions) number of weeks/topics
	maxbytes int  Optional //largest size of file that can be uploaded into the course
	showreports int  Optional //are activity report shown (yes = 1, no =0)
	visible int  Optional //1: available to student, 0:not available
	hiddensections int  Optional //(deprecated, use courseformatoptions) How the hidden sections in the course are
	displayed to students
	groupmode int  Optional //no group, separate, visible
	groupmodeforce int  Optional //1: yes, 0: no
	defaultgroupingid int  Optional //default grouping id
	enablecompletion int  Optional //Enabled, control via completion and activity settings. Disabled,
	not shown in activity settings.
	completionnotify int  Optional //1: yes 0: no
	lang string  Optional //forced course language
	forcetheme string  Optional //name of the force theme
	courseformatoptions  Optional //additional options for particular course format
	list of (
	object {
	name string   //course format option name
	value string   //course format option value
	}
	)
	}
	)
	 */

	//add the year to the course name
	$fullname = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' - ' . $base_title;

	//get the Moodle course ID
	$moodle_id = MoodleXRosarioGet( 'course_period_id', $_REQUEST['course_period_id'] );

	if ( empty( $moodle_id ) )
	{
		return null;
	}

	$id = $moodle_id;

	$course = [
		'id' => $id,
		'fullname' => $fullname,
	];

	if ( isset( $columns['SHORT_NAME'] ) )
	{
		$shortname = $columns['SHORT_NAME'];
		// Fix Moodle error Short name is already used for another course
		$course['shortname'] = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' ' . $shortname;
	}

	if ( isset( $columns['MARKING_PERIOD_ID'] ) )
	{
		//convert YYYY-MM-DD to timestamp
		$startdate = strtotime( GetMP( $columns['MARKING_PERIOD_ID'], 'START_DATE' ) );
		$course['startdate'] = $startdate;
	}

	$courses = [
		$course,
	];

	return [ 'courses' => $courses ];
}

/**
 * @param $response
 */
function core_course_update_courses_response( $response )
{
	return null;
}
