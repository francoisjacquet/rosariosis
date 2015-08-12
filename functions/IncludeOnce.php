<?php

//FJ include once ColorBox
function includeOnceColorBox($rt2colorBoxDiv = false)
{
	static $included = false;
	//FJ responsive rt td too large
	$return .= (!$included ? '<link rel="stylesheet" href="assets/js/colorbox/colorbox.css" type="text/css" media="screen" />
	<script src="assets/js/colorbox/jquery.colorbox-min.js"></script>
	<script>
		var cWidth = 640; var cHeight = 390;
		if (screen.width<768) {
			cWidth = 300; cHeight = 183;
		}
		$(document).ready(function(){
			$(\'.colorbox\').colorbox();
			$(\'.colorboxiframe\').colorbox({iframe:true, innerWidth:cWidth, innerHeight:cHeight});
			$(\'.colorboxinline\').colorbox({inline:true, maxWidth:\'85%\', maxHeight:\'85%\', scrolling:true});
		});
	</script>' : '');
	$return .= ($rt2colorBoxDiv ? '<div class="link2colorBox"><a class="colorboxinline" href="#'.$rt2colorBoxDiv.'"><img src="assets/themes/'. Preferences('THEME') .'/btn/visualize.png" class="button bigger" /> '._('View Online').'</a></div>' : '');

	$included = true;
	return $return;
}
