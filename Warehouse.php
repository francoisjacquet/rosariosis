<?php
//if(WAREHOUSE_PHP==0)
if(!defined('WAREHOUSE_PHP'))
{
	define("WAREHOUSE_PHP",1);
    $RosarioVersion = '2.0.2';

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
		echo '<script type="text/javascript">window.location.href = "index.php?modfunc=logout";</script>';
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
?>
<!doctype html>
<HTML lang="<?php echo mb_substr($locale,0,2); ?>" <?php echo (mb_substr($locale,0,2)=='he' || mb_substr($locale,0,2)=='ar'?' dir="RTL"':''); ?>>
<HEAD>
	<TITLE><?php echo ParseMLField(Config('TITLE')); ?></TITLE>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width" />
	<noscript><META http-equiv="REFRESH" content="0;url=index.php?modfunc=logout&reason=javascript" /></noscript>
	<link REL="SHORTCUT ICON" HREF="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="assets/themes/<?php echo Preferences('THEME'); ?>/stylesheet.css" />
	<script type="text/javascript" src="assets/js/jquery.js"></script>
	<script type="text/javascript" src="assets/js/jquery.form.js"></script>
	<script type="text/javascript" src="assets/js/tipmessage/main15.js"></script>
	<script type="text/javascript" src="assets/js/warehouse.js"></script>
	<link rel="stylesheet" type="text/css" media="all" href="assets/js/jscalendar/calendar-blue.css" />
	<script type="text/javascript" src="assets/js/jscalendar/calendar.js"></script>
	<script type="text/javascript" src="assets/js/jscalendar/lang/calendar-<?php echo mb_substr($locale, 0, 2); ?>.js"></script>
	<script type="text/javascript" src="assets/js/jscalendar/calendar-setup.js"></script>			
</HEAD>
<BODY>
<DIV id="Migoicons" style="visibility:hidden;position:absolute;z-index:1000;top:-100px"></DIV>
<?php
			break;
			case 'footer':
?>
<BR />
<?php
				if (isset($_ROSARIO['PrepareDate'])): 
					for($i=1;$i<=$_ROSARIO['PrepareDate'];$i++)
					{
?>
<script type="text/javascript">
if (document.getElementById('trigger<?php echo $i; ?>'))
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
			break;
			case 'footer_plain':
				echo '</BODY></HTML>';
			break;
		}
	}
}
?>