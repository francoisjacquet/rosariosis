<?php
DrawHeader(ProgramTitle());

Widgets('request');
if(!UserStudentID())
	echo '<BR />';
Search('student_id',$extra);

if(!$_REQUEST['modfunc'] && UserStudentID())
	$_REQUEST['modfunc'] = 'choose';

if($_REQUEST['modfunc']=='verify')
{
	$QI = DBQuery("SELECT TITLE,COURSE_ID,SUBJECT_ID FROM COURSES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
	$courses_RET = DBGet($QI,array(),array('COURSE_ID'));

	DBQuery("DELETE FROM SCHEDULE_REQUESTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."'");
	
	foreach($_REQUEST['courses'] as $subject=>$courses)
	{
		$courses_count = count($courses);
		for($i=0;$i<$courses_count;$i++)
		{
			$course = $courses[$i];

			if(!$course)
				continue;			
			$sql = "INSERT INTO SCHEDULE_REQUESTS (REQUEST_ID,SYEAR,SCHOOL_ID,STUDENT_ID,SUBJECT_ID,COURSE_ID,MARKING_PERIOD_ID,WITH_TEACHER_ID,NOT_TEACHER_ID,WITH_PERIOD_ID,NOT_PERIOD_ID)
						values(".db_seq_nextval('SCHEDULE_REQUESTS_SEQ').",'".UserSyear()."','".UserSchool()."','".UserStudentID()."','".$courses_RET[$course][1]['SUBJECT_ID']."','".$course."',NULL,'".$_REQUEST['with_teacher'][$subject][$i]."','".$_REQUEST['without_teacher'][$subject][$i]."','".$_REQUEST['with_period'][$subject][$i]."','".$_REQUEST['without_period'][$subject][$i]."')";
			DBQuery($sql);
		}
	}
	echo ErrorMessage($error,_('Error'));
	
	$_SCHEDULER['student_id'] = UserStudentID();
	$_SCHEDULER['dont_run'] = true;
	include('modules/Scheduling/Scheduler.php');
	$_REQUEST['modfunc'] = 'choose';
}

if($_REQUEST['modfunc']=='choose')
{
	$functions = array('WITH_PERIOD_ID'=>'_makeWithSelects','NOT_PERIOD_ID'=>'_makeWithoutSelects');
	$requests_RET = DBGet(DBQuery("SELECT sr.COURSE_ID,c.COURSE_TITLE,sr.WITH_PERIOD_ID,sr.NOT_PERIOD_ID,sr.WITH_TEACHER_ID,
										sr.NOT_TEACHER_ID FROM SCHEDULE_REQUESTS sr,COURSES c
									WHERE sr.SYEAR='".UserSyear()."' AND sr.STUDENT_ID='".UserStudentID()."' AND sr.COURSE_ID=c.COURSE_ID"),$functions);

	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=verify" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));

	$columns = array('');
	ListOutput($requests_RET,$columns,'Request','Requests');

	echo '<span class="center">'.SubmitButton(_('Save')).'</span></FORM>';
}
?>
