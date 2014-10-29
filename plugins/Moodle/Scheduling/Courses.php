<?php
//modif Francois: Moodle integrator


//core_course_create_categories function
function core_course_create_categories_object()
{
	//first, gather the necessary variables
	global $columns, $_REQUEST, $table_name;
	
	
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

	$name = $columns['TITLE'];
	
	if($table_name=='COURSE_SUBJECTS')
	{
		$parent = 0;
		$idnumber = (string)$_REQUEST['subject_id'];
	}
	elseif($table_name=='COURSES')
	{
		//get the Moodle parent category
		$parent = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['subject_id']."' AND \"column\"='subject_id'"));
		if (count($parent))
		{
			$parent = (int)$parent[1]['MOODLE_ID'];
		}
		else
		{
			return null;
		}

		$idnumber = (string)$_REQUEST['course_id'];	
	}
	else //error...
		return null;
		
	$descriptionformat = 1;

	$categories = array(
						array(
							'name' => $name,
							'parent' => $parent,
							'idnumber' => $idnumber,
							'descriptionformat' => $descriptionformat,
						)
					);
	
	return array($categories);
}


function core_course_create_categories_response($response)
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
	
	if($table_name=='COURSE_SUBJECTS')
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}
	elseif($table_name=='COURSES')
	{
		$column = 'course_id';
		$rosario_id = (string)$_REQUEST['course_id'];	
	}
	
	DBQuery("INSERT INTO MOODLEXROSARIO (\"column\", rosario_id, moodle_id) VALUES ('".$column."', '".$rosario_id."', ".$response[0]['id'].")");
	return null;
}



//core_course_update_categories function
function core_course_update_categories_object()
{
	//first, gather the necessary variables
	global $columns, $_REQUEST, $table_name;
	
	
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
	if($table_name=='COURSES')
	{
		$column = 'course_id';
		$rosario_id = $_REQUEST['course_id'];	
	}
	elseif($table_name=='COURSE_SUBJECTS')
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}
	$id = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rosario_id."' AND \"column\"='".$column."'"));
	if (count($id))
	{
		$id = (int)$id[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	$name = $columns['TITLE'];
	
	$categories = array(
						array(
							'id' => $id,
							'name' => $name,
						)
					);
	
	return array($categories);
}


function core_course_update_categories_response($response)
{
	return null;
}



//core_course_delete_categories function
function core_course_delete_categories_object()
{
	//gather the Moodle category ID
	if(!empty($_REQUEST['course_id']))
	{
		$column = 'course_id';
		$rosario_id = (string)$_REQUEST['course_id'];	
	}
	elseif(!empty($_REQUEST['subject_id']))
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}
	$id = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rosario_id."' AND \"column\"='".$column."'"));
	if (count($id))
	{
		$id = (int)$id[1]['MOODLE_ID'];
	}
	else
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
	
	$categories = array(
						array(
							'id' => $id,
							'recursive' => $recursive,
						)
					);
	
	return array($categories);
}


function core_course_delete_categories_response($response)
{
	
	if(!empty($_REQUEST['course_id']))
	{
		$column = 'course_id';
		$rosario_id = (string)$_REQUEST['course_id'];	
	}
	elseif(!empty($_REQUEST['subject_id']))
	{
		$column = 'subject_id';
		$rosario_id = $_REQUEST['subject_id'];
	}
	
	//delete the reference the moodlexrosario cross-reference table:
	DBQuery("DELETE FROM MOODLEXROSARIO WHERE \"column\"='".$column."' AND rosario_id='".$rosario_id."'");
	
	return null;
}



//core_course_create_courses function
function core_course_create_courses_object()
{
	//first, gather the necessary variables
	global $columns, $_REQUEST, $mp_title;
	
	
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
	//add the year to the course name
	$fullname = FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS')).' - '.mb_substr($mp_title, 0, mb_strlen($mp_title)-3);
	$shortname = $columns['SHORT_NAME'];
	
	//get the Moodle category
	$categoryid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['course_id']."' AND \"column\"='course_id'"));
	if (count($categoryid))
	{
		$categoryid = (int)$categoryid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	$idnumber = (string)$_REQUEST['course_period_id'];
	$summaryformat = 1;
	$format = 'weeks';
	$showgrades = 1;
	$newsitems = 5;
	//convert YYYY-MM-DD to timestamp
	$startdate = strtotime(GetMP($columns['MARKING_PERIOD_ID'],'START_DATE'));
	$numsections = 10;
	$maxbytes = 8388608;
	$showreports = 1;
	$hiddensections = 0;
	$groupmode = 0;
	$groupmodeforce = 0;
	$defaultgroupingid = 0;
		
	$courses = array(
						array(
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
						)
					);
	
	return array($courses);
}


