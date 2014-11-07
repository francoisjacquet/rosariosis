<?php

/**********************************************************************
 ExampleResource.php file
 Optional
 - Adds a program to the Resources modules.
***********************************************************************/


DrawHeader(ProgramTitle()); //display main header with Module icon and Program title


//get Resources from the database
$QI = DBQuery("SELECT TITLE FROM SCHOOLS WHERE SYEAR='".UserSyear()."' ORDER BY ID");
$schools_RET = DBGet($QI);
$QI = DBQuery("SELECT COUNT(STUDENT_ID) FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' GROUP BY SCHOOL_ID");
$students_RET = DBGet($QI);
var_dump($schools_RET);exit;

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
