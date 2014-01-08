<?php

function Widgets($item,&$myextra=null)
{	global $extra,$_ROSARIO,$RosarioModules;

	if(isset($myextra))
		$extra =& $myextra;

	if(!is_array($_ROSARIO['Widgets']))
		$_ROSARIO['Widgets'] = array();

	if(!is_array($extra['functions']))
		$extra['functions'] = array();

	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && !$_ROSARIO['Widgets'][$item])
	{
		switch($item)
		{
			case 'all':
				$extra['search'] .= '<TR><TD colspan="2"><TABLE class="width-100p cellpadding-2" style="border-collapse:separate; border-spacing: 2px">';

				if($RosarioModules['Students'] && (!$_ROSARIO['Widgets']['calendar'] || !$_ROSARIO['Widgets']['next_year'] || !$_ROSARIO['Widgets']['enrolled'] || !$_ROSARIO['Widgets']['rolled']))
				{
//modif Francois: css WPadmin
				$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'enrollment_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="enrollment_table_arrow" height="12"> <B>'._('Enrollment').'</B></A><BR /><TABLE id="enrollment_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('calendar',$extra);
					Widgets('next_year',$extra);
					Widgets('enrolled',$extra);
					Widgets('rolled',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				//if($RosarioModules['Scheduling'] && (!$_ROSARIO['Widgets']['course'] || !$_ROSARIO['Widgets']['request']) && User('PROFILE')=='admin')
				if($RosarioModules['Scheduling'] && !$_ROSARIO['Widgets']['course'] && User('PROFILE')=='admin')
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'scheduling_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="scheduling_table_arrow" height="12"> <B>'._('Scheduling').'</B></A><BR /><TABLE id="scheduling_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('course',$extra);
					//Widgets('request',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				if($RosarioModules['Attendance'] && (!$_ROSARIO['Widgets']['absences']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'absences_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="absences_table_arrow" height="12"> <B>'._('Attendance').'</B></A><BR /><TABLE id="absences_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('absences',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				if($RosarioModules['Grades'] && (!$_ROSARIO['Widgets']['gpa'] || !$_ROSARIO['Widgets']['class_rank'] || !$_ROSARIO['Widgets']['letter_grade']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'grades_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="grades_table_arrow" height="12"> <B>'._('Grades').'</B></A><BR /><TABLE style="padding:5px;" id="grades_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('gpa',$extra);
					Widgets('class_rank',$extra);
					Widgets('letter_grade',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				if($RosarioModules['Eligibility'] && (!$_ROSARIO['Widgets']['eligibility'] || !$_ROSARIO['Widgets']['activity']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'eligibility_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="eligibility_table_arrow" height="12"> <B>'._('Eligibility').'</B></A><BR /><TABLE id="eligibility_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('eligibility',$extra);
					Widgets('activity',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				if($RosarioModules['Food_Service'] && (!$_ROSARIO['Widgets']['fsa_balance'] || !$_ROSARIO['Widgets']['fsa_discount'] || !$_ROSARIO['Widgets']['fsa_status'] || !$_ROSARIO['Widgets']['fsa_barcode']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'food_service_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="food_service_table_arrow" height="12"> <B>'._('Food Service').'</B></A><BR /><TABLE id="food_service_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('fsa_balance',$extra);
					Widgets('fsa_discount',$extra);
					Widgets('fsa_status',$extra);
					Widgets('fsa_barcode',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				if($RosarioModules['Discipline'] && (!$_ROSARIO['Widgets']['discipline'] || !$_ROSARIO['Widgets']['discipline_categories']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'discipline_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="discipline_table_arrow" height="12"> <B>'._('Discipline').'</B></A><BR /><TABLE id="discipline_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('discipline',$extra);
					Widgets('discipline_categories',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				if($RosarioModules['Student_Billing'] && (!$_ROSARIO['Widgets']['balance']))
				{
					$extra['search'] .= '<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'billing_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="billing_table_arrow" height="12"> <B>'._('Student Billing').'</B></A><BR /><TABLE id="billing_table" style="display:none;" class="widefat width-100p cellspacing-0">';
					Widgets('balance',$extra);
					$extra['search'] .= '</TABLE></TD></TR>';
				}
				$extra['search'] .= '</TABLE></TD></TR>';
			break;

			case 'user':
				$widgets_RET = DBGet(DBQuery("SELECT TITLE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='WidgetsSearch'".(count($_ROSARIO['Widgets'])?" AND TITLE NOT IN ('".implode("','",array_keys($_ROSARIO['Widgets']))."')":'')));
				foreach($widgets_RET as $widget)
					Widgets($widget['TITLE'],$extra);
			break;

			case 'course':
				if($RosarioModules['Scheduling'] && User('PROFILE')=='admin')
				{
				if($_REQUEST['w_course_period_id'])
				{
					if($_REQUEST['w_course_period_id_which']=='course')
					{
						$course = DBGet(DBQuery("SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."'"));
						$extra['FROM'] .= ",SCHEDULE w_ss";
						$extra['WHERE'] .= " AND w_ss.STUDENT_ID=s.STUDENT_ID AND w_ss.SYEAR=ssm.SYEAR AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID AND w_ss.COURSE_ID='".$course[1]['COURSE_ID']."' AND ('".DBDate()."' BETWEEN w_ss.START_DATE AND w_ss.END_DATE OR w_ss.END_DATE IS NULL)";
						if(!$extra['NoSearchTerms'])
							$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Course')).' </b>'.$course[1]['COURSE_TITLE'].'<BR />';
					}
					else
					{
						$extra['FROM'] .= ",SCHEDULE w_ss";
						$extra['WHERE'] .= " AND w_ss.STUDENT_ID=s.STUDENT_ID AND w_ss.SYEAR=ssm.SYEAR AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID AND w_ss.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."' AND ('".DBDate()."' BETWEEN w_ss.START_DATE AND w_ss.END_DATE OR w_ss.END_DATE IS NULL)";
						$course = DBGet(DBQuery("SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."'"));
						if(!$extra['NoSearchTerms'])
							$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Course Period')).' </b>'.$course[1]['COURSE_TITLE'].': '.$course[1]['TITLE'].'<BR />';
					}
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Course').'</TD><TD><DIV id="course_div"></DIV> <A HREF="#" onclick=\'window.open("Modules.php?modname=misc/ChooseCourse.php","","scrollbars=yes,resizable=yes,width=800,height=400");\'>'._('Choose').'</A></TD></TR>';
				}
			break;

			case 'request':
				if($RosarioModules['Scheduling'] && User('PROFILE')=='admin')
				{
				// PART OF THIS IS DUPLICATED IN PrintRequests.php
				if($_REQUEST['request_course_id'])
				{
					$course = DBGet(DBQuery("SELECT c.TITLE FROM COURSES c WHERE c.COURSE_ID='".$_REQUEST['request_course_id']."'"));
					if(!$_REQUEST['not_request_course'])
					{
						$extra['FROM'] .= ",SCHEDULE_REQUESTS sr";
						$extra['WHERE'] .= " AND sr.STUDENT_ID=s.STUDENT_ID AND sr.SYEAR=ssm.SYEAR AND sr.SCHOOL_ID=ssm.SCHOOL_ID AND sr.COURSE_ID='".$_REQUEST['request_course_id']."' ";
						if(!$extra['NoSearchTerms'])
							$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Request')).' </b>'.$course[1]['TITLE'].'<BR />';
					}
					else
					{
						$extra['WHERE'] .= " AND NOT EXISTS (SELECT '' FROM SCHEDULE_REQUESTS sr WHERE sr.STUDENT_ID=ssm.STUDENT_ID AND sr.SYEAR=ssm.SYEAR AND sr.COURSE_ID='".$_REQUEST['request_course_id']."' ) ";
						if(!$extra['NoSearchTerms'])
							$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Missing Request')).' </b>'.$course[1]['TITLE'].'<BR />';
					}
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Request').'</TD><TD><DIV id="request_div"></DIV> <A HREF="#" onclick=\'window.open("Modules.php?modname=misc/ChooseRequest.php","","scrollbars=yes,resizable=yes,width=800,height=400");\'>'._('Choose').'</A></TD></TR>';
				}
			break;

			case 'absences':
				if($RosarioModules['Attendance'])
				{
				if(is_numeric($_REQUEST['absences_low']) && is_numeric($_REQUEST['absences_high']))
				{
					if($_REQUEST['absences_low'] > $_REQUEST['absences_high'])
					{
						$temp = $_REQUEST['absences_high'];
						$_REQUEST['absences_high'] = $_REQUEST['absences_low'];
						$_REQUEST['absences_low'] = $temp;
					}

					if($_REQUEST['absences_low']==$_REQUEST['absences_high'])
						$extra['WHERE'] .= " AND (SELECT sum(1-STATE_VALUE) AS STATE_VALUE FROM ATTENDANCE_DAY ad WHERE ssm.STUDENT_ID=ad.STUDENT_ID AND ad.SYEAR=ssm.SYEAR AND ad.MARKING_PERIOD_ID IN (".GetChildrenMP($_REQUEST['absences_term'],UserMP()).")) = '$_REQUEST[absences_low]'";
					else
						$extra['WHERE'] .= " AND (SELECT sum(1-STATE_VALUE) AS STATE_VALUE FROM ATTENDANCE_DAY ad WHERE ssm.STUDENT_ID=ad.STUDENT_ID AND ad.SYEAR=ssm.SYEAR AND ad.MARKING_PERIOD_ID IN (".GetChildrenMP($_REQUEST['absences_term'],UserMP()).")) BETWEEN '$_REQUEST[absences_low]' AND '$_REQUEST[absences_high]'";
					switch($_REQUEST['absences_term'])
					{
						case 'FY':
							$term = _('this school year to date');
						break;
						case 'SEM':
							$term = _('this semester to date');
						break;
						case 'QTR':
							$term = _('this marking period to date');
						break;
					}
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Days Absent').'&nbsp;'.$term.' '._('Between').' </b>'.$_REQUEST['absences_low'].' &amp; '.$_REQUEST['absences_high'].'<BR />';
				}
//modif Francois: add <label> on radio
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Days Absent').'<BR /><label><INPUT type="radio" name="absences_term" value="FY" checked />&nbsp;'._('YTD').'</label>&nbsp; <label><INPUT type="radio" name="absences_term" value="SEM">&nbsp;'.GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME').'</label>&nbsp; <label><INPUT type="radio" name="absences_term" value="QTR">&nbsp;'.GetMP(UserMP(),'SHORT_NAME').'</label></TD><TD>'._('Between').' <INPUT type="text" name="absences_low" size="3" maxlength="5"> &amp; <INPUT type="text" name="absences_high" size="3" maxlength="5"></TD></TR>';
				}
			break;

			case 'gpa':
				if($RosarioModules['Grades'])
				{
				if(is_numeric($_REQUEST['gpa_low']) && is_numeric($_REQUEST['gpa_high']))
				{
					if($_REQUEST['gpa_low'] > $_REQUEST['gpa_high'])
					{
						$temp = $_REQUEST['gpa_high'];
						$_REQUEST['gpa_high'] = $_REQUEST['gpa_low'];
						$_REQUEST['gpa_low'] = $temp;
					}
					if($_REQUEST['list_gpa'])
					{
//modif Francois: remove STUDENT_GPA_CALCULATED table
						/*$extra['SELECT'] .= ',sgc.WEIGHTED_GPA,sgc.UNWEIGHTED_GPA';
						$extra['columns_after']['WEIGHTED_GPA'] = _('Weighted GPA');
						$extra['columns_after']['UNWEIGHTED_GPA'] = _('Unweighted GPA');*/
						$extra['SELECT'] .= ',sms.CUM_WEIGHTED_FACTOR,sms.CUM_UNWEIGHTED_FACTOR';
						$extra['columns_after']['CUM_WEIGHTED_FACTOR'] = _('Weighted GPA');
						$extra['columns_after']['CUM_UNWEIGHTED_FACTOR'] = _('Unweighted GPA');
					}
					/*if(mb_strpos($extra['FROM'],'STUDENT_GPA_CALCULATED sgc')===false)
					{
						$extra['FROM'] .= ",STUDENT_GPA_CALCULATED sgc";
						$extra['WHERE'] .= " AND sgc.STUDENT_ID=s.STUDENT_ID AND sgc.MARKING_PERIOD_ID='".$_REQUEST['gpa_term']."'";
					}*/
					if(mb_strpos($extra['FROM'],'STUDENT_MP_STATS sms')===false)
					{
						$extra['FROM'] .= ",STUDENT_MP_STATS sms";
						$extra['WHERE'] .= " AND sms.STUDENT_ID=s.STUDENT_ID AND sms.MARKING_PERIOD_ID='".$_REQUEST['gpa_term']."'";
					}
					//$extra['WHERE'] .= " AND sgc.".(($_REQUEST['weighted']=='Y')?'WEIGHTED_':'')."GPA BETWEEN '$_REQUEST[gpa_low]' AND '$_REQUEST[gpa_high]' AND sgc.MARKING_PERIOD_ID='".$_REQUEST['gpa_term']."'";
					$extra['WHERE'] .= " AND sms.CUM_".(($_REQUEST['weighted']=='Y')?'':'UN')."WEIGHTED_FACTOR*(SELECT GP_SCALE FROM REPORT_CARD_GRADE_SCALES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."') BETWEEN '$_REQUEST[gpa_low]' AND '$_REQUEST[gpa_high]'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'.(($_REQUEST['gpa_weighted']=='Y')?_('Weighted GPA').' ':_('Unweighted GPA').' ').Localize('colon',_('Between')).' </b>'.$_REQUEST['gpa_low'].' &amp; '.$_REQUEST['gpa_high'].'<BR />';
				}
//modif Francois: add <label> on checkbox
//modif Francois: replace Cumulative by Full Year
				//$extra['search'] .= "<TR><TD style="text-align:right;">"._('GPA')."<BR /><label><INPUT type=checkbox name=gpa_weighted value=Y>&nbsp;"._('Weighted').'</label><BR /><label><INPUT type="radio" name="gpa_term" value=CUM checked />&nbsp;'._('Cumulative').'</label>&nbsp; <label><INPUT type="radio" name="gpa_term" value="'.GetParentMP('SEM',UserMP()).'">&nbsp;'.GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME').'</label> &nbsp;<label><INPUT type="radio" name="gpa_term" value="'.UserMP().'">&nbsp;'.GetMP(UserMP(),'SHORT_NAME')."</label></TD><TD>"._('Between')." <INPUT type="text" name=gpa_low size=3 maxlength=5> &amp; <INPUT type="text" name=gpa_high size=3 maxlength=5></TD></TR>";
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('GPA').'<BR /><label><INPUT type="checkbox" name="weighted" value="Y">&nbsp;'._('Weighted').'</label><BR />'.(GetMP($MPfy = GetParentMP('FY',GetParentMP('SEM',UserMP())),'DOES_GRADES') == 'Y'?'<label><INPUT type="radio" name="gpa_term" value="'.$MPfy.'" checked />&nbsp;'.GetMP($MPfy,'SHORT_NAME').'</label>&nbsp; ':'').(GetMP($MPsem = GetParentMP('SEM',UserMP()),'DOES_GRADES') == 'Y'?'<label><INPUT type="radio" name="gpa_term" value="'.$MPsem.'">&nbsp;'.GetMP($MPsem,'SHORT_NAME').'</label> &nbsp;':'').(GetMP($MPtrim = UserMP(),'DOES_GRADES') == 'Y'?'<label><INPUT type="radio" name="gpa_term" value="'.$MPtrim.'" checked />&nbsp;'.GetMP($MPtrim,'SHORT_NAME').'</label>':'').'</TD><TD>'._('Between').' <INPUT type="text" name="gpa_low" size="3" maxlength="5"> &amp; <INPUT type="text" name="gpa_high" size="3" maxlength="5"></TD></TR>';
				}
			break;

			case 'class_rank':
				if($RosarioModules['Grades'])
				{
				if(is_numeric($_REQUEST['class_rank_low']) && is_numeric($_REQUEST['class_rank_high']))
				{
					if($_REQUEST['class_rank_low'] > $_REQUEST['class_rank_high'])
					{
						$temp = $_REQUEST['class_rank_high'];
						$_REQUEST['class_rank_high'] = $_REQUEST['class_rank_low'];
						$_REQUEST['class_rank_low'] = $temp;
					}
//modif Francois: remove STUDENT_GPA_CALCULATED table
					/*if(mb_strpos($extra['FROM'],'STUDENT_GPA_CALCULATED sgc')===false)
					{
						$extra['FROM'] .= ",STUDENT_GPA_CALCULATED sgc";
						$extra['WHERE'] .= " AND sgc.STUDENT_ID=s.STUDENT_ID AND sgc.MARKING_PERIOD_ID='".$_REQUEST['class_rank_term']."'";
					}*/
					if(mb_strpos($extra['FROM'],'STUDENT_MP_STATS sms')===false)
					{
						$extra['FROM'] .= ",STUDENT_MP_STATS sms";
						$extra['WHERE'] .= " AND sms.STUDENT_ID=s.STUDENT_ID AND sms.MARKING_PERIOD_ID='".$_REQUEST['class_rank_term']."'";
					}
					//$extra['WHERE'] .= " AND sgc.CLASS_RANK BETWEEN '$_REQUEST[class_rank_low]' AND '$_REQUEST[class_rank_high]'";
					$extra['WHERE'] .= " AND sms.CUM_RANK BETWEEN '$_REQUEST[class_rank_low]' AND '$_REQUEST[class_rank_high]'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Class Rank')).' '._('Between').'</b>'.$_REQUEST['class_rank_low'].' &amp; '.$_REQUEST['class_rank_high'].'<BR />';
				}
//modif Francois: replace Cumulative by Full Year
				//$extra['search'] .= "<TR><TD style="text-align:right;">"._('Class Rank').'<BR /><label><INPUT type="radio" name="class_rank_term" value=CUM checked />&nbsp;'._('Cumulative').'</label> &nbsp;<label><INPUT type="radio" name="class_rank_term" value="'.GetParentMP('SEM',UserMP()).'">&nbsp;'.GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME').'</label> &nbsp;<label><INPUT type="radio" name="class_rank_term" value="'.UserMP().'">&nbsp;'.GetMP(UserMP(),'SHORT_NAME');
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Class Rank').'<BR />'.(GetMP($MPfy = GetParentMP('FY',GetParentMP('SEM',UserMP())),'DOES_GRADES') == 'Y'?'<label><INPUT type="radio" name="class_rank_term" value="'.$MPfy.'">&nbsp;'.GetMP($MPfy,'SHORT_NAME').'</label>&nbsp; ':'').(GetMP($MPsem = GetParentMP('SEM',UserMP()),'DOES_GRADES') == 'Y'?'<label><INPUT type="radio" name="class_rank_term" value="'.$MPsem.'">&nbsp;'.GetMP($MPsem,'SHORT_NAME').'</label> &nbsp;':'').(GetMP($MPtrim = UserMP(),'DOES_GRADES') == 'Y'?'<label><INPUT type="radio" name="class_rank_term" value="'.$MPtrim.'" checked />&nbsp;'.GetMP($MPtrim,'SHORT_NAME').'</label>':'');
				if(mb_strlen($pros = GetChildrenMP('PRO',UserMP())))
				{
					$pros = explode(',',str_replace("'",'',$pros));
					foreach($pros as $pro)
						$extra['search'] .= '<label><INPUT type="radio" name="class_rank_term" value="'.$pro.'">&nbsp;'.GetMP($pro,'SHORT_NAME').'</label> &nbsp;';
				}
				$extra['search'] .= '</TD><TD>'._('Between').' <INPUT type="text" name="class_rank_low" size="3" maxlength="5"> &amp; <INPUT type="text" name="class_rank_high" size="3" maxlength="5"></TD></TR>';
				}
			break;

			case 'letter_grade':
				if($RosarioModules['Grades'])
				{
				if(count($_REQUEST['letter_grade']))
				{
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'.($_REQUEST['letter_grade_exclude']=='Y'?_('Without'):_('With')).' '._('Report Card Grade').': </b>';
					$letter_grades_RET = DBGet(DBQuery("SELECT ID,TITLE FROM REPORT_CARD_GRADES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"),array(),array('ID'));
					foreach($_REQUEST['letter_grade'] as $grade=>$Y)
					{
						$letter_grades .= ",'$grade'";
						if(!$extra['NoSearchTerms'])
							$_ROSARIO['SearchTerms'] .= $letter_grades_RET[$grade][1]['TITLE'].', ';
					}
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] = mb_substr($_ROSARIO['SearchTerms'],0,-2).'<BR />';
					$extra['WHERE'] .= " AND ".($_REQUEST['letter_grade_exclude']=='Y'?'NOT ':'')."EXISTS (SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg3 WHERE sg3.STUDENT_ID=ssm.STUDENT_ID AND sg3.SYEAR=ssm.SYEAR AND sg3.REPORT_CARD_GRADE_ID IN (".mb_substr($letter_grades,1).") AND sg3.MARKING_PERIOD_ID='".$_REQUEST['letter_grade_term']."' )";
				}

				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Grade').'<BR /><label><INPUT type="checkbox" name="letter_grade_exclude" value="Y">&nbsp;'._('Did not receive').'</label><BR /><label><span class="nobr"><INPUT type="radio" name="letter_grade_term" value="'.GetParentMP('SEM',UserMP()).'">&nbsp;'.GetMP(GetParentMP('SEM',UserMP()),'SHORT_NAME').'</span></label>&nbsp;<label><span class="nobr"><INPUT type="radio" name="letter_grade_term" value="'.UserMP().'">&nbsp;'.GetMP(UserMP(),'SHORT_NAME').'</span></label>';
				if(mb_strlen($pros = GetChildrenMP('PRO',UserMP())))
				{
					$pros = explode(',',str_replace("'",'',$pros));
					foreach($pros as $pro)
						$extra['search'] .= '<label><span class="nobr"><INPUT type="radio" name="letter_grade_term" value="'.$pro.'">&nbsp;'.GetMP($pro,'SHORT_NAME').'</span></label>&nbsp;';
				}
				$extra['search'] .= '</TD><TD style="max-width: 270px;">';
//modif Francois: fix error Invalid argument supplied for foreach()
				if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
				{
					$letter_grades_RET = DBGet(DBQuery("SELECT rg.ID,rg.TITLE,rg.GRADE_SCALE_ID FROM REPORT_CARD_GRADES rg,REPORT_CARD_GRADE_SCALES rs WHERE rg.SCHOOL_ID='".UserSchool()."' AND rg.SYEAR='".UserSyear()."' AND rs.ID=rg.GRADE_SCALE_ID".(User('PROFILE')=='teacher'?' AND rg.GRADE_SCALE_ID=(SELECT GRADE_SCALE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID=\''.UserCoursePeriod().'\')':'')." ORDER BY rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER"),array(),array('GRADE_SCALE_ID'));
					foreach($letter_grades_RET as $grades)
					{
						$i = 0;
						if(count($grades))
						{
							foreach($grades as $grade)
							{
								$extra['search'] .= '<label><INPUT type="checkbox" value="Y" name="letter_grade['.$grade['ID'].']">'.$grade['TITLE'].'</label>&nbsp; ';
								$i++;
							}
						}
					}
				}
				$extra['search'] .= '</TD></TR>';
				}
			break;

			case 'eligibility':
				if($RosarioModules['Eligibility'])
				{
				if($_REQUEST['ineligible']=='Y')
				{
					$start_end_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_CONFIG WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND PROGRAM='eligibility' AND TITLE IN ('START_DAY','END_DAY')"));
					if(count($start_end_RET))
					{
						foreach($start_end_RET as $value)
							$$value['TITLE'] = $value['VALUE'];
					}

					switch(date('D'))
					{
						case 'Mon':
						$today = 1;
						break;
						case 'Tue':
						$today = 2;
						break;
						case 'Wed':
						$today = 3;
						break;
						case 'Thu':
						$today = 4;
						break;
						case 'Fri':
						$today = 5;
						break;
						case 'Sat':
						$today = 6;
						break;
						case 'Sun':
						$today = 7;
						break;
					}

					$start_date = mb_strtoupper(date('d-M-y',time() - ($today-$START_DAY)*60*60*24));
					$end_date = mb_strtoupper(date('d-M-y',time()));
					$extra['WHERE'] .= " AND (SELECT count(*) FROM ELIGIBILITY e WHERE ssm.STUDENT_ID=e.STUDENT_ID AND e.SYEAR=ssm.SYEAR AND e.SCHOOL_DATE BETWEEN '$start_date' AND '$end_date' AND e.ELIGIBILITY_CODE='FAILING') > '0'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Eligibility')).' </b>'._('Ineligible').'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right; "></TD><TD><label><INPUT type="checkbox" name="ineligible" value="Y">&nbsp;'._('Ineligible').'</label></TD></TR>';
				}
			break;

			case 'activity':
				if($RosarioModules['Eligibility'])
				{
				if($_REQUEST['activity_id'])
				{
					$extra['FROM'] .= ",STUDENT_ELIGIBILITY_ACTIVITIES sea";
					$extra['WHERE'] .= " AND sea.STUDENT_ID=s.STUDENT_ID AND sea.SYEAR=ssm.SYEAR AND sea.ACTIVITY_ID='".$_REQUEST['activity_id']."'";
					$activity = DBGet(DBQuery("SELECT TITLE FROM ELIGIBILITY_ACTIVITIES WHERE ID='".$_REQUEST['activity_id']."'"));
					if(!$extra['NoSearchTerms'])
//modif Francois: add translation
					$_ROSARIO['SearchTerms'] .= '<b>'._('Activity').': </b>'.$activity[1]['TITLE'].'<BR />';
				}
				if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
					$activities_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ELIGIBILITY_ACTIVITIES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
				$select = '<SELECT name="activity_id"><OPTION value="">'._('Not Specified').'</OPTION>';
				if(count($activities_RET))
				{
					foreach($activities_RET as $activity)
						$select .= '<OPTION value="'.$activity['ID'].'">'.$activity['TITLE'].'</OPTION>';
				}
				$select .= '</SELECT>';
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Activity').'</TD><TD>'.$select.'</TD></TR>';
				}
			break;

			case 'mailing_labels':
				if($_REQUEST['mailing_labels']=='Y')
				{
					$extra['SELECT'] .= ',coalesce(sam.ADDRESS_ID,-ssm.STUDENT_ID) AS ADDRESS_ID,sam.ADDRESS_ID AS MAILING_LABEL';
					$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (sam.STUDENT_ID=ssm.STUDENT_ID AND sam.MAILING='Y'".($_REQUEST['residence']=='Y'?" AND sam.RESIDENCE='Y'":'').")".$extra['FROM'];
					$extra['functions'] += array('MAILING_LABEL'=>'MailingLabel');
				}

				$extra['search'] .= '<TR><TD style="text-align:right; width:130px"><label>'._('Mailing Labels').'&nbsp;<INPUT type="checkbox" name="mailing_labels" value="Y"></label></TD>';
			break;

			case 'balance':
				if($RosarioModules['Student_Billing'])
				{
				if(is_numeric($_REQUEST['balance_low']) && is_numeric($_REQUEST['balance_high']))
				{
					if($_REQUEST['balance_low'] > $_REQUEST['balance_high'])
					{
						$temp = $_REQUEST['balance_high'];
						$_REQUEST['balance_high'] = $_REQUEST['balance_low'];
						$_REQUEST['balance_low'] = $temp;
					}
					$extra['WHERE'] .= " AND (coalesce((SELECT sum(f.AMOUNT) FROM BILLING_FEES f,STUDENTS_JOIN_FEES sjf WHERE sjf.FEE_ID=f.ID AND sjf.STUDENT_ID=ssm.STUDENT_ID AND f.SYEAR=ssm.SYEAR),0)+(SELECT coalesce(sum(f.AMOUNT),0)-coalesce(sum(f.CASH),0) FROM LUNCH_TRANSACTIONS f WHERE f.STUDENT_ID=ssm.STUDENT_ID AND f.SYEAR=ssm.SYEAR)-coalesce((SELECT sum(p.AMOUNT) FROM BILLING_PAYMENTS p WHERE p.STUDENT_ID=ssm.STUDENT_ID AND p.SYEAR=ssm.SYEAR),0)) BETWEEN '$_REQUEST[balance_low]' AND '$_REQUEST[balance_high]' ";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Student Billing Balance')).' </b>'._('Between').' '.$_REQUEST['balance_low'].' &amp; '.$_REQUEST['balance_high'].'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Balance').'<BR /></TD><TD>'._('Between').' <INPUT type="text" name="balance_low" size="5" maxlength="10"> &amp; <INPUT type="text" name="balance_high" size="5" maxlength="10"></TD></TR>';
				}
			break;

			case 'discipline':
				if($RosarioModules['Discipline'])
				{
				if(is_array($_REQUEST['discipline']))
				{
					foreach($_REQUEST['discipline'] as $key=>$value)
					{
						if(!$value)
							unset($_REQUEST['discipline'][$key]);
					}
				}
				if($_REQUEST['month_discipline_entry_begin'] && $_REQUEST['day_discipline_entry_begin'] && $_REQUEST['year_discipline_entry_begin'])
				{
					$_REQUEST['discipline_entry_begin'] = $_REQUEST['day_discipline_entry_begin'].'-'.$_REQUEST['month_discipline_entry_begin'].'-'.$_REQUEST['year_discipline_entry_begin'];
					if(!VerifyDate($_REQUEST['discipline_entry_begin']))
						unset($_REQUEST['discipline_entry_begin']);
					unset($_REQUEST['day_discipline_entry_begin']);unset($_REQUEST['month_discipline_entry_begin']);unset($_REQUEST['year_discipline_entry_begin']);
				}
				if($_REQUEST['month_discipline_entry_end'] && $_REQUEST['day_discipline_entry_end'] && $_REQUEST['year_discipline_entry_end'])
				{
					$_REQUEST['discipline_entry_end'] = $_REQUEST['day_discipline_entry_end'].'-'.$_REQUEST['month_discipline_entry_end'].'-'.$_REQUEST['year_discipline_entry_end'];
					if(!VerifyDate($_REQUEST['discipline_entry_end']))
						unset($_REQUEST['discipline_entry_end']);
					unset($_REQUEST['day_discipline_entry_end']);unset($_REQUEST['month_discipline_entry_end']);unset($_REQUEST['year_discipline_entry_end']);
				}
				if($_REQUEST['discipline_reporter'] || $_REQUEST['discipline_entry_begin'] || $_REQUEST['discipline_entry_end'] || count($_REQUEST['discipline']) || count($_REQUEST['discipline_begin']) || count($_REQUEST['discipline_end']))
				{
					$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SYEAR=ssm.SYEAR AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';
					$extra['FROM'] .= ',DISCIPLINE_REFERRALS dr ';
				}
				$users_RET = DBGet(DBQuery("SELECT STAFF_ID,FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND (SCHOOLS IS NULL OR SCHOOLS LIKE '%,".UserSchool().",%') AND (PROFILE='admin' OR PROFILE='teacher') ORDER BY LAST_NAME,FIRST_NAME,MIDDLE_NAME"),array(),array('STAFF_ID'));
				if($_REQUEST['discipline_reporter'])
				{
					$extra['WHERE'] .= " AND dr.STAFF_ID='$_REQUEST[discipline_reporter]' ";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Reporter').': </b>'.$users_RET[$_REQUEST['discipline_reporter']][1]['LAST_NAME'].', '.$users_RET[$_REQUEST['discipline_reporter']][1]['FIRST_NAME'].' '.$users_RET[$_REQUEST['discipline_reporter']][1]['MIDDLE_NAME'].'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Reporter').'</TD><TD>';
				$extra['search'] .= '<SELECT name=discipline_reporter><OPTION value="">'._('Not Specified').'</OPTION>';
				foreach($users_RET as $id=>$user)
					$extra['search'] .= '<OPTION value='.$id.'>'.$user[1]['LAST_NAME'].', '.$user[1]['FIRST_NAME'].' '.$user[1]['MIDDLE_NAME'].'</OPTION>';
				$extra['search'] .= '</SELECT>';
				$extra['search'] .= '</TD></TR>';

				$discipline_entry_begin_for_ProperDate = $_REQUEST['discipline_entry_begin'];
				if (mb_strlen($_REQUEST['discipline_entry_begin']) > 10) //date = LAST_LOGIN = date + time
					$discipline_entry_begin_for_ProperDate = mb_substr($_REQUEST['discipline_entry_begin'], 0, 10);
					
				if($_REQUEST['discipline_entry_begin'] && $_REQUEST['discipline_entry_end'])
				{
					$extra['WHERE'] .= " AND dr.ENTRY_DATE BETWEEN '$_REQUEST[discipline_entry_begin]' AND '$_REQUEST[discipline_entry_end]' ";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Incident Date').' '._('Between').': </b>'.ProperDate($discipline_entry_begin_for_ProperDate).'<b> '._('and').' </b>'.ProperDate($_REQUEST['discipline_entry_end']).'<BR />';
				}
				elseif($_REQUEST['discipline_entry_begin'])
				{
					$extra['WHERE'] .= " AND dr.ENTRY_DATE>='$_REQUEST[discipline_entry_begin]' ";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Incident Entered').' '._('On or After').' </b>'.ProperDate($discipline_entry_begin_for_ProperDate).'<BR />';
				}
				elseif($_REQUEST['discipline_entry_end'])
				{
					$extra['WHERE'] .= " AND dr.ENTRY_DATE<='$_REQUEST[discipline_entry_end]' ";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Incident Entered').' '._('On or Before').' </b>'.ProperDate($_REQUEST['discipline_entry_end']).'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Incident Date').'</TD><TD><table class="cellpadding-0 cellspacing-0"><tr><td><span class="sizep2">&ge;</span>&nbsp;</td><td>'.PrepareDate('','_discipline_entry_begin',true,array('short'=>true)).'</td></tr><tr><td><span class="sizep2">&le;</span>&nbsp;</td><td>'.PrepareDate('','_discipline_entry_end',true,array('short'=>true)).'</td></tr></table></TD></TR>';
				}
			/*break;

			case 'discipline_categories':*/
				if($RosarioModules['Discipline'])
				{
				$categories_RET = DBGet(DBQuery("SELECT f.ID,u.TITLE,f.DATA_TYPE,u.SELECT_OPTIONS FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u WHERE u.DISCIPLINE_FIELD_ID=f.ID AND u.SYEAR='".UserSyear()."' AND u.SCHOOL_ID='".UserSchool()."' AND f.DATA_TYPE!='textarea'"));
				foreach($categories_RET as $category)
				{
					if($category['DATA_TYPE']!='date')
					{
						$extra['search'] .= '<TR><TD width="150">'.$category['TITLE'].'</TD><TD>';
						switch($category['DATA_TYPE'])
						{
							case 'text':
								$extra['search'] .= '<INPUT type="text" name="discipline['.$category['ID'].']" />';
								if($_REQUEST['discipline'][$cateogory['ID']])
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." LIKE '".$_REQUEST['discipline'][$cateogory['ID']]."%' ";
							break;
							case 'checkbox':
								$extra['search'] .= '<INPUT type="checkbox" name="discipline['.$category['ID'].']" value="Y" />';
								if($_REQUEST['discipline'][$cateogory['ID']])
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." = 'Y' ";
							break;
							case 'numeric':
								$extra['search'] .= '<small>'._('Between').' </small><INPUT type="text" name="discipline_begin['.$category['ID'].']" size="3" maxlength="11" /> & <INPUT type="text" name="discipline_end['.$category['ID'].']" size="3" maxlength="11" />';
								if($_REQUEST['discipline_begin'][$cateogory['ID']] && $_REQUEST['discipline_begin'][$cateogory['ID']])
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." BETWEEN '".$_REQUEST['discipline_begin'][$cateogory['ID']]."' AND '".$_REQUEST['discipline_end'][$cateogory['ID']]."' ";
							break;
							case 'multiple_checkbox':
							case 'multiple_radio':
							case 'select':
								$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
								$category['SELECT_OPTIONS'] = explode("\r",$category['SELECT_OPTIONS']);
								
								$extra['search'] .= '<SELECT name="discipline['.$category['ID'].']"><OPTION value="">'._('N/A').'</OPTION>';
								foreach($category['SELECT_OPTIONS'] as $option)
									$extra['search'] .= '<OPTION value="'.$option.'">'.$option.'</OPTION>';
								$extra['search'] .= '</SELECT>';
								if(($category['DATA_TYPE']=='multiple_radio' || $category['DATA_TYPE']=='select') && $_REQUEST['discipline'][$category['ID']])
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." = '".$_REQUEST['discipline'][$category['ID']]."' ";
								elseif($category['DATA_TYPE']=='multiple_checkbox' && $_REQUEST['discipline'][$category['ID']])
									$extra['WHERE'] .= " AND dr.CATEGORY_".$category['ID']." LIKE '%||".$_REQUEST['discipline'][$category['ID']]."||%' ";
							break;
						}
						$extra['search'] .= '</TD></TR>';
					}
				}
				}
			break;

			case 'next_year':
				if($RosarioModules['Students'])
				{
				$schools_RET = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOLS WHERE ID!='".UserSchool()."' AND SYEAR='".UserSyear()."'"),array(),array('ID'));
				if($_REQUEST['next_year']=='!')
				{
					$extra['WHERE'] .= " AND ssm.NEXT_SCHOOL IS NULL";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Next Year')).' </b>'._('No Value').'<BR />';
				}
				elseif($_REQUEST['next_year']!='')
				{
					$extra['WHERE'] .= " AND ssm.NEXT_SCHOOL='".$_REQUEST['next_year']."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'.Localize('colon',_('Next Year')).' </b>'.($_REQUEST['next_year']==UserSchool()?'Next grade at current school':($_REQUEST['next_year']=='0'?'Retain':($_REQUEST['next_year']=='-1'?'Do not enroll after this school year':$schools_RET[$_REQUEST['next_year']][1]['TITLE']))).'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Next Year').'</TD><TD><SELECT name="next_year"><OPTION value="">'._('N/A').'</OPTION><OPTION value="!">'._('No Value').'</OPTION><OPTION value="'.UserSchool().'">'._('Next grade at current school').'</OPTION><OPTION value="0">'._('Retain').'</OPTION><OPTION value="-1">'._('Do not enroll after this school year').'</OPTION>';
				foreach($schools_RET as $id=>$school)
					$extra['search'] .= '<OPTION value='.$id.'>'.$school[1]['TITLE'].'</OPTION>';
				$extra['search'] .= '</SELECT></TD></TR>';
				}
			break;

			case 'calendar':
				if($RosarioModules['Students'])
				{
				$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID,TITLE FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY DEFAULT_CALENDAR ASC"),array(),array('CALENDAR_ID'));
				if($_REQUEST['calendar']=='!')
				{
					$extra['WHERE'] .= " AND ssm.CALENDAR_ID IS ".($_REQUEST['calendar_not']=='Y'?'NOT ':'')."NULL";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Calendar').': </b>'.($_REQUEST['calendar_not']=='Y'?_('Any Value'):_('No Value')).'<BR />';
				}
				elseif($_REQUEST['calendar']!='')
				{
					$extra['WHERE'] .= " AND ssm.CALENDAR_ID".($_REQUEST['calendar_not']=='Y'?'!':'')."='".$_REQUEST['calendar']."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Calendar').': </b>'.($_REQUEST['calendar_not']=='Y'?_('Not').' ':'').$calendars_RET[$_REQUEST['calendar']][1]['TITLE'].'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Calendar').'</TD><TD><label><INPUT type="checkbox" name="calendar_not" value="Y"> '._('Not').' </label><SELECT name="calendar"><OPTION value="">'._('N/A').'</OPTION><OPTION value="!">'._('No Value').'</OPTION>';
				foreach($calendars_RET as $id=>$calendar)
					$extra['search'] .= '<OPTION value="'.$id.'">'.$calendar[1]['TITLE'].'</OPTION>';
				$extra['search'] .= '</SELECT></TD></TR>';
				}
			break;

			case 'enrolled':
				if($RosarioModules['Students'])
				{
				if($_REQUEST['month_enrolled_begin'] && $_REQUEST['day_enrolled_begin'] && $_REQUEST['year_enrolled_begin'])
				{
					$_REQUEST['enrolled_begin'] = $_REQUEST['day_enrolled_begin'].'-'.$_REQUEST['month_enrolled_begin'].'-'.$_REQUEST['year_enrolled_begin'];
					if(!VerifyDate($_REQUEST['enrolled_begin']))
						unset($_REQUEST['enrolled_begin']);
				}
				if($_REQUEST['month_enrolled_end'] && $_REQUEST['day_enrolled_end'] && $_REQUEST['year_enrolled_end'])
				{
					$_REQUEST['enrolled_end'] = $_REQUEST['day_enrolled_end'].'-'.$_REQUEST['month_enrolled_end'].'-'.$_REQUEST['year_enrolled_end'];
					if(!VerifyDate($_REQUEST['enrolled_end']))
						unset($_REQUEST['enrolled_end']);
				}
				if($_REQUEST['enrolled_begin'] && $_REQUEST['enrolled_end'])
				{
					$extra['WHERE'] .= " AND ssm.START_DATE BETWEEN '".$_REQUEST['enrolled_begin']."' AND '".$_REQUEST['enrolled_end']."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Enrolled').' '._('Between').': </b>'.ProperDate($_REQUEST['enrolled_begin']).' and '.ProperDate($_REQUEST['enrolled_end']).'<BR />';
				}
				elseif($_REQUEST['enrolled_begin'])
				{
					$extra['WHERE'] .= " AND ssm.START_DATE>='".$_REQUEST['enrolled_begin']."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Enrolled').' '._('On or After').': </b>'.ProperDate($_REQUEST['enrolled_begin']).'<BR />';
				}
				if($_REQUEST['enrolled_end'])
				{
					$extra['WHERE'] .= " AND ssm.START_DATE<='".$_REQUEST['enrolled_end']."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Enrolled').' '._('On or Before').': </b>'.ProperDate($_REQUEST['enrolled_end']).'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Attendance Start').'</TD><TD><table class="cellpadding-0 cellspacing-0"><tr><td><span class="sizep2">&ge;</span>&nbsp;</td><td>'.PrepareDate('','_enrolled_begin',true,array('short'=>true)).'</td></tr><tr><td><span class="sizep2">&le;</span>&nbsp;</td><td>'.PrepareDate('','_enrolled_end',true,array('short'=>true)).'</td></tr></table></TD></TR>';
				}
			break;

			case 'rolled':
				if($RosarioModules['Students'])
				{
				if($_REQUEST['rolled'])
				{
					$extra['WHERE'] .= " AND ".($_REQUEST['rolled']=='Y'?'':'NOT ')."exists (SELECT '' FROM STUDENT_ENROLLMENT WHERE STUDENT_ID=ssm.STUDENT_ID AND SYEAR<ssm.SYEAR)";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Previously Enrolled').': </b>'.($_REQUEST['rolled']=='Y'?_('Yes'):_('No')).'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Previously Enrolled').'</TD><TD><label><INPUT type="radio" value="" name="rolled" checked /> '._('N/A').'</label> &nbsp;<label><INPUT type="radio" value="Y" name="rolled"> '._('Yes').'</label> &nbsp;<label><INPUT type="radio" value="N" name="rolled"> '._('No').'</label></TD></TR>';
				}
			break;

			case 'fsa_balance_warning':
				$value = $GLOBALS['warning'];
				$item = 'fsa_balance';
			case 'fsa_balance':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_balance']!='')
				{
					if (!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ',FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
						$extra['WHERE'] .= ' AND fssa.STUDENT_ID=s.STUDENT_ID';
					}
					$extra['FROM'] .= ",FOOD_SERVICE_ACCOUNTS fsa";
					$extra['WHERE'] .= " AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fsa.BALANCE".($_REQUEST['fsa_bal_ge']=='Y'?'>=':'<')."'".round($_REQUEST['fsa_balance'],2)."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Food Service Balance').': </b><span class="sizep2">'.($_REQUEST['fsa_bal_ge']=='Y'?'&ge;':'&lt;').number_format($_REQUEST['fsa_balance'],2).'</span><BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Balance').'</TD><TD><table class="cellpadding-0 cellspacing-0"><tr><td><label><span class="sizep2">&lt;</span> <INPUT type="radio" name="fsa_bal_ge" value="" checked /></label></td><td rowspan="2"><INPUT type="text" name="fsa_balance" size=10'.($value?' value="'.$value.'"':'').'></label></td></tr><tr><td><label><span class="sizep2">&ge;</span> <INPUT type="radio" name="fsa_bal_ge" value=Y></label></td></tr></table></TD></TR>';
				}
			break;

			case 'fsa_discount':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_discount'])
				{
					if(!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					if($_REQUEST['fsa_discount']=='Full')
						$extra['WHERE'] .= " AND fssa.DISCOUNT IS NULL";
					else
						$extra['WHERE'] .= " AND fssa.DISCOUNT='".$_REQUEST['fsa_discount']."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Food Service Discount').': </b>'.$_REQUEST['fsa_discount'].'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Discount').'</TD><TD><SELECT name=fsa_discount><OPTION value="">'._('Not Specified').'</OPTION><OPTION value="Full">'._('Full').'</OPTION><OPTION value="Reduced">'._('Reduced').'</OPTION><OPTION value="Free">'._('Free').'</OPTION></SELECT></TD></TR>';
				}
			break;

			case 'fsa_status_active':
				$value = 'active';
				$item = 'fsa_status';
			case 'fsa_status':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_status']) {
					if (!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					if($_REQUEST['fsa_status']=='Active')
						$extra['WHERE'] .= " AND fssa.STATUS IS NULL";
					else
						$extra['WHERE'] .= " AND fssa.STATUS='".$_REQUEST['fsa_status']."'";
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Account Status').'</TD><TD><SELECT name=fsa_status><OPTION value="">'._('Not Specified').'</OPTION><OPTION value="Active"'.($value=='active'?' SELECTED="SELECTED"':'').'>'._('Active').'</OPTION><OPTION value="Inactive">'._('Inactive').'</OPTION><OPTION value="Disabled">'._('Disabled').'</OPTION><OPTION value="Closed">'._('Closed').'</OPTION></SELECT></TD></TR>';
				}
			break;

			case 'fsa_barcode':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_barcode'])
				{
					if (!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					$extra['WHERE'] .= " AND fssa.BARCODE='".$_REQUEST['fsa_barcode']."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Food Service Barcode').': </b>'.$_REQUEST['fsa_barcode'].'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Barcode').'</TD><TD><INPUT type="text" name="fsa_barcode" size="15"></TD></TR>';
				}
			break;

			case 'fsa_account_id':
				if($RosarioModules['Food_Service'])
				{
				if($_REQUEST['fsa_account_id'])
				{
					if (!mb_strpos($extra['FROM'],'fssa'))
					{
						$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";
						$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
					}
					$extra['WHERE'] .= " AND fssa.ACCOUNT_ID='".($_REQUEST['fsa_account_id']+0)."'";
					if(!$extra['NoSearchTerms'])
						$_ROSARIO['SearchTerms'] .= '<b>'._('Food Service Account ID').': </b>'.($_REQUEST['fsa_account_id']+0).'<BR />';
				}
				$extra['search'] .= '<TR><TD style="text-align:right;">'._('Account ID').'</TD><TD><INPUT type="text" name="fsa_account_id" size="15"></TD></TR>';
				}
			break;
		}
		$_ROSARIO['Widgets'][$item] = true;
	}
}
?>
