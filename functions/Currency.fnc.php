<?php

function Currency($num,$sign='before',$red=false)
{
//modif Francois: locale currency, see config.inc.php	
	global $CurrencySymbol;
	$original = $num;
	if($sign=='before' && $num<0)
	{
		$negative = true;
		$num *= -1;
	}
	elseif($sign=='CR' && $num<0)
	{
		$cr = true;
		$num *= -1;
	}

//	$num = "\$".number_format($num,2,'.',',');
	$num = $CurrencySymbol.number_format($num,2,'.',',');
	if($negative)
		$num = '-'.$num;
	elseif($cr)
		$num = $num.'CR';
	if($red && $original<0)
		$num = '<span style="color:red">'.$num.'</span>';

	return '<!-- '.$original.' -->'.$num;
}
?>