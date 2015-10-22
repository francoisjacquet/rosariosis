<?php

/**
 * Log Hacking attempt
 * Send email to $RosarioNotifyAddress if set
 *
 * @global string $RosarioNotifyAddress config.inc.php set email
 *
 * @return string outputs error message and exit
 */
function HackingLog()
{
	global $RosarioNotifyAddress;
	
	// Send email to $RosarioNotifyAddress if set
	if ( filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
	{
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];

		//FJ add SendEmail function
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

	// display error message and exit
	return ErrorMessage( $error, 'fatal' );
}
