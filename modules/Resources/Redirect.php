<?php
//modif Francois: redirect to resources directly
switch($_REQUEST['to']) {

case 'videohelp': 
	$redir_url = 'http://go.centresis.org/videohelp/index.php';
	break;

case 'forums': 
	$redir_url = 'http://centresis.org/forums/index.php';
	break;

case 'translate': 
	$redir_url = 'http://translate.centresis.org/';
	break;
}
echo '<script type="text/javascript">window.open("'.$redir_url.'", "_blank");</script>';
?>