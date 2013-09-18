<?php
include_once('ProgramFunctions/StudentsUsersInfo.fnc.php');

if($_REQUEST['modfunc']=='delete' && User('PROFILE')=='admin')
{
	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
		echo '</FORM>';
	if(DeletePrompt($_REQUEST['title']))
	{
		DBQuery("DELETE FROM $_REQUEST[table] WHERE ID='$_REQUEST[id]'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']=='update')
	unset($_REQUEST['modfunc']);

if(empty($_REQUEST['modfunc']))
{
	$_REQUEST['category_id'] = '2';
	include('modules/Students/includes/Other_Info.inc.php');
	PopTable('footer');
	
	$table = 'STUDENT_MEDICAL';
	$functions = array('TYPE'=>'_makeType','MEDICAL_DATE'=>'_makeDate','COMMENTS'=>'_makeComments');
	$med_RET = DBGet(DBQuery("SELECT ID,TYPE,MEDICAL_DATE,COMMENTS FROM STUDENT_MEDICAL WHERE STUDENT_ID='".UserStudentID()."' ORDER BY MEDICAL_DATE,TYPE"),$functions);
	$columns = array('TYPE'=>'','MEDICAL_DATE'=>_('Date'),'COMMENTS'=>_('Comments'));
	$link['add']['html'] = array('TYPE'=>_makeType('',''),'MEDICAL_DATE'=>_makeDate('','MEDICAL_DATE'),'COMMENTS'=>_makeComments('','COMMENTS'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&modfunc=delete&table=STUDENT_MEDICAL&title=".urlencode('immunization or physical');
	$link['remove']['variables'] = array('id'=>'ID');

	ListOutput($med_RET,$columns,'Immunization or Physical','Immunizations or Physicals',$link,array(),array('search'=>false));
	$table = 'STUDENT_MEDICAL_ALERTS';
	$functions = array('TITLE'=>'_makeComments');
	$med_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_MEDICAL_ALERTS WHERE STUDENT_ID='".UserStudentID()."' ORDER BY ID"),$functions);
	$columns = array('TITLE'=>_('Medical Alert'));
	$link['add']['html'] = array('TITLE'=>_makeComments('','TITLE'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&modfunc=delete&table=STUDENT_MEDICAL_ALERTS&title=".urlencode('medical alert');
	$link['remove']['variables'] = array('id'=>'ID');

	ListOutput($med_RET,$columns,'Medical Alert','Medical Alerts',$link,array(),array('search'=>false));

	if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
	{
		$table = 'STUDENT_MEDICAL_VISITS';
		$functions = array('SCHOOL_DATE'=>'_makeDate','TIME_IN'=>'_makeComments','TIME_OUT'=>'_makeComments','REASON'=>'_makeComments','RESULT'=>'_makeComments','COMMENTS'=>'_makeComments');
		$med_RET = DBGet(DBQuery("SELECT ID,SCHOOL_DATE,TIME_IN,TIME_OUT,REASON,RESULT,COMMENTS FROM STUDENT_MEDICAL_VISITS WHERE STUDENT_ID='".UserStudentID()."' ORDER BY SCHOOL_DATE"),$functions);
		$columns = array('SCHOOL_DATE'=>_('Date'),'TIME_IN'=>_('Time In'),'TIME_OUT'=>_('Time Out'),'REASON'=>_('Reason'),'RESULT'=>_('Result'),'COMMENTS'=>_('Comments'));
		$link['add']['html'] = array('SCHOOL_DATE'=>_makeDate('','SCHOOL_DATE'),'TIME_IN'=>_makeComments('','TIME_IN'),'TIME_OUT'=>_makeComments('','TIME_OUT'),'REASON'=>_makeComments('','REASON'),'RESULT'=>_makeComments('','RESULT'),'COMMENTS'=>_makeComments('','COMMENTS'));
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&include=$_REQUEST[include]&modfunc=delete&table=STUDENT_MEDICAL_VISITS&title=".urlencode('visit');
		$link['remove']['variables'] = array('id'=>'ID');
		ListOutput($med_RET,$columns,'Nurse Visit','Nurse Visits',$link,array(),array('search'=>false));
	}
	echo '<TABLE><TR><TD>';
}
?>