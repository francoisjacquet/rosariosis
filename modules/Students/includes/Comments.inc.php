<?php

//modif Francois: add School Configuration
$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='students'"),array(),array('TITLE'));

//$_ROSARIO['allow_edit'] = true;
if($_REQUEST['modfunc']=='update')
{
	$existing_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM STUDENT_MP_COMMENTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."'"));
	if(!$existing_RET)
		DBQuery("INSERT INTO STUDENT_MP_COMMENTS (SYEAR,STUDENT_ID,MARKING_PERIOD_ID) values('".UserSyear()."','".UserStudentID()."','".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."')");
	SaveData(array('STUDENT_MP_COMMENTS'=>"STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."'"),'',array('COMMENT'=>_('Comment')));
	//unset($_SESSION['_REQUEST_vars']['modfunc']);
	//unset($_SESSION['_REQUEST_vars']['values']);
}
if(empty($_REQUEST['modfunc']))

{
	$comments_RET = DBGet(DBQuery("SELECT COMMENT FROM STUDENT_MP_COMMENTS WHERE STUDENT_ID='".UserStudentID()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID='".($program_config['STUDENTS_SEMESTER_COMMENTS'][1]['VALUE']?GetParentMP('SEM',UserMP()):UserMP())."'"));
	echo '<TABLE>';
	echo '<TR>';
	echo '<TD style="vertical-align:bottom;">';
	echo '<b>'.$mp['TITLE'].' '._('Comments').'</b><BR />';
//modif Francois: remove maxlength limitation as it is not technically needed
	echo '<TEXTAREA id="textarea" name="values[STUDENT_MP_COMMENTS]['.UserStudentID().'][COMMENT]" cols="66" rows="27" style="width:100%;"'.(AllowEdit()?'':' readonly').'>';	
	echo $comments_RET[1]['COMMENT'];
	echo '</TEXTAREA>';
	echo '</TD>';
	echo '</TR></TABLE>';
	echo '<BR /><b>* '.Localize('colon',_('If more than one teacher will be adding comments for this student')).'</b><BR />';
	echo '<ul><li>'._('Type your name above the comments you enter.').'</li></ul>';
	//echo '<li>'._('Leave space for other teachers to enter their comments.').'</li></ul>';

	$_REQUEST['category_id'] = '4';
	$separator = '<hr>';
	include('modules/Students/includes/Other_Info.inc.php');
}
?>