<?php
/**
* @file $Id: ReferralForm.php 161 2006-09-07 06:21:17Z doritojones $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

DrawHeader(ProgramTitle());

if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{
		if($id!='new')
		{
			$sql = "UPDATE DISCIPLINE_CATEGORIES SET ";

			foreach($columns as $column=>$value)
				$sql .= $column."='".$value."',";
			$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
			$go = true;
		}
		else
		{
			$id = DBGet(DBQuery("SELECT ".db_seq_nextval('DISCIPLINE_CATEGORIES_SEQ').' AS ID'.FROM_DUAL));
			$id = $id[1]['ID'];
			$sql = "INSERT INTO DISCIPLINE_CATEGORIES ";
			
			$fields = "ID,SYEAR,SCHOOL_ID,";
			$values = "'".$id."','".UserSyear()."','".UserSchool()."',";


			$go = 0;
			if($columns['TITLE'])
			{
				foreach($columns as $column=>$value)
				{
					if($value)
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						$go = true;
					}
				}
			
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
	
				switch($columns['TYPE'])
				{
					case 'checkbox':
						DBQuery("ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_$id VARCHAR(1)");
					break;
					
					case 'text':
					case 'multiple_radio':
					case 'multiple_checkbox':
					case 'select':
						DBQuery("ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_$id VARCHAR(1000)");
					break;
					
					case 'numeric':
						DBQuery("ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_$id NUMERIC(10,2)");
					break;
					
					case 'date':
						DBQuery("ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_$id DATE");
					break;
					
					case 'textarea':
						DBQuery("ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_$id VARCHAR(5000)");
					break;
				}
				DBQuery("CREATE INDEX DISCIPLINE_REFERRALS_IND$id ON DISCIPLINE_REFERRALS (CATEGORY_$id)");
			}
		}

		if($go)
			DBQuery($sql);
	}
	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if(DeletePrompt(_('Category')))
	{
		$id = $_REQUEST['id'];
		DBQuery("DELETE FROM DISCIPLINE_CATEGORIES WHERE ID='$id'");
		DBQuery("ALTER TABLE DISCIPLINE_REFERRALS DROP COLUMN CATEGORY_$id");
		unset($_REQUEST['modfunc']);
		unset($_REQUEST['id']);
	}
}

if(empty($_REQUEST['modfunc']))

{
	$sql = "SELECT ID,TITLE,SORT_ORDER,TYPE,OPTIONS FROM DISCIPLINE_CATEGORIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER";
	$QI = DBQuery($sql);
	$referrals_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','SORT_ORDER'=>'_makeTextInput','TYPE'=>'_makeType','OPTIONS'=>'_makeTextAreaInput'));
	
	$columns = array('TITLE'=>_('Title'),'SORT_ORDER'=>_('Sort Order'),'TYPE'=>_('Data Type'),'OPTIONS'=>_('Options'));
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'SORT_ORDER'=>_makeTextInput('','SORT_ORDER'),'OPTIONS'=>_makeTextAreaInput('','OPTIONS'),'TYPE'=>_makeType('','TYPE'));
	if (AllowEdit())
	{
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=delete";
		$link['remove']['variables'] = array('id'=>'ID');
	}
	
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
	ListOutput($referrals_RET,$columns,'Referral Form Category','Referral Form Categories',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function _makeType($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	$new_options = array('checkbox'=>_('Checkbox'),'text'=>_('Text'),'multiple_checkbox'=>_('Select Multiple from Options'),'multiple_radio'=>_('Select One from Options'),'select'=>_('Pull-Down'),'date'=>_('Date'),'numeric'=>_('Number'),'textarea'=>_('Long Text'));
	$options = array('text'=>_('Text'),'multiple_checkbox'=>_('Select Multiple from Options'),'multiple_radio'=>_('Select One from Options'),'select'=>_('Pull-Down'));
	
	if($value=='date' || $value=='numeric' || $value=='checkbox' || $value=='textarea')
		return $new_options[$value];
	else
		return SelectInput($value,'values['.$id.']['.$name.']','',(($id=='new')?$new_options:$options),false);	
}

function _makeTextInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	if($name!='TITLE')
		$extra = 'size=5 maxlength=2';
	if($name=='SORT_ORDER')
		$comment = '<!-- '.$value.' -->';

	return $comment.TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function _makeTextAreaInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($id=='new' || $THIS_RET['TYPE']=='multiple_checkbox' || $THIS_RET['TYPE']=='multiple_radio' || $THIS_RET['TYPE']=='select')
		return TextAreaInput(str_replace('"','\"',$value),'values['.$id.']['.$name.']');
	else
		return 'N/A';
}
?>