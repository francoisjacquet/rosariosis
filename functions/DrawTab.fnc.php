<?php

//modif Francois: remove DrawTab params
//function DrawTab($title,$link='',$tabcolor='',$textcolor='#FFFFFF',$type='',$rollover='')
function DrawTab($title,$link='')
{
    $title = ParseMLField($title);
	if(substr($title,0,1)!='<')
		$title = str_replace(" ","&nbsp;",$title);
/*	if(!$tabcolor)
		$tabcolor = Preferences('HEADER');*/

//modif Francois: css WPadmin
	if($link && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		$block_table .= '<h3><A HREF="'.$link.'" class="BoxHeading" id="tab_link['.preg_replace('/[^a-zA-Z0-9]/','_',$link).']">'._($title).'</A></h3>';
	}
	else
	{
		if(!isset($_REQUEST['_ROSARIO_PDF']))
			$block_table .= '<h3>'.$title.'</h3>';
		else
			$block_table .= '<span class="size-1" style="color:'.Preferences('HIGHLIGHT').'"><b>'.$title.'</b>&nbsp;</span>';
	}
	return $block_table;
}

?>