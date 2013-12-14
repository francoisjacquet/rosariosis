<?php

//modif Francois: add School Configuration
$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='attendance'"),array(),array('TITLE'));

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

//modif Francois: multiple school periods for a course period
//$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER WHERE position(',0,' IN (SELECT DOES_ATTENDANCE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'))>0 UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND position(','||ID||',' IN (SELECT DOES_ATTENDANCE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'))>0 ORDER BY 3,SORT_ORDER,TITLE"));
$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER WHERE position(',0,' IN (SELECT cp.DOES_ATTENDANCE FROM COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='".UserCoursePeriodSchoolPeriod()."'))>0 UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND position(','||ID||',' IN (SELECT cp.DOES_ATTENDANCE FROM COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='".UserCoursePeriodSchoolPeriod()."'))>0 ORDER BY 3,SORT_ORDER,TITLE"));

if(count($categories_RET)==0)
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&table='.$_REQUEST['table'].'" method="POST">';
	DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)));
	echo '</FORM>';
	ErrorMessage(array(_('You cannot take attendance for this course period.')),'fatal');
}

if($_REQUEST['table']=='')
	$_REQUEST['table'] = $categories_RET[1]['ID'];

if($_REQUEST['table']=='0')
	$table = 'ATTENDANCE_PERIOD';
else
	$table = 'LUNCH_PERIOD';

//modif Francois: days numbered
//modif Francois: multiple school periods for a course period
if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
{
	$course_RET = DBGET(DBQuery("SELECT cp.HALF_DAY FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND acc.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE='$date' AND cp.CALENDAR_ID=acc.CALENDAR_ID AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='".UserCoursePeriodSchoolPeriod()."'
	AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
	AND sp.PERIOD_ID=cpsp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number FROM attendance_calendar WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR') AND school_date<=acc.SCHOOL_DATE) AS INT) FOR 1) IN cpsp.DAYS)>0
		OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
	AND position(',$_REQUEST[table],' IN cp.DOES_ATTENDANCE)>0"));
} else {
	$course_RET = DBGET(DBQuery("SELECT cp.HALF_DAY FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND acc.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE='$date' AND cp.CALENDAR_ID=acc.CALENDAR_ID AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='".UserCoursePeriodSchoolPeriod()."'
	AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
	AND sp.PERIOD_ID=cpsp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0
		OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
	AND position(',$_REQUEST[table],' IN cp.DOES_ATTENDANCE)>0"));
}
if(count($course_RET)==0)
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&table='.$_REQUEST['table'].'" method="POST">';
	DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)));
	echo '</FORM>';
	ErrorMessage(array(_('You cannot take attendance for this period on this day.')),'fatal');
}

$qtr_id = GetCurrentMP('QTR',$date,false);
if(!$qtr_id)
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&table='.$_REQUEST['table'].'" method="POST">';
	DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)));
	echo '</FORM>';
	ErrorMessage(array(_('The selected date is not in a school quarter.')),'fatal');
}

// if running as a teacher program then rosario[allow_edit] will already be set according to admin permissions
if(!isset($_ROSARIO['allow_edit']))
{
	// allow teacher edit if selected date is in the current quarter or in the corresponding grade posting period
	$current_qtr_id = GetCurrentMP('QTR',DBDate(),false);
	$time = strtotime(DBDate('postgres'));
	if(($current_qtr_id && $qtr_id==$current_qtr_id || GetMP($qtr_id,'POST_START_DATE') && ($time<=strtotime(GetMP($qtr_id,'POST_END_DATE')))) && ($program_config['ATTENDANCE_EDIT_DAYS_BEFORE'][1]['VALUE']==null || strtotime($date)<=$time+$program_config['ATTENDANCE_EDIT_DAYS_BEFORE'][1]['VALUE']*86400) && ($program_config['ATTENDANCE_EDIT_DAYS_AFTER'][1]['VALUE']=='' || strtotime($date)>=$time-$program_config['ATTENDANCE_EDIT_DAYS_AFTER'][1]['VALUE']*86400))
		$_ROSARIO['allow_edit'] = true;
}

