<?php
//modif Francois: create HackingLog function to centralize code
function HackingLog()
{
	global $RosarioNotifyAddress;
	
	echo _('You\'re not allowed to use this program!').' '._('This attempted violation has been logged and your IP address was captured.');
	Warehouse('footer');
	if($RosarioNotifyAddress)
	{
		//modif Francois: add email headers
		$headers = 'From:'.$RosarioNotifyAddress."\r\n";
		$headers .= 'Return-Path:'.$RosarioNotifyAddress."\r\n"; 
		$headers .= 'Reply-To:'.$RosarioNotifyAddress . "\r\n" . 'X-Mailer:PHP/' . phpversion();
		$params = '-f '.$RosarioNotifyAddress;
		
		@mail($RosarioNotifyAddress,'HACKING ATTEMPT',"INSERT INTO HACKING_LOG (HOST_NAME,IP_ADDRESS,LOGIN_DATE,VERSION,PHP_SELF,DOCUMENT_ROOT,SCRIPT_NAME,MODNAME,QUERY_STRING,USERNAME) values('$_SERVER[SERVER_NAME]','$_SERVER[REMOTE_ADDR]','".date('Y-m-d')."','$RosarioVersion','$_SERVER[PHP_SELF]','$_SERVER[DOCUMENT_ROOT]','$_SERVER[SCRIPT_NAME]','$_REQUEST[modname]','$_SERVER[QUERY_STRING]','".User('USERNAME')."')", $headers, $params);
	}
	exit;
}
?>