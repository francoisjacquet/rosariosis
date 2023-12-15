<?php
/**
 * Warehouse
 *
 * Get configuration
 * Load functions
 * Date format & Time zone
 * Start Session
 * Sanitize $_REQUEST array
 * Internationalization
 * Modules & Plugins
 * Update RosarioSIS
 * Warehouse() function (Output HTML header (including Bottom & Side menus), or footer)
 * isPopup() function (Popup window detection)
 * isAJAX() function (AJAX request detection)
 * ETagCache() function (ETag cache system)
 *
 * @package RosarioSIS
 */

define( 'ROSARIO_VERSION', '11.3.3' );

/**
 * Include config.inc.php file.
 *
 * Do NOT change for require_once, include_once allows the error message to be displayed.
 */

if ( ! include_once 'config.inc.php' )
{
	die( 'config.inc.php file not found. Please read the installation directions.' );
}

if ( empty( $DatabaseType ) )
{
	// @since 10.0 Add $DatabaseType configuration variable
	$DatabaseType = 'postgresql';
}

require_once 'database.inc.php';

/**
 * Optional configuration
 * You can override the following definitions in the config.inc.php file
 */

if ( ! defined( 'ROSARIO_DEBUG' ) )
{
	// Debug mode (for developers): enables notices.
	define( 'ROSARIO_DEBUG', false );
}

error_reporting( ROSARIO_DEBUG ? E_ALL : E_ALL ^ E_NOTICE );

// Server Paths.

if ( ! isset( $RosarioPath ) )
{
	$RosarioPath = __DIR__ . '/';
}

if ( ! isset( $StudentPicturesPath ) )
{
	$StudentPicturesPath = 'assets/StudentPhotos/';
}

if ( ! isset( $UserPicturesPath ) )
{
	$UserPicturesPath = 'assets/UserPhotos/';
}

if ( ! isset( $FileUploadsPath ) )
{
	$FileUploadsPath = 'assets/FileUploads/';
}

if ( ! isset( $LocalePath ) )
{
	// Path were the language packs are stored. You need to restart Apache at each change in this directory.
	$LocalePath = 'locale';
}

if ( ! isset( $ETagCache ) )
{
	// ETag cache system.
	$ETagCache = true;
}

/**
 * Load functions
 */
$functions = glob( 'functions/*.php' );

foreach ( $functions as $function )
{
	require_once $function;
}

if ( $DatabaseType === 'mysql' )
{
	/**
	 * Set MySQL charset to utf8mb4 and collation to utf8mb4_unicode_520_ci
	 *
	 * @since 10.0
	 *
	 * Fix SQL error "Illegal mix of collations (utf8mb4_unicode_520_ci,IMPLICIT)
	 * 	and (utf8mb4_general_ci,IMPLICIT) for operation '='"
	 * @link https://www.php.net/manual/en/mysqli.set-charset.php#121647
	 */
	DBQuery( "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci" );

	/**
	 * Set MySQL mode to accept pipes || as concatenation operator.
	 * For backward-compatibility purpose only, try to always use CONCAT() instead of ||
	 *
	 * @since 10.0
	 * @link https://stackoverflow.com/questions/5975958/string-concatenation-in-mysql#5975980
	 */
	if ( ! ROSARIO_DEBUG )
	{
		DBQuery( "SET @@sql_mode=CONCAT(@@sql_mode,',PIPES_AS_CONCAT')" );
	}
}

/**
 * Date format & Time zone
 */
if ( $DatabaseType === 'postgresql' )
{
	/**
	 * Set PostgreSQL Session Date Format / Datestyle to ISO.
	 *
	 * @since 2.9
	 * @since 10.0 Move query from Date.php to Warehouse.php
	 */
	DBQuery( "SET DATESTYLE='ISO'" );
}

