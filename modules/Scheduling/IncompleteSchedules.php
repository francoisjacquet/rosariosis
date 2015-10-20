<?php
$periods_RET = DBGet(DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER"));

/*
$period_select =  "<SELECT name=period><OPTION value=''>All</OPTION>";
foreach ( (array)$periods_RET as $period)
	$period_select .= "<OPTION value=$period[PERIOD_ID]".(($_REQUEST['period']==$period['PERIOD_ID'])?' SELECTED':'').">".$period['TITLE']."</OPTION>";
$period_select .= "</SELECT>";
*/

DrawHeader(ProgramTitle());
if ($period_select)
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
	DrawHeader($period_select);
	echo '</FORM>';
}

if ($_REQUEST['search_modfunc']=='list')
{
	Widgets('course');
	Widgets('request');
	$extra['SELECT'] .= ',sp.PERIOD_ID';
	//FJ multiple school periods for a course period
	//$extra['FROM'] .= ',SCHOOL_PERIODS sp,SCHEDULE ss,COURSE_PERIODS cp';
	$extra['FROM'] .= ',SCHOOL_PERIODS sp,SCHEDULE ss,COURSE_PERIODS cp,COURSE_PERIOD_SCHOOL_PERIODS cpsp';
	/*$extra['WHERE'] .= ' AND (\''.DBDate().'\' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL) AND ss.SCHOOL_ID=ssm.SCHOOL_ID AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',UserMP()).') AND ss.STUDENT_ID=ssm.STUDENT_ID AND ss.SYEAR=ssm.SYEAR AND ss.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.PERIOD_ID=sp.PERIOD_ID ';*/
	$extra['WHERE'] .= ' AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND (\''.DBDate().'\' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL) AND ss.SCHOOL_ID=ssm.SCHOOL_ID AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',UserMP()).') AND ss.STUDENT_ID=ssm.STUDENT_ID AND ss.SYEAR=ssm.SYEAR AND ss.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=sp.PERIOD_ID ';
	//if (UserStudentID())
	//	$extra['WHERE'] .= " AND s.STUDENT_ID='".UserStudentID()."' ";
	$extra['group'] = array('STUDENT_ID','PERIOD_ID');

//FJ fix error Warning: Missing argument 1 for appendSQL()
	$extra['WHERE'] .= appendSQL('',$extra);
	$extra['WHERE'] .= CustomFields('where');
	$schedule_RET = GetStuList($extra);
	unset($extra);
	unset($_ROSARIO['Widgets']);
}

$extra['new'] = true;
Widgets('course');
Widgets('request');

foreach ( (array)$periods_RET as $period)
{
	$extra['SELECT'] .= ',NULL AS PERIOD_'.$period['PERIOD_ID'];
	$extra['columns_after']['PERIOD_'.$period['PERIOD_ID']] = $period['TITLE'];
	$extra['functions']['PERIOD_'.$period['PERIOD_ID']] = '_preparePeriods';
}
if (!$_REQUEST['search_modfunc'])
	Search('student_id',$extra);
else
{
//FJ fix error Warning: Missing argument 1 for appendSQL()
//	$extra['WHERE'] .= appendSQL();
	$extra['WHERE'] .= appendSQL('',$extra);
	$extra['WHERE'] .= CustomFields('where');
	$students_RET = GetStuList($extra);
	$bad_students[0] = array();
	foreach ( (array)$students_RET as $student)
	{
		if (count($schedule_RET[$student['STUDENT_ID']])!=count($periods_RET))
			$bad_students[] = $student;
	}
	if (!isset($extra['columns_after']) || !is_array($extra['columns_after']))
		$extra['columns_after'] = array();
	unset($bad_students[0]);
	if (AllowUse('Scheduling/Schedule.php'))
	{
		$link['FULL_NAME']['link'] = "Modules.php?modname=Scheduling/Schedule.php";
		$link['FULL_NAME']['variables'] = array('student_id'=>'STUDENT_ID');
	}
	else
		$link = array();
	ListOutput($bad_students,array('FULL_NAME'=>_('Student'),'STUDENT_ID'=>sprintf(_('%s ID'),Config('NAME')),'GRADE_ID'=>_('Grade Level'))+$extra['columns_after'],'Student with an incomplete schedule','Students with incomplete schedules',$link);
}

function _preparePeriods($value,$name)
{	global $THIS_RET,$schedule_RET;

	$period_id = mb_substr($name,7);
	if (!$schedule_RET[$THIS_RET['STUDENT_ID']][$period_id])
	{
		if (isset($_REQUEST['LO_save']))
			$return = _('No');
		else
			$return = button('x');
	}
	else
	{
		if (isset($_REQUEST['LO_save']))
			$return = _('Yes');
		else
			$return = button('check');
	}
	return $return;
}
