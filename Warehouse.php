<?php
if(!defined('WAREHOUSE_PHP'))
{
	define("WAREHOUSE_PHP",1);
	$RosarioVersion = '2.7-beta';

	if (!file_exists ('config.inc.php'))
		die ('config.inc.php not found. Please read the configuration guide.');
	require('config.inc.php');
	require('database.inc.php');

	// Load functions.
	$functions = scandir('functions/');
	foreach ($functions as $function)
	{
		//filter PHP files
		if ( mb_strrchr($function, '.') == '.php' )
			include('functions/'.$function);
	}

	// Start Session.
	session_name('RosarioSIS');
	if ($_SERVER['SCRIPT_NAME']!='/index.php')
		session_set_cookie_params(0,dirname($_SERVER['SCRIPT_NAME']).'/'); //,'',$false,$true);
	session_start();
	if(!$_SESSION['STAFF_ID'] && !$_SESSION['STUDENT_ID'] && mb_strpos($_SERVER['PHP_SELF'],'index.php')===false)
	{
?>
		<script>window.location.href = "index.php?modfunc=logout";</script>
<?php
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
	bindtextdomain('rosariosis', $LocalePath); //binds the messages domain to the locale folder
	bind_textdomain_codeset('rosariosis','UTF-8'); //ensures text returned is utf-8, quite often this is iso-8859-1 by default
	textdomain('rosariosis'); //sets the domain name, this means gettext will be looking for a file called rosariosis.mo
	mb_internal_encoding('UTF-8'); //modif Francois: multibyte strings
	
	// Modules
	// Core modules (packaged with RosarioSIS):
	// Core modules cannot be deleted
	$RosarioCoreModules = array(
		'School_Setup',
		'Students',
		'Users',
		'Scheduling',
		'Grades',
		'Attendance',
		'Eligibility',
		'Discipline',
		'Accounting',
		'Student_Billing',
		'Food_Service',
		'State_Reports',
		'Resources',
		'Custom'
	);
	
	$RosarioModules = unserialize(Config('MODULES'));
	
	// Plugins
	// Core plugins (packaged with RosarioSIS):
	// Core plugins cannot be deleted
	$RosarioCorePlugins = array(
		'Moodle'
	);

	$RosarioPlugins = unserialize(Config('PLUGINS'));
	
	// Load plugins functions.
	foreach($RosarioPlugins as $plugin=>$activated)
	{
		if ($activated)
			include('plugins/'.$plugin.'/functions.php');
	}

	// Load not core modules & plugins locales
	function _LoadAddonLocale($domain, $folder)
	{
		$LocalePath = $folder.$domain.'/locale';
		//check if locale folder exists
		if (is_dir($LocalePath))
		{
			bindtextdomain($domain, $LocalePath); //binds the messages domain to the locale folder
			bind_textdomain_codeset($domain,'UTF-8'); //ensures text returned is utf-8, quite often this is iso-8859-1 by default
		}
	}

	if (($not_core_modules = array_diff(array_keys($RosarioModules),$RosarioCoreModules)) || ($not_core_plugins = array_diff(array_keys($RosarioPlugins),$RosarioCorePlugins))) //not core?
	{
		if(is_array($not_core_modules))
			foreach($not_core_modules as $not_core_module)
				if($RosarioModules[$not_core_module]) //if module activated
					_LoadAddonLocale($not_core_module, 'modules/');

		if(is_array($not_core_plugins))
			foreach($not_core_plugins as $not_core_plugin)
				if($RosarioPlugins[$not_core_plugin]) //if plugin activated
					_LoadAddonLocale($not_core_plugin, 'plugins/');
	}

	function Warehouse($mode)
	{	global $_ROSARIO,$locale;

		switch($mode)
		{
			case 'header':
				$RTL_languages = array('ar', 'he', 'dv', 'fa', 'ur');
?>
<!doctype html>
<HTML lang="<?php echo mb_substr($locale,0,2); ?>"<?php echo (in_array(mb_substr($locale,0,2), $RTL_languages)?' dir="RTL"':''); ?>>
<HEAD>
	<TITLE><?php echo ParseMLField(Config('TITLE')); ?></TITLE>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width" />
	<noscript><META http-equiv="REFRESH" content="0;url=index.php?modfunc=logout&reason=javascript" /></noscript>
	<link REL="SHORTCUT ICON" HREF="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="assets/themes/<?php echo Preferences('THEME'); ?>/stylesheet.css" />
	<script src="assets/js/jquery.js"></script>
	<script src="assets/js/jquery.form.js"></script>
	<script src="assets/js/tipmessage/main15.js"></script>
	<script src="assets/js/warehouse.js"></script>
	<link rel="stylesheet" type="text/css" media="all" href="assets/js/jscalendar/calendar-blue.css" />
	<script src="assets/js/jscalendar/calendar+setup.js"></script>
	<script src="assets/js/jscalendar/lang/calendar-<?php echo file_exists('assets/js/jscalendar/lang/calendar-'.mb_substr($locale, 0, 2).'.js') ? mb_substr($locale, 0, 2) : 'en'; ?>.js"></script>
	<script>var scrollTop="<?php echo Preferences('SCROLL_TOP'); ?>";</script>
</HEAD>
<BODY>
<DIV id="Migoicons" style="visibility:hidden;position:absolute;z-index:1000;top:-100px"></DIV>
<?php
				if ($_ROSARIO['is_popup']) :
?>
<script>if(window == top  && (!window.opener)) window.location.href = "index.php";</script>
<div id="body" tabindex="0" role="main" class="mod">
<?php
				elseif ($_ROSARIO['not_ajax']) :
?>
<div id="wrap">
	<footer id="footer" class="mod">
		<?php include('Bottom.php'); ?>
	</footer>	
	<div id="menuback" class="mod"></div>
	<aside id="menu" class="mod">
		<?php include('Side.php'); ?>
	</aside>
	
	<div id="body" tabindex="0" role="main" class="mod">	
<?php 			
				endif;
			break;
			
			case 'footer':
?>
<BR />
<script>
var modname = "<?php echo $_ROSARIO['Program_loaded']; ?>";
if (menuStudentID!="<?php echo UserStudentID(); ?>" || menuStaffID!="<?php echo UserStaffID(); ?>" || menuSchool!="<?php echo UserSchool(); ?>" || menuCoursePeriod!="<?php echo UserCoursePeriod(); ?>") { 
	ajaxLink(menu_link);
}
<?php 			if (!empty($_ROSARIO['Program_loaded'])) : ?>
else
	openMenu(modname);
<?php			endif;

				if (isset($_ROSARIO['PrepareDate'])): 
					for($i=1;$i<=$_ROSARIO['PrepareDate'];$i++) : ?>
if (document.getElementById('trigger<?php echo $i; ?>'))
	Calendar.setup({
		monthField  : "monthSelect<?php echo $i; ?>",
		dayField    : "daySelect<?php echo $i; ?>",
		yearField   : "yearSelect<?php echo $i; ?>",
		ifFormat    : "%d-%b-%y",
		button      : "trigger<?php echo $i; ?>",
		align       : "Tl",
		singleClick : true
	});
<?php				endfor;
				endif; ?>
</script>
<?php
				$footer_plain = false;
				
				if ($_ROSARIO['is_popup']) : //popups
					$footer_plain = true;
?>
</div><!-- #body -->
<?php
				elseif ($_ROSARIO['not_ajax']) : //AJAX check
					$footer_plain = true;
?>
	</div><!-- #body -->
	<div style="clear:both;"></div>
</div><!-- #wrap -->
<?php
				endif;
				
				if ($footer_plain) :
?>
</BODY></HTML>
<?php
				endif;
			break;
		}
	}
}
?>
