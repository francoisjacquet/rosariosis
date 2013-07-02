<?php

if($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start'])
{
	while(!VerifyDate($start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start']))
		$_REQUEST['day_start']--;
}
else
	$start_date = '01-'.strtoupper(date('M-y'));

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
{
	while(!VerifyDate($end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end']))
		$_REQUEST['day_end']--;
}
else
	$end_date = DBDate();

DrawHeader(ProgramTitle());

if($_REQUEST['attendance'] && $_POST['attendance'] && AllowEdit())
{
	foreach($_REQUEST['attendance'] as $student_id=>$values)
	{
		foreach($values as $school_date=>$columns)
		{
			$sql = "UPDATE ATTENDANCE_PERIOD SET ADMIN='Y',";

			foreach($columns as $column=>$value)
				$sql .= $column."='".str_replace("\'","''",$value)."',";

			$sql = substr($sql,0,-1) . " WHERE SCHOOL_DATE='".$school_date."' AND PERIOD_ID='".$_REQUEST['period_id']."' AND STUDENT_ID='".$student_id."'";
			DBQuery($sql);
			UpdateAttendanceDaily($student_id,$school_date);
		}
	}
	$current_RET = DBGet(DBQuery("SELECT ATTENDANCE_TEACHER_CODE,ATTENDANCE_CODE,ATTENDANCE_REASON,STUDENT_ID,ADMIN,COURSE_PERIOD_ID FROM ATTENDANCE_PERIOD WHERE SCHOOL_DATE='".$date."'"),array(),array('STUDENT_ID','COURSE_PERIOD_ID'));
	unset($_REQUEST['attendance']);
}

if($_REQUEST['search_modfunc'] || $_REQUEST['student_id'] || UserStudentID() || User('PROFILE')=='parent' || User('PROFILE')=='student')
{
	$PHP_tmp_SELF = PreparePHP_SELF();
	$period_select = '<SELECT name="period_id" onchange="this.form.submit();"><OPTION value=""'.(empty($_REQUEST['period_id'])?' SELECTED="SELECTED"':'').'>'._('Daily').'</OPTION>';
	if(!UserStudentID() && !$_REQUEST['student_id'])
	{
		if(User('PROFILE')=='admin')
		{
			//modif Francois: multiple school periods for a course period
			//$periods_RET = DBGet(DBQuery("SELECT sp.PERIOD_ID,sp.TITLE FROM SCHOOL_PERIODS sp WHERE sp.SYEAR='".UserSyear()."' AND sp.SCHOOL_ID='".UserSchool()."' AND (SELECT count(1) FROM COURSE_PERIODS WHERE position(',0,' IN DOES_ATTENDANCE)>0 AND PERIOD_ID=sp.PERIOD_ID AND SYEAR=sp.SYEAR AND SCHOOL_ID=sp.SCHOOL_ID)>0 ORDER BY sp.SORT_ORDER"));
			$periods_RET = DBGet(DBQuery("SELECT sp.PERIOD_ID,sp.TITLE FROM SCHOOL_PERIODS sp WHERE sp.SYEAR='".UserSyear()."' AND sp.SCHOOL_ID='".UserSchool()."' AND (SELECT count(1) FROM COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0 AND cpsp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR=sp.SYEAR AND cp.SCHOOL_ID=sp.SCHOOL_ID)>0 ORDER BY sp.SORT_ORDER"));
			foreach($periods_RET as $period)
				$period_select .= '<OPTION value="'.$period['PERIOD_ID'].'"'.(($_REQUEST['period_id']==$period['PERIOD_ID'])?' SELECTED="SELECTED"':'').'>'.$period['TITLE'].'</OPTION>';
		}
		else
		{
			//modif Francois: multiple school periods for a course period
			//$periods_RET = DBGet(DBQuery("SELECT sp.PERIOD_ID,sp.TITLE FROM SCHOOL_PERIODS sp,COURSE_PERIODS cp WHERE position(',0,' IN cp.DOES_ATTENDANCE)>0 AND sp.PERIOD_ID=cp.PERIOD_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
			$periods_RET = DBGet(DBQuery("SELECT sp.PERIOD_ID,sp.TITLE FROM SCHOOL_PERIODS sp,COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0 AND sp.PERIOD_ID=cpsp.PERIOD_ID AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='".UserCoursePeriodSchoolPeriod()."'"));
			if($periods_RET)
			{
				//$period_select .= '<OPTION value="'.$periods_RET[1]['PERIOD_ID'].'"'.(($_REQUEST['period_id']==$periods_RET[1]['PERIOD_ID'] || !isset($_REQUEST['period_id']))?' SELECTED="SELECTED"':'').">".$periods_RET[1]['TITLE'].'</OPTION>';
				$period_select .= '<OPTION value="'.$periods_RET[1]['PERIOD_ID'].'"'.(($_REQUEST['period_id']==$periods_RET[1]['PERIOD_ID'])?' SELECTED="SELECTED"':'').">".$periods_RET[1]['TITLE'].'</OPTION>';
				if(!isset($_REQUEST['period_id']))
					$_REQUEST['period_id'] = $periods_RET['PERIOD_ID'];
			}
		}
	}
	else
		$period_select .= '<OPTION value="PERIOD"'.($_REQUEST['period_id']?' SELECTED="SELECTED"':'').'>'._('By Period').'</OPTION>';
	$period_select .= '</SELECT>';
	echo '<FORM action="'.$PHP_tmp_SELF.'" method="POST">';
	DrawHeader(_('Timeframe').':'.PrepareDate($start_date,'_start').' '._('to').' '.PrepareDate($end_date,'_end').' : '.$period_select.' : <INPUT type="submit" value="'._('Go').'" />');
}

$cal_RET = DBGet(DBQuery("SELECT DISTINCT SCHOOL_DATE,'_'||to_char(SCHOOL_DATE,'yyyymmdd') AS SHORT_DATE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."' AND SCHOOL_DATE BETWEEN '$start_date' AND '$end_date' ORDER BY SCHOOL_DATE"));

if(UserStudentID() || $_REQUEST['student_id'] || User('PROFILE')=='parent')
{
	// JUST TO SET USERSTUDENTID()
	Search('student_id');
	if($_REQUEST['period_id'])
	{
		//modif Francois: multiple school periods for a course period
		/*$sql = "SELECT
				cp.TITLE as COURSE_PERIOD,sp.TITLE as PERIOD,cp.PERIOD_ID
			FROM
				SCHEDULE s,COURSES c,COURSE_PERIODS cp,SCHOOL_PERIODS sp
			WHERE 
				s.COURSE_ID = c.COURSE_ID AND s.COURSE_ID = cp.COURSE_ID
				AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND cp.PERIOD_ID = sp.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0
				AND s.SYEAR = c.SYEAR AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
				AND s.STUDENT_ID='".UserStudentID()."' AND s.SYEAR='".UserSyear()."'
				AND ('".DBDate()."' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
			ORDER BY sp.SORT_ORDER
			";*/
		$sql = "SELECT
				cp.TITLE as COURSE_PERIOD,sp.TITLE as PERIOD,cpsp.PERIOD_ID
			FROM
				SCHEDULE s,COURSES c,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
			WHERE 
				cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND
				s.COURSE_ID = c.COURSE_ID AND s.COURSE_ID = cp.COURSE_ID
				AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID = sp.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0
				AND s.SYEAR = c.SYEAR AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
				AND s.STUDENT_ID='".UserStudentID()."' AND s.SYEAR='".UserSyear()."'
				AND ('".DBDate()."' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
			ORDER BY sp.SORT_ORDER
			";
		$schedule_RET = DBGet(DBQuery($sql));

		$sql = "SELECT ap.SCHOOL_DATE,ap.PERIOD_ID,ac.SHORT_NAME,ac.STATE_CODE,ac.DEFAULT_CODE FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac WHERE ap.SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."' AND ap.ATTENDANCE_CODE=ac.ID AND ap.STUDENT_ID='".UserStudentID()."'";
		$attendance_RET = DBGet(DBQuery($sql),array(),array('SCHOOL_DATE','PERIOD_ID'));
	}
	else
	{
//modif Francois: add translation 
		$schedule_RET[1] = array('COURSE_PERIOD'=>_('Daily Attendance'),'PERIOD_ID'=>'0');
		$attendance_RET = DBGet(DBQuery("SELECT ad.SCHOOL_DATE,'0' AS PERIOD_ID,ad.STATE_VALUE AS STATE_CODE,".db_case(array('ad.STATE_VALUE',"'0.0'","'A'","'1.0'","'P'","'H'"))." AS SHORT_NAME FROM ATTENDANCE_DAY ad WHERE ad.SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."' AND ad.STUDENT_ID='".UserStudentID()."'"),array(),array('SCHOOL_DATE','PERIOD_ID'));
	}

	$i = 0;
	if(count($schedule_RET))
	{
		foreach($schedule_RET as $course)
		{
			$i++;
			$student_RET[$i]['TITLE'] = $course['COURSE_PERIOD'];
			if (!empty($course['PERIOD']))
			{
				$student_RET[$i]['PERIOD'] = $course['PERIOD'];
				$col_period = true;
			}
			foreach($cal_RET as $value)
				$student_RET[$i][$value['SHORT_DATE']] = _makePeriodColor($attendance_RET[$value['SCHOOL_DATE']][$course['PERIOD_ID']][1]['SHORT_NAME'],$attendance_RET[$value['SCHOOL_DATE']][$course['PERIOD_ID']][1]['STATE_CODE'],$attendance_RET[$value['SCHOOL_DATE']][$course['PERIOD_ID']][1]['DEFAULT_CODE']);
		}
	}

	$columns = array('TITLE'=>_('Course'));
	if (isset($col_period) && $col_period)
		$columns['PERIOD'] = _('Period');
	if(count($cal_RET))
	{
		foreach($cal_RET as $value)
			$columns[$value['SHORT_DATE']] = ShortDate($value['SCHOOL_DATE']);
	}

	ListOutput($student_RET,$columns,'Course','Courses');
}
else
{
	// in pre-2.11 versions the attendance data would be queried for all students here but data for #students*#days can be a lot
	// in 2.11 this was switched to incremental query in the _makeColor function
	if(!$_REQUEST['period_id'])
	{
		$att_sql = "SELECT ad.STATE_VALUE,SCHOOL_DATE,'_'||to_char(ad.SCHOOL_DATE,'yyyymmdd') AS SHORT_DATE FROM ATTENDANCE_DAY ad,STUDENT_ENROLLMENT ssm WHERE ad.STUDENT_ID=ssm.STUDENT_ID AND (('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL) AND '".DBDate()."'>=ssm.START_DATE) AND ssm.SCHOOL_ID='".UserSchool()."' AND ad.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date' AND ad.STUDENT_ID=";
	}
	else
	{
		$att_sql = "SELECT ap.ATTENDANCE_CODE,ap.SCHOOL_DATE,'_'||to_char(ap.SCHOOL_DATE,'yyyymmdd') AS SHORT_DATE FROM ATTENDANCE_PERIOD ap,STUDENT_ENROLLMENT ssm WHERE ap.STUDENT_ID=ssm.STUDENT_ID AND ap.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date' AND ap.PERIOD_ID='$_REQUEST[period_id]' AND ap.STUDENT_ID=";
	}

	if(count($cal_RET))
	{
		foreach($cal_RET as $value)
		{
			$extra['SELECT'] .= ",'' as _".str_replace('-','',$value['SCHOOL_DATE']);
			$extra['columns_after']['_'.str_replace('-','',$value['SCHOOL_DATE'])] = ShortDate($value['SCHOOL_DATE']);
			$extra['functions']['_'.str_replace('-','',$value['SCHOOL_DATE'])] = '_makeColor';
		}
	}
	$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname='.$_REQUEST['next_modname'].'&day_start='.$_REQUEST['day_start'].'&day_end='.$_REQUEST['day_end'].'&month_start='.$_REQUEST['month_start'].'&month_end='.$_REQUEST['month_end'].'&year_start='.$_REQUEST['year_start'].'&year_end='.$_REQUEST['year_end'].'&period_id='.$_REQUEST['period_id'];
	$extra['link']['FULL_NAME']['variables'] = array('student_id'=>'STUDENT_ID');

	Widgets('course');
	Widgets('absences');

	$extra['new'] = true;
	Search('student_id',$extra);
	echo '</FORM>';
}

function _makeColor($value,$column)
{	global $THIS_RET,$att_RET,$att_sql,$attendance_codes;

	//modif Francois: add translation:
	$attendance_codes_locale = array('P'=>_('Present'),'A'=>_('Absent'),'H'=>_('Half'));
		
	if(!$att_RET[$THIS_RET['STUDENT_ID']])
		$att_RET[$THIS_RET['STUDENT_ID']] = DBGet(DBQuery($att_sql.$THIS_RET['STUDENT_ID']),array(),array('SHORT_DATE'));

	if($_REQUEST['period_id'])
	{
		if(!$attendance_codes)
			$attendance_codes = DBGet(DBQuery("SELECT ID,DEFAULT_CODE,STATE_CODE,SHORT_NAME FROM ATTENDANCE_CODES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND TABLE_NAME='0'"),array(),array('ID'));

		$ac = $att_RET[$THIS_RET['STUDENT_ID']][$column][1]['ATTENDANCE_CODE'];
		if($attendance_codes[$ac][1]['DEFAULT_CODE']=='Y')
//modif Francois: remove LO_field
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#00FF00;">'.makeCodePulldown($ac,$THIS_RET['STUDENT_ID'],$column).'</TD></TR></TABLE>';
		elseif($attendance_codes[$ac][1]['STATE_CODE']=='P')
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#0000FF;">'.makeCodePulldown($ac,$THIS_RET['STUDENT_ID'],$column).'</TD></TR></TABLE>';
		elseif($attendance_codes[$ac][1]['STATE_CODE']=='A')
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#FF0000;">'.makeCodePulldown($ac,$THIS_RET['STUDENT_ID'],$column).'</TD></TR></TABLE>';
		elseif($attendance_codes[$ac][1]['STATE_CODE']=='H')
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#FFCC00;">'.makeCodePulldown($ac,$THIS_RET['STUDENT_ID'],$column).'</TD></TR></TABLE>';
		elseif($ac)
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#FFFF00;">'.makeCodePulldown($ac,$THIS_RET['STUDENT_ID'],$column).'</TD></TR></TABLE>';
	}
	else
	{
		$ac = $att_RET[$THIS_RET['STUDENT_ID']][$column][1]['STATE_VALUE'];
		if($ac=='0.0')
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#FF0000;">'.substr($attendance_codes_locale['A'],0,3).'</TD></TR></TABLE>';
		elseif($ac > 0 && $ac < 1)
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#FFCC00;">'.substr($attendance_codes_locale['H'],0,3).'</TD></TR></TABLE>';
		elseif($ac == 1)
			return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:#00FF00;">'.substr($attendance_codes_locale['P'],0,3).'</TD></TR></TABLE>';
	}
}

function _makePeriodColor($name,$state_code,$default_code)
{
	//modif Francois: add translation:
	$attendance_codes_locale = array('P'=>_('Present'),'A'=>_('Absent'),'H'=>_('Half'));

	if($state_code=='A' || $state_code=='0.0')
		$color = '#FF0000';
	elseif($default_code=='Y' || $state_code=='1.0')
		$color='#00FF00';
	elseif($state_code=='P' || is_numeric($state_code))
		$color = '#FFCC00';
	elseif($state_code=='T')
		$color = '#0000FF';

	if($color) // && $state_code!='1.0')
		return '<TABLE class="cellpadding-0 cellspacing-0" style="width:10px;"><TR><TD style="background-color:'.$color.';">'.(empty($attendance_codes_locale[$name])?$name:substr($attendance_codes_locale[$name],0,3)).'</TD></TR></TABLE>';
	else
		return false;
}

function makeCodePulldown($value,$student_id,$date)
{	global $THIS_RET,$attendance_codes,$_ROSARIO;

	$date = substr($date,1,4).'-'.substr($date,5,2).'-'.substr($date,7);

	if(!$_ROSARIO['code_options'])
	{
		foreach($attendance_codes as $id=>$code)
			$_ROSARIO['code_options'][$id] = $code[1]['SHORT_NAME'];
	}

	return SelectInput($value,'attendance['.$student_id.']['.$date.'][ATTENDANCE_CODE]','',$_ROSARIO['code_options']);
}
?>
