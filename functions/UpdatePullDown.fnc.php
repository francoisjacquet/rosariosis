<?php

function UpdatePullDown($form,$select,$values,$id,$text)
{
	$array = "new Array(";
	
	// PREPARE JS ARRAY OF TEXT VALUES
	foreach($values as $category_id=>$category)
	{
		$array .= "new Array(";
		foreach($category as $value)
			$array .= "'$value[$text]',";
		$array = substr($array,0,-1).'),';
	}
	$array = substr($array,0,-1).')';
	
	// PREPARE JS ARRAY OF THE IDS
	$ids_array = "new Array(";

	foreach($values as $category)
	{
		$ids_array .= "new Array(";
		foreach($category as $value)
			$ids_array .= "'$value[$id]',";
		$ids_array = substr($ids_array,0,-1).'),';
	}		
	$ids_array = substr($ids_array,0,-1).')';
	
	return "onChange=\"javascript:updatePullDown(window.document.$form.$select,this.selectedIndex,$array,$ids_array);\"";
}
?>