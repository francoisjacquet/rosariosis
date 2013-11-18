<?php

//modif Francois: add School Configuration
$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='grades'"),array(),array('TITLE'));

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['mp_type_arr']) && count($_REQUEST['st_arr']))
	{
		$mp_type_list = '\''.implode('\',\'',$_REQUEST['mp_type_arr']).'\'';

		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';

		$t_grades = DBGet(DBQuery("select * from transcript_grades where student_id in (".$st_list.") and mp_type in (".$mp_type_list.") and school_id=".$_REQUEST['SCHOOL_ID']." and syear=".$_REQUEST['syear']." ORDER BY end_date"),array(),array('STUDENT_ID', 'MARKING_PERIOD_ID'));
		
		if(count($t_grades))
		{
			
			$showStudentPic = $_REQUEST['showstudentpic'];
			$showSAT = $_REQUEST['showsat'];
			//modif Francois: add Show Grades option
			$showGrades = $_REQUEST['showgrades'];
			//modif Francois: add Show Comments option
			$showMPcomments = $_REQUEST['showmpcomments'];
			//modif Francois: add Show Credits option
			$showCredits = $_REQUEST['showcredits'];
			//modif Francois: add Show Credit Hours option
			$showCreditHours = $_REQUEST['showcredithours'];
			//modif Francois: add Show Studies Certificate option
			$showCertificate = $_REQUEST['showcertificate'];
			if ($showCertificate)
			{
				//modif Francois: add Template
				$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = 'Grades/Transcripts.php' AND STAFF_ID = '".User('STAFF_ID')."'"));
				if (!$template_update)
					DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('Grades/Transcripts.php', '".User('STAFF_ID')."', '".$_REQUEST['inputcertificatetext']."')");
				else
					DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$_REQUEST['inputcertificatetext']."' WHERE MODNAME = 'Grades/Transcripts.php' AND STAFF_ID = '".User('STAFF_ID')."'");
				
				$certificateText = explode('__BLOCK2__', $_REQUEST['inputcertificatetext']);
			}
			
			$students_dataquery = "select
			s.student_id 
			, s.first_name
			, s.last_name
			, s.middle_name
			, s.custom_200000000 as gender
			, s.custom_200000003 as ssecurity
			, s.custom_200000004 as birthdate".
			//, s.custom_200000012 as estgraddate
			", a.address
			, a.city
			, a.state
			, a.zipcode
			, a.phone
			, a.mail_address
			, a.mail_city
			, a.mail_state
			, a.mail_zipcode
			, (select start_date from student_enrollment where student_id = s.student_id order by syear, start_date limit 1) as init_enroll
			, (select sgl.title from school_gradelevels sgl join student_enrollment se on (sgl.id = se.grade_id) where se.syear = ".$_REQUEST['syear']." and se.student_id = s.student_id and (se.end_date is null or se.start_date < se.end_date) order by se.start_date desc limit 1) as grade_level
			, (select sgl2.title from school_gradelevels sgl2, school_gradelevels sgl join student_enrollment se on (sgl.id = se.grade_id) where se.syear = ".$_REQUEST['syear']." and se.student_id = s.student_id and (se.end_date is null or se.start_date < se.end_date) and sgl2.id = sgl.next_grade_id order by se.start_date desc limit 1) as next_grade_level
			from 
			students s  
			left outer join students_join_address sja on (sja.student_id = s.student_id)
			left outer join address a on (a.address_id = sja.address_id) "; 
			$students_data = DBGet(DBQuery($students_dataquery.' where s.student_id in ('.$st_list.') order by last_name, first_name'),array(),array('STUDENT_ID'));

			
			$handle = PDFStart();

			echo '<style type="text/css"> * {font-size:large; line-height:1.2;} </style>';
			
			$columns = array('COURSE_TITLE'=>_('Course'));

			$school_info = DBGet(DBQuery('select * from schools where syear = '.$_REQUEST['syear'].' AND id = '.$_REQUEST['SCHOOL_ID']));
			$school_info = $school_info[1];
					
			foreach($t_grades as $student_id=>$mps)
			{
				$student_data = $students_data[$student_id][1];

				echo '<table class="width-100p"><tr class="valign-top"><td>';
				//Student Photo
				$stu_pic =  $StudentPicturesPath.Config('SYEAR').'/'.$student_id.'.jpg';
				$stu_pic2 =  $StudentPicturesPath.$_REQUEST['syear'].'/'.$student_id.'.jpg';
				$picwidth = 70;
				if (file_exists($stu_pic) && $showStudentPic){
					echo '<img src="'.$stu_pic.'" width="'.$picwidth.'" />';
				} 
				elseif (file_exists($stu_pic2) && $showStudentPic){
					echo '<img src="'.$stu_pic2.'" width="'.$picwidth.'" />';
				}
				else
					echo '&nbsp;';

				echo '</td><td>';
				
				//Student Info
				echo '<span style="font-size:x-large;">'.$student_data['LAST_NAME'].', '.$student_data['FIRST_NAME'].'<br /></span>';
				echo '<span>'.$student_data['ADDRESS'].'<br /></span>';
				echo '<span>'.$student_data['CITY'].(!empty($student_data['STATE'])?', '.$student_data['STATE']:'').(!empty($student_data['ZIPCODE'])?'  '.$student_data['ZIPCODE']:'').'</span>';
				
				echo '<table class="cellspacing-0 cellpadding-5 center" style="width:300px; margin-top:10px;"><tr>';
				echo '<td style="border:solid black; border-width:1px 0 1px 1px; font-size:x-small;">'._('Birthdate').'</td>';
				echo '<td style="border:solid black; border-width:1px 0 1px 1px; font-size:x-small;">'._('Gender').'</td>';
				echo '<td style="border:solid black; border-width:1px; font-size:x-small;">'._('Grade Level').'</td>';
				echo '</tr><tr>';
				$dob = explode('-', $student_data['BIRTHDATE']);
				if (!empty($dob))
					echo '<td style="font-size:small;">'.$dob[1].'/'.$dob[2].'/'.$dob[0].'</td>';
				else
					echo '<td>&nbsp;</td>';
				echo '<td style="font-size:small;">'._($student_data['GENDER']).'</td>';
				echo '<td style="font-size:small;">'.$student_data['GRADE_LEVEL'].'</td>';
				echo '</tr></table>';
				
				echo '</td>';

				//School logo
				$logo_pic =  'assets/school_logo.jpg';
				$picwidth = 120;
				echo '<td style="width:'.$picwidth.'px;">';
				if (file_exists($logo_pic)){
					echo '<img src="'.$logo_pic.'" width="'.$picwidth.'" />';
				}

				echo '</td>';

				//School Info
				echo '<td style="width:384px; text-align:right;"><table style="width:384px; text-align:left;"><tr><td>';
				echo '<span style="font-size:x-large;">'.$school_info['TITLE'].'<br /></span>';
				echo '<span>'.$school_info['ADDRESS'].'<br /></span>';
				echo '<span>'.$school_info['CITY'].(!empty($school_info['STATE'])?', '.$school_info['STATE']:'').(!empty($school_info['ZIPCODE'])?'  '.$school_info['ZIPCODE']:'').'<br /></span>';
				if($school_info['PHONE'])
					echo '<span>'._('Phone').': '.$school_info['PHONE'].'<br /></span>';
				if($school_info['WWW_ADDRESS'])
					echo '<span>'._('Website').': '.$school_info['WWW_ADDRESS'].'<br /></span>';
				if($school_info['SCHOOL_NUMBER'])
					echo '<span>'._('School Number').': '.$school_info['SCHOOL_NUMBER'].'<br /><br /></span>';
				echo '<span>'.$school_info['PRINCIPAL'].'<br /></span>';				
				
				echo '</td></tr></table></td></tr>';
				
				//Certificate Text block 1
				if ($showCertificate)
				{
					echo '<tr><td colspan="4">';
					echo '<br /><span style="font-size:x-large;" class="center">'._('Studies Certificate').'<br /></span>';
					$certificateText[0] = str_replace(array('__SSECURITY__','__FULL_NAME__','__FIRST_NAME__','__LAST_NAME__','__MIDDLE_NAME__','__GRADE_ID__','__NEXT_GRADE_ID__','__YEAR__','__SCHOOL_ID__'),array($student_data['SSECURITY'],$student_data['FULL_NAME'],$student_data['FIRST_NAME'],$student_data['LAST_NAME'],$student_data['MIDDLE_NAME'],$student_data['GRADE_LEVEL'],$student_data['NEXT_GRADE_LEVEL'],$_REQUEST['syear'],$school_info['TITLE']),$certificateText[0]);
					echo '<span>'.nl2br(trim($certificateText[0])).'</span>';
					echo '</td></tr>';
				}
				
				echo '</table>';
				
				//generate ListOutput friendly array
				$listOutput_RET = array();
				$total_credit_earned = 0;
				$total_credit_attempted = 0;
				foreach($mps as $mp_id=>$grades)
				{
					$columns[$mp_id] = $grades[1]['SHORT_NAME'];
					//$i = 1;
					foreach($grades as $grade)
					{
						$i = $grade['COURSE_TITLE'];
						$course_area = CourseTitleArea($grade['COURSE_TITLE']);
						if (!empty($course_area) && $course_area != $course_area_temp)
						{
							//$i_temp = $grade['COURSE_TITLE'];
							$listOutput_RET[$course_area]['COURSE_TITLE'] = '<span style="font-size:inherit; font-weight:bold;">'.$course_area.':</span>';
							$course_area_temp = $course_area;
							//$i++;
						}
						if (!empty($course_area))
							$listOutput_RET[$i]['COURSE_TITLE'] = '&nbsp;&nbsp;&nbsp;'.CourseTitle($grade['COURSE_TITLE']);
						else
							$listOutput_RET[$i]['COURSE_TITLE'] = $grade['COURSE_TITLE'];
						if ($showGrades)
						{
							if ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE'] > 0)
								$listOutput_RET[$i][$mp_id] = $grade['GRADE_PERCENT'].'%';
							elseif ($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE'] < 0)
								$listOutput_RET[$i][$mp_id] = $grade['GRADE_LETTER'];
							else
								$listOutput_RET[$i][$mp_id] = $grade['GRADE_LETTER'].'&nbsp;&nbsp;'.$grade['GRADE_PERCENT'].'%';
						}
						if ($showCredits)
						{
							if (!isset($listOutput_RET[$i]['CREDIT_EARNED']))
							{
								$listOutput_RET[$i]['CREDIT_EARNED'] = sprintf('%01.2f', $grade['CREDIT_EARNED']);
								$total_credit_earned += $grade['CREDIT_EARNED'];
								$total_credit_attempted += $grade['CREDIT_ATTEMPTED'];
								if (!empty($course_area))
									$listOutput_RET[$course_area]['CREDIT_EARNED'] += sprintf('%01.2f', $grade['CREDIT_EARNED']);
							}
						}
						if ($showCreditHours)
						{
							if (!isset($listOutput_RET[$i]['CREDIT_HOURS']))
							{
								$listOutput_RET[$i]['CREDIT_HOURS'] = ((int)$grade['CREDIT_HOURS'] == $grade['CREDIT_HOURS'] ? (int)$grade['CREDIT_HOURS'] : $grade['CREDIT_HOURS']);
								if (!empty($course_area))
									$listOutput_RET[$course_area]['CREDIT_HOURS'] += $grade['CREDIT_HOURS'];
							}
						}
						if ($showMPcomments)
							$listOutput_RET[$i]['COMMENT'] = $grade['COMMENT'];
						//$i++;
					}
				}
				if ($showCredits)
					$columns['CREDIT_EARNED'] = _('Credit');
				if ($showCreditHours)
					$columns['CREDIT_HOURS'] = _('C.H.');
				if ($showMPcomments)
					$columns['COMMENT'] = _('Comment');
					
				$listOutput_RET = array_values($listOutput_RET);
				array_unshift($listOutput_RET,'start_array_to_1');
				unset($listOutput_RET[0]);
				//var_dump($listOutput_RET);exit;
				ListOutput($listOutput_RET,$columns,'.','.',false);
			
				//School Year
				echo '<table class="width-100p"><tr><td>';
				echo '<span><br />'._('School Year').': '.FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS')).'</span>';
				echo '</td></tr>';
				
				//Class Rank
				if ($showGrades)
					if ($grade['MP_TYPE']!='quarter' && !empty($grade['CUM_WEIGHTED_GPA']) && !empty($grade['CUM_RANK']))
					{
						echo '<tr><td>';
						echo '<span>'.sprintf(_('GPA').': %01.2f / %01.0f', $grade['CUM_WEIGHTED_GPA'], $grade['GP_SCALE']).' &ndash; '._('Class Rank').': '.$grade['CUM_RANK'].' / '.$grade['CLASS_SIZE'].'</span>';
						echo '</td></tr>';
					}

				//Total Credits
				if ($showCredits)
					if ($total_credit_attempted > 0)
					{
						echo '<tr><td>';
						echo '<span>'._('Total').' '._('Credit').': '._('Credit Attempted').': '.sprintf('%01.2f', $total_credit_attempted).' &ndash; '._('Credit Earned').': '.sprintf('%01.2f', $total_credit_earned).'</span>';
						echo '</td></tr>';
					}

				//Certificate Text block 2
				if ($showCertificate && !empty($certificateText[1]))
				{
					$certificateText[1] = str_replace(array('__SSECURITY__','__FULL_NAME__','__FIRST_NAME__','__LAST_NAME__','__MIDDLE_NAME__','__GRADE_ID__','__NEXT_GRADE_ID__','__YEAR__','__SCHOOL_ID__'),array($student_data['SSECURITY'],$student_data['FULL_NAME'],$student_data['FIRST_NAME'],$student_data['LAST_NAME'],$student_data['MIDDLE_NAME'],$student_data['GRADE_LEVEL'],$student_data['NEXT_GRADE_LEVEL'],$_REQUEST['syear'],$school_info['TITLE']),$certificateText[1]);
					echo '<tr><td><br /><span>'.nl2br(trim($certificateText[1])).'</span></td></tr>';
				}
				
				echo '</table>';
				
				//Signatures
				echo '<br /><br /><br /><table class="width-100p" style="border-collapse:separate; border-spacing: 40px;"><tr><td style="width:50%;">';
				echo '<table class="width-100p"><tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">'._('Signature').'<br /><br /><br /></span></td></tr>';
				echo '<tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">'._('Title').'</span></td></tr></table>';
				echo '</td><td style="width:50%;">';
				//modif Francois: add second signature for the certificate
				if ($showCertificate)
				{
					echo '<table class="width-100p"><tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">'._('Signature').'<br /><br /><br /></span></td></tr>';
					echo '<tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">'._('Title').'</span></td></tr></table>';
				}
				echo '</td></tr></table>';
				
				echo '<div style="page-break-after: always;"></div>';
			}
			PDFStop($handle);
		}
		else
			BackPrompt(_('No Students were found.'));
	}
	else
		BackPrompt(_('You must choose at least one student and one marking period.'));
}

