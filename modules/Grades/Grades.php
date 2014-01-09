<?php
// Note: The 'active assignments' feature is not fully correct.  If a student has dropped and re-enrolled there can be multiple timespans for
// which the  assignemnts are 'active' for that student.  However, only the timespan of current enrollment is used for 'active' assignment
// determination.  It would be possible to include all enrollment timespans but only the current is used for simplicity.  This is not a bug
// but an accepted limitaion.

DrawHeader(_('Gradebook').' - '.ProgramTitle());

//modif Francois: add School Configuration
$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='grades'"),array(),array('TITLE'));

include 'ProgramFunctions/_makeLetterGrade.fnc.php';

// if running as a teacher program then rosario[allow_edit] will already be set according to admin permissions
if(!isset($_ROSARIO['allow_edit']))
	$_ROSARIO['allow_edit'] = true;

$config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
if(count($config_RET))
	foreach($config_RET as $title=>$value)
		$programconfig[User('STAFF_ID')][$title] = $value[1]['VALUE'];
else
	$programconfig[User('STAFF_ID')] = true;
//$max_allowed = Preferences('ANOMALOUS_MAX','Gradebook')/100;
$max_allowed = ($programconfig[User('STAFF_ID')]['ANOMALOUS_MAX']?$programconfig[User('STAFF_ID')]['ANOMALOUS_MAX']/100:1);

if($_REQUEST['student_id'])
{
	if($_REQUEST['student_id']!=$_SESSION['student_id'])
	{
		$_SESSION['student_id'] = $_REQUEST['student_id'];
		//modif Francois: bugfix SQL bug course period
		/*if($_REQUEST['period'] && $_REQUEST['period']!=$_SESSION['UserCoursePeriod'])
			$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];*/
		if ($_REQUEST['period'])
		{
			list($CoursePeriod, $CoursePeriodSchoolPeriod) = explode('.', $_REQUEST['period']);
			if ($CoursePeriod!=$_SESSION['UserCoursePeriod'])
				$_SESSION['UserCoursePeriod'] = $CoursePeriod;
		}
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
	}
}
else
{
	if($_SESSION['student_id'])
	{
		unset($_SESSION['student_id']);
		//modif Francois: bugfix SQL bug course period
		/*if($_REQUEST['period'] && $_REQUEST['period']!=$_SESSION['UserCoursePeriod'])
			$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];*/
		if ($_REQUEST['period'])
		{
			list($CoursePeriod, $CoursePeriodSchoolPeriod) = explode('.', $_REQUEST['period']);
			if ($CoursePeriod!=$_SESSION['UserCoursePeriod'])
				$_SESSION['UserCoursePeriod'] = $CoursePeriod;
		}
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
	}
}
if($_REQUEST['period'])
{
	//modif Francois: bugfix SQL bug course period
	/*if($_REQUEST['period']!=$_SESSION['UserCoursePeriod'])
	{
		$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];*/
	list($CoursePeriod, $CoursePeriodSchoolPeriod) = explode('.', $_REQUEST['period']);
		
	if ($CoursePeriod!=$_SESSION['UserCoursePeriod'])
	{
		$_SESSION['UserCoursePeriod'] = $CoursePeriod;
		if($_REQUEST['student_id'])
		{
			if($_REQUEST['student_id']!=$_SESSION['student_id'])
				$_SESSION['student_id'] = $_REQUEST['student_id'];
		}
		else
			unset($_SESSION['student_id']);
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
	}
}

$types_RET = DBGet(DBQuery("SELECT ASSIGNMENT_TYPE_ID,TITLE,FINAL_GRADE_PERCENT,COLOR FROM GRADEBOOK_ASSIGNMENT_TYPES gt WHERE STAFF_ID='".User('STAFF_ID')."' AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') AND (SELECT count(1) FROM GRADEBOOK_ASSIGNMENTS WHERE STAFF_ID=gt.STAFF_ID AND ((COURSE_ID=gt.COURSE_ID AND STAFF_ID=gt.STAFF_ID) OR COURSE_PERIOD_ID='".UserCoursePeriod()."') AND MARKING_PERIOD_ID='".UserMP()."' AND ASSIGNMENT_TYPE_ID=gt.ASSIGNMENT_TYPE_ID)>0 ORDER BY SORT_ORDER,TITLE"),array(),array('ASSIGNMENT_TYPE_ID'));
//echo '<pre>'; var_dump($types_RET); echo '</pre>';
if($_REQUEST['type_id'])
	if(!$types_RET[$_REQUEST['type_id']])
		unset($_REQUEST['type_id']);

