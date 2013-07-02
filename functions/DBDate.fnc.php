<?php

function DBDate($type='oracle')
{
	if($type=='oracle')
		return strtoupper(date('d-M-y'));
	elseif($type=='postgres')
		return date('Y-m-d');
	return strtoupper(date('d-M-Y'));
}
?>