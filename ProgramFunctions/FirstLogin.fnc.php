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
				DBQuery( "UPDATE STAFF
					SET PASSWORD='" . $new_password . "'
					WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
					AND SYEAR='" . UserSyear() . "'" );

				DBQuery( "UPDATE STAFF
					SET LAST_LOGIN=CURRENT_TIMESTAMP
					WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
			}
			else
			{
				DBQuery( "UPDATE STUDENTS
					SET PASSWORD='" . $new_password . "'
					WHERE STUDENT_ID='" . $_SESSION['STUDENT_ID'] . "'" );

				DBQuery( "UPDATE STUDENTS
					SET LAST_LOGIN=CURRENT_TIMESTAMP
					WHERE STUDENT_ID='" . $_SESSION['STUDENT_ID'] . "'" );
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
 * @uses FirstLoginLoadJSCSS()
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

	if ( Config( 'LOGIN' ) === 'No' )
	{
		$first_login_form =  FirstLoginFormAfterInstall();
	}
	elseif ( Config( 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN' ) )
	{
		$first_login_form =  FirstLoginFormPasswordChange();
	}

	return FirstLoginLoadJSCSS() . $first_login_form;
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
		return IsFirstLogin()
			&& ( Config( 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN' ) || Config( 'LOGIN' ) === 'No' );
	}
}

if ( ! function_exists( 'FirstLoginLoadJSCSS' ) )
{
	/**
	 * Load JS & CSS files on First Login page.
	 * Redefine ajaxLink() & ajaxForm(): no AJAX.
	 *
	 * @since 5.3
	 *
	 * @return string JS & CSS HTML code.
	 */
	function FirstLoginLoadJSCSS()
	{
		ob_start();

		$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

		// Load JS & CSS.
		// Redefine ajaxLink() & ajaxForm(): no AJAX.
		?>
		<script>
			var ajaxLink = function(link) {
				return true;
			}

			var ajaxPostForm = function(form, submit) {
				return true;
			}
		</script>
		<?php

		return ob_get_clean();
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

		<form action="index.php?modfunc=first-login" method="POST" id="first-login-form">
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


if ( ! function_exists( 'FirstLoginFormForcePasswordChange' ) )
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

		<form action="index.php?modfunc=first-login" method="POST" id="first-login-form">
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
	 *
	 * @param string $mode force_password_change|after_install.
	 *
	 * @return array Fields HTML array.
	 */
	function FirstLoginFormFields( $mode = 'force_password_change' )
	{
		global $_ROSARIO;

		$fields = array();

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
	 *
	 * @return array Poll HTML array.
	 */
	function FirstLoginPoll()
	{
		global $locale,
			$_ROSARIO;

		if ( User( 'STAFF_ID' ) !== '1' )
		{
			return false;
		}

		// Check if client has Internet connection.
		$has_connection = @file_get_contents( 'https://www.rosariosis.org/installation-poll/poll-submit.php' );

		if ( ! $has_connection )
		{
			// Server may be down?
			return false;
		}

		$fields = array();

		$fields[] = '<input type="hidden" name="locale" value="' . $locale . '" />';

		$fields[] = '<input type="hidden" name="version" value="' . ROSARIO_VERSION . '" />';

		$_ROSARIO['allow_edit'] = true;

		$usage_options = array(
			'testing' => _( 'Testing' ),
			'production' => _( 'Production' ),
		);

		$fields[] = RadioInput( '', 'usage', _( 'Usage' ), $usage_options, false );

		$school_options = array(
			'primary' => _( 'Primary' ),
			'secondary' => _( 'Secondary' ),
			'superior' => _( 'Superior' ),
			'other' => _( 'Other' ),
		);

		$fields[] = RadioInput( '', 'school', _( 'School' ), $school_options, false );

		$organization_options = array(
			'private' => _( 'Private' ),
			'public' => _( 'Public' ),
			'non-profit' => _( 'Non-profit' ),
		);

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
			'https://www.rosariosis.org/' . $url_lang . 'installation-poll/'
		);

		ob_start(); ?>
		<script>$('#first-login-form').hide();

		$('#first-login-poll-form input[type="reset"]').click(function(){
			console.log('ici');
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

		$form = '<form action="https://www.rosariosis.org/installation-poll/poll-submit.php" method="POST" id="first-login-poll-form">';

		$title = '<legend>' . _( 'Installation Poll' ) . '</legend>';

		return $form . '<fieldset>' . $title . implode( '</p><p>', $fields ) . '</fieldset></form>' . $js;
	}
}
