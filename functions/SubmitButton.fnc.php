<?php

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