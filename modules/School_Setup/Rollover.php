<?php
$next_syear = UserSyear()+1;
$tables = array('SCHOOLS'=>_('Schools'),'STAFF'=>_('Users'),'SCHOOL_PERIODS'=>_('School Periods'),'SCHOOL_MARKING_PERIODS'=>_('Marking Periods'),'ATTENDANCE_CALENDARS'=>_('Calendars'),'ATTENDANCE_CODES'=>_('Attendance Codes'),'REPORT_CARD_GRADES'=>_('Report Card Grade Codes'),'COURSES'=>_('Courses').'<b>*</b>','STUDENT_ENROLLMENT_CODES'=>_('Student Enrollment Codes'),'STUDENT_ENROLLMENT'=>_('Students').'<b>*</b>','REPORT_CARD_COMMENTS'=>_('Report Card Comment Codes').'<b>*</b>','PROGRAM_CONFIG'=>_('School Configuration'));
$no_school_tables = array('SCHOOLS'=>true,'STUDENT_ENROLLMENT_CODES'=>true,'STAFF'=>true);

if ($RosarioModules['Eligibility'])
	$tables += array('ELIGIBILITY_ACTIVITIES'=>_('Eligibility Activity Codes'));
	
if ($RosarioModules['Food_Service'])
	$tables += array('FOOD_SERVICE_STAFF_ACCOUNTS'=>_('Food Service Staff Accounts'));
	
if($RosarioModules['Discipline'])
//modif Francois: discipline_field_usage rollover
	$tables += array(/*'DISCIPLINE_CATEGORIES'=>_('Referral Form'), */'DISCIPLINE_FIELD_USAGE'=>_('Referral Form'));

$table_list = '<TABLE style="float: left">';
foreach($tables as $table=>$name)
{
	if($table!='FOOD_SERVICE_STAFF_ACCOUNTS')
		$exists_RET[$table] = DBGet(DBQuery("SELECT count(*) AS COUNT FROM $table WHERE SYEAR='$next_syear'".(!$no_school_tables[$table]?" AND SCHOOL_ID='".UserSchool()."'":'')));
	else
		$exists_RET['FOOD_SERVICE_STAFF_ACCOUNTS'] = DBGet(DBQuery("SELECT count(*) AS COUNT FROM STAFF WHERE SYEAR='$next_syear' AND exists(SELECT * FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=STAFF.STAFF_ID)"));
	if($exists_RET[$table][1]['COUNT']>0)
//modif Francois: add <label> on checkbox
		$table_list .= '<TR><TD><label><INPUT type="checkbox" value="Y" name="tables['.$table.']"><span style="color:grey">&nbsp;'.$name.' ('.$exists_RET[$table][1]['COUNT'].')</span></label></TD></TR>';
	else
		$table_list .= '<TR><TD><label><INPUT type="checkbox" value="Y" name="tables['.$table.']" checked />&nbsp;'.$name.'</label></TD></TR>';
}
$table_list .= '</TABLE><BR />'
                .'* '._('You <i>must</i> roll users, school periods, marking periods, calendars, attendance codes, and report card codes at the same time or before rolling courses.')
                .'<BR /><BR />* '._('You <i>must</i> roll enrollment codes at the same time or before rolling students.')
                .'<BR /><BR />* '._('You <i>must</i> roll courses at the same time or before rolling report card comments.')
                .'<BR /><BR />'._('Greyed items have already have data in the next school year (They might have been rolled).')
                .'<BR /><BR />'._('Rolling greyed items will delete already existing data in the next school year.');

DrawHeader(ProgramTitle());

