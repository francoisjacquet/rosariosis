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

?>