if ( isset( $Timezone ) )
{
	// Time zone.
	// Sets the default time zone used by all date/time functions.
	if ( date_default_timezone_set( $Timezone ) )
	{
		if ( $DatabaseType === 'mysql' )
		{
			/**
			 * Get offset from time zone.
			 *
			 * @link https://stackoverflow.com/questions/25086456/php-convert-string-timezone-format-to-offset-integer#25086526
			 */
			$date_time_zone = new DateTimeZone( $Timezone );
			$date = new DateTime( '', $date_time_zone );
			$offset = $date_time_zone->getOffset( $date );
			$offset = ( $offset < 0 ? '-' : '+' ) . gmdate( 'H:i', abs( $offset ) );

			DBQuery( "SET time_zone='" . $offset . "'" );
		}
		else
		{
			// If valid PHP timezone_identifier, should be OK for PostgreSQL.
			DBQuery( "SET TIMEZONE TO '" . $Timezone . "'" );
		}
	}
}
else
{
	// Fix PHP error if date.timezone ini setting is an invalid time zone identifier.
	date_default_timezone_set( date_default_timezone_get() );
}

// Send email on PHP fatal error.
register_shutdown_function( 'ErrorSendEmail' );

/**
 * Start Session
 */
session_name( 'RosarioSIS' );

// @link http://php.net/manual/en/session.security.php
$cookie_path = dirname( $_SERVER['SCRIPT_NAME'] ) === DIRECTORY_SEPARATOR ?
	'/' : dirname( $_SERVER['SCRIPT_NAME'] ) . '/';

// Fix #316 CSRF security issue set cookie samesite to strict.
// @link https://www.php.net/manual/en/function.session-set-cookie-params.php#125072
$cookie_samesite = 'Strict';

// Cookie secure flag for https.
$cookie_https_only = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ||
	( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 );

if ( PHP_VERSION_ID < 70300 )
{
	// PHP version < 7.3.
	session_set_cookie_params(
		0,
		$cookie_path . '; samesite=' . $cookie_samesite,
		'',
		$cookie_https_only,
		true
	);
}
else
{
	session_set_cookie_params( [
		'lifetime' => 0,
		'path' => $cookie_path,
		'domain' => '',
		'secure' => $cookie_https_only,
		'httponly' => true,
		'samesite' => $cookie_samesite,
	] );
}

session_cache_limiter( 'nocache' );

session_start();

if ( empty( $_SESSION['DefaultSyear'] ) )
{
	// @since 11.1 Copy $DefaultSyear global var to session (once) to prevent errors when edited
	$_SESSION['DefaultSyear'] = $DefaultSyear;
}

if ( empty( $_SESSION['token'] ) )
{
	/**
	 * Add CSRF token to protect unauthenticated requests
	 *
	 * @since 9.0
	 * @since 11.0 Fix PHP fatal error if openssl PHP extension is missing
	 * @link https://stackoverflow.com/questions/5207160/what-is-a-csrf-token-what-is-its-importance-and-how-does-it-work
	 */
	$_SESSION['token'] = bin2hex( function_exists( 'openssl_random_pseudo_bytes' ) ?
		openssl_random_pseudo_bytes( 16 ) :
		( function_exists( 'random_bytes' ) ? random_bytes( 16 ) :
			mb_substr( sha1( rand( 999999999, 9999999999 ), true ), 0, 16 ) ) );
}

if ( empty( $_SESSION['STAFF_ID'] )
	&& empty( $_SESSION['STUDENT_ID'] )
	&& ( basename( $_SERVER['SCRIPT_NAME'] ) === 'Modules.php'
		|| basename( $_SERVER['SCRIPT_NAME'] ) === 'Bottom.php'
		|| basename( $_SERVER['SCRIPT_NAME'] ) === 'Side.php' ) )
{
	// Logout if no Staff or Student session ID.
	/**
	 * Redirect to Modules.php URL after login.
	 *
	 * @since 3.8
	 */
	$redirect_to = basename( $_SERVER['SCRIPT_NAME'] ) === 'Modules.php' ?
		'&redirect_to=' . urlencode( $_SERVER['QUERY_STRING'] ) : '';

	// Redirection is done in Javascript in case current request is AJAX.
	?>
	<script>window.location.href = "index.php?modfunc=logout" +
		<?php echo json_encode( $redirect_to ); ?> +
		"&token=" + <?php echo json_encode( $_SESSION['token'] ); ?>;</script>
	<?php
	exit;
}

