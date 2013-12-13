<?php

$start_end_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_CONFIG WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND PROGRAM='eligibility' AND TITLE IN ('START_DAY','END_DAY')"));
if(count($start_end_RET))
{
	foreach($start_end_RET as $value)
		$$value['TITLE'] = $value['VALUE'];
}

switch(date('D'))
{
	case 'Mon':
	$today = 1;
	break;
	case 'Tue':
	$today = 2;
	break;
	case 'Wed':
	$today = 3;
	break;
	case 'Thu':
	$today = 4;
	break;
	case 'Fri':
	$today = 5;
	break;
	case 'Sat':
	$today = 6;
	break;
	case 'Sun':
	$today = 7;
	break;
}

$start = time() - ($today-$START_DAY)*60*60*24;
$end = time();

if(!$_REQUEST['start_date'])
{
	$start_time = $start;
	$start_date = mb_strtoupper(date('d-M-y',$start_time));
	$end_date = mb_strtoupper(date('d-M-y',$end));
}
else
{
	$start_time = $_REQUEST['start_date'];
	$start_date = mb_strtoupper(date('d-M-y',$start_time));
	$end_date = mb_strtoupper(date('d-M-y',$start_time+60*60*24*7));
}

$QI = DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER ");
$periods_RET = DBGet($QI);

$period_select =  '<SELECT name="period"><OPTION value="">'._('All').'</OPTION>';
foreach($periods_RET as $period)
	$period_select .= '<OPTION value="'.$period[PERIOD_ID].'"'.(($_REQUEST['period']==$period['PERIOD_ID'])?' SELECTED="SELECTED"':'').">".$period['TITLE'].'</OPTION>';
$period_select .= '</SELECT>';

DrawHeader(ProgramTitle());
echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';

$begin_year = DBGet(DBQuery("SELECT min(date_part('epoch',SCHOOL_DATE)) as SCHOOL_DATE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
$begin_year = $begin_year[1]['SCHOOL_DATE'];

if($start && $begin_year)
{
//modif: days display to locale
	$date_select = '<OPTION value="'.$start.'">'.ProperDate(date('Y.m.d',$start)).' - '.ProperDate(date('Y.m.d',$end)).'</OPTION>';
	for($i=$start-(60*60*24*7);$i>=$begin_year;$i-=(60*60*24*7))
		$date_select .= '<OPTION value="'.$i.'"'.(($i+86400>=$start_time && $i-86400<=$start_time)?' SELECTED="SELECTED"':'').'>'.ProperDate(date('Y.m.d',$i)).' - '.ProperDate(date('Y.m.d',($i+1+(($END_DAY-$START_DAY))*60*60*24))).'</OPTION>';
}

DrawHeader(_('Timeframe').': <SELECT name="start_date">'.$date_select.'</SELECT> - '._('Period').': '.$period_select.' '.SubmitButton(_('Go')));
echo '</FORM>';

//modif Francois: multiple school periods for a course period
/*$sql = "SELECT s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,sp.TITLE,cp.PERIOD_ID,s.STAFF_ID 
		FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp 
		WHERE 
			sp.PERIOD_ID = cp.PERIOD_ID
			AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
			AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
			".(($_REQUEST['period'])?" AND cp.PERIOD_ID='$_REQUEST[period]'":'')."
			AND NOT EXISTS (SELECT '' FROM ELIGIBILITY_COMPLETED ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID = sp.PERIOD_ID AND ac.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date')
		";*/
$sql = "SELECT s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,sp.TITLE,cpsp.PERIOD_ID,s.STAFF_ID 
		FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp 
		WHERE 
			cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND 
			sp.PERIOD_ID = cpsp.PERIOD_ID
			AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
			AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
			".(($_REQUEST['period'])?" AND cpsp.PERIOD_ID='$_REQUEST[period]'":'')."
			AND NOT EXISTS (SELECT '' FROM ELIGIBILITY_COMPLETED ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID = sp.PERIOD_ID AND ac.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date')
		";
$RET = DBGet(DBQuery($sql),array(),array('STAFF_ID','PERIOD_ID'));

$i = 0;
if(count($RET))
{
	foreach($RET as $staff_id=>$periods)
	{
		$i++;
		$staff_RET[$i]['FULL_NAME'] = $periods[key($periods)][1]['FULL_NAME'];
		foreach($periods as $period_id=>$period)
			$staff_RET[$i][$period_id] = '<IMG SRC="assets/x_button.png" height="15">';
	}
}
$columns = array('FULL_NAME'=>_('Teacher'));
if(!$_REQUEST['period'])
{
	foreach($periods_RET as $period)
		$columns[$period['PERIOD_ID']] = $period['TITLE'];
}

ListOutput($staff_RET,$columns,'Teacher who hasn\'t entered eligibility','Teachers who haven\'t entered eligibility');
?>