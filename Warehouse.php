<?php
//if(WAREHOUSE_PHP==0)
if(!defined('WAREHOUSE_PHP'))
{
	define("WAREHOUSE_PHP",1);
    $RosarioVersion = '1.4.8';

    if (!file_exists ('config.inc.php'))
        die ('config.inc.php not found. Please read the configuration guide.');
	require_once('config.inc.php');
	require_once('database.inc.php');
    
	//modif Francois: remove IgnoreFiles
	// Load functions.
	/*if($handle = opendir('functions'))
	{
		if(!is_array($IgnoreFiles))
			$IgnoreFiles=Array();

		while (false !== ($file = readdir($handle)))
		{
			// if filename isn't '.' '..' or in the Ignore list... load it.
			if($file!='.' && $file!='..' && !in_array($file,$IgnoreFiles))
				require_once('functions/'.$file);
		}
	}*/
	$functions = scandir('functions/');
	foreach ($functions as $function)
	{
		//filter PHP files
		if ( mb_strrchr($function, '.') == '.php' )
			require_once('functions/'.$function);
	}

	

	// Start Session.
    session_name('RosarioSIS');
    if ($_SERVER['SCRIPT_NAME']!='/index.php')
        session_set_cookie_params(0,dirname($_SERVER['SCRIPT_NAME']).'/'); //,'',$false,$true);
	session_start();
	if(!$_SESSION['STAFF_ID'] && !$_SESSION['STUDENT_ID'] && mb_strpos($_SERVER['PHP_SELF'],'index.php')===false)
	{
		header('Location: index.php');
		exit;
	}

    // Internationalization
    if (!empty($_GET['locale'])) 
		$_SESSION['locale'] = $_GET['locale'];
    if (empty($_SESSION['locale'])) 
		$_SESSION['locale'] = $RosarioLocales[0]; //english
    $locale = $_SESSION['locale'];
    putenv('LC_ALL='.$locale);
    setlocale(LC_ALL, $locale);
	setlocale(LC_NUMERIC, 'english','en_US', 'en_US.utf8'); //modif Francois: numeric separator "."
    bindtextdomain('rosariosis', $LocalePath);    //binds the messages domain to the locale folder
    bind_textdomain_codeset('rosariosis','UTF-8');     //ensures text returned is utf-8, quite often this is iso-8859-1 by default
    textdomain('rosariosis');    //sets the domain name, this means gettext will be looking for a file called rosariosis.mo
	mb_internal_encoding('UTF-8'); //modif Francois: multibyte strings
	
	function Warehouse($mode)
	{	global $_ROSARIO,$locale;

		switch($mode)
		{
			case 'header':
//modif Francois: fix bug Internet Explorer Quirks Mode, add DOCTYPE
?>
<!DOCTYPE html>
<HTML lang="<?php echo mb_substr($locale,0,2); ?>" <?php echo (mb_substr($locale,0,2)=='he' || mb_substr($locale,0,2)=='ar'?' dir="RTL"':''); ?>>
<HEAD><TITLE><?php echo ParseMLField(Config('TITLE')); ?></TITLE>
<meta charset="UTF-8" />
<?php			if(basename($_SERVER['PHP_SELF'])!='index.php'): ?>
<noscript><META http-equiv="REFRESH" content="0; url=index.php?modfunc=logout&amp;reason=javascript" /></noscript>
<script type="text/javascript" src="assets/js/tipmessage/main15.js"></script>
<?php			endif; ?>
<?php			if(basename($_SERVER['PHP_SELF'])=='index.php'): ?>
<?php			endif; ?>
<link rel="stylesheet" type="text/css" href="assets/themes/<?php echo Preferences('THEME'); ?>/stylesheet.css" />
<?php

			break;
			case "footer":
//modif Francois: Javascript load optimization
?>
<BR />
<script type="text/javascript" src="assets/js/warehouse.js" defer></script>
<?php
//modif Francois: load calendar Javascript only if required
				if (isset($_ROSARIO['PrepareDate'])): ?>
<link rel="stylesheet" type="text/css" media="all" href="assets/js/jscalendar/calendar-blue.css" />
<script type="text/javascript" src="assets/js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="assets/js/jscalendar/lang/calendar-<?php echo mb_substr($locale, 0, 2); ?>.js"></script>
<script type="text/javascript" src="assets/js/jscalendar/calendar-setup.js"></script>
<?php			
					for($i=1;$i<=$_ROSARIO['PrepareDate'];$i++)
					{
?>
<script type="text/javascript">
	Calendar.setup({
		monthField     :    "monthSelect<?php echo $i; ?>",
		dayField       :    "daySelect<?php echo $i; ?>",
		yearField      :    "yearSelect<?php echo $i; ?>",
		ifFormat       :    "%d-%b-%y",
		button         :    "trigger<?php echo $i; ?>",
		align          :    "Tl",
		singleClick    :    true
	});
</script>
<?php				}
				endif;
				echo '</BODY></HTML>';
			break;
		}
	}
}
?>