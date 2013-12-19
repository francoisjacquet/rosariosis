<?php

if($_REQUEST['month_date'] && $_REQUEST['day_date'] && $_REQUEST['year_date'])
	while(!VerifyDate($date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date']))
		$_REQUEST['day_date']--;
else
{
	$_REQUEST['day_date'] = date('d');
	$_REQUEST['month_date'] = mb_strtoupper(date('M'));
	$_REQUEST['year_date'] = date('Y');
	$date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date'];
}

DrawHeader(ProgramTitle());
$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER,TITLE"));
if($_REQUEST['table']=='')
	$_REQUEST['table'] = '0';
$category_select = "<SELECT name=table onChange='ajaxPostForm(this.form,true);'><OPTION value='0'".($_REQUEST['table']=='0'?' SELECTED="SELECTED"':'').">"._('Attendance')."</OPTION>";
foreach($categories_RET as $category)
	$category_select .= '<OPTION value="'.$category[ID].'"'.(($_REQUEST['table']==$category['ID'])?' SELECTED="SELECTED"':'').">".$category['TITLE']."</OPTION>";
$category_select .= "</SELECT>";

$QI = DBQuery("SELECT sp.PERIOD_ID,sp.TITLE FROM SCHOOL_PERIODS sp WHERE sp.SCHOOL_ID='".UserSchool()."' AND sp.SYEAR='".UserSyear()."' AND EXISTS (SELECT '' FROM COURSE_PERIODS WHERE SYEAR=sp.SYEAR AND PERIOD_ID=sp.PERIOD_ID AND position(',$_REQUEST[table],' IN DOES_ATTENDANCE)>0) ORDER BY sp.SORT_ORDER");
$periods_RET = DBGet($QI,array(),array('PERIOD_ID'));

$period_select = "<SELECT name=period onChange='ajaxPostForm(this.form,true);'><OPTION value=''>"._('All')."</OPTION>";
foreach($periods_RET as $id=>$period)
	$period_select .= '<OPTION value="'.$id.'"'.(($_REQUEST['period']==$id)?' SELECTED="SELECTED"':'').">".$period[1]['TITLE']."</OPTION>";
$period_select .= "</SELECT>";

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)).' - '.$period_select);
DrawHeader('',$category_select);
echo '</FORM>';

//modif Francois: days numbered
//modif Francois: multiple school periods for a course period
if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
{
	$sql = "SELECT s.STAFF_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,sp.TITLE,cpsp.PERIOD_ID,cp.TITLE AS COURSE_TITLE,
			(SELECT 'Y' FROM ATTENDANCE_COMPLETED ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.PERIOD_ID=sp.PERIOD_ID AND TABLE_NAME='$_REQUEST[table]') AS COMPLETED
			FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,ATTENDANCE_CALENDAR acc, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
			WHERE 
				cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
				sp.PERIOD_ID = cpsp.PERIOD_ID AND position(',$_REQUEST[table],' IN cp.DOES_ATTENDANCE)>0
				AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',$date)).")
				AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
				".(($_REQUEST['period'])?" AND cpsp.PERIOD_ID='$_REQUEST[period]'":'')." AND acc.CALENDAR_ID=cp.CALENDAR_ID AND acc.SCHOOL_DATE='$date'
				AND acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0)
				AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number FROM attendance_calendar WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR') AND school_date<=acc.SCHOOL_DATE) AS INT) FOR 1) IN cpsp.DAYS)>0
				OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
			ORDER BY FULL_NAME";
} else {
	$sql = "SELECT s.STAFF_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,sp.TITLE,cpsp.PERIOD_ID,cp.TITLE AS COURSE_TITLE,
			(SELECT 'Y' FROM ATTENDANCE_COMPLETED ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.PERIOD_ID=sp.PERIOD_ID AND TABLE_NAME='$_REQUEST[table]') AS COMPLETED
			FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,ATTENDANCE_CALENDAR acc, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
			WHERE 
				cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
				sp.PERIOD_ID = cpsp.PERIOD_ID AND position(',$_REQUEST[table],' IN cp.DOES_ATTENDANCE)>0
				AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',$date)).")
				AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
				".(($_REQUEST['period'])?" AND cpsp.PERIOD_ID='$_REQUEST[period]'":'')." AND acc.CALENDAR_ID=cp.CALENDAR_ID AND acc.SCHOOL_DATE='$date'
				AND acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0)
				AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0
				OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
			ORDER BY FULL_NAME";
}
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
				$staff_RET[$i][$period['PERIOD_ID']] = ($period['COMPLETED']=='Y'?_('Yes'):_('No'))." ";
		}
	}

	$columns = array('FULL_NAME'=>_('Teacher'));
	foreach($periods_RET as $id=>$period)
		$columns[$id] = $period[1]['TITLE'];

	ListOutput($staff_RET,$columns,'Teacher who takes attendance','Teachers who take attendance');
}
else
{
	$period_title = $periods_RET[$_REQUEST['period']][1]['TITLE'];

	//modif Francois: display icon for completed column
	foreach($RET as $staff_id=>$periods)
	{
		foreach($periods as $id=>$period)
		{
			if(!isset($_REQUEST['_ROSARIO_PDF']))
				$RET[$staff_id][$id]['COMPLETED'] = button($period['COMPLETED']=='Y'?'check':'x','','"#" onMouseOver=\'stm(["'._('Course Title').'","'.str_replace('"','\"',str_replace("'",'&#39;',$period['COURSE_TITLE'])).'"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'').' ';
			else
				$RET[$staff_id][$id]['COMPLETED'] = ($period['COMPLETED']=='Y'?_('Yes'):_('No'))." ";
		}
	}
	ListOutput($RET,array('FULL_NAME'=>_('Teacher'),'COURSE_TITLE'=>_('Course'),'COMPLETED'=>_('Completed')),sprintf(_('Teacher who takes %s attendance'),$period_title),sprintf(_('Teachers who take %s attendance'),$period_title),false,array('STAFF_ID'));
}
?>
