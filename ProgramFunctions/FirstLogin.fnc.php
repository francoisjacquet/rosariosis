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

		if ( ! empty( $values['ADMIN_PASSWORD'] )
			&& User( 'STAFF_ID' ) === '1' )
		{
			// Admin password set.
			$new_password = encrypt_password( $values['ADMIN_PASSWORD'] );

			DBQuery( "UPDATE STAFF
				SET PASSWORD='" . $new_password . "'
				WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
				AND SYEAR='" . UserSyear() . "'" );

			unset( $values['ADMIN_PASSWORD'], $new_password );

			$note[] = _( 'Your new password was saved.' );

			$return = true;
		}

		// Set Config( 'LOGIN' ) to Yes.
		Config( 'LOGIN', 'Yes' );

		return $return;
	}
}

/**
 * First Login Form
 *
 * @since 4.0
 *
 * Seen by admin on first login after installation.
 *
 * @return string Form HTML.
 */
function FirstLoginForm()
{
	ob_start();

	PopTable( 'header', _( 'Confirm Successful Installation' ) ); ?>

	<form action="index.php" method="POST" id="first-login-form">
		<h4 class="center">
			<?php
				echo sprintf(
					_( 'You have successfully installed %s.' ),
					ParseMLField( Config( 'TITLE' ) )
				);
			?>
		</h4>
		<p><?php echo implode( '</p><p>', FirstLoginFormFields() ); ?></p>
		<p class="center"><?php echo Buttons( _( 'OK' ) ); ?></p>
	</form>
	<?php echo FirstLoginPoll(); ?>

	<?php PopTable( 'footer' );

	return ob_get_clean();
}


if ( ! function_exists( 'FirstLoginFormFields' ) )
{
	/**
	 * Get First Login Form Fields
	 *
	 * @since 4.0
	 *
	 * @return array Fields HTML array.
	 */
	function FirstLoginFormFields()
	{
		$fields = array();

		$fields[] = sprintf(
			_( 'Check the %s page to spot remaining configuration problems.' ),
			'<a href="diagnostic.php" target="_blank">diagnostic.php</a>'
		);

		if ( User( 'STAFF_ID' ) === '1' )
		{
			// Set admin password on first login.
			$fields[] = '<br /><input type="text" name="first_login[ADMIN_PASSWORD]" id="first_login_ADMIN_PASSWORD"
				size="25" maxlength="42" minlength="5" tabindex="1" required />' .
				FormatInputTitle(
					'<span class="legend-red">' . _( 'New Password' ) . '</span>',
					'first_login_ADMIN_PASSWORD',
					true
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

		$fields[] = TextInput(
			'0',
			'students',
			_( 'Students' ),
			'type="number" min="0" max="100000" length="4"',
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
		<script src="assets/js/jquery.js"></script>
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
