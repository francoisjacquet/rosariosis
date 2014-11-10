<?php

/**********************************************************************
 ExampleResource.php file
 Optional
 - Adds a program to the Resources modules.
***********************************************************************/


DrawHeader(ProgramTitle()); //display main header with Module icon and Program title


//get Resources from the database
$QI = DBQuery("SELECT ID AS SCHOOL_ID, TITLE FROM SCHOOLS WHERE SYEAR='".UserSyear()."' ORDER BY ID");
$schools_RET = DBGet($QI, array(), array('SCHOOL_ID'));

//get the number of Students for each school
$QI = DBQuery("SELECT SCHOOL_ID, COUNT(STUDENT_ID) AS STUDENT_NB FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' GROUP BY SCHOOL_ID");
$students_RET = DBGet($QI, array(), array('SCHOOL_ID'));

$admins_RET = $teachers_RET = $parents_RET = array();

$school_IDs = array_keys($schools_RET);

//for each school
foreach($school_IDs as $school_ID)
{
	//get the number of Administrators
	$QI = DBQuery("SELECT ".$school_ID." AS SCHOOL_ID, COUNT(STAFF_ID) AS ADMIN_NB FROM STAFF WHERE SYEAR='".UserSyear()."'  AND profile='admin' AND (SCHOOLS LIKE '%,".$school_ID.",%' OR SCHOOLS IS NULL OR SCHOOLS='')");

	$admins_RET = $admins_RET + DBGet($QI, array(), array('SCHOOL_ID'));

	//get the number of Teachers
	$QI = DBQuery("SELECT ".$school_ID." AS SCHOOL_ID, COUNT(STAFF_ID) AS TEACHER_NB FROM STAFF WHERE SYEAR='".UserSyear()."' AND profile='teacher' AND (SCHOOLS LIKE '%,".$school_ID.",%' OR SCHOOLS IS NULL OR SCHOOLS='')");
	$teachers_RET = $teachers_RET + DBGet($QI, array(), array('SCHOOL_ID'));

	//get the number of Parents
	$QI = DBQuery("SELECT ".$school_ID." AS SCHOOL_ID, COUNT(STAFF_ID) AS PARENT_NB FROM STAFF WHERE SYEAR='".UserSyear()."' AND profile='parent' AND (SCHOOLS LIKE '%,".$school_ID.",%' OR SCHOOLS IS NULL OR SCHOOLS='')");
	$parents_RET = $parents_RET + DBGet($QI, array(), array('SCHOOL_ID'));
}

//build the Resources array for ListOutput
$resources_RET = array();
$i = 1; //the first key of the array should not be 0

//for each school
foreach($school_IDs as $school_ID)
{
	$resources_RET[$i] = $schools_RET[$school_ID][1];
	$resources_RET[$i] = $resources_RET[$i] + $students_RET[$school_ID][1];
	$resources_RET[$i] = $resources_RET[$i] + $admins_RET[$school_ID][1];
	$resources_RET[$i] = $resources_RET[$i] + $teachers_RET[$school_ID][1];
	$resources_RET[$i] = $resources_RET[$i] + $parents_RET[$school_ID][1];
	$i++;
}

//uncomment the following line to debug and see the Queries results
//var_dump($schools_RET,$students_RET, $admins_RET, $teachers_RET, $parents_RET, $resources_RET);exit;

//prepare ListOutput table options
//see ListOutput.fnc.php for the complete list of options
$columns = array('TITLE'=>_('Title'), 'STUDENT_NB'=>dgettext('Example', '# of Students'), 'ADMIN_NB'=>dgettext('Example', '# of Administrators'), 'TEACHER_NB'=>dgettext('Example', '# of Teachers'), 'PARENT_NB'=>dgettext('Example', '# of Parents'));

//display secondary header with text (aligned left)
DrawHeader('This is the Example Resource program from the Example module.');

ListOutput($resources_RET,$columns,'School','Schools');
?>
