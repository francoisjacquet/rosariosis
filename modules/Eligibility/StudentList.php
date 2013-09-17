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


DrawHeader(ProgramTitle());
if($_REQUEST['search_modfunc'] || User('PROFILE')=='parent' || User('PROFILE')=='student')
{
	$tmp_PHP_SELF = PreparePHP_SELF();
	echo '<FORM action="'.$tmp_PHP_SELF.'" method="POST">';

	$begin_year = DBGet(DBQuery("SELECT min(date_part('epoch',SCHOOL_DATE)) as SCHOOL_DATE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
	$begin_year = $begin_year[1]['SCHOOL_DATE'];
	if (is_null($begin_year))
		ErrorMessage(array(_('There are no calendars yet setup.')), 'fatal');
	
	$date_select = '<OPTION value="'.$start.'">'.ProperDate(date('Y.m.d',$start)).' - '.ProperDate(date('Y.m.d',$end)).'</OPTION>';
	for($i=$start-(60*60*24*7);$i>=$begin_year;$i-=(60*60*24*7))
		$date_select .= '<OPTION value="'.$i.'"'.(($i+86400>=$start_time && $i-86400<=$start_time)?' SELECTED="SELECTED"':'').'>'.ProperDate(date('Y.m.d',$i)).' - '.ProperDate(date('Y.m.d',($i+1+(($END_DAY-$START_DAY))*60*60*24))).'</OPTION>';
	
	DrawHeader('<SELECT name="start_date">'.$date_select.'</SELECT>'.$period_select.' '.SubmitButton(_('Go')));
	echo '</FORM>';
}

$extra['SELECT'] = ",e.ELIGIBILITY_CODE,c.TITLE as COURSE_TITLE";
$extra['FROM'] = ",ELIGIBILITY e,COURSES c,COURSE_PERIODS cp";
$extra['WHERE'] = "AND e.STUDENT_ID=ssm.STUDENT_ID AND e.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.COURSE_ID=c.COURSE_ID AND e.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date'";

$extra['functions'] = array('ELIGIBILITY_CODE'=>'_makeLower');
$extra['group']	= array('STUDENT_ID');

Widgets('eligibility');
Widgets('activity');
Widgets('course');

if(!$_REQUEST['search_modfunc'] && User('PROFILE')!='parent' && User('PROFILE')!='student')
{
	$extra['new'] = true;
	Search('student_id',$extra);
}
else
{
	$RET = GetStuList($extra);
	
	$columns = array('FULL_NAME'=>_('Student'),'COURSE_TITLE'=>_('Course'),'ELIGIBILITY_CODE'=>_('Grade'));
	ListOutput($RET,$columns,'Student','Students',array(),array('STUDENT_ID'=>array('FULL_NAME','STUDENT_ID')));
}

function _makeLower($word)
{
	return ucwords(mb_strtolower($word));
}
?>