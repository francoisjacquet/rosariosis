<?php
include_once('ProgramFunctions/StudentsUsersInfo.fnc.php');

$functions = array('START_DATE' => '_makeStartInput','END_DATE' => '_makeEndInput','SCHOOL_ID' => '_makeSchoolInput');
unset($THIS_RET);
$RET = DBGet(DBQuery("SELECT e.ID,e.ENROLLMENT_CODE,e.START_DATE,e.DROP_CODE,e.END_DATE,e.END_DATE AS END,e.SCHOOL_ID,e.NEXT_SCHOOL,e.CALENDAR_ID,e.GRADE_ID FROM STUDENT_ENROLLMENT e WHERE e.STUDENT_ID='".UserStudentID()."' AND e.SYEAR='".UserSyear()."' ORDER BY e.START_DATE"),$functions);

$add = true;
if (count($RET))
{
	foreach ( (array)$RET as $value)
	{
		if (($value['DROP_CODE']=='' || !$value['DROP_CODE']) && ($value['END']=='' || !$value['END']))
			$add = false;
	}
}

if ( $add)
	$link['add']['html'] = array('START_DATE'=>_makeStartInput('','START_DATE'),'SCHOOL_ID'=>_makeSchoolInput('','SCHOOL_ID'));

$columns = array('START_DATE' => _('Attendance Start Date this School Year'),'END_DATE' => _('Dropped'),'SCHOOL_ID' => _('School'));

$schools_RET = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOLS WHERE ID!='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
$next_school_options = array(UserSchool() => _('Next grade at current school'),'0' => _('Retain'),'-1' => _('Do not enroll after this school year'));
if (count($schools_RET))
{
	foreach ( (array)$schools_RET as $school)
		$next_school_options[$school['ID']] = $school['TITLE'];
}

$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY DEFAULT_CALENDAR ASC"));
if (count($calendars_RET))
{
	foreach ( (array)$calendars_RET as $calendar)
		$calendar_options[$calendar['CALENDAR_ID']] = $calendar['TITLE'];
}

$gradelevels_RET = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
if (count($gradelevels_RET))
{
	foreach ( (array)$gradelevels_RET as $gradelevel)
		$gradelevel_options[$gradelevel['ID']] = $gradelevel['TITLE'];
}

if ( $_REQUEST['student_id']!='new' && count($RET))
{
	$id = $RET[count($RET)]['ID'];

	$next_school = $RET[count($RET)]['NEXT_SCHOOL'];
	$calendar = $RET[count($RET)]['CALENDAR_ID'];
	$gradelevel_id = $RET[count($RET)]['GRADE_ID'];

	$div = true;
}
else
{
	$id = 'new';

	$next_school = UserSchool();
	$calendar = $calendars_RET[1]['CALENDAR_ID'];
	$gradelevel_id = $gradelevels_RET[1]['ID'];

	$div = false;
}

echo '<hr />';

echo '<TABLE class="width-100p valign-top"><TR class="st">';

echo '<TD>'.SelectInput($gradelevel_id,'values[STUDENT_ENROLLMENT]['.$id.'][GRADE_ID]',(!$gradelevel_id?'<span class="legend-red">':'')._('Grade Level').(!$gradelevel_id?'</span>':''),$gradelevel_options,false,'required',$div).'</TD>';

echo '<TD>'.SelectInput($calendar,'values[STUDENT_ENROLLMENT]['.$id.'][CALENDAR_ID]',(!$calendar||!$div?'<span class="legend-red">':'')._('Calendar').(!$calendar||!$div?'</span>':''),$calendar_options,false,'',$div).'</TD>';

echo '<TD>'.SelectInput($next_school,'values[STUDENT_ENROLLMENT]['.$id.'][NEXT_SCHOOL]',($next_school==''||!$div?'<span class="legend-red">':'')._('Rolling / Retention Options').(!$next_school||!$div?'</span>':''),$next_school_options,false,'',$div).'</TD>';

echo '</TR></TABLE>';

if ( $PopTable_opened)
	PopTable('footer');

ListOutput($RET,$columns,'Enrollment Record','Enrollment Records',$link,array(),array('save'=>false,'search'=>false));

if ( $PopTable_opened)
	echo '<TABLE><TR><TD>';