/**
 * Array recursive walk (values AND keys!)
 *
 * @since 7.6 Fix #308 sanitize key. Pass array keys through function.
 *
 * @param  array  $array    Array by reference.
 * @param  string $function Function name.
 * @return array  &$array Array passed through $function function
 */
function array_rwalk( &$array, $function )
{
	$key = array_keys( $array );

	$size = count( $key );

	for ( $i = 0; $i < $size; $i++ )
	{
		if ( is_array( $array[$key[$i]] ) )
		{
			array_rwalk( $array[$key[$i]], $function );
		}
		else
		{
			$array[$key[$i]] = $function( $array[$key[$i]] );
		}

		// Key is also passed through $function function.
		$fkey = $function( $key[$i] );

		if ( $fkey != $key[$i] ) // Weak comparison so we do not change integer value type.
		{
			// New key, order in array is lost (added last), sorry.
			$array[$fkey] = $array[$key[$i]];

			// Key passed through function differs, unset original key.
			unset( $array[$key[$i]] );
		}
	}
}

/**
 * Limit $_POST array size to a maximum of 16MB
 *
 * $_POST array size is limited by PHP post_max_size configuration option
 * But this includes $_FILES as well & post_max_size must be greater than upload_max_filesize
 * One may want to be able to upload a 100MB file, but may not want the $_POST var,
 * with for example the text or HTML of a textarea to be 100MB and later stored in database.
 *
 * @link https://huntr.dev/bounties/430aedac-c7d9-4acb-9bab-bcc0595d9e95/
 */
if ( ! defined( 'ROSARIO_POST_MAX_SIZE_LIMIT' ) )
{
	/**
	 * Fix a limit of 16MB based on MySQL max_allowed_packet default limit
	 * Limit size can be overriden in the config.inc.php file
	 */
	define( 'ROSARIO_POST_MAX_SIZE_LIMIT', 16 * 1024 * 1024 ); // 16MB in bytes.
}

