<?php

//modif Francois: add subject areas
//Course Titles can store the subject area as following:
//'Subject area|Course Title'
//This function simply returns the course title
function CourseTitle ($courseTitle, $title='') {
	return (mb_strrchr($courseTitle, '|') ? mb_substr(mb_strrchr($courseTitle, '|'),1) : $courseTitle);
}

//This function simply returns the subject area
function CourseTitleArea ($courseTitle, $title='') {
	return (mb_strstr($courseTitle, '|', true) ? mb_strstr($courseTitle, '|', true) : '');
}

?>