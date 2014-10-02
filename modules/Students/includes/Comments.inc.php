<?php

//modif Francois: add School Configuration
$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='students'"),array(),array('TITLE'));

//$_ROSARIO['allow_edit'] = true;
if($_REQUEST['modfunc']=='update' && AllowEdit())
{
	//modif Francois: add time and user to comments "comment thread" like
	$_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'] = date('Y-m-d G:i:s').'|'.User('STAFF_ID')."||".$_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'];
	
	$existing_RET = DBGet(DBQuery("SELECT STUDENT_ID, COMMENT FROM STUDENT_MP_COMMENTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."'"));
	
	if(!$existing_RET)
		DBQuery("INSERT INTO STUDENT_MP_COMMENTS (SYEAR,STUDENT_ID,MARKING_PERIOD_ID) values('".UserSyear()."','".UserStudentID()."','".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."')");
	else
		$_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'] = $existing_RET[1]['COMMENT']."||".$_REQUEST['values']['STUDENT_MP_COMMENTS'][UserStudentID()]['COMMENT'];
		
	SaveData(array('STUDENT_MP_COMMENTS'=>"STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."'"),'',array('COMMENT'=>_('Comment')));
	//unset($_SESSION['_REQUEST_vars']['modfunc']);
	//unset($_SESSION['_REQUEST_vars']['values']);
}
if(empty($_REQUEST['modfunc']))

{
	$comments_RET = DBGet(DBQuery("SELECT COMMENT FROM STUDENT_MP_COMMENTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."'"));
	
	echo '<TABLE style="max-width:500px;">';
	echo '<TR>';
	echo '<TD style="vertical-align:bottom;">';
	echo '<b>'.$mp['TITLE'].' '._('Comments').'</b><BR />';
//modif Francois: remove maxlength limitation as it is not technically needed
	echo '<TEXTAREA id="textarea" name="values[STUDENT_MP_COMMENTS]['.UserStudentID().'][COMMENT]" rows="10" cols="66" style="width:100%;"'.(AllowEdit()?'':' readonly').'>';	
	echo '</TEXTAREA>';
	echo '</TD>';
	echo '</TR>';
	//echo '<BR /><b>* '._('If more than one teacher will be adding comments for this student').':</b><BR />';
	//echo '<ul><li>'._('Type your name above the comments you enter.').'</li></ul>';
	//echo '<li>'._('Leave space for other teachers to enter their comments.').'</li></ul>';
	//modif Francois: add time and user to comments "comment thread" like
	echo '<TR>';
	echo '<TD style="vertical-align:bottom;">';
	if (!empty($comments_RET[1]['COMMENT']))
	{
		$comments = explode('||', $comments_RET[1]['COMMENT']);
		foreach($comments as $comment)
		{
			if(is_array(list($timestamp, $staff_id) = explode('|', $comment)) && is_numeric($staff_id))
			{
				if (User('STAFF_ID') == $staff_id)
					$staff_name = User('NAME');
				else
				{
					$staff_name_RET = DBGet(DBQuery("SELECT FIRST_NAME||' '||LAST_NAME AS NAME FROM STAFF WHERE SYEAR='".UserSyear()."' AND USERNAME=(SELECT USERNAME FROM STAFF WHERE SYEAR='".Config('SYEAR')."' AND STAFF_ID='".$staff_id."')"));
					$staff_name = $staff_name_RET[1]['NAME'];
				}
				echo '<em>'.ProperDate(mb_substr($timestamp,0,10)).mb_substr($timestamp,10).', '.$staff_name.':</em>';
			}
			else
				echo '<div style="background-color:white; padding:10px; margin-bottom:15px ; border-bottom:1px solid black;">'.nl2br($comment).'</div>';
		}
	}
	echo '</TD>';
	echo '</TR>';
	echo '</TABLE>';

	$_REQUEST['category_id'] = '4';
	$separator = '<hr>';
	include('modules/Students/includes/Other_Info.inc.php');
}
?>