<?php
function Buttons($value1,$value2='')
{
	$buttons = '<INPUT type="SUBMIT" value="'.$value1.'" />';
	if($value2!='') 
		$buttons .= ' <INPUT type="RESET" value="'.$value2.'" />';
	
	return $buttons;
}

function button($type,$text='',$link='',$height=18)
{
//modif Francois: css WPadmin
	if($link)
		$button .= '<A HREF='.$link.($type=='remove'? ' title="'._('Delete').'"' : '').'>'; //dont put "" round the link href to let Javascript code insert
	
//modif Francois: icones
	$img_file = 'assets/'.$type.'_button.png';
	if (!is_file($img_file))
		$img_file = 'assets/'.$type.'_button.gif';
	$button .= '<IMG SRC="'.$img_file.'" '.($height?'height="'.$height.'"':'').' style="vertical-align:middle;" />';

	if($text)
		$button .= '<b>'.$text.'</b>';
	
	if($link)
		$button .= '</A>';

	return $button;
}
?>