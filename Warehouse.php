<?php
/**
 * Warehouse
 *
 * Get configuration
 * Load functions
 * Start Session
 * Sanitize $_REQUEST array
 * Internationalization
 * Modules & Plugins
 * Update RosarioSIS
 * Warehouse() function (Output HTML header (including Bottom & Side menus), or footer)
 * isPopup() function (Popup window detection)
 *
 * @package RosarioSIS
 */

define( 'ROSARIO_VERSION', '2.9-alpha' );

/**
 * Include config.inc.php file.
 *
 * Do NOT change for require_once, include_once allows the error message to be displayed.
 */
if ( ! include_once 'config.inc.php' )
{
	die ( 'config.inc.php file not found. Please read the installation directions.' );
}

require_once 'database.inc.php';


/**
 * Optional configuration
 * You can override the following definitions in the config.inc.php file
 */
// Debug mode (for developers): enables notices.
if ( ! defined( 'ROSARIO_DEBUG' ) )
{
	define( 'ROSARIO_DEBUG', false );
}

if ( ROSARIO_DEBUG )
{
	error_reporting( E_ALL );
}
else
	error_reporting( E_ALL ^ E_NOTICE );

// Server Paths.
if ( ! isset( $RosarioPath ) )
{
	$RosarioPath = dirname( __FILE__ ) . '/'; // PHP 5.3 compatible equivalent to __DIR__.
}

if ( ! isset( $StudentPicturesPath ) )
{
	$StudentPicturesPath = 'assets/StudentPhotos/';
}

if ( ! isset( $UserPicturesPath ) )
{
	$UserPicturesPath = 'assets/UserPhotos/';
}

if ( ! isset( $LocalePath ) )
{
	// Path were the language packs are stored. You need to restart Apache at each change in this directory.
	$LocalePath = 'locale';
}

// Time zone.
if ( isset( $Timezone ) ) // Sets the default time zone used by all date/time functions.
{
	if ( date_default_timezone_set( $Timezone ) ) // If valid PHP timezone_identifier, should be OK for Postgres.
	{
		DBQuery( "SET TIMEZONE TO '" . $Timezone . "'" );
	}
}


/**
 * Load functions
 */
$functions = glob( 'functions/*.php' );

foreach ( $functions as $function )
{
	require_once $function;
}


/**
 * Start Session
 */
session_name( 'RosarioSIS' );

// See http://php.net/manual/en/session.security.php.
$cookie_path = dirname( $_SERVER['SCRIPT_NAME'] ) == '/' ?
	'/' :
	dirname( $_SERVER['SCRIPT_NAME'] ) . '/';

session_set_cookie_params( 0, $cookie_path, '', false, true );

session_cache_limiter( 'nocache' );

session_start();

// Logout if no Staff or Student session ID.
if ( empty( $_SESSION['STAFF_ID'] )
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
 * @param  array  $array    Array by reference.
 * @param  string $function Function name.
 *
 * @return array  &$array   Array passed through $function function
 */
function array_rwalk( &$array, $function )
{
	$key = array_keys( $array );

	$size = count( $key );

	for ( $i = 0; $i < $size; $i++ )
	{
		if ( is_array( $array[ $key[ $i ] ] ) )
		{
			array_rwalk( $array[ $key[ $i ] ], $function );
		}
		else
			$array[ $key[ $i ] ] = $function( $array[ $key[ $i ] ] );
	}
}


/**
 * Sanitize $_REQUEST array
 * ($_POST + $_GET)
 */
// Escape strings for DB queries.
array_rwalk( $_REQUEST, 'DBEscapeString' );

// Remove HTML tags.
array_rwalk( $_REQUEST, 'strip_tags' );


/**
 * Internationalization
 */
if ( ! empty( $_GET['locale'] ) )
{
	$_SESSION['locale'] = $_GET['locale'];
}

if ( empty( $_SESSION['locale'] ) )
{
	$_SESSION['locale'] = $RosarioLocales[0]; // English?
}

$locale = $_SESSION['locale'];

putenv( 'LC_ALL=' . $locale );

setlocale( LC_ALL, $locale );

// FJ numeric separator ".".
setlocale( LC_NUMERIC, 'english','en_US', 'en_US.utf8' );

// FJ bugfix for Turkish characters conversion.
if ( $locale === 'tr_TR.utf8' )
{
	setlocale( LC_CTYPE, 'english','en_US', 'en_US.utf8' );
}

// Binds the messages domain to the locale folder.
bindtextdomain( 'rosariosis', $LocalePath );

// Ensures text returned is utf-8, quite often this is iso-8859-1 by default.
bind_textdomain_codeset( 'rosariosis', 'UTF-8' );

// Sets the domain name, this means gettext will be looking for a file called rosariosis.mo.
textdomain( 'rosariosis' );

// FJ multibyte strings.
mb_internal_encoding( 'UTF-8' );


/**
 * Modules
 *
 * Core modules (packaged with RosarioSIS):
 * Core modules cannot be deleted.
 */
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
	'Resources',
	'Custom',
);

