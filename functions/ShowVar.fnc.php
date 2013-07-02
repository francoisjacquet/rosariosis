<?php

function ShowVar($var,$string='N',$pre='Y')
{
	if($string=='Y') ob_start();
	if($pre=='Y') echo "<pre>";
	print_r($var);
	if($pre=='Y') echo "</pre>";
	if($string=='Y')
	{
		$info=ob_get_contents();
		ob_end_clean();
		return $info;
	}

}
?>
