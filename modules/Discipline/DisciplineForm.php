<?php
/**
* @file $Id: DisciplineForm.php 218 2006-10-03 05:54:09Z focus-sis $
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
//modif Francois: fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			if($id!='new')
			{
				$sql = "UPDATE DISCIPLINE_FIELD_USAGE SET ";

				foreach($columns as $column=>$value)
					$sql .= $column."='".$value."',";
				$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
				$go = true;
			}
			else
			{
				$id = DBGet(DBQuery("SELECT ".db_seq_nextval('DISCIPLINE_FIELDS_SEQ').' AS ID'.FROM_DUAL));
				$id = $id[1]['ID'];
				$sql = "INSERT INTO DISCIPLINE_FIELDS ";
				
				$fields = "ID,COLUMN_NAME,";
				$values = "'".$id."','CATEGORY_".$id."',";


				$go = 0;
				if($columns['TITLE'])
				{
					foreach($columns as $column=>$value)
					{
						if($value && $column!='SORT_ORDER' && $column!='SELECT_OPTIONS')
						{
							$fields .= $column.',';
							$values .= "'".$value."',";
							$go = true;
						}
					}
				
					$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

					$usage_sql = "INSERT INTO DISCIPLINE_FIELD_USAGE ";
					
					$fields = "ID,DISCIPLINE_FIELD_ID,SYEAR,SCHOOL_ID,";
					$values = db_seq_nextval('DISCIPLINE_FIELD_USAGE_SEQ').",'".$id."','".UserSyear()."','".UserSchool()."',";
		
					foreach($columns as $column=>$value)
					{
						if($value && $column!='DATA_TYPE')
						{
							$fields .= $column.',';
							$values .= "'".$value."',";
						}
					}
				
					$usage_sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

		
					switch($columns['DATA_TYPE'])
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
					DBQuery($usage_sql);
				}
			}

			if($go)
				DBQuery($sql);
		}
		else
			$error = ErrorMessage(array(_('Please enter a valid Sort Order.')));
	}
	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if(DeletePrompt(_('Category')))
	{
		$id = $_REQUEST['id'];
		DBQuery("DELETE FROM DISCIPLINE_FIELDS WHERE ID='$id'");
		DBQuery("DELETE FROM DISCIPLINE_FIELD_USAGE WHERE DISCIPLINE_FIELD_ID='$id'");
		DBQuery("ALTER TABLE DISCIPLINE_REFERRALS DROP COLUMN CATEGORY_$id");
		unset($_REQUEST['modfunc']);
		unset($_REQUEST['id']);
	}
}

if($_REQUEST['modfunc']=='delete_usage' && AllowEdit())
{
	if(DeletePrompt(_('Category'),_('Don\'t use')))
	{
		$id = $_REQUEST['id'];
		DBQuery("DELETE FROM DISCIPLINE_FIELD_USAGE WHERE ID='$id'");
		unset($_REQUEST['modfunc']);
		unset($_REQUEST['id']);
	}
}

if($_REQUEST['modfunc']=='add_usage' && AllowEdit())
{
	DBQuery("INSERT INTO DISCIPLINE_FIELD_USAGE (ID,DISCIPLINE_FIELD_ID,SYEAR,SCHOOL_ID,TITLE,SELECT_OPTIONS,SORT_ORDER) SELECT ".db_seq_nextval('DISCIPLINE_FIELD_USAGE_SEQ')." AS ID,'".$_REQUEST['id']."' AS DISCIPLINE_FIELD_ID,'".UserSyear()."' AS SYEAR,'".UserSchool()."' AS SCHOOL_ID,TITLE,NULL AS SELECT_OPTIONS,NULL AS SORT_ORDER FROM DISCIPLINE_FIELDS WHERE ID='".$_REQUEST['id']."'");
	unset($_REQUEST['modfunc']);
	unset($_REQUEST['id']);
}


if(empty($_REQUEST['modfunc']))

{
	$sql = "SELECT NULL AS REMOVE,du.ID AS USAGE_ID,df.ID,COALESCE(du.TITLE,df.TITLE) AS TITLE,du.SORT_ORDER,df.DATA_TYPE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df LEFT OUTER JOIN DISCIPLINE_FIELD_USAGE du ON (du.DISCIPLINE_FIELD_ID=df.ID AND du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."') ORDER BY du.ID,du.SORT_ORDER";
	$QI = DBQuery($sql);
	$referrals_RET = DBGet($QI,array('REMOVE'=>'_makeRemove','TITLE'=>'_makeTextInput','SORT_ORDER'=>'_makeTextInput','DATA_TYPE'=>'_makeType','SELECT_OPTIONS'=>'_makeTextAreaInput'));
	
	foreach($referrals_RET as $key=>$item)
	{
		if(!$item['USAGE_ID'])
			$referrals_RET[$key]['row_color']='CCCCCC';
	}

	if(count($referrals_RET))
		$columns = array('REMOVE'=>'');
	else
		$columns = array();

	$columns += array('TITLE'=>_('Title'),'SORT_ORDER'=>_('Sort Order'),'DATA_TYPE'=>_('Data Type'),'SELECT_OPTIONS'=>_('Pull-Down').'/'._('Select Multiple from Options').'/'._('Select One from Options'));
	$link['add']['html'] = array('REMOVE'=>button('add'),'TITLE'=>_makeTextInput('','TITLE'),'SORT_ORDER'=>_makeTextInput('','SORT_ORDER'),'SELECT_OPTIONS'=>_makeTextAreaInput('','SELECT_OPTIONS'),'DATA_TYPE'=>_makeType('','DATA_TYPE'));
	
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;
	ListOutput($referrals_RET,$columns,'Referral Form Category','Referral Form Categories',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function _makeType($value,$name)
{	global $THIS_RET;

	if($THIS_RET['USAGE_ID'])
		$id = $THIS_RET['USAGE_ID'];
	else
		$id = 'new';

	$new_options = array('checkbox'=>_('Checkbox'),'text'=>_('Text'),'multiple_checkbox'=>_('Select Multiple from Options'),'multiple_radio'=>_('Select One from Options'),'select'=>_('Pull-Down'),'date'=>_('Date'),'numeric'=>_('Number'),'textarea'=>_('Long Text'));
	
	if($THIS_RET['ID'])
		return $new_options[$value];
	else
		return SelectInput($value,'values[new]['.$name.']','',$new_options,false);
}

function _makeTextInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['USAGE_ID'])
		$id = $THIS_RET['USAGE_ID'];
	elseif($THIS_RET['ID'])
		$id = 'usage';
	else
		$id = 'new';
	
	if($name!='TITLE')
		$extra = 'size=5 maxlength=2';
	if($name=='SORT_ORDER')
		$comment = '<!-- '.$value.' -->';

	if($id=='usage')
		return $value;		
	else
		return $comment.TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function _makeTextAreaInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['USAGE_ID'])
		$id = $THIS_RET['USAGE_ID'];
	elseif($THIS_RET['ID'])
		$id = 'usage';
	else
		$id = 'new';

	if($id=='usage')
		return $value;
	elseif($id=='new' || $THIS_RET['DATA_TYPE']=='multiple_checkbox' || $THIS_RET['DATA_TYPE']=='multiple_radio' || $THIS_RET['DATA_TYPE']=='select')
	{
		//modif Francois: responsive rt td too large
		$return .= includeOnceColorBox('divTextAreaContent'.$id);
		$return .= '<DIV id="divTextAreaContent'.$id.'" class="rt2colorBox">'.TextAreaInput(str_replace('"','\"',$value),'values['.$id.']['.$name.']','','',false).'</DIV>';
		return $return;
	}
	else
		return _('N/A');
}

function _makeRemove($value,$column)
{	global $THIS_RET;
	
	$return = '';
	if (AllowEdit())
		if($THIS_RET['USAGE_ID'])
		{
			$return = button('remove',_('Don\'t use'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete_usage&id='.$THIS_RET['USAGE_ID'].'"');
			$return .= '&nbsp;'.button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&id='.$THIS_RET['ID'].'"');
		}
		else
			$return = button('add',_('Use at this school'),'"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=add_usage&id='.$THIS_RET['ID'].'"');

	return $return;
}

?>