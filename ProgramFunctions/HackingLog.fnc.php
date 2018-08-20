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
 * @since 4.0 Uses ErrorSendEmail() & "« Back" link to Portal or automatic redirection if has just logged in.
 *
 * @return string outputs error message and exit
 */
function HackingLog()
{
	global $error;

	$portal_url = 'Modules.php?modname=misc/Portal.php';

	if ( User( 'LAST_LOGIN' ) === date( 'Y-m-d G:i:s' )
		&& ! headers_sent() )
	{
		// If User has just logged in, take him back to Portal without displaying message!
		header( 'Location: ' . $portal_url );

		exit;
	}

	$error[] = _( 'You\'re not allowed to use this program!' ) . ' ' .
	_( 'This attempted violation has been logged and your IP address was captured.' ) . ' ' .
	'<a href="' . $portal_url . '"><b>« ' . _( 'Back' ) . '</b></a>';

	ErrorSendEmail( $error, 'HACKING ATTEMPT' );

	// Display fatal error message.

	return ErrorMessage( $error, 'fatal' );
}
