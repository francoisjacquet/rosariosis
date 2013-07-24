<?php

function DrawHeader($left='',$right='',$center='')
{	global $_ROSARIO;

	echo '<TABLE class="width-100p cellspacing-0 cellpadding-0"><TR>';
//modif Francois: CSS WPadmin
	if($left)
		echo '<TD '.$_ROSARIO['DrawHeader'].' style="text-align:left;">&nbsp;'.(!isset($_ROSARIO['DrawHeader'])? (!empty($_ROSARIO['HeaderIcon']) ? '<H2>'.'<IMG src="assets/icons/'.$_ROSARIO['HeaderIcon'].'" class="headerIcon" /> '.$left.'</H2>' : '<H2>'.$left.'</H2>'):$left).'</TD>';
	if($center)
		echo '<TD '.$_ROSARIO['DrawHeader'].' style="text-align:center">'.(!isset($_ROSARIO['DrawHeader'])? '<H2>'.$center.'</H2>':$center).'</TD>';
	if($right)
		echo '<TD '.$_ROSARIO['DrawHeader'].' style="text-align:right">'.(!isset($_ROSARIO['DrawHeader'])? '<H2>'.$right.'</H2>':$right).'</TD>';
	echo '</TR></TABLE>';

	$_ROSARIO['DrawHeader'] = 'class="header2"';
}
?>
