<?php

function GetSyear($date)
{
	//$RET = DBGet(DBQuery("SELECT SYEAR FROM ATTENDANCE_CALENDAR WHERE SCHOOL_DATE = '$date' AND DEFAULT_CALENDAR='Y'"));
	//$RET = DBGet(DBQuery("SELECT SYEAR FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND '".$date."' BETWEEN START_DATE AND END_DATE"));
	$RET = DBGet(DBQuery("SELECT max(SYEAR) AS SYEAR FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND START_DATE<='".$date."'"));

	return $RET[1]['SYEAR'];
}

//modif Francois: school year over one/two calendar years format
function FormatSyear($syear, $syear_over_two_years=true)
{
	if ($syear_over_two_years)
		return $syear.'-'.($syear + 1);
	else
		return $syear;
}
?>