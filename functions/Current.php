<?php

/**
 * @return int Current User School ID
 */
function UserSchool()
{
	return ( isset( $_SESSION['UserSchool'] ) ? $_SESSION['UserSchool'] : null );
}


/**
 * @return int Current User School Year ID
 */
function UserSyear()
{
	return ( isset( $_SESSION['UserSyear'] ) ? $_SESSION['UserSyear'] : null );
}


/**
 * @return int Current User Marking Period ID
 */
function UserMP()
{
	return ( isset( $_SESSION['UserMP'] ) ? $_SESSION['UserMP'] : null );
}


/**
 * @deprecated
 * 
 * @return int Current User Period ID
 */
function UserPeriod()
{
	return ( isset( $_SESSION['UserPeriod'] ) ? $_SESSION['UserPeriod'] : null );
}


/**
 * @return int Current User Course Period ID
 */
function UserCoursePeriod()
{
	return ( isset( $_SESSION['UserCoursePeriod'] ) ? $_SESSION['UserCoursePeriod'] : null );
}


/**
 * FJ multiple school periods for a course period
 * 
 * @return int Current User Course Period School Period ID
 */
function UserCoursePeriodSchoolPeriod()
{
	return ( isset( $_SESSION['UserCoursePeriodSchoolPeriod'] ) ? $_SESSION['UserCoursePeriodSchoolPeriod'] : null );
}


/**
 * @return int Current User Student ID
 */
function UserStudentID()
{
	return ( isset( $_SESSION['student_id'] ) ? $_SESSION['student_id'] : null );
}


/**
 * @return int Current User Staff ID
 */
function UserStaffID()
{
	return ( isset( $_SESSION['staff_id'] ) ? $_SESSION['staff_id'] : null );
}


/**
 * Set Current User Staff ID
 * Set $_SESSION['staff_id']
 * Forbid hacking user staff ID in URL
 *
 * Parent:
 * Check $staff_id == User('STAFF_ID')
 * Teacher:
 * Check $staff_id == User('STAFF_ID')
 *  OR is an ID of the parents of its related students
 * Admin:
 * Check $staff_id is in current Year
 * Student:
 * Forbid
 * 
 * @param  int  $staff_id Staff ID
 *
 * @return void exit to HackingLog if not permitted
 */
