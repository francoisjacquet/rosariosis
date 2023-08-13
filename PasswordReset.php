<?php
/**
 * Password Reset
 *
 * @since 2.9
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

/**
 * Send email with password reset link.
 * Activate debug mode for error messages.
 */
if ( isset( $_POST['email'] )
	&& ! empty( $_REQUEST['email'] ) )
{
	if ( ! filter_var( $_REQUEST['email'], FILTER_VALIDATE_EMAIL ) )
	{
		if ( ROSARIO_DEBUG )
		{
			$error[] = 'Invalid email.';
		}
	}
	else
	{
		// SQL Handle case when multiple users have same email: order by Failed Login.
		$user_RET = DBGet( "SELECT STAFF_ID AS ID,EMAIL,USERNAME,'staff' AS USER_TYPE
			FROM staff
			WHERE LOWER(EMAIL)=LOWER('" . $_REQUEST['email'] . "')
			AND SYEAR='" . Config( 'SYEAR' ) . "'
			ORDER BY FAILED_LOGIN IS NULL,FAILED_LOGIN DESC
			LIMIT 1" );

		if ( ! $user_RET
			&& Config( 'STUDENTS_EMAIL_FIELD' ) )
		{
			// Check & rebuild custom student email field.
			$custom_field = false;

			$cust_field_tmp = Config( 'STUDENTS_EMAIL_FIELD' );

			if ( $cust_field_tmp === 'USERNAME' )
			{
				$custom_field = 'USERNAME';
			}
			elseif ( (string) (int) $cust_field_tmp === $cust_field_tmp )
			{
				$custom_field = 'custom_' . $cust_field_tmp;
			}

			if ( $custom_field )
			{
				// SQL Handle case when multiple users have same email: order by Failed Login.
				$user_RET = DBGet( "SELECT s.STUDENT_ID AS ID,s.USERNAME,'student' AS USER_TYPE,
					s." . $custom_field . " AS EMAIL
					FROM students s,student_enrollment ssm
					WHERE LOWER(s." . $custom_field . ")=LOWER('" . $_REQUEST['email'] . "')
					AND s.STUDENT_ID=ssm.STUDENT_ID
					AND ssm.SYEAR='" . Config( 'SYEAR' ) . "'
					AND ('" . DBDate() . "'>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL
						OR '" . DBDate() . "'<=ssm.END_DATE ) )
					ORDER BY s.FAILED_LOGIN IS NULL,s.FAILED_LOGIN DESC
					LIMIT 1" );
			}
		}

		if ( ! $user_RET )
		{
			if ( ROSARIO_DEBUG )
			{
				$error[] = 'No account with this email were found.';
			}
		}
		elseif ( ! $user_RET[1]['USERNAME'] )
		{
			if ( ROSARIO_DEBUG )
			{
				$error[] = 'No username with this account were found.';
			}
		}
		else
		{
			$email_sent = _sendPasswordResetEmail(
				$user_RET[1]['ID'],
				$user_RET[1]['USER_TYPE'],
				$user_RET[1]['EMAIL']
			);

			if ( ! $email_sent )
			{
				if ( ROSARIO_DEBUG )
				{
					$error[] = 'Password reset email could not be sent.';
				}
			}
			else
			{
				if ( ROSARIO_DEBUG )
				{
					$note[] = 'Success!';
				}
			}
		}
	}

	if ( ! ROSARIO_DEBUG )
	{
		// Redirect to login page.
		header( 'Location: index.php?modfunc=logout&reason=password_reset&token=' . $_SESSION['token'] );

		exit;
	}
}

// Password reset form.
if ( isset( $_REQUEST['h'] )
	&& ! empty( $_REQUEST['h'] )
	&& ( mb_strlen( $_REQUEST['h'] ) == 106
		|| mb_strlen( $_REQUEST['h'] ) == 105 )
	&& mb_substr( $_REQUEST['h'], 0, 3 ) == '$6$' )
{
	// Select Staff where last login > now.
	$staff_RET = DBGet( "SELECT STAFF_ID AS ID, USERNAME, PASSWORD, EMAIL,
		" . DisplayNameSQL() . " AS FULL_NAME, LAST_LOGIN, PROFILE_ID
		FROM staff
		WHERE LAST_LOGIN > CURRENT_TIMESTAMP
		AND SYEAR='" . Config( 'SYEAR' ) . "'" );

	$student_RET = [];

	if ( Config( 'STUDENTS_EMAIL_FIELD' ) )
	{
		// Check & rebuild custom student email field.
		$custom_field = false;

		$cust_field_tmp = Config( 'STUDENTS_EMAIL_FIELD' );

		if ( $cust_field_tmp === 'USERNAME' )
		{
			$custom_field = 'USERNAME';
		}
		elseif ( (string) (int) $cust_field_tmp === $cust_field_tmp )
		{
			$custom_field = 'custom_' . $cust_field_tmp;
		}

		if ( $custom_field )
		{
			// Select Students where last login > now & enrolled.
			$student_RET = DBGet( "SELECT s.STUDENT_ID AS ID, s.USERNAME, s.PASSWORD,
				s." . $custom_field . " AS EMAIL,
				" . DisplayNameSQL( 's' ) . " AS FULL_NAME, s.LAST_LOGIN
				FROM students s, student_enrollment se
				WHERE s.LAST_LOGIN > CURRENT_TIMESTAMP
				AND se.SYEAR='" . Config( 'SYEAR' ) . "'
				AND se.STUDENT_ID=s.STUDENT_ID
				AND ('" . DBDate() . "'>=se.START_DATE
					AND ('" . DBDate() . "'<=se.END_DATE
						OR se.END_DATE IS NULL ) )" );
		}
	}

	if ( ! $staff_RET
		&& ! $student_RET )
	{
		$error[] = _( 'Please enter your email again.' );
	}
	else
	{
		$hash_matched = false;

		foreach ( (array) $staff_RET as $staff )
		{
			// Generate plain hash from user ID, username, name, password, email & last login.
			$plain_hash = $staff['ID'] . $staff['USERNAME'] . $staff['FULL_NAME'] .
				$staff['PASSWORD'] . $staff['EMAIL'] . $staff['LAST_LOGIN'];

			if ( match_password( $_REQUEST['h'], $plain_hash ) )
			{
				$hash_matched = true;

				$user_id = $staff['ID'];

				$user_type = 'staff';

				$user_profile = $staff['PROFILE_ID'];

				// @since 11.1 Prevent using App name, username, or email in the password
				$_ROSARIO['PasswordInput']['user_inputs'] = [
					$staff['USERNAME'],
					$staff['EMAIL'],
				];

				break;
			}
		}

		foreach ( (array) $student_RET as $student )
		{
			// Generate plain hash from user ID, username, name, password, email & last login.
			$plain_hash = $student['ID'] . $student['USERNAME'] . $student['FULL_NAME'] .
				$student['PASSWORD'] . $student['EMAIL'] . $student['LAST_LOGIN'];

			if ( match_password( $_REQUEST['h'], $plain_hash ) )
			{
				$hash_matched = true;

				$user_id = $student['ID'];

				$user_type = 'student';

				// @since 11.1 Prevent using App name, username, or email in the password
				$_ROSARIO['PasswordInput']['user_inputs'] = [
					$student['USERNAME'],
					$student['EMAIL'],
				];

				break;
			}
		}

		if ( $hash_matched )
		{
			// Verify new password if any.
			if ( isset( $_POST['PASSWORD'] )
				&& $_REQUEST['PASSWORD'] !== '' )
			{
				$new_password = $_POST['PASSWORD'];

				if ( $user_type === 'staff' )
				{
					// Update password.
					DBQuery( "UPDATE staff SET PASSWORD='" .
						encrypt_password( $new_password ) . "'
						WHERE STAFF_ID='" . (int) $user_id . "'
						AND SYEAR='" . Config( 'SYEAR' ) . "'" );

					// If admin, send notification email to server admin.
					if ( $user_profile == 1 )
					{
						_notifyServerAdminPasswordReset( $user_id );
					}
				}
				elseif ( $user_type === 'student' )
				{
					// Update password.
					DBQuery( "UPDATE students SET PASSWORD='" .
						encrypt_password( $new_password ) . "'
						WHERE STUDENT_ID='" . (int) $user_id . "'" );
				}

				unset(
					$_POST['PASSWORD'],
					$_REQUEST['PASSWORD']
				);

				// Redirect to login page.
				header( 'Location: index.php' );

				exit;
			}

			_passwordResetForm( $_REQUEST['h'], $user_id );

			exit;
		}
		else
		{
			$error[] = _( 'Please enter your email again.' );
		}
	}
}

// If Student email field config option not set,
// notify that no student can use the password reset.
if ( ! Config( 'STUDENTS_EMAIL_FIELD' ) )
{
	$note[] = _( 'Password reset is not activated for students.' );
}

// Forgot your password? form.
_printPageHead( _( 'Forgot your password?' ) );

?>
<form action="PasswordReset.php" method="POST" target="_top">

	<?php PopTable( 'header', _( 'Forgot your password?' ) ); ?>

		<label><input type="email" name="email" id="email" size="25" maxlength="255" tabindex="1" required autofocus />
		<br />
		<?php echo _( 'Email' ); ?></label>
		<br />
		<br />
		<?php echo Buttons( _( 'Send password reset instructions' ) ); ?>

	<?php PopTable( 'footer' ); ?>

</form>
<?php

Warehouse( 'footer' );


/**
 * Send Password Reset email
 *
 * @param  string $user_id   User ID (Staff or Student)
 * @param  string $user_type 'staff'|'student'
 *
 * @return boolean           true if email sent, else false
 */
function _sendPasswordResetEmail( $user_id, $user_type = 'staff', $email )
{
	global $DatabaseType;

	if ( ! $user_id )
	{
		return false;
	}

	if ( $user_type === 'staff' )
	{
		// Get Staff email, password.
		$staff_RET = DBGet( "SELECT USERNAME, PASSWORD,
			" . DisplayNameSQL() . " AS FULL_NAME
			FROM staff
			WHERE STAFF_ID='" . (int) $user_id . "'
			AND SYEAR='" . Config( 'SYEAR' ) . "'" );

		$username = $staff_RET[1]['USERNAME'];

		$name = $staff_RET[1]['FULL_NAME'];

		$password = $staff_RET[1]['PASSWORD']; // Can be NULL!
	}
	elseif ( $user_type === 'student' )
	{
		// Get Student username, password, name.
		$student_RET = DBGet( "SELECT USERNAME,PASSWORD,
			" . DisplayNameSQL( 's' ) . " AS FULL_NAME
			FROM students s,student_enrollment ssm
			WHERE s.STUDENT_ID='" . (int) $user_id . "'
			AND s.STUDENT_ID=ssm.STUDENT_ID
			AND ssm.SYEAR='" . Config( 'SYEAR' ) . "'
			AND ('" . DBDate() . "'>=ssm.START_DATE
			AND (ssm.END_DATE IS NULL
				OR '" . DBDate() . "'<=ssm.END_DATE ) )" );

		$username = $student_RET[1]['USERNAME'];

		$name = $student_RET[1]['FULL_NAME'];

		$password = $student_RET[1]['PASSWORD']; // Can be NULL!
	}

	if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL )
		|| ! $username )
	{
		return false;
	}

	// Last login = now + 2 hours.
	$last_login = DBGetOne( "SELECT
		CAST(CURRENT_TIMESTAMP + INTERVAL " . ( $DatabaseType === 'mysql' ? '2 hour' : "'2 hour'" ) . " AS char(19)) AS LAST_LOGIN" );

	// Generate hash from user ID, username, name, password, email & last login.
	$hash = encrypt_password( $user_id . $username . $name . $password . $email . $last_login );

	// Generate link.
	$link = RosarioURL( 'script' ) . '?h=' . $hash;

	// Send email.
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	$message = _( 'Please visit the following link to reset your password' ) . ':<br />
		<a href="' . URLEscape( $link ) . '">' . $link . '</a>
		<br />' . _( 'Username' ) . ': ' . $username . '
		<br /><br />' .
		_( 'Please permanently delete this email once you are done.' );

	$email_sent = SendEmail( $email, _( 'Password Reset' ), $message );

	if ( ! $email_sent )
	{
		return false;
	}

	if ( $user_type === 'staff' )
	{
		// Update Last login = now + 2 hours.
		DBQuery( "UPDATE staff
			SET LAST_LOGIN='" . $last_login . "'
			WHERE STAFF_ID='" . (int) $user_id . "'" ); // CURRENT_TIMESTAMP + interval '2 hour'.
	}
	elseif ( $user_type === 'student' )
	{
		// Update Last login = now + 2 hours.
		DBQuery( "UPDATE students
			SET LAST_LOGIN='" . $last_login . "'
			WHERE STUDENT_ID='" . (int) $user_id . "'" ); // CURRENT_TIMESTAMP + interval '2 hour'.
	}

	return true;
}


function _passwordResetForm( $hash, $user_id )
{
	global $_ROSARIO;

	if ( ! $hash
		|| ! $user_id )
	{
		return;
	}

	_printPageHead( _( 'Reset your password' ) );

	?>
	<form action="PasswordReset.php" method="POST" target="_top">

		<input type="hidden" name="h" value="<?php echo AttrEscape( $hash ); ?>" />

		<?php PopTable( 'header', _( 'Reset your password' ) ); ?>

			<?php
			$_ROSARIO['allow_edit'] = true;

			echo PasswordInput(
				'',
				'PASSWORD',
				_( 'New Password' ),
				'strength maxlength="42" tabindex="1" required'
			);

			$_ROSARIO['allow_edit'] = false;
			?>

			<br />
			<div class="center"><?php echo Buttons( _( 'Submit' ) ); ?></div>

		<?php PopTable( 'footer' ); ?>

	</form>
	<?php
	Warehouse( 'footer' );
}


function _printPageHead( $title )
{
	global $locale,
		$error,
		$note,
		$_ROSARIO;

	$_ROSARIO['page'] = 'password-reset';

	Warehouse( 'header' );

	$_ROSARIO['HeaderIcon'] = 'misc';

	DrawHeader( _( 'Password help' ) );

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );
}


function _notifyServerAdminPasswordReset( $user_id )
{
	global $RosarioNotifyAddress;

	// Notify the network admin that a new admin has been created.
	if ( ! filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL )
		|| ! $user_id )
	{
		return false;
	}

	$staff_RET = DBGet( "SELECT USERNAME," . DisplayNameSQL() . " AS FULL_NAME,PROFILE
		FROM staff
		WHERE STAFF_ID='" . (int) $user_id . "'
		AND SYEAR='" . Config( 'SYEAR' ) . "'" );

	// FJ add SendEmail function.
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	$to = $RosarioNotifyAddress;

	$name = $staff_RET[1]['FULL_NAME'];

	$subject = sprintf( 'Password Reset: %s', $name );

	$profile = $staff_RET[1]['PROFILE'];

	$username = $staff_RET[1]['USERNAME'];

	$ip = ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )
		// Filter IP, HTTP_* headers can be forged.
		&& filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ?
		$_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );

	$message = sprintf( 'Password Reset for: %s
Profile: %s
Remote IP: %s', $username, $profile, $ip );

	return SendEmail( $to, $subject, $message );
}
