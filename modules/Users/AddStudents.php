<?php
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if (isset($_REQUEST['student']) && is_array($_REQUEST['student']) && AllowEdit())
	{
		$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENTS_JOIN_USERS WHERE STAFF_ID='".UserStaffID()."'"),array(),array('STUDENT_ID'));
		foreach($_REQUEST['student'] as $student_id=>$yes)
		{
			if (!$current_RET[$student_id])
			{
				$sql = "INSERT INTO STUDENTS_JOIN_USERS (STUDENT_ID,STAFF_ID) values('".$student_id."','".UserStaffID()."')";

				DBQuery($sql);

				//hook
				do_action('Users/AddStudents.php|user_assign_role');
			}
		}
		$note[] = _('The selected user\'s profile now includes access to the selected students.');
	}
	else
		$error[] = _('You must choose at least one student.');

	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

DrawHeader(ProgramTitle());

if ($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if (DeletePrompt(_('student from that user'),_('remove access to')) && !empty($_REQUEST['student_id_remove']))
	{
		DBQuery("DELETE FROM STUDENTS_JOIN_USERS WHERE STUDENT_ID='".$_REQUEST['student_id_remove']."' AND STAFF_ID='".UserStaffID()."'");

		//hook
		do_action('Users/AddStudents.php|user_unassign_role');

		unset($_REQUEST['modfunc']);
	}
}

if (isset($note))
	echo ErrorMessage($note,'note');

if (isset($error))
	echo ErrorMessage($error);

if ($_REQUEST['modfunc']!='delete')
{
	if (UserStaffID())
	{
		$profile = DBGet(DBQuery("SELECT PROFILE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
		if ($profile[1]['PROFILE']!='parent')
			unset($_SESSION['staff_id']);
	}

	//FJ add # Associated students
	$extra['SELECT'] = ",(SELECT count(u.STUDENT_ID)
	FROM STUDENTS_JOIN_USERS u,STUDENT_ENROLLMENT ssm
	WHERE u.STAFF_ID=s.STAFF_ID
	AND ssm.STUDENT_ID=u.STUDENT_ID
	AND ssm.SYEAR='".UserSyear()."'
	AND ('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)) AS ASSOCIATED";

	$extra['columns_after'] = array('ASSOCIATED'=>'# '._('Associated'));

	$extra['profile'] = 'parent';

	if (!UserStaffID())
		Search('staff_id',$extra);

	if (UserStaffID())
	{
		if ($_REQUEST['search_modfunc']=='list')
		{
			echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
			DrawHeader('',SubmitButton(_('Add Selected Students')));
		}

		echo '<TABLE class="center"><TR><TD>';

		$current_RET = DBGet(DBQuery("SELECT u.STUDENT_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME FROM STUDENTS_JOIN_USERS u,STUDENTS s WHERE s.STUDENT_ID=u.STUDENT_ID AND u.STAFF_ID='".UserStaffID()."'"));

		$link['remove'] = array('link'=>'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete','variables'=>array('student_id_remove'=>'STUDENT_ID'));

		ListOutput($current_RET,array('FULL_NAME'=>_('Students')),'Student','Students',$link,array(),array('search'=>false));

		echo '</TD></TR><TR><TD>';

		$extra['link'] = array('FULL_NAME'=>false);
		$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
		$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
		$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
		$extra['new'] = true;
		$extra['options']['search'] = false;

		if (AllowEdit())
			Search('student_id',$extra);

		echo '</TD></TR></TABLE>';

		if ($_REQUEST['search_modfunc']=='list')
			echo '<BR /><div class="center">' . SubmitButton(_('Add Selected Students')) . '</div></FORM>';
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y">';
}
