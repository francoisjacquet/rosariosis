<?php
// TABBED FY,SEM,QTR
// REPLACE DBDate() & date() WITH USER ENTERED VALUES
// ERROR HANDLING

DrawHeader(ProgramTitle());

if($_REQUEST['month_date'] && $_REQUEST['day_date'] && $_REQUEST['year_date'])
	while(!VerifyDate($date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date']))
		$_REQUEST['day_date']--;
else
{
	$min_date = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS MIN_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	if($min_date[1]['MIN_DATE'] && DBDate('postgres')<$min_date[1]['MIN_DATE'])
	{
		$date = $min_date[1]['MIN_DATE'];
		$_REQUEST['day_date'] = date('d',strtotime($date));
		$_REQUEST['month_date'] = mb_strtoupper(date('M',strtotime($date)));
		$_REQUEST['year_date'] = date('y',strtotime($date));
	}
	else
	{
		$_REQUEST['day_date'] = date('d');
		$_REQUEST['month_date'] = mb_strtoupper(date('M'));
		$_REQUEST['year_date'] = date('Y');
		$date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date'];
	}
}
unset($_SESSION['_REQUEST_vars']['modfunc']);

Widgets('course');
Widgets('request');

Search('student_id',$extra);

if($_REQUEST['month_schedule'] && $_POST['month_schedule'])
{
	foreach($_REQUEST['month_schedule'] as $id=>$start_dates)
	foreach($start_dates as $start_date=>$columns)
	{
		foreach($columns as $column=>$value)
		{
			$_REQUEST['schedule'][$id][$start_date][$column] = $_REQUEST['day_schedule'][$id][$start_date][$column].'-'.$value.'-'.$_REQUEST['year_schedule'][$id][$start_date][$column];
			//modif Francois: bugfix SQL bug when incomplete or non-existent date
			//if($_REQUEST['schedule'][$id][$start_date][$column]=='--')
			if(mb_strlen($_REQUEST['schedule'][$id][$start_date][$column]) < 11)
				$_REQUEST['schedule'][$id][$start_date][$column] = '';
			else
			{
				while(!VerifyDate($_REQUEST['schedule'][$id][$start_date][$column]))
				{
					$_REQUEST['day_schedule'][$id][$start_date][$column]--;
					$_REQUEST['schedule'][$id][$start_date][$column] = $_REQUEST['day_schedule'][$id][$start_date][$column].'-'.$value.'-'.$_REQUEST['year_schedule'][$id][$start_date][$column];
				}
			}
		}
	}
	unset($_REQUEST['month_schedule']);
	unset($_REQUEST['day_schedule']);
	unset($_REQUEST['year_schedule']);
	unset($_SESSION['_REQUEST_vars']['month_schedule']);
	unset($_SESSION['_REQUEST_vars']['day_schedule']);
	unset($_SESSION['_REQUEST_vars']['year_schedule']);
	$_POST['schedule'] = $_REQUEST['schedule'];
}

if($_REQUEST['schedule'] && $_POST['schedule'] && AllowEdit())
{
	foreach($_REQUEST['schedule'] as $course_period_id=>$start_dates)
	foreach($start_dates as $start_date=>$columns)
	{
		$sql = "UPDATE SCHEDULE SET ";

		foreach($columns as $column=>$value)
		{
			$sql .= $column."='".$value."',";
		}
		$sql = mb_substr($sql,0,-1) . " WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND START_DATE='".$start_date."'";
		DBQuery($sql);

		if($columns['START_DATE'] || $columns['END_DATE'])
		{
			$start_end_RET = DBGet(DBQuery("SELECT START_DATE,END_DATE FROM SCHEDULE WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND END_DATE<START_DATE"));
			// User should be asked if he wants absences and grades to be deleted
			if(count($start_end_RET))
			{
				DBQuery("DELETE FROM SCHEDULE WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."'");
				DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."'");
				DBQuery("DELETE FROM STUDENT_REPORT_CARD_GRADES WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."'");
				DBQuery("DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."'");
				DBQuery("DELETE FROM ATTENDANCE_PERIOD WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."'");
			}
			else
				DBQuery("DELETE FROM ATTENDANCE_PERIOD WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND (".($columns['START_DATE']?"SCHOOL_DATE<'".$columns['START_DATE']."'":'FALSE').' OR '.($columns['END_DATE']?"SCHOOL_DATE>'".$columns['END_DATE']."'":'FALSE').")");
		}
	}
	unset($_SESSION['_REQUEST_vars']['schedule']);
	unset($_REQUEST['schedule']);
}

if(UserStudentID() && $_REQUEST['modfunc']!='choose_course')
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=modify" METHOD="POST">';
//modif Francois: add label on checkbox
	DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)),SubmitButton(_('Save')));
	DrawHeader('<label>'.CheckBoxOnclick('include_inactive').'&nbsp;'._('Include Inactive Courses').(AllowEdit()?'</label> &nbsp;<label>'.CheckBoxOnclick('include_seats').' '._('Show Available Seats').'</label>':''));
	//modif Francois: remove ProgramLink function
	//DrawHeader(ProgramLink('Scheduling/PrintSchedules.php',_('Print Schedule'),'&modfunc=save&st_arr[]='.UserStudentID().'&_ROSARIO_PDF=true'));
	
	//modif Francois: add Horizontal format option
	$printSchedulesLinkhref = 'Modules.php?modname=Scheduling/PrintSchedules.php&modfunc=save&st_arr[]='.UserStudentID().'&_ROSARIO_PDF=true&schedule_table=Yes';
	?>
	<script type="text/javascript">
		function horizontalFormatSwitch()
		{
			if (document.getElementById("horizontalFormat").checked==true)
				document.getElementById("printSchedulesLink").href=document.getElementById("printSchedulesLink").href+'&horizontalFormat';
			else
				document.getElementById("printSchedulesLink").href=document.getElementById("printSchedulesLink").href.replace('&horizontalFormat','');		
		}
	</script>
	<?php
	//modif Francois: add schedule table
	?>
	<script type="text/javascript">
		function timeTableSwitch()
		{
			if (document.getElementById("schedule_table").checked==true)
				document.getElementById("printSchedulesLink").href=document.getElementById("printSchedulesLink").href.replace('Yes','No');
			else
				document.getElementById("printSchedulesLink").href=document.getElementById("printSchedulesLink").href.replace('No','Yes');		
		}
	</script>
	<?php
	DrawHeader((AllowUse('Scheduling/PrintSchedules.php') ? '<A href="'.$printSchedulesLinkhref.'" target="_blank" id="printSchedulesLink">' : '')._('Print Schedule').(AllowUse('Scheduling/PrintSchedules.php') ? '</A>' : '') . (AllowUse('Scheduling/PrintSchedules.php') ? ' &nbsp;<label><input type="checkbox" id="horizontalFormat" name="horizontalFormat" value="Y" onchange="horizontalFormatSwitch();" /> '._('Horizontal Format').'</label>'.'<label><input name="schedule_table" type="radio" value="Yes" checked onchange="timeTableSwitch();" />&nbsp;'._('Table').'</label> '.'<label><input name="schedule_table" id="schedule_table" type="radio" value="No" onchange="timeTableSwitch();" />&nbsp;'._('List').'</label>' : ''));
	
	/*
	$schedule_fields_RET = DBGet(DBQuery("SELECT cf.TITLE,s.CUSTOM_71 FROM CUSTOM_FIELDS cf,STUDENTS s WHERE s.STUDENT_ID='".UserStudentID()."' AND cf.ID='71'"));
	if($schedule_fields_RET[1]['TITLE']=='Team')
		DrawHeader('<span style="color:gray"><b>'.$schedule_fields_RET[1]['TITLE'].': </b></span>'.$schedule_fields_RET[1]['CUSTOM_71']);
	*/

	// get the fy marking period id, there should be exactly one fy marking period
	$fy_id = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	$fy_id = $fy_id[1]['MARKING_PERIOD_ID'];

	//modif Francois: multiple school periods for a course period
	/*$sql = "SELECT
				s.COURSE_ID,s.COURSE_PERIOD_ID,
				s.MARKING_PERIOD_ID,s.START_DATE,s.END_DATE,
				extract(EPOCH FROM s.START_DATE) AS START_EPOCH,extract(EPOCH FROM s.END_DATE) AS END_EPOCH,sp.PERIOD_ID,
				cp.PERIOD_ID,cp.MARKING_PERIOD_ID AS COURSE_MARKING_PERIOD_ID,cp.MP,cp.CALENDAR_ID,cp.TOTAL_SEATS,
				c.TITLE,cp.COURSE_PERIOD_ID AS PERIOD_PULLDOWN,
				s.STUDENT_ID,ROOM,DAYS,SCHEDULER_LOCK
			FROM SCHEDULE s,COURSES c,COURSE_PERIODS cp,SCHOOL_PERIODS sp
			WHERE
				s.COURSE_ID = c.COURSE_ID AND s.COURSE_ID = cp.COURSE_ID
				AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID
				AND s.SCHOOL_ID = sp.SCHOOL_ID AND s.SYEAR = c.SYEAR AND sp.PERIOD_ID = cp.PERIOD_ID
				AND s.STUDENT_ID='".UserStudentID()."'
				AND s.SYEAR='".UserSyear()."'
				AND s.SCHOOL_ID = '".UserSchool()."'";*/
	$sql = "SELECT
				s.COURSE_ID,s.COURSE_PERIOD_ID,
				s.MARKING_PERIOD_ID,s.START_DATE,s.END_DATE,
				extract(EPOCH FROM s.START_DATE) AS START_EPOCH,extract(EPOCH FROM s.END_DATE) AS END_EPOCH,cp.MARKING_PERIOD_ID AS COURSE_MARKING_PERIOD_ID,cp.MP,cp.CALENDAR_ID,cp.TOTAL_SEATS,
				c.TITLE,cp.COURSE_PERIOD_ID AS PERIOD_PULLDOWN,
				s.STUDENT_ID,ROOM,SCHEDULER_LOCK
			FROM SCHEDULE s,COURSES c,COURSE_PERIODS cp 
			WHERE
				s.COURSE_ID = c.COURSE_ID AND s.COURSE_ID = cp.COURSE_ID
				AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID
				AND s.SYEAR = c.SYEAR
				AND s.STUDENT_ID='".UserStudentID()."'
				AND s.SYEAR='".UserSyear()."'
				AND s.SCHOOL_ID = '".UserSchool()."'";
	if($_REQUEST['include_inactive']!='Y')
		$sql .= " AND ('".$date."' BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND s.START_DATE<='".$date."')) ";
	//$sql .= " ORDER BY sp.SORT_ORDER,s.MARKING_PERIOD_ID";
	$sql .= " ORDER BY cp.SHORT_NAME,s.MARKING_PERIOD_ID";

	$QI = DBQuery($sql);
	$schedule_RET = DBGet($QI,array('TITLE'=>'_makeTitle','PERIOD_PULLDOWN'=>'_makePeriodSelect','COURSE_MARKING_PERIOD_ID'=>'_makeMPSelect','SCHEDULER_LOCK'=>'_makeLock','START_DATE'=>'_makeDate','END_DATE'=>'_makeDate'));

	//modif Francois: bugfix SQL bug $_SESSION['student_id'] is not set
	//$link['add']['link'] = '#" onclick=\'window.open("Modules.php?modname='.$_REQUEST['modname'].'&modfunc=choose_course&student_id='.$_REQUEST['student_id'].'&day_date='.$_REQUEST['day_date'].'&month_date='.$_REQUEST['month_date'].'&year_date='.$_REQUEST['year_date'].'","","scrollbars=yes,resizable=yes,width=900,height=400");\' ';
	$link['add']['link'] = '# onclick=\'window.open("Modules.php?modname='.$_REQUEST['modname'].'&modfunc=choose_course&day_date='.$_REQUEST['day_date'].'&month_date='.$_REQUEST['month_date'].'&year_date='.$_REQUEST['year_date'].'","","scrollbars=yes,resizable=yes,width=900,height=400");\'';
	$link['add']['title'] = _('Add a Course');

	$columns = array('TITLE'=>_('Course'),'PERIOD_PULLDOWN'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'),'ROOM'=>_('Room'),'COURSE_MARKING_PERIOD_ID'=>_('Term'),'SCHEDULER_LOCK'=>'<IMG SRC="assets/locked.png" height="24">','START_DATE'=>_('Enrolled'),'END_DATE'=>_('Dropped'));
	/*//modif Francois: multiple school periods for a course period
	//$days_RET = DBGet(DBQuery("SELECT DISTINCT DAYS FROM COURSE_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
	$days_RET = DBGet(DBQuery("SELECT DISTINCT cpsp.DAYS FROM COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."'"));
	if(count($days_RET)==1)
		unset($columns['DAYS']);

	//modif Francois: days display to locale						
	$days_convert = array('U'=>_('Sunday'),'M'=>_('Monday'),'T'=>_('Tuesday'),'W'=>_('Wednesday'),'H'=>_('Thursday'),'F'=>_('Friday'),'S'=>_('Saturday'));
	//modif Francois: days numbered
	if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
		$days_convert = array('U'=>'7','M'=>'1','T'=>'2','W'=>'3','H'=>'4','F'=>'5','S'=>'6');

	for ($j = 1; $j <= count($schedule_RET); $j++) {
		$columns_DAYS_locale = '';
		for ($i = 0; $i < mb_strlen($schedule_RET[$j]['DAYS']); $i++) {
			$columns_DAYS_locale .= mb_substr($days_convert[mb_substr($schedule_RET[$j]['DAYS'], $i, 1)],0,3) . '.&nbsp;';
		}
		$schedule_RET[$j]['DAYS'] = $columns_DAYS_locale;
	}*/

	VerifySchedule($schedule_RET);

	ListOutput($schedule_RET,$columns,'Course','Courses',$link);

	echo '<BR /><span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';

	if(AllowEdit())
	{
		$include_seats = $_REQUEST['include_seats'];
		unset($_REQUEST);
		unset($extra);
		$_REQUEST['modname'] = 'Scheduling/Schedule.php';
        $_REQUEST['student_id'] = UserStudentID();
		$_REQUEST['search_modfunc'] = 'list';
		$_REQUEST['include_seats'] = $include_seats;
		$extra['link']['FULL_NAME']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=choose_course";
		$extra['link']['FULL_NAME']['variables'] = array('subject_id'=>'SUBJECT_ID','course_id'=>'COURSE_ID','student_id'=>'STUDENT_ID');
		$extra['link']['FULL_NAME']['js'] = true;
		include('modules/Scheduling/UnfilledRequests.php');
	}
}

if($_REQUEST['modfunc']=='choose_course')
{

	if(!$_REQUEST['course_period_id'])
		include "modules/Scheduling/Courses.php";
	else
	{
		//$min_date = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS MIN_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		//if($min_date[1]['MIN_DATE'] && DBDate('postgres')<$min_date[1]['MIN_DATE'])
		//	$date = $min_date[1]['MIN_DATE'];
		//else
		//	$date = DBDate();

		//modif Francois: multiple school periods for a course period
		$mp_RET = DBGet(DBQuery("SELECT cp.COURSE_PERIOD_ID,cp.MARKING_PERIOD_ID,cp.MP,cpsp.DAYS,cpsp.PERIOD_ID,cp.MARKING_PERIOD_ID,cp.TOTAL_SEATS,cp.CALENDAR_ID FROM COURSE_PERIODS cp,  COURSE_PERIOD_SCHOOL_PERIODS cpsp 
		WHERE 
		cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'"));
		if($_REQUEST['course_marking_period_id'])
		{
			$mp_RET[1]['MARKING_PERIOD_ID'] = $_REQUEST['course_marking_period_id'];
			$mp_RET[1]['MP'] = GetMP($_REQUEST['course_marking_period_id'],'MP');
		}
		$mps = GetAllMP($mp_RET[1]['MP'],$mp_RET[1]['MARKING_PERIOD_ID']);

		if($mp_RET[1]['TOTAL_SEATS'])
		{
			$seats = calcSeats0($mp_RET[1],$date);
			if($seats!='' && $seats>=$mp_RET[1]['TOTAL_SEATS'])
				$warnings[] = _('This section is already full.');
		}

		// the course being scheduled has start date of $date but no end date by default, and scheduled into the course marking period by default
		// if marking periods overlap and dates overlap (already scheduled course does not end or ends after $date) then not okay
		$current_RET = DBGet(DBQuery("SELECT COURSE_PERIOD_ID FROM SCHEDULE WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_ID='".$_REQUEST['course_id']."' AND MARKING_PERIOD_ID IN (".$mps.") AND (END_DATE IS NULL OR '".DBDate()."'<=END_DATE)"));
		if(count($current_RET))
			$warnings[] = _('This student is already scheduled into this course.');

		//modif Francois: multiple school periods for a course period
		//if marking periods overlap and same period and same day then not okay
		//$period_RET = DBGet(DBQuery("SELECT cp.DAYS FROM SCHEDULE s,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID AND s.STUDENT_ID='".UserStudentID()."' AND cp.PERIOD_ID='".$mp_RET[1]['PERIOD_ID']."' AND s.MARKING_PERIOD_ID IN (".$mps.") AND (s.END_DATE IS NULL OR '".DBDate()."'<=s.END_DATE)"));
		$period_RET = DBGet(DBQuery("SELECT cpsp.DAYS FROM SCHEDULE s,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cpsp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID AND s.STUDENT_ID='".UserStudentID()."' AND cpsp.PERIOD_ID='".$mp_RET[1]['PERIOD_ID']."' AND s.MARKING_PERIOD_ID IN (".$mps.") AND (s.END_DATE IS NULL OR '".DBDate()."'<=s.END_DATE)"));
		$days_conflict = false;
		foreach($period_RET as $existing)
		{
			if(mb_strlen($mp_RET[1]['DAYS'])+mb_strlen($existing['DAYS'])>7)
			{
				$days_conflict = true;
				break;
			}
			else
				foreach(_str_split($mp_RET[1]['DAYS']) as  $i)
					if(mb_strpos($existing['DAYS'],$i)!==false)
					{
						$days_conflict = true;
						break 2;
					}
		}
		if($days_conflict)
			$warnings[] = _('There is already a course scheduled in that period.');

		if(!$warnings)
		{
			DBQuery("INSERT INTO SCHEDULE (SYEAR,SCHOOL_ID,STUDENT_ID,START_DATE,COURSE_ID,COURSE_PERIOD_ID,MP,MARKING_PERIOD_ID) values('".UserSyear()."','".UserSchool()."','".UserStudentID()."','".$date."','".$_REQUEST['course_id']."','".$_REQUEST['course_period_id']."','".$mp_RET[1]['MP']."','".$mp_RET[1]['MARKING_PERIOD_ID']."')");
			echo '<script type="text/javascript">var opener_reload = document.createElement("a"); opener_reload.href = "Modules.php?modname='.$_REQUEST['modname'].'&year_date='.$_REQUEST['year_date'].'&month_date='.$_REQUEST['month_date'].'&day_date='.$_REQUEST['day_date'].'&time='.time().'"; opener_reload.target = "body"; window.opener.ajaxLink(opener_reload); window.close();</script>';
		}
		elseif($warnings)
		{
			if(Prompt(_('Confirm'),_('There is a conflict.').' '._('Are you sure you want to add this section?'),ErrorMessage($warnings,'note')))
			{
				DBQuery("INSERT INTO SCHEDULE (SYEAR,SCHOOL_ID,STUDENT_ID,START_DATE,COURSE_ID,COURSE_PERIOD_ID,MP,MARKING_PERIOD_ID) values('".UserSyear()."','".UserSchool()."','".UserStudentID()."','".$date."','".$_REQUEST['course_id']."','".$_REQUEST['course_period_id']."','".$mp_RET[1]['MP']."','".$mp_RET[1]['MARKING_PERIOD_ID']."')");
				echo '<script type="text/javascript">var opener_reload = document.createElement("a"); opener_reload.href = "Modules.php?modname='.$_REQUEST['modname'].'&year_date='.$_REQUEST['year_date'].'&month_date='.$_REQUEST['month_date'].'&day_date='.$_REQUEST['day_date'].'&time='.time().'"; opener_reload.target = "body"; window.opener.ajaxLink(opener_reload); window.close();</script>';
			}
		}
	}
}

function _makeTitle($value,$column)
{	global $THIS_RET;

//modif Francois: add subject areas
	return CourseTitle ($value);
}

function _makeLock($value,$column)
{	global $THIS_RET;

//modif Francois: icones
	return '<IMG SRC="assets/'.($value=='Y'?'locked':'unlocked').'.png" height="24"'.(AllowEdit()?' onclick="if(this.src.indexOf(\'assets/locked.png\')!=-1) {this.src=\'assets/unlocked.png\'; document.getElementById(\'lock'.$THIS_RET['COURSE_PERIOD_ID'].'-'.$THIS_RET['START_DATE'].'\').value=\'\';} else {this.src=\'assets/locked.png\'; document.getElementById(\'lock'.$THIS_RET['COURSE_PERIOD_ID'].'-'.$THIS_RET['START_DATE'].'\').value=\'Y\';}"':'').' /><INPUT type="hidden" name="schedule['.$THIS_RET['COURSE_PERIOD_ID'].']['.$THIS_RET['START_DATE'].'][SCHEDULER_LOCK]" id="lock'.$THIS_RET['COURSE_PERIOD_ID'].'-'.$THIS_RET['START_DATE'].'" value="'.$value.'" />';
}

function _makePeriodSelect($course_period_id,$column)
{	global $_ROSARIO,$THIS_RET,$fy_id;

	//modif Francois: multiple school periods for a course period
	//$orders_RET = DBGet(DBQuery("SELECT COURSE_PERIOD_ID,PARENT_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,(SELECT SHORT_NAME FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID=cp.PARENT_ID) AS PARENT,TOTAL_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='$THIS_RET[COURSE_ID]' ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID),TITLE"));
	$orders_RET = DBGet(DBQuery("SELECT COURSE_PERIOD_ID,PARENT_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,(SELECT SHORT_NAME FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID=cp.PARENT_ID) AS PARENT,TOTAL_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='$THIS_RET[COURSE_ID]' ORDER BY SHORT_NAME,TITLE"));

	foreach($orders_RET as $value)
	{
		if($value['TOTAL_SEATS'] && $_REQUEST['include_seats'])
			$seats = calcSeats0($value);

		$periods[$value['COURSE_PERIOD_ID']] = $value['TITLE'] . (($value['MARKING_PERIOD_ID']!=$fy_id && $value['COURSE_PERIOD_ID']!=$course_period_id)?' ('.GetMP($value['MARKING_PERIOD_ID']).')':'').(($value['TOTAL_SEATS'] && $_REQUEST['include_seats'] && $seats!='')?' '.sprintf(_('(%d seats)'),($value['TOTAL_SEATS']-$seats)):'').(($value['COURSE_PERIOD_ID']!=$course_period_id && $value['COURSE_PERIOD_ID']!=$value['PARENT_ID'] && $value['PARENT'])?' -> '.$value['PARENT']:'');
	}

	return SelectInput($course_period_id,"schedule[$THIS_RET[COURSE_PERIOD_ID]][$THIS_RET[START_DATE]][COURSE_PERIOD_ID]",'',$periods,false);
}

function _makeMPSelect($mp_id,$name)
{	global $_ROSARIO,$THIS_RET,$fy_id;

	if(!$_ROSARIO['_makeMPSelect'])
	{
		$semesters_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,NULL AS PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		$quarters_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));

		$_ROSARIO['_makeMPSelect'][$fy_id][1] = array('MARKING_PERIOD_ID'=>$fy_id,'TITLE'=>_('Full Year'),'PARENT_ID'=>'');
		foreach($semesters_RET as $sem)
			$_ROSARIO['_makeMPSelect'][$fy_id][] = $sem;
		foreach($quarters_RET as $qtr)
			$_ROSARIO['_makeMPSelect'][$fy_id][] = $qtr;

		$quarters_QI = DBQuery("SELECT MARKING_PERIOD_ID,TITLE,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER");
		$quarters_indexed_RET = DBGet($quarters_QI,array(),array('PARENT_ID'));

		foreach($semesters_RET as $sem)
		{
			$_ROSARIO['_makeMPSelect'][$sem['MARKING_PERIOD_ID']][1] = $sem;
			foreach($quarters_indexed_RET[$sem['MARKING_PERIOD_ID']] as $qtr)
				$_ROSARIO['_makeMPSelect'][$sem['MARKING_PERIOD_ID']][] = $qtr;
		}

		foreach($quarters_RET as $qtr)
			$_ROSARIO['_makeMPSelect'][$qtr['MARKING_PERIOD_ID']][] = $qtr;
	}

	foreach($_ROSARIO['_makeMPSelect'][$mp_id] as $value)
	{
		if($value['MARKING_PERIOD_ID']!=$THIS_RET['MARKING_PERIOD_ID'] && $THIS_RET['TOTAL_SEATS'] && $_REQUEST['include_seats'])
			$seats = calcSeats0($THIS_RET);

		$mps[$value['MARKING_PERIOD_ID']] = (($value['MARKING_PERIOD_ID']==$THIS_RET['MARKING_PERIOD_ID'] && $value['MARKING_PERIOD_ID']!=$mp_id)?'* ':'').$value['TITLE'].(($value['MARKING_PERIOD_ID']!=$THIS_RET['MARKING_PERIOD_ID'] && $THIS_RET['TOTAL_SEATS'] && $_REQUEST['include_seats'] && $seats!='')?' '.sprintf(_('(%d seats)'),($THIS_RET['TOTAL_SEATS']-$seats)):'');
	}

	return SelectInput($THIS_RET['MARKING_PERIOD_ID'],"schedule[$THIS_RET[COURSE_PERIOD_ID]][$THIS_RET[START_DATE]][MARKING_PERIOD_ID]",'',$mps,false);
}

function calcSeats0($period,$date='')
{
	$mp = $period['MARKING_PERIOD_ID'];

	$seats = DBGet(DBQuery("SELECT max((SELECT count(1) FROM SCHEDULE ss JOIN STUDENT_ENROLLMENT sem ON (sem.STUDENT_ID=ss.STUDENT_ID AND sem.SYEAR=ss.SYEAR) WHERE ss.COURSE_PERIOD_ID='$period[COURSE_PERIOD_ID]' AND (ss.MARKING_PERIOD_ID='$mp' OR ss.MARKING_PERIOD_ID IN (".GetAllMP(GetMP($mp,'MP'),$mp).")) AND (ac.SCHOOL_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ac.SCHOOL_DATE<=ss.END_DATE)) AND (ac.SCHOOL_DATE>=sem.START_DATE AND (sem.END_DATE IS NULL OR ac.SCHOOL_DATE<=sem.END_DATE)))) AS FILLED_SEATS FROM ATTENDANCE_CALENDAR ac WHERE ac.CALENDAR_ID='$period[CALENDAR_ID]' AND ac.SCHOOL_DATE BETWEEN ".($date?"'$date'":db_case(array("(CURRENT_DATE>'".GetMP($mp,'END_DATE')."')",'TRUE',"'".GetMP($mp,'START_DATE')."'",'CURRENT_DATE')))." AND '".GetMP($mp,'END_DATE')."'"));
	return $seats[1]['FILLED_SEATS'];
}

function _makeDate($value,$column)
{	global $THIS_RET;

	if($column=='START_DATE')
		$allow_na = false;
	else
		$allow_na = true;

	return DateInput($value,"schedule[$THIS_RET[COURSE_PERIOD_ID]][$THIS_RET[START_DATE]][$column]",'',true,$allow_na);
}

function VerifySchedule(&$schedule)
{
	$conflicts = array();

	$ij = count($schedule);
	for($i=1; $i<$ij; $i++)
		for($j=$i+1; $j<=$ij; $j++)
			if(!$conflicts[$i] || !$conflicts[$j])
				// the following two if's are equivalent, the second matches the 'Add a Course' logic, the first is the demorgan equivalent and easier to follow
				// if -not- marking periods don't overlap -or- dates don't overlap (i ends and j starts after i -or- j ends and i starts after j) then check further
				//if(! (mb_strpos(GetAllMP(GetMP($schedule[$i]['MARKING_PERIOD_ID'],'MP'),$schedule[$i]['MARKING_PERIOD_ID']),"'".$schedule[$j]['MARKING_PERIOD_ID']."'")===false
				//|| $schedule[$i]['END_EPOCH'] && $schedule[$j]['START_EPOCH']>$schedule[$i]['END_EPOCH'] || $schedule[$j]['END_EPOCH'] && $schedule[$i]['START_EPOCH']>$schedule[$j]['END_EPOCH']))
				// if marking periods overlap -and- dates overlap (i doesn't end or j starts before i ends -and- j doesn't end or i starts before j ends) check further
				if(mb_strpos(GetAllMP(GetMP($schedule[$i]['MARKING_PERIOD_ID'],'MP'),$schedule[$i]['MARKING_PERIOD_ID']),"'".$schedule[$j]['MARKING_PERIOD_ID']."'")!==false
				&& (!$schedule[$i]['END_EPOCH'] || $schedule[$j]['START_EPOCH']<=$schedule[$i]['END_EPOCH']) && (!$schedule[$j]['END_EPOCH'] || $schedule[$i]['START_EPOCH']<=$schedule[$j]['END_EPOCH']))
					// should not be enrolled in the same course with overlapping marking periods and dates
					if($schedule[$i]['COURSE_ID']==$schedule[$j]['COURSE_ID'])
						$conflicts[$i] = $conflicts[$j] = true;
					else
						// if different periods then okay
						if($schedule[$i]['PERIOD_ID']==$schedule[$j]['PERIOD_ID'])
							// should not be enrolled in the same period on the same day
							if(mb_strlen($schedule[$i]['DAYS'])+mb_strlen($schedule[$j]['DAYS'])>7)
								$conflicts[$i] = $conflicts[$j] = true;
							else
								foreach(_str_split($schedule[$i]['DAYS']) as $k)
									if(mb_strpos($schedule[$j]['DAYS'],$k)!==false)
									{
										$conflicts[$i] = $conflicts[$j] = true;
										break;
									}

	foreach($conflicts as $i=>$true)
		$schedule[$i]['TITLE'] = '<span style="color:red">'.$schedule[$i]['TITLE'].'</span>';
}

function _str_split($str)
{
	$ret = array();
	$len = mb_strlen($str);
	for($i=0;$i<$len;$i++)
		$ret [] = mb_substr($str,$i,1);
	return $ret;
}
?>
