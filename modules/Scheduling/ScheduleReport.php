<?php
DrawHeader(ProgramTitle());
echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
if ($_REQUEST['modfunc']!='students')
    DrawHeader('<label>'.CheckBoxOnclick('include_child_mps').' '._('Show Child Marking Period Details').'</label>');
if($_REQUEST['subject_id'])
{
	$RET = DBGet(DBQuery("SELECT TITLE FROM COURSE_SUBJECTS WHERE SUBJECT_ID='".$_REQUEST['subject_id']."'"));
//modif Francois: add translation
	$header .= '<A HREF="Modules.php?modname='.$_REQUEST['modname'].'&include_child_mps='.$_REQUEST['include_child_mps'].'">'._('Top').'</A> &rsaquo; <A HREF="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=courses&subject_id='.$_REQUEST['subject_id'].'&include_child_mps='.$_REQUEST['include_child_mps'].'">'.$RET[1]['TITLE'].'</A>';
	if($_REQUEST['course_id'])
	{
		$header2 = '<A HREF="Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'];
		$location = 'courses';
		$RET = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_REQUEST['course_id']."'"));
		$header .= ' &rsaquo; <A HREF="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=students&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&include_child_mps='.$_REQUEST['include_child_mps'].'">'.$RET[1]['TITLE'].'</A>';

		$header2 .= '&students='.$location.'&modfunc=students&include_child_mps='.$_REQUEST['include_child_mps'].'">'._('List Students').'</A> | '.$header2.'&unscheduled=true&students='.$location.'&modfunc=students&include_child_mps='.$_REQUEST['include_child_mps'].'">'._('List Unscheduled Students').'</A>';

		DrawHeader($header);
		DrawHeader($header2);
	}
	else
		DrawHeader($header);
}
echo '</FORM>';

$LO_options = array('save'=>false,'search'=>false,'print'=>false);

echo '<TABLE><TR class="st">';

// SUBJECTS ----
if(!$_REQUEST['modfunc'] || ($_REQUEST['modfunc']=='courses' && $_REQUEST['students']!='courses'))
{
	$QI = DBQuery("SELECT s.SUBJECT_ID,s.TITLE FROM COURSE_SUBJECTS s WHERE s.SYEAR='".UserSyear()."' AND s.SCHOOL_ID='".UserSchool()."' ORDER BY s.SORT_ORDER,s.TITLE");
	$RET = DBGet($QI);
	if(count($RET) && $_REQUEST['subject_id'])
	{
		foreach($RET as $key=>$value)
		{
			if($value['SUBJECT_ID']==$_REQUEST['subject_id'])
				$RET[$key]['row_color'] = Preferences('HIGHLIGHT');
		}
	}
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=courses&include_child_mps=$_REQUEST[include_child_mps]";
	$link['TITLE']['variables'] = array('subject_id'=>'SUBJECT_ID');
	echo '<TD class="valign-top">';
	$LO_options['responsive'] = false;
	ListOutput($RET,array('TITLE'=>_('Subject')),'Subject','Subjects',$link,array(),$LO_options);
	echo '</TD>';
}

