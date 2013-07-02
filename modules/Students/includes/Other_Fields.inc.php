<?php
include_once('ProgramFunctions/StudentsUsersInfo.fnc.php');
$fields_RET = ParseMLArray($fields_RET,'TITLE');

//echo '<pre>'; var_dump($fields_RET); echo '</pre>';
echo '<TABLE class="width-100p cellpadding-5">';
foreach($fields_RET as $field)
{
	//echo '<pre>'; var_dump($field); echo '</pre>';
	switch($field['TYPE'])
	{
		case 'text':
			echo '<TR><TD>';
			echo _makeTextInput('CUSTOM_'.$field['ID'],$field['TITLE'],'',$request);
			echo '</TD></TR>';
			break;

		case 'autos':
			echo '<TR><TD>';
			echo _makeAutoSelectInput('CUSTOM_'.$field['ID'],$field['TITLE'],$request);
			echo '</TD></TR>';
			break;

		case 'edits':
			echo '<TR><TD>';
			echo _makeAutoSelectInput('CUSTOM_'.$field['ID'],$field['TITLE'],$request);
			echo '</TD></TR>';
			break;

		case 'numeric':
			echo '<TR><TD>';
			echo _makeTextInput('CUSTOM_'.$field['ID'],$field['TITLE'],'size=5 maxlength=10',$request);
			echo '</TD></TR>';
			break;

		case 'date':
			echo '<TR><TD>';
			echo _makeDateInput('CUSTOM_'.$field['ID'],$field['TITLE'],$request);
			echo '</TD></TR>';
			break;

		case 'exports':
		case 'codeds':
		case 'select':
			echo '<TR><TD>';
			echo _makeSelectInput('CUSTOM_'.$field['ID'],$field['TITLE'],$request);
			echo '</TD></TR>';
			break;

		case 'multiple':
			echo '<TR><TD>';
			echo _makeMultipleInput('CUSTOM_'.$field['ID'],$field['TITLE'],$request);
			echo '</TD></TR>';
			break;

		case 'radio':
			echo '<TR><TD>';
			echo _makeCheckboxInput('CUSTOM_'.$field['ID'],$field['TITLE'],$request);
			echo '</TD></TR>';
			break;

		case'textarea':
			echo '<TR><TD>';
			echo _makeTextareaInput('CUSTOM_'.$field['ID'],$field['TITLE'],$request);
			echo '</TD></TR>';
			break;
	}
}
echo '</TABLE>';

?>