if ( $_POST
	&& strlen( serialize( $_POST ) ) > ROSARIO_POST_MAX_SIZE_LIMIT )
{
	$post_max_size_limit = function( $value ) {
		if ( strlen( $value ) > ( ROSARIO_POST_MAX_SIZE_LIMIT / 4 ) )
		{
			// Reset value > limit / 4, or else we would send it in the HackingLog email!
			return 'ROSARIO_POST_MAX_SIZE_LIMIT / 4 reached.';
		}

		return $value;
	};

	array_rwalk( $_POST, $post_max_size_limit );

	array_rwalk( $_REQUEST, $post_max_size_limit );

	require_once 'ProgramFunctions/HackingLog.fnc.php';

	// Do not translate.
	$error[] = 'You are submitting too much data: over the ' .
		( ROSARIO_POST_MAX_SIZE_LIMIT / 1024 / 1024 ) .
		'M limit. Try reducing the data you are submitting.';

	HackingLog();
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

if ( ! empty( $_REQUEST['locale'] )
	&& in_array( $_REQUEST['locale'], $RosarioLocales ) )
{
	$_SESSION['locale'] = $_REQUEST['locale'];
}
elseif ( empty( $_SESSION['locale'] ) )
{
	$_SESSION['locale'] = $RosarioLocales[0]; // English?
}

$locale = $_SESSION['locale'];

putenv( 'LC_ALL=' . $locale );

function_exists( '_setlocale' ) ?
	// PHP Compatibility: MoTranslator.
	_setlocale( LC_ALL, $locale ) :
	setlocale( LC_ALL, $locale );

// Numeric separator ".".
setlocale( LC_NUMERIC, 'C', 'english', 'en_US', 'en_US.utf8', 'en_US.UTF-8' );

if ( $locale === 'tr_TR.utf8' )
{
	// Bugfix for Turkish characters conversion.
	setlocale( LC_CTYPE, 'C', 'english', 'en_US', 'en_US.utf8', 'en_US.UTF-8' );
}

// Binds the messages domain to the locale folder.
bindtextdomain( 'rosariosis', $LocalePath );

// Ensures text returned is utf-8, quite often this is iso-8859-1 by default.
bind_textdomain_codeset( 'rosariosis', 'UTF-8' );

// Sets the domain name, this means gettext will be looking for a file called rosariosis.mo.
textdomain( 'rosariosis' );

if ( mb_internal_encoding() !== 'UTF-8' )
{
	// Multibyte strings: check if not UTF-8 first to avoid cost of setting.
	mb_internal_encoding( 'UTF-8' );
}

if ( ROSARIO_DEBUG )
{
	require_once 'ProgramFunctions/Debug.fnc.php';

	// @since 5.0 Load Kint.
	Kint();
}
else
{
	function d()
	{
		// Prevent PHP Fatal error if Kint debug d() function not loaded.
	}
}

/**
 * Update RosarioSIS
 * Automatically runs after manual files update
 * To apply eventual incremental DB updates
 *
 * @see ProgramFunctions/Update.fnc.php
 * @since 2.9
 */
// Check if version in DB < ROSARIO_VERSION.

if ( version_compare( Config( 'VERSION' ), ROSARIO_VERSION, '<' ) )
{
	require_once 'ProgramFunctions/Update.fnc.php';

	// Run Update() to apply updates if any.
	Update();
}

/**
 * Modules
 *
 * Core modules (packaged with RosarioSIS): cannot be deleted.
 */
$RosarioCoreModules = [
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
];

$RosarioModules = unserialize( Config( 'MODULES' ) );

$non_core_modules = array_diff_key( $RosarioModules, array_flip( $RosarioCoreModules ) );

_LoadAddons( $non_core_modules, 'modules/' );

/**
 * Plugins
 *
 * Core plugins (packaged with RosarioSIS): cannot be deleted.
 */
$RosarioCorePlugins = [
	'Moodle',
];

$RosarioPlugins = unserialize( Config( 'PLUGINS' ) );

_LoadAddons( $RosarioPlugins, 'plugins/' );

/**
 * Load not core modules & plugins
 * (functions & locale)
 * Deactivate if does not exist
 *
 * Local function
 *
 * @param  array  $addons Non core addons (Plugins or Modules).
 * @param  string $folder Plugin or Module folder.
 * @return void
 */
function _LoadAddons( $addons, $folder )
{
	global $RosarioModules,
		$RosarioPlugins;

	/**
	 * Check if non core activated modules exist.
	 * Load locale.
	 * Load functions (optional).
	 */

	foreach ( (array) $addons as $addon => $activated )
	{
		if ( ! $activated )
		{
			continue;
		}

		if ( $folder === 'modules/'
			&& ! file_exists( $folder . $addon . '/Menu.php' ) )
		{
			// If module does not exist, deactivate it.
			$RosarioModules[$addon] = false;

			continue;
		}

		$addon_functions = $folder . $addon . '/functions.php';

		if ( file_exists( $addon_functions ) )
		{
			require_once $addon_functions;
		}
		elseif ( $folder === 'plugins/' )
		{
			// If plugin does not exist, deactivate it.
			$RosarioPlugins[$addon] = false;

			continue;
		}

		// Load addon locale.
		$locale_path = $folder . $addon . '/locale';

		if ( ! is_dir( $locale_path ) )
		{
			continue;
		}

		// Binds the messages domain to the locale folder.
		bindtextdomain( $addon, $locale_path );

		// Ensures text returned is utf-8, quite often this is iso-8859-1 by default.
		bind_textdomain_codeset( $addon, 'UTF-8' );
	}
}

/**
 * Output HTML header (including Bottom & Side menus), or footer
 *
 * @example  Warehouse( 'header' );
 *
 * @since 3.8 Warehouse header head hook
 * @since 3.8 Warehouse footer hook
 * @since 4.4 Warehouse header hook
 * @since 6.0 Warehouse Header Javascripts
 *
 * @global $_ROSARIO  Uses $_ROSARIO['ProgramLoaded'] & $_ROSARIO['page']
 *
 * @uses isPopup()
 * @uses isAJAX()
 * @uses ETagCache()
 *
 * @param string $mode 'header' or 'footer'.
 */
function Warehouse( $mode )
{
	global $_ROSARIO;

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		if ( $mode === 'header' )
		{
			// Start buffer.
			ob_start();
		}

		// Printing PDF, skip, see PDF.fnc.php.

		return;
	}

	switch ( $mode )
	{
		// Header HTML.
		case 'header':
			ETagCache( 'start' );

			if ( isAJAX() )
			{
				// If jQuery not available, log out.

				if ( $_ROSARIO['page'] === 'modules' ): ?>
<script>if (!window.$) window.location.href = 'index.php?modfunc=logout&token=' + <?php echo json_encode( $_SESSION['token'] ); ?>;</script>
				<?php endif;

				// AJAX: we only need to generate #body content.
				break;
			}

			$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

			// Right to left direction.
			$RTL_languages = [ 'ar', 'he', 'dv', 'fa', 'ur', 'ps' ];

			$dir_RTL = in_array( $lang_2_chars, $RTL_languages ) ? ' dir="RTL"' : '';
			?>
<!doctype html>
<html lang="<?php echo $lang_2_chars; ?>"<?php echo $dir_RTL; ?>>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width">
	<title><?php echo ParseMLField( Config( 'TITLE' ) ); ?></title>
	<link rel="icon" href="favicon.ico" sizes="32x32">
	<link rel="icon" href="apple-touch-icon.png" sizes="128x128">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<link rel="stylesheet" href="assets/themes/<?php echo Preferences( 'THEME' ); ?>/stylesheet.css?v=<?php echo ROSARIO_VERSION; ?>">
	<style>.highlight,.highlight-hover:hover{background-color:<?php echo Preferences( 'HIGHLIGHT' ); ?> !important;}</style>
	<?php

			if ( $_ROSARIO['page'] === 'modules'
				|| $_ROSARIO['page'] === 'first-login'
				|| $_ROSARIO['page'] === 'create-account'
				|| $_ROSARIO['page'] === 'password-reset' )
			{
				// @since 6.0 Warehouse Header Javascripts.
				WarehouseHeaderJS();
			}

			/**
			 * Hook.
			 *
			 * Add your extra module/plugin JS and/or CSS (dependencies) to HTML head here.
			 *
			 * @since 3.8
			 *
			 * @since 4.5.1 Move Header head action hook outisde page condition.
			 */
			do_action( 'Warehouse.php|header_head' );
		?>
	<noscript>
		<meta http-equiv="REFRESH" content="0; url=<?php echo URLEscape( 'index.php?modfunc=logout&amp;reason=javascript&amp;token=' . $_SESSION['token'] ); ?>">
	</noscript>
</head>
<body class="<?php echo AttrEscape( $_ROSARIO['page'] ); ?>">
<?php
			if ( $_ROSARIO['page'] === 'modules' ):
				// If popup window, verify it is an actual popup.
				if ( isPopup() ):
				?>
				<script>if(window == top  && (!window.opener)) window.location.href = "Modules.php?modname=misc/Portal.php";</script>
					<?php // @since 10.0 Close popup if no UserSchool in session, happens on login redirect.
					if ( ! UserSchool() ) : ?>
					<script>window.close();</script>
					<?php endif;
				else: ?>
<div id="wrap">
	<footer id="footer" class="mod">
		<?php require_once 'Bottom.php'; // Include Bottom menu. ?>
	</footer>
	<aside id="menu" class="mod">
		<?php require_once 'Side.php'; // Include Side menu. ?>
	</aside>

<?php
				endif;
			endif;

			?>
	<div id="body" tabindex="0" role="main" class="mod">
<?php
			/**
			 * Hook.
			 *
			 * Add your extra module/plugin HTML to body here.
			 *
			 * @since 4.4
			 */
			do_action( 'Warehouse.php|header' );

		break;

		// Footer HTML.
		case 'footer':
			?>
<br>
<?php

			if ( isset( $_ROSARIO['page'] )
				&& $_ROSARIO['page'] === 'modules' ): ?>
<script>
	var modname = "<?php echo issetVal( $_ROSARIO['ProgramLoaded'], '' ); ?>";
	if (typeof menuStudentID !== 'undefined'
		&& (menuStudentID != "<?php echo UserStudentID(); ?>"
			|| menuStaffID != "<?php echo UserStaffID(); ?>"
			|| menuSchool != "<?php echo UserSchool(); ?>"
			|| menuMP != "<?php echo UserMP(); ?>"
			|| menuCoursePeriod != "<?php echo UserCoursePeriod(); ?>")) {
		ajaxLink( 'Side.php?sidefunc=update' );
	}
</script>
<?php
			/**
			 * Hook.
			 *
			 * Add your extra module/plugin JS (dependencies) to HTML footer here.
			 *
			 * @since 3.8
			 * @since 4.4 Hook always runs (no conditions).
			 */
			do_action( 'Warehouse.php|footer' );

			// If not AJAX request.
			if ( ! isAJAX() ):
			?>
	</div><!-- #body -->
	<div class="ajax-error"></div>
<?php

				if ( ! isPopup() ):
				?>
	</div><!-- #wrap -->
				<?php endif;

			?>
</body></html>
			<?php endif;

			if ( ! isPopup() ):

				// require_once 'ProgramFunctions/Help.fnc.php';

				// Check if module has help (not default).
				//$has_help_text = GetHelpTextRaw( $_REQUEST['modname'] );

				/*if ( $has_help_text )
				{
					var_dump($_REQUEST['modname']);
					echo 'Has Help!!!';
				}*/

			endif;

			elseif ( ! isAJAX() ): // Other pages (not modules).

				?>
		</div><!-- #body -->
	</body></html>
			<?php endif;

			ETagCache( 'stop' );

			break;
	}

	// End switch.
}

