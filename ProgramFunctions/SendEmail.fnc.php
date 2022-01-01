<?php
/**
 * Send Email function.
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Send Email
 * And eventual Attachment(s)
 * From: RosarioSIS <rosariosis@yourdomain.com>
 *
 * @since 3.6.1 ProgramFunctions/SendEmail.fnc.php|before_send action hook.
 * @since 8.7 ProgramFunctions/SendEmail.fnc.php|send_error action hook.
 *
 * @example SendEmail( $to, $subject, $msg, 'Foo <bar@from.address>', $cc, array( array( $pdf_file, $pdf_name ) ) );
 *
 * @link https://www.mail-tester.com/
 *
 * @uses PHPMailer class
 * @global $phpmailer
 *
 * @param string|array $to          Recipients, array or comma separated list of emails.
 * @param string       $subject     Subject.
 * @param string       $message     Message.
 * @param string       $reply_to    Reply To email.
 * @param string|array $cc          Carbon Copy, array or comma separated list of emails.
 * @param array        $attachments Array of file paths, or Array of Attachments (file path, file name).
 *
 * @return boolean true if email sent, or false
 */
function SendEmail( $to, $subject, $message, $reply_to = null, $cc = null, $attachments = [] )
{
	global $phpmailer;

	if ( ! is_array( $attachments ) )
	{
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
	}

	// (Re)create it, if it's gone missing.
	if ( ! ( $phpmailer instanceof PHPMailer ) )
	{
		require_once 'classes/PHPMailer/class.phpmailer.php';

		require_once 'classes/PHPMailer/class.smtp.php';

		$phpmailer = new PHPMailer( true );
	}

	// Set to use PHP's mail().
	$phpmailer->isMail();

	// Empty out the values that may be set.
	$phpmailer->clearAllRecipients();
	$phpmailer->clearAttachments();
	$phpmailer->clearCustomHeaders();
	$phpmailer->clearReplyTos();

	// FJ add email headers.
	// Get the site domain and get rid of www.
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );

	if ( substr( $sitename, 0, 4 ) === 'www.' )
	{
		$sitename = substr( $sitename, 4 );
	}

	$programname = mb_strtolower( filter_var(
		Config( 'NAME' ),
		FILTER_SANITIZE_EMAIL
	));

	if ( ! $phpmailer->From
		|| $phpmailer->From === 'root@localhost' )
	{
		// Set Email address to send from: RosarioSIS <rosariosis@yourdomain.com>.
		$phpmailer->From = $programname . '@' . $sitename;
	}

	$phpmailer->FromName = Config( 'NAME' );

	// Set Reply To email if any (use instead of From to prevent spam!).
	if ( $reply_to )
	{
		try
		{
			$reply_to_name = '';

			// Break $reply_to into name and address parts if in the format "Foo <bar@baz.com>".
			if ( preg_match( '/(.*)<(.+)>/', $reply_to, $matches ) )
			{
				if ( count( $matches ) == 3 )
				{
					$reply_to_name = $matches[1];
					$reply_to = $matches[2];
				}
			}

			$phpmailer->addReplyTo( $reply_to, $reply_to_name );
		}
		catch ( phpmailerException $e )
		{
		}
	}

	// Set destination addresses.
	if ( ! is_array( $to ) )
	{
		$to = explode( ',', $to );
	}

	foreach ( (array) $to as $recipient )
	{
		try
		{
			// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
			$recipient_name = '';

			if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) )
			{
				if ( count( $matches ) == 3 )
				{
					$recipient_name = $matches[1];
					$recipient = $matches[2];
				}
			}

			$phpmailer->addAddress( $recipient, $recipient_name );
		}
		catch ( phpmailerException $e )
		{
			continue;
		}
	}

	// Append Program Name to subject.
	$subject = Config( 'NAME' ) . ' - ' . $subject;

	// Set mail's subject.
	$phpmailer->Subject = $subject;

	// Set Charset.
	$phpmailer->CharSet = 'UTF-8';

	// Set Content-Type and body.
	// Detect if HTML message.
	if ( mb_strlen( $message ) !== mb_strlen( strip_tags( $message ) ) )
	{
		// Send plain text message along with the HTML one!
		$phpmailer->msgHTML( $message );
	}
	else
	{
		$phpmailer->ContentType = 'text/plain';

		$phpmailer->Body = $message;
	}

	// Add any CC and BCC recipients.
	if ( $cc
		&& ! is_array( $cc ) )
	{
		$cc = explode( ',', $cc );
	}

	if ( ! empty( $cc ) )
	{
		foreach ( (array) $cc as $recipient )
		{
			try
			{
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
				$recipient_name = '';

				if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) )
				{
					if ( count( $matches ) == 3 )
					{
						$recipient_name = $matches[1];
						$recipient = $matches[2];
					}
				}

				$phpmailer->addCc( $recipient, $recipient_name );
			}
			catch ( phpmailerException $e )
			{
				continue;
			}
		}
	}

	if ( ! empty( $attachments ) )
	{
		foreach ( (array) $attachments as $attachment )
		{
			try
			{
				if ( is_array( $attachment ) )
				{
					$phpmailer->addAttachment( $attachment[0], $attachment[1] );
				}
				else
					$phpmailer->addAttachment( $attachment );
			}
			catch ( phpmailerException $e )
			{
				continue;
			}
		}
	}

	// Send!
	try
	{
		// Hook.
		do_action( 'ProgramFunctions/SendEmail.fnc.php|before_send' );

		return $phpmailer->send();
	}
	catch ( phpmailerException $e )
	{
		global $error;

		// Hook.
		do_action( 'ProgramFunctions/SendEmail.fnc.php|send_error', $e->errorMessage() );

		$error[] = $e->errorMessage();

		return false;
	}
}
