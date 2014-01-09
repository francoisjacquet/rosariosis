<?php
$_REQUEST['modname'] = $_REQUEST['modname'].'&include='.$_REQUEST['include'];
//modif Francois: Bugfix $_REQUEST['include'] 2 times in links
$REQUEST_include = $_REQUEST['include'];
unset($_REQUEST['include']);
DrawHeader(_('Teacher Programs').' - '.ProgramTitle($_REQUEST['modname']));

if(UserStaffID())
{
	$profile = DBGet(DBQuery("SELECT PROFILE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
	if($profile[1]['PROFILE']!='teacher')
	{
		unset($_SESSION['staff_id']);
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
	}
}

$extra['profile'] = 'teacher';
Search('staff_id',$extra);

if(UserStaffID())
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
	//modif Francois: multiple school periods for a course period
	//$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".UserStaffID()."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER");
	$QI = DBQuery("SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cpsp.DAYS,c.TITLE AS COURSE_TITLE, cp.SHORT_NAME AS CP_SHORT_NAME FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND c.COURSE_ID=cp.COURSE_ID AND cpsp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".UserStaffID()."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY cp.SHORT_NAME, sp.SORT_ORDER");
	$RET = DBGet($QI);
	// get the fy marking period id, there should be exactly one fy marking period
	$fy_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));

	if($_REQUEST['period'])
	{
		list($CoursePeriod, $CoursePeriodSchoolPeriod) = explode('.', $_REQUEST['period']);
		$_SESSION['UserCoursePeriod'] = $CoursePeriod;
		$_SESSION['UserCoursePeriodSchoolPeriod'] = $CoursePeriodSchoolPeriod;
	}

	if(!UserCoursePeriod())
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
		$_SESSION['UserCoursePeriodSchoolPeriod'] = $RET[1]['COURSE_PERIOD_SCHOOL_PERIODS_ID'];
	}

	$period_select = '<SELECT name="period" onChange="ajaxPostForm(this.form,true);">';
	$optgroup = FALSE;
	foreach($RET as $period)
	{
		//modif Francois: add optroup to group periods by course periods
		if (!empty($period['COURSE_TITLE']) && $optgroup!=$period['COURSE_TITLE']) //new optgroup
		{
			$period_select .= '<optgroup label="'.CourseTitle($period['COURSE_TITLE']).'">';
			$optgroup = $period['COURSE_TITLE'];
		}
		if ($optgroup!==FALSE && $optgroup!=$period['COURSE_TITLE']) //close optgroup
			$period_select .= '</optgroup>';

			//if(UserCoursePeriod()==$period['COURSE_PERIOD_ID'])
		if(UserCoursePeriodSchoolPeriod()==$period['COURSE_PERIOD_SCHOOL_PERIODS_ID'])
		{
			$selected = ' SELECTED="SELECTED"';
			$_SESSION['UserPeriod'] = $period['PERIOD_ID'];
			$found = true;
		}
		else
			$selected = '';

		//modif Francois: days display to locale						
		$days_convert = array('U'=>_('Sunday'),'M'=>_('Monday'),'T'=>_('Tuesday'),'W'=>_('Wednesday'),'H'=>_('Thursday'),'F'=>_('Friday'),'S'=>_('Saturday'));
		//modif Francois: days numbered
		if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
			$days_convert = array('U'=>'7','M'=>'1','T'=>'2','W'=>'3','H'=>'4','F'=>'5','S'=>'6');

		$period_DAYS_locale = '';
		for ($i = 0; $i < mb_strlen($period['DAYS']); $i++) {
			$period_DAYS_locale .= mb_substr($days_convert[mb_substr($period['DAYS'], $i, 1)],0,3) . '.';
		}
		
		//modif Francois: add subject areas
		//$period_select .= '<OPTION value="'.$period['COURSE_PERIOD_ID'].'.'.$period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'"'.$selected.'>'.$period['SHORT_NAME'].(mb_strlen($period['DAYS'])<5?' ('.$period_DAYS_locale.')':'').($period['MARKING_PERIOD_ID']!=$fy_RET[1]['MARKING_PERIOD_ID']?' '.GetMP($period['MARKING_PERIOD_ID'],'SHORT_NAME'):'').' - '.$period['CP_SHORT_NAME'].'</OPTION>';
		$period_select .= '<OPTION value="'.$period['COURSE_PERIOD_ID'].'.'.$period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'"'.$selected.'>'.$period['TITLE'].(mb_strlen($period['DAYS'])<5?(mb_strlen($period['DAYS'])<2?' '._('Day').' '.$period_DAYS_locale.' - ':' '._('Days').' '.$period_DAYS_locale.' - '):' - ').($period['MARKING_PERIOD_ID']!=$fy_RET[1]['MARKING_PERIOD_ID']?GetMP($period['MARKING_PERIOD_ID'],'SHORT_NAME').' - ':'').$period['CP_SHORT_NAME'].'</OPTION>';

	}
	if(!$found)
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
//modif Francois: fix bug SQL no course period in the user period
		if (empty($RET[1]['COURSE_PERIOD_ID']))
		{
			$_SESSION['UserCoursePeriod'] = 0;
			$_SESSION['UserCoursePeriodSchoolPeriod'] = 0;
			$period_select .= '<OPTION value="">'. sprintf(_('No %s were found.'), _('Course Period')).'</OPTION>';
		}
		$_SESSION['UserPeriod'] = $RET[1]['PERIOD_ID'];
	}
	$period_select .= '</SELECT>';

	DrawHeader($period_select);
	echo '</FORM><BR />';
	unset($_ROSARIO['DrawHeader']);
	$_ROSARIO['HeaderIcon'] = false;

	$_ROSARIO['allow_edit'] = AllowEdit($_REQUEST['modname']);
	$_ROSARIO['User'] = array(0=>$_ROSARIO['User'][1],1=>array('STAFF_ID'=>UserStaffID(),'NAME'=>GetTeacher(UserStaffID()),'USERNAME'=>GetTeacher(UserStaffID(),'','USERNAME'),'PROFILE'=>'teacher','SCHOOLS'=>','.UserSchool().',','SYEAR'=>UserSyear()));

	echo '<div style="border:1px solid #000000; margin:0 auto; padding: 1px; width:100%;">';

	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//modif Francois: Bugfix $_REQUEST['include'] 2 times in links
	if (mb_substr($REQUEST_include, -4, 4)!='.php' || mb_strpos($REQUEST_include, '..')!==false || !is_file('modules/'.$REQUEST_include))	
		HackingLog();
	else
		include('modules/'.$REQUEST_include);

	echo '</div>';
}
?>