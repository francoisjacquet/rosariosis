<?php

$_REQUEST['modfunc'] = 'choose_course';

if(!$_REQUEST['course_id'])
	include 'modules/Scheduling/Courses.php';
else
{
	$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_REQUEST['course_id']."'"));
	$course_title = str_replace(array("'",'"'),array('&#39;','&quot;'),$course_title[1]['TITLE']).'<INPUT type="hidden" name="request_course_id" value="'.$_REQUEST['course_id'].'">'; 

//modif Francois: add <label> on checkbox
	echo '<script type="text/javascript">opener.document.getElementById("request_div").innerHTML = "';
	$toEscape = $course_title.'<BR /><label><INPUT type="checkbox" name="not_request_course" value="Y">'.str_replace(array("'",'"'),array('&#39;','&quot;'),_('Not Requested')).'</label>';
	echo str_replace('"','\"',$toEscape);
	echo '"; window.close();</script>';
}

?>