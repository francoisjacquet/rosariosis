<?php

function DrawHeader($left='',$right='',$center='')
{	global $_ROSARIO;

	if(!isset($_ROSARIO['DrawHeader']))
	{
		$_ROSARIO['DrawHeader'] = '';
		if($_ROSARIO['HeaderIcon'])
			$left = '<IMG src="assets/icons/'.$_ROSARIO['HeaderIcon'].'" height="48" /> '.$left; //modif Francois: icones
	}

	echo '<TABLE class="width-100p cellspacing-0 cellpadding-0"><TR>';
//modif Francois: CSS WPadmin
	if($left)
		echo '<TD '.$_ROSARIO['DrawHeader'].' style="text-align:left;">&nbsp;'.($_ROSARIO['DrawHeader'] == ''? '<H2>'.$left.'</H2>':$left).'</TD>';
	if($center)
		echo '<TD '.$_ROSARIO['DrawHeader'].' style="text-align:center">'.($_ROSARIO['DrawHeader'] == ''? '<H2>'.$center.'</H2>':$center).'</TD>';
	if($right)
		echo '<TD '.$_ROSARIO['DrawHeader'].' style="text-align:right">'.($_ROSARIO['DrawHeader'] == ''? '<H2>'.$right.'</H2>':$right).'</TD>';
	echo '</TR></TABLE>';

	if($_ROSARIO['DrawHeader'] == '') {
		$_ROSARIO['DrawHeader'] = 'class="header2"';
	}
}
?>
