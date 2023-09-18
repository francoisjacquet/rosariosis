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
	echo _ErrorMessage( $error, 'fatal' );
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
						echo _ErrorMessage( $error, 'fatal' );
					}
				}
			}
		}
	}
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

if ( ! empty( $pg_dumpPath )
	&& empty( $DatabaseDumpPath )
	&& $DatabaseType === 'postgresql' )
{
	// @since 10.0 Rename $pg_dumpPath configuration variable to $DatabaseDumpPath
	$DatabaseDumpPath = $pg_dumpPath;
}

// Check pg_dump binary exists.
if ( ! empty( $DatabaseDumpPath )
	&& $DatabaseType === 'postgresql'
	&& ( ! file_exists( $DatabaseDumpPath )
		|| strpos( basename( $DatabaseDumpPath ), 'pg_dump' ) !== 0 ) )
{
	$error[] = 'The value for $DatabaseDumpPath in the config.inc.php file is not correct. pg_dump utility not found.';
}

// Check mysqldump binary exists.
if ( ! empty( $DatabaseDumpPath )
	&& $DatabaseType === 'mysql'
	&& ( ! file_exists( $DatabaseDumpPath )
		|| strpos( basename( $DatabaseDumpPath ), 'mysqldump' ) !== 0 ) )
{
	$error[] = 'The value for $DatabaseDumpPath in the config.inc.php file is not correct. mysqldump utility not found.';
}

// Check for gd extension.
if ( ! extension_loaded( 'gd' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the gd extension (used to resize and compress images). Please install and activate it.';
}

// Check for zip extension.
if ( ! extension_loaded( 'zip' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the zip extension (used to upload add-ons and by Import add-ons). Please install and activate it.';
}

// Check for xmlrpc extension.
if ( version_compare( PHP_VERSION, '8.0' ) == -1
	&& ! extension_loaded( 'xmlrpc' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the xmlrpc extension (only used to connect to Moodle). Please install and activate it.';
}

// Check for curl extension.
if ( ! extension_loaded( 'curl' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the curl extension (only used to connect to Moodle). Please install and activate it.';
}

// Check for intl extension.
if ( ! extension_loaded( 'intl' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the intl extension. Please install and activate it.';
}

// Check session.auto_start.
if ( (bool) ini_get( 'session.auto_start' ) )
{
	$error[] = 'session.auto_start is set to On in your PHP configuration. See the php.ini file to deactivate it.' . $inipath;
}


echo _ErrorMessage( $error, 'error' );

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
 * @param  string $code  error|fatal.
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
			else
				$return .= '<b><span style="color:#00CC00">Note:</span></b> ';

			$return .= ( ($error[0]) ? $error[0] : $error[1] );
		}
		else
		{
			if ( $code === 'error'
				|| $code === 'fatal' )
			{
				$return .= '<b><span style="color:#CC0000">Errors:</span></b>';
			}
			else
				$return .= '<b><span style="color:#00CC00">Note:</span></b>';

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
}
