<?php

function DBDate($type='')
{
	if($type=='postgres')
		return date('Y-m-d');
	return mb_strtoupper(date('d-M-Y'));
}
?>