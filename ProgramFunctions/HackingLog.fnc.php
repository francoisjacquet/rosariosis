<?php
/**
 * Log Hacking attempt function
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Log Hacking attempt
 * Send email to `$RosarioNotifyAddress` if set
 *
 * @global string $RosarioNotifyAddress email set in config.inc.php file
 *
 * @return string outputs error message and exit
 */
function HackingLog()
{
	global $RosarioNotifyAddress;

	// Send email to $RosarioNotifyAddress if set {@see config.inc.php}.
	if ( filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
	{
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
			$ip = $_SERVER['REMOTE_ADDR'];

		// FJ add SendEmail function.
		require_once 'ProgramFunctions/SendEmail.fnc.php';

		$message = "INSERT INTO HACKING_LOG
			(
				HOST_NAME,
				IP_ADDRESS,
				LOGIN_DATE,
				VERSION,
				PHP_SELF,
				DOCUMENT_ROOT,
				SCRIPT_NAME,
				MODNAME,
				QUERY_STRING,
				USERNAME
			)
			values(
				'" . $_SERVER['SERVER_NAME'] . "',
				'" . $ip . "',
				'" . date( 'Y-m-d' ) . "',
				'" . ROSARIO_VERSION . "',
				'" . $_SERVER['PHP_SELF'] . "',
				'" . $_SERVER['DOCUMENT_ROOT'] . "',
				'" . $_SERVER['SCRIPT_NAME'] . "',
				'" . $_REQUEST['modname'] . "',
				'" . $_SERVER['QUERY_STRING'] . "',
				'" . User( 'USERNAME' ) . "'
			)";

		SendEmail( $RosarioNotifyAddress, 'HACKING ATTEMPT', $message );
	}

	$error[] = _( 'You\'re not allowed to use this program!' ) . ' ' .
		_( 'This attempted violation has been logged and your IP address was captured.' );

	// Display fatal error message.
	return ErrorMessage( $error, 'fatal' );
}
