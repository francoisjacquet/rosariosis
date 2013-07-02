<?php

//modif Francois: include once Jquery
function includeOnceJquery()
{
	static $included = false;
	$return = (!$included ? '<script src="assets/js/jquery.js" type="text/javascript" charset="utf-8"></script>' : '');
	$included = true;
	return $return;
}

?>