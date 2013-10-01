<?php

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
	$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
	$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

	if($_REQUEST['day_include_active_date'] && $_REQUEST['month_include_active_date'] && $_REQUEST['year_include_active_date'])
	{
		$date = $_REQUEST['day_include_active_date'].'-'.$_REQUEST['month_include_active_date'].'-'.$_REQUEST['year_include_active_date'];
		$date_extra = 'OR (\''.$date.'\' >= sr.START_DATE AND sr.END_DATE IS NULL)';
	}
	else
	{
		$date = DBDate();
		$date_extra = 'OR sr.END_DATE IS NULL';
	}
//modif Francois: multiple school periods for a course period
//	$columns = array('PERIOD_TITLE'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'),'MARKING_PERIOD_ID'=>_('Term'),'DAYS'=>_('Days'),'ROOM'=>_('Room'),'COURSE_TITLE'=>_('Course'));
	$columns = array('PERIOD_TITLE'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'),'MARKING_PERIOD_ID'=>_('Term'),'ROOM'=>_('Room'),'COURSE_TITLE'=>_('Course'));

/*	$extra['SELECT'] .= ',c.TITLE AS COURSE_TITLE,p_cp.TITLE AS PERIOD_TITLE,sr.MARKING_PERIOD_ID,p_cp.DAYS,p_cp.ROOM';
	$extra['FROM'] .= ' LEFT OUTER JOIN SCHEDULE sr ON (sr.STUDENT_ID=ssm.STUDENT_ID),COURSES c,COURSE_PERIODS p_cp,SCHOOL_PERIODS sp ';
	$extra['WHERE'] .= " AND p_cp.PERIOD_ID=sp.PERIOD_ID AND ssm.SYEAR=sr.SYEAR AND sr.COURSE_ID=c.COURSE_ID AND sr.COURSE_PERIOD_ID=p_cp.COURSE_PERIOD_ID  AND ('$date' BETWEEN sr.START_DATE AND sr.END_DATE $date_extra)";*/
	$extra['SELECT'] .= ',c.TITLE AS COURSE_TITLE,p_cp.TITLE AS PERIOD_TITLE,sr.MARKING_PERIOD_ID,p_cp.ROOM';
	$extra['FROM'] .= ' LEFT OUTER JOIN SCHEDULE sr ON (sr.STUDENT_ID=ssm.STUDENT_ID),COURSES c,COURSE_PERIODS p_cp ';
	$extra['WHERE'] .= " AND ssm.SYEAR=sr.SYEAR AND sr.COURSE_ID=c.COURSE_ID AND sr.COURSE_PERIOD_ID=p_cp.COURSE_PERIOD_ID  AND ('$date' BETWEEN sr.START_DATE AND sr.END_DATE $date_extra)";
	if($_REQUEST['mp_id'])
		$extra['WHERE'] .= ' AND sr.MARKING_PERIOD_ID IN ('.GetAllMP(GetMP($_REQUEST['mp_id'],'MP'),$_REQUEST['mp_id']).')';

//	$extra['functions'] = array('MARKING_PERIOD_ID'=>'GetMP','DAYS'=>'_makeDays');
//modif Francois: add subject areas
	$extra['functions'] = array('MARKING_PERIOD_ID'=>'GetMP', 'COURSE_TITLE'=>'CourseTitle');
	$extra['group'] = array('STUDENT_ID');
//	$extra['ORDER'] = ',sp.SORT_ORDER';
	if($_REQUEST['mailing_labels']=='Y')
		$extra['group'][] = 'ADDRESS_ID';
	Widgets('mailing_labels');

	$RET = GetStuList($extra);
	
//modif Francois: add schedule table
	$schedule_table_days = array('U'=>false,'M'=>false,'T'=>false,'W'=>false,'H'=>false,'F'=>false,'S'=>false);
	//modif Francois: days display to locale						
	$days_convert = array('U'=>_('Sunday'),'M'=>_('Monday'),'T'=>_('Tuesday'),'W'=>_('Wednesday'),'H'=>_('Thursday'),'F'=>_('Friday'),'S'=>_('Saturday'));
	//modif Francois: days numbered
	if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
		$days_convert = array('U'=>_('Day').' 7','M'=>_('Day').' 1','T'=>_('Day').' 2','W'=>_('Day').' 3','H'=>_('Day').' 4','F'=>_('Day').' 5','S'=>_('Day').' 6');
	
	$schedule_table_RET = DBGet(DBQuery("SELECT cp.ROOM,cs.TITLE,sp.TITLE AS SCHOOL_PERIOD,cpsp.DAYS,stu.STUDENT_ID,sta.FIRST_NAME||' '||sta.LAST_NAME AS FULL_NAME FROM COURSE_PERIODS cp,COURSES c,SCHOOLS s,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp,STUDENTS stu,SCHEDULE sch,STAFF sta,COURSE_SUBJECTS cs WHERE cp.COURSE_ID=c.COURSE_ID AND c.SUBJECT_ID=cs.SUBJECT_ID AND cp.SYEAR='".UserSyear()."' AND s.ID=cp.SCHOOL_ID AND s.ID='".UserSchool()."' AND s.SYEAR=cp.SYEAR AND sp.PERIOD_ID=cpsp.PERIOD_ID AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID  AND sch.MARKING_PERIOD_ID IN (".GetAllMP(GetMP($_REQUEST['mp_id'],'MP'),$_REQUEST['mp_id']).") AND stu.STUDENT_ID IN ($st_list) AND stu.STUDENT_ID=sch.STUDENT_ID AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND sta.STAFF_ID=cp.TEACHER_ID AND sp.LENGTH <= ".(Config('ATTENDANCE_FULL_DAY_MINUTES') / 2)." ORDER BY sp.SORT_ORDER"),array('TITLE'=>'CourseTitle', 'DAYS'=>'_GetDays'),array('STUDENT_ID','SCHOOL_PERIOD'));
	//modif Francois: note the "sp.LENGTH <= (Config('ATTENDANCE_FULL_DAY_MINUTES') / 2)" condition to remove Full Day and Half Day school periods from the schedule table!
	
	$columns_table = array('SCHOOL_PERIOD' => _('Periods'));
	foreach ($schedule_table_days as $day=>$true)
	{
		if ($true)
			$columns_table[$day] = $days_convert[$day];
	}
	
	if(count($RET))
	{
		$handle = PDFStart();
		if ($_REQUEST['schedule_table'] == 'No')	
			foreach($RET as $student_id=>$courses)
			{
				if($_REQUEST['mailing_labels']=='Y')
				{
					foreach($courses as $address)
					{
						echo '<BR /><BR /><BR />';
						unset($_ROSARIO['DrawHeader']);
						DrawHeader(_('Student Schedule'));
						DrawHeader(GetSchool(UserSchool()),ProperDate($date));
						DrawHeader($address[1]['FULL_NAME'],$address[1]['STUDENT_ID']);
						DrawHeader($address[1]['GRADE_ID'],$_REQUEST['mp_id']?GetMP($_REQUEST['mp_id']):'');

						echo '<BR /><BR /><BR /><TABLE class="width-100p"><TR><TD style="width:50px;"> &nbsp; </TD><TD>'.$address[1]['MAILING_LABEL'].'</TD></TR></TABLE><BR />';

						ListOutput($address,$columns,'Course','Courses',array(),array(),array('center'=>false,'print'=>false));
						echo '<div style="page-break-after: always;"></div>';
					}
				}
				else
				{
					//modif Francois: add Horizontal format option
					if (isset($_REQUEST['horizontalFormat']))
					{
						//echo '<!-- MEDIA SIZE 8.5x11in -->';
						$_SESSION['orientation'] = 'landscape';
					}
					unset($_ROSARIO['DrawHeader']);
					DrawHeader(_('Student Schedule'));
					DrawHeader(GetSchool(UserSchool()),ProperDate($date));
					DrawHeader($courses[1]['FULL_NAME'],$courses[1]['STUDENT_ID']);
					DrawHeader($courses[1]['GRADE_ID'],$_REQUEST['mp_id']?GetMP($_REQUEST['mp_id']):'');

					ListOutput($courses,$columns,'Course','Courses',array(),array(),array('center'=>false,'print'=>false));
					echo '<div style="page-break-after: always;"></div>';
				}
			}
			
	//modif Francois: add schedule table
		if ($_REQUEST['schedule_table'] == 'Yes')	
			foreach($schedule_table_RET as $student_id=>$schedule_table)
			{
				/*foreach($schedule_table as $period=>$course_periods)
				{
					$schedule_table_body .= '<TR><TD>'.$period.'</TD>';

					$schedule_table_TDs = $schedule_table_TDs_empty;
					foreach ($course_periods as $course_period)
					{
						foreach ($course_period['DAYS'] as $course_period_day)
						{
							if (!is_array($schedule_table_TDs[$course_period_day]))
								$schedule_table_TDs[$course_period_day] = array();
							$schedule_table_TDs[$course_period_day][] = '<TD>'.$course_period['TITLE'].'<BR />'.$course_period['FULL_NAME'].(empty($course_period['ROOM'])?'':'<BR />'._('Room').': '.$course_period['ROOM']).'</TD>';
						}
					}
					foreach ($schedule_table_TDs as $schedule_table_TD)
					{
						if (is_array($schedule_table_TD))
							if (count($schedule_table_TD) == 1)
								$schedule_table_body .= $schedule_table_TD[0];
							else
								$schedule_table_body .= '<TD><TABLE><TR>'.implode($schedule_table_TD).'</TR></TABLE></TD>';
						else
							$schedule_table_body .= $schedule_table_TD;
					}
					$schedule_table_body .= '</TR>';
				}
				$schedule_table_body .= '</TABLE>';*/

				if($_REQUEST['mailing_labels']=='Y')
				{
					foreach($RET[$student_id] as $address)
					{
						echo '<BR /><BR /><BR />';
						unset($_ROSARIO['DrawHeader']);
						DrawHeader(_('Student Schedule'));
						DrawHeader(GetSchool(UserSchool()),ProperDate($date));
						DrawHeader($address[1]['FULL_NAME'],$address[1]['STUDENT_ID']);
						DrawHeader($address[1]['GRADE_ID'],$_REQUEST['mp_id']?GetMP($_REQUEST['mp_id']):'');

						echo '<BR /><BR /><BR /><TABLE class="width-100p"><TR><TD style="width:50px;"> &nbsp; </TD><TD>'.$address[1]['MAILING_LABEL'].'</TD></TR></TABLE><BR />';
						
						$schedule_table = _schedule_table_RET($schedule_table);
						
						ListOutput($schedule_table,$columns_table,'Period','Periods',false,array());
					
					}
				}
				else
				{
					//modif Francois: add Horizontal format option
					if (isset($_REQUEST['horizontalFormat']))
					{
						//echo '<!-- MEDIA SIZE 8.5x11in -->';
						$_SESSION['orientation'] = 'landscape';
					}
					unset($_ROSARIO['DrawHeader']);
					DrawHeader(_('Student Schedule'));
					DrawHeader(GetSchool(UserSchool()),ProperDate($date));
					DrawHeader($RET[$student_id][1]['FULL_NAME'],$RET[$student_id][1]['STUDENT_ID']);
					DrawHeader($RET[$student_id][1]['GRADE_ID'],$_REQUEST['mp_id']?GetMP($_REQUEST['mp_id']):'');
					
					$schedule_table = _schedule_table_RET($schedule_table);
					
					ListOutput($schedule_table,$columns_table,'Period','Periods',false,array());
				}
				
				echo '<div style="page-break-after: always;"></div>';
			}
		

		PDFStop($handle);
	}
	else
		BackPrompt(_('No Students were found.'));
	}
	else
		BackPrompt(_('You must choose at least one student.'));
}

