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

		PopTable( 'header', _( 'Confirm Successful Installation' ) ); ?>

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
		<?php echo FirstLoginPoll(); ?>

		<?php PopTable( 'footer' );

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
			$_ROSARIO['allow_edit'] = true;

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
		global $locale,
			$_ROSARIO,
			$DatabaseType,
			$db_connection;

		if ( User( 'STAFF_ID' ) !== '1' )
		{
			return '';
		}

		// Check if client has Internet connection.
		$has_connection = @file_get_contents( 'https://www.rosariosis.org/installation-poll/poll-submit.php' );

		if ( ! $has_connection )
		{
			// Server may be down?
			return '';
		}

		$fields = [];

		$fields[] = '<input type="hidden" name="locale" value="' . AttrEscape( $locale ) . '">';

		$fields[] = '<input type="hidden" name="version" value="' . AttrEscape( ROSARIO_VERSION ) . '">';

		$fields[] = '<input type="hidden" name="database" value="' . AttrEscape( $DatabaseType ) . '">';

		if ( $DatabaseType === 'postgresql' )
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

		$fields[] = '<input type="hidden" name="database_version" value="' . AttrEscape( $database_version ) . '">';

		// i.e. 8.1.3, get 8.1 back.
		$php_version = (float) PHP_VERSION;

		$fields[] = '<input type="hidden" name="php_version" value="' . AttrEscape( $php_version ) . '">';

		$_ROSARIO['allow_edit'] = true;

		$usage_options = [
			'testing' => _( 'Testing' ),
			'production' => _( 'Production' ),
		];

		$fields[] = RadioInput( '', 'usage', _( 'Usage' ), $usage_options, false );

		$school_options = [
			'primary' => _( 'Primary' ),
			'secondary' => _( 'Secondary' ),
			'superior' => _( 'Superior' ),
			'other' => _( 'Other' ),
		];

		$fields[] = RadioInput( '', 'school', _( 'School' ), $school_options, false );

		$organization_options = [
			'private' => _( 'Private' ),
			'public' => _( 'Public' ),
			'non-profit' => _( 'Non-profit' ),
		];

		$fields[] = RadioInput( '', 'organization', _( 'Organization' ), $organization_options, false );

		$fields[] = TextInput(
			'0',
			'students',
			_( 'Students' ),
			'type="number" min="0" max="99999" length="4"',
			false
		);

		$_ROSARIO['allow_edit'] = false;

		$fields[] = '<div class="center">' . Buttons( _( 'Submit' ), _( 'Cancel' ) ) . '</div>';

		$url_lang = '';

		if ( $locale === 'es_ES.utf8'
			|| $locale === 'fr_FR.utf8' )
		{
			$url_lang = substr( $locale, 0, 2 ) . '/';
		}

		$fields[] = sprintf(
			_( 'Poll answers are anonymous. Consult installation statistics <a href="%s" target="_blank">online</a>.' ),
			URLEscape( 'https://www.rosariosis.org/' . $url_lang . 'installation-poll/' )
		);

		ob_start(); ?>
		<script>$('#first-login-form').hide();

		$('#first-login-poll-form input[type="reset"]').click(function(){
			$('#first-login-poll-form').hide();
			$('#first-login-form').show();
		});

		$('#first-login-poll-form').submit(function(e){

			var form = $(this),
				url = form.attr('action');

			$('#first-login-poll-form input[type="submit"],#first-login-poll-form input[type="reset"]').attr('disabled', 'disabled');
			$.ajax({
				type: 'POST',
				url: url,
				data: form.serialize(),
				complete: function(jqxhr,status) {
					$('#first-login-poll-form').hide();
					$('#first-login-form').show();
				}
			});

			e.preventDefault();
		});</script>
		<?php
		$js = ob_get_clean();

		$form = '<form action="https://www.rosariosis.org/installation-poll/poll-submit.php" method="POST" id="first-login-poll-form" target="_top">';

		$title = '<legend>' . _( 'Installation Poll' ) . '</legend>';

		return $form . '<fieldset>' . $title . implode( '</p><p>', $fields ) . '</fieldset></form>' . $js;
	}
}
