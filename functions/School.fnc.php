<?php
function UpdateSchoolArray($school_id=null){
    if(!$school_id) $school_id = UserSchool();
    $_SESSION['SchoolData'] = DBGet(DBQuery("SELECT * FROM SCHOOLS WHERE ID = '".$school_id."' AND SYEAR = '".UserSyear()."'"));
    $_SESSION['SchoolData'] = $_SESSION['SchoolData'][1];
//modif Francois: if only one school, no Search All Schools option
	$schools_nb = DBGet(DBQuery("SELECT COUNT(*) AS SCHOOLS_NB FROM SCHOOLS WHERE SYEAR = '".UserSyear()."';"));
	$_SESSION['SchoolData']['SCHOOLS_NB'] = $schools_nb[1]['SCHOOLS_NB'];
	$_SESSION['SchoolData'];  
}
function SchoolInfo($field=null){
    if($field)
        return $_SESSION['SchoolData'][$field];
    else
        return $_SESSION['SchoolData'];
}
?>