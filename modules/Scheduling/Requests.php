<?php
if($_REQUEST['modfunc']!='XMLHttpRequest')
	DrawHeader(ProgramTitle());

Widgets('request');
Search('student_id',$extra);

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if(DeletePrompt(_('Request')))
	{
		DBQuery("DELETE FROM SCHEDULE_REQUESTS WHERE REQUEST_ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		unset($_SESSION['_REQUEST_vars']['id']);
	}
}

if($_REQUEST['modfunc']=='update' && AllowEdit())
{
	foreach($_REQUEST['values'] as $request_id=>$columns)
	{
		$sql = "UPDATE SCHEDULE_REQUESTS SET ";

		foreach($columns as $column=>$value)
		{
			$sql .= $column."='".$value."',";
		}
		$sql = mb_substr($sql,0,-1) . " WHERE STUDENT_ID='".UserStudentID()."' AND REQUEST_ID='".$request_id."'";
		DBQuery($sql);
	}
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='add' && AllowEdit())
{
    $course_id = $_REQUEST['course'];
	$subject_id = DBGet(DBQuery("SELECT SUBJECT_ID FROM COURSES WHERE COURSE_ID='".$course_id."'"));
	$subject_id = $subject_id[1]['SUBJECT_ID'];

	DBQuery("INSERT INTO SCHEDULE_REQUESTS (REQUEST_ID,SYEAR,SCHOOL_ID,STUDENT_ID,SUBJECT_ID,COURSE_ID) values(".db_seq_nextval('SCHEDULE_REQUESTS_SEQ').",'".UserSyear()."','".UserSchool()."','".UserStudentID()."','".$subject_id."','".$course_id."')");
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='XMLHttpRequest')
{
	header("Content-Type: text/xml\n\n");
    $courses_RET = DBGet(DBQuery("SELECT c.COURSE_ID,c.TITLE FROM COURSES c WHERE ".($_REQUEST['subject_id']?"c.SUBJECT_ID='".$_REQUEST['subject_id']."' AND ":'')."UPPER(c.TITLE) LIKE '".mb_strtoupper($_REQUEST['course_title'])."%' AND c.SYEAR='".UserSyear()."' AND c.SCHOOL_ID='".UserSchool()."'"));
	echo '<?phpxml version="1.0" standalone="yes"?><courses>';
	if(count($courses_RET))
	{
		foreach($courses_RET as $course)
			echo '<course><id>'.$course['COURSE_ID'].'</id><title>'.str_replace('&','&amp;',$course['TITLE']).'</title></course>';
	}
	echo '</courses>';
}

