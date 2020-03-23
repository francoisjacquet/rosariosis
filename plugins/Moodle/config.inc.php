<?php
/**
 * Moodle plugin configuration interface
 */

require_once 'plugins/Moodle/includes/ImportUsers.fnc.php';

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| empty( $RosarioPlugins['Moodle'] )
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle().

if ( ! empty( $_REQUEST['save'] ) )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		// Update PROGRAM_CONFIG table.
		$updated = $numeric_error = false;

		foreach ( (array) $_REQUEST['values']['PROGRAM_CONFIG'] as $program => $columns )
		{
			foreach ( (array) $columns as $column => $value )
			{
				$numeric_columns = array(
					'MOODLE_PARENT_ROLE_ID',
					'ROSARIO_STUDENTS_EMAIL_FIELD_ID',
				);

				if ( in_array( $column, $numeric_columns )
					&& $value != ''
					&& ! is_numeric( $value ) )
				{
					if ( $column !== 'ROSARIO_STUDENTS_EMAIL_FIELD_ID'
						|| $value !== 'USERNAME' )
					{
						$numeric_error = true;

						continue;
					}
				}

				ProgramConfig( $program, $column, $value );

				$updated = true;
			}
		}

		if ( $updated )
		{
			$note[] = button( 'check' ) . '&nbsp;' .
				_( 'The plugin configuration has been modified.' );
		}

		if ( $numeric_error )
		{
			$error[] = _( 'Please enter valid Numeric data.' );
		}
	}

	// Unset save & values & redirect URL.
	RedirectURL( 'save', 'values' );
}

if ( ! empty( $_REQUEST['check'] ) )
{
	if ( ! _validMoodleURLandToken() )
	{
		$error[] = _( 'Test' ) . ': ' . _( 'Fail' );
	}
	else
	{
		$note[] = button( 'check' ) . '&nbsp;' . _( 'Test' ) . ': ' . _( 'Success' );
	}

	// Unset check & redirect URL.
	RedirectURL( 'check' );
}

if ( ! empty( $_REQUEST['import_users'] ) )
{
	// @since 5.9 Import Moodle Users.
	if ( ! Config( 'STUDENTS_EMAIL_FIELD' ) )
	{
		$student_email_field = '<b>' . _( 'Student email field' ) . '</b>';

		if ( AllowEdit( 'School_Setup/Configuration.php' ) ) {

			$student_email_field = '<a href="Modules.php?modname=School_Setup/Configuration.php">' .
				$student_email_field . '</a>';
		}

		$error[] = sprintf(
			_( 'You must configure the %s to use this script.' ),
			$student_email_field
		);

		ErrorMessage( $error, 'fatal' );
	}

	// Users auth='manual'.
	$users = MoodleUsersList( 'auth', 'manual' );

	// Filter users confirmed=true, suspended=false, and id not exists in MOODLEXROSARIO table.
	$users_filtered = MoodleUsersFilter( $users );

	if ( ! empty( $_REQUEST['values'] ) )
	{
		$moodle_users_imported = 0;

		foreach ( (array) $_REQUEST['values']['ID'] as $moodle_user_id )
		{
			if ( empty( $_REQUEST['values']['PROFILE'][ $moodle_user_id ] )
				|| empty( $users_filtered[ $moodle_user_id ] ) )
			{
				continue;
			}

			$moodle_user = $users_filtered[ $moodle_user_id ];

			if ( $_REQUEST['values']['PROFILE'][ $moodle_user_id ] === 'student' )
			{
				$student_id = MoodleUserImportStudent( $moodle_user );

				if ( $student_id )
				{
					MoodleUserEnrollStudent( $student_id );

					$moodle_users_imported++;
				}

				continue;
			}

			if ( MoodleUserImportUser( $moodle_user, $_REQUEST['values']['PROFILE'][ $moodle_user_id ] ) )
			{
				$moodle_users_imported++;
			}
		}

		$note[] = sprintf( _( '%s users were imported.' ), $moodle_users_imported );

		// Unset values & import_users & redirect URL.
		RedirectURL( array( 'values', 'import_users' ) );
	}
	else
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Moodle&import_users=true" method="POST" class="import-users-form">';

		DrawHeader(
			'',
			SubmitButton(
				_( 'Import Selected Users' ),
				'',
				' class="import-users-button button-primary"'
			)
		);

		DrawHeader(
			CheckboxInput(
				'',
				'values[PASSWORD_SET_USE_USERNAME]',
				_( 'Set Password: use Username'),
				'',
				true
			) .
			'<br /><br />' .
			MoodleUsersStudentEnrollmentForm()
		);

		$columns = array(
			'CHECKBOX' => MakeChooseCheckbox( '', '', 'values[ID]' ),
			'PROFILE' => _( 'Profile' ),
			'FIRST_NAME' => _( 'First Name' ),
			'LAST_NAME' => _( 'Last Name' ),
			'EMAIL_ADDRESS' => _( 'Email Address' ),
			'USERNAME' => _( 'Username' ),
			'ID' => _( 'Moodle ID' ),
		);

		if ( ROSARIO_DEBUG && function_exists( 'd' ) )
		{
			d( $users_filtered );
		}

		$LO_users = MoodleUsersMake( $users_filtered );

		ListOutput( $LO_users, $columns, 'Moodle User', 'Moodle Users' );

		echo '<br /><div class="center">' . SubmitButton(
			_( 'Import Selected Users' ),
			'',
			' class="import-users-button button-primary"'
		) . '</div>';

		echo '</form>';

		MoodleImportUsersFormConfirmCountdownJS( 'import-users' );
	}
}


