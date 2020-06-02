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
 * @since 6.4.1 Only send email and redirect to Portal without displaying error.
 */
function HackingLog()
{
	global $error;

	$portal_url = 'Modules.php?modname=misc/Portal.php';

	if ( ! empty( $_SERVER['HTTP_REFERER'] )
		&& mb_strpos( $_SERVER['HTTP_REFERER'], '&redirect_to=' ) !== false
		&& ! headers_sent() )
	{
		// If User has just logged in, take him back to Portal without sending email!
		header( 'Location: ' . $portal_url );

		exit;
	}

	?>
	<script>
		// Redirect automatically to Portal.
		window.location.href = <?php echo json_encode( $portal_url ); ?>;
	</script>
	<?php

	// Use link target="_top" so we reload side menu.
	$error[] = _( 'You\'re not allowed to use this program!' );

	ErrorSendEmail( $error, 'HACKING ATTEMPT' );

	exit;
}
