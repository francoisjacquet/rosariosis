<?php
//modif Francois: Moodle integrator


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
	//add the year to the course name
	$fullname = $next_syear.' - '.GetMP($rolled_course_period['MARKING_PERIOD_ID'],'SHORT_NAME').' - '.$rolled_course_period['SHORT_NAME'];
	$shortname = $rolled_course_period['SHORT_NAME'];
	
	//get the Moodle category
	$categoryid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rolled_course_period['COURSE_ID']."' AND \"column\"='course_id'"));
	if (count($categoryid))
	{
		$categoryid = (int)$categoryid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	$idnumber = (string)$rolled_course_period['COURSE_PERIOD_ID'];
	$summaryformat = 1;
	$format = 'weeks';
	$showgrades = 1;
	$newsitems = 5;
	//convert YYYY-MM-DD to timestamp
	$startdate = strtotime(GetMP($rolled_course_period['MARKING_PERIOD_ID'],'START_DATE'));
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
	global $rolled_course_period;
	
	//then, save the ID in the moodlexrosario cross-reference table:
/*
list of ( 
	object {
		id int   //course id
		shortname string   //short name
	} 
)*/
	
	DBQuery("INSERT INTO MOODLEXROSARIO (\"column\", rosario_id, moodle_id) VALUES ('course_period_id', '".$rolled_course_period['COURSE_PERIOD_ID']."', ".$response[0]['id'].")");
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
	$userid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rolled_course_period['TEACHER_ID']."' AND \"column\"='staff_id'"));
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	//gather the Moodle course ID
	$courseid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rolled_course_period['COURSE_PERIOD_ID']."' AND \"column\"='course_period_id'"));
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

?>