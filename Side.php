<?php
error_reporting(1);
include "./Warehouse.php";

$tmp_REQUEST = $_REQUEST;
$_SESSION['Side_PHP_SELF'] = "Side.php";

$old_school = UserSchool();
$old_syear = UserSyear();
$old_period = UserCoursePeriod();

$addJavascripts = '';

if($_REQUEST['school'] && $_REQUEST['school']!=$old_school)
{
	unset($_SESSION['student_id']);
	$_SESSION['unset_student'] = true;
	unset($_SESSION['staff_id']);
	unset($_SESSION['UserMP']);
	unset($_REQUEST['mp']);
}

if($_REQUEST['modfunc']=='update' && $_POST)
{
	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && $_REQUEST['school']!=$old_school)
	{
		$_SESSION['UserSchool'] = $_REQUEST['school'];
		DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID='".UserSchool()."' WHERE STAFF_ID='".User('STAFF_ID')."'");
	}

	$_SESSION['UserSyear'] = $_REQUEST['syear'];
	$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];
	$_SESSION['UserMP'] = $_REQUEST['mp'];
	if(User('PROFILE')=='parent')
	{
		if($_SESSION['student_id']!=$_REQUEST['student_id'])
			unset($_SESSION['UserMP']);
		$_SESSION['student_id'] = $_REQUEST['student_id'];
	}
	$addJavascripts .= 'var body_link = document.createElement("a"); body_link.href = "'.str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'])).'"; body_link.target = "body"; ajaxLink(body_link);';
}

if(!$_SESSION['UserSyear'])
	$_SESSION['UserSyear'] = Config('SYEAR');

if(!$_SESSION['student_id'] && User('PROFILE')=='student')
	$_SESSION['student_id'] = $_SESSION['STUDENT_ID'];
//if(!$_SESSION['staff_id'] && User('PROFILE')!='admin' && User('PROFILE')!='teacher')
if(!$_SESSION['staff_id'] && User('PROFILE')=='parent')
	$_SESSION['staff_id'] = $_SESSION['STAFF_ID'];

if(!$_SESSION['UserSchool'])
{
	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && (!User('SCHOOLS') || mb_strpos(User('SCHOOLS'),','.User('CURRENT_SCHOOL_ID').',')!==false))
		$_SESSION['UserSchool'] = User('CURRENT_SCHOOL_ID');
	elseif(User('PROFILE')=='student')
		$_SESSION['UserSchool'] = trim(User('SCHOOLS'),',');
}
UpdateSchoolArray(UserSchool());

if((!$_SESSION['UserMP'] || ($_REQUEST['school'] && $_REQUEST['school']!=$old_school) || ($_REQUEST['syear'] && $_REQUEST['syear']!=$old_syear)) && User('PROFILE')!='parent')
	$_SESSION['UserMP'] = GetCurrentMP('QTR',DBDate(),false);

if(($_REQUEST['school'] && $_REQUEST['school']!=$old_school) || ($_REQUEST['syear'] && $_REQUEST['syear']!=$old_syear))
{
	unset($_SESSION['UserPeriod']);
	unset($_SESSION['UserCoursePeriod']);
}

if($_REQUEST['student_id']=='new')
{
	unset($_SESSION['student_id']);
	unset($_SESSION['_REQUEST_vars']['student_id']);
	unset($_SESSION['_REQUEST_vars']['search_modfunc']);
	$addJavascripts .= 'var body_link = document.createElement("a"); body_link.href = "'.str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('advanced'))).'"; body_link.target = "body"; ajaxLink(body_link);';
}
if($_REQUEST['staff_id']=='new')
{
	unset($_SESSION['staff_id']);
	unset($_SESSION['_REQUEST_vars']['staff_id']);
	unset($_SESSION['_REQUEST_vars']['search_modfunc']);
	$addJavascripts .= 'var body_link = document.createElement("a"); body_link.href = "'.str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('advanced'))).'"; body_link.target = "body"; ajaxLink(body_link);';
}
unset($_REQUEST['modfunc']);

