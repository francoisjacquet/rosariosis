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
		DBQuery( "UPDATE CONFIG
			SET CONFIG_VALUE='Yes'
			WHERE TITLE='LOGIN'" );

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
	?>
	<form action="index.php" method="POST">

	<?php PopTable( 'header', _( 'Confirm Successful Installation' ) ); ?>

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

	<?php PopTable( 'footer' ); ?>

	</form>
	<?php

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

		if ( User( 'STAFF_ID' ) === '1' )
		{
			// Set admin password on first login.
			$fields[] = '<input type="text" name="first_login[ADMIN_PASSWORD]" id="first_login_ADMIN_PASSWORD"
				size="25" maxlength="42" minlength="5" tabindex="1" required />' .
				FormatInputTitle( _( 'New Password' ), 'first_login_ADMIN_PASSWORD', true );
		}

		$fields[] = sprintf(
			_( 'Check the %s page to spot remaining configuration problems.' ),
			'<a href="diagnostic.php" target="_blank">diagnostic.php</a>'
		);

		return $fields;
	}
}
