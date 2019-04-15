<?php

$person_RET = DBGet( "SELECT *
	FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp
	WHERE p.PERSON_ID='" . $_REQUEST['person_id'] . "'
	AND sjp.PERSON_ID=p.PERSON_ID
	AND sjp.STUDENT_ID='" . $_REQUEST['student_id'] . "'" );

$contacts_RET = DBGet( "SELECT TITLE,VALUE
	FROM PEOPLE_JOIN_CONTACTS
	WHERE PERSON_ID='" . $_REQUEST['person_id'] . "'" );

$fields_RET = DBGet( "SELECT pf.ID,pf.TITLE
	FROM PEOPLE_FIELDS pf,PEOPLE_FIELD_CATEGORIES pfc
	WHERE pf.CATEGORY_ID=pfc.ID
	AND (" . ( $person_RET[1]['CUSTODY'] == 'Y' ? "pfc.CUSTODY='Y'" : 'FALSE' ) . "
		OR " . ( $person_RET[1]['EMERGENCY'] == 'Y' ? "pfc.EMERGENCY='Y'" : 'FALSE') . ")
	ORDER BY pfc.SORT_ORDER,pf.SORT_ORDER" );

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
		echo '<tr><td><b>' . $info['TITLE'] . '</b></td><td>' . $info['VALUE'] . '</td></tr>';
	}

	foreach ( (array) $fields_RET as $info )
	{
		echo '<tr><td><b>' . $info['TITLE'] . '</b></td><td>' .
			$person_RET[1]['CUSTOM_'.$info['ID']] . '</td></tr>';
	}

	echo '</table>';
}
else
	echo _( 'This person has no information in the system.' );

PopTable( 'footer' );
