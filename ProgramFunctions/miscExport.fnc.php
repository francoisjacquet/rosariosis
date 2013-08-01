<?php
function _makeNextSchool($value,$column)
{	global $THIS_RET,$_ROSARIO;

	if($value=='0')
		return _('Retain');
	elseif($value=='-1')
		return _('Do not enroll after this school year');
	elseif($value==$THIS_RET['SCHOOL_ID'])
		return _('Next Grade at ').GetSchool($value);
	else
		return GetSchool($value);
}

function _makeCalendar($value,$column)
{	global $THIS_RET,$_ROSARIO,$calendars_RET;

	if(!$calendars_RET)
		$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."'"),array(),array('CALENDAR_ID'));

	return $calendars_RET[$value][1]['TITLE'];
}

function _makeTeachers($value,$column)
{
	foreach(explode('","',mb_substr($value,2,-2)) as $row)
		$return .= $row.'<BR />';
	return mb_substr($return,0,-4);
}
?>