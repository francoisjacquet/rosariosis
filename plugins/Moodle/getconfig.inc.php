<?php

/**
 * Set Moodle plugin configuration options
 *
 * @since 6.0
 *
 * @return bool False if ! UserSchool() or empty constants.
 */
function MoodleConfig()
{
	static $defined_constants = false;

	if ( ! UserSchool() )
	{
		return false;
	}

	if ( ! $defined_constants )
	{
		$defined_constants = true;

		// Define constants for plugin use.

		// Example: http://localhost/moodle
		define( 'MOODLE_URL', ProgramConfig( 'moodle', 'MOODLE_URL' ) );

		// Example: d6c51ea6ffd9857578722831bcb070e1
		define( 'MOODLE_TOKEN', ProgramConfig( 'moodle', 'MOODLE_TOKEN' ) );

		// Example: 10
		define( 'MOODLE_PARENT_ROLE_ID', ProgramConfig( 'moodle', 'MOODLE_PARENT_ROLE_ID' ) );

		$email_field = null;

		if ( Config( 'STUDENTS_EMAIL_FIELD' ) )
		{
			$email_field = Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
				Config( 'STUDENTS_EMAIL_FIELD' ) : 'CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );
		}

		// Example: 11 => CUSTOM_11.
		define( 'ROSARIO_STUDENTS_EMAIL_FIELD', $email_field );
	}

	return MOODLE_URL
		&& MOODLE_TOKEN
		&& MOODLE_PARENT_ROLE_ID
		&& ROSARIO_STUDENTS_EMAIL_FIELD;
}