$current_Q = "SELECT ATTENDANCE_TEACHER_CODE,STUDENT_ID,ADMIN,COMMENT,COURSE_PERIOD_ID,ATTENDANCE_REASON FROM $table t WHERE SCHOOL_DATE='$date' AND PERIOD_ID='".UserPeriod()."'".($table=='LUNCH_PERIOD'?" AND TABLE_NAME='$_REQUEST[table]'":'');
$current_RET = DBGet(DBQuery($current_Q),array(),array('STUDENT_ID'));
if($_REQUEST['attendance'] && $_POST['attendance'])
{
	foreach($_REQUEST['attendance'] as $student_id=>$value)
	{
		if($current_RET[$student_id])
		{
			$sql = "UPDATE $table SET ATTENDANCE_TEACHER_CODE='".mb_substr($value,5)."',COURSE_PERIOD_ID='".UserCoursePeriod()."'";
			if($current_RET[$student_id][1]['ADMIN']!='Y')
				$sql .= ",ATTENDANCE_CODE='".mb_substr($value,5)."'";
			if($_REQUEST['comment'][$student_id])
				$sql .= ",COMMENT='".trim($_REQUEST['comment'][$student_id])."'";
			$sql .= " WHERE SCHOOL_DATE='$date' AND PERIOD_ID='".UserPeriod()."' AND STUDENT_ID='$student_id'";
		}
		else
			$sql = "INSERT INTO ".$table." (STUDENT_ID,SCHOOL_DATE,MARKING_PERIOD_ID,PERIOD_ID,COURSE_PERIOD_ID,ATTENDANCE_CODE,ATTENDANCE_TEACHER_CODE,COMMENT".($table=='LUNCH_PERIOD'?',TABLE_NAME':'').") values('$student_id','$date','$qtr_id','".UserPeriod()."','".UserCoursePeriod()."','".mb_substr($value,5)."','".mb_substr($value,5)."','".$_REQUEST['comment'][$student_id]."'".($table=='LUNCH_PERIOD'?",'$_REQUEST[table]'":'').")";
		DBQuery($sql);
		if($_REQUEST['table']=='0')
			UpdateAttendanceDaily($student_id,$date);
	}
	$RET = DBGet(DBQuery("SELECT 'Y' AS COMPLETED FROM ATTENDANCE_COMPLETED WHERE STAFF_ID='".User('STAFF_ID')."' AND SCHOOL_DATE='$date' AND PERIOD_ID='".UserPeriod()."' AND TABLE_NAME='".$_REQUEST['table']."'"));
	if(!count($RET))
		DBQuery("INSERT INTO ATTENDANCE_COMPLETED (STAFF_ID,SCHOOL_DATE,PERIOD_ID,TABLE_NAME) values('".User('STAFF_ID')."','$date','".UserPeriod()."','".$_REQUEST['table']."')");

	$current_RET = DBGet(DBQuery($current_Q),array(),array('STUDENT_ID'));
	unset($_SESSION['_REQUEST_vars']['attendance']);
}

$codes_RET = DBGet(DBQuery("SELECT ID,TITLE,DEFAULT_CODE,STATE_CODE FROM ATTENDANCE_CODES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND TYPE = 'teacher' AND TABLE_NAME='".$_REQUEST['table']."'".($_REQUEST['table']=='0' && $course_RET[1]['HALF_DAY'] ? " AND STATE_CODE!='H'" : '')." ORDER BY SORT_ORDER"));
if(count($codes_RET))
{
	foreach($codes_RET as $code)
	{
		$extra['SELECT'] .= ",'$code[STATE_CODE]' AS CODE_".$code['ID'];
		if($code['DEFAULT_CODE']=='Y')
			$extra['functions']['CODE_'.$code['ID']] = '_makeRadioSelected';
		else
			$extra['functions']['CODE_'.$code['ID']] = '_makeRadio';
		$columns['CODE_'.$code['ID']] = $code['TITLE'];
	}
}
else
	$columns = array();
$extra['SELECT'] .= ',s.STUDENT_ID AS COMMENT,s.STUDENT_ID AS ATTENDANCE_REASON';
$columns += array('COMMENT'=>_('Teacher Comment'));
if(!is_array($extra['functions']))
	$extra['functions'] = array();
$extra['functions'] += array('FULL_NAME'=>'_makeTipMessage','COMMENT'=>'makeCommentInput','ATTENDANCE_REASON'=>'makeAttendanceReason');
$extra['DATE'] = $date;
$stu_RET = GetStuList($extra);
if($attendance_reason)
	$columns += array('ATTENDANCE_REASON'=>_('Office Comment'));

$date_note = $date!=DBDate() ? ' <span style="color:red" class="nobr">'._('The selected date is not today').'</span> |' : '';
$date_note .= AllowEdit() ? ' <span style="color:green" class="nobr">'._('You can edit this attendance').'</span>':' <span style="color:red" class="nobr">'._('You cannot edit this attendance').'</span>';

$completed_RET = DBGet(DBQuery("SELECT 'Y' as COMPLETED FROM ATTENDANCE_COMPLETED WHERE STAFF_ID='".User('STAFF_ID')."' AND SCHOOL_DATE='$date' AND PERIOD_ID='".UserPeriod()."' AND TABLE_NAME='".$_REQUEST['table']."'"));
if(count($completed_RET))
	$note = ErrorMessage(array('<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('You already have taken attendance today for this period.')),'note');

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&table='.$_REQUEST['table'].'" method="POST">';
DrawHeader('',SubmitButton(_('Save')));
DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)).$date_note);
//DrawHeader($note);
echo $note;

$LO_columns = array('FULL_NAME'=>_('Student'),'STUDENT_ID'=>_('RosarioSIS ID'),'GRADE_ID'=>_('Grade Level')) + $columns;

