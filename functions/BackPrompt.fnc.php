<?php

function BackPrompt($message)
{
//modif Francois: errors not readable
//	echo '<SCRIPT>alert("'.str_replace(array("'",'"'),array('&#39;','&quot;'),$message).'");self.history.go(-1);</SCRIPT>';
	echo '<SCRIPT>alert("'.str_replace(array("'",'"'),array('&#39;','&quot;'),$message).'");window.close();</SCRIPT>';
	exit();
}
?>