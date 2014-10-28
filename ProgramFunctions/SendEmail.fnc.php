<?php

//modif Francois: add SendEmail function

// $from: if empty, defaults to $RosarioNotifyAddress
// $cc: Carbon Copy, comma separated list of emails
//returns true if email sent, or false

//example:
/*
	if($RosarioNotifyAddress)
	{
		//modif Francois: add SendEmail function
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
	global $RosarioNotifyAddress;
	
	//modif Francois: add email headers
	if (empty($from))
		$from = $RosarioNotifyAddress;
	
	$headers = 'From:'.$from."\r\n";
	if (!empty($cc))
		$headers .= "Cc:".$cc."\r\n";
	$headers .= 'Return-Path:'.$from."\r\n"; 
	$headers .= 'Reply-To:'.$from . "\r\n" . 'X-Mailer: PHP/' . phpversion();
	$params = '-f '.$from;
	
	//append Porgram Name to subject
	$subject = Config('NAME').' - '.$subject;
	
	return @mail($to,utf8_decode($subject),utf8_decode($message),$headers, $params);	
}

?>
