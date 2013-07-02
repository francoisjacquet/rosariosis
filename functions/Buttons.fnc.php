<?php
function Buttons($value1,$value2='')
{
	$buttons = '<INPUT type="SUBMIT" value="'.$value1.'" />';
	if($value2!='') 
		$buttons .= ' <INPUT type="RESET" value="'.$value2.'" />';
	
	return $buttons;
}
?>