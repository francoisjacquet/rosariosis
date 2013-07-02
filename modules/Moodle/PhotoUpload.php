<?php
require('config.inc.php');

if (!MOODLE_INTEGRATOR)
	exit;
	
require('database.inc.php');
// Load functions.
if($handle = opendir("functions"))
{
	if(!is_array($IgnoreFiles))
		$IgnoreFiles=Array();

	while (false !== ($file = readdir($handle)))
	{
		// if filename isn't '.' '..' or in the Ignore list... load it.
		if($file!='.' && $file!='..' && !in_array($file,$IgnoreFiles))
			require_once('functions/'.$file);
	}
}

echo Moodle($_POST['modname'], 'core_files_upload');
?>