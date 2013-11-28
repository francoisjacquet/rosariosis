<?php
if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(!empty($_REQUEST['activity_id']))
	{
		if (count($_REQUEST['student']))
		{
	//modif Francois: fix bug add the same activity more than once
	//		$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENT_ELIGIBILITY_ACTIVITIES WHERE ACTIVITY_ID='".$_SESSION['activity_id']."' AND SYEAR='".UserSyear()."'"),array(),array('STUDENT_ID'));
			$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENT_ELIGIBILITY_ACTIVITIES WHERE ACTIVITY_ID='".$_REQUEST['activity_id']."' AND SYEAR='".UserSyear()."'"),array(),array('STUDENT_ID'));
			foreach($_REQUEST['student'] as $student_id=>$yes)
			{
				if(!$current_RET[$student_id])
				{
					$sql = "INSERT INTO STUDENT_ELIGIBILITY_ACTIVITIES (SYEAR,STUDENT_ID,ACTIVITY_ID)
								values('".UserSyear()."','".$student_id."','".$_REQUEST['activity_id']."')";
					DBQuery($sql);
				}
			}
			$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('This activity has been added to the selected students.');
		}
		else
			$error[] = _('You must choose at least one student.');
	}
	else
		$error[] = _('You must choose an activity.');
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['modfunc']);
}

DrawHeader(ProgramTitle());

if(isset($note))
	echo ErrorMessage($note, 'note');
if (isset($error))
	echo ErrorMessage($error);

if($_REQUEST['search_modfunc']=='list')
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
	DrawHeader('',SubmitButton(_('Add Activity to Selected Students')));
	echo '<BR />';

//modif Francois: css WPadmin
	echo '<TABLE class="postbox cellpadding-6" style="margin:0 auto;"><TR><TD style="text-align:right">'._('Activity').'</TD>';
	echo '<TD>';
	$activities_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ELIGIBILITY_ACTIVITIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	echo '<SELECT name="activity_id"><OPTION value="">'._('N/A').'</OPTION>';
	if(count($activities_RET))
	{
		foreach($activities_RET as $activity)
			echo '<OPTION value="'.$activity['ID'].'">'.$activity['TITLE'].'</OPTION>';
	}
	echo '</SELECT>';
	echo '</TD>';
	echo '</TR></TABLE><BR />';

}
//modif Francois: fix bug no Search when student already selected
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
	$extra['new'] = true;
	Widgets('activity');
	Widgets('course');

Search('student_id',$extra);
if($_REQUEST['search_modfunc']=='list')
	echo '<BR /><span class="center">'.SubmitButton(_('Add Activity to Selected Students')).'</span></FORM>';

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '&nbsp;&nbsp;<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y">';
}

?>