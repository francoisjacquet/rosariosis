<?php

function GetTeacher($teacher_id,$title='',$column='FULL_NAME',$schools=true)
{	global $_ROSARIO;

	if(!$_ROSARIO['GetTeacher'])
	{
		$QI=DBQuery("SELECT STAFF_ID,LAST_NAME||', '||FIRST_NAME AS FULL_NAME,USERNAME,PROFILE FROM STAFF WHERE SYEAR='".UserSyear()."'".($schools?" AND (SCHOOLS IS NULL OR SCHOOLS LIKE '%,".UserSchool().",%')":''));
		$_ROSARIO['GetTeacher'] = DBGet($QI,array(),array('STAFF_ID'));
	}

	return $_ROSARIO['GetTeacher'][$teacher_id][1][$column];
}
?>