<?php

/**********************************************************************
 Resources.php file
 Optional
 - Override the Resources.php program present in the Resources modules
 The program is the same but the source contains comments.
***********************************************************************/

if($_REQUEST['modfunc']=='update' && $_REQUEST['values'] && $_POST['values'] && AllowEdit()) //AllowEdit must be verified before inserting, updating, deleting data
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{
		if($id!='new') //update Resource
		{
			$sql = "UPDATE RESOURCES SET ";
							
			foreach($columns as $column=>$value)
			{
				$sql .= $column."='".$value."',";
			}
			$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
			DBQuery($sql);
		}
		else //new Resource, insert
		{
			$sql = "INSERT INTO RESOURCES ";

			$fields = 'ID,SCHOOL_ID,';
			$values = db_seq_nextval('RESOURCES_SEQ').",'".UserSchool()."',";

			$go = 0;
			foreach($columns as $column=>$value)
			{
				if(!empty($value) || $value=='0')
				{
					$fields .= $column.',';
					$values .= "'".$value."',";
					$go = true;
				}
			}
			$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
			
			if($go) //if Resource has values check
				DBQuery($sql);
		}
	}
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

DrawHeader(ProgramTitle()); //display main header with Module icon and Program title

if($_REQUEST['modfunc']=='remove' && AllowEdit()) //AllowEdit must be verified before inserting, updating, deleting data
{
	if(DeletePrompt(_('Resource'))) //confirm deletion
	{
		DBQuery("DELETE FROM RESOURCES WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

if(empty($_REQUEST['modfunc'])) //display Resources
{
	//get Resources from the database
	$sql = "SELECT ID,TITLE,LINK FROM RESOURCES WHERE SCHOOL_ID='".UserSchool()."' ORDER BY ID";
	$QI = DBQuery($sql);
	$resources_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','LINK'=>'_makeLink')); //call _makeTextInput and _makeLink local functions to format the results

	//prepare ListOutput table options
	//see ListOutput.fnc.php for the complete list of options
	$columns = array('TITLE'=>_('Title'),'LINK'=>_('Link'));
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'LINK'=>_makeLink('','LINK'));
	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove';
	$link['remove']['variables'] = array('id'=>'ID');
	
	//form used to send the new / updated Resources to be processed by the same script (see at the top)
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" method="POST">';
	
	//display secondary header with text (aligned left) and Save button (aligned right)
	DrawHeader('This is the Resources program from the Example module. The program is the same but the source contains comments.',SubmitButton(_('Save'))); //SubmitButton is diplayed only if AllowEdit

	ListOutput($resources_RET,$columns,'Resource','Resources',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>'; //SubmitButton is diplayed only if AllowEdit
	echo '</FORM>';
}

//local function called by DBGet
//begin function name with an underscore "_" when it is local
function _makeTextInput($value,$name)
{	global $THIS_RET; //current returned data row
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	if($name=='LINK')
		$extra = 'maxlength=1000';
		
	if($name=='TITLE')
		$extra = 'maxlength=256';

	return TextInput($value,'values['.$id.']['.$name.']','',$extra); //call TextInput function from functions/Inputs.php file
}

//local function called by DBGet
//begin function name with an underscore "_" when it is local
function _makeLink($value,$name)
{	global $THIS_RET; //current returned data row

	if (AllowEdit()) //if AllowEdit, display TextInput
	{
		if($value)
			return '<div style="display:table-cell;"><a href="'.$value.'" target="_blank">'._('Link').'</a>&nbsp;</div><div style="display:table-cell;">'._makeTextInput($value,$name).'</div>';
		else
			return _makeTextInput($value,$name);
	}
	
	//truncate links > 100 chars
	$truncated_link = $value;
	if (mb_strlen($truncated_link) > 100)
	{
		$separator = '/.../';
		$separatorlength = mb_strlen($separator) ;
		$maxlength = 100 - $separatorlength;
		$start = $maxlength / 2 ;
		$trunc =  mb_strlen($truncated_link) - $maxlength;
		$truncated_link = substr_replace($truncated_link, $separator, $start, $trunc);
	}
	 
	return '<a href="'.$value.'" target="_blank">'.$truncated_link.'</a>';
}
?>