$assignments_RET = DBGet(DBQuery("SELECT ASSIGNMENT_ID,ASSIGNMENT_TYPE_ID,TITLE,POINTS,ASSIGNED_DATE,DUE_DATE,extract(EPOCH FROM DUE_DATE) AS DUE_EPOCH,CASE WHEN (ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ASSIGNED_DATE) AND (DUE_DATE IS NULL OR CURRENT_DATE>=DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=gradebook_assignments.MARKING_PERIOD_ID) THEN 'Y' ELSE NULL END AS DUE FROM GRADEBOOK_ASSIGNMENTS WHERE STAFF_ID='".User('STAFF_ID')."' AND ((COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') AND STAFF_ID='".User('STAFF_ID')."') OR COURSE_PERIOD_ID='".UserCoursePeriod()."') AND MARKING_PERIOD_ID='".UserMP()."'".($_REQUEST['type_id']?" AND ASSIGNMENT_TYPE_ID='$_REQUEST[type_id]'":'')." ORDER BY ".Preferences('ASSIGNMENT_SORTING','Gradebook')." DESC,ASSIGNMENT_ID DESC,TITLE"),array(),array('ASSIGNMENT_ID'));
//echo '<pre>'; var_dump($assignments_RET); echo '</pre>';
// when changing course periods the assignment_id will be wrong except for '' (totals) and 'all'
if($_REQUEST['assignment_id'] && $_REQUEST['assignment_id']!='all')
	if(!$assignments_RET[$_REQUEST['assignment_id']])
		unset($_REQUEST['assignment_id']);
	//else
	//	$_REQUEST['type_id'] = $assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNMENT_TYPE_ID'];
if(UserStudentID() && !$_REQUEST['assignment_id'])
	$_REQUEST['assignment_id'] = 'all';

if($_REQUEST['values'] && $_POST['values'] && $_SESSION['type_id']==$_REQUEST['type_id'] && $_SESSION['assignment_id']==$_REQUEST['assignment_id'])
{
	include 'ProgramFunctions/_makePercentGrade.fnc.php';
	if(UserStudentID())
		$current_RET[UserStudentID()] = DBGet(DBQuery("SELECT g.ASSIGNMENT_ID FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND a.MARKING_PERIOD_ID='".UserMP()."' AND g.STUDENT_ID='".UserStudentID()."' AND g.COURSE_PERIOD_ID='".UserCoursePeriod()."'".($_REQUEST['assignment_id']=='all'?'':" AND g.ASSIGNMENT_ID='$_REQUEST[assignment_id]'")),array(),array('ASSIGNMENT_ID'));
	elseif($_REQUEST['assignment_id']=='all')
		$current_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.ASSIGNMENT_ID,g.POINTS FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND a.MARKING_PERIOD_ID='".UserMP()."' AND g.COURSE_PERIOD_ID='".UserCoursePeriod()."'"),array(),array('STUDENT_ID','ASSIGNMENT_ID'));
	else
		$current_RET = DBGet(DBQuery("SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]' AND COURSE_PERIOD_ID='".UserCoursePeriod()."'"),array(),array('STUDENT_ID','ASSIGNMENT_ID'));

	foreach($_REQUEST['values'] as $student_id=>$assignments)
	{
		foreach($assignments as $assignment_id=>$columns)
		{
			if($columns['POINTS'])
			{
				if($columns['POINTS']=='*')
					$columns['POINTS'] = '-1';
				else
				{
					if(mb_substr($columns['POINTS'],-1)=='%')
						$columns['POINTS'] = mb_substr($columns['POINTS'],0,-1) * $assignments_RET[$assignment_id][1]['POINTS'] / 100;
					elseif(!is_numeric($columns['POINTS']))
						$columns['POINTS'] = _makePercentGrade($columns['POINTS'],UserCoursePeriod()) * $assignments_RET[$assignment_id][1]['POINTS'] / 100;
					if($columns['POINTS']<0)
						$columns['POINTS'] = '0';
					elseif($columns['POINTS']>9999.99)
						$columns['POINTS'] = '9999.99';
				}
			}
			$sql = '';
			if($current_RET[$student_id][$assignment_id])
			{
				$sql = "UPDATE GRADEBOOK_GRADES SET ";
				foreach($columns as $column=>$value)
				{
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1)." WHERE STUDENT_ID='$student_id' AND ASSIGNMENT_ID='$assignment_id' AND COURSE_PERIOD_ID='".UserCoursePeriod()."'";
			}
			elseif($columns['POINTS']!='' || $columns['COMMENT'])
				$sql = "INSERT INTO GRADEBOOK_GRADES (STUDENT_ID,PERIOD_ID,COURSE_PERIOD_ID,ASSIGNMENT_ID,POINTS,COMMENT) values('$student_id','".UserPeriod()."','".UserCoursePeriod()."','".$assignment_id."','".$columns['POINTS']."','".$columns['COMMENT']."')";

			if($sql)
				DBQuery($sql);
		}
	}

	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($current_RET);
}
$_SESSION['type_id'] = $_REQUEST['type_id'];
$_SESSION['assignment_id'] = $_REQUEST['assignment_id'];
$LO_options = array('search'=>false);