if(empty($_REQUEST['modfunc']))

{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		//modif Francois: include gentranscript.php in Transcripts.php
		//echo '<FORM action="modules/Grades/gentranscript.php" method="POST">';
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = '<INPUT type="submit" value="'._('Create Transcripts for Selected Students').'" />';

		$extra['extra_header_left'] = '<TABLE>';

		$extra['extra_header_left'] .= '<TR><TD colspan="2"><b>'.Localize('colon',_('Include on Transcript')).'</b><INPUT type="hidden" name="SCHOOL_ID" value="'.UserSchool().'"><BR /></TD></TR>';
        $mp_types = DBGet(DBQuery("SELECT DISTINCT MP_TYPE FROM MARKING_PERIODS WHERE NOT MP_TYPE IS NULL AND SCHOOL_ID='".UserSchool()."'"),array(),array());
        $extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align:top;">'._('Marking Periods').':</TD><TD><TABLE><TR class="st"><TD  style="vertical-align:top;"><TABLE>';
//modif Francois: add translation
		$marking_periods_locale = array('Year'=>_('Year'), 'Semester'=>_('Semester'), 'Quarter'=>_('Quarter'));
		foreach($mp_types as $mp_type)
		{
			$extra['extra_header_left'] .= '<TR>';
//modif Francois: add <label> on checkbox
//			$extra['extra_header_left'] .= '<TD><label><INPUT type=checkbox name=mp_type_arr[] value='.$mp_type['MP_TYPE'].'>'.ucwords($mp_type['MP_TYPE']).'</label></TD>';              
			$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="mp_type_arr[]" value="'.$mp_type['MP_TYPE'].'"> '.$marking_periods_locale[ucwords($mp_type['MP_TYPE'])].'</label></TD>';              
            $extra['extra_header_left'] .= '</TR>';
		}
		$extra['extra_header_left'] .= '</TABLE></TD>';
        $extra['extra_header_left'] .= '<INPUT type="hidden" name="syear" value="'.UserSyear().'">';
        $extra['extra_header_left'] .= '<TD style="vertical-align:top;">'._('Other Options').':</TD>';
        $extra['extra_header_left'] .= '<TD><TABLE>';
        //modif Francois: add Show Grades option
		$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="checkbox" name="showgrades" value="1" checked /> '._('Grades').'</label></TD></TR>';
        $extra['extra_header_left'] .= '<TR><TD><label><INPUT type="checkbox" name="showstudentpic" value="1"> '._('Student Photo').'</label></TD></TR>';
        //modif Francois: add Show Comments option
		$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="checkbox" name="showmpcomments" value="1"> '._('Comments').'</label></TD></TR>';
        //modif Francois: add Show Credits option
		$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="checkbox" name="showcredits" value="1" checked /> '._('Credits').'</label></TD></TR>';
		//modif Francois: add Show Credit Hours option
		$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="checkbox" name="showcredithours" value="1"> '._('Credit Hours').'</label></TD></TR>';
		//modif Francois: limit Cetificate to admin
		if (User('PROFILE')=='admin')
		{
			//modif Francois: add Show Studies Certificate option
			$field_SSECURITY = ParseMLArray(DBGet(DBQuery("SELECT TITLE FROM CUSTOM_FIELDS WHERE ID = 200000003")),'TITLE');
			
			$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="checkbox" name="showcertificate" value="1" onclick=\'javascript: document.getElementById("divcertificatetext").style.display="block"; document.getElementById("inputcertificatetext").focus();\'> '._('Studies Certificate').'</label></TD></TR>';
			
			//modif Francois: add Template
			$templates = DBGet(DBQuery("SELECT TEMPLATE, STAFF_ID FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID IN (0,'".User('STAFF_ID')."')"), array(), array('STAFF_ID'));
		}
        //$extra['extra_header_left'] .= '<TR><TD><INPUT type=checkbox name=showsat value=1>SAT Scores</TD></TR>';
        $extra['extra_header_left'] .= '</TABLE>';

		$extra['extra_header_left'] .= '</TD><TD></TD></TR></TABLE></TR>';
		$extra['extra_header_left'] .= '</TABLE>';

		//modif Francois: limit Cetificate to admin
		if (User('PROFILE')=='admin')
		{
			//modif Francois: add Show Studies Certificate option
			$extra['extra_header_left'] .= '<DIV id="divcertificatetext" style="display:none"><TEXTAREA id="inputcertificatetext" name="inputcertificatetext" cols="100" rows="5">'.str_replace(array("'",'"'),array('&#39;','&rdquo;',''),($templates[User('STAFF_ID')] ? $templates[User('STAFF_ID')][1]['TEMPLATE'] : $templates[0][1]['TEMPLATE'])).'</TEXTAREA><BR /><span class="legend-gray">'.str_replace(array("'",'"'),array('&#39;','\"'),_('Certificate Studies Text')).'</span>
			<TABLE><TR><TD style="text-align:right; vertical-align: top;">'.Localize('colon',_('Substitutions')).'</TD><TD><TABLE><TR>';
			$extra['extra_header_left'] .= '<TD>__SSECURITY__</TD><TD>= '.str_replace(array("'",'"'),array('&#39;','\"'),$field_SSECURITY[1]['TITLE']).'</TD><TD colspan="3">&nbsp;</TD>';
			$extra['extra_header_left'] .= '</TR><TR>';
			$extra['extra_header_left'] .= '<TD>__FULL_NAME__</TD><TD>= '._('Last, First M').'</TD><TD>&nbsp;</TD>';
			$extra['extra_header_left'] .= '<TD>__LAST_NAME__</TD><TD>= '._('Last Name').'</TD>';
			$extra['extra_header_left'] .= '</TR><TR>';
			$extra['extra_header_left'] .= '<TD>__FIRST_NAME__</TD><TD>= '._('First Name').'</TD><TD>&nbsp;</TD>';
			$extra['extra_header_left'] .= '<TD>__MIDDLE_NAME__</TD><TD>= '._('Middle Name').'</TD>';
			$extra['extra_header_left'] .= '</TR><TR>';
			$extra['extra_header_left'] .= '<TD>__GRADE_ID__</TD><TD>= '._('Grade Level').'</TD><TD>&nbsp;</TD>';
			$extra['extra_header_left'] .= '<TD>__NEXT_GRADE_ID__</TD><TD>= '._('Next Grade').'</TD>';
			$extra['extra_header_left'] .= '</TR><TR>';
			$extra['extra_header_left'] .= '<TD>__SCHOOL_ID__</TD><TD>= '._('School').'</TD><TD>&nbsp;</TD>';
			$extra['extra_header_left'] .= '<TD>__YEAR__</TD><TD>= '._('School Year').'</TD>';
			$extra['extra_header_left'] .= '</TR><TR>';
			$extra['extra_header_left'] .= '<TD>__BLOCK2__</TD><TD>= '._('Text Block 2').'</TD><TD colspan="3">&nbsp;</TD>';
			$extra['extra_header_left'] .= '</TR></TABLE></TD></TR></TABLE></DIV>';
		}
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');" /><A>');
	$extra['new'] = true;
	$extra['options']['search'] = false;
	$extra['force_search'] = true;

	Widgets('course');
	Widgets('gpa');
	Widgets('class_rank');
	Widgets('letter_grade');

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center"><INPUT type="submit" value="'._('Create Transcripts for Selected Students').'" /></span>';
		echo '</FORM>';
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}
?>