if(!$_REQUEST['modfunc'] && UserStudentID())
{
	echo '<script type="text/javascript">
function SendXMLRequest(subject_id,course)
{
	if(window.XMLHttpRequest)
		connection = new XMLHttpRequest();
	else if(window.ActiveXObject)
		connection = new ActiveXObject("Microsoft.XMLHTTP");
	connection.onreadystatechange = processRequest;
	connection.open("GET","Modules.php?modname='.$_REQUEST['modname'].'&_ROSARIO_PDF=true&modfunc=XMLHttpRequest&subject_id="+subject_id+"&course_title="+course,true);
	connection.send(null);
}

function changeStyle(tag,over)
{
	if(over)
	{
		tag.style.backgroundColor="#'.Preferences('HIGHLIGHT').'";
		tag.style.color="#000";
	}
	else
	{
		tag.style.backgroundColor="#333";
		tag.style.color="#000";
	}
}

function doOnClick(course)
{
	var courseonclick = document.createElement("a");
	courseonclick.href = "Modules.php?modname='.$_REQUEST['modname'].'&modfunc=add&course="+course;
	courseonclick.target = "body";
	ajaxLink(courseonclick);
}

function processRequest()
{
	// LOADED && ACCEPTED
	if(connection.readyState == 4 && connection.status == 200)
	{
		XMLResponse = connection.responseXML;
		document.getElementById("courses_div").style.display = "inline";
		course_list = XMLResponse.getElementsByTagName("courses");
		course_list = course_list[0];
		courses = course_list.getElementsByTagName("course");

		for(i=0;i<courses.length;i++)
		{
			id = courses[i].getElementsByTagName("id")[0].firstChild.data;
			title = courses[i].getElementsByTagName("title")[0].firstChild.data;
			document.getElementById("courses_div").innerHTML = document.getElementById("courses_div").innerHTML + "<A onmousedown=\"doOnClick(\'"+ id +"\')\" href=\"#\">" + title + "</A><br />";
		}
	}
}
</script>';

	$functions = array('COURSE'=>'_makeCourse','WITH_TEACHER_ID'=>'_makeTeacher','WITH_PERIOD_ID'=>'_makePeriod');
	$requests_RET = DBGet(DBQuery("SELECT r.REQUEST_ID,c.TITLE as COURSE,r.COURSE_ID,r.MARKING_PERIOD_ID,r.WITH_TEACHER_ID,r.NOT_TEACHER_ID,r.WITH_PERIOD_ID,r.NOT_PERIOD_ID FROM SCHEDULE_REQUESTS r,COURSES c WHERE r.COURSE_ID=c.COURSE_ID AND r.SYEAR='".UserSyear()."' AND r.STUDENT_ID='".UserStudentID()."'"),$functions);
	$columns = array('COURSE'=>_('Course'),'WITH_TEACHER_ID'=>_('Teacher'),'WITH_PERIOD_ID'=>_('Period'));

	//$link['add']['html'] = array('COURSE_ID'=>_makeCourse('','COURSE_ID'),'WITH_TEACHER_ID'=>_makeTeacher('','WITH_TEACHER_ID'),'WITH_PERIOD_ID'=>_makePeriod('','WITH_PERIOD_ID'),'MARKING_PERIOD_ID'=>_makeMP('','MARKING_PERIOD_ID'));
	$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
//modif Francois: add translation
	$subjects = '<SELECT name="subject_id" onchange="document.getElementById(\'courses_div\').innerHTML = \'\';SendXMLRequest(this.form.subject_id.options[this.form.subject_id.selectedIndex].value,this.form.course_title.value);"><option value="">'._('All Subjects').'</option>';
	foreach($subjects_RET as $subject)
		$subjects .= '<OPTION value="'.$subject['SUBJECT_ID'].'">'.$subject['TITLE'].'</OPTION>';
	$subjects .= '</SELECT>';
	$link['remove'] = array('link'=>'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove','variables'=>array('id'=>'REQUEST_ID'));
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
//modif Francois: css WPadmin
	$link['add']['span'] = ''.Localize('colon',_('Add a Request')).'  &nbsp; <span class="nobr">'._('Subject').' '.$subjects.'</span> &nbsp; <span class="nobr">'._('Course Title').' <INPUT type="text" id="course_title" name="course_title" onkeypress="if(event.keyCode==13)return false;" onblur="document.getElementById(\'courses_div\').style.display=\'none\';" onkeyup="document.getElementById(\'courses_div\').innerHTML = \'\';SendXMLRequest(this.form.subject_id.options[this.form.subject_id.selectedIndex].value,this.form.course_title.value);"></span><DIV id="courses_div"></DIV>';
	ListOutput($requests_RET,$columns,'Request','Requests',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function _makeCourse($value,$column)
{	global $THIS_RET;

	return $value;
}

function _makeTeacher($value,$column)
{	global $THIS_RET;

	$teachers_RET = DBGet(DBQuery("SELECT s.FIRST_NAME,s.LAST_NAME,s.STAFF_ID AS TEACHER_ID FROM STAFF s,COURSE_PERIODS cp WHERE s.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_ID='".$THIS_RET['COURSE_ID']."'"));
	foreach($teachers_RET as $teacher)
		$options[$teacher['TEACHER_ID']] = $teacher['FIRST_NAME'].' '.$teacher['LAST_NAME'];

	return Localize('colon',_('With')).'&nbsp;'.SelectInput($value,'values['.$THIS_RET['REQUEST_ID'].'][WITH_TEACHER_ID]','',$options).' &mdash; '.Localize('colon',_('Without')).'&nbsp;'.SelectInput($THIS_RET['NOT_TEACHER_ID'],'values['.$THIS_RET['REQUEST_ID'].'][NOT_TEACHER_ID]','',$options);
}

function _makePeriod($value,$column)
{	global $THIS_RET;

	//modif Francois: multiple school periods for a course period
	//$periods_RET = DBGet(DBQuery("SELECT p.TITLE,p.PERIOD_ID FROM SCHOOL_PERIODS p,COURSE_PERIODS cp WHERE p.PERIOD_ID=cp.PERIOD_ID AND cp.COURSE_ID='".$THIS_RET['COURSE_ID']."'"));
	$periods_RET = DBGet(DBQuery("SELECT p.TITLE,p.PERIOD_ID FROM SCHOOL_PERIODS p,COURSE_PERIODS cp,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND p.PERIOD_ID=cpsp.PERIOD_ID AND cp.COURSE_ID='".$THIS_RET['COURSE_ID']."'"));
	foreach($periods_RET as $period)
		$options[$period['PERIOD_ID']] = $period['TITLE'];

	return Localize('colon',_('On')).' '.SelectInput($value,'values['.$THIS_RET['REQUEST_ID'].'][WITH_PERIOD_ID]','',$options).' &mdash; '.Localize('colon',_('Not on')).' '.SelectInput($THIS_RET['NOT_PERIOD_ID'],'values['.$THIS_RET['REQUEST_ID'].'][NOT_PERIOD_ID]','',$options);
}

// DOESN'T SUPPORT MP REQUEST
function _makeMP($value,$column)
{	global $THIS_RET;

	return SelectInput($value,'values['.$THIS_RET['REQUEST_ID'].'][MARKING_PERIOD_ID]','',$options);
}

?>