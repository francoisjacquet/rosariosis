<?php


if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['mp_arr']) && count($_REQUEST['st_arr']))
	{
	$mp_list = '\''.implode('\',\'',$_REQUEST['mp_arr']).'\'';
	$last_mp = end($_REQUEST['mp_arr']);
	$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';

	$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";
	$extra['SELECT'] .= ",sg1.GRADE_LETTER as GRADE_TITLE,sg1.GRADE_PERCENT,sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,sg1.COURSE_TITLE as COURSE_TITLE,rc_cp.TITLE AS TEACHER,sp.SORT_ORDER";

	if($_REQUEST['elements']['period_absences']=='Y')
		$extra['SELECT'] .= ",rc_cp.DOES_ATTENDANCE,
				(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE='A' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
				(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE='A' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.MARKING_PERIOD_ID=cast(ap.MARKING_PERIOD_ID as text) AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES";

	if($_REQUEST['elements']['comments']=='Y')
	{
		$custom_fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE FROM CUSTOM_FIELDS WHERE ID=200000000"),array(),array('ID'));
		if ($custom_fields_RET['200000000'] && $custom_fields_RET['200000000'][1]['TYPE'] == 'select')
			$extra['SELECT'] .= ',s.CUSTOM_200000000 AS GENDER';
		else
			$extra['SELECT'] .= ',\'None\' AS GENDER';
				
	}
	//modif Francois: multiple school periods for a course period
	//$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1,ATTENDANCE_CODES ac,COURSE_PERIODS rc_cp,SCHOOL_PERIODS sp";
	$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1,ATTENDANCE_CODES ac,COURSE_PERIODS rc_cp,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp";
	/*$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
					AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=rc_cp.PERIOD_ID";*/
	$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
					AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=cpsp.PERIOD_ID 
					AND rc_cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID";
	$extra['ORDER'] .= ",sp.SORT_ORDER,ac.TITLE";
	$extra['functions']['TEACHER'] = '_makeTeacher';
	$extra['group']	= array('STUDENT_ID','COURSE_PERIOD_ID','MARKING_PERIOD_ID');

	$RET = GetStuList($extra);

	if($_REQUEST['elements']['comments']=='Y')
	{
		// GET THE COMMENTS
		unset($extra);
		$extra['WHERE'] = " AND s.STUDENT_ID IN (".$st_list.")";
		//modif Francois: order General Comments first
		$extra['SELECT_ONLY'] = "s.STUDENT_ID,sc.COURSE_PERIOD_ID,sc.MARKING_PERIOD_ID,sc.REPORT_CARD_COMMENT_ID,sc.COMMENT,(SELECT SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER,(SELECT COALESCE(SCALE_ID, 0) FROM REPORT_CARD_COMMENTS WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER2";
		$extra['FROM'] = ",STUDENT_REPORT_CARD_COMMENTS sc";
		//modif Francois: get the comments of all MPs
		//$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID='".$last_mp."'";
		$extra['WHERE'] .= " AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID IN (".$mp_list.")";
		$extra['ORDER_BY'] = 'SORT_ORDER,SORT_ORDER2';
		$extra['group'] = array('STUDENT_ID','COURSE_PERIOD_ID','MARKING_PERIOD_ID');

		$comments_RET = GetStuList($extra);
		//echo '<pre>'; print_r($comments_RET); echo '</pre>'; exit;

		$all_commentsA_RET = DBGet(DBQuery("SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NOT NULL AND COURSE_ID='0' ORDER BY SORT_ORDER,ID"),array(),array('ID'));

		//modif Francois: get color for Course specific categories & get comment scale
		//$commentsA_RET = DBGet(DBQuery("SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NOT NULL AND COURSE_ID!='0'"),array(),array('ID'));
		$commentsA_RET = DBGet(DBQuery("SELECT c.ID,c.TITLE,c.SORT_ORDER,cc.COLOR,cs.TITLE AS SCALE_TITLE
		FROM REPORT_CARD_COMMENTS c, REPORT_CARD_COMMENT_CATEGORIES cc, REPORT_CARD_COMMENT_CODE_SCALES cs
		WHERE c.SCHOOL_ID='".UserSchool()."'
		AND c.SYEAR='".UserSyear()."'
		AND c.COURSE_ID IS NOT NULL
		AND c.COURSE_ID!='0'
		AND cc.SYEAR=c.SYEAR
		AND cc.SCHOOL_ID=c.SCHOOL_ID
		AND cc.COURSE_ID=c.COURSE_ID
		AND cc.ID=c.CATEGORY_ID
		AND cs.SCHOOL_ID=c.SCHOOL_ID
		AND cs.ID=c.SCALE_ID"),array(),array('ID'));

		$commentsB_RET = DBGet(DBQuery("SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NULL"),array(),array('ID'));
	}

	if($_REQUEST['elements']['mp_tardies']=='Y' || $_REQUEST['elements']['ytd_tardies']=='Y')
	{
		// GET THE ATTENDANCE
		unset($extra);
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";
		$extra['SELECT_ONLY'] = "ap.SCHOOL_DATE,ap.COURSE_PERIOD_ID,ac.ID AS ATTENDANCE_CODE,ap.MARKING_PERIOD_ID,ssm.STUDENT_ID";
		$extra['FROM'] = ",ATTENDANCE_CODES ac,ATTENDANCE_PERIOD ap";
		$extra['WHERE'] .= " AND ac.ID=ap.ATTENDANCE_CODE AND (ac.DEFAULT_CODE!='Y' OR ac.DEFAULT_CODE IS NULL) AND ac.SYEAR=ssm.SYEAR AND ap.STUDENT_ID=ssm.STUDENT_ID";
		$extra['group'] = array('STUDENT_ID','ATTENDANCE_CODE','MARKING_PERIOD_ID');

		$attendance_RET = GetStuList($extra);
	}

	if($_REQUEST['elements']['mp_absences']=='Y' || $_REQUEST['elements']['ytd_absences']=='Y')
	{
		// GET THE DAILY ATTENDANCE
		unset($extra);
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";
		$extra['SELECT_ONLY'] = "ad.SCHOOL_DATE,ad.MARKING_PERIOD_ID,ad.STATE_VALUE,ssm.STUDENT_ID";
		$extra['FROM'] = ",ATTENDANCE_DAY ad";
		$extra['WHERE'] .= " AND ad.STUDENT_ID=ssm.STUDENT_ID AND ad.SYEAR=ssm.SYEAR AND (ad.STATE_VALUE='0.0' OR ad.STATE_VALUE='.5') AND ad.SCHOOL_DATE<='".GetMP($last_mp,'END_DATE')."'";
		$extra['group'] = array('STUDENT_ID','MARKING_PERIOD_ID');

		$attendance_day_RET = GetStuList($extra);
	}

	if($_REQUEST['mailing_labels']=='Y')
	{
		// GET THE ADDRESSES
		unset($extra);
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";
		$extra['SELECT'] = 's.STUDENT_ID';
		Widgets('mailing_labels');
		$extra['SELECT_ONLY'] = $extra['SELECT'];
		$extra['SELECT'] = '';
		$extra['group'] = array('STUDENT_ID','ADDRESS_ID');

		$addresses_RET = GetStuList($extra);
	}

	//modif Francois: limit code scales to the ones in current SYEAR in REPORT_CARD_COMMENTS
	//$comment_codes_RET = DBGet(DBQuery("SELECT cc.TITLE,cc.COMMENT,cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT FROM REPORT_CARD_COMMENT_CODES cc, REPORT_CARD_COMMENT_CODE_SCALES cs WHERE cc.SCHOOL_ID='".UserSchool()."' AND cs.ID=cc.SCALE_ID ORDER BY cs.SORT_ORDER,cs.ID,cc.SORT_ORDER,cc.ID"));
	$comment_codes_RET = DBGet(DBQuery("SELECT cs.ID AS SCALE_ID,cc.TITLE,cc.COMMENT,cs.TITLE AS SCALE_TITLE,cs.COMMENT AS SCALE_COMMENT
	FROM REPORT_CARD_COMMENT_CODES cc, REPORT_CARD_COMMENT_CODE_SCALES cs
	WHERE cc.SCHOOL_ID='".UserSchool()."'
	AND cs.ID=cc.SCALE_ID
	AND cc.SCALE_ID IN (SELECT DISTINCT c.SCALE_ID FROM REPORT_CARD_COMMENTS c WHERE c.SYEAR='".UserSyear()."' AND c.SCHOOL_ID=cc.SCHOOL_ID AND c.SCALE_ID IS NOT NULL)
	ORDER BY cs.SORT_ORDER,cs.ID,cc.SORT_ORDER,cc.ID"));

	if(count($RET))
	{
		$columns = array('COURSE_TITLE'=>_('Course'));

		if($_REQUEST['elements']['teacher']=='Y')
			$columns += array('TEACHER'=>_('Teacher'));

		if($_REQUEST['elements']['period_absences']=='Y')
			//$columns += array('ABSENCES'=>_('Abs<BR />YTD / MP'));
			$columns += array('ABSENCES'=>_('Absences'));

		if(count($_REQUEST['mp_arr'])>2)
			$mp_TITLE = 'SHORT_NAME';
		else
			$mp_TITLE = 'TITLE';

		foreach($_REQUEST['mp_arr'] as $mp)
			$columns[$mp] = GetMP($mp,$mp_TITLE);

		if($_REQUEST['elements']['comments']=='Y')
		{
			foreach($all_commentsA_RET as $comment)
				$columns['C'.$comment[1]['ID']] = $comment[1]['TITLE'];

			$columns['COMMENT'] = _('Comments');
		}

		$handle = PDFStart();
		//echo '<!-- MEDIA SIZE 8.5x11in -->';
		foreach($RET as $student_id=>$course_periods)
		{
			$comments_arr = array();
			$comments_arr_key = count($all_commentsA_RET)>0;
			unset($grades_RET);
			$i = 0;
			
			foreach($course_periods as $course_period_id=>$mps)
			{
				$i++;
				$grades_RET[$i]['COURSE_TITLE'] = $mps[key($mps)][1]['COURSE_TITLE'];
				$grades_RET[$i]['TEACHER'] = $mps[key($mps)][1]['TEACHER'];

				foreach($_REQUEST['mp_arr'] as $mp)
				{
					if($mps[$mp])
					{
						$grades_RET[$i][$mp] = '<B>'.$mps[$mp][1]['GRADE_TITLE'].'</B>';

						if($_REQUEST['elements']['percents']=='Y' && $mps[$mp][1]['GRADE_PERCENT']>0)
							$grades_RET[$i][$mp] .= '&nbsp;'.$mps[$mp][1]['GRADE_PERCENT'].'%';
						
						if($_REQUEST['elements']['comments']=='Y')
						{
							$sep = '; ';
							$sep_mp = ' | ';
							$grades_RET[$i]['COMMENT'] .= (empty($grades_RET[$i]['COMMENT'])?'':$sep_mp);
							$temp_grades_COMMENTS = $grades_RET[$i]['COMMENT'];
							//modif Francois: fix error Invalid argument supplied for foreach()
							//modif Francois: get the comments of all MPs
							//if (is_array($comments_RET[$student_id][$course_period_id][$last_mp]))
							if (isset($comments_RET[$student_id][$course_period_id][$mp]) && is_array($comments_RET[$student_id][$course_period_id][$mp]))
							{
								//foreach($comments_RET[$student_id][$course_period_id][$last_mp] as $comment)
								foreach($comments_RET[$student_id][$course_period_id][$mp] as $comment)
								{
									if($all_commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']])
										$grades_RET[$i]['C'.$comment['REPORT_CARD_COMMENT_ID']] .= $comment['COMMENT']!=' ' ? (empty($grades_RET[$i]['C'.$comment['REPORT_CARD_COMMENT_ID']])?'':$sep_mp).$comment['COMMENT'] : (empty($grades_RET[$i]['C'.$comment['REPORT_CARD_COMMENT_ID']])?'':$sep_mp).'&middot;';
									else
									{
										$sep_tmp = empty($grades_RET[$i]['COMMENT']) || mb_substr($grades_RET[$i]['COMMENT'],-3)==$sep_mp ? '' : $sep;

										if($commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']])
										{
											$color = $commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['COLOR'];

											if ($color)
												$color_html = '<span style="color:'.$color.'">';
											else
												$color_html = '';

											$grades_RET[$i]['COMMENT'] .= $sep_tmp.$color_html.$commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];
											$grades_RET[$i]['COMMENT'] .= '('.($comment['COMMENT']!=' '?$comment['COMMENT']:'&middot;').')'.($color_html ? '</span>':'');
											$comments_arr_key = true;
										}
										else
											$grades_RET[$i]['COMMENT'] .= $sep_tmp.$commentsB_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];

										$comments_arr[$grades_RET[$i]['COURSE_TITLE']][$comment['REPORT_CARD_COMMENT_ID']] = $comment['SORT_ORDER'];
									}
								}
							}

							if($mps[$mp][1]['COMMENT_TITLE'])
								$grades_RET[$i]['COMMENT'] .= (empty($grades_RET[$i]['COMMENT']) || mb_substr($grades_RET[$i]['COMMENT'],-3)==$sep_mp ? '' : $sep).$mps[$mp][1]['COMMENT_TITLE'];

							if ($grades_RET[$i]['COMMENT'] == $temp_grades_COMMENTS)
								$grades_RET[$i]['COMMENT'] .= (empty($grades_RET[$i]['COMMENT']) || mb_substr($grades_RET[$i]['COMMENT'],-3)==$sep_mp ? '' : $sep)._('None');
						}
						
						$last_mp = $mp;
					}
				}

				if($_REQUEST['elements']['period_absences']=='Y')
				{
					if($mps[$last_mp][1]['DOES_ATTENDANCE'])
						$grades_RET[$i]['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'].' / '.$mps[$last_mp][1]['MP_ABSENCES'];
					else
						$grades_RET[$i]['ABSENCES'] = _('N/A');
				}
			}
			asort($comments_arr,SORT_NUMERIC);

			if($_REQUEST['mailing_labels']=='Y')
			{
				if($addresses_RET[$student_id] && count($addresses_RET[$student_id]))
					$addresses = $addresses_RET[$student_id];
				else
					$addresses = array(0=>array(1=>array('STUDENT_ID'=>$student_id,'ADDRESS_ID'=>'0','MAILING_LABEL'=>'<BR /><BR />')));
			}
			else
				$addresses = array(0=>array());

			foreach($addresses as $address)
			{
				unset($_ROSARIO['DrawHeader']);

				if($_REQUEST['mailing_labels']=='Y')
					echo '<BR /><BR /><BR />';

				//modif Francois: add school logo
				$logo_pic =  'assets/school_logo_'.UserSchool().'.jpg';
				$picwidth = 120;
				if (file_exists($logo_pic))
					echo '<TABLE><TR><TD style="width:'.$picwidth.'px;"><img src="'.$logo_pic.'" width="'.$picwidth.'" /></TD><TD class="width-100p">';

				DrawHeader(_('Report Card'));
				DrawHeader($mps[key($mps)][1]['FULL_NAME'],$mps[key($mps)][1]['STUDENT_ID']);
				DrawHeader($mps[key($mps)][1]['GRADE_ID'],SchoolInfo('TITLE'));
				//modif Francois: add school year
				DrawHeader(_('School Year').': '.FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS')));

				$count_lines = 4;
				if($_REQUEST['elements']['mp_absences']=='Y')
				{
					$count = 0;
					//modif Francois: fix error Invalid argument supplied for foreach()
					if (isset($attendance_day_RET[$student_id][$last_mp]) && is_array($attendance_day_RET[$student_id][$last_mp]))
					{
						foreach($attendance_day_RET[$student_id][$last_mp] as $abs)
							$count += 1-$abs['STATE_VALUE'];
					}
					$mp_absences = sprintf(_('Absences in %s'),GetMP($last_mp,'TITLE')).': '.$count;
				}

				if($_REQUEST['elements']['ytd_absences']=='Y')
				{
					$count = 0;
					//modif Francois: fix error Invalid argument supplied for foreach()
					if (isset($attendance_day_RET[$student_id]) && is_array($attendance_day_RET[$student_id]))
					{
						foreach($attendance_day_RET[$student_id] as $mp_abs)
							foreach($mp_abs as $abs)
								$count += 1-$abs['STATE_VALUE'];
					}

					DrawHeader(_('Absences this year').': '.$count,$mp_absences);
					$count_lines++;
				}
				elseif($_REQUEST['elements']['mp_absences']=='Y')
				{
					DrawHeader($mp_absences);
					$count_lines++;
				}

				if($_REQUEST['elements']['mp_tardies']=='Y')
				{
					$count = 0;

					if (is_array($attendance_RET[$student_id][$_REQUEST['mp_tardies_code']][$last_mp]))
						foreach($attendance_RET[$student_id][$_REQUEST['mp_tardies_code']][$last_mp] as $abs)
							$count++;

					$mp_tardies = sprintf(_('Tardy in %s'),GetMP($last_mp,'TITLE')).': '.$count;
				}
				if($_REQUEST['elements']['ytd_tardies']=='Y')
				{
					$count = 0;

					if (is_array($attendance_RET[$student_id][$_REQUEST['ytd_tardies_code']]))
						foreach($attendance_RET[$student_id][$_REQUEST['ytd_tardies_code']] as $mp_abs)
							foreach($mp_abs as $abs)
								$count++;

					DrawHeader(_('Tardy this year').': '.$count,$mp_tardies);
					$count_lines++;
				}
				elseif($_REQUEST['elements']['mp_tardies']=='Y')
				{
					DrawHeader($mp_tardies);
					$count_lines++;
				}

				//modif Francois: add school logo
				if (file_exists($logo_pic))
				{
					echo '</TD></TR></TABLE>';
					$count_lines++;
				}

				if($_REQUEST['mailing_labels']=='Y')
				{
					DrawHeader(ProperDate(DBDate()));
					$count_lines++;
					for($i=$count_lines;$i<=6;$i++)
						echo '<BR />';
					echo '<TABLE><TR><TD style="width:50px;"> &nbsp; </TD><TD style="width:300px;">'.$address[1]['MAILING_LABEL'].'</TD></TR></TABLE>';
				}
				echo '<BR />';

				ListOutput($grades_RET,$columns,'.','.',array(),array(),array('print'=>false));

				if($_REQUEST['elements']['comments']=='Y' && ($comments_arr_key || count($comments_arr)))
				{
					$gender = mb_substr($mps[key($mps)][1]['GENDER'],0,1);
					$personalizations = array('^n'=>($mps[key($mps)][1]['FIRST_NAME']),'^s'=>($gender=='M'?_('his'):($gender=='F'?_('her'):_('his/her'))) );

					//modif Francois: limit comment scales to the ones used in student's courses
					$course_periods_list = implode(array_keys($course_periods), ',');

					$student_comment_scales_RET = DBGet(DBQuery("SELECT cs.ID
					FROM REPORT_CARD_COMMENT_CODE_SCALES cs
					WHERE cs.ID IN
						(SELECT c.SCALE_ID
						FROM REPORT_CARD_COMMENTS c
						WHERE (c.COURSE_ID IN(SELECT COURSE_ID FROM SCHEDULE WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID IN(".$course_periods_list.")) OR c.COURSE_ID=0)
						AND c.SCHOOL_ID=cs.SCHOOL_ID
						AND c.SYEAR='".UserSyear()."')
					AND cs.SCHOOL_ID='".UserSchool()."'"), array(), array('ID'));
					$student_comment_scales = array_keys($student_comment_scales_RET);

					$comment_sc_display = false;

					$comment_sc_txt = _('Comment Scales').'<BR /><ul>';

					$i = 0;
					$scale_title = '';
					if($comments_arr_key)
						foreach($comment_codes_RET as $comment)
						{
							//modif Francois: limit comment scales to the ones used in student's courses
							if (in_array($comment['SCALE_ID'], $student_comment_scales))
							{
								if($i++%3==0 || $scale_title != $comment['SCALE_TITLE'])
								{
									if ($scale_title != $comment['SCALE_TITLE'])
									{
										if ($i>1)
											$comment_sc_txt .= '</TR></TABLE></li>';

										$comment_sc_txt .= '<li>'.$comment['SCALE_TITLE'].(!empty($comment['SCALE_COMMENT']) ? ', '.$comment['SCALE_COMMENT'] : '').'<BR /><TABLE class="width-100p"><TR>';
										$i = 4;
									}
									else
										$comment_sc_txt .= '</TR><TR>';
								}
								$comment_sc_txt .= '<TD>('.$comment['TITLE'].') '.$comment['COMMENT'].'</TD>';
								$comment_sc_display = true;
								$scale_title = $comment['SCALE_TITLE'];
							}
						}

					$comment_sc_txt .= '</TR></TABLE></li></ul>';

					$course_title = '';
					$i = $j = 0;

					$commentsA_display = $commentsB_display = false;

					$commentsB_displayed = array();
					$commentsB_txt = _('General Comments').'<BR /><TABLE class="width-100p"><TR>';

					$commentsA_txt = _('Course-specific Comments').'<BR /><ul>';

					foreach($comments_arr as $comment_course_title=>$comments)
						foreach ($comments as $comment=>$sort_order)
						{
							if($commentsA_RET[$comment])
							{
								if($i++%2==0 || $course_title != $comment_course_title)
								{
									if ($course_title != $comment_course_title)
									{
										if ($i>1)
											$commentsA_txt .= '</TR></TABLE></li>';

										$commentsA_txt .= '<li>'.$comment_course_title.'<BR /><TABLE class="width-100p"><TR>';
										$i = 3;
									}
									else
										$commentsA_txt .= '</TR><TR>';
								}

								$color = $commentsA_RET[$comment][1]['COLOR'];

								if ($color)
									$color_html = '<span style="color:'.$color.'">';
								else
									$color_html = '';

								$commentsA_txt .= '<TD style="width:50%;">'.$color_html.$commentsA_RET[$comment][1]['SORT_ORDER'].': '.str_replace(array_keys($personalizations),$personalizations,$commentsA_RET[$comment][1]['TITLE']).($color_html ? '</span>':'').' ('._('Comment Scale').': '.$commentsA_RET[$comment][1]['SCALE_TITLE'].')'.'</TD>';
								$commentsA_display = true;
								$course_title = $comment_course_title;
							}

							if($commentsB_RET[$comment] && !in_array($commentsB_RET[$comment][1]['SORT_ORDER'], $commentsB_displayed))
							{
								if($j++%2==0)
									$commentsB_txt .= '</TR><TR>';

								$commentsB_txt .= '<TD style="width:50%;">'.$commentsB_RET[$comment][1]['SORT_ORDER'].': '.str_replace(array_keys($personalizations),$personalizations,$commentsB_RET[$comment][1]['TITLE']).'</TD>';
								$commentsB_display = true;
								$commentsB_displayed[] = $commentsB_RET[$comment][1]['SORT_ORDER'];
							}
						}

					$commentsB_txt .= '</TR></TABLE>';

					$commentsA_txt .= '</TR></TABLE></li></ul>';

					echo '<b>'._('Explanation of Comment Codes').'</b>';

					if($comment_sc_display)
						echo DrawHeader($comment_sc_txt);

					if ($commentsA_display)
						echo DrawHeader($commentsA_txt);

					if ($commentsB_display)
						echo DrawHeader($commentsB_txt);

					echo '</TR></TABLE>';
				}
				echo '<div style="page-break-after: always;"></div>';
			}
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
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_ROSARIO_PDF=true" method="POST">';
//modif Francois: add translation
		$extra['header_right'] = '<INPUT type="submit" value="'._('Create Report Cards for Selected Students').'" />';

		//modif Francois: get the title istead of the attendance code short name
		$attendance_codes = DBGet(DBQuery("SELECT SHORT_NAME,ID,TITLE FROM ATTENDANCE_CODES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL) AND TABLE_NAME='0'"));

		$extra['extra_header_left'] = '<TABLE>';
		$extra['extra_header_left'] .= '<TR><TD colspan="2"><b>'._('Include on Report Card').':</b></TD></TR>';

		$extra['extra_header_left'] .= '<TR class="st"><TD></TD><TD><TABLE>';
		$extra['extra_header_left'] .= '<TR>';
//modif Francois: add <label> on checkbox
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[teacher]" value="Y" checked /> '._('Teacher').'</label></TD>';
		$extra['extra_header_left'] .= '<TD></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[comments]" value="Y" checked /> '._('Comments').'</label></TD>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[percents]" value="Y"> '._('Percents').'</label></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[ytd_absences]" value="Y" checked /> '._('Year-to-date Daily Absences').'</label></TD>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[mp_absences]" value="Y"'.(GetMP(UserMP(),'SORT_ORDER')!=1?' checked':'').' /> '._('Daily Absences this quarter').'</label></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[ytd_tardies]" value="Y" /> '._('Other Attendance Year-to-date').':</label> <SELECT name="ytd_tardies_code">';

		foreach($attendance_codes as $code)
			$extra['extra_header_left'] .= '<OPTION value='.$code['ID'].'>'.$code['TITLE'].'</OPTION>';

		$extra['extra_header_left'] .= '</SELECT></TD>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[mp_tardies]" value="Y"> '._('Other Attendance this quarter').':</label> <SELECT name="mp_tardies_code">';

		foreach($attendance_codes as $code)
			$extra['extra_header_left'] .= '<OPTION value='.$code['ID'].'>'.$code['TITLE'].'</OPTION>';

		$extra['extra_header_left'] .= '</SELECT></TD>';
		$extra['extra_header_left'] .= '</TR><TR>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="elements[period_absences]" value="Y"> '._('Period-by-period absences').'</label></TD>';
		$extra['extra_header_left'] .= '<TD></TD>';
		$extra['extra_header_left'] .= '</TR>';
		$extra['extra_header_left'] .= '</TABLE></TD></TR>';

		//modif Francois: get the title instead of the short marking period name
		$mps_RET = DBGet(DBQuery("SELECT PARENT_ID,MARKING_PERIOD_ID,SHORT_NAME,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"),array(),array('PARENT_ID'));
		$extra['extra_header_left'] .= '<TR class="st"><TD>'._('Marking Periods').':</TD><TD><TABLE><TR><TD><TABLE>';
		foreach($mps_RET as $sem=>$quarters)
		{
			$extra['extra_header_left'] .= '<TR class="st">';
			foreach($quarters as $qtr)
			{
				$pro = GetChildrenMP('PRO',$qtr['MARKING_PERIOD_ID']);
				if($pro)
				{
					$pros = explode(',',str_replace("'",'',$pro));
					foreach($pros as $pro)
						if(GetMP($pro,'DOES_GRADES')=='Y')
							$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="mp_arr[]" value="'.$pro.'" /> '.GetMP($pro,'TITLE').'</label></TD>';
				}
				$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="mp_arr[]" value="'.$qtr['MARKING_PERIOD_ID'].'" /> '.$qtr['TITLE'].'</label></TD>';
			}
			if(GetMP($sem,'DOES_GRADES')=='Y')
				$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="mp_arr[]" value="'.$sem.'" /> '.GetMP($sem,'TITLE').'</label></TD>';
			$extra['extra_header_left'] .= '</TR>';
		}
		$extra['extra_header_left'] .= '</TABLE></TD>';
		if($sem)
		{
			$fy = GetParentMP('FY',$sem);
			$extra['extra_header_left'] .= '<TD><TABLE><TR>';
			if(GetMP($fy,'DOES_GRADES')=='Y')
				$extra['extra_header_left'] .= '<TD><label><INPUT type="checkbox" name="mp_arr[]" value="'.$fy.'" /> '.GetMP($fy,'TITLE').'</label></TD>';
			$extra['extra_header_left'] .= '</TR></TABLE></TD>';
		}
		$extra['extra_header_left'] .= '</TD></TR></TABLE></TR>';
		Widgets('mailing_labels');
		$extra['extra_header_left'] .= $extra['search'];
		$extra['search'] = '';
		$extra['extra_header_left'] .= '</TABLE>';
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');" /><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;
	//$extra['force_search'] = true;

	Widgets('course');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center"><INPUT type="submit" value="'._('Create Report Cards for Selected Students').'" /></span>';
		echo '</FORM>';
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}

function _makeTeacher($teacher,$column)
{
	return mb_substr($teacher,mb_strrpos(str_replace(' - ',' ^ ',$teacher),'^')+2);
}
?>
