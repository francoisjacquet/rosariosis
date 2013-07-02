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
echo '<form name="RosarioArgs" method="post"  action="'.$redir_url.'">';
?>
</form>
<script type="text/javascript">document.RosarioArgs.submit();</script>
<noscript>You must have JavaScript enabled to use the <a href="http://www.rosariosis.org">RosarioSIS</a> Online Resources.</noscript>