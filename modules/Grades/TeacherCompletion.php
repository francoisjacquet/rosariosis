<?php

DrawHeader(ProgramTitle());

$sem = GetParentMP('SEM',UserMP());
$fy = GetParentMP('FY',$sem);
$pros = GetChildrenMP('PRO',UserMP());

// if the UserMP has been changed, the REQUESTed MP may not work
if(!$_REQUEST['mp'] || mb_strpos($str="'".UserMP()."','".$sem."','".$fy."',".$pros,"'".$_REQUEST['mp']."'")===false)
	$_REQUEST['mp'] = UserMP();

$QI = DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND EXISTS (SELECT '' FROM COURSE_PERIODS WHERE PERIOD_ID=school_periods.PERIOD_ID) ORDER BY SORT_ORDER");
$periods_RET = DBGet($QI,array(),array('PERIOD_ID'));

$period_select = '<SELECT name="period" onChange="ajaxPostForm(this.form,true);"><OPTION value="">'._('All').'</OPTION>';
foreach($periods_RET as $id=>$period)
	$period_select .= '<OPTION value="'.$id.'"'.(($_REQUEST['period']==$id)?' SELECTED="SELECTED"':'').">".$period[1]['TITLE']."</OPTION>";
$period_select .= "</SELECT>";

$mp_select = '<SELECT name="mp" onChange="ajaxPostForm(this.form,true);">';
if($pros!='')
	foreach(explode(',',str_replace("'",'',$pros)) as $pro)
		if(GetMP($pro,'DOES_GRADES')=='Y')
			$mp_select .= '<OPTION value="'.$pro.'"'.(($pro==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($pro)."</OPTION>";

$mp_select .= '<OPTION value="'.UserMP().'"'.((UserMP()==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP(UserMP())."</OPTION>";

if(GetMP($sem,'DOES_GRADES')=='Y')
	$mp_select .= '<OPTION value="'.$sem.'"'.(($sem==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($sem)."</OPTION>";

if(GetMP($fy,'DOES_GRADES')=='Y')
	$mp_select .= '<OPTION value="'.$fy.'"'.(($fy==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($fy)."</OPTION>";
$mp_select .= '</SELECT>';

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
DrawHeader($mp_select.' - '.$period_select);
echo '</FORM>';

//modif Francois: multiple school periods for a course period
/*$sql = "SELECT s.STAFF_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,sp.TITLE,cp.PERIOD_ID,cp.TITLE AS COURSE_TITLE,
			(SELECT 'Y' FROM GRADES_COMPLETED ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.MARKING_PERIOD_ID='$_REQUEST[mp]' AND ac.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AS COMPLETED
		FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp
		WHERE
			sp.PERIOD_ID = cp.PERIOD_ID AND cp.GRADE_SCALE_ID IS NOT NULL
			AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
			AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
			".(($_REQUEST['period'])?" AND cp.PERIOD_ID='$_REQUEST[period]'":'')."
		ORDER BY FULL_NAME";*/
$sql = "SELECT s.STAFF_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,sp.TITLE,cpsp.PERIOD_ID,cp.TITLE AS COURSE_TITLE,
			(SELECT 'Y' FROM GRADES_COMPLETED ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.MARKING_PERIOD_ID='$_REQUEST[mp]' AND ac.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AS COMPLETED
		FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp 
		WHERE 
			cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
			sp.PERIOD_ID = cpsp.PERIOD_ID AND cp.GRADE_SCALE_ID IS NOT NULL
			AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
			AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
			".(($_REQUEST['period'])?" AND cpsp.PERIOD_ID='$_REQUEST[period]'":'')."
		ORDER BY FULL_NAME";
$RET = DBGet(DBQuery($sql),array(),array('STAFF_ID'));

if(!$_REQUEST['period'])
{
	foreach($RET as $staff_id=>$periods)
	{
		$i++;
		$staff_RET[$i]['FULL_NAME'] = $periods[1]['FULL_NAME'];
		foreach($periods as $period)
		{
			if(!isset($_REQUEST['_ROSARIO_PDF']))
				$staff_RET[$i][$period['PERIOD_ID']] .= button($period['COMPLETED']=='Y'?'check':'x','','"#" onMouseOver=\'stm(["'._('Course Title').'","'.str_replace('"','\"',str_replace("'",'&#39;',$period['COURSE_TITLE'])).'"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'').' ';
			else
				$staff_RET[$i][$period['PERIOD_ID']] = $period['COMPLETED']=='Y'?_('Yes').' ':_('No').' ';
		}
	}

	$columns = array('FULL_NAME'=>_('Teacher'));
	foreach($periods_RET as $id=>$period)
		$columns[$id] = $period[1]['TITLE'];

	ListOutput($staff_RET,$columns,'Teacher who enters grades','Teachers who enter grades');
}
else
{
	$period_title = $periods_RET[$_REQUEST['period']][1]['TITLE'];

	foreach($RET as $staff_id=>$periods)
	{
		foreach($periods as $period_id=>$period)
		{
			if(!isset($_REQUEST['_ROSARIO_PDF']))
				$RET[$staff_id][$period_id]['COMPLETED'] = button($period['COMPLETED']=='Y'?'check':'x','','').' ';
			else
				$RET[$staff_id][$period_id]['COMPLETED'] = $period['COMPLETED']=='Y'?_('Yes').' ':_('No').' ';
		}
	}
	
	ListOutput($RET,array('FULL_NAME'=>_('Teacher'),'COURSE_TITLE'=>_('Course'),'COMPLETED'=>_('Completed')),sprintf(_('Teacher who enters grades for %s'), $period_title),sprintf(_('Teachers who enter grades for %s'), $period_title),false,array('STAFF_ID'));
}
?>