/**
 * Warehouse Header Javascripts
 * Loads jQuery, plugins, warehouse & calendar lang JS.
 * Loads theme's scripts.js file if any found.
 *
 * @since 6.0
 * @since 7.6 JS remove warehouse.min.js & include warehouse.js inside plugins.min.js
 */
function WarehouseHeaderJS()
{
	$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

	?>
	<script src="assets/js/jquery.js?v=2.2.4"></script>
	<script src="assets/js/plugins.min.js?v=<?php echo ROSARIO_VERSION; ?>"></script>
	<script src="assets/js/jscalendar/lang/calendar-<?php echo file_exists( 'assets/js/jscalendar/lang/calendar-' . $lang_2_chars . '.js' ) ? $lang_2_chars : 'en'; ?>.js"></script>
	<?php
	// Add scripts.js file from theme if any found.
	if ( file_exists( 'assets/themes/' . Preferences( 'THEME' ) . '/scripts.js' ) ): ?>
		<script src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/scripts.js"></script>
	<?php endif;
}

/**
 * Popup window detection
 *
 * @todo deprecate & Remove popup, use soft popup with featherlight or https://victordiego.com/lightbox/.
 *
 * Set it once in Modules.php:
 * @example isPopup( $modname, $_REQUEST['modfunc'] );
 * Later call it:
 * @example if ( isPopup() )
 * @link http://www.securiteam.com/securitynews/6S02U1P6BI.html
 *
 * @param  string  $modname Mod name. Optional, defaults to ''.
 * @param  string  $modfunc Mod function. Optional, defaults to ''.
 * @return boolean True if popup, else false
 */
