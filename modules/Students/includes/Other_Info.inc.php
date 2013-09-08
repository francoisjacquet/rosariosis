<?php
include_once('ProgramFunctions/StudentsUsersInfo.fnc.php');
$category_RET = DBGet(DBQuery("SELECT COLUMNS FROM STUDENT_FIELD_CATEGORIES WHERE ID='".$_REQUEST['category_id']."'"));
$fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED FROM CUSTOM_FIELDS WHERE CATEGORY_ID='".$_REQUEST['category_id']."' ORDER BY SORT_ORDER,TITLE"));
$fields_RET = ParseMLArray($fields_RET,'TITLE');

if(UserStudentID())
{
	$custom_RET = DBGet(DBQuery("SELECT * FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'"));
	$value = $custom_RET[1];
}

//echo '<pre>'; var_dump($fields_RET); echo '</pre>';
if(count($fields_RET))
	echo $separator;
echo '<TABLE class="cellpadding-5">';
$i = 1;
$per_row = $category_RET[1]['COLUMNS']?$category_RET[1]['COLUMNS']:'3';

//modif Francois: Moodle integrator / email field
if ($_REQUEST['moodle_create_student'])
	include('modules/Moodle/config.inc.php');

foreach($fields_RET as $field)
{
	//echo '<pre>'; var_dump($field); echo '</pre>';
	switch($field['TYPE'])
	{
		case 'text':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			
//modif Francois: Moodle integrator / email field
			if ($_REQUEST['moodle_create_student'] && ROSARIO_STUDENTS_EMAIL_FIELD_ID == $field['ID'])
				echo TextInput($value['CUSTOM_'.$field['ID']],'students[CUSTOM_'.$field['ID'].']',($value['CUSTOM_'.$field['ID']]=='' ? '<span class="legend-red">'.$field['TITLE'].'</span>' : $field['TITLE']),' required',false);
			else
				echo _makeTextInput('CUSTOM_'.$field['ID'],$field['TITLE'],'','students');
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;

		case 'autos':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			echo _makeAutoSelectInput('CUSTOM_'.$field['ID'],$field['TITLE'],'students');
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;

		case 'edits':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			echo _makeAutoSelectInput('CUSTOM_'.$field['ID'],$field['TITLE'],'students');
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;

		case 'numeric':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			echo _makeTextInput('CUSTOM_'.$field['ID'],$field['TITLE'],'size=5 maxlength=10','students');
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;

		case 'date':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			echo _makeDateInput('CUSTOM_'.$field['ID'],$field['TITLE'],'students');
//modif Francois: display age next to birthdate
			if ($field['ID'] == '200000004')
				echo _makeStudentAge('CUSTOM_'.$field['ID'], _('Age'));
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;

		case 'exports':
		case 'codeds':
		case 'select':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			echo _makeSelectInput('CUSTOM_'.$field['ID'],$field['TITLE'],'students');
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;

		case 'multiple':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			echo _makeMultipleInput('CUSTOM_'.$field['ID'],$field['TITLE'],'students');
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;

		case 'radio':
			if(($i-1)%$per_row==0)
				echo '<TR class="st">';
			echo '<TD>';
			echo _makeCheckboxInput('CUSTOM_'.$field['ID'],$field['TITLE'],'students');
			echo '</TD>';
			if($i%$per_row==0)
				echo '</TR>';
			else
				echo '<TD style="width:50px;"></TD>';
			$i++;
			break;
	}
}
if(($i-1)%$per_row!=0)
	echo '</TR>';
echo '</TABLE><BR />';

echo '<TABLE class="cellpadding-5">';
$i = 1;
foreach($fields_RET as $field)
{
	if($field['TYPE']=='textarea')
	{
		if(($i-1)%2==0)
			echo '<TR class="st">';
		echo '<TD>';
		echo _makeTextareaInput('CUSTOM_'.$field['ID'],$field['TITLE'],'students');
		echo '</TD>';
		if($i%2==0)
			echo '</TR>';
		else
			echo '<TD style="width:50px;"></TD>';
		$i++;
	}
}
if(($i-1)%2!=0)
	echo '</TR>';
//modif Francois: fix empty table
if ($i == 1)
	echo '<TR><TD></TD></TR>';
echo '</TABLE>';

?>