$RosarioModules = unserialize( Config( 'MODULES' ) );


// Load modules functions (optional).
foreach ( (array) $RosarioModules as $module => $activated )
{
	if ( ! $activated )
	{
		continue;
	}

	$module_functions = 'modules/' . $module . '/functions.php';

	if ( file_exists( $module_functions ) )
	{
		require_once $module_functions;
	}
}


/**
 * Plugins
 *
 * Core plugins (packaged with RosarioSIS):
 * Core plugins cannot be deleted
 */
$RosarioCorePlugins = array(
	'Moodle',
);

$RosarioPlugins = unserialize( Config( 'PLUGINS' ) );

// Load plugins functions.
foreach ( (array) $RosarioPlugins as $plugin => $activated )
{
	if ( $activated )
	{
		require_once 'plugins/' . $plugin . '/functions.php';
	}
}


/**
 * Load not core modules & plugins locales
 *
 * Local function
 *
 * @param  string $domain Text domain.
 * @param  string $folder Plugin or Module folder.
 *
 * @return void
 */
function _LoadAddonLocale( $domain, $folder )
{
	$LocalePath = $folder . $domain . '/locale';

	// Check if locale folder exists.
	if ( is_dir( $LocalePath ) )
	{
		// Binds the messages domain to the locale folder.
		bindtextdomain( $domain, $LocalePath );

		// Ensures text returned is utf-8, quite often this is iso-8859-1 by default.
		bind_textdomain_codeset( $domain, 'UTF-8' );
	}
}

$non_core_modules = array_diff( array_keys( $RosarioModules ), $RosarioCoreModules );
$non_core_plugins = array_diff( array_keys( $RosarioPlugins ), $RosarioCorePlugins );

// If any non core modules or plugins, load locale.
// Load module locale.
foreach ( (array) $non_core_modules as $non_core_module )
{
	// If module activated.
	if ( $RosarioModules[ $non_core_module ] )
	{
		_LoadAddonLocale( $non_core_module, 'modules/' );
	}
}

// Load plugin locale.
foreach ( (array) $non_core_plugins as $non_core_plugin )
{
	// If plugin activated.
	if ( $RosarioPlugins[ $non_core_plugin ] )
	{
		_LoadAddonLocale( $non_core_plugin, 'plugins/' );
	}
}


/**
 * Update RosarioSIS
 * Automatically runs after manual files update
 * To apply eventual incremental DB updates
 *
 * @since 2.9
 *
 * @see ProgramFunctions/Update.fnc.php
 */
// Check if version in DB < ROSARIO_VERSION.
if ( version_compare( Config( 'VERSION' ), ROSARIO_VERSION,	'<' ) )
{
	require_once 'ProgramFunctions/Update.fnc.php';

	// Run Update() to apply updates if any.
	Update();
}


/**
 * Output HTML header (including Bottom & Side menus), or footer
 *
 * @example  Warehouse( 'header' );
 *
 * @global $_ROSARIO Uses $_ROSARIO['is_popup'], $_ROSARIO['not_ajax'], $_ROSARIO['ProgramLoaded']
 *
 * @param  string $mode 'header' or 'footer'.
 */
