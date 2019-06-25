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

$connection = db_start();

// Test if database is already installed first.
$result = @pg_exec( $connection, "SELECT 1
	FROM information_schema.tables
	WHERE table_schema='public'
	AND table_name='config';" );

$config_table_exists = db_fetch_row( $result );

if ( $result !== false
	&& $config_table_exists )
{
	$result = @pg_exec( $connection, "SELECT CONFIG_VALUE
	FROM CONFIG
	WHERE TITLE='LOGIN';" );

	$config_login = db_fetch_row( $result );

	if ( ! empty( $_POST['lang'] )
		&& $config_login['CONFIG_VALUE'] === 'No' )
	{
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

		// Run SQL queries. Do not use DBQuery() as it will not work.
		$result = @pg_exec( $connection, $rosariosis_sql );

		if ( $result === false )
		{
			$errstring = pg_last_error( $connection );

			// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
			db_show_error( $rosariosis_sql, 'DB Execute Failed.', $errstring );
		}
		else
		{
			die( 'Success: database translated. <a href="index.php">Access RosarioSIS</a>' );
		}
	}

	die( 'Database already installed.' );
}

if ( ! file_exists( 'rosariosis.sql' ) )
{
	die( 'Error: rosariosis.sql file not found.' );
}

$rosariosis_sql = file_get_contents( 'rosariosis.sql' );

// Run SQL queries. Do not use DBQuery() as it will not work.
$result = @pg_exec( $connection, $rosariosis_sql );

if ( $result === false )
{
	$errstring = pg_last_error( $connection );

	// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
	db_show_error( $rosariosis_sql, 'DB Execute Failed.', $errstring );
}
else
{
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
	<?php

	die( 'Success: database installed. <a href="index.php">Access RosarioSIS</a>' );
}
