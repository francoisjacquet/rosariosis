<?php

function DBDate($type='oracle')
{
	if($type=='oracle')
		return mb_strtoupper(date('d-M-y'));
	elseif($type=='postgres')
		return date('Y-m-d');
	return mb_strtoupper(date('d-M-Y'));
}
?>