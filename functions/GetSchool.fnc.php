<?php

function GetSchool($sch,$name='TITLE')
{	global $_ROSARIO;

	if(!$_ROSARIO['GetSchool'])
	{
		$QI=DBQuery("SELECT ID,TITLE,SCHOOL_NUMBER FROM SCHOOLS WHERE SYEAR='".UserSyear()."'");
		$_ROSARIO['GetSchool'] = DBGet($QI,array(),array('ID'));
	}

	if($name=='TITLE' || $name=='SCHOOL_ID')
		if($_ROSARIO['GetSchool'][$sch])
			return $_ROSARIO['GetSchool'][$sch][1]['TITLE'];
		else
			return $sch;
	else
		return $_ROSARIO['GetSchool'][$sch][1][$name];
}
?>
