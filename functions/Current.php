<?php

function UserSchool()
{
	return $_SESSION['UserSchool'];
}

function UserSyear()
{
	return $_SESSION['UserSyear'];
}

function UserMP()
{
	return $_SESSION['UserMP'];
}

// DEPRECATED
function UserPeriod()
{
	return $_SESSION['UserPeriod'];
}

function UserCoursePeriod()
{
	return $_SESSION['UserCoursePeriod'];
}

//modif Francois: multiple school periods for a course period
function UserCoursePeriodSchoolPeriod()
{
	return $_SESSION['UserCoursePeriodSchoolPeriod'];
}

function UserStudentID()
{
	return $_SESSION['student_id'];
}

function UserStaffID()
{
	return $_SESSION['staff_id'];
}

?>