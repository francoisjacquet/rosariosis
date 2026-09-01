<?php
/**
 * Diagnostic
 *
 * Check for missing PHP extensions, files or misconfigurations
 *
 * TRANSLATION: do NOT translate these error messages since they need to stay in English for technical support.
 *
 * @package RosarioSIS
 */

$error = [];

$warning = [];

// FJ check PHP version.
if ( version_compare( PHP_VERSION, '5.5.9' ) == -1 )
{
	$error[] = 'RosarioSIS requires PHP 5.5.9 to run, your version is : ' . PHP_VERSION;
}

// FJ verify PHP extensions and php.ini.
$inipath = php_ini_loaded_file();

if ( $inipath )
{
	$inipath = ' Loaded php.ini: ' . $inipath;
}
else
	$inipath = ' Note: No php.ini file is loaded!';

if ( count( $error ) )
{
	_ErrorMessage( $error, 'fatal' );
}


if ( ! file_exists( './Warehouse.php' ) )
{
	$error[] = 'The diagnostic.php file needs to be in the RosarioSIS directory to be able to run. Please move it there, and run it again.';
}
elseif ( ! include_once './config.inc.php' )
{
	$error[] = 'config.inc.php file not found. Please read the installation directions.';
}
else
{
	if ( ! @opendir( $RosarioPath . 'functions' ) )
	{
		$error[] = 'The value for $RosarioPath in the config.inc.php file is not correct or else the functions directory does not have the correct permissions to be read by the webserver. Make sure $RosarioPath points to the RosarioSIS installation directory and that it is readable by the `' . $_SERVER['USER'] . '` user.';
	}

	if ( empty( $DatabaseType ) )
	{
		// @since 10.0 Add $DatabaseType configuration variable
		$DatabaseType = 'postgresql';
	}

	if ( $DatabaseType !== 'postgresql'
		&& $DatabaseType !== 'mysql' )
	{
		$error[] = 'The value for $DatabaseType in the config.inc.php file is not correct. $DatabaseType value is either postgresql or mysql.';
	}
	elseif ( $DatabaseType === 'postgresql'
		&& ! function_exists( 'pg_connect' ) )
	{
		$error[] = 'PHP extensions: RosarioSIS relies on the pgsql extension (used to connect to the PostgreSQL database). Please install and activate it.';
	}
	elseif ( $DatabaseType === 'mysql'
		&& ! function_exists( 'mysqli_connect' ) )
	{
		$error[] = 'PHP extensions: RosarioSIS relies on the mysql (or mysqli) extension (used to connect to the MySQL database). Please install and activate it.';
	}
	else
	{
		require_once './database.inc.php';

		$db_connection = db_start( false );

		if ( ! $db_connection )
		{
			$error[] = 'RosarioSIS cannot connect to the ' . ( $DatabaseType === 'mysql' ? 'MySQL' : 'PostgreSQL' ) . ' database server. Please review the database configuration variables in the config.inc.php file.';

			$error[] = ( $DatabaseType === 'mysql' ? mysqli_connect_error() : error_get_last()['message'] );
		}
		else
		{
			$result = db_query( 'SELECT * FROM config', false );

			if ( $result === false )
			{
				$errstring = ( $DatabaseType === 'mysql' ?
					mysqli_errno( $db_connection ) . ' ' . mysqli_error( $db_connection ) :
					pg_last_error( $db_connection ) );

				if ( mb_strpos( $errstring, 'permission denied' ) !== false )
				{
					$error[] = 'The database was created with the wrong permissions. The user specified in the config.inc.php file does not have permission to access the database.';
				}
				elseif ( mb_strpos( $errstring, 'elation "config" does not exist' ) !== false
					|| ( mb_strpos( $errstring, '1146' ) !== false && mb_strpos( $errstring, 'config' ) !== false ) ) // MySQL
				{
					$error[] = 'At least one of the database tables does not exist. To install the database, access the <a href="InstallDatabase.php">InstallDatabase.php</a> page.';
				}
				elseif ( $errstring )
				{
					$error[] = $errstring;
				}
			}
			else
			{
				// OK, we can connect to database & config table exists.
				$result = db_query( "SELECT * FROM staff WHERE SYEAR='" . $DefaultSyear . "'" );

				if ( ! db_fetch_row( $result ) )
				{
					$error[] = 'The value for $DefaultSyear in the config.inc.php file is incorrect.';
				}
				else
				{
					require_once './Warehouse.php';

					// OK, $DefaultSyear is correct so we can login.
					if ( ( isset( $_SESSION['STAFF_ID'] )
							&& $_SESSION['STAFF_ID'] < 1 )
						|| User( 'PROFILE' ) !== 'admin' )
					{
						// @since 9.0 Restrict diagnostic access to logged in admin.
						$error[] = 'Please login as an administrator before accessing the diagnostic.php page.';

						// Exit.
						_ErrorMessage( $error, 'fatal' );
					}
				}
			}
		}
	}
}