if(UserStudentID())
{
	$extra['WHERE'] = " AND s.STUDENT_ID='".UserStudentID()."'";

	if(!$_REQUEST['type_id'])
		$LO_columns = array('TYPE_TITLE'=>_('Category'));
	else
		$LO_columns = array();
	$LO_columns += array('TITLE'=>_('Assignment'),'POINTS'=>_('Points'),'COMMENT'=>_('Comment'));
// modif Francois: display percent grade according to Configuration
	if ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>=0)
		$LO_columns['PERCENT_GRADE'] = _('Percent');
// modif Francois: display letter grade according to Configuration
	if ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<=0)
		$LO_columns['LETTER_GRADE'] = _('Letter');
	$LO_columns += array('TITLE'=>_('Assignment'),'POINTS'=>_('Points'),/*'PERCENT_GRADE'=>_('Percent'),*/'LETTER_GRADE'=>_('Letter'),'COMMENT'=>_('Comment'));
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&include_all=$_REQUEST[include_all]";
	$link['TITLE']['variables'] = array('type_id'=>'ASSIGNMENT_TYPE_ID','assignment_id'=>'ASSIGNMENT_ID');

	$current_RET[UserStudentID()] = DBGet(DBQuery("SELECT g.ASSIGNMENT_ID FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND a.MARKING_PERIOD_ID='".UserMP()."' AND g.STUDENT_ID='".UserStudentID()."' AND g.COURSE_PERIOD_ID='".UserCoursePeriod()."'".($_REQUEST['assignment_id']=='all'?'':" AND g.ASSIGNMENT_ID='$_REQUEST[assignment_id]'")),array(),array('ASSIGNMENT_ID'));
	$count_assignments = count($assignments_RET);

	$extra['SELECT'] = ",ga.ASSIGNMENT_TYPE_ID,ga.ASSIGNMENT_ID,ga.TITLE,ga.POINTS AS TOTAL_POINTS,'' AS PERCENT_GRADE,'' AS LETTER_GRADE,CASE WHEN (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID) THEN 'Y' ELSE NULL END AS DUE";
	$extra['SELECT'] .= ',gg.POINTS,gg.COMMENT';
	if(!$_REQUEST['type_id'])
	{
		$extra['SELECT'] .= ',(SELECT TITLE FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID) AS TYPE_TITLE';
		$link['TYPE_TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&include_all=$_REQUEST[include_all]";
		$link['TYPE_TITLE']['variables'] = array('type_id'=>'ASSIGNMENT_TYPE_ID');
	}
	$extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON (ga.STAFF_ID=cp.TEACHER_ID AND ((ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) OR ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AND ga.MARKING_PERIOD_ID='".UserMP()."'".($_REQUEST['assignment_id']=='all'?'':" AND ga.ASSIGNMENT_ID='$_REQUEST[assignment_id]'").($_REQUEST['type_id']?" AND ga.ASSIGNMENT_TYPE_ID='$_REQUEST[type_id]'":'').") LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)";
	if(!$_REQUEST['include_all'])
		$extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR (ga.DUE_DATE IS NULL OR (".db_greatest('ssm.START_DATE','ss.START_DATE')."<=ga.DUE_DATE) AND (".db_least('ssm.END_DATE','ss.END_DATE')." IS NULL OR ".db_least('ssm.END_DATE','ss.END_DATE').">=ga.DUE_DATE)))".($_REQUEST['type_id']?" AND ga.ASSIGNMENT_TYPE_ID='$_REQUEST[type_id]'":'');
	$extra['ORDER_BY'] = Preferences('ASSIGNMENT_SORTING','Gradebook')." DESC";
	$extra['functions'] = array('POINTS'=>'_makeExtraStuCols','PERCENT_GRADE'=>'_makeExtraStuCols','LETTER_GRADE'=>'_makeExtraStuCols','COMMENT'=>'_makeExtraStuCols');
}
else
{
	$LO_columns = array('FULL_NAME'=>_('Student'));
	if($_REQUEST['assignment_id']!='all')
		$LO_columns += array('STUDENT_ID'=>_('RosarioSIS ID'));
	if($_REQUEST['include_inactive']=='Y')
		$LO_columns += array('ACTIVE'=>_('School Status'),'ACTIVE_SCHEDULE'=>_('Course Status'));
	$link['FULL_NAME']['link'] = "Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&include_all=$_REQUEST[include_all]&type_id=$_REQUEST[type_id]&assignment_id=all";
	$link['FULL_NAME']['variables'] = array('student_id'=>'STUDENT_ID');

	if($_REQUEST['assignment_id']=='all')
	{
		$current_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.ASSIGNMENT_ID,g.POINTS FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND a.MARKING_PERIOD_ID='".UserMP()."' AND g.COURSE_PERIOD_ID='".UserCoursePeriod()."'".($_REQUEST['type_id']?" AND a.ASSIGNMENT_TYPE_ID='$_REQUEST[type_id]'":'')),array(),array('STUDENT_ID','ASSIGNMENT_ID'));
		$count_extra = array('SELECT_ONLY'=>'ssm.STUDENT_ID');
		$count_students = GetStuList($count_extra);
		$count_students = count($count_students);

		$extra['SELECT'] = ",extract(EPOCH FROM ".db_greatest('ssm.START_DATE','ss.START_DATE').") AS START_EPOCH,extract(EPOCH FROM ".db_least('ssm.END_DATE','ss.END_DATE').") AS END_EPOCH";
		$extra['functions'] = array();
		if(count($assignments_RET))
		{
			foreach($assignments_RET as $id=>$assignment)
			{
				$assignment = $assignment[1];
				$extra['SELECT'] .= ",'$id' AS G$id";
				$extra['functions'] += array('G'.$id=>'_makeExtraCols');
				$LO_columns['G'.$id] = ($_REQUEST['type_id']?'':$types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['TITLE'].'<BR />').$assignment['TITLE'];
				/*if(!$_REQUEST['type_id'] && $types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['COLOR'])
					$LO_options['header_colors']['G'.$id] = $types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['COLOR'];*/
			}
		}
	}
	elseif($_REQUEST['assignment_id'])
	{
		$extra['SELECT'] .= ",'$_REQUEST[assignment_id]' AS POINTS,'$_REQUEST[assignment_id]' AS PERCENT_GRADE,'$_REQUEST[assignment_id]' AS LETTER_GRADE,'$_REQUEST[assignment_id]' AS COMMENT";
		$extra['SELECT'] .= ",extract(EPOCH FROM ".db_greatest('ssm.START_DATE','ss.START_DATE').") AS START_EPOCH,extract(EPOCH FROM ".db_least('ssm.END_DATE','ss.END_DATE').") AS END_EPOCH";
		$extra['functions'] = array('POINTS'=>'_makeExtraAssnCols','PERCENT_GRADE'=>'_makeExtraAssnCols','LETTER_GRADE'=>'_makeExtraAssnCols','COMMENT'=>'_makeExtraAssnCols');
		$LO_columns += array('POINTS'=>_('Points'),'COMMENT'=>_('Comment'));
	// modif Francois: display percent grade according to Configuration
		if ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>=0)
			$LO_columns['PERCENT_GRADE'] = _('Percent');
	// modif Francois: display letter grade according to Configuration
		if ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<=0)
			$LO_columns['LETTER_GRADE'] = _('Letter');
		$current_RET = DBGet(DBQuery("SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]' AND COURSE_PERIOD_ID='".UserCoursePeriod()."'"),array(),array('STUDENT_ID','ASSIGNMENT_ID'));
	}
	else
	{
		if(count($assignments_RET))
		{
			$extra['SELECT_ONLY'] = "s.STUDENT_ID,     gt.ASSIGNMENT_TYPE_ID,sum(".db_case(array('gg.POINTS',"'-1'","'0'",'gg.POINTS')).") AS PARTIAL_POINTS,sum(".db_case(array('gg.POINTS',"'-1'","'0'",'ga.POINTS')).") AS PARTIAL_TOTAL,    gt.FINAL_GRADE_PERCENT";
			$extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON ((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) AND ga.MARKING_PERIOD_ID='".UserMP()."') LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";
			$extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=cp.COURSE_ID AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))".($_REQUEST['type_id']?" AND ga.ASSIGNMENT_TYPE_ID='$_REQUEST[type_id]'":'');
			if(!$_REQUEST['include_all'])
				$extra['WHERE'] .=" AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";
			$extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";
			$extra['group'] = array('STUDENT_ID');
			$points_RET = GetStuList($extra);
			//echo '<pre>'; var_dump($points_RET); echo '</pre>';
			unset($extra);
			$extra['SELECT'] = ",extract(EPOCH FROM ".db_greatest('ssm.START_DATE','ss.START_DATE').") AS START_EPOCH,extract(EPOCH FROM ".db_least('ssm.END_DATE','ss.END_DATE').") AS END_EPOCH,'' AS POINTS,'' AS PERCENT_GRADE,'' AS LETTER_GRADE";
			$extra['functions'] = array('POINTS'=>'_makeExtraAssnCols','PERCENT_GRADE'=>'_makeExtraAssnCols','LETTER_GRADE'=>'_makeExtraAssnCols');
			$LO_columns['POINTS'] = _('Points');
