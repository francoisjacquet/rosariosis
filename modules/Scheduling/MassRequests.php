<?php
include_once('modules/Scheduling/functions.inc.php');
if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if($_SESSION['MassRequests.php'])
	{
		if (is_array($_REQUEST['student']))
		{
			$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM SCHEDULE_REQUESTS WHERE COURSE_ID='".$_REQUEST['MassRequests.php']['course_id']."' AND SYEAR='".UserSyear()."'"),array(),array('STUDENT_ID'));
			foreach($_REQUEST['student'] as $student_id=>$yes)
			{
				if(!$current_RET[$student_id])
				{
					$sql = "INSERT INTO SCHEDULE_REQUESTS (REQUEST_ID,SYEAR,SCHOOL_ID,STUDENT_ID,SUBJECT_ID,COURSE_ID,MARKING_PERIOD_ID,WITH_TEACHER_ID,NOT_TEACHER_ID,WITH_PERIOD_ID,NOT_PERIOD_ID)
								values(".db_seq_nextval('SCHEDULE_REQUESTS_SEQ').",'".UserSyear()."','".UserSchool()."','".$student_id."','".$_SESSION['MassRequests.php']['subject_id']."','".$_SESSION['MassRequests.php']['course_id']."',NULL,'".$_REQUEST['with_teacher_id']."','".$_REQUEST['without_teacher_id']."','".$_REQUEST['with_period_id']."','".$_REQUEST['without_period_id']."')";
					DBQuery($sql);
				}
			}
			$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('This course has been added as a request for the selected students.');
		}
		else
			$error[] = _('You must choose at least one student.');
	}
	else
		$error[] = _('You must choose a course.');
		
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['modfunc']);
	unset($_SESSION['MassRequests.php']);
}


if($_REQUEST['modfunc']!='choose_course')
{
	DrawHeader(ProgramTitle());
	
	if (isset($error))
		echo ErrorMessage($error);
	if(isset($note))
		echo ErrorMessage($note, 'note');

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		DrawHeader('',SubmitButton(_('Add Request to Selected Students')));
//modif Francois: css WPadmin
		echo '<BR /><TABLE class="postbox cellpadding-0 cellspacing-0" style="margin:0 auto;"><TR><TH colspan="3"><H3>'._('Request to Add').'</H3></TH></TR><TR><TD style="text-align:right">&nbsp;</TD><TD style="padding: 8px;"><DIV id=course_div>';
		if($_SESSION['MassRequests.php'])
		{
			$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_SESSION['MassRequests.php']['course_id']."'"));
			$course_title = $course_title[1]['TITLE'];

			echo $course_title;
		}
		echo '</DIV>'.'<A HREF="#" onclick=\'window.open("Modules.php?modname='.$_REQUEST['modname'].'&modfunc=choose_course","","scrollbars=yes,resizable=yes,width=800,height=400");\'>'._('Choose a Course').'</A></TD></TR>';
		echo '<TR><TD style="text-align:right; vertical-align:top;">'._('With').'</TD><TD>';
		echo '<BR /><TABLE><TR><TD style="text-align:right">'._('Teacher').'</TD><TD><SELECT name=with_teacher_id><OPTION value="">'._('N/A').'</OPTION>';
		//modif Francois: fix bug teacher's schools is NULL
		//$teachers_RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME FROM STAFF WHERE SCHOOLS LIKE '%,".UserSchool().",%' AND SYEAR='".UserSyear()."' AND PROFILE='teacher' ORDER BY LAST_NAME,FIRST_NAME"));
		$teachers_RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME FROM STAFF WHERE (SCHOOLS LIKE '%,".UserSchool().",%' OR SCHOOLS IS NULL) AND SYEAR='".UserSyear()."' AND PROFILE='teacher' ORDER BY LAST_NAME,FIRST_NAME"));
		foreach($teachers_RET as $teacher)
			echo '<OPTION value="'.$teacher['STAFF_ID'].'">'.$teacher['LAST_NAME'].', '.$teacher['FIRST_NAME'].' '.$teacher['MIDDLE_NAME'].'</OPTION>';
		echo '</SELECT></TD></TR><TR><TD style="text-align:right">'._('Period').'</TD><TD><SELECT name=with_period_id><OPTION value="">'._('N/A').'</OPTION>';
		$periods_RET = DBGet(DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER"));
		foreach($periods_RET as $period)
			echo '<OPTION value="'.$period['PERIOD_ID'].'">'.$period['TITLE'].'</OPTION>';
		echo '</SELECT></TD></TR></TABLE>';
		echo '</TD></TR>';
		echo '<TR><TD style="text-align:right; vertical-align:top;">'._('Without').'</TD><TD>';
		echo '<BR /><TABLE><TR><TD style="text-align:right">'._('Teacher').'</TD><TD><SELECT name=without_teacher_id><OPTION value="">'._('N/A').'</OPTION>';
		foreach($teachers_RET as $teacher)
			echo '<OPTION value="'.$teacher['STAFF_ID'].'">'.$teacher['LAST_NAME'].', '.$teacher['FIRST_NAME'].' '.$teacher['MIDDLE_NAME'].'</OPTION>';
		echo '</SELECT></TD></TR><TR><TD style="text-align:right">'._('Period').'</TD><TD><SELECT name="without_period_id"><OPTION value="">'._('N/A').'</OPTION>';
		foreach($periods_RET as $period)
			echo '<OPTION value="'.$period['PERIOD_ID'].'">'.$period['TITLE'].'</OPTION>';
		echo '</SELECT></TD></TR></TABLE>';
		echo '</TD></TR>';
		echo '</TABLE><BR />';
	}
}

if(empty($_REQUEST['modfunc']))

{
	if($_REQUEST['search_modfunc']!='list')
		unset($_SESSION['MassRequests.php']);
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
	$extra['new'] = true;

	Widgets('request');
	MyWidgets('ly_course');
	//Widgets('activity');

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
		echo '<BR /><span class="center">'.SubmitButton(_('Add Request to Selected Students'))."</span></FORM>";
}

if($_REQUEST['modfunc']=='choose_course')
{

//modif Francois: fix bug window closed
	if(!$_REQUEST['course_id'])
		include 'modules/Scheduling/Courses.php';
	else
	{
		$_SESSION['MassRequests.php']['subject_id'] = $_REQUEST['subject_id'];
		$_SESSION['MassRequests.php']['course_id'] = $_REQUEST['course_id'];

		$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_SESSION['MassRequests.php']['course_id']."'"));
		$course_title = $course_title[1]['TITLE'];

		echo '<script type="text/javascript">opener.document.getElementById("course_div").innerHTML = "'.str_replace(array("'",'"'),array('&#39;','&quot;'),$course_title).'"; window.close();</script>';
		
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '&nbsp;&nbsp;<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y">';
}

?>