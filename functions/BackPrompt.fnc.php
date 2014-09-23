<?php

function BackPrompt($message)
{
//modif Francois: errors not readable
//	echo '<SCRIPT>alert("'.str_replace(array("'",'"'),array('&#39;','&quot;'),$message).'");self.history.go(-1);</SCRIPT>';
	?>

	<SCRIPT>alert(<?php echo json_encode($message); ?>);window.close();</SCRIPT>

	<?php exit();
}
?>