// modif Francois: display percent grade according to Configuration
			if ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>=0)
				$LO_columns['PERCENT_GRADE'] = _('Percent');
// modif Francois: display letter grade according to Configuration
			if ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<=0)
				$LO_columns['LETTER_GRADE'] = _('Letter');
		}
	}
}

$stu_RET = GetStuList($extra);
//echo '<pre>'; var_dump($stu_RET); echo '</pre>';

//modif Francois: add translation
$type_select = '<script type="text/javascript">var type_idonchange = document.createElement("a"); type_idonchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&include_inactive='.$_REQUEST['include_inactive'].'&include_all='.$_REQUEST['include_all'].($_REQUEST['assignment_id']=='all'?'&assignment_id=all':'').(UserStudentID()?'&student_id='.UserStudentID():'').'&type_id="; type_idonchange.target = "body";</script>';
$type_select .= '<SELECT name="type_id" onchange="type_idonchange.href += this.options[selectedIndex].value; ajaxLink(type_idonchange);"><OPTION value=""'.(!$_REQUEST['type_id']?' SELECTED="SELECTED"':'').'>'._('All').'</OPTION>';
foreach($types_RET as $id=>$type)
	$type_select .= '<OPTION value="'.$id.'"'.($_REQUEST['type_id']==$id?' SELECTED="SELECTED"':'').'>'.$type[1]['TITLE'].'</OPTION>';
