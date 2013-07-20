<?php
DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update')
{
	if($_REQUEST['student'] && $_POST['student'])
	{
		foreach($_REQUEST['student'] as $transaction_id=>$school_id)
			if($school_id)
				DBQuery("UPDATE FOOD_SERVICE_TRANSACTIONS SET SCHOOL_ID='".$school_id."' WHERE TRANSACTION_ID='".$transaction_id."'");
	}
	if($_REQUEST['staff'] && $_POST['staff'])
	{
		foreach($_REQUEST['staff'] as $transaction_id=>$school_id)
			if($school_id)
				DBQuery("UPDATE FOOD_SERVICE_STAFF_TRANSACTIONS SET SCHOOL_ID='".$school_id."' WHERE TRANSACTION_ID='".$transaction_id."'");
	}
	unset($_REQUEST['student']);
	unset($_REQUEST['staff']);
	unset($_REQUEST['modfunc']);
}

$schools_RET = DBGet(DBQuery("SELECT ID,SYEAR,TITLE FROM SCHOOLS"),array(),array('SYEAR'));
//echo '<pre>'; var_dump($schools_RET); echo '</pre>';
foreach($schools_RET as $syear=>$schools)
	foreach($schools as $school)
		$schools_select[$syear][$school['ID']] = $school['TITLE'];
//echo '<pre>'; var_dump($schools_select); echo '</pre>';

$students_RET = DBGet(DBQuery("SELECT fst.TRANSACTION_ID,fst.ACCOUNT_ID,fst.SYEAR,".db_case(array('fst.STUDENT_ID',"''",'NULL',"(SELECT FIRST_NAME||' '||LAST_NAME FROM STUDENTS WHERE STUDENT_ID=fst.STUDENT_ID)"))." AS FULL_NAME,fst.ACCOUNT_ID AS STUDENTS,fst.SCHOOL_ID FROM FOOD_SERVICE_TRANSACTIONS fst WHERE fst.SCHOOL_ID IS NULL"),array('STUDENTS'=>'_students','SCHOOL_ID'=>'_make_school'));
$staff_RET = DBGet(DBQuery("SELECT fst.TRANSACTION_ID,fst.STAFF_ID,fst.SYEAR,(SELECT FIRST_NAME||' '||LAST_NAME FROM STAFF WHERE STAFF_ID=fst.STAFF_ID) AS FULL_NAME,fst.SCHOOL_ID FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst WHERE fst.SCHOOL_ID IS NULL"),array('SCHOOL_ID'=>'_make_staff_school'));

//echo '<pre>'; var_dump($students_RET); echo '</pre>';
//echo '<pre>'; var_dump($users_RET); echo '</pre>';

echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';
DrawHeader('',SubmitButton(_('Save')));
$columns = array('TRANSACTION_ID'=>_('ID'),'ACCOUNT_ID'=>_('Account ID'),'SYEAR'=>_('School Year'),'FULL_NAME'=>_('Student'),'STUDENTS'=>_('Students'),'SCHOOL_ID'=>_('School'));
ListOutput($students_RET,$columns,'Student Transaction w/o School','Student Transactions w/o School',false,array(),array('save'=>false,'search'=>false));
$columns = array('TRANSACTION_ID'=>_('ID'),'SYEAR'=>_('School Year'),'FULL_NAME'=>_('User'),'SCHOOL_ID'=>_('School'));
ListOutput($staff_RET,$columns,'User Transaction w/o School','User Transactions w/o School',false,array(),array('save'=>false,'search'=>false));
echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
echo '</FORM>';

function _students($value,$column)
{
	$RET = DBGet(DBQuery("SELECT s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fsa WHERE s.STUDENT_ID=fsa.STUDENT_ID AND fsa.ACCOUNT_ID='".$value."'"));
	foreach($RET as $student)
		$ret .= $student['FULL_NAME'].'<BR />';
	$ret = mb_substr($ret,0,-4);
	return $ret;
}

function _make_school($value,$column)
{	global $THIS_RET,$schools_select;

	return SelectInput($value,"student[$THIS_RET[TRANSACTION_ID]]",'',$schools_select[$THIS_RET['SYEAR']]);
	//function SelectInput($value,$name,$title='',$options,$allow_na=_('N/A'),$extra='',$div=true)
}

function _make_staff_school($value,$column)
{	global $THIS_RET,$schools_select;

	return SelectInput($value,"staff[$THIS_RET[TRANSACTION_ID]]",'',$schools_select[$THIS_RET['SYEAR']]);
	//function SelectInput($value,$name,$title='',$options,$allow_na=_('N/A'),$extra='',$div=true)
}
?>