if ( empty( $RosarioURL )
	|| ! filter_var( $RosarioURL, FILTER_VALIDATE_URL ) )
{
	// @since 13.0 Security fix #396 add $RosarioURL config variable
	$error[] = 'The value for $RosarioURL in the config.inc.php file is not correct.';
}
elseif ( strtolower( rtrim( $RosarioURL, '/' ) ) !== strtolower( _rosarioURL() ) )
{
	// @since 13.0 Security fix #396 add $RosarioURL config variable
	$warning[] = 'The value for $RosarioURL in the config.inc.php file may be incorrect.';
}

if ( ! is_array( $RosarioLocales )
	|| empty( $RosarioLocales ) )
{
	$error[] = 'The value for $RosarioLocales in the config.inc.php file is not correct.';
}

// Check wkhtmltopdf binary exists.
if ( ! empty( $wkhtmltopdfPath )
	&& ( ! file_exists( $wkhtmltopdfPath )
		|| strpos( basename( $wkhtmltopdfPath ), 'wkhtmltopdf' ) !== 0 ) )
{
	$error[] = 'The value for $wkhtmltopdfPath in the config.inc.php file is not correct.';
}

// Check for gd extension.
if ( ! extension_loaded( 'gd' ) )
{
	$warning[] = 'PHP extensions: RosarioSIS relies on the gd extension (used to resize and compress images). Please install and activate it.';
}

// Check for zip extension.
if ( ! extension_loaded( 'zip' ) )
{
	$warning[] = 'PHP extensions: RosarioSIS relies on the zip extension (used to upload add-ons). Please install and activate it.';
}

// Check for curl extension.
if ( ! extension_loaded( 'curl' ) )
{
	$warning[] = 'PHP extensions: RosarioSIS relies on the curl extension (used to make external API calls). Please install and activate it.';
}

// Check for intl extension.
if ( ! extension_loaded( 'intl' ) )
{
	$warning[] = 'PHP extensions: RosarioSIS relies on the intl extension (used for internationalization). Please install and activate it.';
}

// Check for gettext extension (not on Windows).
if ( ! extension_loaded( 'gettext' )
	&& strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' )
{
	$warning[] = 'PHP extensions: RosarioSIS relies on the gettext extension (used for translations). Please install and activate it.';
}

// Check session.auto_start.
if ( (bool) ini_get( 'session.auto_start' ) )
{
	$error[] = 'session.auto_start is set to On in your PHP configuration. See the php.ini file to deactivate it.' . $inipath;
}


echo _ErrorMessage( $error, 'error' );

echo _ErrorMessage( $warning, 'warning' );

if ( ! count( $error ) )
{
	echo '<h3>Your RosarioSIS installation is properly configured.</h3>';
}


/**
 * Error Message
 *
 * Local function
 *
 * @param  array  $error Errors.
 * @param  string $code  error|fatal|warning.
 *
 * @return string Errors HTML, exits if fatal error
 */
function _ErrorMessage( $error, $code = 'error' )
{
	if ( $error )
	{
		$return = '<table cellpadding="10"><tr><td style="text-align:left;"><p style="font-size:larger;">';

		if ( count( $error ) == 1 )
		{
			if ( $code === 'error'
				|| $code === 'fatal' )
			{
				$return .= '<b><span style="color:#CC0000">Error:</span></b> ';
			}
			elseif ( $code === 'warning' )
			{
				$return .= '<b><span style="color:orange">Warning:</span></b> ';
			}
			else
				$return .= '<b><span style="color:#00CC00">Note:</span></b> ';

			$return .= reset( $error );
		}
		else
		{
			if ( $code === 'error'
				|| $code === 'fatal' )
			{
				$return .= '<b><span style="color:#CC0000">Errors:</span></b>';
			}
			elseif ( $code === 'warning' )
			{
				$return .= '<b><span style="color:orange">Warnings:</span></b> ';
			}
			else
				$return .= '<b><span style="color:#00CC00">Notes:</span></b>';

			$return .= '<ul>';

			foreach ( (array) $error as $value )
			{
				$return .= '<li>' . $value . '</li>';
			}

			$return .= '</ul>';
		}

		$return .= '</p></td></tr></table><br />';

		if ( $code === 'fatal' )
		{
			echo $return;

			exit;
		}

		return $return;
	}

	return '';
}

function _rosarioURL()
{
	$url = 'http://';

	if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) )
	{
		// Fix detect https inside Docker or behind reverse proxy.
		$url = 'https://';
	}

	$url .= $_SERVER['SERVER_NAME'];

	if ( $_SERVER['SERVER_PORT'] != '80'
		&& $_SERVER['SERVER_PORT'] != '443' )
	{
		$url .= ':' . $_SERVER['SERVER_PORT'];
	}

	$url .= dirname( $_SERVER['SCRIPT_NAME'] ) === DIRECTORY_SEPARATOR ?
		'' : dirname( $_SERVER['SCRIPT_NAME'] );

	return $url;
}
