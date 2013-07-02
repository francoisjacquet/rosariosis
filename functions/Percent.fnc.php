<?php

function Percent($num,$decimals=2)
{
	return number_format($num*100,$decimals,'.','').'%';
}
?>