<?php
//plugin configuration interface
//verify the script is called by the right program & plugin is activated
if ($_REQUEST['modname'] == 'School_Setup/Configuration.php' && $RosarioPlugins['Moodle'] && $_REQUEST['modfunc'] == 'config')
{
	//note: no need to call ProgramTitle()

	if ( $_REQUEST['save']=='true')
	{
		if ( $_REQUEST['values'] && $_POST['values'] && AllowEdit())
		{
			//update the PROGRAM_CONFIG table
			if ( ( empty( $_REQUEST['values']['PROGRAM_CONFIG']['MOODLE_PARENT_ROLE_ID'] )
					|| is_numeric( $_REQUEST['values']['PROGRAM_CONFIG']['MOODLE_PARENT_ROLE_ID'] ) )
				&& ( empty( $_REQUEST['values']['PROGRAM_CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID'] )
					|| ( is_numeric( $_REQUEST['values']['PROGRAM_CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID'] )
						|| $_REQUEST['values']['PROGRAM_CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID'] === 'USERNAME' ) ) )
			{
				$sql = '';
				if (isset($_REQUEST['values']['PROGRAM_CONFIG']) && is_array($_REQUEST['values']['PROGRAM_CONFIG']))
					foreach ( (array) $_REQUEST['values']['PROGRAM_CONFIG'] as $column => $value )
					{
						$sql .= "UPDATE PROGRAM_CONFIG SET ";
						$sql .= "VALUE='".$value."' WHERE TITLE='".$column."'";
						$sql .= " AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."';";
					}
				if ($sql != '')
				{
					DBQuery($sql);
					$note[] = button('check') .'&nbsp;'._('The plugin configuration has been modified.');
				}

				unset( $_ROSARIO['ProgramConfig'] ); // update ProgramConfig var
			}
			else
			{
				$error[] = _('Please enter valid Numeric data.');
			}
		}

		unset($_REQUEST['save']);
		unset($_SESSION['_REQUEST_vars']['values']);
		unset($_SESSION['_REQUEST_vars']['save']);
	}

	if ( empty($_REQUEST['save']))
	{
		// TODO: use real values, not the CONSTANTS.
		/*if ( !_validMoodleURLandToken() )
			$error[] = _( 'The Moodle URL is not valid.' );*/

		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&tab=plugins&modfunc=config&plugin=Moodle&save=true" method="POST">';

		DrawHeader('',SubmitButton(_('Save')));

		if (!empty($note))
			echo ErrorMessage($note, 'note');

		if (!empty($error))
			echo ErrorMessage($error, 'error');

		echo '<br />';
		PopTable( 'header', _( 'Moodle' ) );

		// URL
		echo '<table><tr><td>' . TextInput(
			ProgramConfig( 'moodle', 'MOODLE_URL' ),
			'values[PROGRAM_CONFIG][MOODLE_URL]',
			_( 'Moodle URL' ),
			'size=29 placeholder=http://localhost/moodle'
		) .	'</td></tr>';

		$token = ProgramConfig( 'moodle', 'MOODLE_TOKEN' );

		if ( $token
			&& !AllowEdit() ) //obfuscate token as it is sensitive data
		{
			$token = mb_strimwidth( $token, 0, 19, "..." );
		}

		// Token
		echo '<tr><td>' . TextInput(
			$token,
			'values[PROGRAM_CONFIG][MOODLE_TOKEN]',
			_( 'Moodle Token' ),
			'maxlength=32 size=29 placeholder=d6c51ea6ffd9857578722831bcb070e1'
		) . '</td></tr>';

		// Parent Role ID
		echo '<tr><td>' . TextInput(
			ProgramConfig( 'moodle', 'MOODLE_PARENT_ROLE_ID' ),
			'values[PROGRAM_CONFIG][MOODLE_PARENT_ROLE_ID]',
			_( 'Moodle Parent Role ID' ),
			'maxlength=2 size=2 min=0 placeholder=10'
		) . '</td></tr>';

		// Students email Field ID
		$students_email_field_RET = DBGet( DBQuery( "SELECT ID, TITLE
			FROM CUSTOM_FIELDS
			WHERE TYPE='text'
			AND CATEGORY_ID=1" ) );

		$students_email_field_options = array( 'USERNAME' => _( 'Username' ) );

		foreach ( (array) $students_email_field_RET as $field )
		{
			$students_email_field_options[ str_replace( 'custom_', '', $field['ID'] ) ] = ParseMLField( $field['TITLE'] );
		}

		echo '<tr><td>' . SelectInput(
			ProgramConfig( 'moodle', 'ROSARIO_STUDENTS_EMAIL_FIELD_ID' ),
			'values[PROGRAM_CONFIG][ROSARIO_STUDENTS_EMAIL_FIELD_ID]',
			sprintf( _( 'Student email field' ), Config( 'NAME' ) ),
			$students_email_field_options,
			'N/A'
		) . '</td></tr></table>';

		PopTable( 'footer' );

		echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
		echo '</form>';
	}
}
else
{
	$error[] = _('You\'re not allowed to use this program!');
	echo ErrorMessage($error, 'fatal');
}


/**
 * Check Moodle URL and Token
 * Forms a valid URL
 * And that Moodle server responds to webservice test request
 *
 * @todo Finish Moodle core_user_get_users_by_field WS call.
 *
 * @return bool true if URL or Token not set or if URL and Token are OK, else false
 */
function _validMoodleURLandToken()
{
	$url_available = true;

	// Check Moodle URL is available if set
	if ( MOODLE_URL
		&& MOODLE_TOKEN )
	{
		$serverurl = MOODLE_URL . '/webservice/xmlrpc/server.php?wstoken=' . MOODLE_TOKEN;

		if ( ! filter_var( $serverurl, FILTER_VALIDATE_URL ) )
		{
			$url_available = false;
		}
		else // Check URL is available with cURL
		{
			require_once 'plugins/Moodle/client.php';

			/*$functionname = 'core_user_get_users_by_field';

			$object = core_user_get_users_by_field_object();

			moodle_xmlrpc_call( $functionname, $object );*/

			// Leave $url_available = true as moodle_xmlrpc_call()
			// will already add the error to the $error global var
		}

	}

	return $url_available;
}

//core_user_get_users_by_field function
function core_user_get_users_by_field_object()
{
	//then, convert variables for the Moodle object:
/*
[field]  => string
[values] =>
	Array
		(
		[0] => string
		)
*/
	$idnumber = '2'; // Default Admin

	$idnumbers = array(
		'field' => 'idnumber',
		'values' => array( $idnumber ),
	);

	return array( $idnumbers );
}


function core_user_get_users_by_field_response( $response )
{
	return null;
}
