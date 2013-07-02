<?php

function GetPeriod($period_id,$title='')
{	global $_ROSARIO;

	if(!$_ROSARIO['GetPeriod'])
	{
		$sql = "SELECT TITLE, PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."'";
		$_ROSARIO['GetPeriod'] = DBGet(DBQuery($sql),array(),array('PERIOD_ID'));
	}
	
	return $_ROSARIO['GetPeriod'][$period_id][1]['TITLE'];
}
?>
