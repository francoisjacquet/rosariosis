<?php

function VerifyDate($date)
{
	if(strlen($date)==9) // ORACLE
	{
		$day = substr($date,0,2)+0;
		$month = MonthNWSwitch(substr($date,3,3),'tonum')+0;
		$year = substr($date,7,2);
		$year = (($year<50)?20:19) . $year;
	}
	elseif(strlen($date)==10) // POSTGRES
	{
		$day = substr($date,8,2)+0;
		$month = substr($date,5,2)+0;
		$year = substr($date,0,4);
	}
	elseif(strlen($date)==11) // ORACLE with 4-digit year
	{
		$day = substr($date,0,2)+0;
		$month = MonthNWSwitch(substr($date,3,3),'tonum')+0;
		$year = substr($date,7,4);
	}
	else
		return false;

	return checkdate($month,$day,$year);
}

?>
