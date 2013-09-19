<?php

//modif Francois: include once Jquery
function includeOnceJquery()
{
	static $included = false;
	$return = (!$included ? '<script src="assets/js/jquery.js" type="text/javascript" charset="utf-8"></script>' : '');
	$included = true;
	return $return;
}

//modif Francois: include once ColorBox
function includeOnceColorBox($rt2colorBoxDiv = false)
{
	static $included = false;
	$return .= includeOnceJquery();
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
			$(\'.colorboxinline\').colorbox({inline:true' : '');
	
	//modif Francois: responsive rt td too large
	//responsive mode: 300px large, inline content
	$return .= ($rt2colorBoxDiv && !$included ? ', innerWidth:300' : '');
	$return .= (!$included ? '});
		});
	</script>' : '');
	$return .= ($rt2colorBoxDiv ? '<div class="link2colorBox"><a class="colorboxinline" href="#'.$rt2colorBoxDiv.'"><img src="assets/visualize.png" class="alignImg" /> '._('View Online').'</a></div>' : '');

	$included = true;
	return $return;
}

?>