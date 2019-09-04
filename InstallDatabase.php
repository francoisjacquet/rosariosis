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

require_once 'database.inc.php';

// Test if database is already installed first.
$result = db_query( "SELECT 1
	FROM information_schema.tables
	WHERE table_schema='public'
	AND table_name='config';" );

$config_table_exists = db_fetch_row( $result );

if ( $result !== false
	&& $config_table_exists )
{
	$result = db_query( "SELECT CONFIG_VALUE
	FROM CONFIG
	WHERE TITLE='LOGIN';" );

	$config_login = db_fetch_row( $result );

	if ( empty( $_POST['lang'] )
		|| $config_login['CONFIG_VALUE'] !== 'No' )
	{
		die( 'Database already installed.' );
	}

	// Translate Database.
	if ( $_POST['lang'] === 'fr'
		&& file_exists( 'rosariosis_fr.sql' ) )
	{
		$rosariosis_sql = file_get_contents( 'rosariosis_fr.sql' );
	}
	elseif ( $_POST['lang'] === 'es'
		&& file_exists( 'rosariosis_es.sql' ) )
	{
		$rosariosis_sql = file_get_contents( 'rosariosis_es.sql' );
	}

	$result = db_query( $rosariosis_sql );

	die( 'Success: database translated. <a href="index.php">Access RosarioSIS</a>' );
}

if ( ! file_exists( 'rosariosis.sql' ) )
{
	die( 'Error: rosariosis.sql file not found.' );
}

$rosariosis_sql = file_get_contents( 'rosariosis.sql' );

$result = db_query( $rosariosis_sql );

if ( file_exists( 'rosariosis_addons.sql' ) )
{
	// @since 5.1 Install add-ons.
	$sql_addons = file_get_contents( 'rosariosis_addons.sql' );

	$sql_addons_sql = '';

	// https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php
	$separator = "\r\n";

	$line = strtok( $sql_addons, $separator );

	while ( $line !== false )
	{
		if ( strpos( $line, '\include' ) !== false )
		{
			// \include files.
			$sql_addon_include_file = trim( str_replace( array( '\include', "'", ';' ), '', $line ) );

			if ( file_exists( $sql_addon_include_file ) )
			{
				$sql_addon_install = file_get_contents( $sql_addon_include_file );

				db_query( $sql_addon_install );
			}
		}
		else
		{
			$sql_addons_queries .= $line . $separator;
		}

		$line = strtok( $separator );
	}

	db_query( $sql_addons_queries );
}

?>
<form method="POST">
	Translate database to
	<select name="lang">
		<option value="es">Spanish</option>
		<option value="fr">French</option>
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
