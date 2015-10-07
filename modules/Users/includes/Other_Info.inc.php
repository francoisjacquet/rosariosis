<?php
include_once( 'ProgramFunctions/StudentsUsersInfo.fnc.php' );

$category_RET = DBGet( DBQuery( "SELECT COLUMNS
	FROM STAFF_FIELD_CATEGORIES
	WHERE ID='" . $_REQUEST['category_id'] . "'" ) );

$fields_RET = DBGet( DBQuery( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED
	FROM STAFF_FIELDS
	WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'
	ORDER BY SORT_ORDER,TITLE" ) );

$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

$value = array();

if( UserStaffID() )
{
	$custom_RET = DBGet( DBQuery( "SELECT * FROM
		STAFF
		WHERE STAFF_ID='" . UserStaffID() . "'" ) );

	$value = $custom_RET[1];
}

if ( count( $fields_RET ) )
{
	echo $separator;

	echo '<TABLE class="width-100p valign-top">';
}

$i = 1;

/**
 * Number of Columns per Row
 * Default: 3
 *
 * @var int
 */
$per_row = $category_RET[1]['COLUMNS'] ? (int)$category_RET[1]['COLUMNS'] : 3;

foreach( (array)$fields_RET as $field )
{

	//echo '<pre>'; var_dump($field); echo '</pre>';

	if ( ( $i - 1 )%$per_row === 0 )
		echo '<TR class="st">';

	echo '<TD>';

	switch( $field['TYPE'] )
	{
		case 'text':

			echo _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

		break;

		case 'autos':
		case 'edits':

			echo _makeAutoSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

		break;

		case 'numeric':

			echo _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'size=9 maxlength=18', 'staff' );

		break;

		case 'date':

			echo _makeDateInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

		break;

		case 'exports':
		case 'codeds':
		case 'select':

			echo _makeSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

		break;

		case 'multiple':

			echo _makeMultipleInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

		break;

		case 'radio':

			echo _makeCheckboxInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

		break;

		case 'textarea':

			// Only 2 fields per row when textarea
			if ( $per_row > 2 )
			{
				// New row
				echo '</TD></TR><TR class="st">';

				echo '<TD colspan="' . ( $per_row - 1 ) . '">';

				$i = $i + $i%$per_row;
			}

			echo _makeTextareaInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

		break;
	}

	echo '</TD>';

	if ( $i%$per_row === 0 )
		echo '</TR>';

	$i++;
}

if ( $i > 1 )
{
	if ( ( $i - 1 )%$per_row !== 0 )
		echo '</TR>';

	echo '</TABLE>';
}