// COURSES ----
if($_REQUEST['modfunc']=='courses')
{
	$QI = DBQuery("SELECT c.COURSE_ID,c.TITLE,cp.TOTAL_SEATS,cp.COURSE_PERIOD_ID,cp.MARKING_PERIOD_ID,cp.MP,cp.CALENDAR_ID,(SELECT count(*) FROM SCHEDULE_REQUESTS sr WHERE sr.COURSE_ID=c.COURSE_ID) AS COUNT_REQUESTS FROM COURSES c,COURSE_PERIODS cp WHERE c.SUBJECT_ID='$_REQUEST[subject_id]' AND c.COURSE_ID=cp.COURSE_ID AND c.SYEAR='".UserSyear()."' AND c.SCHOOL_ID='".UserSchool()."' ORDER BY c.TITLE");
	$_RET = DBGet($QI,array(),array('COURSE_ID'));

	$RET = calcSeats($_RET,array('COURSE_ID','TITLE','COUNT_REQUESTS'));

	if(count($RET) && $_REQUEST['course_id'])
	{
		foreach($RET as $key=>$value)
		{
			if($value['COURSE_ID']==$_REQUEST['course_id'])
				$RET[$key]['row_color'] = Preferences('HIGHLIGHT');
		}
	}
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=students&subject_id=$_REQUEST[subject_id]&include_child_mps=$_REQUEST[include_child_mps]";
	$link['TITLE']['variables'] = array('course_id'=>'COURSE_ID');
	$columns = array('TITLE'=>_('Course'),'COUNT_REQUESTS'=>_('Requests'));
	if($_REQUEST['include_child_mps'])
	{
		$OFT_string = mb_substr(_('Open'),0,1).'&#124;'.mb_substr(_('Filled'),0,1).'&#124;'.mb_substr(_('Total'),0,1);
		//modif Francois: fix error Missing argument 1
		foreach(explode(',',GetAllMP('')) as $mp)
		{
			$mp = trim($mp,"'");
			$columns += array('OFT_'.$mp=>(GetMP($mp,'SHORT_NAME')?GetMP($mp,'SHORT_NAME'):GetMP($mp)).'<BR />'.$OFT_string);
		}
	}
	else
		$columns += array('OPEN_SEATS'=>_('Open'),'FILLED_SEATS'=>_('Filled'),'TOTAL_SEATS'=>_('Total'));
	echo '<TD class="valign-top">';
	$LO_options['responsive'] = true;
	ListOutput($RET,$columns,'Course','Courses',$link,array(),$LO_options);
	echo '</TD>';
}

// COURSE PERIODS ----
if($_REQUEST['modfunc']=='course_periods' || $_REQUEST['students']=='course_periods')
{
	//modif Francois: multiple school periods for a course period
	//$QI = DBQuery("SELECT COURSE_PERIOD_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,TOTAL_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='".$_REQUEST['course_id']."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID),TITLE");
	$QI = DBQuery("SELECT COURSE_PERIOD_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,TOTAL_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='".$_REQUEST['course_id']."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SHORT_NAME,TITLE");
	$RET = DBGet($QI);

	foreach($RET as $key=>$period)
	{
		$value = array();
		if($_REQUEST['include_child_mps'])
			$total_seats = $filled_seats = array();
		else
			$total_seats = $filled_seats = 0;
		calcSeats1($period,$total_seats,$filled_seats);
		if($_REQUEST['include_child_mps'])
		{
			foreach($total_seats as $mp=>$total)
			{
				$value += array('OFT_'.$mp=>($total!==false?($filled_seats[$mp]!==false?$total-$filled_seats[$mp]:''):_('N/A')).'|'.($filled_seats[$mp]!==false?$filled_seats[$mp]:'').'|'.($total!==false?$total:_('N/A')));
			}
		}
		else
			$value += array('OPEN_SEATS'=>($total_seats!==false?($filled_seats!==false?$total_seats-$filled_seats:''):_('N/A')),'FILLED_SEATS'=>($filled_seats!==false?$filled_seats:''),'TOTAL_SEATS'=>($total_seats!==false?$total_seats:_('N/A')));
		$RET[$key] += $value;
	}

	if(count($RET) && $_REQUEST['course_period_id'])
	{
		foreach($RET as $key=>$value)
		{
			if($value['COURSE_PERIOD_ID']==$_REQUEST['course_period_id'])
				$RET[$key]['row_color'] = Preferences('HIGHLIGHT');
		}
	}
	$link = array();
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=students&students=course_periods&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]&include_child_mps=$_REQUEST[include_child_mps]";
	$link['TITLE']['variables'] = array('course_period_id'=>'COURSE_PERIOD_ID');
    $columns = array('TITLE'=>_('Course'),'COUNT_REQUESTS'=>_('Requests'));
	$columns = array('TITLE'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'));
	if($_REQUEST['include_child_mps'])
	{
		foreach(explode(',',GetAllMP()) as $mp)
		{
			$mp = trim($mp,"'");
			$columns += array('OFT_'.$mp=>(GetMP($mp,'SHORT_NAME')?GetMP($mp,'SHORT_NAME'):GetMP($mp)).'<BR />O|F|T');
		}
	}
	else
		$columns += array('OPEN_SEATS'=>_('Open'),'FILLED_SEATS'=>_('Filled'),'TOTAL_SEATS'=>_('Total'));
	echo '<TD class="valign-top">';
	ListOutput($RET,$columns,'Course Period','Course Periods',$link,array(),$LO_options);
	echo '</TD>';
}

