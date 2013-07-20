<?php

function VerifyDate($date)
{
	if(mb_strlen($date)==9) // ORACLE
	{
		$day = mb_substr($date,0,2)+0;
		$month = MonthNWSwitch(mb_substr($date,3,3),'tonum')+0;
		$year = mb_substr($date,7,2);
		$year = (($year<50)?20:19) . $year;
	}
	elseif(mb_strlen($date)==10) // POSTGRES
	{
		$day = mb_substr($date,8,2)+0;
		$month = mb_substr($date,5,2)+0;
		$year = mb_substr($date,0,4);
	}
	elseif(mb_strlen($date)==11) // ORACLE with 4-digit year
	{
		$day = mb_substr($date,0,2)+0;
		$month = MonthNWSwitch(mb_substr($date,3,3),'tonum')+0;
		$year = mb_substr($date,7,4);
	}
	else
		return false;

	return checkdate($month,$day,$year);
}

?>
