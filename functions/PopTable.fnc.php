<?php

// DRAWS A TABLE WITH A BLUE TAB, SURROUNDING SHADOW
// REQUIRES A TITLE

function PopTable($action,$title='Search',$table_att='')
{	global $_ROSARIO;

	if($action=='header')
	{
//FJ css WPadmin
		echo '<TABLE class="postbox cellspacing-0" '.$table_att.'>';
		echo '<THEAD><TR><TH class="center">';
		if(is_array($title))
			echo WrapTabs($title,$_ROSARIO['selected_tab']);
		else
			echo DrawTab($title);
		echo '</TH></TR></THEAD><TBODY><TR><TD class="popTable">';
	}
	elseif($action=='footer')
	{
		echo '</TD></TR></TBODY></TABLE>';
	}
}
?>
