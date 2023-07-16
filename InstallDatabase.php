<?php
/**
 * Install RosarioSIS database
 *
 * Please create your database first
 * and then fill in the details in the config.inc.php file.
 *
 * @since 4.8.3
 */

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

// rosariosis_[lang].sql files available for database translation.
$lang = [
	'fr' => 'French',
	'pt_BR' => 'Portuguese (Brazil)',
	'es' => 'Spanish',
];

// Test if database is already installed first.
if ( _configTableCheck() )
{
	$result = db_query( "SELECT CONFIG_VALUE
	FROM config
	WHERE TITLE='LOGIN';" );

	$config_login = db_fetch_row( $result );

	if ( empty( $_POST['lang'] )
		|| ! in_array( $_POST['lang'], array_keys( $lang ) )
		|| $config_login['CONFIG_VALUE'] !== 'No' )
	{
		die( 'Database already installed.' );
	}

	if ( $DatabaseType === 'mysql' )
	{
		// @since 10.2 MySQL fix character encoding when translating database
		db_query( "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci" );

		// @since 10.4.3 MySQL always use InnoDB (default), avoid MyISAM
		db_query( "SET default_storage_engine=InnoDB;" );
	}

	$addons_sql = $rosariosis_sql = '';

	$rosariosis_sql_file = 'rosariosis_' . $_POST['lang'] . '.sql';

	$addons_sql_file = 'rosariosis_addons_' . $_POST['lang'] . '.sql';

	// Translate Database.
	if ( file_exists( $rosariosis_sql_file ) )
	{
		// Same translation files for both MySQL & PostgreSQL.
		$rosariosis_sql = file_get_contents( $rosariosis_sql_file );

		if ( file_exists( $addons_sql_file ) )
		{
			$addons_sql = _getAddonsSQL( $addons_sql_file );
		}
	}

	if ( $rosariosis_sql )
	{
		db_query( $rosariosis_sql );
	}

	if ( $addons_sql )
	{
		db_query( $addons_sql );
	}

	die( 'Success: database translated. <a href="index.php">Access RosarioSIS</a>' );
}

$sql_file = $DatabaseType === 'mysql' ? 'rosariosis_mysql.sql' : 'rosariosis.sql';

if ( ! file_exists( $sql_file ) )
{
	die( 'Error: ' . $sql_file . ' file not found.' );
}

$rosariosis_sql = file_get_contents( $sql_file );

if ( $DatabaseType === 'mysql' )
{
	// @since 10.0 Remove DELIMITER $$ declarations before procedures or functions.
	$rosariosis_sql = MySQLRemoveDelimiter( $rosariosis_sql );

	// @since 10.5 MySQL change database charset to utf8mb4 and collation to utf8mb4_unicode_520_ci
	db_query( "ALTER DATABASE " . DBEscapeIdentifier( $DatabaseName ) . " CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_520_ci'" );
}

db_query( $rosariosis_sql );

if ( filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
{
	// @since 11.1 Set email for default admin user so he can reset his password
	db_query( "UPDATE staff
		SET EMAIL='" . DBEscapeString( $RosarioNotifyAddress ) . "'
		WHERE USERNAME='admin';" );
}

if ( file_exists( 'rosariosis_addons.sql' ) )
{
	// @since 5.1 Install add-ons.
	// Same add-ons file for both MySQL & PostgreSQL.
	$addons_sql = _getAddonsSQL( 'rosariosis_addons.sql' );

	db_query( $addons_sql );
}

?>
<form method="POST">
	Translate database to
	<select name="lang">
		<?php foreach ( $lang as $lang_code => $lang_name ) : ?>
			<option value="<?php echo $lang_code; ?>"><?php echo $lang_name; ?></option>
		<?php endforeach; ?>
	</select>
	<br />
	<input type="submit" value="Submit" />
	<br />
</form>
<br />
<?php

die( 'Success: database' .
	( file_exists( 'rosariosis_addons.sql' ) ? ' and add-ons' : '' ) .
	' installed. <a href="index.php">Access RosarioSIS</a>' );

/**
 * Check if config table exists
 *
 * @since 10.0 Add MySQL support
 *
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @return bool True if config table exists
 */
function _configTableCheck()
{
	global $DatabaseType;

	$result = db_query( "SELECT 1
		FROM information_schema.tables
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='config';" );

	return $result === false ? false : (bool) db_fetch_row( $result );
}


/**
 * Get add-ons SQL
 * Both SQL inside file
 * and SQL inside \include files.
 *
 * @since 10.0
 * @since 10.9.6 Do not use strtok(), can't handle nested calls for multiple files
 *
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param  string $file Full path to SQL file.
 * @return string       SQL queries.
 */
function _getAddonsSQL( $file )
{
	global $DatabaseType;

	$sql_addons_queries = '';

	// https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php
	$separator = "\r\n";

	$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	foreach ( $lines as $line )
	{
		if ( strpos( $line, '\include' ) !== false )
		{
			// \include files.
			$sql_addon_include_file = trim( str_replace( [ '\include', "'", ';' ], '', $line ) );

			if ( $DatabaseType === 'mysql' )
			{
				$sql_addon_include_file = str_replace( 'install.sql', 'install_mysql.sql', $sql_addon_include_file );
			}

			if ( file_exists( $sql_addon_include_file ) )
			{
				$sql_addon_install = file_get_contents( $sql_addon_include_file );

				if ( $DatabaseType === 'mysql' )
				{
					// @since 10.0 Remove DELIMITER $$ declarations before procedures or functions.
					$sql_addon_install = MySQLRemoveDelimiter( $sql_addon_install );
				}

				$sql_addons_queries .= $sql_addon_install . $separator;
			}
		}
		else
		{
			$sql_addons_queries .= $line . $separator;
		}
	}

	return $sql_addons_queries;
}