function isPopup( $modname = '', $modfunc = '' )
{
	static $is_popup = null;

	if ( ! is_null( $is_popup ) )
	{
		return $is_popup;
	}

	$is_popup = false;

	if ( ! $modname )
	{
		return $is_popup;
	}

	/**
	 * Popup window detection.
	 *
	 * FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	 */

	if ( in_array(
		$modname,
		[
			'misc/ChooseRequest.php',
			'misc/ChooseCourse.php',
			'misc/ViewContact.php',
		]
	)
		|| ( $modname === 'School_Setup/Calendar.php'
			&& $modfunc === 'detail' )
		|| ( in_array(
			$modname,
			[
				'Scheduling/MassDrops.php',
				'Scheduling/Schedule.php',
				'Scheduling/MassSchedule.php',
				'Scheduling/MassRequests.php',
				'Scheduling/Courses.php',
			]
		)
			&& $modfunc === 'choose_course' ) )
	{
		$is_popup = true;
	}

	return $is_popup;
}

/**
 * AJAX request detection
 *
 * @example if ( isAJAX() )
 * @since 3.0.1
 *
 * @return boolean True if is AJAX, else false
 */
function isAJAX()
{
	static $is_ajax = null;

	if ( ! is_null( $is_ajax ) )
	{
		return $is_ajax;
	}

	$is_ajax = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] )
		&& $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

	return $is_ajax;
}

