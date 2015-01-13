<?php

function UserSchool()
{
	return (isset($_SESSION['UserSchool']) ? $_SESSION['UserSchool'] : null);
}

function UserSyear()
{
	return (isset($_SESSION['UserSyear']) ? $_SESSION['UserSyear'] : null);
}

function UserMP()
{
	return (isset($_SESSION['UserMP']) ? $_SESSION['UserMP'] : null);
}

// DEPRECATED
function UserPeriod()
{
	return $_SESSION['UserPeriod'];
}

function UserCoursePeriod()
{
	return (isset($_SESSION['UserCoursePeriod']) ? $_SESSION['UserCoursePeriod'] : null);
}

//modif Francois: multiple school periods for a course period
function UserCoursePeriodSchoolPeriod()
{
	return $_SESSION['UserCoursePeriodSchoolPeriod'];
}

function UserStudentID()
{
	return (isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null);
}

function UserStaffID()
{
	return (isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : null);
}

//modif Francois: Forbid hacking user student/staff ID in URL
//add setters for $_SESSION['staff_id'] & $_SESSION['student_id']

/* 
 * set $_SESSION['staff_id']
 * Parent:
 * Check $_SESSION['staff_id'] == $_SESSION['STAFF_ID']
 * Teacher:
 * Check $_SESSION['staff_id'] == $_SESSION['STAFF_ID']
 *  OR is an ID of the parents of its related students
 * Admin:
 * No checks
 * Student:
 * Forbid
 */
function SetUserStaffID($staff_id)
{
	$isHack = false;
	
	switch(User('PROFILE'))
	{
		case 'parent':
			if ($staff_id !== $_SESSION['STAFF_ID'])
				$isHack = true;
		break;
		
		case 'teacher':
			//get teacher's related parents, include parents of inactive students
			$RET = DBGet(DBQuery("SELECT s.STAFF_ID
				FROM STAFF s
				WHERE s.SYEAR='".UserSyear()."' 
				AND (s.SCHOOLS LIKE '%,".UserSchool().",%' OR s.SCHOOLS IS NULL OR s.SCHOOLS='') 
				AND (s.STAFF_ID='".User('STAFF_ID')."' OR s.PROFILE='parent' AND exists(
					SELECT '' 
					FROM STUDENTS_JOIN_USERS _sju,STUDENT_ENROLLMENT _sem,SCHEDULE _ss 
					WHERE _sju.STAFF_ID=s.STAFF_ID 
					AND _sem.STUDENT_ID=_sju.STUDENT_ID 
					AND _sem.SYEAR='".UserSyear()."' 
					AND _ss.STUDENT_ID=_sem.STUDENT_ID 
					AND _ss.COURSE_PERIOD_ID='".UserCoursePeriod()."'
				))"), array(), array('STAFF_ID'));
			$related_parents = array_keys($RET);

			if (!in_array($staff_id, $related_parents))
				$isHack = true;
			
		break;

		case 'admin':

		break;

		case 'student':
		default:
			//modif Francois: create account
			if (User('PROFILE') || basename($_SERVER['PHP_SELF'])!='index.php')
				$isHack = true;

		break;
	}
	
	if ($isHack)
	{
		include('ProgramFunctions/HackingLog.fnc.php');
		HackingLog();
	}
	
	$_SESSION['staff_id'] = $staff_id;
}

/* 
 * set $_SESSION['student_id']
 * Student:
 * Check $_SESSION['student_id'] == $_SESSION['STUDENT_ID']
 * Parent:
 * Check $_SESSION['student_id'] is an ID of its related students
 * Teacher:
 * Check $_SESSION['student_id'] is an ID of its related students
 * Admin:
 * No checks
 */
function SetUserStudentID($student_id)
{
	$isHack = false;
	
	switch(User('PROFILE'))
	{
		case 'student':
			if ($student_id !== $_SESSION['STUDENT_ID'])
				$isHack = true;
		break;
		
		case 'parent':
			//get parent's related students
			$RET = DBGet(DBQuery("SELECT sju.STUDENT_ID 
				FROM STUDENTS s,STUDENTS_JOIN_USERS sju,STUDENT_ENROLLMENT se 
				WHERE s.STUDENT_ID=sju.STUDENT_ID 
				AND sju.STAFF_ID='".User('STAFF_ID')."' 
				AND se.SYEAR='".UserSyear()."' 
				AND se.STUDENT_ID=sju.STUDENT_ID 
				AND ('".DBDate()."'>=se.START_DATE AND ('".DBDate()."'<=se.END_DATE OR se.END_DATE IS NULL))"), array(), array('STUDENT_ID'));
			$related_students = array_keys($RET);

			if (!in_array($student_id, $related_students))
				$isHack = true;
		break;
		
		case 'teacher':
			//get teacher's related students, include inactive students
			$RET = DBGet(DBQuery("SELECT s.STUDENT_ID 
				FROM STUDENTS s 
				JOIN SCHEDULE ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.START_DATE=
					(SELECT START_DATE FROM SCHEDULE 
					WHERE STUDENT_ID=s.STUDENT_ID 
					AND SYEAR=ss.SYEAR 
					AND COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID 
					ORDER BY START_DATE DESC 
					LIMIT 1)
				) 
				JOIN COURSE_PERIODS cp ON (cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID='".User('STAFF_ID')."') 
				JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID AND ssm.SYEAR=ss.SYEAR AND ssm.SCHOOL_ID='".UserSchool()."' AND ssm.ID=
					(SELECT ID 
					FROM STUDENT_ENROLLMENT 
					WHERE STUDENT_ID=ssm.STUDENT_ID 
					AND SYEAR=ssm.SYEAR 
					ORDER BY START_DATE DESC 
					LIMIT 1)
				)"), array(), array('STUDENT_ID'));
			$related_students = array_keys($RET);

			if (!in_array($student_id, $related_students))
				$isHack = true;
		break;

		case 'admin':

		break;

		default:
			//modif Francois: create account
			if (User('PROFILE') || basename($_SERVER['PHP_SELF'])!='index.php')
				$isHack = true;

		break;
	}
	
	if ($isHack)
	{
		include('ProgramFunctions/HackingLog.fnc.php');
		HackingLog();
	}
	
	$_SESSION['student_id'] = $student_id;
}

?>
