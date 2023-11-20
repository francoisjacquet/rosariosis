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
		// Update program_config table.
		$updated = $numeric_error = false;

		foreach ( (array) $_REQUEST['values']['program_config'] as $program => $columns )
		{
			foreach ( (array) $columns as $column => $value )
			{
				$numeric_columns = [
					'MOODLE_PARENT_ROLE_ID',
				];

				if ( in_array( $column, $numeric_columns )
					&& $value != ''
					&& ! is_numeric( $value ) )
				{
					$numeric_error = true;

					continue;
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
	RedirectURL( [ 'save', 'values' ] );
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
	// Users auth='manual'.
	$users = MoodleUsersList( 'auth', 'manual' );

	// Filter users confirmed=true, suspended=false, and id not exists in moodlexrosario table.
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
		RedirectURL( [ 'values', 'import_users' ] );
	}
	else
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Moodle&import_users=true' ) . '" method="POST" class="import-users-form">';

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

		$columns = [
			'CHECKBOX' => MakeChooseCheckbox( '', '', 'values[ID]' ),
			'PROFILE' => _( 'Profile' ),
			'FIRST_NAME' => _( 'First Name' ),
			'LAST_NAME' => _( 'Last Name' ),
			'EMAIL_ADDRESS' => _( 'Email Address' ),
			'USERNAME' => _( 'Username' ),
			'ID' => _( 'Moodle ID' ),
		];

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
	if ( ! Config( 'STUDENTS_EMAIL_FIELD' ) )
	{
		$student_email_field = '<a href="Modules.php?modname=School_Setup/Configuration.php&tab=system"><b>' .
			_( 'Student email field' ) . '</b></a>';

		$error[] = sprintf(
			_( 'You must configure the %s to use this script.' ),
			$student_email_field
		);

		ErrorMessage( $error, 'fatal' );
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&tab=plugins&modfunc=config&plugin=Moodle&save=true' ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	$school_title = '';

	// If more than 1 school, add its title to table title.
	if ( SchoolInfo( 'SCHOOLS_NB' ) > 1 )
	{
		$school_title = SchoolInfo( 'SHORT_NAME' );

		if ( ! $school_title )
		{
			// No short name, get full title.
			$school_title = SchoolInfo( 'TITLE' );
		}

		$school_title = ' (' . $school_title . ')';
	}

	PopTable( 'header', _( 'Moodle' ) . $school_title );

	// URL.
	echo '<table><tr><td>' . TextInput(
		ProgramConfig( 'moodle', 'MOODLE_URL' ),
		'values[program_config][moodle][MOODLE_URL]',
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
		'values[program_config][moodle][MOODLE_TOKEN]',
		_( 'Moodle Token' ),
		'maxlength=32 size=29 placeholder=d6c51ea6ffd9857578722831bcb070e1'
	) . '</td></tr>';

	// Parent Role ID.
	echo '<tr><td>' . TextInput(
		ProgramConfig( 'moodle', 'MOODLE_PARENT_ROLE_ID' ),
		'values[program_config][moodle][MOODLE_PARENT_ROLE_ID]',
		_( 'Moodle Parent Role ID' ),
		'maxlength=2 size=2 min=0 placeholder=10'
	) . '</td></tr></table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton();

	if ( ProgramConfig( 'moodle', 'MOODLE_URL' )
		&& ProgramConfig( 'moodle', 'MOODLE_TOKEN' ) )
	{
		echo ' ' . SubmitButton( _( 'Test' ), 'check', '' );
	}

	echo '</div></form>';

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&tab=plugins&modfunc=config&plugin=Moodle&import_users=true' ) . '" method="POST">';

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

	$criteria = [
		'key' => 'id',
		'value' => $id,
	];

	$object = [ 'criteria' => $criteria ];

	return moodle_xmlrpc_call( $functionname, $object );
}
