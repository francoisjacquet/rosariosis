<?php

// user logged in
if ( UserSchool() > 0 )
{
	//define constants for plugin use

	//example: http://localhost/moodle
	define( 'MOODLE_URL', ProgramConfig( 'moodle', 'MOODLE_URL' ) );

	//example: d6c51ea6ffd9857578722831bcb070e1
	define( 'MOODLE_TOKEN', ProgramConfig( 'moodle', 'MOODLE_TOKEN' ) );

	//example: 10
	define( 'MOODLE_PARENT_ROLE_ID', ProgramConfig( 'moodle', 'MOODLE_PARENT_ROLE_ID' ) );

	//example: 11
	define( 'ROSARIO_STUDENTS_EMAIL_FIELD_ID', ProgramConfig( 'moodle', 'ROSARIO_STUDENTS_EMAIL_FIELD_ID' ) );
}
// not logged in
else
{
	define( 'MOODLE_URL', null );
	define( 'MOODLE_TOKEN', null );
	define( 'MOODLE_PARENT_ROLE_ID', null );
	define( 'ROSARIO_STUDENTS_EMAIL_FIELD_ID', null );
}
