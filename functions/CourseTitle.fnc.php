<?php

//modif Francois: add subject areas
//Course Titles can store the subject area as following:
//'Subject area|Course Title'
//This function simply returns the course title
function CourseTitle ($courseTitle, $title='') {
	return (strrchr($courseTitle, '|') ? substr(strrchr($courseTitle, '|'),1) : $courseTitle);
}

//This function simply returns the subject area
function CourseTitleArea ($courseTitle, $title='') {
	return (strstr($courseTitle, '|', true) ? strstr($courseTitle, '|', true) : '');
}

?>