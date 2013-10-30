<?php
include_once('ProgramFunctions/StudentsUsersInfo.fnc.php');

if(($_REQUEST['month_values'] && $_POST['month_values']) || ($_REQUEST['values']['STUDENT_ENROLLMENT'] && $_POST['values']['STUDENT_ENROLLMENT']))
{
	if(!$_REQUEST['values']['STUDENT_ENROLLMENT']['new']['ENROLLMENT_CODE'] && !$_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']['START_DATE'])
	{
		unset($_REQUEST['values']['STUDENT_ENROLLMENT']['new']);
		unset($_REQUEST['day_values']['STUDENT_ENROLLMENT']['new']);
		unset($_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']);
		unset($_REQUEST['year_values']['STUDENT_ENROLLMENT']['new']);
	}
	else
	{
		$date = $_REQUEST['day_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['year_values']['STUDENT_ENROLLMENT']['new']['START_DATE'];
		$found_RET = DBGet(DBQuery("SELECT ID FROM STUDENT_ENROLLMENT WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND '$date' BETWEEN START_DATE AND END_DATE"));
		if(count($found_RET))
		{
			unset($_REQUEST['values']['STUDENT_ENROLLMENT']['new']);
			unset($_REQUEST['day_values']['STUDENT_ENROLLMENT']['new']);
			unset($_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']);
			unset($_REQUEST['year_values']['STUDENT_ENROLLMENT']['new']);
			echo ErrorMessage(array(_('The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.')));
		}
	}

	$iu_extra['STUDENT_ENROLLMENT'] = "STUDENT_ID='".UserStudentID()."' AND ID='__ID__'";
	$iu_extra['fields']['STUDENT_ENROLLMENT'] = 'ID,SYEAR,STUDENT_ID,';
	$iu_extra['values']['STUDENT_ENROLLMENT'] = "nextval('STUDENT_ENROLLMENT_SEQ'),'".UserSyear()."','".UserStudentID()."',";
	if(!$new_student)
		SaveData($iu_extra,'',$field_names);
}

$functions = array('START_DATE'=>'_makeStartInput','END_DATE'=>'_makeEndInput','SCHOOL_ID'=>'_makeSchoolInput');
unset($THIS_RET);
$RET = DBGet(DBQuery("SELECT e.ID,e.ENROLLMENT_CODE,e.START_DATE,e.DROP_CODE,e.END_DATE,e.END_DATE AS END,e.SCHOOL_ID,e.NEXT_SCHOOL,e.CALENDAR_ID FROM STUDENT_ENROLLMENT e WHERE e.STUDENT_ID='".UserStudentID()."' AND e.SYEAR='".UserSyear()."' ORDER BY e.START_DATE"),$functions);

$add = true;
if(count($RET))
{
	foreach($RET as $value)
	{
		if(($value['DROP_CODE']=='' || !$value['DROP_CODE']) && ($value['END']=='' || !$value['END']))
			$add = false;
	}
}
if($add)
	$link['add']['html'] = array('START_DATE'=>_makeStartInput('','START_DATE'),'SCHOOL_ID'=>_makeSchoolInput('','SCHOOL_ID'));

$columns = array('START_DATE'=>_('Attendance Start Date this School Year'),'END_DATE'=>_('Dropped'),'SCHOOL_ID'=>_('School'));
//$_REQUEST['modfunc'] = '';
$schools_RET = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOLS WHERE ID!='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
$next_school_options = array(UserSchool()=>_('Next grade at current school'),'0'=>_('Retain'),'-1'=>_('Do not enroll after this school year'));
if(count($schools_RET))
{
	foreach($schools_RET as $school)
		$next_school_options[$school['ID']] = $school['TITLE'];
}

$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY DEFAULT_CALENDAR ASC"));
if(count($calendars_RET))
{
	foreach($calendars_RET as $calendar)
		$calendar_options[$calendar['CALENDAR_ID']] = $calendar['TITLE'];
}

if($_REQUEST['student_id']!='new')
{
	if(count($RET))
		$id = $RET[count($RET)]['ID'];
	else
		$id = 'new';

	ob_start();
	ListOutput($RET,$columns,'Enrollment Record','Enrollment Records',$link,array(),array('save'=>false,'search'=>false));
	$LO = ob_get_clean();
	if($id!='new')
		$next_school = $RET[count($RET)]['NEXT_SCHOOL'];
	if($id!='new')
		$calendar = $RET[count($RET)]['CALENDAR_ID'];
	$div = true;
}
else
{
	$id = 'new';
	ob_start();
	ListOutput($RET,$columns,'Enrollment Record','Enrollment Records',$link,array(),array('count'=>false));
	$LO = ob_get_clean();
	$next_school = UserSchool();
	$calendar = $calendars_RET[1]['CALENDAR_ID'];
	$div = false;
}

echo '<TABLE style="margin:0 auto;"><TR class="st"><TD>'.SelectInput($calendar,"values[STUDENT_ENROLLMENT][$id][CALENDAR_ID]",(!$calendar||!$div?'<span class="legend-red">':'')._('Calendar').(!$calendar||!$div?'</span>':''),$calendar_options,false,'',$div).'</TD><TD style="width:30px;"></TD><TD>'.SelectInput($next_school,"values[STUDENT_ENROLLMENT][$id][NEXT_SCHOOL]",($next_school==''||!$div?'<span class="legend-red">':'')._('Rolling / Retention Options').(!$next_school||!$div?'</span>':''),$next_school_options,false,'',$div).'</TD></TR></TABLE>';

if ($PopTable_opened)
	PopTable('footer');
echo $LO;
if ($PopTable_opened)
	echo '<TABLE><TR><TD>';
?>