function SetUserStaffID( $staff_id )
{
	$isHack = false;
	
	switch ( User( 'PROFILE' ) )
	{
		case 'parent':
			if ( $staff_id !== User( 'STAFF_ID' ) )
				$isHack = true;
		break;
		
		case 'teacher':
			if ( $staff_id !== User( 'STAFF_ID' ) )
			{
				//get teacher's related parents, include parents of inactive students
				$is_related_parent = DBGet( DBQuery( "SELECT 1
					FROM STAFF s
					WHERE s.SYEAR='" . UserSyear() . "' 
					AND (s.SCHOOLS LIKE '%," . UserSchool() . ",%' OR s.SCHOOLS IS NULL OR s.SCHOOLS='') 
					AND (s.PROFILE='parent' AND exists(
						SELECT '' 
						FROM STUDENTS_JOIN_USERS _sju,STUDENT_ENROLLMENT _sem,SCHEDULE _ss 
						WHERE _sju.STAFF_ID=s.STAFF_ID 
						AND _sem.STUDENT_ID=_sju.STUDENT_ID 
						AND _sem.SYEAR='" . UserSyear() . "' 
						AND _ss.STUDENT_ID=_sem.STUDENT_ID 
						AND _ss.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
					))
					AND s.STAFF_ID='" . $staff_id . "'" ), array(), array( 'STAFF_ID' ) );

				if ( !count( $is_related_parent ) )
					$isHack = true;
			}

		break;

		case 'admin':
			//Check $staff_id is in current Year
			$is_admin_staff = DBGet( DBQuery( "SELECT 1
				FROM STAFF
				WHERE STAFF_ID='" . $staff_id . "'
				AND SYEAR='" . UserSyear() . "'" ) );

			if ( !count( $is_admin_staff ) )
				$isHack = true;

		break;

		case 'student':
		default:
			//FJ create account
			if ( User( 'PROFILE' )
				|| basename( $_SERVER['PHP_SELF'] ) != 'index.php' )
				$isHack = true;

		break;
	}
	
	if ( $isHack )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';

		HackingLog();
	}
	
	$_SESSION['staff_id'] = $staff_id;
}


/**
 * Set Current User Student ID
 * Set $_SESSION['student_id']
 * Forbid hacking user student ID in URL
 *
 * Student:
 * Check $student_id == $_SESSION['STUDENT_ID']
 * Parent:
 * Check $student_id is an ID of its related students
 * Teacher:
 * Check $student_id is an ID of its related students
 * Admin:
 * Check $student_id is in current Year & School
 * 
 * @param  int  $student_id Student ID
 *
 * @return void exit to HackingLog if not permitted
 */
function SetUserStudentID( $student_id )
{
	$isHack = false;
	
	switch ( User( 'PROFILE' ) )
	{
		case 'student':
			if ( $student_id !== $_SESSION['STUDENT_ID'] )
				$isHack = true;
		break;
		
		case 'parent':
			//get parent's related students
			$is_related_student = DBGet( DBQuery( "SELECT 1
				FROM STUDENTS s,STUDENTS_JOIN_USERS sju,STUDENT_ENROLLMENT se 
				WHERE s.STUDENT_ID=sju.STUDENT_ID 
				AND sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' 
				AND se.SYEAR='" . UserSyear() . "' 
				AND se.STUDENT_ID=sju.STUDENT_ID 
				AND ('" . DBDate() . "'>=se.START_DATE AND ('" . DBDate() . "'<=se.END_DATE OR se.END_DATE IS NULL))
				AND sju.STUDENT_ID='" . $student_id . "'"), array(), array( 'STUDENT_ID' ) );

			if ( !count( $is_related_student ) )
				$isHack = true;
		break;
		
		case 'teacher':
			//get teacher's related students, include inactive students
			$is_related_student = DBGet( DBQuery( "SELECT 1
				FROM STUDENTS s 
				JOIN SCHEDULE ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='" . UserSyear() . "' AND ss.START_DATE=
					(SELECT START_DATE FROM SCHEDULE 
					WHERE STUDENT_ID=s.STUDENT_ID 
					AND SYEAR=ss.SYEAR 
					AND COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID 
					ORDER BY START_DATE DESC 
					LIMIT 1)
				) 
				JOIN COURSE_PERIODS cp ON (cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "') 
				JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID AND ssm.SYEAR=ss.SYEAR AND ssm.SCHOOL_ID='" . UserSchool() . "' AND ssm.ID=
					(SELECT ID 
					FROM STUDENT_ENROLLMENT 
					WHERE STUDENT_ID=ssm.STUDENT_ID 
					AND SYEAR=ssm.SYEAR 
					ORDER BY START_DATE DESC 
					LIMIT 1)
				)
				AND s.STUDENT_ID='" . $student_id . "'"), array(), array( 'STUDENT_ID' ) );

			if ( !count( $is_related_student ) )
				$isHack = true;
		break;

		case 'admin':
			//Check $student_id is in current Year & School
			$is_admin_student = DBGet( DBQuery( "SELECT 1
				FROM STUDENT_ENROLLMENT
				WHERE STUDENT_ID='" . $student_id . "'
				AND SCHOOL_ID=" . UserSchool() . "
				AND SYEAR='" . UserSyear() . "'") );

			if ( !count( $is_admin_student ) )
				$isHack = true;
		break;

		default:
			//FJ create account
			if ( User( 'PROFILE' )
				|| basename( $_SERVER['PHP_SELF'] ) != 'index.php' )
				$isHack = true;

		break;
	}
	
	if ( $isHack )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';

		HackingLog();
	}
	
	$_SESSION['student_id'] = $student_id;
}
