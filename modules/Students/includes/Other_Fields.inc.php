<?php
include_once( 'ProgramFunctions/StudentsUsersInfo.fnc.php' );

$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

//echo '<pre>'; var_dump($fields_RET); echo '</pre>';

echo '<TABLE class="width-100p">';

foreach( (array)$fields_RET as $field )
{
	//echo '<pre>'; var_dump($field); echo '</pre>';

	echo '<TR><TD>';

	switch( $field['TYPE'] )
	{
		case 'text':
		case 'numeric':

			echo _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], $request );

		break;

		case 'autos':
		case 'edits':

			echo _makeAutoSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], $request );

		break;

		case 'date':

			echo _makeDateInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], $request );

		break;

		case 'exports':
		case 'codeds':
		case 'select':

			echo _makeSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], $request );

		break;

		case 'multiple':

			echo _makeMultipleInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], $request );

		break;

		case 'radio':

			echo _makeCheckboxInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], $request );

		break;

		case 'textarea':

			echo _makeTextareaInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], $request );

		break;
	}

	echo '</TD></TR>';
}

echo '</TABLE>';
