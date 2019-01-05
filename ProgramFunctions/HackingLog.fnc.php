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
 * @since 4.1 Redirect automatically to Portal after 5 seconds.
 * @since 4.3 Reload menu now so it does not contain links to disallowed programs.
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

	?>
	<script>
		// Reload menu now so it does not contain links to disallowed programs.
		ajaxLink( 'Side.php' );

		// Redirect automatically to Portal after 5 seconds.
		setTimeout( function(){
			window.location.href = <?php echo json_encode( $portal_url ); ?>;
		}, 5000);
	</script>
	<?php

	// Use link target="_top" so we reload side menu.
	$error[] = _( 'You\'re not allowed to use this program!' ) . ' ' .
	_( 'This attempted violation has been logged and your IP address was captured.' ) . ' ' .
	'<a href="' . $portal_url . '" target="_top"><b>« ' . _( 'Back' ) . '</b></a>';

	ErrorSendEmail( $error, 'HACKING ATTEMPT' );

	// Display fatal error message.

	return ErrorMessage( $error, 'fatal' );
}
