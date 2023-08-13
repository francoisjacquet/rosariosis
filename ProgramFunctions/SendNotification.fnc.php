<?php
/**
 * Send Notification functions.
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Send Create Student Account notification
 *
 * @since 5.9
 * @since 6.7 Better check if Student account activated.
 *
 * @param int    $student_id Student ID.
 * @param string $to         To email address. Defaults to $RosarioNotifyAddress (see config.inc.php).
 *
 * @return bool  False if email not sent, else true.
 */
function SendNotificationCreateStudentAccount( $student_id, $to = '' )
{
	global $RosarioNotifyAddress;

	require_once 'ProgramFunctions/SendEmail.fnc.php';

	if ( empty( $to ) )
	{
		$to = $RosarioNotifyAddress;
	}

	if ( ! $student_id
		|| ! filter_var( $to, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$student_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
		FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	$message = _( 'New student account was created for %s (%d) (inactive).' );

	// Student was Inactive and is enrolled as of today, in Default School Year: Account Activation.
	$student_account_activated = DBGetOne( "SELECT 1
		FROM student_enrollment
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . Config( 'SYEAR' ) . "'
		AND START_DATE IS NOT NULL
		AND CURRENT_DATE>=START_DATE
		AND (CURRENT_DATE<=END_DATE OR END_DATE IS NULL)" );

	if ( $student_account_activated )
	{
		// @since 5.9 Automatic Student Account Activation.
		$message = _( 'New student account was activated for %s (%d).' );
	}

	$message = sprintf( $message, $student_name, $student_id );

	return SendEmail( $to, _( 'Create Student Account' ), $message );
}

/**
 * Send Create User Account notification
 *
 * @since 5.9
 *
 * @param int    $staff_id Staff ID.
 * @param string $to       To email address. Defaults to $RosarioNotifyAddress (see config.inc.php).
 *
 * @return bool  False if email not sent, else true.
 */
function SendNotificationCreateUserAccount( $staff_id, $to = '' )
{
	global $RosarioNotifyAddress;

	require_once 'ProgramFunctions/SendEmail.fnc.php';

	if ( empty( $to ) )
	{
		$to = $RosarioNotifyAddress;
	}

	if ( ! $staff_id
		|| ! filter_var( $to, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$user_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
		FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'" );

	$message = sprintf(
		_( 'New user account was created for %s (%d) (No Access).' ),
		$user_name,
		UserStaffID()
	);

	return SendEmail( $to, _( 'Create User Account' ), $message );
}

/**
 * Send New Administrator notification
 *
 * @since 5.9
 *
 * @param int    $staff_id Staff ID.
 * @param string $to       To email address. Defaults to $RosarioNotifyAddress (see config.inc.php).
 *
 * @return bool  False if not admin, email not sent, else true.
 */
function SendNotificationNewAdministrator( $staff_id, $to = '' )
{
	global $RosarioNotifyAddress;

	require_once 'ProgramFunctions/SendEmail.fnc.php';

	if ( empty( $to ) )
	{
		$to = $RosarioNotifyAddress;
	}

	if ( ! $staff_id
		|| ! filter_var( $to, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$is_admin_profile = DBGetOne( "SELECT 1 FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'
		AND PROFILE='admin'" );

	if ( ! $is_admin_profile )
	{
		return false;
	}

	$admin_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
		FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'" );

	$message = sprintf(
		_( 'New Administrator account was created for %s, by %s.' ),
		$admin_name,
		User( 'NAME' )
	);

	return SendEmail( $to, _( 'New Administrator Account' ), $message );
}

/**
 * Send Activate Student Account notification
 * Do not send notification if password not set.
 * Do not send notification if RosarioSIS installed on localhost (Windows typically).
 *
 * @since 5.9
 *
 * @uses _rosarioLoginURL() function
 *
 * @param int    $student_id Student ID.
 * @param string $to         To email address. Defaults to student email (see Config( 'STUDENTS_EMAIL_FIELD' )).
 *
 * @return bool  False if email not sent, else true.
 */
function SendNotificationActivateStudentAccount( $student_id, $to = '' )
{
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	if ( empty( $to ) )
	{
		if ( ! Config( 'STUDENTS_EMAIL_FIELD' ) )
		{
			return false;
		}

		// @since 5.9 Send Account Activation email notification to Student.
		$student_email_field = Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
			'USERNAME' : 'CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );

		$to = DBGetOne( "SELECT " . $student_email_field . " FROM students
			WHERE STUDENT_ID='" . (int) $student_id . "'" );
	}

	if ( ! $student_id
		|| ! filter_var( $to, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$is_password_set = DBGetOne( "SELECT 1 FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND PASSWORD IS NOT NULL" );

	if ( ! $is_password_set )
	{
		return false;
	}

	$rosario_url = RosarioURL();

	if ( ( strpos( $rosario_url, '127.0.0.1' ) !== false
			|| strpos( $rosario_url, 'localhost' ) !== false )
		&& ! ROSARIO_DEBUG )
	{
		// Do not send notification if RosarioSIS installed on localhost (Windows typically).
		return false;
	}

	$message = _( 'Your account was activated (%d). You can login at %s' );

	$student_username = DBGetOne( "SELECT USERNAME
		FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	$message .= "\n\n" . _( 'Username' ) . ': ' . $student_username;

	$message = sprintf( $message, $student_id, $rosario_url );

	return SendEmail( $to, _( 'Create Student Account' ), $message );
}

/**
 * Send Activate User Account notification
 * Do not send notification if password not set or "No Access" profile.
 * Do not send notification if RosarioSIS installed on localhost (Windows typically).
 *
 * @since 5.9
 *
 * @uses _rosarioLoginURL() function
 *
 * @param int    $staff_id User ID.
 * @param string $to       To email address. Defaults to user email.
 *
 * @return bool  False if email not sent, else true.
 */
function SendNotificationActivateUserAccount( $staff_id, $to = '' )
{
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	if ( empty( $to ) )
	{
		$to = DBGetOne( "SELECT EMAIL FROM staff
			WHERE STAFF_ID='" . (int) $staff_id . "'" );
	}

	if ( ! $staff_id
		|| ! filter_var( $to, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$is_no_access_profile = DBGetOne( "SELECT 1 FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'
		AND PROFILE='none'" );

	$is_password_set = DBGetOne( "SELECT 1 FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'
		AND PASSWORD IS NOT NULL" );

	if ( $is_no_access_profile
		|| ! $is_password_set )
	{
		return false;
	}

	$rosario_url = RosarioURL();

	if ( ( strpos( $rosario_url, '127.0.0.1' ) !== false
			|| strpos( $rosario_url, 'localhost' ) !== false )
		&& ! ROSARIO_DEBUG )
	{
		// Do not send notification if RosarioSIS installed on localhost (Windows typically).
		return false;
	}

	$message = _( 'Your account was activated (%d). You can login at %s' );

	$staff_username = DBGetOne( "SELECT USERNAME
		FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'" );

	$message .= "\n\n" . _( 'Username' ) . ': ' . $staff_username;

	$message = sprintf( $message, $staff_id, $rosario_url );

	return SendEmail( $to, _( 'Create User Account' ), $message );
}

/**
 * Send New Student Account notification
 * Do not send notification if password not set.
 * Send notification even if RosarioSIS installed on localhost (Windows typically)
 * because action should originate in user choice (checkbox checked).
 *
 * @since 6.1
 *
 * @uses _rosarioLoginURL() function
 *
 * @param int    $student_id Student ID.
 * @param string $to         To email address. Defaults to student email (see Config( 'STUDENTS_EMAIL_FIELD' )).
 * @param string $password   Plain password.
 *
 * @return bool  False if email not sent, else true.
 */
function SendNotificationNewStudentAccount( $student_id, $to = '', $password = '' )
{
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	if ( empty( $to ) )
	{
		if ( ! Config( 'STUDENTS_EMAIL_FIELD' ) )
		{
			return false;
		}

		$student_email_field = Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
			'USERNAME' : 'CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );

		$to = DBGetOne( "SELECT " . $student_email_field . " FROM students
			WHERE STUDENT_ID='" . (int) $student_id . "'" );
	}

	if ( ! $student_id
		|| ! filter_var( $to, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$is_password_set = DBGetOne( "SELECT 1 FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND PASSWORD IS NOT NULL" );

	if ( ! $is_password_set )
	{
		return false;
	}

	$rosario_url = RosarioURL();

	$message = _( 'Your account was activated (%d). You can login at %s' );

	$student_username = DBGetOne( "SELECT USERNAME
		FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	$message .= "\n\n" . _( 'Username' ) . ': ' . $student_username;

	if ( $password )
	{
		$message .= "\n" . _( 'Password' ) . ': ' . $password;
	}

	$message = sprintf( $message, $student_id, $rosario_url );

	return SendEmail( $to, _( 'Student Account' ), $message );
}

/**
 * Send New User Account notification
 * Do not send notification if password not set or "No Access" profile.
 * Send notification even if RosarioSIS installed on localhost (Windows typically)
 * because action should originate in user choice (checkbox checked).
 *
 * @since 6.1
 *
 * @uses _rosarioLoginURL() function
 *
 * @param int    $staff_id User ID.
 * @param string $to       To email address. Defaults to user email.
 * @param string $password Plain password.
 *
 * @return bool  False if email not sent, else true.
 */
function SendNotificationNewUserAccount( $staff_id, $to = '', $password = '' )
{
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	if ( empty( $to ) )
	{
		$to = DBGetOne( "SELECT EMAIL FROM staff
			WHERE STAFF_ID='" . (int) $staff_id . "'" );
	}

	if ( ! $staff_id
		|| ! filter_var( $to, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$is_no_access_profile = DBGetOne( "SELECT 1 FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'
		AND PROFILE='none'" );

	$is_password_set = DBGetOne( "SELECT 1 FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'
		AND PASSWORD IS NOT NULL" );

	if ( $is_no_access_profile
		|| ! $is_password_set )
	{
		return false;
	}

	$rosario_url = RosarioURL();

	$message = _( 'Your account was activated (%d). You can login at %s' );

	$staff_username = DBGetOne( "SELECT USERNAME
		FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "'" );

	$message .= "\n\n" . _( 'Username' ) . ': ' . $staff_username;

	if ( $password )
	{
		$message .= "\n" . _( 'Password' ) . ': ' . $password;
	}

	$message = sprintf( $message, $staff_id, $rosario_url );

	return SendEmail( $to, _( 'User Account' ), $message );
}

/**
 * RosarioSIS login page URL
 * Removes part beginning with 'Modules.php' or 'index.php' from URI.
 *
 * Local function
 *
 * @since 5.9
 * @deprecated since 11.2 Use RosarioURL() instead of local function
 *
 * @return string Login page URL.
 */
function _rosarioLoginURL()
{
	return RosarioURL();
}
