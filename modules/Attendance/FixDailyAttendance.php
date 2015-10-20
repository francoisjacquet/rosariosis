<?php
//FJ move Attendance.php from functions/ to modules/Attendance/includes
require('modules/Attendance/includes/UpdateAttendanceDaily.fnc.php');

DrawHeader(ProgramTitle());

//FJ add translation 
$message = '<TABLE><TR><TD colspan="7" class="center">'._('From').' '.PrepareDate(DBDate(),'_min').' '._('to').' '.PrepareDate(DBDate(),'_max').'</TD></TR></TABLE>';
if (Prompt(_('Confirm'),_('When do you want to recalculate the daily attendance?'),$message))
{
	//FJ display notice while calculating daily attendance
	echo '<BR />';
	PopTable('header',_('Recalculate Daily Attendance'));
	echo '<DIV id="messageDIV" class="center"><span class="loading"></span> '._('Calculating ...').' </DIV>';
	PopTable('footer');
	ob_flush();
	flush();
	set_time_limit(0);
	
	$current_RET = DBGet(DBQuery("SELECT DISTINCT to_char(SCHOOL_DATE,'dd-MON-YYYY') as SCHOOL_DATE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"),array(),array('SCHOOL_DATE'));
	$students_RET = GetStuList();

	$begin = mktime(0,0,0,MonthNWSwitch($_REQUEST['month_min'],'to_num'),$_REQUEST['day_min']*1,$_REQUEST['year_min']) + 43200;
	$end = mktime(0,0,0,MonthNWSwitch($_REQUEST['month_max'],'to_num'),$_REQUEST['day_max']*1,$_REQUEST['year_max']) + 43200;

	for($i=$begin;$i<=$end;$i+=86400)
	{
		if ( $current_RET[mb_strtoupper(date('d-M-Y',$i))])
		{
			foreach ( (array)$students_RET as $student)
			{
				UpdateAttendanceDaily($student['STUDENT_ID'],date('d-M-Y',$i));
			}
		}
	}
	
	unset($_REQUEST['modfunc']);
	
	//FJ display notice while calculating daily attendance
	echo '<script>var msg_done='.json_encode(ErrorMessage(array(_('The Daily Attendance for that timeframe has been recalculated.')), 'note')).'; document.getElementById("messageDIV").innerHTML = msg_done;</script>';
}