$type_select .= '</SELECT>';

$assignment_select = '<script type="text/javascript">var assignment_idonchange = document.createElement("a"); assignment_idonchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&include_inactive='.$_REQUEST['include_inactive'].'&include_all='.$_REQUEST['include_all'].'&type_id='.$_REQUEST['type_id'].'&assignment_id="; assignment_idonchange.target = "body";</script>';
$assignment_select .= '<SELECT name="assignment_id" onchange="assignment_idonchange.href += this.options[selectedIndex].value; ajaxLink(assignment_idonchange);"><OPTION value="">'._('Totals').'</OPTION><OPTION value="all"'.(($_REQUEST['assignment_id']=='all' && !UserStudentID())?' SELECTED="SELECTED"':'').'>'._('All').'</OPTION>';
if(UserStudentID() && $_REQUEST['assignment_id']=='all')
	$assignment_select .= '<OPTION value="all" SELECTED="SELECTED">'.$stu_RET[1]['FULL_NAME'].'</OPTION>';
foreach($assignments_RET as $id=>$assignment)
	$assignment_select .= '<OPTION value="'.$id.'"'.($_REQUEST['assignment_id']==$id?' SELECTED="SELECTED"':'').'>'.($_REQUEST['type_id']?'':$types_RET[$assignment[1]['ASSIGNMENT_TYPE_ID']][1]['TITLE'].' - ').$assignment[1]['TITLE'].'</OPTION>';
$assignment_select .= '</SELECT>';

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&student_id='.UserStudentID().'" method="POST">';

