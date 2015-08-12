<?php

DrawHeader(ProgramTitle());

if(!$_REQUEST['modfunc'] && $_REQUEST['search_modfunc']!='list')
	unset($_SESSION['MassDrops.php']);

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if($_SESSION['MassDrops.php'])
	{
		if (isset($_REQUEST['student']) && is_array($_REQUEST['student']))
		{
			$END_DATE = $_REQUEST['day'].'-'.$_REQUEST['month'].'-'.$_REQUEST['year'];
			if(VerifyDate($END_DATE))
			{
				$course_mp = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'"));
				$course_mp = $course_mp[1]['MARKING_PERIOD_ID'];
				$course_mp_table = GetMP($course_mp,'MP');

				if($course_mp_table=='FY' || $course_mp==$_REQUEST['marking_period_id'] || mb_strpos(GetChildrenMP($course_mp_table,$course_mp),"'".$_REQUEST['marking_period_id']."'")!==false)
				{
					$mp_table = GetMP($_REQUEST['marking_period_id'],'MP');
					//$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM SCHEDULE WHERE COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."' AND SYEAR='".UserSyear()."' AND (('".$start_date."' BETWEEN START_DATE AND END_DATE OR END_DATE IS NULL) AND '".$start_date."'>=START_DATE)"),array(),array('STUDENT_ID'));
					$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM SCHEDULE WHERE COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."' "));

					foreach($_REQUEST['student'] as $student_id=>$yes)
					{
						if($current_RET[$student_id])
						{
							DBQuery("UPDATE SCHEDULE SET END_DATE='".$END_DATE."' WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'");

							//$start_end_RET = DBGet(DBQuery("SELECT START_DATE,END_DATE FROM SCHEDULE WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND END_DATE<START_DATE"));
							$start_end_RET = DBGet(DBQuery("SELECT START_DATE,END_DATE FROM SCHEDULE WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."' AND END_DATE<START_DATE"));
							
							//User is asked if he wants absences and grades to be deleted
							if(count($start_end_RET))
							{
								//if user clicked Cancel or OK or Display Prompt
								if(DeletePrompt(_('Students\' Absences and Grades'), 'Delete', false))
								{
									//if user clicked OK
									if ($_REQUEST['delete_ok'])
									{
										DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'");
										DBQuery("DELETE FROM STUDENT_REPORT_CARD_GRADES WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'");
										DBQuery("DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'");
										DBQuery("DELETE FROM ATTENDANCE_PERIOD WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'");
									}
									//else simply delete schedule entry

									DBQuery("DELETE FROM SCHEDULE WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'");

									//hook
									do_action('Scheduling/MassDrops.php|drop_student');
								}
								else
									$schedule_deletion_pending = true;
							}
							else
								DBQuery("DELETE FROM ATTENDANCE_PERIOD WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."' AND SCHOOL_DATE>'".$END_DATE."'");
						}
					}

					if(empty($schedule_deletion_pending))
						$note[] = button('check') .'&nbsp;'._('This course has been dropped for the selected students\' schedules.');
				}
				else
					$error[] = _('You cannot schedule a student into that course during this marking period.').' '.sprintf(_('This course meets on %s.'),GetMP($course_mp));
			}
			else
				$error[] = _('The date you entered is not valid');
		}
		else
			$error[] = _('You must choose at least one student.');
	}
	else
		$error[] = _('You must choose a course.');

	if(empty($schedule_deletion_pending))
	{
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		unset($_REQUEST['modfunc']);
		unset($_SESSION['MassDrops.php']);
	}
}


if (isset($error))
	echo ErrorMessage($error);
if(isset($note))
	echo ErrorMessage($note, 'note');

if($_REQUEST['modfunc']!='choose_course')
{
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		DrawHeader('',SubmitButton(_('Drop Course for Selected Students')));

		echo '<BR />';

		PopTable('header', _('Course to Drop'));

		echo '<TABLE><TR><TD colspan="2"><DIV id=course_div>';

		if($_SESSION['MassDrops.php'])
		{
			$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_SESSION['MassDrops.php']['course_id']."'"));
			$course_title = $course_title[1]['TITLE'];
			$period_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'"));
			$period_title = $period_title[1]['TITLE'];

			echo $course_title.'<BR />'.$period_title;
		}
		echo '</DIV>'.'<A HREF="#" onclick=\'window.open("Modules.php?modname='.$_REQUEST['modname'].'&modfunc=choose_course","","scrollbars=yes,resizable=yes,width=800,height=400");\'>'._('Choose a Course').'</A></TD></TR>';
		echo '<TR class="st"><TD>'._('Drop Date').'</TD><TD>'.PrepareDate(DBDate(),'').'</TD></TR>';

		echo '<TR class="st"><TD>'._('Marking Period').'</TD><TD>';
		echo '<SELECT name=marking_period_id>';
		$mp_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,".db_case(array('MP',"'FY'","'0'","'SEM'","'1'","'QTR'","'2'"))." AS TBL FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TBL,SORT_ORDER"));
		foreach($mp_RET as $mp)
			echo '<OPTION value="'.$mp['MARKING_PERIOD_ID'].'">'.$mp['TITLE'].'</OPTION>';
		echo '</SELECT>';
		echo '</TD></TR></TABLE>';

		PopTable('footer');

		echo '<BR />';
	}
}

if(empty($_REQUEST['modfunc']))

{
	if($_REQUEST['search_modfunc']!='list')
		unset($_SESSION['MassDrops.php']);
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
	$extra['new'] = true;

	Widgets('course');
	Widgets('request');
	Widgets('activity');

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Drop Course for Selected Students')).'</span>';
		echo '</FORM>';
	}
}

if($_REQUEST['modfunc']=='choose_course')
{

	if(!$_REQUEST['course_period_id'])
		include 'modules/Scheduling/Courses.php';
	else
	{
		$_SESSION['MassDrops.php']['subject_id'] = $_REQUEST['subject_id'];
		$_SESSION['MassDrops.php']['course_id'] = $_REQUEST['course_id'];
		$_SESSION['MassDrops.php']['course_period_id'] = $_REQUEST['course_period_id'];

		$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_SESSION['MassDrops.php']['course_id']."'"));
		$course_title = $course_title[1]['TITLE'];
		$period_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."'"));
		$period_title = $period_title[1]['TITLE'];

		echo '<script>opener.document.getElementById("course_div").innerHTML = '.json_encode($course_title.'<BR />'.$period_title).'; window.close();</script>';
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y">';
}
