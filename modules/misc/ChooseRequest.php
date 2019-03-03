<?php

$_REQUEST['modfunc'] = 'choose_course';

if ( empty( $_REQUEST['course_id'] ) )
	include 'modules/Scheduling/Courses.php';
else
{
	$course_title = DBGet( "SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_REQUEST['course_id']."'" );
	$course_title = $course_title[1]['TITLE'].'<input type="hidden" name="request_course_id" value="'.$_REQUEST['course_id'].'">';

//FJ add <label> on checkbox
	echo '<script>opener.document.getElementById("request_div").innerHTML = ';
	$toEscape = $course_title.'<br /><label><input type="checkbox" name="missing_request_course" value="Y">'._('Not Requested').'</label>';
	echo json_encode($toEscape);
	echo '; window.close();</script>';
}
