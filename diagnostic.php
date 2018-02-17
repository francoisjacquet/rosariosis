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

session_name( 'RosarioSIS' );

session_start();

$error = array();

// FJ check PHP version.
if ( version_compare( PHP_VERSION, '5.3.2' ) == -1 )
{
	$error[] = 'RosarioSIS requires PHP 5.3.2 to run, your version is : ' . PHP_VERSION;
}

if ( ! isset( $_SESSION['STAFF_ID'] ) )
{
	$unset_username = true;
	$_SESSION['USERNAME'] = 'diagnostic';
	$_SESSION['STAFF_ID'] = '-1';
}

// FJ verify PHP extensions and php.ini.
$inipath = php_ini_loaded_file();

if ( $inipath )
{
	$inipath = ' Loaded php.ini: ' . $inipath;
}
else
	$inipath = ' Note: No php.ini file is loaded!';

// Check for pgsql extension.
if ( ! extension_loaded( 'pgsql' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the pgsql (PostgreSQL) extension. Please install and activate it.';
}

// Check for gettext extension.
if ( ! extension_loaded( 'gettext' )
	|| ! function_exists( 'bindtextdomain' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the gettext extension. Please install and activate it.';
}

// Check for mbstring extension.
if ( ! extension_loaded( 'mbstring' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the mbstring extension. Please install and activate it.';
}

// Check for gd extension.
if ( ! extension_loaded( 'gd' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the gd extension. Please install and activate it.';
}

// Check for xml extension.
if ( ! extension_loaded( 'xml' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the xml extension. Please install and activate it.';
}

if ( count( $error ) )
{
	echo _ErrorMessage( $error, 'fatal' );
}


if ( ! file_exists( './Warehouse.php' ) )
{
	$error[] = 'The diagnostic.php file needs to be in the RosarioSIS directory to be able to run. Please move it there, and run it again.';
}
else
{
	require_once './Warehouse.php';

	if ( ! @opendir( $RosarioPath . '/functions' ) )
	{
		$error[] = 'The value for $RosarioPath in the config.inc.php file is not correct or else the functions directory does not have the correct permissions to be read by the webserver. Make sure $RosarioPath points to the RosarioSIS installation directory and that it is readable by all users.';
	}

	if ( ! function_exists( 'pg_connect' ) )
	{
		$error[] = 'The pgsql extension (see the php.ini file) is not activated OR PHP was not compiled with PostgreSQL support. You may need to recompile PHP using the --with-pgsql option for RosarioSIS to work.';
	}
	else
	{
		/**
		 * Fix pg_connect(): Unable to connect to PostgreSQL server:
		 * could not connect to server:
		 * No such file or directory Is the server running locally
		 * and accepting connections on Unix domain socket "/tmp/.s.PGSQL.5432"
		 *
		 * Always set host, force TCP.
		 *
		 * @since 3.8
		 */
		$connectstring = 'host=' . $DatabaseServer . ' ';

		if ( $DatabasePort !== '5432' )
		{
			$connectstring .= 'port=' . $DatabasePort .' ';
		}

		$connectstring .= 'dbname=' . $DatabaseName . ' user=' . $DatabaseUsername;

		if ( $DatabasePassword !== '' )
		{
			$connectstring .= ' password=' . $DatabasePassword;
		}

		$connection = pg_connect( $connectstring );

		if ( ! $connection )
		{
			$error[] = 'RosarioSIS cannot connect to the PostgreSQL database. Either Postgres is not running, it was not started with the -i option, or connections from this host are not allowed in the pg_hba.conf file. Last Postgres Error: ' . pg_last_error();
		}
		else
		{
			$result = @pg_exec( $connection, 'SELECT * FROM CONFIG' );

			if ( $result === false )
			{
				$errstring = pg_last_error( $connection );

				if ( mb_strpos( $errstring, 'permission denied' ) !== false )
				{
					$error[] = 'The database was created with the wrong permissions. The user specified in the config.inc.php file does not have permission to access the rosario database. Use the super-user (postgres) or recreate the database adding \connect - YOUR_USERNAME to the top of the rosariosis.sql file.';
				}
				elseif ( mb_strpos( $errstring, 'elation "config" does not exist' ) !== false )
				{
					$error[] = 'At least one of the tables does not exist. Make sure you ran the rosariosis.sql file as described in the INSTALL.md file.';
				}
				elseif ( $errstring )
				{
					$error[] = $errstring;
				}
			}

			$result = @pg_exec( $connection, "SELECT * FROM STAFF WHERE SYEAR='" . $DefaultSyear . "'" );

			if ( ! pg_fetch_all( $result ) )
			{
				$error[] = 'The value for $DefaultSyear in the config.inc.php file is incorrect.';
			}

			if ( ! is_array( $RosarioLocales )
				|| empty( $RosarioLocales ) )
			{
				$error[] = 'The value for $RosarioLocales in the config.inc.php file is not correct.';
			}
		}
	}

	// FJ check wkhtmltopdf binary exists.
	if ( ! empty( $wkhtmltopdfPath )
		&& ( ! file_exists( $wkhtmltopdfPath )
			|| strpos( basename( $wkhtmltopdfPath ), 'wkhtmltopdf' ) !== 0 ) )
	{
		$error[] = 'The value for $wkhtmltopdfPath in the config.inc.php file is not correct.';
	}

	// FJ check pg_dump binary exists.
	if ( ! empty( $pg_dumpPath )
		&& ( ! file_exists( $pg_dumpPath )
			|| strpos( basename( $pg_dumpPath ), 'pg_dump' ) !== 0 ) )
	{
		$error[] = 'The value for $pg_dumpPath in the config.inc.php file is not correct.';
	}

}

// Check for xmlrpc extension.
if ( ! extension_loaded( 'xmlrpc' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the xmlrpc extension (only used to connect to Moodle). Please install and activate it.';
}

// Check for curl extension.
if ( ! extension_loaded( 'curl' ) )
{
	$error[] = 'PHP extensions: RosarioSIS relies on the curl extension (only used to connect to Moodle). Please install and activate it.';
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

if ( $unset_username )
{
	unset( $_SESSION['USERNAME'] );
	unset( $_SESSION['STAFF_ID'] );
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

			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
				&& function_exists( 'Warehouse' ) )
			{
				Warehouse( 'footer' );
			}

			exit;
		}

		return $return;
	}
}
