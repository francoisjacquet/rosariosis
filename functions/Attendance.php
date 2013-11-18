<?php

function UpdateAttendanceDaily($student_id,$date='',$comment=false)
{
	if(!$date)
		$date = DBDate();

	//modif Francois: days numbered
	//modif Francois: multiple school periods for a course period
	if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
	{
		$sql = "SELECT
					sum(sp.LENGTH) AS TOTAL
				FROM SCHEDULE s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,ATTENDANCE_CALENDAR ac, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
				WHERE 
					cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
					s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0
					AND ac.SCHOOL_DATE='$date' AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL)
					AND ac.CALENDAR_ID=cp.CALENDAR_ID AND ac.SCHOOL_ID=s.SCHOOL_ID AND ac.SYEAR=s.SYEAR
					AND s.SYEAR = cp.SYEAR AND sp.PERIOD_ID = cpsp.PERIOD_ID
					AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number FROM attendance_calendar WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<='$date' AND end_date>='$date' AND mp='QTR') AND school_date<='$date') AS INT) FOR 1) IN cpsp.DAYS)>0
					AND s.STUDENT_ID='$student_id'
					AND s.SYEAR='".UserSyear()."'
					AND ('$date' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '$date'>=s.START_DATE))
					AND s.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',$date)).")
				";
	} else {
		$sql = "SELECT
					sum(sp.LENGTH) AS TOTAL
				FROM SCHEDULE s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,ATTENDANCE_CALENDAR ac, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
				WHERE 
					cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
					s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0
					AND ac.SCHOOL_DATE='$date' AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL)
					AND ac.CALENDAR_ID=cp.CALENDAR_ID AND ac.SCHOOL_ID=s.SCHOOL_ID AND ac.SYEAR=s.SYEAR
					AND s.SYEAR = cp.SYEAR AND sp.PERIOD_ID = cpsp.PERIOD_ID
					AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM cast('$date' AS DATE)) AS INT)+1 FOR 1) IN cpsp.DAYS)>0
					AND s.STUDENT_ID='$student_id'
					AND s.SYEAR='".UserSyear()."'
					AND ('$date' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND '$date'>=s.START_DATE))
					AND s.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',$date)).")
				";
	}
	$RET = DBGet(DBQuery($sql));
	$total = $RET[1]['TOTAL'];
	if($total==0)
		return;

	$sql = "SELECT sum(sp.LENGTH) AS TOTAL
			FROM ATTENDANCE_PERIOD ap,SCHOOL_PERIODS sp,ATTENDANCE_CODES ac
			WHERE ap.STUDENT_ID='$student_id' AND ap.SCHOOL_DATE='$date' AND ap.PERIOD_ID=sp.PERIOD_ID AND ac.ID = ap.ATTENDANCE_CODE AND ac.STATE_CODE='A'
			AND sp.SYEAR='".UserSyear()."'";
	$RET = DBGet(DBQuery($sql));
	$total -= $RET[1]['TOTAL'];

	$sql = "SELECT sum(sp.LENGTH) AS TOTAL
			FROM ATTENDANCE_PERIOD ap,SCHOOL_PERIODS sp,ATTENDANCE_CODES ac
			WHERE ap.STUDENT_ID='$student_id' AND ap.SCHOOL_DATE='$date' AND ap.PERIOD_ID=sp.PERIOD_ID AND ac.ID = ap.ATTENDANCE_CODE AND ac.STATE_CODE='H'
			AND sp.SYEAR='".UserSyear()."'";
	$RET = DBGet(DBQuery($sql));
	$total -= $RET[1]['TOTAL']*.5;

	if($total >= Config('ATTENDANCE_FULL_DAY_MINUTES'))
		$length = '1.0';
	elseif($total >= (Config('ATTENDANCE_FULL_DAY_MINUTES') / 2))
		$length = '.5';
	else
		$length = '0.0';

	$current_RET = DBGet(DBQuery("SELECT MINUTES_PRESENT,STATE_VALUE,COMMENT FROM ATTENDANCE_DAY WHERE STUDENT_ID='$student_id' AND SCHOOL_DATE='$date'"));
	if(count($current_RET) && ($current_RET[1]['MINUTES_PRESENT']!=$total || $current_RET[1]['STATE_VALUE']!=$length))
		DBQuery("UPDATE ATTENDANCE_DAY SET MINUTES_PRESENT='$total',STATE_VALUE='$length'".($comment!==false?",COMMENT='".$comment."'":'')." WHERE STUDENT_ID='$student_id' AND SCHOOL_DATE='$date'");
	elseif(count($current_RET) && $comment!==false && $current_RET[1]['COMMENT']!=$comment)
		DBQuery("UPDATE ATTENDANCE_DAY SET COMMENT='".$comment."' WHERE STUDENT_ID='$student_id' AND SCHOOL_DATE='$date'");
	elseif(count($current_RET)==0)
		DBQuery("INSERT INTO ATTENDANCE_DAY (SYEAR,STUDENT_ID,SCHOOL_DATE,MINUTES_PRESENT,STATE_VALUE,MARKING_PERIOD_ID,COMMENT) values('".UserSyear()."','$student_id','$date','$total','$length','".GetCurrentMP('QTR',$date)."','".$comment."')");
}

?>