$tabs = array(array('title'=>_('All'),'link'=>"Modules.php?modname=$_REQUEST[modname]&type_id=".($_REQUEST['assignment_id']=='all'?'&assignment_id=all':'').(UserStudentID()?'&student_id='.UserStudentID():'')."&include_inactive=$_REQUEST[include_inactive]&include_all=$_REQUEST[include_all]"));
foreach($types_RET as $id=>$type)
	$tabs[] = array('title'=>$type[1]['TITLE'].($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'?'|'.number_format(100*$type[1]['FINAL_GRADE_PERCENT'],0).'%':''),'link'=>"Modules.php?modname=$_REQUEST[modname]&type_id=$id".($_REQUEST['assignment_id']=='all'?'&assignment_id=all':'').(UserStudentID()?'&student_id='.UserStudentID():'')."&include_inactive=$_REQUEST[include_inactive]&include_all=$_REQUEST[include_all]")+($type[1]['COLOR']?array('color'=>$type[1]['COLOR']):array());

//modif Francois: add label on checkbox
DrawHeader($type_select.$assignment_select,$_REQUEST['assignment_id']?SubmitButton(_('Save')):'');
DrawHeader('<label>'.CheckBoxOnclick('include_inactive').'&nbsp;'._('Include Inactive Students').'</label> &nbsp;<label>'.CheckBoxOnclick('include_all').'&nbsp;'._('Include Inactive Assignments').'</label>');
if($_REQUEST['assignment_id'] && $_REQUEST['assignment_id']!='all')
{
    $assigned_date = $assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNED_DATE'];
    $due_date = $assignments_RET[$_REQUEST['assignment_id']][1]['DUE_DATE'];
    $due = $assignments_RET[$_REQUEST['assignment_id']][1]['DUE'];
    DrawHeader('<b>'.Localize('colon',_('Assigned Date')).'</b> '.($assigned_date?ProperDate($assigned_date):_('N/A')).', <b>'.Localize('colon',_('Due Date')).'</b> '.($due_date?ProperDate($due_date):_('N/A')).($due?' - <b>'._('Assignment is Due').'</b>':''));
}

if($_REQUEST['type_id'] && $types_RET[$_REQUEST['type_id']][1]['COLOR'])
	$LO_options['header_color'] = $types_RET[$_REQUEST['type_id']][1]['COLOR'];
if(!UserStudentID() && $_REQUEST['assignment_id']=='all')
	$LO_options['yscroll'] = true;

$LO_options['header'] = WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&type_id=".($_REQUEST['type_id']?$_REQUEST['type_id']:($_REQUEST['assignment_id'] && $_REQUEST['assignment_id']!='all'?$assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNMENT_TYPE_ID']:'')).($_REQUEST['assignment_id']=='all'?'&assignment_id=all':'').(UserStudentID()?'&student_id='.UserStudentID():'')."&include_inactive=$_REQUEST[include_inactive]&include_all=$_REQUEST[include_all]");
echo '<BR />';
if (UserStudentID())
	ListOutput($stu_RET,$LO_columns,'Assignment','Assignments',$link,array(),$LO_options);
else
	ListOutput($stu_RET,$LO_columns,'Student','Students',$link,array(),$LO_options);

echo $_REQUEST['assignment_id']?'<span class="center">'.SubmitButton(_('Save')).'</span>':'';
echo '</FORM>';

function _makeExtraAssnCols($assignment_id,$column)
{	global $THIS_RET,$assignments_RET,$current_RET,$points_RET,$tabindex,$max_allowed,$total,$programconfig;

	switch($column)
	{
		case 'POINTS':
			$tabindex++;
			if(!$assignment_id)
			{
				$total = $total_points = 0;
				if(count($points_RET[$THIS_RET['STUDENT_ID']]))
				{
					foreach($points_RET[$THIS_RET['STUDENT_ID']] as $partial_points)
						if($partial_points['PARTIAL_TOTAL']!=0 || $programconfig[User('STAFF_ID')]['WEIGHT']!='Y')
						{
							$total += $partial_points['PARTIAL_POINTS'];
							$total_points += $partial_points['PARTIAL_TOTAL'];
						}
				}

//				return '<TABLE cellspacing=0 cellpadding=0><TR><TD>'.$total.'</TD><TD>&nbsp;/&nbsp;</TD><TD>'.$total_points.'</TD></TR></TABLE>';
				return $total.'&nbsp;/&nbsp;'.$total_points;
			}
			else
			{
				if($_REQUEST['include_all'] || ($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS']!='' || !$assignments_RET[$assignment_id][1]['DUE_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']>=$THIS_RET['START_EPOCH'] && (!$THIS_RET['END_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']<=$THIS_RET['END_EPOCH'])))
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];
					if($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS']=='-1')
						$points = '*';
					elseif(mb_strpos($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'],'.'))
						$points = rtrim(rtrim($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'],'0'),'.');
					else
						$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];

//					return '<TABLE cellspacing=0 cellpadding=1><TR><TD>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'</TD><TD>&nbsp;/&nbsp;</TD><TD>'.$total_points.'</TD></TR></TABLE>';
					return TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex, false).'&nbsp;/&nbsp;'.$total_points;
				}
			}
		break;

		case 'PERCENT_GRADE':
			if(!$assignment_id)
			{
				$total = $total_percent = 0;
				if(count($points_RET[$THIS_RET['STUDENT_ID']]))
				{
					foreach($points_RET[$THIS_RET['STUDENT_ID']] as $partial_points)
						if($partial_points['PARTIAL_TOTAL']!=0 || $programconfig[User('STAFF_ID')]['WEIGHT']!='Y')
						{
							$total += $partial_points['PARTIAL_POINTS']*($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'?$partial_points['FINAL_GRADE_PERCENT']/$partial_points['PARTIAL_TOTAL']:1);
							$total_percent += ($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'?$partial_points['FINAL_GRADE_PERCENT']:$partial_points['PARTIAL_TOTAL']);
						}
					if($total_percent!=0)
						$total /= $total_percent;
				}

				return ($total>$max_allowed?'<span style="color:red">':'').Percent($total,0).($total>$max_allowed?'</span>':'');
			}
			else
			{
				if($_REQUEST['include_all'] || ($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS']!='' || !$assignments_RET[$assignment_id][1]['DUE_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']>=$THIS_RET['START_EPOCH'] && (!$THIS_RET['END_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']<=$THIS_RET['END_EPOCH'])))
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];
					$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];
					if($total_points!=0)
						if($points!='-1')
							return ($assignments_RET[$assignment_id][1]['DUE']||$points!=''?($points>$total_points*$max_allowed?'<span style="color:red">':''):'<span style="color:gray">').Percent($points/$total_points,0).($assignments_RET[$assignment_id][1]['DUE']||$points!=''?($points>$total_points*$max_allowed?'</span>':''):'');
						else
							return _('N/A');
					else
						return 'E/C';
				}
			}
		break;

		case 'LETTER_GRADE':
			if(!$assignment_id)
			{
				return '<B>'._makeLetterGrade($total).'</B>';
			}
			else
			{
				if($_REQUEST['include_all'] || ($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS']!='' || !$assignments_RET[$assignment_id][1]['DUE_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']>=$THIS_RET['START_EPOCH'] && (!$THIS_RET['END_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']<=$THIS_RET['END_EPOCH'])))
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];
					$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];
					if($total_points!=0)
						if($points!='-1')
							return ($assignments_RET[$assignment_id][1]['DUE']||$points!=''?'':'<span style="color:gray">').'<B>'._makeLetterGrade($points/$total_points).'</B>'.($assignments_RET[$assignment_id][1]['DUE']||$points!=''?'':'</span>');
						else
							return _('N/A');
					else
						return _('N/A');
				}
			}
		break;

		case 'COMMENT':
			if(!$assignment_id)
			{
			}
			else
			{
				if($_REQUEST['include_all'] || ($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS']!='' || !$assignments_RET[$assignment_id][1]['DUE_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']>=$THIS_RET['START_EPOCH'] && (!$THIS_RET['END_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']<=$THIS_RET['END_EPOCH'])))
				{
					return TextInput($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['COMMENT'],'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][COMMENT]','',' maxlength=100 tabindex='.(500+$tabindex));
				}
			}
		break;
	}

}

function _makeExtraStuCols($value,$column)
{	global $THIS_RET,$assignment_count,$count_assignments,$max_allowed;

	switch($column)
	{
		case 'POINTS':
			$assignment_count++;
			$tabindex = $assignment_count;

			if($value=='-1')
				$value = '*';
			elseif(mb_strpos($value,'.'))
				$value = rtrim(rtrim($value,'0'),'.');

//			return '<TABLE cellspacing=0 cellpadding=1><TR><TD>'.TextInput($value,'values['.$THIS_RET['STUDENT_ID'].']['.$THIS_RET['ASSIGNMENT_ID'].'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'</TD><TD>&nbsp;/&nbsp;</TD><TD>'.$THIS_RET['TOTAL_POINTS'].'</TD></TR></TABLE>';
			return TextInput($value,'values['.$THIS_RET['STUDENT_ID'].']['.$THIS_RET['ASSIGNMENT_ID'].'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex, false).'&nbsp;/&nbsp;'.$THIS_RET['TOTAL_POINTS'];
		break;

		case 'PERCENT_GRADE':
			if($THIS_RET['TOTAL_POINTS']!=0)
				if($THIS_RET['POINTS']!='-1')
					return ($THIS_RET['DUE']||$THIS_RET['POINTS']!=''?($THIS_RET['POINTS']>$THIS_RET['TOTAL_POINTS']*$max_allowed?'<span style="color:red">':''):'<span style="color:gray">').Percent($THIS_RET['POINTS']/$THIS_RET['TOTAL_POINTS'],0).($THIS_RET['DUE']||$THIS_RET['POINTS']!=''?($THIS_RET['POINTS']>$THIS_RET['TOTAL_POINTS']*$max_allowed?'</span>':''):'');
				else
					return _('N/A');
			else
				return 'E/C';
		break;

		case 'LETTER_GRADE':
			if($THIS_RET['TOTAL_POINTS']!=0)
				if($THIS_RET['POINTS']!='-1')
					return ($THIS_RET['DUE']||$THIS_RET['POINTS']!=''?'':'<span style="color:gray">').'<B>'._makeLetterGrade($THIS_RET['POINTS']/$THIS_RET['TOTAL_POINTS']).'</B>'.($THIS_RET['DUE']||$THIS_RET['POINTS']!=''?'':'</span>');
				else
					return _('N/A');
			else
				return _('N/A');
		break;

		case 'COMMENT':
			$tabindex += $count_assignments;

			return TextInput($value,'values['.$THIS_RET['STUDENT_ID'].']['.$THIS_RET['ASSIGNMENT_ID'].'][COMMENT]','',' maxlength=100 tabindex='.$tabindex);
		break;
	}
}

function _makeExtraCols($assignment_id,$column)
{	global $THIS_RET,$assignments_RET,$current_RET,$old_student_id,$student_count,$tabindex,$count_students,$max_allowed,$program_config;

	if($THIS_RET['STUDENT_ID']!=$old_student_id)
	{
		$student_count++;
		$tabindex=$student_count;
		$old_student_id = $THIS_RET['STUDENT_ID'];
	}
	else
		$tabindex += $count_students;
	$total_points = $assignments_RET[$assignment_id][1]['POINTS'];

	if($_REQUEST['include_all'] || ($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS']!='' || !$assignments_RET[$assignment_id][1]['DUE_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']>=$THIS_RET['START_EPOCH'] && (!$THIS_RET['END_EPOCH'] || $assignments_RET[$assignment_id][1]['DUE_EPOCH']<=$THIS_RET['END_EPOCH'])))
	{
		if($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS']=='-1')
			$points = '*';
		elseif(mb_strpos($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'],'.'))
			$points = rtrim(rtrim($current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'],'0'),'.');
		else
			$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];

		if($total_points!=0)
			if($points!='*')
//				return '<TABLE cellspacing=0 cellpadding=0><TR align=center><TD>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'<HR>'.$total_points.'</TD><TD>&nbsp;'.($assignments_RET[$assignment_id][1]['DUE']||$points!=''?($points>$total_points*$max_allowed?'<span style="color:red">':''):'<span style="color:gray">').Percent($points/$total_points,0).($assignments_RET[$assignment_id][1]['DUE']||$points!=''?($points>$total_points*$max_allowed?'</span>':''):'').'<BR />&nbsp;<B>'._makeLetterGrade($points/$total_points).'</B>'.'</TD></TR></TABLE>';
// modif Francois: display letter grade according to Configuration
				return TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex, false).'&nbsp;/&nbsp;'.$total_points.($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<=0 ? '' : '&nbsp;&minus;&nbsp;'.($assignments_RET[$assignment_id][1]['DUE']||$points!=''?($points>$total_points*$max_allowed?'<span style="color:red">':''):'<span style="color:gray">').Percent($points/$total_points,0).($assignments_RET[$assignment_id][1]['DUE']||$points!=''?($points>$total_points*$max_allowed?'</span>':''):'')).($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>=0 ? '' : '&nbsp;&minus;&nbsp;<B>'._makeLetterGrade($points/$total_points).'</B>');
			else
//				return '<TABLE cellspacing=0 cellpadding=1><TR align=center><TD>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'<HR>'.$total_points.'</TD><TD>&nbsp;'._('N/A').'<BR />&nbsp;'._('N/A').'</TD></TR></TABLE>';
				return TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex, false).'&nbsp;/&nbsp;'.$total_points.'&nbsp;&minus;&nbsp;'._('N/A').'&nbsp;&minus;&nbsp;'._('N/A').'</TD></TR></TABLE>';
		else
			return '<TABLE class="cellpadding-1 cellspacing-0"><TR class="center"><TD>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'<HR>'.$total_points.'</TD><TD>&nbsp;E/C</TD></TR></TABLE>';
	}
}
?>
