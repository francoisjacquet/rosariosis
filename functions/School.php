<?php
function UpdateSchoolArray($school_id=null){
	if(!isset($school_id)) 
		$school_id = UserSchool();
	$_SESSION['SchoolData'] = DBGet(DBQuery("SELECT * FROM SCHOOLS WHERE ID = '".$school_id."' AND SYEAR = '".UserSyear()."'"));
	$_SESSION['SchoolData'] = $_SESSION['SchoolData'][1];
//modif Francois: if only one school, no Search All Schools option
	$schools_nb = DBGet(DBQuery("SELECT COUNT(*) AS SCHOOLS_NB FROM SCHOOLS WHERE SYEAR = '".UserSyear()."';"));
	$_SESSION['SchoolData']['SCHOOLS_NB'] = $schools_nb[1]['SCHOOLS_NB'];
	$_SESSION['SchoolData'];  
}

function SchoolInfo($field=null){
	if($_SESSION['SchoolData']['ID']!=UserSchool())
		UpdateSchoolArray(UserSchool());
		
	if(isset($field))
		return $_SESSION['SchoolData'][$field];
	else
		return $_SESSION['SchoolData'];
}

function GetSchool($sch,$name='TITLE')
{
	global $_ROSARIO;

	if(!$_ROSARIO['GetSchool'])
	{
		$QI=DBQuery("SELECT ID,TITLE,SCHOOL_NUMBER FROM SCHOOLS WHERE SYEAR='".UserSyear()."'");
		$_ROSARIO['GetSchool'] = DBGet($QI,array(),array('ID'));
	}
	if($name=='TITLE' || $name=='SCHOOL_ID' || $name=='LIST_SCHOOL_ID')
	{
		if($_ROSARIO['GetSchool'][$sch])
			return $_ROSARIO['GetSchool'][$sch][1]['TITLE'];
		else
			return $sch;
	}
	else
		return $_ROSARIO['GetSchool'][$sch][1][$name];
}
?>
