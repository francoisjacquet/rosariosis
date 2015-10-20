<?php
 
if (!UserSyear())
{
	$_SESSION['UserSyear'] = Config('SYEAR');
}

$_ROSARIO['HeaderIcon'] = 'modules/misc/icon.png';
DrawHeader(ParseMLField(Config('TITLE')));

DrawHeader('<span id="salute"></span><script>
var currentTime = new Date();
var hours = currentTime.getHours();
var salute = document.getElementById("salute");
if (hours < 12) salute.innerHTML='.json_encode(sprintf(_('Good Morning, %s.'), User('NAME'))).';
else if (hours < 18) salute.innerHTML='.json_encode(sprintf(_('Good Afternoon, %s.'), User('NAME'))).';
else salute.innerHTML='.json_encode(sprintf(_('Good Evening, %s.'), User('NAME'))).';</script>');

$welcome = sprintf(_('Welcome to %s!'), ParseMLField(Config('TITLE')));

if (!empty($_SESSION['LAST_LOGIN']))
	$welcome .= '<BR />&nbsp;'.sprintf(_('Your last login was <b>%s</b>.'), ProperDate(mb_substr($_SESSION['LAST_LOGIN'],0,10)).mb_substr($_SESSION['LAST_LOGIN'],10));

if ( !empty( $failed_login ) )
	$welcome .= '<BR />'.ErrorMessage(array(sprintf(_('There have been <b>%d</b> failed login attempts since your last successful login.'),$failed_login)), 'warning');

switch (User('PROFILE'))
{
	case 'admin':
		DrawHeader($welcome.'<BR />&nbsp;'._('You are an <b>Administrator</b> on the system.'));

		$PHPCheck = PHPCheck();
		if (!empty($PHPCheck))
			echo ErrorMessage($PHPCheck, 'warning');

		//FJ Discipline new referrals alert
		if ($RosarioModules['Discipline'] && AllowUse('Discipline/Referrals.php') && $_SESSION['LAST_LOGIN'])
		{
			$last_login_date = mb_substr( $_SESSION['LAST_LOGIN'], 0, 10 );

			$extra = array();
			$extra['SELECT_ONLY'] = 'count(*) AS COUNT';
			$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
			$extra['WHERE'] = ' AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SYEAR=ssm.SYEAR AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '."'".$last_login_date."' AND '".DBDate()."'";
			
			$disc_RET = GetStuList($extra);

			if ($disc_RET[1]['COUNT']>0)
			{
				$message = '<A HREF="Modules.php?modname=Discipline/Referrals.php&search_modfunc=list&discipline_entry_begin='.$last_login_date.'&discipline_entry_end='.DBDate().'"><img src="modules/Discipline/icon.png" class="button bigger" /> ';
				$message .= sprintf(ngettext('%d new referral', '%d new referrals', $disc_RET[1]['COUNT']), $disc_RET[1]['COUNT']);
				$message .= '</A>';	
				echo ErrorMessage(array($message), 'note');
			}
		}
		
		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//FJ file attached to portal notes
//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
		$notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID 
		FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st 
		WHERE pn.SYEAR='".UserSyear()."' 
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) 
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) 
		AND st.STAFF_ID='".User('STAFF_ID')."' 
		AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) 
		AND s.ID=pn.SCHOOL_ID 
		AND s.SYEAR=pn.SYEAR 
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate', 'CONTENT'=>'_formatContent', 'FILE_ATTACHED'=>'makeFileAttached'));

		if (count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached'),'SCHOOL'=>_('School')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

		//FJ Portal Polls
		$polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pp.TITLE||'</B>' AS TITLE,'options' AS OPTIONS,pp.ID 
		FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st 
		WHERE pp.SYEAR='".UserSyear()."' 
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) 
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) 
		AND st.STAFF_ID='".User('STAFF_ID')."' 
		AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0) 
		AND s.ID=pp.SCHOOL_ID AND s.SYEAR=pp.SYEAR 
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if (count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

//FJ add translation
		$events_RET = DBGet(DBQuery("SELECT ce.ID,ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE AS SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,s.TITLE AS SCHOOL 
		FROM CALENDAR_EVENTS ce,SCHOOLS s,STAFF st 
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE 
		AND CURRENT_DATE+11 
		AND ce.SYEAR='".UserSyear()."' 
		AND st.STAFF_ID='".User('STAFF_ID')."' 
		AND (st.SCHOOLS IS NULL OR position(','||ce.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
		AND s.ID=ce.SCHOOL_ID 
		AND s.SYEAR=ce.SYEAR 
		ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay', 'DESCRIPTION'=>'_formatContent'),array('SCHOOL_DATE'));

		if (count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));
        
		if (Preferences('HIDE_ALERTS')!='Y')
		{
		// warn if missing attendances
		$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY 3,SORT_ORDER"));
		foreach ( (array)$categories_RET as $category)
		{
		//FJ days numbered
		//FJ multiple school periods for a course period
			if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
			{
				$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,s.TITLE AS SCHOOL,acc.SCHOOL_DATE,cp.TITLE 
				FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHOOLS s,STAFF st, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
				WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID 
				AND acc.SYEAR='".UserSyear()."' 
				AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) 
				AND st.STAFF_ID='".User('STAFF_ID')."' 
				AND (st.SCHOOLS IS NULL OR position(','||acc.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
				AND cp.SCHOOL_ID=acc.SCHOOL_ID 
				AND cp.SYEAR=acc.SYEAR 
				AND acc.SCHOOL_DATE<'".DBDate()."' 
				AND cp.CALENDAR_ID=acc.CALENDAR_ID
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE) 
				AND sp.PERIOD_ID=cpsp.PERIOD_ID 
				AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
					(SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number 
					FROM attendance_calendar 
					WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR' AND SCHOOL_ID=acc.SCHOOL_ID) 
					AND school_date<=acc.SCHOOL_DATE 
					AND SCHOOL_ID=acc.SCHOOL_ID) 
				AS INT) FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='".$category['ID']."') 
				AND position(',".$category['ID'].",' IN cp.DOES_ATTENDANCE)>0 
				AND s.ID=acc.SCHOOL_ID 
				AND s.SYEAR=acc.SYEAR 
				ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			} else {
				$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,s.TITLE AS SCHOOL,acc.SCHOOL_DATE,cp.TITLE 
				FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHOOLS s,STAFF st, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
				WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID 
				AND acc.SYEAR='".UserSyear()."' 
				AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) 
				AND st.STAFF_ID='".User('STAFF_ID')."' 
				AND (st.SCHOOLS IS NULL OR position(','||acc.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
				AND cp.SCHOOL_ID=acc.SCHOOL_ID 
				AND cp.SYEAR=acc.SYEAR 
				AND acc.SCHOOL_DATE<'".DBDate()."' 
				AND cp.CALENDAR_ID=acc.CALENDAR_ID
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE) 
				AND sp.PERIOD_ID=cpsp.PERIOD_ID 
				AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='".$category['ID']."') 
				AND position(',".$category['ID'].",' IN cp.DOES_ATTENDANCE)>0 
				AND s.ID=acc.SCHOOL_ID 
				AND s.SYEAR=acc.SYEAR 
				ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
			}
			
			if (count($RET))
			{
				echo ErrorMessage(array(_('Teachers have missing attendance data')), 'warning');

				ListOutput($RET,array('SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'),'SCHOOL'=>_('School')),'Course Period with missing attendance data','Course Periods with missing attendance data',array(),array('COURSE_PERIOD_ID'),array('save'=>false,'search'=>false));
//				echo '';
			}
		}
		}

		if ($RosarioModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
			$FS_config = ProgramConfig( 'food_service' );
			
			// warn if negative food service balance
			$staff = DBGet(DBQuery("SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='".User('STAFF_ID')."'"));
			$staff = $staff[1];
			if ($staff['BALANCE'] && $staff['BALANCE']<0)
				echo ErrorMessage(array(sprintf(_('You have a <b>negative</b> food service balance of <span style="color:red">%s</span>'),$staff['BALANCE'])), 'warning');

			// warn if students with food service balances below minimum
			$extra = array();
			$extra['SELECT'] = ',fssa.STATUS,fsa.BALANCE';
			$extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
			$extra['WHERE'] = ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NULL AND fsa.BALANCE<\''.$FS_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'].'\'';
			$_REQUEST['_search_all_schools'] = 'Y';
			$RET = GetStuList($extra);
			if (count($RET))
			{
			    echo ErrorMessage(array(sprintf(_('Some students have food service balances below %1.2f'),$FS_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'])), 'warning');

			    ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade Level'),'BALANCE'=>_('Balance')),'Student','Students',array(),array(),array('save'=>false,'search'=>false));
//			    echo '</p>';
			}
		}

		echo '<p>&nbsp;'._('Happy administrating...').'</p>';
	break;

	case 'teacher':
		DrawHeader($welcome.'<BR />&nbsp;'._('You are a <b>Teacher</b> on the system.'));

		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pn.TITLE||'</B>' AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID 
		FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st 
		WHERE pn.SYEAR='".UserSyear()."' 
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) 
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) 
		AND st.STAFF_ID='".User('STAFF_ID')."' 
		AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
		AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL 
		AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) 
		AND s.ID=pn.SCHOOL_ID 
		AND s.SYEAR=pn.SYEAR 
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent','FILE_ATTACHED'=>'makeFileAttached'));

		if (count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached'),'SCHOOL'=>_('School')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

//FJ Portal Polls
        $polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pp.TITLE||'</B>' AS TITLE,'options' AS OPTIONS,pp.ID 
		FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st 
		WHERE pp.SYEAR='".UserSyear()."' 
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) 
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) 
		AND st.STAFF_ID='".User('STAFF_ID')."' 
		AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL 
		AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0) 
		AND s.ID=pp.SCHOOL_ID 
		AND s.SYEAR=pp.SYEAR 
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if (count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

//FJ add translation
		$events_RET = DBGet(DBQuery("SELECT ce.ID,ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,s.TITLE AS SCHOOL 
		FROM CALENDAR_EVENTS ce,SCHOOLS s 
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE 
		AND CURRENT_DATE+11 
		AND ce.SYEAR='".UserSyear()."' 
		AND position(','||ce.SCHOOL_ID||',' IN (SELECT SCHOOLS FROM STAFF WHERE STAFF_ID='".User('STAFF_ID')."'))>0 
		AND s.ID=ce.SCHOOL_ID 
		AND s.SYEAR=ce.SYEAR 
		ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay', 'DESCRIPTION'=>'_formatContent'),array('SCHOOL_DATE'));

		if (count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

		//FJ Portal Assignments
		$assignments_RET = DBGet(DBQuery("SELECT a.ASSIGNMENT_ID AS ID,a.TITLE,a.DUE_DATE,to_char(a.DUE_DATE,'Day') AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,c.TITLE AS COURSE
		FROM GRADEBOOK_ASSIGNMENTS a,COURSES c
		WHERE (a.COURSE_ID=c.COURSE_ID
		OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
		AND a.DUE_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11
		AND a.STAFF_ID='".User('STAFF_ID')."' 
		AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
		ORDER BY a.DUE_DATE,a.TITLE"),array('DUE_DATE'=>'ProperDate', 'DAY'=>'_eventDay', 'ASSIGNED_DATE'=>'ProperDate', 'DESCRIPTION'=>'_formatContent'));

		if (count($assignments_RET))
		{
			ListOutput($assignments_RET,array('DAY'=>_('Day'),'DUE_DATE'=>_('Date'),'ASSIGNED_DATE'=>_('Assigned Date'),'TITLE'=>_('Assignment'),'DESCRIPTION'=>_('Notes'),'COURSE'=>_('Course')),'Upcoming Assignment','Upcoming Assignments',array(),array(),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));

		if (Preferences('HIDE_ALERTS')!='Y')
		{
			// warn if missing attendances
			$categories_RET = DBGet(DBQuery("SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION SELECT ID,TITLE,1,SORT_ORDER FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY 3,SORT_ORDER"));
			foreach ( (array)$categories_RET as $category)
			{
			//FJ days numbered
			//FJ multiple school periods for a course period
				if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
				{
					$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,acc.SCHOOL_DATE,cp.TITLE 
					FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
					WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID 
					AND acc.SYEAR='".UserSyear()."' 
					AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) 
					AND cp.SCHOOL_ID=acc.SCHOOL_ID 
					AND cp.SYEAR=acc.SYEAR 
					AND acc.SCHOOL_DATE<'".DBDate()."' 
					AND cp.CALENDAR_ID=acc.CALENDAR_ID 
					AND cp.TEACHER_ID='".User('STAFF_ID')."'
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID 
					AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
						(SELECT CASE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." WHEN 0 THEN ".SchoolInfo('NUMBER_DAYS_ROTATION')." ELSE COUNT(school_date)% ".SchoolInfo('NUMBER_DAYS_ROTATION')." END AS day_number 
						FROM attendance_calendar 
						WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR' AND SCHOOL_ID=acc.SCHOOL_ID) 
						AND school_date<=acc.SCHOOL_DATE 
						AND SCHOOL_ID=acc.SCHOOL_ID) 
					AS INT) FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
					AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='".$category['ID']."') 
					AND position(',".$category['ID'].",' IN cp.DOES_ATTENDANCE)>0 
					ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
				} else {
					$RET = DBGET(DBQuery("SELECT cp.COURSE_PERIOD_ID,acc.SCHOOL_DATE,cp.TITLE 
					FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp 
					WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID 
					AND acc.SYEAR='".UserSyear()."' 
					AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0) 
					AND cp.SCHOOL_ID=acc.SCHOOL_ID 
					AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'".DBDate()."' 
					AND cp.CALENDAR_ID=acc.CALENDAR_ID 
					AND cp.TEACHER_ID='".User('STAFF_ID')."'
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID 
					AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
					AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='".$category['ID']."') 
					AND position(',".$category['ID'].",' IN cp.DOES_ATTENDANCE)>0 
					ORDER BY cp.TITLE,acc.SCHOOL_DATE"),array('SCHOOL_DATE'=>'ProperDate'),array('COURSE_PERIOD_ID'));
				}
			
				if (count($RET))
				{
					echo ErrorMessage(array(_('You have missing attendance data')), 'warning');

					ListOutput($RET,array('SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher')),'Course Period with missing attendance data','Course Periods with missing attendance data',array(),array('COURSE_PERIOD_ID'),array('save'=>false,'search'=>false));
	//				echo '</p>';
				}
			}
		}

		if ($RosarioModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
			// warn if negative food service balance
			$staff = DBGet(DBQuery("SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='".User('STAFF_ID')."'"));
			$staff = $staff[1];

			if ($staff['BALANCE'] && $staff['BALANCE']<0)
				echo ErrorMessage(array(sprintf(_('You have a <b>negative</b> food service balance of <span style="color:red">%s</span>'),$staff['BALANCE'])), 'warning');
		}

		echo '<p>&nbsp;'._('Happy teaching...').'</p>';
	break;

	case 'parent':
		DrawHeader($welcome.'<BR />&nbsp;'._('You are a <b>Parent</b> on the system.'));

		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='".User('STAFF_ID')."' AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
		$notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID 
		FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st 
		WHERE pn.SYEAR='".UserSyear()."' 
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) 
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) 
		AND st.STAFF_ID='".User('STAFF_ID')."' 
		AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) 
		AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
		AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) 
		AND s.ID=pn.SCHOOL_ID 
		AND s.SYEAR=pn.SYEAR 
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent','FILE_ATTACHED'=>'makeFileAttached'));

		if (count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached'),'SCHOOL'=>_('School')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

//FJ Portal Polls
        $polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<B>'||pp.TITLE||'</B>' AS TITLE,'options' AS OPTIONS,pp.ID 
		FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st 
		WHERE pp.SYEAR='".UserSyear()."' 
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) 
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) 
		AND st.STAFF_ID='".User('STAFF_ID')."' 
		AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0) 
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL 
		AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0) 
		AND s.ID=pp.SCHOOL_ID 
		AND s.SYEAR=pp.SYEAR 
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if (count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

//FJ add translation
		$events_RET = DBGet(DBQuery("SELECT ce.ID,ce.TITLE,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,ce.DESCRIPTION,s.TITLE AS SCHOOL 
		FROM CALENDAR_EVENTS ce,SCHOOLS s 
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11 
		AND ce.SYEAR='".UserSyear()."' 
		AND ce.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='".User('STAFF_ID')."' AND se.SYEAR=ce.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) 
		AND s.ID=ce.SCHOOL_ID 
		AND s.SYEAR=ce.SYEAR 
		ORDER BY ce.SCHOOL_DATE,s.TITLE"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay', 'DESCRIPTION'=>'_formatContent'),array('SCHOOL_DATE'));

		if (count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description'),'SCHOOL'=>_('School')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

		//FJ Portal Assignments
		$assignments_RET = DBGet(DBQuery("SELECT a.ASSIGNMENT_ID AS ID,a.TITLE,a.DUE_DATE,to_char(a.DUE_DATE,'Day') AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,c.TITLE AS COURSE
		FROM GRADEBOOK_ASSIGNMENTS a,SCHEDULE s,COURSES c
		WHERE (a.COURSE_ID=c.COURSE_ID
		OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
		AND (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
		AND a.DUE_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11
		AND s.STUDENT_ID='".UserStudentID()."' 
		AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
		ORDER BY a.DUE_DATE,a.TITLE"),array('DUE_DATE'=>'ProperDate', 'DAY'=>'_eventDay', 'ASSIGNED_DATE'=>'ProperDate', 'DESCRIPTION'=>'_formatContent', 'STAFF_ID'=>'GetTeacher'));

		if (count($assignments_RET))
		{
			ListOutput($assignments_RET,array('DAY'=>_('Day'),'DUE_DATE'=>_('Date'),'ASSIGNED_DATE'=>_('Assigned Date'),'TITLE'=>_('Assignment'),'DESCRIPTION'=>_('Notes'),'COURSE'=>_('Course'),'STAFF_ID'=>_('Teacher')),'Upcoming Assignment','Upcoming Assignments',array(),array(),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));

		if ($RosarioModules['Food_Service'] && Preferences('HIDE_ALERTS')!='Y')
		{
			$FS_config = ProgramConfig( 'food_service' );
			
			// warn if students with low food service balances
			$extra['SELECT'] = ',fssa.STATUS,fsa.ACCOUNT_ID,fsa.BALANCE AS BALANCE,'.$FS_config['FOOD_SERVICE_BALANCE_TARGET'][1]['VALUE'].'-fsa.BALANCE AS DEPOSIT';
			$extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
			$extra['WHERE'] = ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NULL AND fsa.BALANCE<\''.$FS_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'].'\'';
			$extra['ASSOCIATED'] = User('STAFF_ID');

			$RET = GetStuList($extra);

			if (count($RET))
			{
				echo ErrorMessage(array(sprintf(_('You have students with food service balance below %1.2f - please deposit at least the Minimum Deposit into you children\'s accounts.'),$FS_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'])), 'warning');

				ListOutput($RET,array('FULL_NAME'=>_('Student'),'GRADE_ID'=>_('Grade Level'),'ACCOUNT_ID'=>_('Account ID'),'BALANCE'=>_('Balance'),'DEPOSIT'=>_('Minimum Deposit')),'Student','Students',array(),array(),array('save'=>false,'search'=>false));
			}

			// warn if negative food service balance
			$staff = DBGet(DBQuery("SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='".User('STAFF_ID')."'"));
			$staff = $staff[1];

			if ($staff['BALANCE'] && $staff['BALANCE']<0)
				echo ErrorMessage(array(sprintf(_('You have a <b>negative</b> food service balance of <span style="color:red">%s</span>'),Currency($staff['BALANCE']))), 'warning');
		}

		echo '<p>&nbsp;'._('Happy parenting...').'</p>';
	break;

	case 'student':
		DrawHeader($welcome.'<BR />&nbsp;'._('You are a <b>Student</b> on the system.'));

		include_once('ProgramFunctions/PortalPollsNotes.fnc.php');
//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
//        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s WHERE pn.SYEAR='".UserSyear()."' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND pn.SCHOOL_ID='".UserSchool()."' AND  position(',0,' IN pn.PUBLISHED_PROFILES)>0 AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent'));
        $notes_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID 
		FROM PORTAL_NOTES pn,SCHOOLS s 
		WHERE pn.SYEAR='".UserSyear()."' 
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL) 
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) 
		AND pn.SCHOOL_ID='".UserSchool()."' 
		AND position(',0,' IN pn.PUBLISHED_PROFILES)>0 
		AND s.ID=pn.SCHOOL_ID 
		AND s.SYEAR=pn.SYEAR 
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','CONTENT'=>'_formatContent','FILE_ATTACHED'=>'makeFileAttached'));

		if (count($notes_RET))
		{
			ListOutput($notes_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'CONTENT'=>_('Note'),'FILE_ATTACHED'=>_('File Attached')),'Note','Notes',array(),array(),array('save'=>false,'search'=>false));
		}

//FJ Portal Polls
		//FJ Portal Polls add students teacher
        $polls_RET = DBGet(DBQuery("SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,pp.TITLE,'options' AS OPTIONS,pp.ID 
		FROM PORTAL_POLLS pp,SCHOOLS s 
		WHERE pp.SYEAR='".UserSyear()."' 
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL) 
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL) 
		AND pp.SCHOOL_ID='".UserSchool()."' 
		AND position(',0,' IN pp.PUBLISHED_PROFILES)>0 
		AND s.ID=pp.SCHOOL_ID 
		AND s.SYEAR=pp.SYEAR 
		AND (pp.STUDENTS_TEACHER_ID IS NULL OR pp.STUDENTS_TEACHER_ID IN (SELECT cp.TEACHER_ID FROM SCHEDULE sch, COURSE_PERIODS cp WHERE sch.SYEAR='".UserSyear()."' AND sch.SCHOOL_ID='".UserSchool()."' AND sch.STUDENT_ID='".UserStudentID()."' AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)) 
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC"),array('PUBLISHED_DATE'=>'ProperDate','OPTIONS'=>'PortalPollsDisplay'));

		if (count($polls_RET))
		{
			ListOutput($polls_RET,array('PUBLISHED_DATE'=>_('Date Posted'),'TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'SCHOOL'=>_('School')),'Poll','Polls',array(),array(),array('save'=>false,'search'=>false));
		}

		$events_RET = DBGet(DBQuery("SELECT ID,TITLE,SCHOOL_DATE,to_char(SCHOOL_DATE,'Day') AS DAY,DESCRIPTION
		FROM CALENDAR_EVENTS
		WHERE SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11
		AND SYEAR='".UserSyear()."'
		AND SCHOOL_ID='".UserSchool()."'"),array('SCHOOL_DATE'=>'ProperDate', 'DAY'=>'_eventDay', 'DESCRIPTION'=>'_formatContent'),array('SCHOOL_DATE'));

		if (count($events_RET))
		{
			ListOutput($events_RET,array('DAY'=>_('Day'),'SCHOOL_DATE'=>_('Date'),'TITLE'=>_('Event'),'DESCRIPTION'=>_('Description')),'Day With Upcoming Events','Days With Upcoming Events',array(),array('SCHOOL_DATE'),array('save'=>false,'search'=>false));
		}

		//FJ Portal Assignments
		$assignments_RET = DBGet(DBQuery("SELECT a.ASSIGNMENT_ID AS ID,a.TITLE,a.DUE_DATE,to_char(a.DUE_DATE,'Day') AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,c.TITLE AS COURSE
		FROM GRADEBOOK_ASSIGNMENTS a,SCHEDULE s,COURSES c
		WHERE (a.COURSE_ID=c.COURSE_ID
		OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
		AND (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
		AND a.DUE_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11
		AND s.STUDENT_ID='".UserStudentID()."' 
		AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
		ORDER BY a.DUE_DATE,a.TITLE"),array('DUE_DATE'=>'ProperDate', 'DAY'=>'_eventDay', 'ASSIGNED_DATE'=>'ProperDate', 'DESCRIPTION'=>'_formatContent', 'STAFF_ID'=>'GetTeacher'));

		if (count($assignments_RET))
		{
			ListOutput($assignments_RET,array('DAY'=>_('Day'),'DUE_DATE'=>_('Date'),'ASSIGNED_DATE'=>_('Assigned Date'),'TITLE'=>_('Assignment'),'DESCRIPTION'=>_('Notes'),'COURSE'=>_('Course'),'STAFF_ID'=>_('Teacher')),'Upcoming Assignment','Upcoming Assignments',array(),array(),array('save'=>false,'search'=>false));
		}

        //RSSOutput(USER('PROFILE'));

		echo '<p>&nbsp;'._('Happy learning...').'</p>';
	break;
}

function _formatContent($value,$column)
{
	global $THIS_RET;

	$id = $THIS_RET['ID'];

	if ( !$value )
		return '';

	//FJ responsive rt td too large
	//FJ Portal Assignments
	if ( isset( $THIS_RET['COURSE'] ) )
		$return = '<DIV id="divAssignmentContent' . $id . '" class="rt2colorBox">';
	else
		$return = '<DIV id="divNoteContent' . $id . '" class="rt2colorBox">';

	// convert MarkDown to HTML
	$return .= '<div class="markdown-to-html">' . $value . '</div>';

	$return .= '</DIV>';

	return $return;
}

function PHPCheck() {
	$ret = array();

	//FJ check PHP version
	if (version_compare(PHP_VERSION, '5.3.2') == -1)
	    $ret[] = 'RosarioSIS requires PHP 5.3.2 to run, your version is : ' . PHP_VERSION;

	if ((bool)ini_get('safe_mode'))
		$ret[] = 'safe_mode is set to On in your PHP configuration.';

	if (mb_strpos(ini_get('disable_functions'),'passthru')!==false)
		$ret[] = 'passthru is disabled in your PHP configuration.';

	return $ret;
}

//FJ add translation
function _eventDay($string, $key) {
	return _(trim($string));
}
