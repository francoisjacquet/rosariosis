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
		FROM STUDENTS
		WHERE STUDENT_ID='" . $student_id . "'" );

	$message = _( 'New student account was created for %s (%d) (inactive).' );

	if ( Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ) )
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
		FROM STAFF
		WHERE STAFF_ID='" . $staff_id . "'" );

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
 * @return bool  False if email not sent, else true.
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

	$admin_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
		FROM STAFF
		WHERE STAFF_ID='" . $staff_id . "'" );

	$message = sprintf(
		_( 'New Administrator account was created for %s, by %s.' ),
		$admin_name,
		User( 'NAME' )
	);

	return SendEmail( $to, _( 'New Administrator Account' ), $message );
}