echo '<script type="text/javascript">'.$addJavascripts.'openMenu(modname);</script>';
?>
<div id="menushadow"></div>
<?php
// User Information
echo '<TABLE class="width-100p cellspacing-0 cellpadding-0"><TR><TD>';
echo '</TD></TR><TR>';
//modif Francois: strftime for locale date date('l F j, Y')
echo '<TD class="width-100p valign-top">';
echo '<A HREF="index.php" target="_top"><img src="assets/themes/'.Preferences('THEME').'/logo.png" id="SideLogo" /></A>';
echo '<FORM action="Side.php?modfunc=update" method="POST" target="menu">
	<INPUT type="hidden" name="modname" value="" id="modname_input">
	&nbsp;<b>'.User('NAME')."</b><BR />
	&nbsp;".mb_convert_case(iconv('','UTF-8',strftime('%A %B %d, %Y')), MB_CASE_TITLE, "UTF-8")."<BR />";
if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
{
	$schools = mb_substr(str_replace(",","','",User('SCHOOLS')),2,-2);
	$QI = DBQuery("SELECT ID,TITLE,SHORT_NAME FROM SCHOOLS WHERE SYEAR='".UserSyear()."'".($schools?" AND ID IN ($schools)":''));
	$RET = DBGet($QI);

	if(!UserSchool())
	{
		$_SESSION['UserSchool'] = $RET[1]['ID'];
		DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID='".UserSchool()."' WHERE STAFF_ID='".User('STAFF_ID')."'");
	}

	echo '<SELECT name="school" onChange="ajaxPostForm(this.form,true);" style="width:180px;">';
	foreach($RET as $school)
		echo '<OPTION value="'.$school[ID].'"'.((UserSchool()==$school['ID'])?' SELECTED="SELECTED"':'').">".($school['SHORT_NAME']?$school['SHORT_NAME']:$school['TITLE']).'</OPTION>';

	echo '</SELECT><BR />';
}

if(User('PROFILE')=='parent')
{
	$RET = DBGet(DBQuery("SELECT sju.STUDENT_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,se.SCHOOL_ID FROM STUDENTS s,STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE s.STUDENT_ID=sju.STUDENT_ID AND sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR='".UserSyear()."' AND se.STUDENT_ID=sju.STUDENT_ID AND ('".DBDate()."'>=se.START_DATE AND ('".DBDate()."'<=se.END_DATE OR se.END_DATE IS NULL))"));

	if(!UserStudentID())
		$_SESSION['student_id'] = $RET[1]['STUDENT_ID'];

	echo '<SELECT name="student_id" onChange="ajaxPostForm(this.form,true);">';
	if(count($RET))
	{
		foreach($RET as $student)
		{
			echo '<OPTION value="'.$student['STUDENT_ID'].'"'.((UserStudentID()==$student['STUDENT_ID'])?' SELECTED="SELECTED"':'').">".$student['FULL_NAME'].'</OPTION>';
			if(UserStudentID()==$student['STUDENT_ID'])
				$_SESSION['UserSchool'] = $student['SCHOOL_ID'];
		}
	}
	echo '</SELECT><BR />';

	if(!UserMP() || UserSchool()!=$old_school || UserSyear()!=$old_syear)
		$_SESSION['UserMP'] = GetCurrentMP('QTR',DBDate(),false);
}

if(1)
{
	if(User('PROFILE')!='student')
		$sql = "SELECT sy.SYEAR FROM SCHOOLS sy,STAFF s WHERE sy.ID='$_SESSION[UserSchool]' AND s.SYEAR=sy.SYEAR AND (s.SCHOOLS IS NULL OR position(','||sy.ID||',' IN s.SCHOOLS)>0) AND s.USERNAME=(SELECT USERNAME FROM STAFF WHERE STAFF_ID='".$_SESSION['STAFF_ID']."')";
	else
		//modif Francois: limit school years to the years the student was enrolled
		//$sql = "SELECT DISTINCT sy.SYEAR FROM SCHOOLS sy,STUDENT_ENROLLMENT s WHERE s.SYEAR=sy.SYEAR";
		$sql = "SELECT DISTINCT sy.SYEAR FROM SCHOOLS sy,STUDENT_ENROLLMENT s WHERE s.SYEAR=sy.SYEAR AND s.STUDENT_ID='".$_SESSION['student_id']."'";
	$sql .= " ORDER BY sy.SYEAR DESC";
	$years_RET = DBGet(DBQuery($sql));
}
else
	$years_RET = array(1=>array('SYEAR'=>Config('SYEAR')));

