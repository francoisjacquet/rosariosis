<?php
function Buttons($value1,$value2='')
{
	$buttons = '<INPUT type="SUBMIT" value="'.$value1.'" />';
	if($value2!='') 
		$buttons .= ' <INPUT type="RESET" value="'.$value2.'" />';
	
	return $buttons;
}

function button($type,$text='',$link='',$class='')
{
	if($link)
		//dont put "" round the link href to let Javascript code insert
		$button .= '<A HREF='.$link.($type=='remove' && empty($text)? ' title="'._('Delete').'"' : '').'>';

	$img_file = 'assets/themes/'. Preferences('THEME') . '/btn/' . $type .'_button.png';

	$button .= '<IMG SRC="'.$img_file.'" class="button '.$class.'" />';

	if($text)
		$button .= ' <b>'.$text.'</b>';

	if($link)
		$button .= '</A>';

	return $button;
}

function SubmitButton($value='Submit',$name='',$options='')
{
	if(AllowEdit())
		return '<INPUT type="submit" value="'.$value.'"'.($name?' name="'.$name.'"':'').($options?' '.$options:'').' />';
	else
		return '';
}

function ResetButton($value='Reset',$options='')
{
	if(AllowEdit())
		return '<INPUT type="reset" value="'.$value.'"'.($options?' '.$options:'').' />';
	else
		return '';
}
?>
