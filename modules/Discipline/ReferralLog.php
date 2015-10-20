<?php

$categories_RET = DBGet(DBQuery("SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER 
FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u 
WHERE u.DISCIPLINE_FIELD_ID=f.ID
ORDER BY ".db_case(array('DATA_TYPE',"'textarea'","'1'","'0'")).",SORT_ORDER"),array(),array('ID'));

$extra['second_col'] .= '<TR><TD><fieldset><legend>'._('Include in Discipline Log').'</legend><TABLE class="width-100p">';

$extra['second_col'] .= '<TR><TD><label><INPUT type="checkbox" name="elements[ENTRY_DATE]" value="Y" checked />&nbsp;'._('Entry Date').'</label></TD></TR>';

$extra['second_col'] .= '<TR><TD><label><INPUT type="checkbox" name="elements[STAFF_ID]" value="Y" checked />&nbsp;'._('Reporter').'</label></TD></TR>';

foreach($categories_RET as $id=>$category)
{
	$extra['second_col'] .= '<TR><TD><label><INPUT type="checkbox" name="elements[CATEGORY_'.$id.']" value="Y"'.($category[1]['DATA_TYPE']=='textarea'?' checked':'').' />&nbsp;'.$category[1]['TITLE'].'</label></TD></TR>';
	$i++;
}
$extra['second_col'] .= '</TABLE></fieldset></TD></TR>';

//FJ no templates in Rosario

//Widgets('all');
$extra['new'] = true;
$extra['action'] .= '&_ROSARIO_PDF=true';

if (!$_REQUEST['search_modfunc'])
{
	DrawHeader(ProgramTitle());
	
	Search('student_id',$extra);
}
else
{
	if ($_REQUEST['month_discipline_entry_begin'] && $_REQUEST['day_discipline_entry_begin'] && $_REQUEST['year_discipline_entry_begin'])
	{
		$start_date = $_REQUEST['day_discipline_entry_begin'].'-'.$_REQUEST['month_discipline_entry_begin'].'-'.$_REQUEST['year_discipline_entry_begin'];
		if (!VerifyDate($start_date))
			unset($start_date);
		$end_date = $_REQUEST['day_discipline_entry_end'].'-'.$_REQUEST['month_discipline_entry_end'].'-'.$_REQUEST['year_discipline_entry_end'];
		if (!VerifyDate($end_date))
			unset($end_date);
	}

	if (!isset($_REQUEST['_ROSARIO_PDF']))
	{
		DrawHeader(ProgramTitle());
		echo '<BR /><BR />';
	}
	
	foreach($_REQUEST['elements'] as $column=>$Y)
	{
		$extra['SELECT'] .= ',r.'.$column;
	}

	$extra['FROM'] .= ',DISCIPLINE_REFERRALS r ';
	$extra['WHERE'] .= " AND r.STUDENT_ID=ssm.STUDENT_ID AND r.SYEAR=ssm.SYEAR ";
	if (mb_strpos($extra['FROM'],'DISCIPLINE_REFERRALS dr')!==false)
		$extra['WHERE'] .= ' AND r.ID=dr.ID';
	
	$extra['group'] = array('STUDENT_ID');
	$extra['ORDER'] = ',r.ENTRY_DATE';
	$extra['WHERE'] .= appendSQL('',$extra);
	
	$RET = GetStuList($extra);

	if (count($RET))
	{
		$handle = PDFStart();
		foreach($RET as $student_id=>$referrals)
		{
			unset($_ROSARIO['DrawHeader']);
			DrawHeader(_('Discipline Log'));

			DrawHeader($referrals[1]['FULL_NAME'],$referrals[1]['STUDENT_ID']);
			DrawHeader(SchoolInfo('TITLE'),$courses[1]['GRADE_ID']);
			if ($start_date && $end_date)
				DrawHeader(ProperDate($start_date).' - '.ProperDate($end_date));
			else
//FJ school year over one/two calendar years format
				//DrawHeader(_('School Year').': '.UserSyear().'-'.(UserSyear()+1));
				DrawHeader(_('School Year').': '.FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS')));

			echo '<BR />';

			foreach($referrals as $referral)
			{
				if ($_REQUEST['elements']['ENTRY_DATE'])
					DrawHeader('<b>'._('Date').': </b>'.ProperDate($referral['ENTRY_DATE']));

				if ($_REQUEST['elements']['STAFF_ID'])
					DrawHeader('<b>'._('Reporter').': </b>'.GetTeacher($referral['STAFF_ID']));

				foreach($_REQUEST['elements'] as $column=>$Y)
				{
					if ($column=='ENTRY_DATE' || $column=='STAFF_ID')
						continue;

					if ($categories_RET[mb_substr($column,9)][1]['DATA_TYPE']!='textarea')
					{
						$title_txt = '<b>'.$categories_RET[mb_substr($column,9)][1]['TITLE'].': </b> ';

						if ($categories_RET[mb_substr($column,9)][1]['DATA_TYPE']=='checkbox')
							DrawHeader($title_txt.($referral[$column] == 'Y' ? button('check', '', '', 'bigger') : button('x', '', '', 'bigger')));
						elseif ($categories_RET[mb_substr($column,9)][1]['DATA_TYPE']=='multiple_checkbox')
							DrawHeader($title_txt.str_replace('||',', ',mb_substr($referral[$column],2,-2)));
						else
							DrawHeader($title_txt.$referral[$column]);
					}
					else
						DrawHeader(nl2br($referral[$column]));
				}

				echo '<BR />';
			}

			echo '<div style="page-break-after: always;"></div>';
		}

		PDFStop($handle);
	}
	else
		BackPrompt(_('No Students were found.'));
}
