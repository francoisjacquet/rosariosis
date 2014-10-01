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

?>