/**
 * ETag cache system.
 * Start buffer, or stop buffer
 * and calculate ETag:
 * If ETag === If-None-Match, then send 403
 * Else send content (buffer) + ETag.
 * If no mode is set, will only check if $ETagCache activated.
 *
 * @global $ETagCache ETag cache system.
 * @since  3.1
 *
 * @param  string  $mode Mode: start|stop. Optional, defaults to ''.
 * @return boolean True if ETagCache activated, else false.
 */
function ETagCache( $mode = '' )
{
	global $ETagCache;

	static $ob_started = false;

	if ( ! $ETagCache )
	{
		return false;
	}

	if ( $mode === 'start'
		&& ! $ob_started )
	{
		// Start buffer (to generate ETag).
		$ob_started = ob_start();
	}
	elseif ( $mode === 'stop'
		&& $ob_started )
	{
		// Stop & get buffer buffer (to generate ETag).
		$etag_buffer = ob_get_clean();

		/**
		 * Generate ETag
		 * Weak ETag ("W/").
		 *
		 * @link https://en.wikipedia.org/wiki/HTTP_ETag
		 */
		$etag = 'W/' . md5( $etag_buffer );

		// If-None-Match header sent by client.

		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] )
			&& $_SERVER['HTTP_IF_NONE_MATCH'] === $etag )
		{
			/**
			 * private means can be stored only in a private cache (e.g. local caches in browsers)
			 * no-cache does not mean "do not cache" but requires the cache to revalidate it before reuse
			 *
			 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
			 */
			header( "Cache-Control: private, no-cache" );

			// Page cached: send 304 + empty content.
			header( $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified' );

			exit;
		}
		else
		{
			// FJ fix headers already sent error when program outputs buffer.

			if ( ! headers_sent() )
			{
				header( "Cache-Control: private, no-cache" );

				// Send ETag + content (buffer).
				header( 'ETag: ' . $etag );
			}

			echo $etag_buffer;
		}
	}

	return true;
}


/**
 * Null coalesce.
 * Useful to prevent PHP undefined index / variable notices.
 * Equivalent to:
 * `isset( $var ) ? $var : null`
 *
 * @example $extra['SELECT'] = issetVal( $extra['SELECT'], '' );
 *
 * @since 5.0
 *
 * @todo Migrate to PHP7 ?? operator.
 * @link https://www.php.net/manual/en/migration70.new-features.php#migration70.new-features.null-coalesce-op
 *
 * @param  mixed &$var    Variable.
 * @param  mixed $default Default value if undefined. Defaults to null.
 * @return mixed Variable or default.
 */
function issetVal( &$var, $default = null )
{
	return ( isset( $var ) ) ? $var : $default;
}
