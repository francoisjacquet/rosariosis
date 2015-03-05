<?php

function WrapTabs($tabs,$selected='',&$selected_key='')
{
		$row = 0;
     	$characters = 0;
//FJ css WPadmin
	if(count($tabs))
	{
		$rows[0] = '<div class="h3multi">';
		foreach($tabs as $key=>$tab)
		{
			if(mb_substr($tab['title'],0,1)!='<')
				$tab_len = mb_strlen($tab['title']);
			else
				$tab_len = 0;

			if($tab['link']==PreparePHP_SELF() || $tab['link']==$selected)
			{
				$rows[$row] .= '<!--BOTTOM-->'.'<span class="h3selected">' . DrawTab($tab['title'],$tab['link'],true) . '</span>';
				$selected_key = $key;
			}
			else
				$rows[$row] .= DrawTab($tab['title'],$tab['link']);

			$characters += $tab_len + 6;
		}
	}
	$rows[$row] .= "\n\n";

	$i = 0;
	$row_count = count($rows) - 1;

	for($key=$row_count;$key>=0;$key--)
	{
//FJ remove ereg
//		if(!ereg("<!--BOTTOM-->",$rows[$key]))
		if(mb_strpos($rows[$key],"<!--BOTTOM-->")===FALSE)
		{
			$table .= $rows[$key];
			$i++;
		}
		else
			$bottom = $key;
	}
	$table .= $rows[$bottom] . '</div>';

	return $table;
}

//FJ remove DrawTab params
//function DrawTab($title,$link='',$tabcolor='',$textcolor='#FFFFFF',$type='',$rollover='')
function DrawTab($title,$link='',$h3selected=false)
{
    $title = ParseMLField($title);
	if(mb_substr($title,0,1)!='<')
		$title = str_replace(" ","&nbsp;",$title);
/*	if(!$tabcolor)
		$tabcolor = Preferences('HEADER');*/

//FJ css WPadmin
	if($link && !isset($_REQUEST['_ROSARIO_PDF']))
		$block_table .= '<h3'.($h3selected ? ' class="title"' : '').'><A HREF="'.$link.'" class="BoxHeading" id="tab_link['.preg_replace('/[^a-zA-Z0-9]/','_',$link).']">'._($title).'</A></h3>';
	else
		$block_table .= '<h3 class="title">'.$title.'</h3>';
		
	return $block_table;
}

?>