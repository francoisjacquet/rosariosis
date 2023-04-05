<?php

$person_RET = DBGet( "SELECT *
	FROM people p,students_join_people sjp
	WHERE p.PERSON_ID='" . (int) $_REQUEST['person_id'] . "'
	AND sjp.PERSON_ID=p.PERSON_ID
	AND sjp.STUDENT_ID='" . (int) $_REQUEST['student_id'] . "'" );

$contacts_RET = DBGet( "SELECT TITLE,VALUE
	FROM people_join_contacts
	WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'" );

$fields_RET = DBGet( "SELECT pf.ID,pf.TITLE,pf.TYPE
	FROM people_fields pf,people_field_categories pfc
	WHERE pf.CATEGORY_ID=pfc.ID
	AND (" . ( $person_RET[1]['CUSTODY'] == 'Y' ? "pfc.CUSTODY='Y'" : 'FALSE' ) . "
		OR " . ( $person_RET[1]['EMERGENCY'] == 'Y' ? "pfc.EMERGENCY='Y'" : 'FALSE') . ")
	AND pf.TYPE NOT IN('files','textarea')
	ORDER BY pfc.SORT_ORDER IS NULL,pfc.SORT_ORDER,pf.SORT_ORDER IS NULL,pf.SORT_ORDER" );

echo '<br />';

PopTable(
	'header',
	( $person_RET[1]['STUDENT_RELATION'] ? $person_RET[1]['STUDENT_RELATION'] . ': ' : '' ) .
		DisplayName(
			$person_RET[1]['FIRST_NAME'],
			$person_RET[1]['LAST_NAME'],
			$person_RET[1]['MIDDLE_NAME']
		)
);

if ( ! empty( $contacts_RET )
	|| ! empty( $fields_RET ) )
{
	echo '<table class="widefat width-100p">';

	foreach ( (array) $contacts_RET as $info )
	{
		echo '<tr><td class="size-1">' . $info['TITLE'] . '</td><td>' . $info['VALUE'] . '</td></tr>';
	}

	foreach ( (array) $fields_RET as $info )
	{
		$info_value = $person_RET[1]['CUSTOM_' . $info['ID']];

		$make_field_function = makeFieldTypeFunction( $info['TYPE'] );

		if ( $make_field_function )
		{
			// Format Contact Field value based on its Type
			$info_value = $make_field_function( $info_value, 'PEOPLE_' . $info['ID'] );
		}

		echo '<tr><td class="size-1">' . ParseMLField( $info['TITLE'] ) . '</td><td>' .
			$info_value . '</td></tr>';
	}

	echo '</table>';
}
else
	echo _( 'This person has no information in the system.' );

PopTable( 'footer' );