if(empty($_REQUEST['modfunc']))

{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		$mp_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,".db_case(array('MP',"'FY'","'0'","'SEM'","'1'","'QTR'","'2'"))." AS TBL FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TBL,SORT_ORDER"));
		$mp_select = '<SELECT name=mp_id><OPTION value="">'._('N/A');
		foreach($mp_RET as $mp)
			$mp_select .= '<OPTION value='.$mp['MARKING_PERIOD_ID'].'>'.$mp['TITLE'];
		$mp_select .= '</SELECT>';

		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_ROSARIO_PDF=true" method="POST" id="printSchedulesForm">';
		$extra['header_right'] = '<INPUT type="submit" value="'._('Create Schedules for Selected Students').'" />';

		$extra['extra_header_left'] = '<TABLE>';
		$extra['extra_header_left'] .= '<TR class="st"><TD>'._('Marking Period').'</TD><TD>'.$mp_select.'</TD></TR>';
		$extra['extra_header_left'] .= '<TR class="st"><TD>'._('Include only courses active as of').'</TD><TD>'.PrepareDate('','_include_active_date').'</TD></TR>';
		
		//modif Francois: add Horizontal format option
		$extra['extra_header_left'] .= '<TR><TD colspan="2">'.'<label><span class="nobr">'._('Horizontal Format').'&nbsp;<input type="checkbox" id="horizontalFormat" name="horizontalFormat" value="Y" /></span></label>'.'</TD></TR>';
	//modif Francois: add schedule table
		$extra['extra_header_left'] .= '<TR><TD colspan="2">'.'<label><input name="schedule_table" type="radio" value="Yes" checked />&nbsp;'._('Table').'</label> '.'<label><input name="schedule_table" type="radio" value="No" />&nbsp;'._('List').'</label>'.'</TD></TR>';
		
		Widgets('mailing_labels');
		$extra['extra_header_left'] .= $extra['search'];
		$extra['search'] = '';
		$extra['extra_header_left'] .= '</TABLE>';
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;
	//$extra['force_search'] = true;

	Widgets('request');
	Widgets('course');

	Search('student_id',$extra);

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center"><INPUT type="submit" value="'._('Create Schedules for Selected Students').'" /></span>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}

//modif Francois: add schedule table
function _GetDays($value, $column)
{	global $schedule_table_days;

	$days_array = str_split($value);
	
	
	foreach ($days_array as $index=>$day)
	{
		$schedule_table_days[$day] = true;
	}
	return $days_array;
}

function _schedule_table_RET($schedule_table_RET)
{
	$schedule_table_body = array();
	$i = 1;
	foreach($schedule_table_RET as $period=>$course_periods)
	{
		$schedule_table_body[$i]['SCHOOL_PERIOD'] = $period;

		foreach ($course_periods as $course_period)
		{
			foreach ($course_period['DAYS'] as $course_period_day)
			{
				if (!is_array($schedule_table_body[$i][$course_period_day]))
					$schedule_table_body[$i][$course_period_day] = array();
				$schedule_table_body[$i][$course_period_day][] = '<TD>'.$course_period['TITLE'].'<BR />'.$course_period['FULL_NAME'].(empty($course_period['ROOM'])?'':'<BR />'._('Room').': '.$course_period['ROOM']).'</TD>';
			}
		}
		$j = 0;
		foreach ($schedule_table_body[$i] as $day_key => $schedule_table_day)
		{
			$j++;
			if ($j == 1) // skip SCHOOL_PERIOD column
				continue;
			if (count($schedule_table_day) == 1)
				$schedule_table_body[$i][$day_key] = str_replace(array('<TD>', '</TD>'), '', $schedule_table_day[0]);
			else
				$schedule_table_body[$i][$day_key] = '<TABLE><TR>'.implode($schedule_table_day).'</TR></TABLE>';
		}
		$i++;
	}
	return $schedule_table_body;
}
?>
