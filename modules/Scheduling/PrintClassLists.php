<?php
if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['cp_arr']))
	{
		$cp_list = '\''.implode('\',\'',$_REQUEST['cp_arr']).'\'';

		$extra['DATE'] = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS START_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		$extra['DATE'] = $extra['DATE'][1]['START_DATE'];
		if(!$extra['DATE'] || DBDate('postgres')>$extra['DATE'])
			$extra['DATE'] = DBDate();

		// get the fy marking period id, there should be exactly one fy marking period
		$fy_id = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		$fy_id = $fy_id[1]['MARKING_PERIOD_ID'];

		//modif Francois: multiple school periods for a course period
		//modif Francois: add subject areas
		$course_periods_RET = DBGet(DBQuery("SELECT cp.TITLE,cp.COURSE_PERIOD_ID,cp.TITLE,cp.MARKING_PERIOD_ID,cp.MP,c.TITLE AS COURSE_TITLE,cp.TEACHER_ID,(SELECT LAST_NAME||', '||FIRST_NAME FROM STAFF WHERE STAFF_ID=cp.TEACHER_ID) AS TEACHER FROM COURSE_PERIODS cp,COURSES c 
		WHERE 
		c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID IN ($cp_list) ORDER BY TEACHER"), array('COURSE_TITLE'=>'CourseTitle'));

		$first_extra = $extra;
		$handle = PDFStart();
		$PCL_UserCoursePeriod = $_SESSION['UserCoursePeriod']; // save/restore for teachers
		foreach($course_periods_RET as $teacher_id=>$course_period)
		{
			unset($_ROSARIO['DrawHeader']);
			DrawHeader(_('Class List'));
			/*//modif Francois: days display to locale						
			$days_convert = array('U'=>_('Sunday'),'M'=>_('Monday'),'T'=>_('Tuesday'),'W'=>_('Wednesday'),'H'=>_('Thursday'),'F'=>_('Friday'),'S'=>_('Saturday'));
			//modif Francois: days numbered
			if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
				$days_convert = array('U'=>'7','M'=>'1','T'=>'2','W'=>'3','H'=>'4','F'=>'5','S'=>'6');

			$course_period_DAYS_locale = '';
			for ($i = 0; $i < mb_strlen($course_period['DAYS']); $i++) {
				$course_period_DAYS_locale .= mb_substr($days_convert[mb_substr($course_period['DAYS'], $i, 1)],0,3) . '.&nbsp;';
			}
			$course_period['DAYS'] = $course_period_DAYS_locale;*/
			
			//DrawHeader($course_period['TEACHER'],$course_period['COURSE_TITLE'].' '.GetPeriod($course_period['PERIOD_ID']).($course_period['MARKING_PERIOD_ID']!="$fy_id"?' - '.GetMP($course_period['MARKING_PERIOD_ID']):'').(mb_strlen($course_period['DAYS'])<5?' - '.$course_period['DAYS']:''));
			DrawHeader($course_period['COURSE_TITLE'],$course_period['TITLE']);
			DrawHeader(GetSchool(UserSchool()),ProperDate(DBDate()));

			$_ROSARIO['User'] = array(1=>array('STAFF_ID'=>$course_period['TEACHER_ID'],'NAME'=>'name','PROFILE'=>'teacher','SCHOOLS'=>','.UserSchool().',','SYEAR'=>UserSyear()));
			$_SESSION['UserCoursePeriod'] = $course_period['COURSE_PERIOD_ID'];

			$extra = $first_extra;
			$extra['MP'] = $course_period['MARKING_PERIOD_ID'];
			$extra['MPTable'] = $course_period['MP'];
			$extra['suppress_save'] = true;
			include('modules/misc/Export.php');

			echo '<div style="page-break-after: always;"></div>';
		}
		$_SESSION['UserCoursePeriod'] = $PCL_UserCoursePeriod;
		PDFStop($handle);
	}
	else
		BackPrompt(_('You must choose at least one course period.'));
}

if(empty($_REQUEST['modfunc']))

{
	DrawHeader(ProgramTitle());

	if(User('PROFILE')!='admin')
		$_REQUEST['search_modfunc'] = 'list';

	if($_REQUEST['search_modfunc']=='list')
	{
		$_REQUEST['search_modfunc'] = 'select';
		$extra['header_right'] = '<INPUT type="submit" value="'._('Create Class Lists for Selected Course Periods').'" />';

		$extra['extra_header_left'] = '<TABLE><TR><TD><label><INPUT type="checkbox" name="include_inactive" value="Y"> '._('Include Inactive Students').'</label></TD></TR></TABLE>';

		$Search = 'mySearch';
		include('modules/misc/Export.php');
	}
	else
	{
		$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
		if($_SESSION['Back_PHP_SELF']!='course')
		{
			$_SESSION['Back_PHP_SELF'] = 'course';
			unset($_SESSION['List_PHP_SELF']);
		}
		echo '<script type="text/javascript">var footer_link = document.createElement("a"); footer_link.href = "Bottom.php"; footer_link.target = "footer"; ajaxLink(footer_link);</script>';
		echo '<BR />';
		PopTable('header',_('Find a Course'));
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].'" method="POST">';
		echo '<TABLE>';

		$RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME||', '||FIRST_NAME AS FULL_NAME FROM STAFF WHERE PROFILE='teacher' AND (SCHOOLS IS NULL OR position(',".UserSchool().",' IN SCHOOLS)>0) AND SYEAR='".UserSyear()."' ORDER BY FULL_NAME"));
		echo '<TR><TD style="text-align:right;">'._('Teacher').'</TD><TD>';
		echo '<SELECT name="teacher_id" style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION>';
		foreach($RET as $teacher)
			echo '<OPTION value="'.$teacher['STAFF_ID'].'">'.$teacher['FULL_NAME'].'</OPTION>';
		echo '</SELECT>';
		echo '</TD></TR>';

		$RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TITLE"));
		echo '<TR><TD style="text-align:right;">'._('Subject').'</TD><TD>';
		echo '<SELECT name="subject_id" style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION>';
		foreach($RET as $subject)
			echo '<OPTION value="'.$subject['SUBJECT_ID'].'">'.$subject['TITLE'].'</OPTION>';
		echo '</SELECT>';

		$RET = DBGet(DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		echo '<TR><TD style="text-align:right;">'._('Period').'</TD><TD>';
		echo '<SELECT name="period_id" style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION>';
		foreach($RET as $period)
			echo '<OPTION value="'.$period['PERIOD_ID'].'">'.$period['TITLE'].'</OPTION>';
		echo '</SELECT>';
		echo '</TD></TR>';

		Widgets('course');
		echo $extra['search'];

		echo '<TR><TD colspan="2" class="center">';
		echo '<BR />';
		echo Buttons(_('Submit'),_('Reset'));
		echo '</TD></TR>';
		echo '</TABLE>';
		echo '</FORM>';
		PopTable('footer');
	}
}

function mySearch($extra)
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&search_modfunc=list&_ROSARIO_PDF=true'.$extra['action'].'" method="POST" name="search">';

	DrawHeader('',$extra['header_right']);
	DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);
	echo '<TABLE>'.$extra['extra_search'].'</TABLE>';

	$sql = 'SELECT \'&nbsp;&nbsp;<INPUT type="checkbox" name="cp_arr[]" value="\'||cp.COURSE_PERIOD_ID||\'">\' AS CHECKBOX,cp.TITLE FROM COURSE_PERIODS cp';
	if(User('PROFILE')=='admin')
	{
		if($_REQUEST['teacher_id'])
			$where .= " AND cp.TEACHER_ID='$_REQUEST[teacher_id]'";
		if($_REQUEST['first'])
			$where .= " AND UPPER(s.FIRST_NAME) LIKE '".mb_strtoupper($_REQUEST['first'])."%'";
		if($_REQUEST['w_course_period_id'])
			if($_REQUEST['w_course_period_id'])
				if($_REQUEST['w_course_period_id_which']=='course')
					$where .= " AND cp.COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."')";
				else
					$where .= " AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."'";
		if($_REQUEST['subject_id'])
		{
			$from .= ",COURSES c";
			$where .= " AND c.COURSE_ID=cp.COURSE_ID AND c.SUBJECT_ID='".$_REQUEST['subject_id']."'";
		}
		//modif Francois: multiple school periods for a course period
		if($_REQUEST['period_id'])
		{
			$from .= ',COURSE_PERIOD_SCHOOL_PERIODS cpsp';
			$where .= " AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID='".$_REQUEST['period_id']."'";
			//$where .= " AND cp.PERIOD_ID='".$_REQUEST['period_id']."'";
		}
		$sql .= "$from WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."'$where";
	}
	else // teacher
	{
		$sql .= " WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."' AND cp.TEACHER_ID='".User('STAFF_ID')."'";
	}
	//modif Francois: multiple school periods for a course period
	//$sql .= ' ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID)';
	$sql .= ' ORDER BY cp.SHORT_NAME,cp.TITLE';

	$course_periods_RET = DBGet(DBQuery($sql));
	$LO_columns = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'cp_arr\');"><A>','TITLE'=>_('Course Period'));

	if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
	{
		$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
		if($_SESSION['Back_PHP_SELF']!='course')
		{
			$_SESSION['Back_PHP_SELF'] = 'course';
			unset($_SESSION['Search_PHP_SELF']);
		}
		echo '<script type="text/javascript">var footer_link = document.createElement("a"); footer_link.href = "Bottom.php"; footer_link.target = "footer"; ajaxLink(footer_link);</script>';
	}
	ListOutput($course_periods_RET,$LO_columns,'Course Period','Course Periods');
	echo '<BR /><span class="center"><INPUT type="submit" value="'._('Create Class Lists for Selected Course Periods').'" /></span>';
	echo '</FORM>';
}
?>