<?php
if(!$_REQUEST['month'])
	$_REQUEST['month'] = date("m");
else
	$_REQUEST['month'] = MonthNWSwitch($_REQUEST['month'],'tonum');
if(!$_REQUEST['year'])
	$_REQUEST['year'] = date("Y");
else
	$_REQUEST['year'] = ($_REQUEST['year']<1900?'20'.$_REQUEST['year']:$_REQUEST['year']);

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['period']) && count($_REQUEST['student']) && count($_REQUEST['dates']))
	{
		foreach($_REQUEST['period'] as $period_id=>$yes)
			$periods_list .= ",'".$period_id."'";
		$periods_list = '('.mb_substr($periods_list,1).')';

		foreach($_REQUEST['student'] as $student_id=>$yes)
			$students_list .= ",'".$student_id."'";
		$students_list = '('.mb_substr($students_list,1).')';

		$current_RET = DBGet(DBQuery("SELECT STUDENT_ID,PERIOD_ID,SCHOOL_DATE FROM ATTENDANCE_PERIOD WHERE EXTRACT(MONTH FROM SCHOOL_DATE)='".($_REQUEST['month']*1)."' AND EXTRACT(YEAR FROM SCHOOL_DATE)='$_REQUEST[year]' AND PERIOD_ID IN $periods_list AND STUDENT_ID IN $students_list"),array(),array('STUDENT_ID','SCHOOL_DATE','PERIOD_ID'));
		$state_code = DBGet(DBQuery("SELECT STATE_CODE FROM ATTENDANCE_CODES WHERE ID='$_REQUEST[absence_code]'"));
		$state_code = $state_code[1]['STATE_CODE'];
		foreach($_REQUEST['student'] as $student_id=>$yes)
		{
			foreach($_REQUEST['dates'] as $date=>$yes)
			{
				$current_mp = GetCurrentMP('QTR',$date);
				$all_mp = GetAllMP('QTR',$current_mp);
				//modif Francois: days numbered
				//modif Francois: multiple school periods for a course period
				if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
				{
					$course_periods_RET = DBGet(DBQuery("SELECT s.COURSE_PERIOD_ID,cpsp.PERIOD_ID,cp.HALF_DAY FROM SCHEDULE s,COURSE_PERIODS cp,ATTENDANCE_CALENDAR ac,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND sp.PERIOD_ID=cpsp.PERIOD_ID AND ac.SCHOOL_DATE='$date' AND ac.CALENDAR_ID=cp.CALENDAR_ID AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL) AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND s.STUDENT_ID='$student_id' AND cpsp.PERIOD_ID IN $periods_list AND position(',0,' IN cp.DOES_ATTENDANCE)>0 AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE)) AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number FROM attendance_calendar WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=ac.SCHOOL_DATE AND end_date>=ac.SCHOOL_DATE AND mp='QTR') AND school_date<=ac.SCHOOL_DATE) AS INT) FOR 1) IN cpsp.DAYS)>0 AND s.MARKING_PERIOD_ID IN ($all_mp)"),array(),array('PERIOD_ID'));
				} else {
					$course_periods_RET = DBGet(DBQuery("SELECT s.COURSE_PERIOD_ID,cpsp.PERIOD_ID,cp.HALF_DAY FROM SCHEDULE s,COURSE_PERIODS cp,ATTENDANCE_CALENDAR ac,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE sp.PERIOD_ID=cpsp.PERIOD_ID AND ac.SCHOOL_DATE='$date' AND ac.CALENDAR_ID=cp.CALENDAR_ID AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL) AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND s.STUDENT_ID='$student_id' AND cpsp.PERIOD_ID IN $periods_list AND position(',0,' IN cp.DOES_ATTENDANCE)>0 AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE)) AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM ac.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0 AND s.MARKING_PERIOD_ID IN ($all_mp)"),array(),array('PERIOD_ID'));				
				}
				//echo '<pre>'; var_dump($course_periods_RET); echo '</pre>';
				foreach($_REQUEST['period'] as $period_id=>$yes)
				{
					$course_period_id = $course_periods_RET[$period_id][1]['COURSE_PERIOD_ID'];
					if($course_period_id && !($course_periods_RET[$period_id][1]['COURSE_PERIOD_ID']=='Y' && $state_code=='H'))
					{
						if(!$current_RET[$student_id][$date][$period_id])
						{
							$sql = "INSERT INTO ATTENDANCE_PERIOD (STUDENT_ID,SCHOOL_DATE,PERIOD_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID,ATTENDANCE_CODE,ATTENDANCE_REASON,ADMIN)
										values('$student_id','$date','$period_id','$current_mp','$course_period_id','$_REQUEST[absence_code]','$_REQUEST[absence_reason]','Y')";
							DBQuery($sql);
						}
						else
						{
							$sql = "UPDATE ATTENDANCE_PERIOD SET ATTENDANCE_CODE='$_REQUEST[absence_code]',ATTENDANCE_REASON='$_REQUEST[absence_reason]',ADMIN='Y',COURSE_PERIOD_ID='$course_period_id'
								WHERE STUDENT_ID='$student_id' AND SCHOOL_DATE='$date' AND PERIOD_ID='$period_id'";
							DBQuery($sql);
						}
					}
				}
				UpdateAttendanceDaily($student_id,$date,($_REQUEST['absence_reason']?$_REQUEST['absence_reason']:false));
			}
		}
		$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('Absence records were added for the selected students.');
	}
	else
		$error[] = _('You must choose at least one period and one student.');
		
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['modfunc']);
}

