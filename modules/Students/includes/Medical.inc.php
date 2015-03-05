<?php
include_once('ProgramFunctions/StudentsUsersInfo.fnc.php');


if($_REQUEST['values'] && $_REQUEST['category_id']== '2')
	SaveData(array('STUDENT_MEDICAL_ALERTS'=>"ID='__ID__'",'STUDENT_MEDICAL'=>"ID='__ID__'",'STUDENT_MEDICAL_VISITS'=>"ID='__ID__'",'fields'=>array('STUDENT_MEDICAL'=>'ID,STUDENT_ID,','STUDENT_MEDICAL_ALERTS'=>'ID,STUDENT_ID,','STUDENT_MEDICAL_VISITS'=>'ID,STUDENT_ID,'),'values'=>array('STUDENT_MEDICAL'=>db_seq_nextval('STUDENT_MEDICAL_SEQ').",'".UserStudentID()."',",'STUDENT_MEDICAL_ALERTS'=>db_seq_nextval('STUDENT_MEDICAL_ALERTS_SEQ').",'".UserStudentID()."',",'STUDENT_MEDICAL_VISITS'=>db_seq_nextval('STUDENT_MEDICAL_VISITS_SEQ').",'".UserStudentID()."',")));

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
		echo '</FORM>';
	if(DeletePrompt($_REQUEST['title']))
	{
		DBQuery("DELETE FROM ".$_REQUEST['table']." WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']=='update')
	unset($_REQUEST['modfunc']);

if(empty($_REQUEST['modfunc']))
{
	$_REQUEST['category_id'] = '2';
	include('modules/Students/includes/Other_Info.inc.php');
	
	if ($PopTable_opened)
		PopTable('footer');
	
	$table = 'STUDENT_MEDICAL';
	$functions = array('TYPE'=>'_makeType','MEDICAL_DATE'=>'_makeDate','COMMENTS'=>'_makeComments');
	$med_RET = DBGet(DBQuery("SELECT ID,TYPE,MEDICAL_DATE,COMMENTS FROM STUDENT_MEDICAL WHERE STUDENT_ID='".UserStudentID()."' ORDER BY MEDICAL_DATE,TYPE"),$functions);
	$columns = array('TYPE'=>'','MEDICAL_DATE'=>_('Date'),'COMMENTS'=>_('Comments'));
	$link['add']['html'] = array('TYPE'=>_makeType('',''),'MEDICAL_DATE'=>_makeDate('','MEDICAL_DATE'),'COMMENTS'=>_makeComments('','COMMENTS'));
	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&category_id='.$_REQUEST['category_id'].'&modfunc=delete&table=STUDENT_MEDICAL&title='.urlencode(_('Immunization or Physical'));
	$link['remove']['variables'] = array('id'=>'ID');

	ListOutput($med_RET,$columns,'Immunization or Physical','Immunizations or Physicals',$link,array(),array('search'=>false));
	$table = 'STUDENT_MEDICAL_ALERTS';
	$functions = array('TITLE'=>'_makeComments');
	$med_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_MEDICAL_ALERTS WHERE STUDENT_ID='".UserStudentID()."' ORDER BY ID"),$functions);
	$columns = array('TITLE'=>_('Medical Alert'));
	$link['add']['html'] = array('TITLE'=>_makeComments('','TITLE'));
	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&category_id='.$_REQUEST['category_id'].'&modfunc=delete&table=STUDENT_MEDICAL_ALERTS&title='.urlencode(_('Medical Alert'));
	$link['remove']['variables'] = array('id'=>'ID');

	ListOutput($med_RET,$columns,'Medical Alert','Medical Alerts',$link,array(),array('search'=>false));

	if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
	{
		$table = 'STUDENT_MEDICAL_VISITS';
		$functions = array('SCHOOL_DATE'=>'_makeDate','TIME_IN'=>'_makeComments','TIME_OUT'=>'_makeComments','REASON'=>'_makeComments','RESULT'=>'_makeComments','COMMENTS'=>'_makeComments');
		$med_RET = DBGet(DBQuery("SELECT ID,SCHOOL_DATE,TIME_IN,TIME_OUT,REASON,RESULT,COMMENTS FROM STUDENT_MEDICAL_VISITS WHERE STUDENT_ID='".UserStudentID()."' ORDER BY SCHOOL_DATE"),$functions);
		$columns = array('SCHOOL_DATE'=>_('Date'),'TIME_IN'=>_('Time In'),'TIME_OUT'=>_('Time Out'),'REASON'=>_('Reason'),'RESULT'=>_('Result'),'COMMENTS'=>_('Comments'));
		$link['add']['html'] = array('SCHOOL_DATE'=>_makeDate('','SCHOOL_DATE'),'TIME_IN'=>_makeComments('','TIME_IN'),'TIME_OUT'=>_makeComments('','TIME_OUT'),'REASON'=>_makeComments('','REASON'),'RESULT'=>_makeComments('','RESULT'),'COMMENTS'=>_makeComments('','COMMENTS'));
		$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&category_id='.$_REQUEST['category_id'].'&modfunc=delete&table=STUDENT_MEDICAL_VISITS&title='.urlencode(_('Nurse Visit'));
		$link['remove']['variables'] = array('id'=>'ID');
		ListOutput($med_RET,$columns,'Nurse Visit','Nurse Visits',$link,array(),array('search'=>false));
	}
	if ($PopTable_opened)//FJ bugfix display in PrintStudentInfo.php
		echo '<TABLE><TR><TD>';
}
?>