//modif Francois: school year over one/two calendar years format
if(Prompt(_('Confirm').' '._('Rollover'),sprintf(_('Are you sure you want to roll the data for %s to the next school year?'),FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS'))),$table_list))
{
	if(!($_REQUEST['tables']['COURSES'] && ((!$_REQUEST['tables']['STAFF'] && $exists_RET['STAFF'][1]['COUNT']<1) || (!$_REQUEST['tables']['SCHOOL_PERIODS'] && $exists_RET['SCHOOL_PERIODS'][1]['COUNT']<1) || (!$_REQUEST['tables']['SCHOOL_MARKING_PERIODS'] && $exists_RET['SCHOOL_MARKING_PERIODS'][1]['COUNT']<1) || (!$_REQUEST['tables']['ATTENDANCE_CALENDARS'] && $exists_RET['ATTENDANCE_CALENDARS'][1]['COUNT']<1) || (!$_REQUEST['tables']['REPORT_CARD_GRADES'] && $exists_RET['REPORT_CARD_GRADES'][1]['COUNT']<1))))
	{
		if(!($_REQUEST['tables']['REPORT_CARD_COMMENTS'] && ((!$_REQUEST['tables']['COURSES'] && $exists_RET['COURSES'][1]['COUNT']<1))))
		{
			if(count($_REQUEST['tables']))
			{
				foreach($_REQUEST['tables'] as $table=>$value)
				{
					//if($exists_RET[$table][1]['COUNT']>0)
					//	DBQuery("DELETE FROM $table WHERE SYEAR='".$next_syear."'".(!$no_school_tables[$table]?" AND SCHOOL_ID='".UserSchool()."'":''));
					Rollover($table);
				}
			}
		}
		else
			$error[] = _('You <i>must</i> roll courses at the same time or before rolling report card comments.');
	}
	else
		$error[] = _('You <i>must</i> roll users, school periods, marking periods, calendars, and report card codes at the same time or before rolling courses.');

	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
//	DrawHeader('<IMG SRC=assets/check_button.png>'._('The data have been rolled.'),'<INPUT type=submit value="'._('OK').'">');

//modif Francois: Moodle integrator
	echo $moodleError;

	if (!isset($error))
		echo ErrorMessage(array('<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('The data have been rolled.')), 'note');
	else
		echo ErrorMessage($error);
		
	echo '<span class="center"><INPUT type="submit" value="'._('OK').'" /></span></FORM>';
	unset($_SESSION['_REQUEST_vars']['tables']);
	unset($_SESSION['_REQUEST_vars']['delete_ok']);
}

function Rollover($table)
{	global $next_syear,$RosarioModules,$moodleError;

	switch($table)
	{
		case 'SCHOOLS':
			DBQuery("DELETE FROM SCHOOLS WHERE SYEAR='$next_syear'");
            DBQuery("INSERT INTO SCHOOLS (SYEAR,ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,SCHOOL_NUMBER,SHORT_NAME,REPORTING_GP_SCALE,NUMBER_DAYS_ROTATION) SELECT SYEAR+1,ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,SCHOOL_NUMBER,SHORT_NAME,REPORTING_GP_SCALE,NUMBER_DAYS_ROTATION FROM SCHOOLS WHERE SYEAR='".UserSyear()."'");
		break;

		case 'STAFF':
			$user_custom='';
			$fields_RET = DBGet(DBQuery("SELECT ID FROM STAFF_FIELDS"));
			foreach($fields_RET as $field)
				$user_custom .= ',CUSTOM_'.$field['ID'];
			if($RosarioModules['Food_Service'])
			{
				DBQuery("UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET STAFF_ID=(SELECT ROLLOVER_ID FROM STAFF WHERE STAFF_ID=FOOD_SERVICE_STAFF_ACCOUNTS.STAFF_ID) WHERE exists(SELECT * FROM STAFF WHERE STAFF_ID=FOOD_SERVICE_STAFF_ACCOUNTS.STAFF_ID AND ROLLOVER_ID IS NOT NULL AND SYEAR='$next_syear')");
				DBQuery("DELETE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE exists(SELECT * FROM STAFF WHERE STAFF_ID=FOOD_SERVICE_STAFF_ACCOUNTS.STAFF_ID AND SYEAR='$next_syear')");
			}
			DBQuery("DELETE FROM STUDENTS_JOIN_USERS WHERE STAFF_ID IN (SELECT STAFF_ID FROM STAFF WHERE SYEAR='$next_syear')");
			DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID IN (SELECT STAFF_ID FROM STAFF WHERE SYEAR='$next_syear')");
			DBQuery("DELETE FROM PROGRAM_USER_CONFIG WHERE USER_ID IN (SELECT STAFF_ID FROM STAFF WHERE SYEAR='$next_syear')");
			DBQuery("DELETE FROM STAFF WHERE SYEAR='$next_syear'");

			DBQuery("INSERT INTO STAFF (STAFF_ID,SYEAR,CURRENT_SCHOOL_ID,TITLE,FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,PHONE,EMAIL,PROFILE,HOMEROOM,LAST_LOGIN,SCHOOLS,PROFILE_ID,ROLLOVER_ID$user_custom) SELECT ".db_seq_nextval('STAFF_SEQ').",SYEAR+1,CURRENT_SCHOOL_ID,TITLE,FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,PHONE,EMAIL,PROFILE,HOMEROOM,NULL,SCHOOLS,PROFILE_ID,STAFF_ID$user_custom FROM STAFF WHERE SYEAR='".UserSyear()."'");
			
//modif Francois: Moodle integrator
			if (MOODLE_INTEGRATOR)
			{
				$staff_RET = DBGet(DBQuery("SELECT STAFF_ID,ROLLOVER_ID FROM STAFF WHERE SYEAR='$next_syear' AND ROLLOVER_ID IS NOT NULL"));
				foreach($staff_RET as $value)
					DBQuery("UPDATE MOODLEXROSARIO SET ROSARIO_ID='$value[STAFF_ID]' WHERE ROSARIO_ID='$value[ROLLOVER_ID]' AND \"COLUMN\"='staff_id' AND SCHOOL_ID='".UserSchool()."'");
			}

			DBQuery("INSERT INTO PROGRAM_USER_CONFIG (USER_ID,PROGRAM,TITLE,VALUE) SELECT s.STAFF_ID,puc.PROGRAM,puc.TITLE,puc.VALUE FROM STAFF s,PROGRAM_USER_CONFIG puc WHERE puc.USER_ID=s.ROLLOVER_ID AND s.SYEAR='$next_syear'");

			DBQuery("INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT) SELECT STAFF_ID,MODNAME,CAN_USE,CAN_EDIT FROM STAFF,STAFF_EXCEPTIONS WHERE USER_ID=ROLLOVER_ID AND SYEAR='$next_syear'");

			DBQuery("INSERT INTO STUDENTS_JOIN_USERS (STUDENT_ID,STAFF_ID) SELECT j.STUDENT_ID,s.STAFF_ID FROM STAFF s,STUDENTS_JOIN_USERS j WHERE j.STAFF_ID=s.ROLLOVER_ID AND s.SYEAR='$next_syear'");
		break;

		case 'SCHOOL_PERIODS':
			DBQuery("DELETE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='$next_syear'");
			DBQuery("INSERT INTO SCHOOL_PERIODS (PERIOD_ID,SYEAR,SCHOOL_ID,SORT_ORDER,TITLE,SHORT_NAME,LENGTH,ATTENDANCE,ROLLOVER_ID) SELECT ".db_seq_nextval('SCHOOL_PERIODS_SEQ').",SYEAR+1,SCHOOL_ID,SORT_ORDER,TITLE,SHORT_NAME,LENGTH,ATTENDANCE,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
		break;

		case 'ATTENDANCE_CALENDARS':
			DBQuery("DELETE FROM ATTENDANCE_CALENDARS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='$next_syear'");
			DBQuery("INSERT INTO ATTENDANCE_CALENDARS (CALENDAR_ID,SYEAR,SCHOOL_ID,TITLE,DEFAULT_CALENDAR,ROLLOVER_ID) SELECT ".db_seq_nextval('CALENDARS_SEQ').",SYEAR+1,SCHOOL_ID,TITLE,DEFAULT_CALENDAR,CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
		break;

		case 'ATTENDANCE_CODES':
			DBQuery("DELETE FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM ATTENDANCE_CODES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("INSERT INTO ATTENDANCE_CODE_CATEGORIES (ID,SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,ROLLOVER_ID) SELECT ".db_seq_nextval('ATTENDANCE_CODE_CATEGORIES_SEQ').",SYEAR+1,SCHOOL_ID,TITLE,SORT_ORDER,ID FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("INSERT INTO ATTENDANCE_CODES (ID,SYEAR,SCHOOL_ID,TITLE,SHORT_NAME,TYPE,STATE_CODE,DEFAULT_CODE,TABLE_NAME,SORT_ORDER) SELECT ".db_seq_nextval('ATTENDANCE_CODES_SEQ').",c.SYEAR+1,c.SCHOOL_ID,c.TITLE,c.SHORT_NAME,c.TYPE,c.STATE_CODE,c.DEFAULT_CODE,".db_case(array('c.TABLE_NAME',"'0'","'0'",'(SELECT ID FROM ATTENDANCE_CODE_CATEGORIES WHERE SCHOOL_ID=c.SCHOOL_ID AND ROLLOVER_ID=c.TABLE_NAME)')).",c.SORT_ORDER FROM ATTENDANCE_CODES c WHERE c.SYEAR='".UserSyear()."' AND c.SCHOOL_ID='".UserSchool()."'");
		break;

		case 'SCHOOL_MARKING_PERIODS':
			DBQuery("DELETE FROM SCHOOL_MARKING_PERIODS WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");

			DBQuery("INSERT INTO SCHOOL_MARKING_PERIODS (MARKING_PERIOD_ID,PARENT_ID,SYEAR,MP,SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_COMMENTS,ROLLOVER_ID) SELECT ".db_seq_nextval('MARKING_PERIOD_SEQ').",PARENT_ID,SYEAR+1,MP,SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE+365,END_DATE+365,POST_START_DATE+365,POST_END_DATE+365,DOES_GRADES,DOES_COMMENTS,MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("UPDATE SCHOOL_MARKING_PERIODS SET PARENT_ID=(SELECT mp.MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS mp WHERE mp.SYEAR=school_marking_periods.SYEAR AND mp.SCHOOL_ID=school_marking_periods.SCHOOL_ID AND mp.ROLLOVER_ID=school_marking_periods.PARENT_ID) WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			
			//modif Francois: ROLL Gradebook Config's Final Grading Percentages
			$db_case_array = array('puc.TITLE');
			
			$mp_next = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,ROLLOVER_ID,MP FROM SCHOOL_MARKING_PERIODS WHERE (MP='QTR' OR MP='SEM') AND SYEAR='".$next_syear."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
			foreach($mp_next as $mp)
			{
				$db_case_array[] = "'FY-".$mp['ROLLOVER_ID']."'";
				$db_case_array[] = "'FY-".$mp['MARKING_PERIOD_ID']."'";
				if ($mp['MP'] == 'QTR')
				{
					$db_case_array[] = "'SEM-".$mp['ROLLOVER_ID']."'";
					$db_case_array[] = "'SEM-".$mp['MARKING_PERIOD_ID']."'";
				}
			}
			
			DBQuery("UPDATE PROGRAM_USER_CONFIG puc SET TITLE=(SELECT (".db_case($db_case_array).") FROM STAFF s WHERE (puc.TITLE LIKE 'FY-%' OR puc.TITLE LIKE 'SEM-%') AND puc.PROGRAM='Gradebook' AND puc.USER_ID=s.STAFF_ID AND s.SYEAR='$next_syear') FROM STAFF s WHERE (puc.TITLE LIKE 'FY-%' OR puc.TITLE LIKE 'SEM-%') AND puc.PROGRAM='Gradebook' AND puc.USER_ID=s.STAFF_ID AND s.SYEAR='$next_syear'");
		break;

		case 'COURSES':
			DBQuery("DELETE FROM COURSE_SUBJECTS WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM COURSES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE EXISTS (SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cp.SYEAR='$next_syear' AND cp.SCHOOL_ID='".UserSchool()."');");
			DBQuery("DELETE FROM COURSE_PERIODS WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");

			// ROLL COURSE_SUBJECTS
			DBQuery("INSERT INTO COURSE_SUBJECTS (SYEAR,SCHOOL_ID,SUBJECT_ID,TITLE,SHORT_NAME,SORT_ORDER,ROLLOVER_ID) SELECT SYEAR+1,SCHOOL_ID,".db_seq_nextval('COURSE_SUBJECTS_SEQ').",TITLE,SHORT_NAME,SORT_ORDER,SUBJECT_ID FROM COURSE_SUBJECTS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
			
//modif Francois: Moodle integrator
			if (MOODLE_INTEGRATOR)
			{
				$course_subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,ROLLOVER_ID FROM COURSES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."' AND ROLLOVER_ID IS NOT NULL"));
				foreach($course_subjects_RET as $value)
					DBQuery("UPDATE MOODLEXROSARIO SET ROSARIO_ID=$value[SUBJECT_ID] WHERE ROSARIO_ID=$value[ROLLOVER_ID] AND \"COLUMN\"='subject_id' AND SCHOOL_ID='".UserSchool()."'");
			}
			

			// ROLL COURSES
			DBQuery("INSERT INTO COURSES (SYEAR,COURSE_ID,SUBJECT_ID,SCHOOL_ID,GRADE_LEVEL,TITLE,SHORT_NAME,ROLLOVER_ID) SELECT SYEAR+1,".db_seq_nextval('COURSES_SEQ').",(SELECT SUBJECT_ID FROM COURSE_SUBJECTS s WHERE s.SCHOOL_ID=c.SCHOOL_ID AND s.ROLLOVER_ID=c.SUBJECT_ID),SCHOOL_ID,GRADE_LEVEL,TITLE,SHORT_NAME,COURSE_ID FROM COURSES c WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
			
//modif Francois: Moodle integrator
			if (MOODLE_INTEGRATOR)
			{
				$courses_RET = DBGet(DBQuery("SELECT COURSE_ID,ROLLOVER_ID FROM COURSES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."' AND ROLLOVER_ID IS NOT NULL"));
				foreach($courses_RET as $value)
					DBQuery("UPDATE MOODLEXROSARIO SET ROSARIO_ID='$value[COURSE_ID]' WHERE ROSARIO_ID='$value[ROLLOVER_ID]' AND \"COLUMN\"='course_id' AND SCHOOL_ID='".UserSchool()."'");
			}
			

			// ROLL COURSE_PERIODS
			DBQuery("INSERT INTO COURSE_PERIODS (SYEAR,SCHOOL_ID,COURSE_PERIOD_ID,COURSE_ID,TITLE,SHORT_NAME,MP,MARKING_PERIOD_ID,TEACHER_ID,ROOM,TOTAL_SEATS,FILLED_SEATS,DOES_ATTENDANCE,GRADE_SCALE_ID,DOES_HONOR_ROLL,DOES_CLASS_RANK,DOES_BREAKOFF,GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,AVAILABILITY,HALF_DAY,PARENT_ID,CALENDAR_ID,ROLLOVER_ID) SELECT SYEAR+1,SCHOOL_ID,".db_seq_nextval('COURSE_PERIODS_SEQ').",(SELECT COURSE_ID FROM COURSES c WHERE c.SCHOOL_ID=p.SCHOOL_ID AND c.ROLLOVER_ID=p.COURSE_ID),TITLE,SHORT_NAME,MP,(SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS n WHERE n.MP=p.MP AND n.SCHOOL_ID=p.SCHOOL_ID AND n.ROLLOVER_ID=p.MARKING_PERIOD_ID),(SELECT STAFF_ID FROM STAFF n WHERE n.ROLLOVER_ID=p.TEACHER_ID),ROOM,TOTAL_SEATS,0 AS FILLED_SEATS,DOES_ATTENDANCE,(SELECT ID FROM REPORT_CARD_GRADE_SCALES WHERE SCHOOL_ID=p.SCHOOL_ID AND ROLLOVER_ID=p.GRADE_SCALE_ID),DOES_HONOR_ROLL,DOES_CLASS_RANK,DOES_BREAKOFF,GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,AVAILABILITY,HALF_DAY,PARENT_ID,(SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SCHOOL_ID=p.SCHOOL_ID AND ROLLOVER_ID=p.CALENDAR_ID),COURSE_PERIOD_ID FROM COURSE_PERIODS p WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("UPDATE COURSE_PERIODS SET PARENT_ID=(SELECT cp.COURSE_PERIOD_ID FROM COURSE_PERIODS cp WHERE cp.ROLLOVER_ID=course_periods.PARENT_ID) WHERE PARENT_ID IS NOT NULL AND SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			$categories_RET = DBGet(DBQuery("SELECT ID,ROLLOVER_ID FROM ATTENDANCE_CODE_CATEGORIES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."' AND ROLLOVER_ID IS NOT NULL"));
			foreach($categories_RET as $value)
				DBQuery("UPDATE COURSE_PERIODS SET DOES_ATTENDANCE=replace(DOES_ATTENDANCE,',$value[ROLLOVER_ID],',',$value[ID],') WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
				
			//modif Francois: Moodle integrator
			if (MOODLE_INTEGRATOR)
			{
				$course_periods_RET = DBGet(DBQuery("SELECT cp.COURSE_PERIOD_ID, cp.COURSE_ID, cp.SHORT_NAME, cp.MARKING_PERIOD_ID, cp.TEACHER_ID FROM COURSE_PERIODS cp, MOODLEXROSARIO mxc WHERE cp.SYEAR='$next_syear' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.ROLLOVER_ID IS NOT NULL AND cp.ROLLOVER_ID=mxc.ROSARIO_ID AND mxc.\"COLUMN\"='course_period_id'"));
				foreach($course_periods_RET as $rolled_course_period)
				{
					$moodleError .= Moodle($_REQUEST['modname'], 'core_course_create_courses');
					$moodleError .= Moodle($_REQUEST['modname'], 'enrol_manual_enrol_users');
				}
			}

//modif Francois: multiple school periods for a course period
			// ROLL COURSE_PERIOD_SCHOOL_PERIODS
			DBQuery("INSERT INTO COURSE_PERIOD_SCHOOL_PERIODS (COURSE_PERIOD_SCHOOL_PERIODS_ID,COURSE_PERIOD_ID,PERIOD_ID,DAYS) SELECT ".db_seq_nextval('COURSE_PERIOD_SCHOOL_PERIODS_SEQ').",(SELECT COURSE_PERIOD_ID FROM COURSE_PERIODS cp WHERE cpsp.COURSE_PERIOD_ID=cp.ROLLOVER_ID),(SELECT PERIOD_ID FROM SCHOOL_PERIODS n WHERE n.ROLLOVER_ID=cpsp.PERIOD_ID),DAYS FROM COURSE_PERIOD_SCHOOL_PERIODS cpsp, COURSE_PERIODS cp WHERE cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID");
		break;

		case 'STUDENT_ENROLLMENT':
			$next_start_date = DBDate();
			DBQuery("DELETE FROM STUDENT_ENROLLMENT WHERE SYEAR='$next_syear' AND LAST_SCHOOL='".UserSchool()."'");
			// ROLL STUDENTS TO NEXT GRADE
			//modif Francois: do NOT roll students where next grade is NULL
			DBQuery("INSERT INTO STUDENT_ENROLLMENT (ID,SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL) SELECT ".db_seq_nextval('STUDENT_ENROLLMENT_SEQ').",SYEAR+1,SCHOOL_ID,STUDENT_ID,(SELECT NEXT_GRADE_ID FROM SCHOOL_GRADELEVELS g WHERE g.ID=e.GRADE_ID),'$next_start_date' AS START_DATE,NULL AS END_DATE,(SELECT ID FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR=e.SYEAR+1 AND TYPE='Add' AND DEFAULT_CODE='Y') AS ENROLLMENT_CODE,NULL AS DROP_CODE,(SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE ROLLOVER_ID=e.CALENDAR_ID),SCHOOL_ID,SCHOOL_ID FROM STUDENT_ENROLLMENT e WHERE e.SYEAR='".UserSyear()."' AND e.SCHOOL_ID='".UserSchool()."' AND (('".DBDate()."' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL) AND '".DBDate()."'>=e.START_DATE) AND e.NEXT_SCHOOL='".UserSchool()."' AND (SELECT NEXT_GRADE_ID FROM SCHOOL_GRADELEVELS g WHERE g.ID=e.GRADE_ID) IS NOT NULL");

			// ROLL STUDENTS WHO ARE TO BE RETAINED
			DBQuery("INSERT INTO STUDENT_ENROLLMENT (ID,SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL) SELECT ".db_seq_nextval('STUDENT_ENROLLMENT_SEQ').",SYEAR+1,SCHOOL_ID,STUDENT_ID,GRADE_ID,'$next_start_date' AS START_DATE,NULL AS END_DATE,(SELECT ID FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR=e.SYEAR+1 AND TYPE='Add' AND DEFAULT_CODE='Y') AS ENROLLMENT_CODE,NULL AS DROP_CODE,(SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE ROLLOVER_ID=e.CALENDAR_ID),SCHOOL_ID,SCHOOL_ID FROM STUDENT_ENROLLMENT e WHERE e.SYEAR='".UserSyear()."' AND e.SCHOOL_ID='".UserSchool()."' AND (('".DBDate()."' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL) AND '".DBDate()."'>=e.START_DATE) AND e.NEXT_SCHOOL='0'");

			// ROLL STUDENTS TO NEXT SCHOOL
			DBQuery("INSERT INTO STUDENT_ENROLLMENT (ID,SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL) SELECT ".db_seq_nextval('STUDENT_ENROLLMENT_SEQ').",SYEAR+1,NEXT_SCHOOL,STUDENT_ID,(SELECT g.ID FROM SCHOOL_GRADELEVELS g WHERE g.SORT_ORDER=1 AND g.SCHOOL_ID=e.NEXT_SCHOOL),'$next_start_date' AS START_DATE,NULL AS END_DATE,(SELECT ID FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR=e.SYEAR+1 AND TYPE='Add' AND DEFAULT_CODE='Y') AS ENROLLMENT_CODE,NULL AS DROP_CODE,(SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE ROLLOVER_ID=e.CALENDAR_ID),NEXT_SCHOOL,SCHOOL_ID FROM STUDENT_ENROLLMENT e WHERE e.SYEAR='".UserSyear()."' AND e.SCHOOL_ID='".UserSchool()."' AND (('".DBDate()."' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL) AND '".DBDate()."'>=e.START_DATE) AND e.NEXT_SCHOOL NOT IN ('".UserSchool()."','0','-1')");
		break;

		case 'REPORT_CARD_GRADES':
			DBQuery("DELETE FROM REPORT_CARD_GRADE_SCALES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM REPORT_CARD_GRADES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("INSERT INTO REPORT_CARD_GRADE_SCALES (ID,SYEAR,SCHOOL_ID,TITLE,COMMENT,HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ROLLOVER_ID) SELECT ".db_seq_nextval('REPORT_CARD_GRADE_SCALES_SEQ').",SYEAR+1,SCHOOL_ID,TITLE,COMMENT,HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ID FROM REPORT_CARD_GRADE_SCALES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("INSERT INTO REPORT_CARD_GRADES (ID,SYEAR,SCHOOL_ID,TITLE,COMMENT,BREAK_OFF,GPA_VALUE,GRADE_SCALE_ID,SORT_ORDER) SELECT ".db_seq_nextval('REPORT_CARD_GRADES_SEQ').",SYEAR+1,SCHOOL_ID,TITLE,COMMENT,BREAK_OFF,GPA_VALUE,(SELECT ID FROM REPORT_CARD_GRADE_SCALES WHERE ROLLOVER_ID=GRADE_SCALE_ID AND SCHOOL_ID=report_card_grades.SCHOOL_ID),SORT_ORDER FROM REPORT_CARD_GRADES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
		break;

		case 'REPORT_CARD_COMMENTS':
			DBQuery("DELETE FROM REPORT_CARD_COMMENT_CATEGORIES WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM REPORT_CARD_COMMENTS WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			DBQuery("INSERT INTO REPORT_CARD_COMMENT_CATEGORIES (ID,SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,COURSE_ID,ROLLOVER_ID) SELECT ".db_seq_nextval('REPORT_CARD_COMMENT_CATEGORIES_SEQ').",SYEAR+1,SCHOOL_ID,TITLE,SORT_ORDER,".db_case(array('COURSE_ID',"''",'NULL',"(SELECT COURSE_ID FROM COURSES WHERE ROLLOVER_ID=rc.COURSE_ID)")).",ID FROM REPORT_CARD_COMMENT_CATEGORIES rc WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
            DBQuery("INSERT INTO REPORT_CARD_COMMENTS (ID,SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,COURSE_ID,CATEGORY_ID,SCALE_ID) SELECT ".db_seq_nextval('REPORT_CARD_COMMENTS_SEQ').",SYEAR+1,SCHOOL_ID,TITLE,SORT_ORDER,".db_case(array('COURSE_ID',"''",'NULL',"(SELECT COURSE_ID FROM COURSES WHERE ROLLOVER_ID=rc.COURSE_ID)")).",".db_case(array('CATEGORY_ID',"''",'NULL',"(SELECT ID FROM REPORT_CARD_COMMENT_CATEGORIES WHERE ROLLOVER_ID=rc.CATEGORY_ID)")).",SCALE_ID FROM REPORT_CARD_COMMENTS rc WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
		break;

		case 'ELIGIBILITY_ACTIVITIES':
		case 'DISCIPLINE_CATEGORIES':
//modif Francois: discipline_field_usage rollover
		case 'DISCIPLINE_FIELD_USAGE':
			DBQuery("DELETE FROM $table WHERE SYEAR='$next_syear' AND SCHOOL_ID='".UserSchool()."'");
			$table_properties = db_properties($table);
			$columns = '';
			foreach($table_properties as $column=>$values)
			{
				if($column!='ID' && $column!='SYEAR')
					$columns .= ','.$column;
			}
			DBQuery("INSERT INTO $table (ID,SYEAR".$columns.") SELECT ".db_seq_nextval($table.'_SEQ').",SYEAR+1".$columns." FROM $table WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
		break;

		// DOESN'T HAVE A SCHOOL_ID
		case 'STUDENT_ENROLLMENT_CODES':
			DBQuery("DELETE FROM $table WHERE SYEAR='$next_syear'");
			$table_properties = db_properties($table);
			$columns = '';
			foreach($table_properties as $column=>$values)
			{
				if($column!='ID' && $column!='SYEAR')
					$columns .= ','.$column;
			}
			DBQuery("INSERT INTO $table (ID,SYEAR".$columns.") SELECT ".db_seq_nextval($table.'_SEQ').",SYEAR+1".$columns." FROM $table WHERE SYEAR='".UserSyear()."'");
		break;

		case 'FOOD_SERVICE_STAFF_ACCOUNTS':
			DBQuery("UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET STAFF_ID=(SELECT STAFF_ID FROM STAFF WHERE ROLLOVER_ID=FOOD_SERVICE_STAFF_ACCOUNTS.STAFF_ID) WHERE exists(SELECT * FROM STAFF WHERE ROLLOVER_ID=FOOD_SERVICE_STAFF_ACCOUNTS.STAFF_ID AND SYEAR='$next_syear')");
		break;
		
//modif Francois: add School Configuration
		case 'PROGRAM_CONFIG':
			DBQuery("DELETE FROM PROGRAM_CONFIG WHERE SYEAR='$next_syear'");
            DBQuery("INSERT INTO PROGRAM_CONFIG (SYEAR,SCHOOL_ID,PROGRAM,TITLE,VALUE) SELECT SYEAR+1,SCHOOL_ID,PROGRAM,TITLE,VALUE FROM PROGRAM_CONFIG WHERE SYEAR='".UserSyear()."'");
		break;
	}
}
?>