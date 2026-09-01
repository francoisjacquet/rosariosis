<?php
/**
 * Email functions class
 *
 * @since 13.0
 *
 * @package RosarioSIS
 */

namespace RosarioSIS\Functions;

class Email
{
	/** @var object Mailer object instance */
	static $mailer;

	/**
	 * Constructor
	 *
	 * @uses PHPMailer class
	 * @global $phpmailer
	 *
	 * @param object $mailer Mailer object instance. Optional.
	 */
	function __construct( $mailer = null )
	{
		// @deprecated since 13.0 Use RosarioSIS\Functions\Email::$mailer instead
		global $phpmailer;

		if ( $mailer )
		{
			// Dependency injection.
			self::$mailer = $phpmailer = $mailer;
		}
		// (Re)create it, if it's gone missing.
		elseif ( ! is_object( self::$mailer ) )
		{
			self::$mailer = $phpmailer = new \PHPMailer\PHPMailer\PHPMailer( true );
		}
	}

	/**
	 * Send Email
	 * And eventual Attachment(s)
	 * From: RosarioSIS <rosariosis@yourdomain.com>
	 *
	 * @since 13.0 RosarioSIS/Functions/Email.php|before_send action hook.
	 * @since 13.0 RosarioSIS/Functions/Email.php|send_error action hook.
	 *
	 * @example (new RosarioSIS\Functions\Email)->send( $to, $subject, $msg, 'replyto@domain', $cc, [ [ $pdf_file, $pdf_name ] ] );
	 *
	 * @link https://www.mail-tester.com/
	 *
	 * @global $error
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
	function send( $to, $subject, $message, $reply_to = null, $cc = null, $attachments = [] )
	{
		// Set to use PHP's mail().
		self::$mailer->isMail();

		// Empty out the values that may be set.
		self::$mailer->clearAllRecipients();
		self::$mailer->clearAttachments();
		self::$mailer->clearCustomHeaders();
		self::$mailer->clearReplyTos();

		/**
		 * Fix error Invalid address: (From) rosariosis@localhost
		 *
		 * @since 12.0 Use PCRE8 email address validator
		 * Uses the same RFC5322 regex on which FILTER_VALIDATE_EMAIL is based, but allows dotless domains.
		 */
		\PHPMailer\PHPMailer\PHPMailer::$validator = 'pcre8';

		$this->setFrom();

		// Set Reply To email if any (use instead of From to prevent spam!).
		$this->addReplyTo( $reply_to );

		// Set destination addresses.
		$this->addTo( $to );

		// Append Program Name to subject.
		$subject = Config( 'NAME' ) . ' - ' . $subject;

		// Set mail's subject.
		self::$mailer->Subject = $subject;

		// Set Charset.
		self::$mailer->CharSet = 'UTF-8';

		// Detect if HTML message.
		if ( mb_strlen( $message ) !== mb_strlen( strip_tags( $message ) ) )
		{
			// Send plain text message along with the HTML one!
			self::$mailer->msgHTML( $message );
		}
		else
		{
			// Set Content-Type and body.
			self::$mailer->ContentType = 'text/plain';
			self::$mailer->Body = $message;
		}

		// Add any CC and BCC recipients.
		$this->addCc( $cc );

		$this->addAttachments( $attachments );

		try
		{
			global $RosarioActions;

			// Hook.
			if ( ! empty( $RosarioActions['ProgramFunctions/SendEmail.fnc.php|before_send'] ) )
			{
				// @deprecated since 13.0
				do_action( 'ProgramFunctions/SendEmail.fnc.php|before_send' );

				trigger_error(
					'ProgramFunctions/SendEmail.fnc.php|before_send action hook is deprecated since RosarioSIS 13.0. Please use RosarioSIS/Functions/Email.php|before_send action hook instead.',
					E_USER_DEPRECATED
				);
			}

			do_action( 'RosarioSIS/Functions/Email.php|before_send' );

			return self::$mailer->send();
		}
		catch ( \Exception $e )
		{
			global $error;

			// Hook.
			if ( ! empty( $RosarioActions['ProgramFunctions/SendEmail.fnc.php|send_error'] ) )
			{
				// @deprecated since 13.0
				do_action( 'ProgramFunctions/SendEmail.fnc.php|send_error', $e->errorMessage() );

				trigger_error(
					'ProgramFunctions/SendEmail.fnc.php|send_error action hook is deprecated since RosarioSIS 13.0. Please use RosarioSIS/Functions/Email.php|send_error action hook instead.',
					E_USER_DEPRECATED
				);
			}

			do_action( 'RosarioSIS/Functions/Email.php|send_error', $e->errorMessage() );

			$error[] = $e->errorMessage();

			return false;
		}
	}

	/**
	 * Set From email address and name
	 * From: RosarioSIS <rosariosis@yourdomain.com>
	 *
	 * @return bool True if From set.
	 */
	protected function setFrom()
	{
		self::$mailer->FromName = Config( 'NAME' );

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

		if ( ! self::$mailer->From
			|| self::$mailer->From === 'root@localhost' )
		{
			// Set Email address to send from: RosarioSIS <rosariosis@yourdomain.com>.
			self::$mailer->From = $programname . '@' . $sitename;

			return true;
		}

		return false;
	}

	/**
	 * Add to Reply To email
	 *
	 * @param string $reply_to Reply To email.
	 *
	 * @return bool True if Reply To email added.
	 */
	protected function addReplyTo( $reply_to )
	{
		if ( ! $reply_to )
		{
			return false;
		}

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

			self::$mailer->addReplyTo( $reply_to, $reply_to_name );
		}
		catch ( \Exception $e )
		{
			return false;
		}

		return true;
	}

	/**
	 * Add to recipients
	 *
	 * @param string|array $to Recipients, array or comma separated list of emails.
	 *
	 * @return bool True if to recipients added.
	 */
	protected function addTo( $to )
	{
		if ( ! $to )
		{
			return false;
		}

		if ( ! is_array( $to ) )
		{
			$to = explode( ',', $to );
		}

		$return = false;

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

				self::$mailer->addAddress( $recipient, $recipient_name );
			}
			catch ( \Exception $e )
			{
				continue;
			}

			$return = true;
		}

		return $return;
	}

	/**
	 * Add Carbon Copy
	 *
	 * @param string|array $cc Carbon Copy, array or comma separated list of emails.
	 *
	 * @return bool True if Carbon Copy added.
	 */
	protected function addCc( $cc )
	{
		if ( ! $cc )
		{
			return false;
		}

		if ( ! is_array( $cc ) )
		{
			$cc = explode( ',', $cc );
		}

		$return = false;

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

				self::$mailer->addCc( $recipient, $recipient_name );
			}
			catch ( \Exception $e )
			{
				continue;
			}

			$return = true;
		}

		return $return;
	}

	/**
	 * Add attachments
	 *
	 * @param array $attachements Array of file paths, or Array of Attachments (file path, file name).
	 *
	 * @return bool True if attachments added.
	 */
	protected function addAttachments( $attachments )
	{
		if ( ! $attachments )
		{
			return false;
		}

		if ( ! is_array( $attachments ) )
		{
			$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
		}

		$return = false;

		foreach ( (array) $attachments as $attachment )
		{
			try
			{
				if ( is_array( $attachment ) )
				{
					self::$mailer->addAttachment( $attachment[0], $attachment[1] );
				}
				else
					self::$mailer->addAttachment( $attachment );
			}
			catch ( \Exception $e )
			{
				continue;
			}

			$return = true;
		}

		return $return;
	}
}