echo '<SELECT name="syear" onChange="ajaxPostForm(this.form,true);">';
foreach($years_RET as $year)
//modif Francois: school year over one/two calendar years format
//	echo '<OPTION value="'.$year['SYEAR'].'"'.((UserSyear()==$year['SYEAR'])?' SELECTED="SELECTED"':'').'>'.$year['SYEAR'].'-'.($year['SYEAR']+1).'</OPTION>';
	echo '<OPTION value="'.$year['SYEAR'].'"'.((UserSyear()==$year['SYEAR'])?' SELECTED="SELECTED"':'').'>'.FormatSyear($year['SYEAR'],Config('SCHOOL_SYEAR_OVER_2_YEARS')).'</OPTION>';
echo '</SELECT><BR />';

$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER"));
echo '<SELECT name="mp" onChange="ajaxPostForm(this.form,true);">';
if(count($RET))
{
	if(!UserMP())
		$_SESSION['UserMP'] = $RET[1]['MARKING_PERIOD_ID'];

	foreach($RET as $quarter)
		echo '<OPTION value="'.$quarter['MARKING_PERIOD_ID'].'"'.(UserMP()==$quarter['MARKING_PERIOD_ID']?' SELECTED="SELECTED"':'').'>'.$quarter['TITLE'].'</OPTION>';
}
echo '</SELECT>';

if(User('PROFILE')=='teacher')
{
	//modif Francois: multiple school periods for a course period
	//$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM COURSE_PERIODS cp, SCHOOL_PERIODS sp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".User('STAFF_ID')."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER");
	$QI = DBQuery("SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cpsp.DAYS,c.TITLE AS COURSE_TITLE, cp.SHORT_NAME AS CP_SHORT_NAME FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND c.COURSE_ID=cp.COURSE_ID AND cpsp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".User('STAFF_ID')."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY cp.SHORT_NAME, sp.SORT_ORDER");
//modif Francois: add subject areas
	$RET = DBGet($QI, array('COURSE_TITLE'=>'CourseTitle'));
	// get the fy marking period id, there should be exactly one fy marking period per school
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
		unset($_SESSION['student_id']);
		$_SESSION['unset_student'] = true;
	}

	echo '<BR /><SELECT name="period" onChange="ajaxPostForm(this.form,true);" style="width:180px;">';
	$optgroup = FALSE;
	foreach($RET as $period)
	{
		//modif Francois: add optroup to group periods by course periods
		if (!empty($period['COURSE_TITLE']) && $optgroup!=$period['COURSE_TITLE']) //new optgroup
		{
			echo '<optgroup label="'.$period['COURSE_TITLE'].'">';
			$optgroup = $period['COURSE_TITLE'];
		}
		if ($optgroup!==FALSE && $optgroup!=$period['COURSE_TITLE']) //close optgroup
			echo '</optgroup>';
		
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
		
		$period_days = '';
		for ($i=0; $i<mb_strlen($period['DAYS']); $i++)
		{
			$period_days .= mb_substr($days_convert[$period['DAYS'][$i]],0,3).'.';
		}
		echo '<OPTION value="'.$period['COURSE_PERIOD_ID'].'.'.$period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'"'.$selected.'>'.$period['TITLE'].(mb_strlen($period['DAYS'])<5?(mb_strlen($period['DAYS'])<2?' '._('Day').' '.$period_days.' - ':' '._('Days').' '.$period_days.' - '):' - ').($period['MARKING_PERIOD_ID']!=$fy_RET[1]['MARKING_PERIOD_ID']?GetMP($period['MARKING_PERIOD_ID'],'SHORT_NAME').' - ':'').$period['CP_SHORT_NAME'].'</OPTION>';
	}
	if(!$found)
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
		$_SESSION['UserPeriod'] = $RET[1]['PERIOD_ID'];
		unset($_SESSION['student_id']);
		$_SESSION['unset_student'] = true;
	}
	echo '</SELECT>';
}
echo '</FORM>';

