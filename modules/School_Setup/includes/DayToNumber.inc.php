<?php
/*
Translate a day of the week to its corresponding number according to the attendance days for the school
Monday 1 to Sunday 7
Example: if Monday is a legal holiday, then Tuesday is 1 and the next Monday is 7
$day_time parameter is a UNIX timestamp
Return false if the day is not attendance day
*/

function dayToNumber($day_time)
{
	
	$date = date('Y-m-d',$day_time);
	//check if the day is attendance day
	$check_day_RET = DBGet(DBQuery("SELECT school_date AS check_day FROM attendance_calendar WHERE school_date='".$date."' AND school_id='".($school_id = UserSchool())."'"));
	if (empty($check_day_RET)) return FALSE;
	
	//quarter start date
	$begin_quarter_RET = DBGet(DBQuery("SELECT start_date FROM school_marking_periods WHERE start_date<='".$date."' AND end_date>='".$date."' AND mp='QTR' AND school_id='".$school_id."'"));
	if (empty($begin_quarter_RET)) return FALSE;
	$begin_quarter = $begin_quarter_RET[1]['START_DATE'];

	//number of school days since the beginning of the quarter
	$school_days_RET = DBGet(DBQuery("SELECT COUNT(school_date) AS school_days FROM attendance_calendar WHERE school_date>='".$begin_quarter."' AND school_date<='".$date."' AND school_id='".$school_id."'"));
	$school_days = $school_days_RET[1]['SCHOOL_DAYS'];
	
	if ($school_days % SchoolInfo('NUMBER_DAYS_ROTATION') == 0) return SchoolInfo('NUMBER_DAYS_ROTATION');
	return $school_days % SchoolInfo('NUMBER_DAYS_ROTATION');
}

?>