if ( empty( $_REQUEST['save'] )
	&& empty( $_REQUEST['import_users'] ) )
{
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
		'&tab=plugins&modfunc=config&plugin=Moodle&save=true" method="POST">';

	DrawHeader( '', SubmitButton() );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';
	PopTable( 'header', _( 'Moodle' ) );

	// URL.
	echo '<table><tr><td>' . TextInput(
		ProgramConfig( 'moodle', 'MOODLE_URL' ),
		'values[PROGRAM_CONFIG][moodle][MOODLE_URL]',
		_( 'Moodle URL' ),
		'size=29 placeholder=http://localhost/moodle'
	) .	'</td></tr>';

	$token = ProgramConfig( 'moodle', 'MOODLE_TOKEN' );

	if ( $token
		&& ! AllowEdit() ) // Obfuscate token as it is sensitive data.
	{
		// Fix: do not use mb_strimwidth() as Mbstring polyfill does not implement it.
		// @see classes/PHPCompatibility/Mbstring/Mbstring.php
		$token = mb_substr( $token, 0, 16 ) . '...';
	}

	// Token.
	echo '<tr><td>' . TextInput(
		$token,
		'values[PROGRAM_CONFIG][moodle][MOODLE_TOKEN]',
		_( 'Moodle Token' ),
		'maxlength=32 size=29 placeholder=d6c51ea6ffd9857578722831bcb070e1'
	) . '</td></tr>';

	// Parent Role ID.
	echo '<tr><td>' . TextInput(
		ProgramConfig( 'moodle', 'MOODLE_PARENT_ROLE_ID' ),
		'values[PROGRAM_CONFIG][moodle][MOODLE_PARENT_ROLE_ID]',
		_( 'Moodle Parent Role ID' ),
		'maxlength=2 size=2 min=0 placeholder=10'
	) . '</td></tr>';

	// Students email Field ID.
	$students_email_field_RET = DBGet( "SELECT ID, TITLE
		FROM CUSTOM_FIELDS
		WHERE TYPE='text'
		AND CATEGORY_ID=1" );

	$students_email_field_options = array( 'USERNAME' => _( 'Username' ) );

	foreach ( (array) $students_email_field_RET as $field )
	{
		$students_email_field_options[ str_replace( 'custom_', '', $field['ID'] ) ] = ParseMLField( $field['TITLE'] );
	}

	echo '<tr><td>' . SelectInput(
		ProgramConfig( 'moodle', 'ROSARIO_STUDENTS_EMAIL_FIELD_ID' ),
		'values[PROGRAM_CONFIG][moodle][ROSARIO_STUDENTS_EMAIL_FIELD_ID]',
		sprintf( _( 'Student email field' ), Config( 'NAME' ) ),
		$students_email_field_options,
		'N/A'
	) . '</td></tr></table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton();

	if ( ProgramConfig( 'moodle', 'MOODLE_URL' )
		&& ProgramConfig( 'moodle', 'MOODLE_TOKEN' ) )
	{
		echo ' ' . SubmitButton( _( 'Test' ), 'check', '' );
	}

	echo '</div></form>';

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
		'&tab=plugins&modfunc=config&plugin=Moodle&import_users=true" method="POST">';

	echo '<br /><div class="center">';

	if ( ProgramConfig( 'moodle', 'MOODLE_URL' )
		&& ProgramConfig( 'moodle', 'MOODLE_TOKEN' ) )
	{
		// @since 5.9 Import Moodle Users.
		echo ' ' . SubmitButton( _( 'Import Users' ), '', '' );
	}

	echo '</div></form>';
}


/**
 * Check Moodle URL and Token
 * Forms a valid URL
 * And that Moodle server responds to webservice test request
 *
 * @since 3.9
 *
 * @return bool false if URL or Token not set or invalid, else true
 */
function _validMoodleURLandToken()
{
	require_once 'plugins/Moodle/client.php';

	// Check Moodle URL if set + token set.
	if ( ! MOODLE_URL
		|| ! MOODLE_TOKEN )
	{
		return false;
	}

	$serverurl = MOODLE_URL . '/webservice/xmlrpc/server.php?wstoken=' . MOODLE_TOKEN;

	if ( ! filter_var( $serverurl, FILTER_VALIDATE_URL ) )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'core_user_get_users';

	// Dummy response function.
	function core_user_get_users_response( $response )
	{
		// We had a response, return true so moodle_xmlrpc_call will return true.
		return true;
	}

	$id = 2; // Default Admin ID.

	$criteria = array(
		'key' => 'id',
		'value' => $id,
	);

	$object = array( 'criteria' => $criteria );

	return moodle_xmlrpc_call( $functionname, $object );
}
