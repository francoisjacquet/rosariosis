<?php
/**
* @file $Id: ReferralLog.php 405 2007-01-22 21:10:19Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

$categories_RET = DBGet(DBQuery("SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER 
FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u 
WHERE f.DATA_TYPE!='multiple_checkbox' 
AND u.DISCIPLINE_FIELD_ID=f.ID 
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

//modif Francois: no templates in Rosario

//Widgets('all');
$extra['new'] = true;
$extra['action'] .= '&_ROSARIO_PDF=true';

if(!$_REQUEST['search_modfunc'])
{
	DrawHeader(ProgramTitle());
	
	Search('student_id',$extra);
}
else
{
	if($_REQUEST['month_discipline_entry_begin'] && $_REQUEST['day_discipline_entry_begin'] && $_REQUEST['year_discipline_entry_begin'])
	{
		$start_date = $_REQUEST['day_discipline_entry_begin'].'-'.$_REQUEST['month_discipline_entry_begin'].'-'.$_REQUEST['year_discipline_entry_begin'];
		if(!VerifyDate($start_date))
			unset($start_date);
		$end_date = $_REQUEST['day_discipline_entry_end'].'-'.$_REQUEST['month_discipline_entry_end'].'-'.$_REQUEST['year_discipline_entry_end'];
		if(!VerifyDate($end_date))
			unset($end_date);
	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
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
	if(mb_strpos($extra['FROM'],'DISCIPLINE_REFERRALS dr')!==false)
		$extra['WHERE'] .= ' AND r.ID=dr.ID';
	
	$extra['group'] = array('STUDENT_ID');
	$extra['ORDER'] = ',r.ENTRY_DATE';
	$extra['WHERE'] .= appendSQL('',$extra);
	
	$RET = GetStuList($extra);

	if(count($RET))
	{
		$handle = PDFStart();
		foreach($RET as $student_id=>$referrals)
		{
			unset($_ROSARIO['DrawHeader']);
			DrawHeader(_('Discipline Log'));

			DrawHeader($referrals[1]['FULL_NAME'],$referrals[1]['STUDENT_ID']);
			DrawHeader(SchoolInfo('TITLE'),$courses[1]['GRADE_ID']);
			if($start_date && $end_date)
				DrawHeader(ProperDate($start_date).' - '.ProperDate($end_date));
			else
//modif Francois: school year over one/two calendar years format
				//DrawHeader(_('School Year').': '.UserSyear().'-'.(UserSyear()+1));
				DrawHeader(_('School Year').': '.FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS')));
//modif Francois: css WPadmin
			echo '<BR />';

			foreach($referrals as $referral)
			{
				if($_REQUEST['elements']['ENTRY_DATE'])
					DrawHeader('<b>'._('Date').': </b>'.ProperDate($referral['ENTRY_DATE']));

				if($_REQUEST['elements']['STAFF_ID'])
					DrawHeader('<b>'._('Reporter').': </b>'.GetTeacher($referral['STAFF_ID']));

				$end_tr = false;
				foreach($_REQUEST['elements'] as $column=>$Y)
				{
					if($column=='ENTRY_DATE' || $column=='STAFF_ID')
						continue;

					if($categories_RET[mb_substr($column,9)][1]['DATA_TYPE']=='textarea' && !$end_tr)
					{
						$end_tr = true;
						//echo '</TR></TABLE>';
					}
					elseif($categories_RET[mb_substr($column,9)][1]['DATA_TYPE']=='textarea')
						echo '<BR />';
					
					if($categories_RET[mb_substr($column,9)][1]['DATA_TYPE']!='textarea')
					{
						if($categories_RET[mb_substr($column,9)][1]['DATA_TYPE']=='checkbox')
							DrawHeader('<b>'.$categories_RET[mb_substr($column,9)][1]['TITLE'].': </b> '.($referral[$column] == 'Y' ? button('check', '', '', 'bigger') : button('x', '', '', 'bigger')));
						else
							DrawHeader('<b>'.$categories_RET[mb_substr($column,9)][1]['TITLE'].': </b> '.$referral[$column]);
					}
					else
						DrawHeader($referral[$column]);
				}
				echo '<HR>';
			}
			echo '<BR />';
			echo '<div style="page-break-after: always;"></div>';
		}
		PDFStop($handle);
	}
	else
		BackPrompt(_('No Students were found.'));
}
?>