function core_course_create_courses_response($response)
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
	
	DBQuery("INSERT INTO MOODLEXROSARIO (\"column\", rosario_id, moodle_id) VALUES ('course_period_id', '".$_REQUEST['course_period_id']."', ".$response[0]['id'].")");
	return null;
}



//enrol_manual_enrol_users function
//TODO use core_role_assign_roles function instead when no context needed? (https://tracker.moodle.org/browse/MDL-39152)
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
	$userid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$columns['TEACHER_ID']."' AND \"column\"='staff_id'"));
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	//gather the Moodle course ID
	$courseid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['course_period_id']."' AND \"column\"='course_period_id'"));
	if (count($courseid))
	{
		$courseid = (int)$courseid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
		
	$enrolments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'courseid' => $courseid,
						)
					);
	
	return array($enrolments);
}


function enrol_manual_enrol_users_response($response)
{
	return null;
}



//core_course_delete_courses function
function core_course_delete_courses_object()
{
	//gather the Moodle course ID
	$id = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['course_period_id']."' AND \"column\"='course_period_id'"));
	if (count($id))
	{
		$id = (int)$id[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	int   //course ID
)*/

	$courses = array($id);
	
	return array($courses);
}


function core_course_delete_courses_response($response)
{
	
	//delete the reference the moodlexrosario cross-reference table:
	DBQuery("DELETE FROM MOODLEXROSARIO WHERE \"column\" = 'course_period_id' AND rosario_id ='".$_REQUEST['course_period_id']."'");
	
	return null;
}



//core_role_unassign_roles function
//TODO get rid of local_getcontexts_get_contexts function call when context not needed? (https://tracker.moodle.org/browse/MDL-39153)
function core_role_unassign_roles_object()
{
	//first, gather the necessary variables
	global $current, $_REQUEST;
	
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int   //The context to unassign the user role from
	} 
)
*/
	//gather the Moodle user ID
	$userid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$current[1]['TEACHER_ID']."' AND \"column\"='staff_id'"));
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	//teacher's roleid = teacher = 3
	$roleid = 3;

	//get contextid:
	global $moodle_contextlevel, $moodle_instance;
	$moodle_contextlevel = CONTEXT_COURSE;
	$rosario_id = $_REQUEST['course_period_id'];
	//gather the Moodle course ID
	$moodle_instance = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rosario_id."' AND \"column\"='course_period_id'"));
	if (count($moodle_instance))
	{
		$moodle_instance = (int)$moodle_instance[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	$contexts = Moodle('Global/functions.php', 'local_getcontexts_get_contexts');
	$contextid = $contexts[0]['id'];
	
	$unassignments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'contextid' => $contextid,
						)
					);
	
	return array($unassignments);
}


function core_role_unassign_roles_response($response)
{
	return null;
}


//core_course_update_courses function
function core_course_update_courses_object()
{
	//first, gather the necessary variables
	global $columns, $_REQUEST, $mp_title;
	
	
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
	$fullname = FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS')).' - '.mb_substr($mp_title, 0, mb_strlen($mp_title)-3);
	
	//get the Moodle course ID
	$moodle_id = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['course_period_id']."' AND \"column\"='course_period_id'"));
	if (count($moodle_id))
	{
		$moodle_id = (int)$moodle_id[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	$id = $moodle_id;
		
	$courses = array(
						array(
							'id' => $id,
							'fullname' => $fullname,
						)
					);
	if (isset($columns['SHORT_NAME']))
	{
		$shortname = $columns['SHORT_NAME'];
		$course['shortname'] = $shortname;
	}
	if (isset($columns['MARKING_PERIOD_ID']))
	{
		//convert YYYY-MM-DD to timestamp
		$startdate = strtotime(GetMP($columns['MARKING_PERIOD_ID'],'START_DATE'));
		$course['startdate'] = $startdate;
	}

	return array($courses);
}


function core_course_update_courses_response($response)
{
	return null;
}
?>