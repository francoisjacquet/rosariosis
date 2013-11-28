<?php
/**
* @file $Id: MakeReferral.php 480 2007-04-27 06:05:14Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

if($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start'])
{
	while(!VerifyDate($start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start']))
		$_REQUEST['day_start']--;
}
else
	$start_date = '01-'.mb_strtoupper(date('M-y'));

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
{
	while(!VerifyDate($end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end']))
		$_REQUEST['day_end']--;
}
else
	$end_date = DBDate();

if($_REQUEST['month_values'] && $_POST['month_values'])
{
	foreach($_REQUEST['month_values'] as $column=>$value)
	{
		$_REQUEST['values'][$column] = $_REQUEST['day_values'][$column].'-'.$value.'-'.$_REQUEST['year_values'][$column];
		//modif Francois: bugfix SQL bug when incomplete or non-existent date
		//if($_REQUEST['values'][$column]=='--')
		if(mb_strlen($_REQUEST['values'][$column]) < 11)
			$_REQUEST['values'][$column] = '';
		else
		{
			while(!VerifyDate($_REQUEST['values'][$column]))
			{
				$_REQUEST['day_values'][$column]--;
				$_REQUEST['values'][$column] = $_REQUEST['day_values'][$column].'-'.$value.'-'.$_REQUEST['year_values'][$column];
			}
		}
	}
	$_POST['values'] = $_REQUEST['values'];
}

if($_REQUEST['values'] && $_POST['values'])
{
	$sql = "INSERT INTO DISCIPLINE_REFERRALS ";
	
	$fields = "ID,SYEAR,SCHOOL_ID,STUDENT_ID,";
	$values = db_seq_nextval('DISCIPLINE_REFERRALS_SEQ').",'".UserSyear()."','".UserSchool()."','".UserStudentID()."',";

	$go = 0;

	foreach($_REQUEST['values'] as $column=>$value)
	{
		if($value)
		{
			$fields .= $column.',';
			if(!is_array($value))
				$values .= "'".str_replace('&quot;','"',$value)."',";
			else
			{
				$values .= "'||";
				foreach($value as $val)
				{
					if($val)
						$values .= str_replace('&quot;','"',$val).'||';
				}
				$values .= "',";
			}
			$go = true;
		}
	}

	$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
	DBQuery($sql);
//modif Francois: css WPadmin
	$note = '<div class="updated"><IMG SRC="assets/check_button.png" class="alignImg" /> '._('That discipline incident has been referred to an administrator.').'</div>';
	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_REQUEST['student_id']);
	unset($_SESSION['student_id']);
}

DrawHeader(ProgramTitle());
if(mb_strlen($note)>0)
	echo $note;

//if(!$_REQUEST['student_id'])
	$extra['new'] = true;


if($_REQUEST['student_id'])
	echo '<BR />';
//Widgets('all');
Search('student_id',$extra);

if(UserStudentID() && $_REQUEST['student_id'])
{
	//modif Francois: teachers need AllowEdit (to edit the input fields)
	$_ROSARIO['allow_edit'] = true;
	
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
	echo '<BR />';
	PopTable('header',ProgramTitle());
	$categories_RET = DBGet(DBQuery("SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER"));
	
	echo '<TABLE class="width-100p">';
	echo '<TR class="st"><TD><span style="color:gray">'._('Student').'</span></TD><TD>';
	$name = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'"));
	echo $name[1]['FIRST_NAME'].'&nbsp;'.($name[1]['MIDDLE_NAME']?$name[1]['MIDDLE_NAME'].' ':'').$name[1]['LAST_NAME'].'&nbsp;'.$name[1]['NAME_SUFFIX'];
	echo '</TD></TR>';

	echo '<TR class="st"><TD><span style="color:gray">'._('Reporter').'</span></TD><TD>';
	$users_RET = DBGet(DBQuery("SELECT STAFF_ID,FIRST_NAME,LAST_NAME,MIDDLE_NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND SCHOOLS LIKE '%,".UserSchool().",%' AND PROFILE IN ('admin','teacher') ORDER BY LAST_NAME,FIRST_NAME,MIDDLE_NAME"));
	echo '<SELECT name="values[STAFF_ID]">';
	foreach($users_RET as $user)
		echo '<OPTION value="'.$user['STAFF_ID'].'"'.(User('STAFF_ID')==$user['STAFF_ID']?' SELECTED="SELECTED"':'').'>'.$user['LAST_NAME'].', '.$user['FIRST_NAME'].' '.$user['MIDDLE_NAME'].'</OPTION>';
	echo '</SELECT>';
	echo '</TD></TR>';
	echo '<TR class="st"><TD><span style="color:gray">'._('Incident Date').'</span></TD><TD>';
	echo PrepareDate(DBDate(),'_values[ENTRY_DATE]');
	echo '</TD></TR>';
	foreach($categories_RET as $category)
	{
		echo '<TR class="st"><TD><span style="color:gray">'.$category['TITLE'].'</span></TD><TD>';
		switch($category['DATA_TYPE'])
		{
			case 'text':
				echo TextInput('','values[CATEGORY_'.$category['ID'].']','','maxlength=255');
				//echo '<INPUT type="TEXT" name="values[CATEGORY_'.$category['ID'].']" maxlength="255" />';
			break;
	
			case 'numeric':
				echo TextInput('','values[CATEGORY_'.$category['ID'].']','','size=4 maxlength=10');
				//echo '<INPUT type="TEXT" name="values[CATEGORY_'.$category['ID'].']" size="4" maxlength="10" />';
			break;
	
			case 'textarea':
				echo TextAreaInput('','values[CATEGORY_'.$category['ID'].']','','rows=4 cols=30');
				//echo '<TEXTAREA name="values[CATEGORY_'.$category['ID'].']" rows="4" cols="30"></TEXTAREA>';
			break;
	
			case 'checkbox':
				echo CheckboxInput('','values[CATEGORY_'.$category['ID'].']','','',true);
				//echo '<INPUT type="CHECKBOX" name="values[CATEGORY_'.$category['ID'].']" value="Y" />';
			break;
			
			case 'date':
				echo DateInput(DBDate(),'_values[CATEGORY_'.$category['ID'].']');
				//echo PrepareDate(DBDate(),'_values[CATEGORY_'.$category['ID'].']');
			break;
			
			case 'multiple_checkbox':
				$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
				$options = explode("\r",$category['SELECT_OPTIONS']);
				
				echo '<TABLE class="cellpadding-3"><TR class="st">';
				$i = 0;
				foreach($options as $option)
				{
					$i++;
					if($i%3==0)
						echo '</TR><TR class="st">';
					echo '<TD><label><INPUT type="checkbox" name="values[CATEGORY_'.$category['ID'].'][]" value="'.str_replace('"','&quot;',$option).'" />&nbsp;'.$option.'</label></TD>';
				}
				echo '</TR></TABLE>';
			break;
			
			case 'multiple_radio':
				$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
				$options = explode("\r",$category['SELECT_OPTIONS']);
				
				echo '<TABLE class="cellpadding-3"><TR class="st">';
				$i = 0;
				foreach($options as $option)
				{
					$i++;
					if($i%3==0)
						echo '</TR><TR class="st">';
					echo '<TD><label><INPUT type="radio" name="values[CATEGORY_'.$category['ID'].']" value="'.str_replace('"','&quot;',$option).'">&nbsp;'.$option.'</label></TD>';
				}
				echo '</TR></TABLE>';
			break;

			case 'select':
				$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
				$options = explode("\r",$category['SELECT_OPTIONS']);
				
				echo SelectInput('','values[CATEGORY_'.$category['ID'].']','',$options,'N/A');
				/*echo '<SELECT name="values[CATEGORY_'.$category['ID'].']"><OPTION value="">'._('N/A').'</OPTION>';
				foreach($options as $option)
				{
					echo '<OPTION value="'.str_replace('"','&quot;',$option).'">'.$option.'</OPTION>';
				}
				echo '</SELECT>';*/
			break;
		}
		echo '</TD></TR>';
	}
	echo '</TABLE>';
	PopTable('footer');
	echo '<span class="center"><INPUT type="submit" value="'._('Submit').'" /></span>';
	echo '</FORM>';
}
?>