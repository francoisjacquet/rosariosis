<?php

// DRAWS A TABLE WITH A BLUE TAB, SURROUNDING SHADOW
// REQUIRES A TITLE

function PopTable($action,$title='Search',$table_att='')
{	global $_ROSARIO;

	if($action=='header')
	{
//modif Francois: css WPadmin
		echo '<TABLE class="postbox cellspacing-0 cellpadding-0" '.$table_att.'>';
		echo '<TR><TD class="center">';				
		if(is_array($title))
			echo WrapTabs($title,$_ROSARIO['selected_tab']);
		else
			echo DrawTab($title);
		echo '</TD></TR>
		<TR><TD class="popTable">';
	}
	elseif($action=='footer')
	{
		echo '</TD>
		</TR>
		</TABLE>';
	}
}
?>