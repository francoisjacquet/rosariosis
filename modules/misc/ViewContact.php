<?php

$person_RET = DBGet( "SELECT *
	FROM people p,students_join_people sjp
	WHERE p.PERSON_ID='" . (int) $_REQUEST['person_id'] . "'
	AND sjp.PERSON_ID=p.PERSON_ID
	AND sjp.STUDENT_ID='" . (int) $_REQUEST['student_id'] . "'" );

$contacts_RET = DBGet( "SELECT TITLE,VALUE
	FROM people_join_contacts
	WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'" );

$fields_RET = DBGet( "SELECT pf.ID,pf.TITLE
	FROM people_fields pf,people_field_categories pfc
	WHERE pf.CATEGORY_ID=pfc.ID
	AND (" . ( $person_RET[1]['CUSTODY'] == 'Y' ? "pfc.CUSTODY='Y'" : 'FALSE' ) . "
		OR " . ( $person_RET[1]['EMERGENCY'] == 'Y' ? "pfc.EMERGENCY='Y'" : 'FALSE') . ")
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
		echo '<tr><td class="size-1">' . $info['TITLE'] . '></td><td>' .
			$person_RET[1]['CUSTOM_'.$info['ID']] . '</td></tr>';
	}

	echo '</table>';
}
else
	echo _( 'This person has no information in the system.' );

PopTable( 'footer' );
