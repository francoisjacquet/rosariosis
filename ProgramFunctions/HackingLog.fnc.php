<?php
//modif Francois: create HackingLog function to centralize code
function HackingLog()
{
	global $RosarioNotifyAddress;
	
	echo _('You\'re not allowed to use this program!').' '._('This attempted violation has been logged and your IP address was captured.');
	Warehouse('footer');
	if($RosarioNotifyAddress)
	{
		//modif Francois: add SendEmail function
		include('ProgramFunctions/SendEmail.fnc.php');
		
		$message = "INSERT INTO HACKING_LOG (HOST_NAME,IP_ADDRESS,LOGIN_DATE,VERSION,PHP_SELF,DOCUMENT_ROOT,SCRIPT_NAME,MODNAME,QUERY_STRING,USERNAME) 
values('".$_SERVER['SERVER_NAME']."','".$_SERVER['REMOTE_ADDR']."','".date('Y-m-d')."','".$RosarioVersion."','".$_SERVER['PHP_SELF']."','".$_SERVER['DOCUMENT_ROOT']."','".$_SERVER['SCRIPT_NAME']."','".$_REQUEST['modname']."','".$_SERVER['QUERY_STRING']."','".User('USERNAME')."')";
		
		SendEmail($RosarioNotifyAddress,'HACKING ATTEMPT', $message);
	}
	exit;
}
?>
