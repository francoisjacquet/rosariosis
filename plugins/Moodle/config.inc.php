<?php
//plugin configuration interface
//verify the script is called by the right program & plugin is activated
if ($_REQUEST['modname'] == 'School_Setup/Configuration.php' && $RosarioPlugins['Moodle'] && $_REQUEST['modfunc'] == 'config')
{
	//note: no need to call ProgramTitle()

	if($_REQUEST['save']=='true')
	{
		if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
		{
			//update the PROGRAM_CONFIG table
			if ((empty($_REQUEST['values']['PROGRAM_CONFIG']['MOODLE_PARENT_ROLE_ID']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['MOODLE_PARENT_ROLE_ID'])) && (empty($_REQUEST['values']['PROGRAM_CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID']) || is_numeric($_REQUEST['values']['PROGRAM_CONFIG']['ROSARIO_STUDENTS_EMAIL_FIELD_ID'])))
			{
				$sql = '';
				if (isset($_REQUEST['values']['PROGRAM_CONFIG']) && is_array($_REQUEST['values']['PROGRAM_CONFIG']))
					foreach($_REQUEST['values']['PROGRAM_CONFIG'] as $column=>$value)
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

	if(empty($_REQUEST['save']))
	{
		if ( !_validMoodleURLandToken() )
			$error[] = _( 'The Moodle URL is not valid.' );

		echo '<FORM ACTION="Modules.php?modname='.$_REQUEST['modname'].'&tab=plugins&modfunc=config&plugin=Moodle&save=true" METHOD="POST">';
	
		DrawHeader('',SubmitButton(_('Save')));
		
		if (!empty($note))
			echo ErrorMessage($note, 'note');

		if (!empty($error))
			echo ErrorMessage($error, 'error');

		echo '<BR />';
		PopTable('header',_('Moodle'));

		echo '<FIELDSET><legend>'._('Moodle').'</legend><TABLE>';

		// URL
		echo '<TR><TD>' . TextInput(
			ProgramConfig( 'moodle', 'MOODLE_URL' ),
			'values[PROGRAM_CONFIG][MOODLE_URL]',
			_( 'Moodle URL' ),
			'size=29 placeholder=http://localhost/moodle'
		) .	'</TD></TR>';

		$token = ProgramConfig( 'moodle', 'MOODLE_TOKEN' );

		if ( $token
			&& !AllowEdit() ) //obfuscate token as it is sensitive data
		{
			$token = mb_strimwidth( $token, 0, 19, "..." );
		}

		// Token
		echo '<TR><TD>' . TextInput(
			$token,
			'values[PROGRAM_CONFIG][MOODLE_TOKEN]',
			_( 'Moodle Token' ),
			'maxlength=32 size=29 placeholder=d6c51ea6ffd9857578722831bcb070e1'
		) . '</TD></TR>';

		// Parent Role ID
		echo '<TR><TD>' . TextInput(
			ProgramConfig( 'moodle', 'MOODLE_PARENT_ROLE_ID' ),
			'values[PROGRAM_CONFIG][MOODLE_PARENT_ROLE_ID]',
			_( 'Moodle Parent Role ID' ),
			'maxlength=2 size=2 min=0 placeholder=10'
		) . '</TD></TR>';

		// Students email Field ID	
		echo '<TR><TD>' . TextInput(
			ProgramConfig( 'moodle', 'ROSARIO_STUDENTS_EMAIL_FIELD_ID' ),
			'values[PROGRAM_CONFIG][ROSARIO_STUDENTS_EMAIL_FIELD_ID]',
			sprintf( _( '%s Student email field ID' ), Config( 'NAME' ) ),
			'maxlength=2 size=2 min=0 placeholder=11'
		) . '</TD></TR>';
	
		echo '</TABLE></FIELDSET>';

		PopTable('footer');

		echo '<BR /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
		echo '</FORM>';
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
 * TODO test
 *
 * @return bool true if URL or Token not set or if URL and Token are OK, else false
 */
function _validMoodleURLandToken()
{
	$url_available = true;

	// Check Moodle URL is available if set
	if ( !empty( MOODLE_URL )
		&& !empty( MOODLE_TOKEN ) )
	{
		$serverurl = MOODLE_URL . '/webservice/xmlrpc/server.php?wstoken=' . MOODLE_TOKEN;

		if ( filter_var( $serverurl, FILTER_VALIDATE_URL) === false )
		{
			$url_available = false;
		}
		else // Check URL is available with cURL
		{
			$functionname = 'core_user_create_users';

			$object = core_user_create_users_object();

			moodle_xmlrpc_call( $functionname, $object );

			// Leave $url_available = true as moodle_xmlrpc_call()
			// will already add the error to the $error global var
		}

	}

	return $url_available;
}

//core_user_get_users_by_id function
//TODO write function!
function core_user_get_users_by_id_object()
{
	//then, convert variables for the Moodle object:
/*
list of (
	object {
)
*/
	$idnumber = 1; // Default Admin

	$users = array(
		array(
			'idnumber' => $idnumber,
		)
	);

	return array( $users );
}


function core_user_get_users_by_id_response( $response )
{
	return null;
}
