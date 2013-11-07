<?php

function BackPrompt($message)
{
//modif Francois: errors not readable
//	echo '<SCRIPT type="text/javascript">alert("'.str_replace(array("'",'"'),array('&#39;','&quot;'),$message).'");self.history.go(-1);</SCRIPT>';
	echo '<SCRIPT type="text/javascript">alert("'.str_replace(array("'",'"'),array('&#39;','&quot;'),$message).'");window.close();</SCRIPT>';
	exit();
}
?>