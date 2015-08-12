<?php
//FJ create HackingLog function to centralize code
function HackingLog()
{
	global $RosarioNotifyAddress, $RosarioVersion;
	
	if($RosarioNotifyAddress)
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];

		//FJ add SendEmail function
		include_once('ProgramFunctions/SendEmail.fnc.php');
		
		$message = "INSERT INTO HACKING_LOG (HOST_NAME,IP_ADDRESS,LOGIN_DATE,VERSION,PHP_SELF,DOCUMENT_ROOT,SCRIPT_NAME,MODNAME,QUERY_STRING,USERNAME) 
values('".$_SERVER['SERVER_NAME']."','".$ip."','".date('Y-m-d')."','".$RosarioVersion."','".$_SERVER['PHP_SELF']."','".$_SERVER['DOCUMENT_ROOT']."','".$_SERVER['SCRIPT_NAME']."','".$_REQUEST['modname']."','".$_SERVER['QUERY_STRING']."','".User('USERNAME')."')";
		
		SendEmail($RosarioNotifyAddress,'HACKING ATTEMPT', $message);
	}

	$error[] = _('You\'re not allowed to use this program!').' '._('This attempted violation has been logged and your IP address was captured.');

	return ErrorMessage($error, 'fatal');
}
?>
