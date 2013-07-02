<?php
$person_RET = DBGet(DBQuery("SELECT * FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp WHERE p.PERSON_ID='$_REQUEST[person_id]' AND sjp.PERSON_ID=p.PERSON_ID AND sjp.STUDENT_ID='$_REQUEST[student_id]'"));
$contacts_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PEOPLE_JOIN_CONTACTS WHERE PERSON_ID='$_REQUEST[person_id]'"));
$fields_RET = DBGet(DBQuery("SELECT pf.ID,pf.TITLE FROM PEOPLE_FIELDS pf,PEOPLE_FIELD_CATEGORIES pfc WHERE pf.CATEGORY_ID=pfc.ID AND (".($person_RET[1]['CUSTODY']=='Y'?"pfc.CUSTODY='Y'":'FALSE')." OR ".($person_RET[1]['EMERGENCY']=='Y'?"pfc.EMERGENCY='Y'":'FALSE').") ORDER BY pfc.SORT_ORDER,pf.SORT_ORDER"));

echo '<BR />';
PopTable('header',($person_RET[1]['STUDENT_RELATION']?$person_RET[1]['STUDENT_RELATION'].': ':'').$person_RET[1]['FIRST_NAME'].' '.$person_RET[1]['MIDDLE_NAME'].' '.$person_RET[1]['LAST_NAME'],'width="75%"');
if(count($contacts_RET) || count($fields_RET))
{
	foreach($contacts_RET as $info)
		echo '<B>'.$info['TITLE'].'</B>: '.$info['VALUE'].'<BR />';
	foreach($fields_RET as $info)
		echo '<B>'.$info['TITLE'].'</B>: '.$person_RET[1]['CUSTOM_'.$info['ID']].'<BR />';
}
else
	echo _('This person has no information in the system.');
PopTable('footer');
?>