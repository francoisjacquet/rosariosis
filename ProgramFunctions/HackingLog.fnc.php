<?php
/**
 * Log Hacking attempt function
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Log Hacking attempt
 * Send email if `$RosarioNotifyAddress` or `$RosarioErrorsAddress` set
 *
 * @global string $RosarioNotifyAddress or $RosarioErrorsAddress email set in config.inc.php file
 * @since 4.0 Uses ErrorSendEmail()
 *
 * @return string outputs error message and exit
 */
function HackingLog()
{
	global $error;

	$error[] = _( 'You\'re not allowed to use this program!' ) . ' ' .
	_( 'This attempted violation has been logged and your IP address was captured.' );

	ErrorSendEmail( $error, 'HACKING ATTEMPT' );

	// Display fatal error message.

	return ErrorMessage( $error, 'fatal' );
}