function Warehouse( $mode )
{
	global $_ROSARIO;

	switch ( $mode )
	{
		// Header HTML.
		case 'header':

			$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

			// Right to left direction.
			$RTL_languages = array( 'ar', 'he', 'dv', 'fa', 'ur' );

			$dir_RTL = in_array( $lang_2_chars, $RTL_languages ) ? ' dir="RTL"' : '';
?>
<!doctype html>
<html lang="<?php echo $lang_2_chars; ?>"<?php echo $dir_RTL; ?>>
<head>
	<title><?php echo ParseMLField( Config( 'TITLE' ) ); ?></title>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<noscript>
		<meta http-equiv="REFRESH" content="0;url=index.php?modfunc=logout&amp;reason=javascript" />
	</noscript>
	<link rel="icon" href="favicon.ico" sizes="32x32" />
	<link rel="icon" href="apple-touch-icon.png" sizes="128x128" />
	<link rel="stylesheet" href="assets/themes/<?php echo Preferences( 'THEME' ); ?>/stylesheet.css?v=<?php echo ROSARIO_VERSION; ?>" />
	<style>.highlight,.highlight-hover:hover{background-color:<?php echo Preferences( 'HIGHLIGHT' ); ?> !important;}</style>
	<script src="assets/js/jquery.js"></script>
	<script src="assets/js/plugins.min.js"></script>
	<script src="assets/js/warehouse.js?v=<?php echo ROSARIO_VERSION; ?>"></script>
	<script src="assets/js/jscalendar/lang/calendar-<?php echo file_exists( 'assets/js/jscalendar/lang/calendar-' . $lang_2_chars . '.js' ) ? $lang_2_chars : 'en'; ?>.js"></script>
	<script>var scrollTop = "<?php echo Preferences( 'SCROLL_TOP' ); ?>";</script>
</head>
<body>
<?php
			// If popup window, verify it is an actual popup.
			if ( $_ROSARIO['is_popup'] ) :
?>
<script>if(window == top  && (!window.opener)) window.location.href = "index.php";</script>
<?php
			// Else if not AJAX request.
			elseif ( $_ROSARIO['not_ajax'] ) :
?>
<div id="wrap">
	<footer id="footer" class="mod">
		<?php require_once 'Bottom.php'; // Include Bottom menu. ?>
	</footer>	
	<div id="menuback" class="mod"></div>
	<aside id="menu" class="mod">
		<?php require_once 'Side.php'; // Include Side menu. ?>
	</aside>

<?php
			endif;
?>
	<div id="body" tabindex="0" role="main" class="mod">
<?php
		break;

		// Footer HTML.
		case 'footer':
?>
<br />
<script>
	var modname = "<?php echo isset( $_ROSARIO['ProgramLoaded'] ) ? $_ROSARIO['ProgramLoaded'] : ''; ?>";
	if (typeof menuStudentID !== 'undefined'
		&& (menuStudentID != "<?php echo UserStudentID(); ?>"
			|| menuStaffID != "<?php echo UserStaffID(); ?>"
			|| menuSchool != "<?php echo UserSchool(); ?>"
			|| menuCoursePeriod != "<?php echo UserCoursePeriod(); ?>")) { 
		ajaxLink( 'Side.php' );
	}
<?php 		if ( ! empty( $_ROSARIO['ProgramLoaded'] ) ) : ?>
	else
		openMenu( modname );
<?php		endif; ?>
</script>
<?php
			// If popup window or if not AJAX request.
			if ( $_ROSARIO['is_popup']
				|| $_ROSARIO['not_ajax'] ) :
?>
	</div><!-- #body -->
<?php
				if ( $_ROSARIO['not_ajax']
					&& ! $_ROSARIO['is_popup'] ) :
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
	} // End switch.
} // End Warehouse().


/**
 * Popup window detection
 *
 * @link http://www.securiteam.com/securitynews/6S02U1P6BI.html
 *
 * @example $_ROSARIO['is_popup'] = isPopup( $modname, $_REQUEST['modfunc'] );
 *
 * @param  string $modname Mod name.
 * @param  string $modfunc Mod function.
 *
 * @return boolean True if popup, else false
 */
function isPopup( $modname, $modfunc )
{
	$is_popup = false;

	/**
	 * Popup window detection.
	 *
	 * FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	 */
	if ( in_array(
		$modname,
		array(
			'misc/ChooseRequest.php',
			'misc/ChooseCourse.php',
			'misc/ViewContact.php',
		)
	)
	|| ( $modname === 'School_Setup/Calendar.php'
		&& $modfunc === 'detail' )
	|| ( in_array(
			$modname,
			array(
				'Scheduling/MassDrops.php',
				'Scheduling/Schedule.php',
				'Scheduling/MassSchedule.php',
				'Scheduling/MassRequests.php',
				'Scheduling/Courses.php',
			)
		)
		&& $modfunc === 'choose_course' ) )
	{
		$is_popup = true;
	}

	return $is_popup;
}
