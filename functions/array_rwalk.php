<?php

// RECURSIVE ARRAY_WALK()

function array_rwalk(&$array, $function)
{
	foreach($array as $key => $value)
	{
		if(is_array($value))
		{
			array_rwalk($value, $function);
			$array[$key] = $value;
		}
		else
			$array[$key] = $function($value);
	}
}
?>