<?php

//FJ add SendEmail function

// $from: if empty, defaults to rosariosis@[yourserverdomain]
// $cc: Carbon Copy, comma separated list of emails
//returns true if email sent, or false

//example:
/*
	if( filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
	{
		//FJ add SendEmail function
		include('ProgramFunctions/SendEmail.fnc.php');
		
		$message = "System: ".ParseMLField(Config('TITLE'))." \n";
		$message .= "Date: ".date("m/d/Y h:i:s")."\n";
		$message .= "Page: ".$_SERVER['PHP_SELF'].' '.ProgramTitle()." \n\n";
		$message .= "Failure Notice:  $failnote \n";
		$message .= "Additional Info: $additional \n";
		$message .= "\n $sql \n";
		$message .= "Request Array: \n".print_r($_REQUEST, true);
		$message .= "\n\nSession Array: \n".print_r($_SESSION, true);
		
		SendEmail($RosarioNotifyAddress,'Database Error',$message);
	}
*/

function SendEmail($to, $subject, $message, $from = null, $cc = null)
{	
	//FJ add email headers
	if (empty($from))
	{
		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from = 'rosariosis@' . $sitename;
	}

	$headers = 'From:'. $from ."\r\n";
	if (!empty($cc))
		$headers .= "Cc:". $cc ."\r\n";
	$headers .= 'Return-Path:'. $from ."\r\n"; 
	$headers .= 'Reply-To:'. $from ."\r\n". 'X-Mailer: PHP/' . phpversion() ."\r\n";
	$headers .= 'Content-Type: text/plain; charset=UTF-8';
	//The f flag generates a Warning:
	//X-Authentication-Warning: [host]: www-data set sender to [from_email] using -f
	//$params = '-f '.$from;

	//append Program Name to subject
	$subject = Config('NAME').' - '.$subject;

	return @mail($to,$subject,$message,$headers);	
}

?>
