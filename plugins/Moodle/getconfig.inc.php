<?php

// user logged in
if ( UserSchool() > 0 )
{
	//get config values from PROGRAM_CONFIG table
	$program_config = DBGet( DBQuery( "SELECT * FROM
		PROGRAM_CONFIG WHERE
		SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND PROGRAM='moodle'"), array(), array( 'TITLE' ) );

	//define constants for plugin use

	//example: http://localhost/moodle
	define( 'MOODLE_URL', $program_config['MOODLE_URL'][1]['VALUE'] );

	//example: d6c51ea6ffd9857578722831bcb070e1
	define( 'MOODLE_TOKEN', $program_config['MOODLE_TOKEN'][1]['VALUE'] );

	//example: 10
	define( 'MOODLE_PARENT_ROLE_ID', $program_config['MOODLE_PARENT_ROLE_ID'][1]['VALUE'] );

	//example: 11
	define( 'ROSARIO_STUDENTS_EMAIL_FIELD_ID', $program_config['ROSARIO_STUDENTS_EMAIL_FIELD_ID'][1]['VALUE'] );
}
// not logged in
else
{
	define( 'MOODLE_URL', null );
	define( 'MOODLE_TOKEN', null );
	define( 'MOODLE_PARENT_ROLE_ID', null );
	define( 'ROSARIO_STUDENTS_EMAIL_FIELD_ID', null );
}

?>