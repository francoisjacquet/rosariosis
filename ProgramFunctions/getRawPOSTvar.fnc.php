<?php

//modif Francois: bypass strip_tags on the $_REQUEST vars

//used to get TinyMCE textarea content
function getRawPOSTvar($key)
{
	$rawpost = "&".file_get_contents("php://input"); 
	$pos = preg_match("/&".$key."=([^&]*)/i",$rawpost, $regs);
	
	if ($pos == 1)
		return urldecode($regs[1]);
	else
		return null;
}

?>