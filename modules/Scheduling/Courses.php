<?php

if($_REQUEST['modfunc']!='choose_course')
	DrawHeader(ProgramTitle());
		
unset($_SESSION['_REQUEST_vars']['subject_id']);unset($_SESSION['_REQUEST_vars']['course_id']);unset($_SESSION['_REQUEST_vars']['course_period_id']);

// if only one subject, select it automatically -- works for Course Setup and Choose a Course
if($_REQUEST['modfunc']!='delete' && !$_REQUEST['subject_id'])
{
	$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."'"));
	if(count($subjects_RET)==1)
		$_REQUEST['subject_id'] = $subjects_RET[1]['SUBJECT_ID'];
}

$LO_options = array('save'=>false,'search'=>false,'responsive'=>false);

if($_REQUEST['course_modfunc']=='search')
{
	echo '<BR />';
	PopTable('header',_('Search'));
	echo '<FORM name="search" action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&course_modfunc=search&last_year='.$_REQUEST['last_year'].'" method="POST">';
	echo '<TABLE><TR><TD><INPUT type="text" name="search_term" value="'.$_REQUEST['search_term'].'" required></TD><TD><INPUT type="submit" value="'._('Search').'"></TD></TR></TABLE>';
	if($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php')
		echo '<INPUT type="hidden" name="include_child_mps" value="'.$_REQUEST['include_child_mps'].'"><INPUT type="hidden" name="year_date" value="'.$_REQUEST['year_date'].'"><INPUT type="hidden" name="month_date" value="'.$_REQUEST['month_date'].'"><INPUT type="hidden" name="day_date" value="'.$_REQUEST['day_date'].'">';
	echo '</FORM>';
	echo '<script type="text/javascript"><!--
		document.search.search_term.focus();
		--></script>';
	PopTable('footer');

	if($_REQUEST['search_term'])
	{
		$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE (UPPER(TITLE) LIKE '%".mb_strtoupper($_REQUEST['search_term'])."%' OR UPPER(SHORT_NAME)='".mb_strtoupper($_REQUEST['search_term'])."') AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER,TITLE"));
		$courses_RET = DBGet(DBQuery("SELECT SUBJECT_ID,COURSE_ID,TITLE FROM COURSES WHERE (UPPER(TITLE) LIKE '%".mb_strtoupper($_REQUEST['search_term'])."%' OR UPPER(SHORT_NAME)='".mb_strtoupper($_REQUEST['search_term'])."') AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' AND SCHOOL_ID='".UserSchool()."' ORDER BY TITLE"));
		//modif Francois: http://centresis.org/forums/viewtopic.php?f=13&t=4112
		$periods_RET = DBGet(DBQuery("SELECT c.SUBJECT_ID,cp.COURSE_ID,cp.COURSE_PERIOD_ID,cp.TITLE,cp.MP,cp.MARKING_PERIOD_ID,cp.CALENDAR_ID,cp.TOTAL_SEATS AS AVAILABLE_SEATS FROM COURSE_PERIODS cp,COURSES c WHERE cp.COURSE_ID=c.COURSE_ID AND (UPPER(cp.TITLE) LIKE '%".mb_strtoupper($_REQUEST['search_term'])."%' OR UPPER(cp.SHORT_NAME)='".mb_strtoupper($_REQUEST['search_term'])."') AND cp.SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' AND cp.SCHOOL_ID='".UserSchool()."'".($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php'?" AND '$date'<=(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE SYEAR=cp.SYEAR AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)":'')." ORDER BY cp.SHORT_NAME,TITLE"));
		if($_REQUEST['modname']=='Scheduling/Schedule.php')
			calcSeats1($periods_RET,$date);

		$link = array();

		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
		$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID');
		echo '<div class="st">';
		ListOutput($subjects_RET,array('TITLE'=>'Subject'),'Subject','Subjects',$link,array(),$LO_options);

		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
		$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID','course_id'=>'COURSE_ID');
		echo '</div><div class="st">';
		ListOutput($courses_RET,array('TITLE'=>_('Course')),'Course','Courses',$link,array(),$LO_options);

		$columns = array('TITLE'=>_('Course Period'));
		$link = array();
		if($_REQUEST['modname']!='Scheduling/Schedule.php' || ($_REQUEST['modname']=='Scheduling/Schedule.php' && !$_REQUEST['include_child_mps']))
		{
			$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]";
			$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID','course_id'=>'COURSE_ID','course_period_id'=>'COURSE_PERIOD_ID');
			if($_REQUEST['modfunc']=='choose_course')
				$link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]";
		}
		if($_REQUEST['modname']=='Scheduling/Schedule.php')
			$columns += array('AVAILABLE_SEATS'=>($_REQUEST['include_child_mps']?_('MP').'('._('Available Seats').')':_('Available Seats')));
		echo '</div><div class="st">';
		ListOutput($periods_RET,$columns,'Course Period','Course Periods',$link,array(),$LO_options);
		echo '</div>';
	}
}

//modif Francois: days display to locale						
$days_convert = array('U'=>_('Sunday'),'M'=>_('Monday'),'T'=>_('Tuesday'),'W'=>_('Wednesday'),'H'=>_('Thursday'),'F'=>_('Friday'),'S'=>_('Saturday'));
//modif Francois: days numbered
if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
	$days_convert = array('U'=>'7','M'=>'1','T'=>'2','W'=>'3','H'=>'4','F'=>'5','S'=>'6');


// UPDATING
if($_REQUEST['tables'] && $_POST['tables'] && AllowEdit())
{
	$where = array('COURSE_SUBJECTS'=>'SUBJECT_ID',
				'COURSES'=>'COURSE_ID',
				'COURSE_PERIODS'=>'COURSE_PERIOD_ID', 'COURSE_PERIOD_SCHOOL_PERIODS'=>'COURSE_PERIOD_SCHOOL_PERIODS_ID');

	if($_REQUEST['tables']['parent_id'])
		$_REQUEST['tables']['COURSE_PERIODS'][$_REQUEST['course_period_id']]['PARENT_ID'] = $_REQUEST['tables']['parent_id'];

	$temp_PERIOD_ID = array();
	foreach($_REQUEST['tables'] as $table_name=>$tables)
	{
		foreach($tables as $id=>$columns)
		{
	//modif Francois: fix SQL bug invalid sort order
			if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
			{
				//modif Francois: added SQL constraint TITLE (course_subjects & courses) & SHORT_NAME, TEACHER_ID (course_periods) & PERIOD_ID (course_period_school_periods) are not null
				if (!((isset($columns['TITLE']) && empty($columns['TITLE'])) || ($table_name=='COURSE_PERIODS' && ((isset($columns['SHORT_NAME']) && empty($columns['SHORT_NAME'])) || (isset($columns['TEACHER_ID']) && empty($columns['TEACHER_ID'])))) || (mb_strpos($id,'new')!==false && !empty($columns['PERIOD_ID']) && !isset($columns['DAYS']))))
				{
					if($columns['TOTAL_SEATS'] && !is_numeric($columns['TOTAL_SEATS']))
						$columns['TOTAL_SEATS'] = preg_replace('/[^0-9]+/','',$columns['TOTAL_SEATS']);
					$days = '';
					if($columns['DAYS'])
					{
						foreach($columns['DAYS'] as $day=>$y)
						{
							if($y=='Y')
								$days .= $day;
						}
						$columns['DAYS'] = $days;
					}
					if($columns['DOES_ATTENDANCE'])
					{        
						foreach($columns['DOES_ATTENDANCE'] as $tbl=>$y)
						{
							if($y=='Y')
								$tbls .= ','.$tbl;
						}
						if($tbls)
							$columns['DOES_ATTENDANCE'] = $tbls.',';
						else
							$columns['DOES_ATTENDANCE'] = '';
					}

					//if($id!='new')
					if(mb_strpos($id,'new')===FALSE)
					{
						if($table_name=='COURSES' && $columns['SUBJECT_ID'] && $columns['SUBJECT_ID']!=$_REQUEST['subject_id'])
							$_REQUEST['subject_id'] = $columns['SUBJECT_ID'];

						$sql = "UPDATE $table_name SET ";

						if($table_name=='COURSE_PERIODS')
						{
							//$current = DBGet(DBQuery("SELECT TEACHER_ID,PERIOD_ID,MARKING_PERIOD_ID,DAYS,SHORT_NAME FROM COURSE_PERIODS WHERE ".$where[$table_name]."='$id'"));
							$current = DBGet(DBQuery("SELECT TEACHER_ID,MARKING_PERIOD_ID,SHORT_NAME,TITLE FROM COURSE_PERIODS WHERE ".$where[$table_name]."='$id'"));

							if(isset($columns['TEACHER_ID']))
								$staff_id = $columns['TEACHER_ID'];
							else
								$staff_id = $current[1]['TEACHER_ID'];
								
							if(isset($columns['MARKING_PERIOD_ID']))
								$marking_period_id = $columns['MARKING_PERIOD_ID'];
							else
								$marking_period_id = $current[1]['MARKING_PERIOD_ID'];

							if($columns['SHORT_NAME'])
								$short_name = $columns['SHORT_NAME'];
							else
								$short_name = $current[1]['SHORT_NAME'];

							$teacher = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND STAFF_ID='$staff_id'"));
							if(GetMP($marking_period_id,'MP')!='FY')
								$mp_title = GetMP($marking_period_id,'SHORT_NAME').' - ';
							if($short_name)
								$mp_title .= $short_name.' - ';
							
							//$title = str_replace("'","''",$mp_title.$teacher[1]['FIRST_NAME'].' '.$teacher[1]['MIDDLE_NAME'].' '.$teacher[1]['LAST_NAME']);
							//modif Francois: remove teacher's middle name to gain space
							$base_title = str_replace("'","''",$mp_title.$teacher[1]['FIRST_NAME'].' '.$teacher[1]['LAST_NAME']);
							//get the missing part of the title before the short name:
							$title = mb_substr($current[1]['TITLE'],0,mb_strpos($current[1]['TITLE'], (GetMP($current[1]['MARKING_PERIOD_ID'],'MP')!='FY' ? GetMP($current[1]['MARKING_PERIOD_ID'],'SHORT_NAME') : $current[1]['SHORT_NAME']))).$base_title;							
							$sql .= "TITLE='$title',";

							if(isset($columns['MARKING_PERIOD_ID']))
							{
								if(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='FY')
									$columns['MP'] = 'FY';
								elseif(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='SEM')
									$columns['MP'] = 'SEM';
								else
									$columns['MP'] = 'QTR';
							}
						}
						//modif Francois: multiple school period for a course period
						if($table_name=='COURSE_PERIOD_SCHOOL_PERIODS')
						{
							$other_school_p = DBGet(DBQuery("SELECT PERIOD_ID,DAYS FROM COURSE_PERIOD_SCHOOL_PERIODS WHERE ".$where['COURSE_PERIODS']."='$_REQUEST[course_period_id]' AND ".$where[$table_name]."<>'$id'"));

							if (in_array($columns['PERIOD_ID'], $temp_PERIOD_ID))
								break;
							$temp_PERIOD_ID[] = $columns['PERIOD_ID'];
							
							$title_temp = '';
							foreach ($other_school_p as $school_p)
							{
								$school_p_title = DBGet(DBQuery("SELECT TITLE FROM SCHOOL_PERIODS WHERE PERIOD_ID='$school_p[PERIOD_ID]' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
								
			//modif Francois: days display to locale		
								$nb_days = mb_strlen($school_p['DAYS']);
								$columns_DAYS_locale = $nb_days > 1?' '._('Days').' ':($nb_days == 0 ? '' : ' '._('Day').' ');
								for ($i = 0; $i < $nb_days; $i++) {
									$columns_DAYS_locale .= mb_substr($days_convert[mb_substr($school_p['DAYS'], $i, 1)],0,3) . '.';
								}
								
								if(mb_strlen($school_p['DAYS'])<5)
		//							$mp_title .= $columns['DAYS'].' - ';
									$title_temp .= $school_p_title[1]['TITLE'].$columns_DAYS_locale.' - ';
								else
									$title_temp .= $school_p_title[1]['TITLE'].' - ';
							}
							
							if (!isset($base_title))
							{
								$current_cp = DBGet(DBQuery("SELECT TITLE,MARKING_PERIOD_ID,SHORT_NAME FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'"));
								$base_title = mb_substr($current_cp[1]['TITLE'], mb_strpos($current_cp[1]['TITLE'], (GetMP($current_cp[1]['MARKING_PERIOD_ID'],'MP')!='FY' ? GetMP($current_cp[1]['MARKING_PERIOD_ID'],'SHORT_NAME') : $current_cp[1]['SHORT_NAME'])), mb_strlen($current_cp[1]['TITLE']));
							}
								
							if(!empty($columns['DAYS']))
							{
								//modif Francois: days display to locale	
								$nb_days = mb_strlen($columns['DAYS']);
								$columns_DAYS_locale = $nb_days > 1?' '._('Days').' ':($nb_days == 0 ? '' : ' '._('Day').' ');
								for ($i = 0; $i < $nb_days; $i++) {
									$columns_DAYS_locale .= mb_substr($days_convert[mb_substr($columns['DAYS'], $i, 1)],0,3) . '.';
								}

								$period = DBGet(DBQuery("SELECT sp.TITLE FROM SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE sp.PERIOD_ID=cpsp.PERIOD_ID AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='".$id."' AND sp.SCHOOL_ID='".UserSchool()."' AND sp.SYEAR='".UserSyear()."'"));
								
								if (mb_strlen($columns['DAYS'])<5)
//									$mp_title .= $columns['DAYS'].' - ';
									$title = $period[1]['TITLE'].$columns_DAYS_locale.' - '.$title_temp.$base_title;
								else
									$title = $period[1]['TITLE'].' - '.$title_temp.$base_title;
							}
							else
								$title = $title_temp.$base_title;

							DBQuery("UPDATE COURSE_PERIODS SET TITLE='".$title."' WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'");
							
							if (empty($columns['DAYS'])) //delete school period
							{
								DBQuery("DELETE FROM COURSE_PERIOD_SCHOOL_PERIODS WHERE COURSE_PERIOD_SCHOOL_PERIODS_ID='".$id."'");
								break; //no update
							}
						}

						foreach($columns as $column=>$value)
							$sql .= $column."='".$value."',";

						$sql = mb_substr($sql,0,-1) . " WHERE ".$where[$table_name]."='$id'";
						DBQuery($sql);
						
	//modif Francois: Moodle integrator
						if (MOODLE_INTEGRATOR)
						{
							if($table_name=='COURSE_SUBJECTS' || $table_name=='COURSES')
								$moodleError = Moodle($_REQUEST['modname'], 'core_course_update_categories');
							if($table_name=='COURSE_PERIODS') 
							{
								//if Course Period is already in Moodle
								$moodle_id = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['course_period_id']."' AND \"column\"='course_period_id'"));
								if (count($moodle_id))
								{
									$moodleError = Moodle($_REQUEST['modname'], 'core_course_update_courses');
									if ($columns['TEACHER_ID'] && $columns['TEACHER_ID'] != $current[1]['TEACHER_ID']) //update teacher too
									{
										$moodleError .= Moodle($_REQUEST['modname'], 'core_role_unassign_roles');
										$moodleError .= Moodle($_REQUEST['modname'], 'enrol_manual_enrol_users');
									}
								}
							}
						}

					}
					else
					{
						$sql = "INSERT INTO $table_name ";

						if($table_name=='COURSE_SUBJECTS')
						{
							$id = DBGet(DBQuery("SELECT ".db_seq_nextval('COURSE_SUBJECTS_SEQ').' AS ID'.FROM_DUAL));
							$fields = 'SUBJECT_ID,SCHOOL_ID,SYEAR,';
							$values = "'".$id[1]['ID']."','".UserSchool()."','".UserSyear()."',";
							$_REQUEST['subject_id'] = $id[1]['ID'];
						}
						elseif($table_name=='COURSES')
						{
							$id = DBGet(DBQuery("SELECT ".db_seq_nextval('COURSES_SEQ').' AS ID'.FROM_DUAL));
		//modif Francois: SQL error column "subject_id" specified more than once !!not resolved
							$fields = 'COURSE_ID,SUBJECT_ID,SCHOOL_ID,SYEAR,';
							$values = "'".$id[1]['ID']."','$_REQUEST[subject_id]','".UserSchool()."','".UserSyear()."',";
		/*					$fields = 'COURSE_ID,SCHOOL_ID,SYEAR,';
							$values = "'".$id[1]['ID']."','".UserSchool()."','".UserSyear()."',";*/
							$_REQUEST['course_id'] = $id[1]['ID'];
						}
						elseif($table_name=='COURSE_PERIODS')
						{
							$id = DBGet(DBQuery("SELECT ".db_seq_nextval('COURSE_PERIODS_SEQ').' AS ID'.FROM_DUAL));
							$fields = 'SYEAR,SCHOOL_ID,COURSE_PERIOD_ID,COURSE_ID,TITLE,FILLED_SEATS,';
							$teacher = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND STAFF_ID='$columns[TEACHER_ID]'"));

							if(!isset($columns['PARENT_ID']))
								$columns['PARENT_ID'] = $id[1]['ID'];

							if(isset($columns['MARKING_PERIOD_ID']))
							{
								if(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='FY')
									$columns['MP'] = 'FY';
								elseif(GetMP($columns['MARKING_PERIOD_ID'],'MP')=='SEM')
									$columns['MP'] = 'SEM';
								else
									$columns['MP'] = 'QTR';

								if(GetMP($columns['MARKING_PERIOD_ID'],'MP')!='FY')
									$mp_title = GetMP($columns['MARKING_PERIOD_ID'],'SHORT_NAME').' - ';
							}

							if($columns['SHORT_NAME'])
								$mp_title .= $columns['SHORT_NAME'].' - ';
							$title = str_replace("'","''",$mp_title.$teacher[1]['FIRST_NAME'].' '.$teacher[1]['MIDDLE_NAME'].' '.$teacher[1]['LAST_NAME']);

							$values = "'".UserSyear()."','".UserSchool()."','".$id[1]['ID']."','$_REQUEST[course_id]','$title','0',";
							$_REQUEST['course_period_id'] = $id[1]['ID'];
							
						}
						//modif Francois: multiple school period for a course period
						elseif($table_name=='COURSE_PERIOD_SCHOOL_PERIODS')
						{
							//modif Francois: add new school period to existing course period
							if (isset($columns['PERIOD_ID']) && empty($columns['PERIOD_ID']))
								break;
								
							$other_school_p = DBGet(DBQuery("SELECT PERIOD_ID,DAYS FROM COURSE_PERIOD_SCHOOL_PERIODS WHERE ".$where['COURSE_PERIODS']."='$_REQUEST[course_period_id]'"), array(), array('PERIOD_ID'));
							
							if (in_array($columns['PERIOD_ID'], $temp_PERIOD_ID) || in_array($columns['PERIOD_ID'], array_keys($other_school_p)))
								break;
								
							$temp_PERIOD_ID[] = $columns['PERIOD_ID'];
							
							$id_school_p = DBGet(DBQuery("SELECT ".db_seq_nextval('COURSE_PERIOD_SCHOOL_PERIODS_SEQ').' AS ID'.FROM_DUAL));
							$period = DBGet(DBQuery("SELECT TITLE FROM SCHOOL_PERIODS WHERE PERIOD_ID='$columns[PERIOD_ID]' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
							$fields = 'COURSE_PERIOD_SCHOOL_PERIODS_ID,COURSE_PERIOD_ID,';
							$values = "'".$id_school_p[1]['ID']."','$_REQUEST[course_period_id]',";

		//modif Francois: days display to locale						
							$nb_days = mb_strlen($columns['DAYS']);
							$columns_DAYS_locale = $nb_days > 1?' '._('Days').' ':($nb_days == 0 ? '' : ' '._('Day').' ');
							for ($i = 0; $i < $nb_days; $i++) {
								$columns_DAYS_locale .= mb_substr($days_convert[mb_substr($columns['DAYS'], $i, 1)],0,3) . '.';
							}
							
							if(mb_strlen($columns['DAYS'])<5)
	//							$mp_title .= $columns['DAYS'].' - ';
								$title = $period[1]['TITLE'].$columns_DAYS_locale.' - '.$title;
							else
								$title = $period[1]['TITLE'].' - '.$title;
							
							DBQuery("UPDATE COURSE_PERIODS SET TITLE='".$title."' WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'");
							
						}

						$go = 0;
						foreach($columns as $column=>$value)
						{
							if(isset($value))
							{
								$fields .= $column.',';
								$values .= "'".$value."',";
								$go = true;
							}
						}
						$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

						if($go)
						{
							DBQuery($sql);				
	//modif Francois: Moodle integrator
							if($table_name=='COURSE_SUBJECTS' || $table_name=='COURSES')
								$moodleError = Moodle($_REQUEST['modname'], 'core_course_create_categories');
							elseif($table_name=='COURSE_PERIODS' && $_REQUEST['moodle_create_course_period'])
							{
								$moodleError = Moodle($_REQUEST['modname'], 'core_course_create_courses');
								$moodleError .= Moodle($_REQUEST['modname'], 'enrol_manual_enrol_users');
							}
						}
					}
				}
				else
					$error = ErrorMessage(array(_('Please fill in the required fields')));
			}
			else
				$error = ErrorMessage(array(_('Please enter a valid Sort Order.')));
		}
	}
	unset($_REQUEST['tables']);
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if($_REQUEST['course_period_id'])
	{
		$table = _('Course Period');
		$sql[] = "UPDATE COURSE_PERIODS SET PARENT_ID=NULL WHERE PARENT_ID='$_REQUEST[course_period_id]'";
		$sql[] = "DELETE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
		$sql[] = "DELETE FROM SCHEDULE WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
		//modif Francois: multiple school period for a course period
		$sql[] = "DELETE FROM COURSE_PERIOD_SCHOOL_PERIODS WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
        $unset = 'course_period_id';
	}
	elseif($_REQUEST['course_id'])
	{
		$table = _('Course');
		$sql[] = "DELETE FROM COURSES WHERE COURSE_ID='$_REQUEST[course_id]'";
		$sql[] = "UPDATE COURSE_PERIODS SET PARENT_ID=NULL WHERE PARENT_ID IN (SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE COURSE_ID='$_REQUEST[course_id]')";
		$sql[] = "DELETE FROM COURSE_PERIODS WHERE COURSE_ID='$_REQUEST[course_id]'";
		$sql[] = "DELETE FROM SCHEDULE WHERE COURSE_ID='$_REQUEST[course_id]'";
		$sql[] = "DELETE FROM SCHEDULE_REQUESTS WHERE COURSE_ID='$_REQUEST[course_id]'";
        $unset = 'course_id';
	}
	elseif($_REQUEST['subject_id'])
	{
		$table = _('Subject');
		$sql[] = "DELETE FROM COURSE_SUBJECTS WHERE SUBJECT_ID='$_REQUEST[subject_id]'";
		$courses = DBGet(DBQuery("SELECT COURSE_ID FROM COURSES WHERE SUBJECT_ID='$_REQUEST[subject_id]'"));
		if(count($courses))
		{
			foreach($courses as $course)
			{
				$sql[] = "DELETE FROM COURSES WHERE COURSE_ID='$course[COURSE_ID]'";
				$sql[] = "UPDATE COURSE_PERIODS SET PARENT_ID=NULL WHERE PARENT_ID IN (SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE COURSE_ID='$course[COURSE_ID]')";
				$sql[] = "DELETE FROM COURSE_PERIODS WHERE COURSE_ID='$course[COURSE_ID]'";
				$sql[] = "DELETE FROM SCHEDULE WHERE COURSE_ID='$course[COURSE_ID]'";
				$sql[] = "DELETE FROM SCHEDULE_REQUESTS WHERE COURSE_ID='$course[COURSE_ID]'";
			}
		}
        $unset = 'subject_id';
	}

	if(DeletePrompt($table))
	{
		foreach($sql as $query)
			DBQuery($query);

//modif Francois: Moodle integrator
		if ($_REQUEST['course_period_id'])
			$moodleError = Moodle($_REQUEST['modname'], 'core_course_delete_courses');
		elseif ($_REQUEST['subject_id'] || $_REQUEST['course_id'])
			$moodleError = Moodle($_REQUEST['modname'], 'core_course_delete_categories');
		
        unset($_REQUEST[$unset]);
		unset($_REQUEST['modfunc']);
	}
}

if((!$_REQUEST['modfunc'] || $_REQUEST['modfunc']=='choose_course') && !$_REQUEST['course_modfunc'])
{
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;
	
//modif Francois: Moodle integrator
	echo $moodleError;
	
	$sql = "SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['last_year']=='true'?UserSyear()-1:UserSyear())."' ORDER BY SORT_ORDER,TITLE";
	$QI = DBQuery($sql);
	$subjects_RET = DBGet($QI);

	if($_REQUEST['modfunc']!='choose_course')
	{
		if(AllowEdit())
		{
			$delete_button = '<script type="text/javascript">var delete_link = document.createElement("a"); delete_link.href = "Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&course_period_id='.$_REQUEST['course_period_id'].'"; delete_link.target = "body";</script>';
			$delete_button .= '<INPUT type="button" value="'._('Delete').'" onClick="javascript:ajaxLink(delete_link);" />';
		}
		// ADDING & EDITING FORM
		if($_REQUEST['course_period_id'])
		{
			if($_REQUEST['course_period_id']!='new')
			{
			//modif Francois: multiple school periods for a course period
				/*$sql = "SELECT PARENT_ID,TITLE,SHORT_NAME,PERIOD_ID,DAYS,
								MP,MARKING_PERIOD_ID,TEACHER_ID,CALENDAR_ID,
								ROOM,TOTAL_SEATS,DOES_ATTENDANCE,
								GRADE_SCALE_ID,DOES_HONOR_ROLL,DOES_CLASS_RANK,
								GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,
								HALF_DAY,DOES_BREAKOFF
						FROM COURSE_PERIODS
						WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";*/
				$sql = "SELECT PARENT_ID,TITLE,SHORT_NAME,
								MP,MARKING_PERIOD_ID,TEACHER_ID,CALENDAR_ID,
								ROOM,TOTAL_SEATS,DOES_ATTENDANCE,
								GRADE_SCALE_ID,DOES_HONOR_ROLL,DOES_CLASS_RANK,
								GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,
								HALF_DAY,DOES_BREAKOFF
						FROM COURSE_PERIODS
						WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
				$sql2 = "SELECT COURSE_PERIOD_SCHOOL_PERIODS_ID, PERIOD_ID, DAYS
						FROM COURSE_PERIOD_SCHOOL_PERIODS
						WHERE COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$RET = $RET[1];
				$title = $RET['TITLE'];
				$QI2 = DBQuery($sql2);
				$RET2 = DBGet($QI2);
				$new = false;
			}
			else
			{
				$sql = "SELECT TITLE
						FROM COURSES
						WHERE COURSE_ID='$_REQUEST[course_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$title = $RET[1]['TITLE'].' - '._('New Course Period');
				unset($delete_button);
				unset($RET);
				$checked = 'CHECKED';
				$new = true;
			}

			echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&course_period_id='.$_REQUEST['course_period_id'].'" method="POST">';
			DrawHeader($title,$delete_button.SubmitButton(_('Save')));
			
//modif Francois: Moodle integrator
			//propose to create course period in Moodle: if 1) this is a creation, 2) this is an already created course period but not in Moodle yet
			//AND 3) if the course is in Moodle
			
			if (MOODLE_INTEGRATOR && AllowEdit())
			{
				//2) verifiy if the student is in Moodle:
				$old_course_period_in_moodle = false;
				if ($_REQUEST['course_period_id'] != 'new')
					$old_course_period_in_moodle = DBGet(DBQuery("SELECT 1 FROM moodlexrosario WHERE rosario_id='".$_REQUEST['course_period_id']."' AND \"column\"='course_period_id'"));
					
				//3) verifiy if the course is in Moodle:
				$course_in_moodle = false;
				if ($_REQUEST['course_id'] != 'new')
					$course_in_moodle = DBGet(DBQuery("SELECT 1 FROM moodlexrosario WHERE rosario_id='".$_REQUEST['course_id']."' AND \"column\"='course_id'"));
				
				if ($course_in_moodle && ($_REQUEST['course_period_id']=='new' || !$old_course_period_in_moodle))
					DrawHeader('<label>'.CheckBoxOnclick('moodle_create_course_period').'&nbsp;'._('Create Course Period in Moodle').'</label>');
			}
			
			$header .= '<TABLE class="width-100p cellpadding-3" id="coursesTable">';
			$header .= '<TR class="st">';

//modif Francois: Moodle integrator
			$header .= '<TD>' . TextInput($RET['SHORT_NAME'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][SHORT_NAME]',($RET['SHORT_NAME']?'':'<span style="color:red">')._('Short Name').($RET['SHORT_NAME']?'':'</span>'),'required', ($_REQUEST['moodle_create_course_period'] ? false : true)) . '</TD>';

			$teachers_RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME FROM STAFF WHERE (SCHOOLS IS NULL OR STRPOS(SCHOOLS,',".UserSchool().",')>0) AND SYEAR='".UserSyear()."' AND PROFILE='teacher' ORDER BY LAST_NAME,FIRST_NAME"));
			if(count($teachers_RET))
			{
				foreach($teachers_RET as $teacher)
					$teachers[$teacher['STAFF_ID']] = $teacher['LAST_NAME'].', '.$teacher['FIRST_NAME'].' '.$teacher['MIDDLE_NAME'];
			}
//modif Francois: Moodle integrator
			$header .= '<TD>' . SelectInput($RET['TEACHER_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][TEACHER_ID]',($RET['TEACHER_ID']?'':'<span style="color:red">')._('Teacher').($RET['TEACHER_ID']?'':'</span>'),$teachers, ($_REQUEST['moodle_create_course_period'] ? false : true), '', ($_REQUEST['moodle_create_course_period'] ? false : true)) . '</TD>';

			$header .= '<TD>' . TextInput($RET['ROOM'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][ROOM]',_('Room')) . '</TD>';

			$periods_RET = DBGet(DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER,TITLE"));
			if(count($periods_RET))
			{
				foreach($periods_RET as $period)
					$periods[$period['PERIOD_ID']] = $period['TITLE'];
			}
			//$header .= '<TD>' . SelectInput($RET['MP'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][MP]','Length',array('FY'=>'Full Year','SEM'=>'Semester','QTR'=>'Marking Period')) . '</TD>';
			$mp_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,SHORT_NAME,".db_case(array('MP',"'FY'","'0'","'SEM'","'1'","'QTR'","'2'"))." AS TBL FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TBL,SORT_ORDER"));
			unset($options);

			if(count($mp_RET))
			{
				foreach($mp_RET as $mp)
					$options[$mp['MARKING_PERIOD_ID']] = $mp['SHORT_NAME'];
			}
//modif Francois: Moodle integrator
			$header .= '<TD>' . SelectInput($RET['MARKING_PERIOD_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][MARKING_PERIOD_ID]',($RET['MARKING_PERIOD_ID']?'':'<span style="color:red">')._('Marking Period').($RET['MARKING_PERIOD_ID']?'':'</span>'),$options,false, '', ($_REQUEST['moodle_create_course_period'] ? false : true)) . '</TD>';
			$header .= '<TD>' . TextInput($RET['TOTAL_SEATS'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][TOTAL_SEATS]',_('Seats'),'size=4') . '</TD>';

			$header .= '</TR>';

			$days = array('M','T','W','H','F','S','U');
			//modif Francois: days numbered
			if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
				$days = array_slice($days, 0, SchoolInfo('NUMBER_DAYS_ROTATION'));
				
			//modif Francois: multiple school periods for a course period
			
			$i = 0;
			do 
			{
				$i++;
				//modif Francois: add new school period to existing course period
				if (!$new && $i > count($RET2))
				{
					$new = true;
					$not_really_new = true;
					unset($school_period);
				}
				if (!$new)
					$school_period = $RET2[$i];
				else
					$school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] = 'new' . $i;
				$header .= '<TR id="schoolPeriod'.$i.'" class="st">';
				//modif Francois: existing school period not modifiable
				if (!$new)
					$header .= '<TD>' . $periods[$school_period['PERIOD_ID']] . '<BR /><span class="legend-gray">' ._('Period'). '</span></TD>';
				else
					$header .= '<TD>' . SelectInput($school_period['PERIOD_ID'],'tables[COURSE_PERIOD_SCHOOL_PERIODS]['.$school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'][PERIOD_ID]',($school_period['PERIOD_ID']?'':'<span style="color:red">')._('Period').($school_period['PERIOD_ID']?'':'</span>'),$periods) . '</TD>';
				$header .= '<TD>';
				if($new==false && Preferences('HIDDEN')=='Y')
				{
					$header .= '<DIV id="divtables[COURSE_PERIOD_SCHOOL_PERIODS]['.$school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'][DAYS]"><div class="onclick" onclick=\'addHTML("';
					$header .= str_replace('"','\"', '<input type="hidden" name="tables[COURSE_PERIOD_SCHOOL_PERIODS]['.$school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'][PERIOD_ID]" value="'.$school_period['PERIOD_ID'].'" />');
				}
				$header .= '<TABLE><TR>';

				foreach($days as $day)
				{
					if(mb_strpos($school_period['DAYS'],$day)!==false || ($new && $day!='S' && $day!='U'))
						$value = 'Y';
					else
						$value = '';

	//				$header .= '<TD>'.str_replace('"','\"',CheckboxInput($value,'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DAYS]['.$day.']',($day=='U'?'S':$day),$checked,false,'','',false)).'</TD>';
					$header_temp = '<TD>'.CheckboxInput($value,'tables[COURSE_PERIOD_SCHOOL_PERIODS]['.$school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'][DAYS]['.$day.']',mb_substr($days_convert[$day],0,3),$checked,$new,'','',false).'</TD>';
					if($new==false && Preferences('HIDDEN')=='Y')
						$header .= str_replace('"','\"',$header_temp);
					else
						$header .= $header_temp;
				}
				$header .= '</TR></TABLE>';
				if($new==false && Preferences('HIDDEN')=='Y')
				{
	//				$header .= '","days",true);\'><span style=\'border-bottom-style:dotted;border-bottom-width:1;border-bottom-color:'.Preferences('TITLES').';\'>'.$RET['DAYS'].'</span></div></DIV>';
					//modif Francois: days display to locale						
					$school_period_locale = '';
					for ($j = 0; $j < mb_strlen($school_period['DAYS']); $j++) {
						$school_period_locale .= mb_substr($days_convert[mb_substr($school_period['DAYS'], $j, 1)],0,3) . '.&nbsp;';
					}
					$school_period['DAYS'] = $school_period_locale;
					$header .= '","divtables[COURSE_PERIOD_SCHOOL_PERIODS]['.$school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'][DAYS]",true);\'><span class="underline-dots">'.$school_period['DAYS'].'</span></div></DIV>';
				}

				$header .= '<span class="'.($school_period['DAYS']?'legend-gray':'legend-red').'">'._('Meeting Days').'</span>';
				$header .= '</TD>';
				$header .= '</TR>';
				
				if ($not_really_new)
					$new = false;
				if ($new)
					break;
			} while ( $i <= count($RET2) );
			
			$header .= '<TR class="st"><TD><a href="#" onclick="'.($new ? 'newSchoolPeriod()' : 'document.getElementById(\'schoolPeriod\'+'.$i.').style.display=\'table-row\';').'"><img src="assets/add_button.gif" width="18" style="vetical-align:middle" /> '._('New Period').'</a></TD></TR>';
			if (!$new)
				$header .= '<script type="text/javascript">document.getElementById(\'schoolPeriod\'+'.$i.').style.display = "none";</script>';
			?>
			<script type="text/javascript">
				var nbSchoolPeriods = <?php echo $i; ?>;
				function newSchoolPeriod()
				{
					var table = document.getElementById('coursesTable');
					row = table.insertRow(1+nbSchoolPeriods);
					// insert table cells to the new row
					var tr = document.getElementById('schoolPeriod'+nbSchoolPeriods);
					row.setAttribute('id', 'schoolPeriod'+(nbSchoolPeriods+1));
					row.setAttribute('class', 'st');					
					for (i = 0; i < 2; i++) {
						createCell(row.insertCell(i), tr, i, nbSchoolPeriods+1);
					}
					nbSchoolPeriods ++;
				}
				// fill the cells
				function createCell(cell, tr, i, newId) {
					cell.innerHTML = tr.cells[i].innerHTML;
					reg = new RegExp('new' + (newId-1),'g'); //g for global string
					cell.innerHTML = cell.innerHTML.replace(reg, 'new'+newId);
				}
			</script>
			<?php
			$header .= '<TR class="st">';

			$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'"._('Attendance')."' AS TITLE UNION SELECT ID,TITLE FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));

			$header .= '<TD>';
			if($new==false && Preferences('HIDDEN')=='Y')
				$header .= '<DIV id="attendance"><div class="onclick" onclick=\'addHTML("';
			$header .= '<TABLE><TR>';
			$top = '<TABLE><TR>';
			foreach($categories_RET as $value)
			{
				if(mb_strpos($RET['DOES_ATTENDANCE'],','.$value['ID'].',')!==false)
				{
					$val = 'Y';
					$img = 'check';
				}
				else
				{
					$val = '';
					$img = 'x';
				}

				$header_temp = '<TD>'.CheckboxInput($val,'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_ATTENDANCE]['.$value['ID'].']',$value['TITLE'],$checked,false,'','',false).'</TD>';
				if($new==false && Preferences('HIDDEN')=='Y')
					$header .= str_replace('"','\"',$header_temp);
				else
					$header .= $header_temp;
				
				$top .= '<TD><span class="underline-dots"><IMG SRC="assets/'.$img.'.png" height="15"></span>&nbsp;'.$value['TITLE'];
			}
			$header .= '</TR></TABLE>';
			$top .= '</TR></TABLE>';
			if($new==false && Preferences('HIDDEN')=='Y')
				$header .= '","attendance",true);\'>'.$top.'</div></DIV>';
			$header .= '<span class="legend-gray">'._('Takes Attendance').'</span>';
			$header .= '</TD>';

			$header .= '<TD>' . CheckboxInput($RET['DOES_HONOR_ROLL'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_HONOR_ROLL]',_('Affects Honor Roll'),$checked,$new,'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
			$header .= '<TD>' . CheckboxInput($RET['DOES_CLASS_RANK'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_CLASS_RANK]',_('Affects Class Rank'),$checked,$new,'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
			$header .= '<TD>' . SelectInput($RET['GENDER_RESTRICTION'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][GENDER_RESTRICTION]',_('Gender Restriction'),array('N'=>_('None'),'M'=>_('Male'),'F'=>_('Female')),false) . '</TD>';

			$options_RET = DBGet(DBQuery("SELECT TITLE,ID FROM REPORT_CARD_GRADE_SCALES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
			$options = array();
			foreach($options_RET as $option)
				$options[$option['ID']] = $option['TITLE'];
			$header .= '<TD>' . SelectInput($RET['GRADE_SCALE_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][GRADE_SCALE_ID]',_('Grading Scale'),$options,_('Not Graded')) . '</TD>';
            //bjj Added to handle credits
            $header .= '<TD>' . TextInput(sprintf('%0.3f',(is_null($RET['CREDITS']) ? '1' : $RET['CREDITS'])),'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][CREDITS]',_('Credits'),'size=4',(is_null($RET['CREDITS']) ? false : true)) . '</TD>'; 
			$options_RET = DBGet(DBQuery("SELECT TITLE,CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY DEFAULT_CALENDAR ASC,TITLE"));
			$options = array();
			foreach($options_RET as $option)
				$options[$option['CALENDAR_ID']] = $option['TITLE'];
			$header .= '<TD>' . SelectInput($RET['CALENDAR_ID'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][CALENDAR_ID]',($RET['CALENDAR_ID']?'':'<span style="color:red">')._('Calendar').($RET['CALENDAR_ID']?'':'</span>'),$options,false) . '</TD>';

			//BJJ Parent course select was here...  moved it down

			$header .= '</TR>';

			$header .= '<TR class="st">';

			//$header .= '<TD>' . CheckboxInput($RET['HOUSE_RESTRICTION'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][HOUSE_RESTRICTION]','Restricts House','',$new) . '</TD>';
			$header .= '<TD>' . CheckboxInput($RET['HALF_DAY'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][HALF_DAY]',_('Half Day'),$checked,$new,'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
			$header .= '<TD>' . CheckboxInput($RET['DOES_BREAKOFF'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][DOES_BREAKOFF]',_('Allow Teacher Gradescale'),$checked,$new,'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
            //BJJ added cells to place parent selection in the last column
            $header .= '<TD colspan= 4>&nbsp;</td>';
            
            if($_REQUEST['course_period_id']!='new' && $RET['PARENT_ID']!=$_REQUEST['course_period_id'])
            {
                $parent = DBGet(DBQuery("SELECT cp.TITLE as CP_TITLE,c.TITLE AS C_TITLE FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$RET['PARENT_ID']."'"));
                $parent = $parent[1]['C_TITLE'].': '.$parent[1]['CP_TITLE'];
            }
            elseif($_REQUEST['course_period_id']!='new')
            {
                $children = DBGet(DBQuery("SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE PARENT_ID='".$_REQUEST['course_period_id']."' AND COURSE_PERIOD_ID!='".$_REQUEST['course_period_id']."'"));
                if(count($children))
                    $parent = _('N/A');
                else
                    $parent = _('None');
            }

            $header .= '<TD colspan="2"><DIV id=course_div>'.$parent.'</DIV> '.($parent!=_('N/A')?'<A HREF="#" onclick=\'window.open("Modules.php?modname='.$_REQUEST['modname'].'&modfunc=choose_course","","scrollbars=yes,resizable=yes,width=800,height=400");\'>'._('Choose').'</A><BR />':'').'<span class="legend-gray">'._('Parent Course Period').'</span></TD>';

			$header .= '</TR>';
			$header .= '</TABLE>';
			DrawHeader($header);
			//echo '</FORM>';
		}
		elseif($_REQUEST['course_id'])
		{
			if($_REQUEST['course_id']!='new')
			{
//modif Francois: add Credit Hours to Courses
				//$sql = "SELECT TITLE,SHORT_NAME,GRADE_LEVEL
				$sql = "SELECT TITLE,SHORT_NAME,GRADE_LEVEL,CREDIT_HOURS
						FROM COURSES
						WHERE COURSE_ID='$_REQUEST[course_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$RET = $RET[1];
				$title = $RET['TITLE'];
			}
			else
			{
				$sql = "SELECT TITLE
						FROM COURSE_SUBJECTS
						WHERE SUBJECT_ID='$_REQUEST[subject_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$title = $RET[1]['TITLE'].' - '._('New Course');
				unset($delete_button);
				unset($RET);
			}

			echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&subject_id='.$_REQUEST[subject_id].'&course_id='.$_REQUEST[course_id].'" method="POST">';
			DrawHeader($title,$delete_button.SubmitButton(_('Save')));
			$header .= '<TABLE class="width-100p cellpadding-3">';
			$header .= '<TR>';

//modif Francois: title required
			$header .= '<TD>' . TextInput($RET['TITLE'],'tables[COURSES]['.$_REQUEST['course_id'].'][TITLE]',(!$RET['TITLE']?'<span style="color:red">':'')._('Title').(!$RET['TITLE']?'</span>':''), 'required') . '</TD>';
			$header .= '<TD>' . TextInput($RET['SHORT_NAME'],'tables[COURSES]['.$_REQUEST['course_id'].'][SHORT_NAME]',_('Short Name')) . '</TD>';
//modif Francois: add Credit Hours to Courses
			$header .= '<TD>' . TextInput($RET['CREDIT_HOURS'],'tables[COURSES]['.$_REQUEST['course_id'].'][CREDIT_HOURS]',_('Credit Hours')) . '</TD>';
			if($_REQUEST['modfunc']!='choose_course')
			{
				foreach($subjects_RET as $type)
					$options[$type['SUBJECT_ID']] = $type['TITLE'];

				$header .= '<TD>' . SelectInput($RET['SUBJECT_ID']?$RET['SUBJECT_ID']:$_REQUEST['subject_id'],'tables[COURSES]['.$_REQUEST['course_id'].'][SUBJECT_ID]',_('Subject'),$options,false) . '</TD>';
			}
			$header .= '</TR>';
			$header .= '</TABLE>';
			DrawHeader($header);
			echo '</FORM>';
		}
		elseif($_REQUEST['subject_id'])
		{
			if($_REQUEST['subject_id']!='new')
			{
				$sql = "SELECT TITLE,SORT_ORDER
						FROM COURSE_SUBJECTS
						WHERE SUBJECT_ID='$_REQUEST[subject_id]'";
				$QI = DBQuery($sql);
				$RET = DBGet($QI);
				$RET = $RET[1];
				$title = $RET['TITLE'];
			}
			else
			{
				$title = _('New Subject');
				unset($delete_button);
			}

			echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'" method="POST">';
			DrawHeader($title,$delete_button.SubmitButton(_('Save')));
			$header .= '<TABLE class="width-100p cellpadding-3">';
			$header .= '<TR>';

//modif Francois: title required
			$header .= '<TD>' . TextInput($RET['TITLE'],'tables[COURSE_SUBJECTS]['.$_REQUEST['subject_id'].'][TITLE]',(!$RET['TITLE']?'<span style="color:red">':'')._('Title').(!$RET['TITLE']?'</span>':''), 'required') . '</TD>';
			$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables[COURSE_SUBJECTS]['.$_REQUEST['subject_id'].'][SORT_ORDER]',_('Sort Order')) . '</TD>';

			$header .= '</TR>';
			$header .= '</TABLE>';
			DrawHeader($header);
			echo '</FORM>';
		}
	}

	// DISPLAY THE MENU
	if($_REQUEST['modfunc']=='choose_course')
	{
		if($_REQUEST['modname']=='Scheduling/Schedule.php')
		{
//modif Francois: add translation
			echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
			DrawHeader(_('Choose a').' '.($_REQUEST['subject_id']?($_REQUEST['course_id']?($_REQUEST['last_year']=='true'?_('Last Year Course Period'):_('Course Period')):($_REQUEST['last_year']=='true'?_('Last Year Course'):_('Course'))):($_REQUEST['last_year']=='true'?_('Last Year Subject'):_('Subject'))),_('Enrollment Date').' '.PrepareDate($date,'_date',false,array('submit'=>true)),'');
			DrawHeader('<label>'.CheckBoxOnclick('include_child_mps').' '._('Offer Enrollment in Child Marking Periods').'</label>');
			echo '</FORM>';
		}
		else
			DrawHeader(_('Choose a').' '.($_REQUEST['subject_id']?($_REQUEST['course_id']?($_REQUEST['last_year']=='true'?_('Last Year Course Period'):_('Course Period')):($_REQUEST['last_year']=='true'?_('Last Year Course'):_('Course'))):($_REQUEST['last_year']=='true'?_('Last Year Subject'):_('Subject'))));
	}
	elseif(!$_REQUEST['subject_id'])
		DrawHeader(_('Courses'));
	DrawHeader('','<A HREF="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&course_modfunc=search&last_year='.$_REQUEST['last_year'].($_REQUEST['modfunc']=='choose_course'&&$_REQUEST['modname']=='Scheduling/Schedule.php'?'&include_child_mps='.$_REQUEST['include_child_mps'].'&year_date='.$_REQUEST['year_date'].'&month_date='.$_REQUEST['month_date'].'&day_date='.$_REQUEST['day_date']:'').'">'._('Search').'</A>&nbsp;');

	if(count($subjects_RET))
	{
		if($_REQUEST['subject_id'])
		{
			foreach($subjects_RET as $key=>$value)
			{
				if($value['SUBJECT_ID']==$_REQUEST['subject_id'])
					$subjects_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	$columns = array('TITLE'=>_('Subject'));
	$link = array();
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]";
	$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID');
	if($_REQUEST['modfunc']=='choose_course')
		$link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
	else
		$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=new";

	echo '<div class="st">';
	ListOutput($subjects_RET,$columns,'Subject','Subjects',$link,array(),$LO_options);
	echo '</div>';

	if($_REQUEST['subject_id'] && $_REQUEST['subject_id']!='new')
	{
		$sql = "SELECT COURSE_ID,TITLE FROM COURSES WHERE SUBJECT_ID='$_REQUEST[subject_id]' ORDER BY TITLE";
		$QI = DBQuery($sql);
		$courses_RET = DBGet($QI);

		if(count($courses_RET))
		{
			if($_REQUEST['course_id'])
			{
				foreach($courses_RET as $key=>$value)
				{
					if($value['COURSE_ID']==$_REQUEST['course_id'])
						$courses_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
				}
			}
		}

		$columns = array('TITLE'=>_('Course'));
		$link = array();
		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]";
		$link['TITLE']['variables'] = array('course_id'=>'COURSE_ID');
		if($_REQUEST['modfunc']=='choose_course')
			$link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&last_year=$_REQUEST[last_year]".($_REQUEST['modname']=='Scheduling/Schedule.php'?"&include_child_mps=$_REQUEST[include_child_mps]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]":'');
		else
			$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=new";

		echo '<div class="st">';
		ListOutput($courses_RET,$columns,'Course','Courses',$link,array(),$LO_options);
		echo '</div>';

		if($_REQUEST['course_id'] && $_REQUEST['course_id']!='new')
		{
                //modif Francois: multiple school periods for a course period
				//$periods_RET = DBGet(DBQuery("SELECT '$_REQUEST[subject_id]' AS SUBJECT_ID,COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,TOTAL_SEATS AS AVAILABLE_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='$_REQUEST[course_id]' ".($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php'?" AND '$date'<=(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE SYEAR=cp.SYEAR AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)":'')." ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID),TITLE"));
                $periods_RET = DBGet(DBQuery("SELECT '$_REQUEST[subject_id]' AS SUBJECT_ID,COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,TOTAL_SEATS AS AVAILABLE_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='$_REQUEST[course_id]' ".($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php'?" AND '$date'<=(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE SYEAR=cp.SYEAR AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)":'')." ORDER BY SHORT_NAME,TITLE"));

                if($_REQUEST['modname']=='Scheduling/Schedule.php')
                    calcSeats1($periods_RET,$date);

                if(count($periods_RET))
                {
                    if($_REQUEST['course_period_id'])
                    {
                        foreach($periods_RET as $key=>$value)
                        {
                            if($value['COURSE_PERIOD_ID']==$_REQUEST['course_period_id'])
                                $periods_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
                        }
                    }
                }

                $columns = array('TITLE'=>_('Course Period'));
                $link = array();
                if($_REQUEST['modname']!='Scheduling/Schedule.php' || ($_REQUEST['modname']=='Scheduling/Schedule.php' && !$_REQUEST['include_child_mps']))
                {
                    $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]";
                    $link['TITLE']['variables'] = array('course_period_id'=>'COURSE_PERIOD_ID','course_marking_period_id'=>'MARKING_PERIOD_ID');
                    if($_REQUEST['modfunc']=='choose_course')
                        $link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]&student_id=$_REQUEST[student_id]&last_year=$_REQUEST[last_year]";
                    else
                        $link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]&course_period_id=new";
                }
                if($_REQUEST['modname']=='Scheduling/Schedule.php')
                    $columns += array('AVAILABLE_SEATS'=>($_REQUEST['include_child_mps']?_('MP').'('._('Available Seats').')':_('Available Seats')));

				echo '<div class="st">';
                ListOutput($periods_RET,$columns,'Course Period','Course Periods',$link,array(),$LO_options);
                echo '</div>';
		}
	}
}

if($_REQUEST['modname']=='Scheduling/Courses.php' && $_REQUEST['modfunc']=='choose_course' && $_REQUEST['course_period_id'])
{
	$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'"));
	$course_title = str_replace(array("'",'"'),array('&#39;','&quot;'),$course_title[1]['TITLE']) . '<INPUT type="hidden" name="tables[parent_id]" value="'.$_REQUEST['course_period_id'].'">';
	$course_title = str_replace('"','\"',$course_title);

	echo '<script type="text/javascript">opener.document.getElementById("'.($_REQUEST['last_year']=='true'?'ly_':'').'course_div").innerHTML = "'.$course_title.'"; window.close();</script>';
}

function calcSeats1(&$periods,$date)
{
	$date_time = strtotime($date);
	foreach($periods as $key=>$period)
	{
		if($_REQUEST['include_child_mps'])
		{
			$mps = GetChildrenMP($period['MP'],$period['MARKING_PERIOD_ID']);
			if($period['MP']=='FY' || $period['MP']=='SEM')
				$mps = "'$period[MARKING_PERIOD_ID]'".($mps?','.$mps:'');
		}
		else
			$mps = "'".$period['MARKING_PERIOD_ID']."'";
		$periods[$key]['AVAILABLE_SEATS'] = '';
		foreach(explode(',',$mps) as $mp)
		{
			$mp = trim($mp,"'");
			if(strtotime(GetMP($mp,'END_DATE'))>=$date_time)
			{
				$link = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&subject_id=$period[SUBJECT_ID]&course_id=$period[COURSE_ID]";
				$link .= "&last_year=$_REQUEST[last_year]&year_date=$_REQUEST[year_date]&month_date=$_REQUEST[month_date]&day_date=$_REQUEST[day_date]";
				$link .= "&course_period_id=$period[COURSE_PERIOD_ID]&course_marking_period_id=$mp";
				if($period['AVAILABLE_SEATS'])
				{
					$seats = DBGet(DBQuery("SELECT max((SELECT count(1) FROM SCHEDULE ss JOIN STUDENT_ENROLLMENT sem ON (sem.STUDENT_ID=ss.STUDENT_ID AND sem.SYEAR=ss.SYEAR) WHERE ss.COURSE_PERIOD_ID='$period[COURSE_PERIOD_ID]' AND (ss.MARKING_PERIOD_ID='$mp' OR ss.MARKING_PERIOD_ID IN (".GetAllMP(GetMP($mp,'MP'),$mp).")) AND (ac.SCHOOL_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ac.SCHOOL_DATE<=ss.END_DATE)) AND (ac.SCHOOL_DATE>=sem.START_DATE AND (sem.END_DATE IS NULL OR ac.SCHOOL_DATE<=sem.END_DATE)))) AS FILLED_SEATS FROM ATTENDANCE_CALENDAR ac WHERE ac.CALENDAR_ID='$period[CALENDAR_ID]' AND ac.SCHOOL_DATE BETWEEN '$date' AND '".GetMP($mp,'END_DATE')."'"));
					if($seats[1]['FILLED_SEATS']!='')
						if($_REQUEST['include_child_mps'])
							$periods[$key]['AVAILABLE_SEATS'] .= '<A href='.$link.'>'.(GetMP($mp,'SHORT_NAME')?GetMP($mp,'SHORT_NAME'):GetMP($mp)).'('.($period['AVAILABLE_SEATS']-$seats[1]['FILLED_SEATS']).')</A> | ';
						else
							$periods[$key]['AVAILABLE_SEATS'] = $period['AVAILABLE_SEATS']-$seats[1]['FILLED_SEATS'];
				}
				else
					if($_REQUEST['include_child_mps'])
						$periods[$key]['AVAILABLE_SEATS'] .= '<A href='.$link.'>'.(GetMP($mp,'SHORT_NAME')?GetMP($mp,'SHORT_NAME'):GetMP($mp)).'</A> | ';
					else
						$periods[$key]['AVAILABLE_SEATS'] = _('N/A');
			}
		}
		if($_REQUEST['include_child_mps'])
			$periods[$key]['AVAILABLE_SEATS'] = mb_substr($periods[$key]['AVAILABLE_SEATS'],0,-3);
	}
}
?>