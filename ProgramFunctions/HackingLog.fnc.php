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
 * @since 9.0 Logout after 10 Hacking attempts within 1 minute.
 */
function HackingLog()
{
	global $error;

	$redirect_url = 'Modules.php?modname=misc/Portal.php';

	if ( ! empty( $_SERVER['HTTP_REFERER'] )
		&& mb_strpos( $_SERVER['HTTP_REFERER'], '&redirect_to=' ) !== false
		&& ! headers_sent() )
	{
		// If User has just logged in, take him back to Portal without sending email!
		header( 'Location: ' . $redirect_url );

		exit;
	}

	// Log Hacking time in session.
	$_SESSION['HackingLog'][] = time();

	if ( count( $_SESSION['HackingLog'] ) >= 10 )
	{
		$one_minute_ago = time() - 60;

		$attempts_within_one_minute = 0;

		foreach ( $_SESSION['HackingLog'] as $i => $time )
		{
			if ( $time >= $one_minute_ago )
			{
				$attempts_within_one_minute++;

				continue;
			}

			unset( $_SESSION['HackingLog'][ $i ] );
		}

		if ( $attempts_within_one_minute >= 10 )
		{
			// Logout after 10 Hacking attempts within 1 minute.
			$redirect_url = 'index.php?modfunc=logout&token=' . $_SESSION['token'];
		}
	}

	$error[] = _( 'You\'re not allowed to use this program!' );

	ErrorSendEmail( $error, 'HACKING ATTEMPT' );

	if ( ! headers_sent() )
	{
		// Redirect automatically to Portal or Logout.
		header( 'Location: ' . $redirect_url );

		exit;
	}

	?>
	<script>
		// Redirect automatically to Portal or Logout.
		window.location.href = <?php echo json_encode( $redirect_url ); ?>;
	</script>
	<?php

	exit;
}
