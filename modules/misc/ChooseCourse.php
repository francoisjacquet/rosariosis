<?php

$_REQUEST['modfunc'] = 'choose_course';

if ( ! $_REQUEST['course_period_id'])
	include 'modules/Scheduling/Courses.php';
else
{
	$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'"));
	$course_title = $course_title[1]['TITLE'] . '<input type="hidden" name="w_'.($_REQUEST['last_year']=='true'?'ly_':'').'course_period_id" value="'.$_REQUEST['course_period_id'].'" />';

//FJ add <label> on radio
	echo '<script>opener.document.getElementById("'.($_REQUEST['last_year']=='true'?'ly_':'').'course_div").innerHTML = ';
	$toEscape = $course_title.'<br /><label><input type="radio" name="w_'.($_REQUEST['last_year']=='true'?'ly_':'').'course_period_id_which" value="course_period" checked /> '._('Course Period').'</label><label><input type="radio" name="w_'.($_REQUEST['last_year']=='true'?'ly_':'').'course_period_id_which" value="course" /> '._('Course').'</label>';
	echo json_encode($toEscape);
	echo '; window.close();</script>';
}