DrawHeader(ProgramTitle());

if (isset($note))
	echo ErrorMessage($note, 'note');
if (isset($error))
	echo ErrorMessage($error);

if(empty($_REQUEST['modfunc']))

{
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" METHOD="POST">';
		DrawHeader('',SubmitButton(_('Add Absences to Selected Students')));

		echo '<BR />';

//modif Francois: css WPadmin
		echo '<TABLE class="postbox cellpadding-4" style="margin:0 auto;"><TR><TD style="text-align:right">'._('Add Absence to Periods').'</TD>';
		echo '<TD><TABLE><TR>';
		//$periods_RET = DBGet(DBQuery("SELECT SHORT_NAME,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));

		//modif Francois: multiple school periods for a course period
		//$periods_RET = DBGet(DBQuery("SELECT SHORT_NAME,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND EXISTS (SELECT '' FROM COURSE_PERIODS WHERE PERIOD_ID=SCHOOL_PERIODS.PERIOD_ID AND position(',0,' IN DOES_ATTENDANCE)>0) ORDER BY SORT_ORDER"));
		$periods_RET = DBGet(DBQuery("SELECT SHORT_NAME,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND EXISTS (SELECT '' FROM COURSE_PERIOD_SCHOOL_PERIODS cpsp, COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=SCHOOL_PERIODS.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0) ORDER BY SORT_ORDER"));
		foreach($periods_RET as $period)
//modif Francois: add <label> on checkbox
			echo '<TD><label><INPUT type="CHECKBOX" value="Y" name="period['.$period['PERIOD_ID'].']"> '.$period['SHORT_NAME'].'</label></TD>';
		echo '</TR></TABLE></TD>';
		echo '<TR><TD style="text-align:right">'._('Absence Code').'</TD><TD><SELECT name="absence_code">';
		$codes_RET = DBGet(DBQuery("SELECT TITLE,ID FROM ATTENDANCE_CODES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND TABLE_NAME='0'"));
		foreach($codes_RET as $code)
			echo '<OPTION value='.$code['ID'].'>'.$code['TITLE'].'</OPTION>';
		echo '</SELECT></TD></TR>';
		echo '<TR><TD style="text-align:right">'._('Absence Reason').'</TD><TD><INPUT type="text" name="absence_reason"></TD></TR>';
		echo '<TR><TD colspan="2" class="center">';
		$time = mktime(0,0,0,$_REQUEST['month']*1,1,mb_substr($_REQUEST['year'],2));
		echo PrepareDate(mb_strtoupper(date("d-M-y",$time)),'',false,array('M'=>1,'Y'=>1,'submit'=>true));

		$skip = date("w",$time);
		$last = 31;
		while(!checkdate($_REQUEST['month']*1, $last, mb_substr($_REQUEST['year'],2)))
			$last--;

		echo '<TABLE><TR>';
//		echo '<TH>S</TH><TH>M</TH><TH>T</TH><TH>W</TH><TH>Th</TH><TH>F</TH><TH>S</TH></TR><TR>';
		echo '<TH>'.mb_substr(_('Sunday'),0,3).'</TH><TH>'.mb_substr(_('Monday'),0,3).'</TH><TH>'.mb_substr(_('Tuesday'),0,3).'</TH><TH>'.mb_substr(_('Wednesday'),0,3).'</TH><TH>'.mb_substr(_('Thursday'),0,3).'</TH><TH>'.mb_substr(_('Friday'),0,3).'</TH><TH>'.mb_substr(_('Saturday'),0,3).'</TH></TR><TR>';
		$calendar_RET = DBGet(DBQuery("SELECT SCHOOL_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND MINUTES!='0' AND EXTRACT(MONTH FROM SCHOOL_DATE)='".($_REQUEST['month']*1)."'"),array(),array('SCHOOL_DATE'));
		for($i=1;$i<=$skip;$i++)
			echo '<TD></TD>';

		for($i=1;$i<=$last;$i++)
		{
			$this_date = $_REQUEST['year'].'-'.$_REQUEST['month'].'-'.($i<10?'0'.$i:$i);
			if(!$calendar_RET[$this_date])
				$disabled = ' DISABLED';
			elseif(date('Y-m-d')==$this_date)
				$disabled = ' checked';
			else
				$disabled = '';

			echo '<TD style="text-align:right"><label>'.$i.'<INPUT type="checkbox" name="dates['.$this_date.']" value="Y"'.$disabled.'></label></TD>';
			$skip++;
			if($skip%7==0 && $i!=$last)
				echo '</TR><TR>';
		}
		echo '</TR></TABLE>';
		echo '</TD></TR></TABLE></BR>';
	}

	Widgets('course');
	Widgets('absences');

	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');" /><A>');
	$extra['new'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
		echo '<BR /><span class="center">'.SubmitButton(_('Add Absences to Selected Students')).'</span></FORM>';
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '&nbsp;&nbsp;<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y" />';
}
?>