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
	if($link)
		$button .= '</A>';

	if($text)
	{
		$button .= '<b>';
		if($link)
			$button .= '<A HREF='.$link.'>'; //dont put "" round the link href to let Javascript code insert
		$button .= $text;
		if($link)
			$button .= '</A>';
		$button .= '</b><BR />';
	}

	return $button;
}
?>