echo '</TR></TABLE>';

// LIST STUDENTS ----
if($_REQUEST['modfunc']=='students')
{
	if($_REQUEST['unscheduled']=='true')
	{
		$sql = "SELECT s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,s.STUDENT_ID,s.CUSTOM_200000004,ssm.GRADE_ID
				FROM SCHEDULE_REQUESTS sr,STUDENTS s,STUDENT_ENROLLMENT ssm
				WHERE (('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)) AND s.STUDENT_ID=sr.STUDENT_ID AND s.STUDENT_ID=ssm.STUDENT_ID AND ssm.SYEAR='".UserSyear()."' AND ssm.SCHOOL_ID='".UserSchool()."' ";
		if($_REQUEST['course_id'])
			$sql .= "AND sr.COURSE_ID='$_REQUEST[course_id]' ";
		elseif($_REQUEST['course_id'])
			$sql .= "AND sr.COURSE_ID='$_REQUEST[course_id]' ";
		$sql .= "AND NOT EXISTS (SELECT '' FROM SCHEDULE ss WHERE ss.COURSE_ID=sr.COURSE_ID AND ss.STUDENT_ID=sr.STUDENT_ID AND ('".DBDate()."' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL))";
	}
	else
	{
		$sql = "SELECT s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,s.STUDENT_ID,s.CUSTOM_200000004,ssm.GRADE_ID
				FROM SCHEDULE ss,STUDENTS s,STUDENT_ENROLLMENT ssm
				WHERE ('".DBDate()."' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL) AND (('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)) AND s.STUDENT_ID=ss.STUDENT_ID AND s.STUDENT_ID=ssm.STUDENT_ID AND ssm.SYEAR='".UserSyear()."' AND ssm.SCHOOL_ID='".UserSchool()."' ";
		if($_REQUEST['course_period_id'])
			$sql .= "AND ss.COURSE_PERIOD_ID='$_REQUEST[course_period_id]'";
		elseif($_REQUEST['course_id'])
			$sql .= "AND ss.COURSE_ID='$_REQUEST[course_id]'";
	}
	$sql .= ' ORDER BY s.LAST_NAME,s.FIRST_NAME';
	$RET = DBGet(DBQuery($sql),array('CUSTOM_200000004'=>'ShortDate','GRADE_ID'=>'GetGrade'));

	$link = array();
	if(AllowUse('Scheduling/Schedule.php'))
	{
		$link['FULL_NAME']['link'] = "Modules.php?modname=Scheduling/Schedule.php";
		$link['FULL_NAME']['variables'] = array('student_id'=>'STUDENT_ID');
	}
    if ($_REQUEST['unscheduled']=='true')
	    ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade Level'),'CUSTOM_200000004'=>_('Birthdate')),'Unscheduled Student','Unscheduled Students',$link,array(),$LO_options);
    else
        ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade Level'),'CUSTOM_200000004'=>_('Birthdate')),'Student','Students',$link,array(),$LO_options);
}

function calcSeats1($period,&$total_seats,&$filled_seats)
{

	if($_REQUEST['include_child_mps'])
	{
		$mps = GetChildrenMP($period['MP'],$period['MARKING_PERIOD_ID']);
		if($period['MP']=='FY' || $period['MP']=='SEM')
			$mps = "'$period[MARKING_PERIOD_ID]'".($mps?','.$mps:'');
	}
	else
		$mps = "'".$period['MARKING_PERIOD_ID']."'";

	foreach(explode(',',$mps) as $mp)
	{
		$mp = trim($mp,"'");
		$seats = DBGet(DBQuery("SELECT max((SELECT count(1) FROM SCHEDULE ss JOIN STUDENT_ENROLLMENT sem ON (sem.STUDENT_ID=ss.STUDENT_ID AND sem.SYEAR=ss.SYEAR) WHERE ss.COURSE_PERIOD_ID='$period[COURSE_PERIOD_ID]' AND (ss.MARKING_PERIOD_ID='$mp' OR ss.MARKING_PERIOD_ID IN (".GetAllMP(GetMP($mp,'MP'),$mp).")) AND (ac.SCHOOL_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ac.SCHOOL_DATE<=ss.END_DATE)) AND (ac.SCHOOL_DATE>=sem.START_DATE AND (sem.END_DATE IS NULL OR ac.SCHOOL_DATE<=sem.END_DATE)))) AS FILLED_SEATS FROM ATTENDANCE_CALENDAR ac WHERE ac.CALENDAR_ID='$period[CALENDAR_ID]' AND ac.SCHOOL_DATE BETWEEN ".db_case(array("(CURRENT_DATE>'".GetMP($mp,'END_DATE')."')",'TRUE',"'".GetMP($mp,'START_DATE')."'",'CURRENT_DATE'))." AND '".GetMP($mp,'END_DATE')."'"));
		if($_REQUEST['include_child_mps'])
		{
			if($total_seats[$mp]!==false)
				if($period['TOTAL_SEATS'])
					$total_seats[$mp] += $period['TOTAL_SEATS'];
				else
					$total_seats[$mp] = false;
			if($filled_seats!==false)
				if($seats[1]['FILLED_SEATS']!='')
					$filled_seats[$mp] += $seats[1]['FILLED_SEATS'];
				else
					$filled_seats[$mp] = false;
		}
		else
		{
			if($total_seats!==false)
				if($period['TOTAL_SEATS'])
					$total_seats += $period['TOTAL_SEATS'];
				else
					$total_seats = false;
			if($filled_seats!==false)
				if($seats[1]['FILLED_SEATS']!='')
					$filled_seats += $seats[1]['FILLED_SEATS'];
				else
					$filled_seats = false;
		}
	}
}

function calcSeats(&$_RET,$columns)
{
	$RET = array(0=>array());
	foreach($_RET as $periods)
	{
		$value = array();
		foreach($columns as $column)
			$value += array($column=>$periods[key($periods)][$column]);
		if($_REQUEST['include_child_mps'])
			$total_seats = $filled_seats = array();
		else
			$total_seats = $filled_seats = 0;
		foreach($periods as $period)
			calcSeats1($period,$total_seats,$filled_seats);
		if($_REQUEST['include_child_mps'])
		{
			foreach($total_seats as $mp=>$total)
			{
				$filled = $filled_seats[$mp];
				$value += array('OFT_'.$mp=>($total!==false?($filled!==false?$total-$filled:''):'n/a').'|'.($filled!==false?$filled:'').'|'.($total!==false?$total:'n/a'));
			}
		}
		else
			$value += array('OPEN_SEATS'=>($total_seats!==false?($filled_seats!==false?$total_seats-$filled_seats:''):'n/a'),'FILLED_SEATS'=>($filled_seats!==false?$filled_seats:''),'TOTAL_SEATS'=>($total_seats!==false?$total_seats:'n/a'));
		$RET[] = $value;
	}
	unset($RET[0]);

	return $RET;
}
?>