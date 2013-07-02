<?php

function GetGrade($grade,$column='TITLE')
{	global $_ROSARIO;

	if($column!='TITLE' && $column!='SHORT_NAME' && $column!='SORT_ORDER' && $column!='NEXT_GRADE_ID')
		$column = 'TITLE';

	if(!$_ROSARIO['GetGrade'])
	{
		$QI=DBQuery("SELECT ID,TITLE,SHORT_NAME,SORT_ORDER,NEXT_GRADE_ID FROM SCHOOL_GRADELEVELS");
		$_ROSARIO['GetGrade'] = DBGet($QI,array(),array('ID'));
	}
	if($column=='TITLE')
		$extra = '<!-- '.$_ROSARIO['GetGrade'][$grade][1]['SORT_ORDER'].' -->';

	return $extra.$_ROSARIO['GetGrade'][$grade][1][$column];
}
?>