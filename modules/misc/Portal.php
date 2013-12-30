<?php
// DebugBreak();
 
if(!UserSyear())
{
	$_SESSION['UserSyear'] = Config('SYEAR');
}

$_ROSARIO['HeaderIcon'] = 'rosario.png'; //modif Francois: icones Educons
//DrawHeader(ParseMLField(Config('TITLE')),'RosarioSIS v.'.$RosarioVersion);
DrawHeader(ParseMLField(Config('TITLE')));

DrawHeader('<span id="salute"></span><script type="text/javascript">
var currentTime = new Date();
var hours = currentTime.getHours();
if (hours < 12) document.getElementById("salute").innerHTML="'.sprintf(_('Good Morning, %s.'), User('NAME')).'";
else if (hours < 18) document.getElementById("salute").innerHTML="'.sprintf(_('Good Afternoon, %s.'), User('NAME')).'";
else document.getElementById("salute").innerHTML="'.sprintf(_('Good Evening, %s.'), User('NAME')).'";</script>');

$welcome = sprintf(_('Welcome to %s!'), ParseMLField(Config('TITLE')));
if($_SESSION['LAST_LOGIN'])
	$welcome .= '<BR />&nbsp;'.sprintf(_('Your last login was <b>%s</b>.'), ProperDate(mb_substr($_SESSION['LAST_LOGIN'],0,10)).mb_substr($_SESSION['LAST_LOGIN'],10));
if($_REQUEST['failed_login'])
//modif Francois: css WPadmin add class error for all Warning! of this file
//	$welcome .= '<BR />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.sprintf(_('There have been <b>%d</b> failed login attempts since your last successful login.'),$_REQUEST['failed_login']);
	$welcome .= '<BR /><div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.sprintf(_('There have been <b>%d</b> failed login attempts since your last successful login.'),$_REQUEST['failed_login']).'</p></div>';

switch (User('PROFILE'))
{
	case 'admin':
		//DrawHeader($welcome.'<BR />&nbsp;'._('You are an <b>Administrator</b> on the system.<BR />').PHPCheck().versionCheck());
		DrawHeader($welcome.'<BR />&nbsp;'._('You are an <b>Administrator</b> on the system.').'<BR />'.PHPCheck());

		//modif Francois: Discipline new referrals alert
		if(AllowUse('Discipline/Referrals.php') && User('LAST_LOGIN'))
		{
			$extra = array();
			$extra['SELECT_ONLY'] = 'count(*) AS COUNT';
			$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
			$extra['WHERE'] = ' AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SYEAR=ssm.SYEAR AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '."'".User('LAST_LOGIN')."' AND '".DBDate()."'";
			
			$disc_RET = GetStuList($extra);

			if($disc_RET[1]['COUNT']>0)
			{
				$message = '<A HREF="Modules.php?modname=Discipline/Referrals.php&search_modfunc=list&discipline_entry_begin='.User('LAST_LOGIN').'&discipline_entry_end='.DBDate().'"><img src="assets/icons/Discipline.png" class="alignImg" /> ';
				$message .= sprintf(ngettext('%d new referral', '%d new referrals', $disc_RET[1]['COUNT']), $disc_RET[1]['COUNT']);
				$message .= '</A>';	
				DrawHeader($message);
			}
		}
		
		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//modif Francois: file attached to portal notes
//modif Francois: fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent','FILE_ATTACHED'=>'makeFileAttached'));

		if(count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached'),'SCHOOL'=>_('School')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: Portal Polls
        $polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pp.TITLE||'</B>' AS TITLE,'options' AS OPTIONS,pp.ID FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st WHERE pp.SYEAR='".UserSyear()."' AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0) AND s.ID=pp.SCHOOL_ID AND s.SYEAR=pp.SYEAR ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if(count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: add translation
//		$events_RET = DBGet(DBQuery("SELECT ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE AS SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Dy') AS DAY,s.TITLE AS SCHOOL FROM CALENDAR_EVENTS ce,SCHOOLS s,STAFF st WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+6 AND ce.SYEAR='".UserSyear()."' AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||ce.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND s.ID=ce.SCHOOL_ID AND s.SYEAR=ce.SYEAR ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate'),array('SCHOOL_DATE'));
		$events_RET = DBGet(DBQuery("SELECT ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE AS SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,s.TITLE AS SCHOOL FROM CALENDAR_EVENTS ce,SCHOOLS s,STAFF st WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11 AND ce.SYEAR='".UserSyear()."' AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||ce.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND s.ID=ce.SCHOOL_ID AND s.SYEAR=ce.SYEAR ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));
        
		if(Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if missing attendances
		$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY 3,SORT_ORDER"));
		foreach($categories_RET as $category)
		{
		//modif Francois: days numbered
		//modif Francois: multiple school periods for a course period
			if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
			{
				$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,s.TITLE AS SCHOOL,acc.SCHOOL_DATE,cp.TITLE FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHOOLS s,STAFF st, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||acc.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'".DBDate()."' AND cp.CALENDAR_ID=acc.CALENDAR_ID
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
				AND sp.PERIOD_ID=cpsp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number FROM attendance_calendar WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR') AND school_date<=acc.SCHOOL_DATE) AS INT) FOR 1) IN cpsp.DAYS)>0
					OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='$category[ID]') AND position(',$category[ID],' IN cp.DOES_ATTENDANCE)>0 AND s.ID=acc.SCHOOL_ID AND s.SYEAR=acc.SYEAR ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			} else {
				$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,s.TITLE AS SCHOOL,acc.SCHOOL_DATE,cp.TITLE FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHOOLS s,STAFF st, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||acc.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'".DBDate()."' AND cp.CALENDAR_ID=acc.CALENDAR_ID
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
				AND sp.PERIOD_ID=cpsp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0
					OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='$category[ID]') AND position(',$category[ID],' IN cp.DOES_ATTENDANCE)>0 AND s.ID=acc.SCHOOL_ID AND s.SYEAR=acc.SYEAR ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			}
			
			if (count($RET))
			{
				echo '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.Localize('colon',_('Teachers have missing attendance data')).'</p></div>';
				ListOutput($RET,array('SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'),'SCHOOL'=>_('School')),'Course Period with missing attendance data','Course Periods with missing attendance data',array(),array('COURSE_PERIOD_ID'),array('save'=>false,'search'=>false));
//				echo '';
			}
		}
		}

		if($RosarioModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
		    // warn if negative food service balance
		    $staff = DBGet(DBQuery("SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='".User('STAFF_ID')."'"));
		    $staff = $staff[1];
		    if($staff['BALANCE'] && $staff['BALANCE']<0)
			    echo '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.sprintf(_('You have a <b>negative</b> food service balance of <span style="color:red">%s</span>'),$staff['BALANCE']).'</p></div>';

		    // warn if students with way low food service balances
		    $extra['SELECT'] = ',fssa.STATUS,fsa.BALANCE';
		    $extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
		    $extra['WHERE'] = ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NULL AND fsa.BALANCE<\'-10\'';
		    $_REQUEST['_search_all_schools'] = 'Y';
		    $RET = GetStuList($extra);
		    if (count($RET))
            {
			    echo '<p><div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.Localize('colon',_('Some students have food service balances below -$10.00')).'</p></div>';
			    ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade Level'),'BALANCE'=>_('Balance')),'Student','Students',array(),array(),array('save'=>false,'search'=>false));
//			    echo '</p>';
  		    }
		}

		echo '<p>&nbsp;'._('Happy administrating...').'</p>';
	break;

	case 'teacher':
		DrawHeader($welcome.'<BR />&nbsp;'._('You are a <b>Teacher</b> on the system.'));

		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//modif Francois: fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent','FILE_ATTACHED'=>'makeFileAttached'));

		if(count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached'),'SCHOOL'=>_('School')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: Portal Polls
        $polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pp.TITLE||'</B>' AS TITLE,'options' AS OPTIONS,pp.ID FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st WHERE pp.SYEAR='".UserSyear()."' AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0) AND s.ID=pp.SCHOOL_ID AND s.SYEAR=pp.SYEAR ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if(count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: add translation
		$events_RET = DBGet(DBQuery("SELECT ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,s.TITLE AS SCHOOL FROM CALENDAR_EVENTS ce,SCHOOLS s WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11 AND ce.SYEAR='".UserSyear()."' AND position(','||ce.SCHOOL_ID||',' IN (SELECT SCHOOLS FROM STAFF WHERE STAFF_ID='".User('STAFF_ID')."'))>0 AND s.ID=ce.SCHOOL_ID AND s.SYEAR=ce.SYEAR ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));

		if(Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if missing attendances
		$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY 3,SORT_ORDER"));
		foreach($categories_RET as $category)
		{
		//modif Francois: days numbered
		//modif Francois: multiple school periods for a course period
			if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
			{
				$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,acc.SCHOOL_DATE,cp.TITLE FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'".DBDate()."' AND cp.CALENDAR_ID=acc.CALENDAR_ID AND cp.TEACHER_ID='".User('STAFF_ID')."'
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
				AND sp.PERIOD_ID=cpsp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast((SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number FROM attendance_calendar WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR') AND school_date<=acc.SCHOOL_DATE) AS INT) FOR 1) IN cpsp.DAYS)>0
					OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='$category[ID]') AND position(',$category[ID],' IN cp.DOES_ATTENDANCE)>0 ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			} else {
				$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,acc.SCHOOL_DATE,cp.TITLE FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND acc.SYEAR='".UserSyear()."' AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) AND cp.SCHOOL_ID=acc.SCHOOL_ID AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'".DBDate()."' AND cp.CALENDAR_ID=acc.CALENDAR_ID AND cp.TEACHER_ID='".User('STAFF_ID')."'
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE UNION SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
				AND sp.PERIOD_ID=cpsp.PERIOD_ID AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0
					OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='$category[ID]') AND position(',$category[ID],' IN cp.DOES_ATTENDANCE)>0 ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			}
			
			if (count($RET))
			{
				echo '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.Localize('colon',_('You have missing attendance data')).'</div></p>';
				ListOutput($RET,array('SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher')),'Course Period with missing attendance data','Course Periods with missing attendance data',array(),array('COURSE_PERIOD_ID'),array('save'=>false,'search'=>false));
//				echo '</p>';
			}
		}
		}

		if($RosarioModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if negative food service balance
		$staff = DBGet(DBQuery("SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='".User('STAFF_ID')."'"));
		$staff = $staff[1];
		if($staff['BALANCE'] && $staff['BALANCE']<0)
			echo '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.sprintf(_('You have a <b>negative</b> food service balance of <span style="color:red">%s</span>'),$staff['BALANCE']).'</p></div>';
		}

		echo '<p>&nbsp;'._('Happy teaching...').'</p>';
	break;

	case 'parent':
		DrawHeader($welcome.'<BR />&nbsp;'._('You are a <b>Parent</b> on the system.'));

		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//modif Francois: fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent','FILE_ATTACHED'=>'makeFileAttached'));

		if(count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached'),'SCHOOL'=>_('School')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: Portal Polls
        $polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pp.TITLE||'</B>' AS TITLE,'options' AS OPTIONS,pp.ID FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st WHERE pp.SYEAR='".UserSyear()."' AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0) AND s.ID=pp.SCHOOL_ID AND s.SYEAR=pp.SYEAR ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if(count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: add translation
		$events_RET = DBGet(DBQuery("SELECT ce.TITLE,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,ce.DESCRIPTION,s.TITLE AS SCHOOL FROM CALENDAR_EVENTS ce,SCHOOLS s WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11 AND ce.SYEAR='".UserSyear()."' AND ce.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=ce.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND s.ID=ce.SCHOOL_ID AND s.SYEAR=ce.SYEAR ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));

		if($RosarioModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if students with low food service balances
//modif Francois: add translation
//		$extra['SELECT'] = ',fssa.STATUS,fsa.ACCOUNT_ID,\'$\'||fsa.BALANCE AS BALANCE,\'$\'||16.5-fsa.BALANCE AS DEPOSIT';
		$extra['SELECT'] = ',fssa.STATUS,fsa.ACCOUNT_ID,\''.$CurrencySymbol.'\'||fsa.BALANCE AS BALANCE,\''.$CurrencySymbol.'\'||16.5-fsa.BALANCE AS DEPOSIT';
		$extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
		$extra['WHERE'] = ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NULL AND fsa.BALANCE<\'5\'';
		$extra['ASSOCIATED'] = User('STAFF_ID');
		$RET = GetStuList($extra);
		if (count($RET))
		{
			echo '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'._('You have students with food service balance below $5.00 - please deposit at least the Minimum Deposit into you children\'s accounts.').'</p></div>';
			ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade Level'),'ACCOUNT_ID'=>_('Account ID'),'BALANCE'=>_('Balance'),'DEPOSIT'=>_('Minimum Deposit')),'Student','Students',array(),array(),array('save'=>false,'search'=>false));
//			echo '</p>';
		}

		// warn if negative food service balance
		$staff = DBGet(DBQuery("SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='".User('STAFF_ID')."'"));
		$staff = $staff[1];
		if($staff['BALANCE'] && $staff['BALANCE']<0)
			echo '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<span style="color:red"><b>'._('Warning!').'</b></span>&nbsp;'.sprintf(_('You have a <b>negative</b> food service balance of <span style="color:red">%s</span>'),$staff['BALANCE']).'</p></div>';
		}

		echo '<p>&nbsp;'._('Happy parenting...').'</p>';
	break;

	case 'student':
		DrawHeader($welcome.'<BR />&nbsp;'._('You are a <b>Student</b> on the system.'));

		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//modif Francois: fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND pn.SCHOOL_ID='".UserSchool()."' AND  position(',0,' IN pn.PUBLISHED_PROFILES)>0 AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID FROM PORTAL_NOTES pn,SCHOOLS s WHERE pn.SYEAR='".UserSyear()."' AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND pn.SCHOOL_ID='".UserSchool()."' AND position(',0,' IN pn.PUBLISHED_PROFILES)>0 AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent','FILE_ATTACHED'=>'makeFileAttached'));

		if(count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: Portal Polls
		//modif Francois: Portal Polls add students teacher
        $polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,pp.TITLE,'options' AS OPTIONS,pp.ID FROM PORTAL_POLLS pp,SCHOOLS s WHERE pp.SYEAR='".UserSyear()."' AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) AND pp.SCHOOL_ID='".UserSchool()."' AND position(',0,' IN pp.PUBLISHED_PROFILES)>0 AND s.ID=pp.SCHOOL_ID AND s.SYEAR=pp.SYEAR AND (pp.STUDENTS_TEACHER_ID IS NULL OR pp.STUDENTS_TEACHER_ID IN (SELECT cp.TEACHER_ID FROM SCHEDULE sch, COURSE_PERIODS cp WHERE sch.SYEAR='".UserSyear()."' AND sch.SCHOOL_ID='".UserSchool()."' AND sch.STUDENT_ID='".UserStudentID()."' AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)) ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if(count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

//modif Francois: add translation
		$events_RET = DBGet(DBQuery("SELECT TITLE,SCHOOL_DATE,to_char(SCHOOL_DATE,'Day') AS DAY,DESCRIPTION FROM CALENDAR_EVENTS WHERE SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11 AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay'),array('SCHOOL_DATE'));

		if(count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));

		echo '<p>&nbsp;'._('Happy learning...').'</p>';
	break;
}

function _formatContent($value,$column)
{	global $THIS_RET;

	$id = $THIS_RET['ID'];

	$value_br = nl2br($value);
	
	//modif Francois: transform URL to links
	$value_br_url = $value_br;
	preg_match_all('@(https?://([-\w\.]+)+(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)?)@',$value_br_url,$matches);
	if($matches){
		foreach($matches[0] as $url){
			$text = (mb_strlen($url) > 50 ? mb_substr($url, 0, 50).'...' : $url); //cut URL text if URL > 50 chars
			$replace = '<a href="'.$url.'" target="_blank">'.$text.'</a>';
			$value_br_url = str_replace($url,$replace,$value_br_url);
		}
	}
	//modif Francois: responsive rt td too large
	if ($value_br==$value && mb_strlen($value) < 50)
		$return = $value_br_url;
	else
	{
		$return = includeOnceColorBox('divNoteContent'.$id);
		$return .= '<DIV id="divNoteContent'.$id.'" class="rt2colorBox">'.$value_br_url.'</DIV>';
	}
	
	return $return;
}

function PHPCheck() {
    $ret = '';
    if ((bool)ini_get('safe_mode'))
       $ret .= '&nbsp;WARNING: safe_mode is set to On in your PHP configuration.<br />';
    if (mb_strpos(ini_get('disable_functions'),'passthru')!==false)
       $ret .= '&nbsp;WARNING: passthru is disabled in your PHP configuration.<br />';
    return $ret;
}


//modif Francois: add translation
function _eventDay($string, $key) {
	return _(trim($string));
}

?>