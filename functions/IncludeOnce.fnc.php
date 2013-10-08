<?php

//modif Francois: include once ColorBox
function includeOnceColorBox($rt2colorBoxDiv = false)
{
	static $included = false;
	//modif Francois: responsive rt td too large
	$return .= (!$included ? '<link rel="stylesheet" href="assets/js/colorbox/colorbox.css" type="text/css" media="screen" />
	<script type="text/javascript" src="assets/js/colorbox/jquery.colorbox-min.js"></script>
	<script type="text/javascript">
		var iframeInnerWidth = 640;
		if (screen.width<768)
			iframeInnerWidth = 300;
		$(document).ready(function(){
			$(\'.colorbox\').colorbox();
			$(\'.colorboxiframe\').colorbox({iframe:true, innerWidth:iframeInnerWidth});
			$(\'.colorboxinline\').colorbox({inline:true, maxWidth:\'95%\', maxHeight:\'95%\', scrolling:true});
		});
	</script>' : '');
	$return .= ($rt2colorBoxDiv ? '<div class="link2colorBox"><a class="colorboxinline" href="#'.$rt2colorBoxDiv.'"><img src="assets/visualize.png" class="alignImg" /> '._('View Online').'</a></div>' : '');

	$included = true;
	return $return;
}

?>