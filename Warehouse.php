<?php

if( !defined( 'WAREHOUSE_PHP' ) )
{
	define( 'WAREHOUSE_PHP', 1 );

	define( 'ROSARIO_VERSION', '2.9a' );

	if ( !file_exists( 'config.inc.php' ) )
		die ( 'config.inc.php not found. Please read the installation directions.' );

	require( 'config.inc.php' );

	require( 'database.inc.php' );


	/**
	 * Optional configuration
	 * You can override the following definitions in the config.inc.php file
	 */
	// Debug mode (for developers): enables notices
	if ( !defined( 'ROSARIO_DEBUG' ) )
		define( 'ROSARIO_DEBUG', false );

	if ( ROSARIO_DEBUG )
		error_reporting( E_ALL );
	else
		error_reporting( E_ALL ^ E_NOTICE );

	// Server Paths
	if ( !isset( $RosarioPath ) )
		$RosarioPath = dirname( __FILE__ ) . '/';

	if ( !isset( $StudentPicturesPath ) )
		$StudentPicturesPath = 'assets/StudentPhotos/';

	if ( !isset( $UserPicturesPath ) )
		$UserPicturesPath = 'assets/UserPhotos/';

	if ( !isset( $LocalePath ) )
		$LocalePath = 'locale'; // Path were the language packs are stored. You need to restart Apache at each change in this directory

	// Time zone
	if ( isset( $Timezone ) ) // Sets the default time zone used by all date/time functions
	{
		if ( date_default_timezone_set( $Timezone ) ) // if valid PHP timezone_identifier, should be OK for Postgres
			DBQuery( "SET TIMEZONE TO '" . $Timezone . "'" );
	}


	/**
	 * Load functions.
	 */
	$functions = glob( 'functions/*.php' );

	foreach ( $functions as $function )
	{
		include( $function );
	}


	/**
	 * Start Session.
	 */
	session_name( 'RosarioSIS' );

	//http://php.net/manual/en/session.security.php
	$cookie_path = dirname( $_SERVER['SCRIPT_NAME'] ) == '/' ? '/' : dirname( $_SERVER['SCRIPT_NAME'] ) . '/';
	session_set_cookie_params( 0, $cookie_path, '', false, true );

	session_cache_limiter( 'nocache' );

	session_start();

	// Logout if no Staff or Student session ID
	if( empty( $_SESSION['STAFF_ID'] )
		&& empty( $_SESSION['STUDENT_ID'] )
		&& basename( $_SERVER['SCRIPT_NAME'] ) !== 'index.php' )
	{
?>
		<script>window.location.href = "index.php?modfunc=logout";</script>
<?php
		exit;
	}


	/**
	 * Array recursive walk
	 *
	 * @param  array  &$array
	 * @param  string $function function name
	 *
	 * @return array  &$array   array passed through $function function
	 */
	function array_rwalk( &$array, $function )
	{
		$key = array_keys( $array );

		$size = sizeOf( $key );

		for ( $i = 0; $i < $size; $i++ )
			if ( is_array( $array[$key[$i]] ) )
				array_rwalk( $array[$key[$i]], $function );
			else
				$array[$key[$i]] = $function( $array[$key[$i]] );
	}


	/**
	 * Sanitize $_REQUEST array
	 * $_POST + $_GET
	 */
	// Escape strings for DB queries
	array_rwalk( $_REQUEST, 'DBEscapeString' );

	// Remove HTML tags
	array_rwalk( $_REQUEST, 'strip_tags' );


	/**
	 * Internationalization
	 */
	if ( !empty( $_GET['locale'] ) )
		$_SESSION['locale'] = $_GET['locale'];

	if ( empty( $_SESSION['locale'] ) )
		$_SESSION['locale'] = $RosarioLocales[0]; //english?

	$locale = $_SESSION['locale'];

	putenv( 'LC_ALL=' . $locale );

	setlocale( LC_ALL, $locale );

	//FJ numeric separator "."
	setlocale( LC_NUMERIC, 'english','en_US', 'en_US.utf8' );

	//FJ bugfix for Turkish characters conversion
	if ( $locale == 'tr_TR.utf8' )
		setlocale( LC_CTYPE, 'english','en_US', 'en_US.utf8' );

	//binds the messages domain to the locale folder
	bindtextdomain( 'rosariosis', $LocalePath );

	//ensures text returned is utf-8, quite often this is iso-8859-1 by default
	bind_textdomain_codeset( 'rosariosis', 'UTF-8' );

	//sets the domain name, this means gettext will be looking for a file called rosariosis.mo
	textdomain( 'rosariosis' );

	//FJ multibyte strings
	mb_internal_encoding( 'UTF-8' );
	

	/**
	 * Modules
	 */
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
	
	$RosarioModules = unserialize( Config( 'MODULES' ) );


	/**
	 * Plugins
	 */
	// Core plugins (packaged with RosarioSIS):
	// Core plugins cannot be deleted
	$RosarioCorePlugins = array(
		'Moodle'
	);

	$RosarioPlugins = unserialize( Config( 'PLUGINS' ) );
	
	// Load plugins functions.
	foreach( (array)$RosarioPlugins as $plugin => $activated )
	{
		if ( $activated )
			include( 'plugins/' . $plugin . '/functions.php' );
	}

	/**
	 * Load not core modules & plugins locales
	 *
	 * @param  string $domain text domain
	 * @param  string $folder plugin or module folder
	 *
	 * @return void
	 */
	function _LoadAddonLocale( $domain, $folder )
	{
		$LocalePath = $folder . $domain . '/locale';

		//check if locale folder exists
		if ( is_dir( $LocalePath ) )
		{
			//binds the messages domain to the locale folder
			bindtextdomain( $domain, $LocalePath );

			//ensures text returned is utf-8, quite often this is iso-8859-1 by default
			bind_textdomain_codeset( $domain, 'UTF-8' );
		}
	}

	// if not core modules or plugins, load locale
	if ( ( $not_core_modules = array_diff( array_keys( $RosarioModules ), $RosarioCoreModules ) )
		|| ( $not_core_plugins = array_diff( array_keys( $RosarioPlugins ), $RosarioCorePlugins ) ) )
	{
		// load module locale
		foreach( $not_core_modules as $not_core_module )
			//if module activated
			if( $RosarioModules[$not_core_module] )
				_LoadAddonLocale( $not_core_module, 'modules/' );

		// load plugin locale
		foreach( $not_core_plugins as $not_core_plugin )
			//if plugin activated
			if( $RosarioPlugins[$not_core_plugin] )
				_LoadAddonLocale( $not_core_plugin, 'plugins/' );
	}


	/**
	 * Output HTML header (including Bottom & Side menus), or footer
	 *
	 * @example  Warehouse( 'header' );
	 *
	 * @param string $mode 'header' or 'footer'
	 *
	 * @return  outputs HTML
	 */
	function Warehouse( $mode )
	{
		global $_ROSARIO;

		switch( $mode )
		{
			// Header HTML
			case 'header':

				$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

				// Right to left direction
				$RTL_languages = array( 'ar', 'he', 'dv', 'fa', 'ur' );

				$dir_RTL = in_array( $lang_2_chars, $RTL_languages ) ? ' dir="RTL"' : '';
?>
<!doctype html>
<html lang="<?php echo $lang_2_chars; ?>"<?php echo $dir_RTL; ?>>
<head>
	<title><?php echo ParseMLField( Config( 'TITLE' ) ); ?></title>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width" />
	<noscript>
		<meta http-equiv="REFRESH" content="0;url=index.php?modfunc=logout&amp;reason=javascript" />
	</noscript>
	<link rel="SHORTCUT ICON" href="favicon.ico" />
	<link rel="stylesheet" href="assets/themes/<?php echo Preferences( 'THEME' ); ?>/stylesheet.css?v=<?php echo ROSARIO_VERSION; ?>" />
	<script src="assets/js/jquery.js"></script>
	<script src="assets/js/plugins.min.js"></script>
	<script src="assets/js/warehouse.js?v=<?php echo ROSARIO_VERSION; ?>"></script>
	<script src="assets/js/jscalendar/lang/calendar-<?php echo file_exists( 'assets/js/jscalendar/lang/calendar-' . $lang_2_chars . '.js' ) ? $lang_2_chars : 'en'; ?>.js"></script>
	<script>var scrollTop = "<?php echo Preferences( 'SCROLL_TOP' ); ?>";</script>
</head>
<body>
<?php
				// if popup window, verify it is an actual popup
				if ( $_ROSARIO['is_popup'] ) :
?>
<script>if(window == top  && (!window.opener)) window.location.href = "index.php";</script>
<?php
				// else if not AJAX request
				elseif ( $_ROSARIO['not_ajax'] ) :
?>
<div id="wrap">
	<footer id="footer" class="mod">
		<?php include( 'Bottom.php' ); // include Bottom menu ?>
	</footer>	
	<div id="menuback" class="mod"></div>
	<aside id="menu" class="mod">
		<?php include( 'Side.php' ); // include Side menu ?>
	</aside>

<?php
				endif;
?>
	<div id="body" tabindex="0" role="main" class="mod">
<?php
			break;
			

			// Footer HTML
			case 'footer':
?>
<BR />
<script>
	var modname = "<?php echo isset( $_ROSARIO['ProgramLoaded'] ) ? $_ROSARIO['ProgramLoaded'] : ''; ?>";
	if (typeof menuStudentID !== 'undefined'
		&& (menuStudentID != "<?php echo UserStudentID(); ?>"
			|| menuStaffID != "<?php echo UserStaffID(); ?>"
			|| menuSchool != "<?php echo UserSchool(); ?>"
			|| menuCoursePeriod != "<?php echo UserCoursePeriod(); ?>")) { 
		ajaxLink( 'Side.php' );
	}
<?php 			if ( !empty( $_ROSARIO['ProgramLoaded'] ) ) : ?>
	else
		openMenu( modname );
<?php				endif;

				if ( isset( $_ROSARIO['PrepareDate'] ) ):
					for( $i = 1; $i <= $_ROSARIO['PrepareDate']; $i++ ) : ?>
	if (document.getElementById('trigger<?php echo $i; ?>'))
		Calendar.setup({
			monthField:"monthSelect<?php echo $i; ?>",
			dayField:"daySelect<?php echo $i; ?>",
			yearField:"yearSelect<?php echo $i; ?>",
			ifFormat:"%d-%b-%y",
			button:"trigger<?php echo $i; ?>",
			align:"Tl",
			singleClick:true
		});
<?php				endfor;
				endif; ?>
</script>
<?php
				// if popup window or if not AJAX request
				if ( $_ROSARIO['is_popup']
					|| $_ROSARIO['not_ajax'] ) :
?>
	</div><!-- #body -->
<?php
					if ( $_ROSARIO['not_ajax'] ) :
?>
	<div style="clear:both;"></div>
</div><!-- #wrap -->
<?php
					endif;
?>
</body></html>
<?php
				endif;

			break;
		} // end switch
	} // end Warehouse()
}