//$tabs[] = array('title'=>'Attendance','link'=>"Modules.php?modname=$_REQUEST[modname]&table=0&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]&year_date=$_REQUEST[year_date]");
//$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
foreach($categories_RET as $category)
	$tabs[] = array('title'=>ParseMLField($category['TITLE']),'link'=>"Modules.php?modname=$_REQUEST[modname]&table=$category[ID]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]&year_date=$_REQUEST[year_date]");

echo '<BR />';
if(count($categories_RET))
    $LO_options = array('download'=>false,'search'=>false,'header'=>WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&table=$_REQUEST[table]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]&year_date=$_REQUEST[year_date]"));
else
    $LO_options = array();

ListOutput($stu_RET,$LO_columns,'Student','Students',false,array(),$LO_options);

echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
echo '</FORM>';

function _makeRadio($value,$title)
{	global $THIS_RET,$current_RET;

	$colors = array('P'=>'#00FF00','A'=>'#FF0000','H'=>'#FFCC00','T'=>'#0000FF');
	if($current_RET[$THIS_RET['STUDENT_ID']][1]['ATTENDANCE_TEACHER_CODE']==mb_substr($title,5))
		return '<div style="'.($current_RET[$THIS_RET['STUDENT_ID']][1]['COURSE_PERIOD_ID']==UserCoursePeriod()?($colors[$value]?'background-color:'.$colors[$value].';':''):'background-color:#000000;').' float:left;">&nbsp;&nbsp;<INPUT type="radio" name="attendance['.$THIS_RET['STUDENT_ID'].']" value="'.$title.'" checked />&nbsp;&nbsp;</div>';
	else
		return '<div style="float:left;">&nbsp;&nbsp;<INPUT type="radio" name="attendance['.$THIS_RET['STUDENT_ID'].']" value="'.$title.'"'.(AllowEdit()?'':' disabled').'>&nbsp;&nbsp;</div>';
}

function _makeRadioSelected($value,$title)
{	global $THIS_RET,$current_RET;

	$colors = array('P'=>'#00FF00','A'=>'#FF0000','H'=>'#FFCC00','T'=>'#0000FF');
	$colors1 = array('P'=>'#DDFFDD','A'=>'#FFDDDD','H'=>'#FFEEDD','T'=>'#DDDDFF');
	if($current_RET[$THIS_RET['STUDENT_ID']][1]['ATTENDANCE_TEACHER_CODE']!='')
		if($current_RET[$THIS_RET['STUDENT_ID']][1]['ATTENDANCE_TEACHER_CODE']==mb_substr($title,5))
			return '<div style="'.($current_RET[$THIS_RET['STUDENT_ID']][1]['COURSE_PERIOD_ID']==UserCoursePeriod()?($colors[$value]?'background-color:'.$colors[$value].';':''):'background-color:#000000;').' float:left;">&nbsp;&nbsp;<INPUT type="radio" name="attendance['.$THIS_RET['STUDENT_ID'].']" value="'.$title.'" checked />&nbsp;&nbsp;</div>';
		else
			return '<div style="float:left;">&nbsp;&nbsp;<INPUT type="radio" name="attendance['.$THIS_RET['STUDENT_ID'].']" value="'.$title.'"'.(AllowEdit()?'':' disabled').'>&nbsp;&nbsp;</div>';
	else
		return '<div style="'.($colors1[$value]?'background-color:'.$colors1[$value].';':'').'; float:left;">&nbsp;&nbsp;<INPUT type="radio" name="attendance['.$THIS_RET['STUDENT_ID'].']" value="'.$title.'" checked />&nbsp;&nbsp;</div>';
}

function _makeTipMessage($value,$title)
{	global $THIS_RET,$StudentPicturesPath;

	if($StudentPicturesPath && ($file = @fopen($picture_path=$StudentPicturesPath.UserSyear().'/'.$THIS_RET['STUDENT_ID'].'.jpg','r') || $file = @fopen($picture_path=$StudentPicturesPath.(UserSyear()-1).'/'.$THIS_RET['STUDENT_ID'].'.jpg','r')))
		return '<DIV onMouseOver=\'stm(["'.str_replace('"','\"',str_replace("'",'&#39;',$THIS_RET['FULL_NAME'])).'","<IMG SRC=\"'.str_replace('\\','\\\\',$picture_path).'\" width=\"150\">"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'>'.$value.'</DIV>';
	else
		return $value;
}

function makeCommentInput($student_id,$column)
{	global $current_RET;

	return TextInput($current_RET[$student_id][1]['COMMENT'],'comment['.$student_id.']','','maxlength="100" size="20"',true,true);
}

function makeAttendanceReason($student_id,$column)
{	global $current_RET,$attendance_reason;

	if($current_RET[$student_id][1]['ATTENDANCE_REASON'])
	{
		$attendance_reason = true;
		return $current_RET[$student_id][1]['ATTENDANCE_REASON'];
	}
}
?>