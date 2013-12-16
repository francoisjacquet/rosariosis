<?php

$_REQUEST['modfunc'] = 'choose_course';

if(!$_REQUEST['course_period_id'])
	include 'modules/Scheduling/Courses.php';
else
{
	$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'"));
	$course_title = $course_title[1]['TITLE'] . '<INPUT type=\"hidden\" name=\"w_'.($_REQUEST['last_year']=='true'?'ly_':'').'course_period_id\" value=\"'.$_REQUEST['course_period_id'].'\">';

//modif Francois: add <label> on radio
	echo '<script type="text/javascript">opener.document.getElementById("'.($_REQUEST['last_year']=='true'?'ly_':'').'course_div").innerHTML = "';	$toEscape = str_replace(array("'",'"'),array('&#39;','&quot;'),$course_title).'<BR /><label><INPUT type="radio" name="w_'.($_REQUEST['last_year']=='true'?'ly_':'').'course_period_id_which" value="course_period" checked /> '.str_replace(array("'",'"'),array('&#39;','&quot;'),_('Course Period')).'</label><label><INPUT type="radio" name="w_'.($_REQUEST['last_year']=='true'?'ly_':'').'course_period_id_which" value="course"> '.str_replace(array("'",'"'),array('&#39;','&quot;'),_('Course')).'</label>';
	echo str_replace('"','\"',$toEscape);
	echo '"; window.close();</script>';
}

?>