if(UserStudentID() && (User('PROFILE')=='admin' || User('PROFILE')=='teacher'))
{
	$sql = "SELECT FIRST_NAME||' '||coalesce(MIDDLE_NAME,' ')||' '||LAST_NAME||' '||coalesce(NAME_SUFFIX,' ') AS FULL_NAME FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'";
	$RET = DBGet(DBQuery($sql));
	echo '<TABLE class="width-100p cellspacing-0 cellpadding-0" style="background-color:#333366;"><TR><TD><A HREF="Side.php?student_id=new" target="menu"><IMG SRC="assets/x_button.png" height="24" style="vertical-align: middle;"></A></TD><TD><B>'.(AllowUse('Students/Student.php')?'<A HREF="Modules.php?modname=Students/Student.php&student_id='.UserStudentID().'">':'').'<span style="color:white" class="size-2">'.$RET[1]['FULL_NAME'].'</span>'.(AllowUse('Students/Student.php')?'</A>':'').'</B></TD></TR></TABLE>';
}
if(UserStaffID() && (User('PROFILE')=='admin' || User('PROFILE')=='teacher'))
{
	if(UserStudentID())
		echo '<div style="height:5px;"></div>';
	$sql = "SELECT FIRST_NAME||' '||LAST_NAME AS FULL_NAME FROM STAFF WHERE STAFF_ID='".UserStaffID()."'";
	$RET = DBGet(DBQuery($sql));
	echo '<TABLE class="width-100p cellspacing-0 cellpadding-0" style="background-color:'.(UserStaffID()==User('STAFF_ID')?'#663333':'#336633').';"><TR><TD><A HREF="Side.php?staff_id=new" target="menu"><IMG SRC="assets/x_button.png" height="24" style="vertical-align: middle;"></A></TD><TD><B>'.(AllowUse('Users/User.php')?'<A HREF="Modules.php?modname=Users/User.php&staff_id='.UserStaffID().'">':'').'<span style="color:white" class="size-2">'.$RET[1]['FULL_NAME'].'</span>'.(AllowUse('Users/User.php')?'</A>':'').'</B></TD></TR></TABLE>';
}
//modif Francois: css WPadmin
echo '<BR /><div id="adminmenu">';


// Program Information
require('Menu.php');
foreach($_ROSARIO['Menu'] as $modcat=>$programs)
{
	if(count($_ROSARIO['Menu'][$modcat]))
	{
		$keys = array_keys($_ROSARIO['Menu'][$modcat]);

		echo '<A href="Modules.php?modname='.$modcat.'/Search.php" class="menu-top"><IMG SRC="assets/icons/'.$modcat.'.png" height="32" style="vertical-align:middle;">&nbsp;'._(str_replace('_',' ',$modcat)).'</A><DIV id="menu_'.$modcat.'" class="wp-submenu"><TABLE class="width-100p cellspacing-0 cellpadding-0">';
		//foreach($_ROSARIO['Menu'][$modcat] as $file=>$title)
		foreach($keys as $key_index=>$file)
		{
			$title = $_ROSARIO['Menu'][$modcat][$file];
			if(mb_stripos($file,'http://') !== false)
				echo '<TR><TD><A HREF="'.$file.'" target="_blank">'.$title.'</A></TD></TR>';
			elseif(!is_numeric($file))
				echo '<TR><TD><A HREF="Modules.php?modname='.$file.'" onclick="modname=\''.$file.'\'; selMenuA(modname);"'.(mb_stripos($file,'_ROSARIO_PDF') !== false ? ' target="_blank"' : '').'>'.$title.'</A></TD></TR>';
			elseif($keys[$key_index+1] && !is_numeric($keys[$key_index+1]))
				echo '<TR><TD style="height:3px;"></TD></TR><TR><TD class="menu-inter">&nbsp;'.$title.'</TD></TR>';
		}
		echo '</TABLE></DIV>';

	}
}
//modif Francois: fin css WPadmin
echo '</div>';//id="adminmenu"
//modif Francois: Javascript load optimization
?>
</TD></TR></TABLE>