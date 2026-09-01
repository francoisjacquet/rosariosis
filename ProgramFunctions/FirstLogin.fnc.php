<?php
/**
 * First Login functions.
 *
 * Called on index.php & misc/Portal.php on submit.
 */


if ( ! function_exists( 'DoFirstLoginForm' ) )
{
	/**
	 * Do First Login Form
	 * Save Password & set LAST_LOGIN.
	 *
	 * @since 4.0
	 *
	 * @param array $values Form values.
	 *
	 * @return bool False if no action performed or error, else true.
	 */
	function DoFirstLoginForm( $values )
	{
		global $note;

		$return = false;

		if ( ! empty( $values['PASSWORD'] )
			&& ( User( 'STAFF_ID' ) === '1' || Config( 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN' ) ) )
		{
			// Password set.
			$new_password = encrypt_password( $values['PASSWORD'] );

			if ( User( 'STAFF_ID' ) )
			{
				DBQuery( "UPDATE staff
					SET PASSWORD='" . $new_password . "',LAST_LOGIN=CURRENT_TIMESTAMP
					WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
					AND SYEAR='" . UserSyear() . "'" );
			}
			else
			{
				DBQuery( "UPDATE students
					SET PASSWORD='" . $new_password . "',LAST_LOGIN=CURRENT_TIMESTAMP
					WHERE STUDENT_ID='" . (int) $_SESSION['STUDENT_ID'] . "'" );
			}

			unset( $values['PASSWORD'], $new_password );

			$note[] = _( 'Your new password was saved.' );

			$return = true;
		}

		if ( Config( 'LOGIN' ) === 'No' )
		{
			// Set Config( 'LOGIN' ) to Yes.
			Config( 'LOGIN', 'Yes' );
		}

		return $return;
	}
}

/**
 * First Login Form
 *
 * @since 4.0
 * @since 13.0 Security fix #396 add $RosarioURL config variable
 *
 * @uses FirstLoginFormAfterInstall()
 * @uses FirstLoginFormPasswordChange()
 *
 * Seen by admin on first login after installation.
 *
 * @return string Form HTML.
 */
function FirstLoginForm()
{
	if ( ! HasFirstLoginForm() )
	{
		return '';
	}

	if ( Config( 'LOGIN' ) === 'No'
		&& User( 'STAFF_ID' ) === '1' )
	{
		/**
		 * Add $RosarioURL config variable to config.inc.php file
		 *
		 * @since 13.0 Security fix #396 add $RosarioURL config variable
		 */
		if ( empty( $RosarioURL )
			&& is_writable( 'config.inc.php' ) )
		{
			$config_lines = '/**
 * URL that points to the RosarioSIS instance
 * - Update if you change your site from http to https
 * - Update after domain migration
 *
 * @example https://rosariosis.mydomain.com
 * @example http://localhost/rosariosis/
 */
$RosarioURL = \'' . RosarioURL() . '\';';

			$config = file_get_contents( 'config.inc.php' );

			$config .= "\n" . $config_lines;

			file_put_contents( 'config.inc.php', $config );
		}

		return FirstLoginFormAfterInstall();
	}

	if ( Config( 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN' ) )
	{
		return FirstLoginFormPasswordChange();
	}

	return '';
}

/**
 * Is First Login?
 *
 * @since 5.3
 *
 * @return bool True if no last login & user in session.
 */
function IsFirstLogin()
{
	return empty( $_SESSION['LAST_LOGIN'] ) && ( User( 'STAFF_ID' ) || ! empty( $_SESSION['STUDENT_ID'] ) );
}

if ( ! function_exists( 'HasFirstLoginForm' ) )
{
	/**
	 * Has First Login form?
	 *
	 * @since 5.3
	 *
	 * @return bool True if Is First Login & Force Password change on first login or After Install.
	 */
	function HasFirstLoginForm()
	{
		return ( IsFirstLogin()
				&& Config( 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN' ) )
			|| ( Config( 'LOGIN' ) === 'No' && User( 'STAFF_ID' ) === '1' );
	}
}

if ( ! function_exists( 'FirstLoginFormAfterInstall' ) )
{
	/**
	 * After Install form on First login
	 *
	 * @since 5.3
	 *
	 * @return string Confirm Successful Installation Pop table + form + Poll if admin.
	 */
	function FirstLoginFormAfterInstall()
	{
		ob_start();

		PopTable( 'header', _( 'Confirm Successful Installation' ) );

		$poll_form = FirstLoginPoll();

		if ( $poll_form )
		{
			echo $poll_form;
		}
		else
		{
			?>
			<form action="index.php?modfunc=first-login" method="POST" id="first-login-form" target="_top">
				<h4 class="center">
					<?php
						echo sprintf(
							_( 'You have successfully installed %s.' ),
							ParseMLField( Config( 'TITLE' ) )
						);
					?>
				</h4>
				<p><?php echo implode( '</p><p>', FirstLoginFormFields( 'after_install' ) ); ?></p>
				<p class="center"><?php echo Buttons( _( 'OK' ) ); ?></p>
			</form>
			<?php
		}

		PopTable( 'footer' );

		return ob_get_clean();
	}
}


if ( ! function_exists( 'FirstLoginFormPasswordChange' ) )
{
	/**
	 * Password Change form on First Login.
	 *
	 * @since 5.3
	 *
	 * @return string Pop table with Password Change form.
	 */
	function FirstLoginFormPasswordChange()
	{
		ob_start();

		PopTable( 'header', _( 'Password Change' ) ); ?>

		<form action="index.php?modfunc=first-login" method="POST" id="first-login-form" target="_top">
			<p><?php echo implode( '</p><p>', FirstLoginFormFields( 'force_password_change' ) ); ?></p>
			<p class="center"><?php echo Buttons( _( 'OK' ) ); ?></p>
		</form>

		<?php PopTable( 'footer' );

		return ob_get_clean();
	}
}

if ( ! function_exists( 'FirstLoginFormFields' ) )
{
	/**
	 * Get First Login Form Fields
	 *
	 * @since 4.0
	 * @since 5.3 Add $mode param.
	 * @since 11.1 Prevent using App name, username, or email in the password
	 *
	 * @param string $mode force_password_change|after_install.
	 *
	 * @return array Fields HTML array.
	 */
	function FirstLoginFormFields( $mode = 'force_password_change' )
	{
		global $_ROSARIO;

		$fields = [];

		if ( $mode === 'after_install' )
		{
			$fields[] = sprintf(
				_( 'Check the %s page to spot remaining configuration problems.' ),
				'<a href="diagnostic.php" target="_blank">diagnostic.php</a>'
			);
		}

		if ( ( $mode === 'after_install' && User( 'STAFF_ID' ) === '1' )
			|| $mode === 'force_password_change' )
		{
			AllowEditTemporary( 'start' );

			// @since 11.1 Prevent using App name, username, or email in the password
			$_ROSARIO['PasswordInput']['user_inputs'] = [
				User( 'USERNAME' ),
				User( 'EMAIL' ),
			];

			// Set password on first login.
			$fields[] = PasswordInput(
				'',
				'first_login[PASSWORD]',
				_( 'New Password' ),
				'required strength autofocus'
			);

			AllowEditTemporary( 'stop' );
		}

		return $fields;
	}
}


if ( ! function_exists( 'FirstLoginPoll' ) )
{
	/**
	 * Get First Login Poll
	 *
	 * @since 4.6
	 * @since 5.2 Add Organization radio inputs.
	 * @since 10.4.1 Add Database Type and Version, add PHP version.
	 *
	 * @return string Poll HTML array or empty string if not 'admin' user or if rosariosis.org not reachable.
	 */
	function FirstLoginPoll()
	{
		global $DatabaseType,
			$db_connection;

		if ( User( 'STAFF_ID' ) !== '1'
			|| ! empty( $_REQUEST['poll_cancel'] ) )
		{
			return '';
		}

		if ( ! empty( $_REQUEST['poll'] ) )
		{
			$data = $_REQUEST['poll'];

			$data['ip'] = ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )
				// Filter IP, HTTP_* headers can be forged.
				&& filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ?
				$_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
			$data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$data['url'] = RosarioURL();
			$data['locale'] = $_SESSION['locale'];
			$data['version'] = ROSARIO_VERSION;
			$data['database'] = $DatabaseType;
			// i.e. 8.1.3, get 8.1 back.
			$data['php_version'] = (float) PHP_VERSION;

			if ( $db_connection instanceof PDO )
			{
				// i.e. 5.7.24-0ubuntu0.18.04.1, get 5.7 back.
				$database_version = (float) $db_connection->getAttribute( PDO::ATTR_SERVER_VERSION );
			}
			elseif ( $DatabaseType === 'postgresql' )
			{
				$database_version = pg_version( $db_connection );

				// i.e. 13.8 (Debian 13.8-0+deb11u1), get 13.8 back.
				$database_version = (float) $database_version['server'];
			}
			else
			{
				// i.e. version 10.5.15 is 100515
				$database_version = mysqli_get_server_version( $db_connection );

				$main_version = (int) ( $database_version / 10000 );

				// Get 10.5 back.
				$database_version = $main_version . '.' .
					(int) ( ( $database_version - ( $main_version * 10000 ) ) / 100 );
			}

			$data['database_version'] = $database_version;

			// POST poll data to rosariosis.org.
			try
			{
				$curl = new curl;

				$curl->post( 'https://www.rosariosis.org/installation-poll/poll-submit.php', $data );
			}
			catch ( Exception $e )
			{
				// No curl installed, fail silently.
			}

			return '';
		}

		// Check if client has Internet connection.
		$has_connection = @file_get_contents( 'https://www.rosariosis.org/installation-poll/poll-submit.php' );

		if ( ! $has_connection )
		{
			// Server may be down?
			return '';
		}

		AllowEditTemporary( 'start' );

		$usage_options = [
			'testing' => _( 'Testing' ),
			'production' => _( 'Production' ),
		];

		$fields = [];

		$fields[] = RadioInput( '', 'poll[usage]', _( 'Usage' ), $usage_options, false );

		$school_options = [
			'primary' => _( 'Primary' ),
			'secondary' => _( 'Secondary' ),
			'superior' => _( 'Superior' ),
			'other' => _( 'Other' ),
		];

		$fields[] = RadioInput( '', 'poll[school]', _( 'School' ), $school_options, false );

		$organization_options = [
			'private' => _( 'Private' ),
			'public' => _( 'Public' ),
			'non-profit' => _( 'Non-profit' ),
		];

		$fields[] = RadioInput( '', 'poll[organization]', _( 'Organization' ), $organization_options, false );

		$fields[] = TextInput(
			'0',
			'poll[students]',
			_( 'Students' ),
			'type="number" min="0" max="99999"',
			false
		);

		AllowEditTemporary( 'stop' );

		$buttons = Buttons( _( 'Submit' ) );

		$buttons .= ' <input type="button" value="' . AttrEscape( _( 'Cancel' ) ) .
			// @since RosarioSIS 12.5 CSP remove unsafe-inline Javascript
			'" class="onclick-ajax-link" data-link="index.php?modfunc=first-login&poll_cancel=Y" />';

		$fields[] = '<div class="center">' . $buttons . '</div>';

		$url_lang = '';

		if ( $_SESSION['locale'] === 'es_ES.utf8'
			|| $_SESSION['locale'] === 'fr_FR.utf8' )
		{
			$url_lang = substr( $_SESSION['locale'], 0, 2 ) . '/';
		}

		$fields[] = sprintf(
			_( 'Poll answers are anonymous. Consult installation statistics <a href="%s" target="_blank">online</a>.' ),
			URLEscape( 'https://www.rosariosis.org/' . $url_lang . 'installation-poll/' )
		);

		// @since 12.5 CSP remove unsafe-inline Javascript
		$form = '<form action="index.php?modfunc=first-login" method="POST" id="first-login-poll-form" target="_top">';

		$title = '<legend>' . _( 'Installation Poll' ) . '</legend>';

		return $form . '<fieldset>' . $title . implode( '</p><p>', $fields ) . '</fieldset></form>';
	}
}
