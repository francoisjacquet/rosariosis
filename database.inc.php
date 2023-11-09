<?php
/**
 * Database functions
 *
 * @package RosarioSIS
 */

/**
 * Establish DB connection
 *
 * @since 10.0 Add MySQL support
 *
 * @global $DatabaseServer   Database server hostname
 * @global $DatabaseUsername Database username
 * @global $DatabasePassword Database password
 * @global $DatabaseName     Database name
 * @global $DatabasePort     Database port
 * @global $DatabaseType     Database type: mysql or postgresql
 * @see config.inc.php file for globals definition
 *
 * @param  bool   $show_error Show error and die. Optional, defaults to true.
 *
 * @return PostgreSQL or MySQL connection resource
 */
function db_start( $show_error = true )
{
	global $DatabaseServer,
		$DatabaseUsername,
		$DatabasePassword,
		$DatabaseName,
		$DatabasePort,
		$DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		// @link https://www.php.net/manual/en/mysqli-driver.report-mode.php
		mysqli_report( MYSQLI_REPORT_OFF );

		$db_connection = mysqli_connect(
			$DatabaseServer,
			$DatabaseUsername,
			$DatabasePassword,
			$DatabaseName,
			$DatabasePort
		);
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
		 * @since 3.5.2
		 */
		$connectstring = 'host=' . $DatabaseServer . ' ';

		if ( isset( $DatabasePort )
			&& $DatabasePort !== '5432' )
		{
			$connectstring .= 'port=' . $DatabasePort . ' ';
		}

		$connectstring .= 'dbname=' . $DatabaseName . ' user=' . $DatabaseUsername;

		if ( $DatabasePassword !== '' )
		{
			$connectstring .= ' password=' . $DatabasePassword;
		}

		$db_connection = pg_connect( $connectstring );
	}

	// Error code for both.
	if ( $db_connection === false
		&& $show_error )
	{
		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
		db_show_error(
			'',
			sprintf( "Could not Connect to Database Server '%s'.", $DatabaseServer ),
			( $DatabaseType === 'mysql' ? mysqli_connect_error() : error_get_last()['message'] )
		);
	}

	return $db_connection;
}

/**
 * Execute DB query
 * pg_exec wrapper, dies on error.
 *
 * @since 5.1
 * @since 5.2 Add $show_error optional param.
 * @since 8.1 Remove @ error control operator on pg_exec: allow PHP Warning
 * @since 9.0 Fix PHP8.1 deprecated use PostgreSQL $db_connection global variable
 * @since 10.0 Add MySQL support
 *
 * @uses db_start()
 * @uses db_show_error()
 *
 * @global $db_connection PgSql or MySQLi connection instance
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param  string $sql        SQL statement.
 * @param  bool   $show_error Show error and die. Optional, defaults to true.
 *
 * @return resource PostgreSQL or MySQL result resource.
 */
function db_query( $sql, $show_error = true )
{
	global $db_connection,
		$DatabaseType;

	if ( ! isset( $db_connection ) )
	{
		$db_connection = db_start();
	}

	if ( $DatabaseType === 'mysql' )
	{
		/**
		 * Allow for multiple queries (INSERT, UPDATE and DELETE).
		 * If an error happens in the second or later query, first queries will be executed.
		 * This is a difference of behavior compared to PostgreSQL (rollback on error).
		 */
		$result = mysqli_multi_query( $db_connection, $sql );

		if ( $result )
		{
			$result = mysqli_store_result( $db_connection );

			while ( mysqli_more_results( $db_connection ) )
			{
				if ( mysqli_next_result( $db_connection ) )
				{
					// If multiple SELECT, return last result to be coherent with PostgreSQL.
					$result = mysqli_store_result( $db_connection );
				}
			}

			if ( ! $result && ! mysqli_errno( $db_connection ) )
			{
				// mysqli_store_result returns false if no result.
				// Return null to be coherent with PostgreSQL.
				$result = null;
			}
		}
	}
	else
	{
		$result = pg_exec( $db_connection, $sql );
	}

	if ( $result === false
		&& $show_error )
	{
		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
		db_show_error(
			$sql,
			'DB Execute Failed.',
			( $DatabaseType === 'mysql' ?
				mysqli_errno( $db_connection ) . ' ' . mysqli_error( $db_connection ) :
				pg_last_error( $db_connection ) )
		);
	}

	return $result;
}

/**
 * SQL query filter
 * Replace empty strings ('') with NULL values:
 * - Check for ( or , character before empty string '' in INSERT INTO.
 * - Check for <> or = character before empty string ''.
 *
 * @since 5.2
 *
 * @param  string $sql SQL queries.
 * @return string      Filtered SQL queries.
 */
function db_sql_filter( $sql )
{
	// Replace empty strings ('') with NULL values.

	if ( stripos( $sql, 'INSERT INTO ' ) !== false )
	{
		// Check for ( or , character before empty string ''.
		$sql = preg_replace( "/([,\(])[\r\n\t ]*''(?!')/", '\\1NULL', $sql );
	}

	// Check for <> or = character before empty string ''.
	$sql = preg_replace( "/(<>|=)[\r\n\t ]*''(?!'|\w|\d)/", '\\1NULL', $sql );

	/**
	 * IS NOT NULL cases
	 *
	 * Replace <>NULL & !=NULL with IS NOT NULL
	 *
	 * @link http://www.postgresql.org/docs/current/static/functions-comparison.html
	 */
	$sql = str_ireplace(
		[ '<>NULL', '!=NULL' ],
		' IS NOT NULL',
		$sql
	);

	return $sql;
}

/**
 * This function connects, and does the passed query, then returns a result resource
 * Not receiving the return == unusable search.
 *
 * @example $processable_results = DBQuery( "SELECT * FROM students" );
 *
 * @uses db_sql_filter()
 * @uses db_query()
 * @see DBGet()
 *
 * @since 4.3 Do DBQuery after action hook.
 *
 * @param  string   $sql       SQL statement.
 * @return resource PostgreSQL result resource
 */
function DBQuery( $sql )
{
	$sql = db_sql_filter( $sql );

	$result = db_query( $sql );

	// Do DBQuery after action hook.
	do_action( 'database.inc.php|dbquery_after', [ $sql, $result ] );

	return $result;
}

/**
 * Return next row
 *
 * @since 10.0 Add MySQL support
 * @since 10.2.1 Fix error mysqli_fetch_assoc(): Argument #1 must be of type mysqli_result, null given
 *
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param  resource PostgreSQL result resource $result Result.
 * @return array|bool    Next row in result set or false.
 */
function db_fetch_row( $result )
{
	global $DatabaseType;

	$return = false;

	if ( $DatabaseType === 'mysql'
		&& $result instanceof mysqli_result )
	{
		$return = mysqli_fetch_assoc( $result );
	}
	else
	{
		$return = pg_fetch_array( $result, null, PGSQL_ASSOC );
	}

	return is_array( $return ) ? array_change_key_case( $return, CASE_UPPER ) : $return;
}

/**
 * Returns code to go into SQL statement for accessing the next value of a sequence
 *
 * @deprecated since 9.2.1 Use DBLastInsertID() instead
 *
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param  string $seqname PostgreSQL sequence name.
 * @return sting  nextval code
 */
function db_seq_nextval( $seqname )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return DBSeqNextID( $seqname );
	}

	return "nextval('" . DBEscapeString( $seqname ) . "')";
}


/**
 * DB Sequence Next ID
 *
 * @deprecated since 9.2.1 Use DBLastInsertID() instead (with the exception of student ID)
 *
 * @since 11.0.1 MySQL fix infinite loop, emulate PostgreSQL's nextval()
 * @since 11.2.4 MySQL 8+ fix infinite loop due to cached AUTO_INCREMENT
 *
 * @example $id = DBSeqNextID( 'people_person_id_seq' );
 *
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param string $seqname Sequence name (or table name for MySQL).
 *
 * @return int Next ID.
 */
function DBSeqNextID( $seqname )
{
	global $DatabaseType;

	static $auto_increment = [];

	if ( $DatabaseType === 'mysql' )
	{
		if ( empty( $auto_increment ) // Set only once per session.
			&& DBGetOne( "SHOW VARIABLES LIKE 'information_schema_stats_expiry';" ) )
		{
			/**
			 * Do NOT cache table statistics cache in MySQL 8+
			 *
			 * Note: only for MySQL, MariaDB does not have this system variable
			 *
			 * @link https://stackoverflow.com/questions/51283195/wrong-auto-increment-value-on-select
			 *
			 * @since 11.2.4 MySQL 8+ fix infinite loop due to cached AUTO_INCREMENT
			 */
			DBQuery( "SET @@SESSION.information_schema_stats_expiry = 0;" );
		}

		// Try to get table name from PostgreSQL sequence name by removing '_id_seq'.
		// Will fail if PRIMARY KEY / serial column name is != id.
		$table_name = str_ireplace( [ '_id_seq', '_seq' ], '', $seqname );

		$seq_next_RET = db_fetch_row( DBQuery( "SELECT AUTO_INCREMENT
			FROM information_schema.tables
			WHERE table_schema=DATABASE()
			AND table_name='" . mb_strtolower( DBEscapeString( $table_name ) ) . "'" ) );

		// Return 0 if query failed. 0 in a MySQL query is valid for an AUTO_INCREMENT ID column.
		$seq_next_id = empty( $seq_next_RET ) ? 0 : $seq_next_RET['AUTO_INCREMENT'];

		if ( $seq_next_id )
		{
			if ( empty( $auto_increment[ $table_name ] ) )
			{
				$auto_increment[ $table_name ] = $seq_next_id;
			}
			elseif ( $auto_increment[ $table_name ] == $seq_next_id )
			{
				/**
				 * Manually increment AUTO_INCREMENT
				 *
				 * @since 11.0.1 MySQL fix infinite loop, emulate PostgreSQL's nextval()
				 */
				$seq_next_id++;

				DBQuery( "ALTER TABLE " . DBEscapeIdentifier( $table_name ) . "
					AUTO_INCREMENT=" . (int) $seq_next_id );

				$auto_increment[ $table_name ] = $seq_next_id;
			}
			else
			{
				unset( $auto_increment[ $table_name ] );
			}
		}
	}
	else
	{
		$seq_next_RET = db_fetch_row( DBQuery( "SELECT " . db_seq_nextval( $seqname ) . ' AS ID' ) );

		$seq_next_id = $seq_next_RET['ID'];
	}

	return $seq_next_id;
}

/**
 * DB Last Inserted ID
 *
 * @since 9.2.1
 * @since 10.0 Add MySQL support
 *
 * @link https://stackoverflow.com/questions/2944297/postgresql-function-for-last-inserted-id
 *
 * @return int Last ID.
 */
function DBLastInsertID()
{
	global $DatabaseType;

	$last_insert_id_function = $DatabaseType === 'mysql' ? 'LAST_INSERT_ID()' : 'LASTVAL()';

	$last_insert_id_RET = db_fetch_row( DBQuery( "SELECT " . $last_insert_id_function . ' AS ID' ) );

	return $last_insert_id_RET['ID'];
}

/**
 * Start transaction
 *
 * @return void
 */
function db_trans_start()
{
	db_query( 'BEGIN;' );
}

/**
 * Run query on transaction -- if failure, runs rollback
 *
 * @since 5.2 $connection param removed.
 *
 * @param  string     $sql       SQL statement.
 * @return PostgreSQL result resource
 */
function db_trans_query( $sql, $show_error = true )
{
	$sql = db_sql_filter( $sql );

	// Use @ error control operator to silence PHP Warning in case of failure.
	$result = @db_query( $sql, $show_error );

	if ( $result === false )
	{
		// Rollback commands.
		db_trans_rollback();
	}

	return $result;
}

/**
 * Commit changes
 *
 * @param  PostgreSQL connection resource $connection Connection. DEPRECATED.
 * @return void
 */
function db_trans_commit()
{
	db_query( 'COMMIT;' );
}

/**
 * Rollback changes
 *
 * @since 5.2
 *
 * @return void
 */
function db_trans_rollback()
{
	db_query( 'ROLLBACK;' );
}

/**
 * Dry run query on transaction -- rollback anyway
 * Useful to check first if foreign key constraints are preventing DELETE.
 *
 * @since 5.2
 *
 * @example $can_delete = DBTransDryRun( UserDeleteSQL( UserStaffID() ) );
 *
 * @param  string     $sql       SQL statement.
 * @return bool Can run the queries in the transaction without error?
 */
function DBTransDryRun( $sql )
{
	db_trans_start();

	$result = db_trans_query( $sql, false );

	if ( $result !== false )
	{
		// Rollback transaction anyway.
		db_trans_rollback();
	}

	return $result !== false;
}

/**
 * Generate CASE-WHEN condition
 *
 * @example db_case( [ 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ] )
 * will return ' CASE WHEN FAILED_LOGIN  IS NULL THEN 1 ELSE FAILED_LOGIN+1 END '
 *
 * @param  array  $array    [ Column, IS, THEN, ELSE ]
 * @return string CASE-WHEN condition
 */
function db_case( $array )
{
	$counter = 0;

	$array_count = count( $array );

	$string = ' CASE WHEN ' . $array[0] . ' =';

	$counter++;

	$arr_count = count( $array );

	for ( $i = 1; $i < $arr_count; $i++ )
	{
		$value = $array[$i];

		if ( $value == "''"
			&& mb_substr( $string, -1 ) == '=' )
		{
			$value = ' IS NULL';

			$string = mb_substr( $string, 0, -1 );
		}

		$string .= $value;

		if ( $counter == ( $array_count - 2 )
			&& $array_count % 2 == 0 )
		{
			$string .= ' ELSE ';
		}
		elseif ( $counter == ( $array_count - 1 ) )
		{
			$string .= ' END ';
		}
		elseif ( $counter % 2 == 0 )
		{
			$string .= ' WHEN ' . $array[0] . '=';
		}
		elseif ( $counter % 2 == 1 )
		{
			$string .= ' THEN ';
		}

		$counter++;
	}

	return $string;
}


/**
 * Returns an array with the field names for the specified table as key with subkeys
 * of SIZE, TYPE, SCALE and NULL.  TYPE: varchar, numeric, etc.
 *
 * @since 10.0 Add MySQL support
 *
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param  string $table DB Table.
 * @return array  Table properties
 */
function db_properties( $table )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		$sql = "SHOW COLUMNS FROM " . DBEscapeIdentifier( $table );
	}
	else
	{
		$sql = "SELECT a.attnum,a.attname AS field,t.typname AS type,
		a.attlen AS length,a.atttypmod AS lengthvar,a.attnotnull AS notnull
		FROM pg_class c,pg_attribute a,pg_type t
		WHERE c.relname='" . mb_strtolower( DBEscapeString( $table ) ) . "'
		AND a.attnum > 0 AND a.attrelid = c.oid AND a.atttypid = t.oid
		ORDER BY a.attnum";
	}

	$result = DBQuery( $sql );

	while ( $row = db_fetch_row( $result ) )
	{
		$field = mb_strtoupper( $row['FIELD'] );

		if ( $DatabaseType === 'mysql' )
		{
			$open_parens_pos = mb_strpos( $row['TYPE'], '(' );

			$properties[$field]['TYPE'] = mb_strtoupper(
				mb_substr( $row['TYPE'], 0, $open_parens_pos ? $open_parens_pos : null )
			);

			if ( ! $pos = mb_strpos( $row['TYPE'], ',' ) )
			{
				$pos = $open_parens_pos;
			}
			else
			{
				$properties[$field]['SCALE'] = mb_substr( $row['TYPE'], $pos + 1, -1 );
			}

			$properties[$field]['SIZE'] = '';

			if ( $open_parens_pos )
			{
				$properties[$field]['SIZE'] = mb_substr(
					$row['TYPE'],
					$open_parens_pos + 1,
					( $pos !== $open_parens_pos ? $pos - $open_parens_pos -1 : -1 )
				);
			}

			$properties[$field]['NULL'] = $row['NULL'] != '' && $row['NULL'] !== 'NO' ? 'Y' : 'N';

			continue;
		}

		$properties[$field]['TYPE'] = mb_strtoupper( $row['TYPE'] );

		if ( mb_strtoupper( $row['TYPE'] ) == 'NUMERIC' )
		{
			$properties[$field]['SIZE'] = ( $row['LENGTHVAR'] >> 16 ) & 0xffff;
			$properties[$field]['SCALE'] = ( $row['LENGTHVAR'] - 4 ) & 0xffff;
		}
		else
		{
			if ( $row['LENGTH'] > 0 )
			{
				$properties[$field]['SIZE'] = $row['LENGTH'];
			}
			elseif ( $row['LENGTHVAR'] > 0 )
			{
				$properties[$field]['SIZE'] = $row['LENGTHVAR'] - 4;
			}
		}

		$properties[$field]['NULL'] = $row['NOTNULL'] === 't' ? 'N' : 'Y';
	}

	return $properties;
}

/**
 * Show SQL error message
 * Send notification email if `$RosarioNotifyAddress` or `$RosarioErrorsAddress` set
 *
 * @global string $RosarioNotifyAddress or $RosarioErrorsAddress email set in config.inc.php file
 * @since 4.0 Uses ErrorSendEmail()
 * @since 4.6 Show SQL query.
 *
 * @param string $sql        SQL statement.
 * @param string $failnote   Failure Notice.
 * @param string $additional Additional Information.
 */
function db_show_error( $sql, $failnote, $additional = '' )
{
	global $RosarioNotifyAddress,
		$RosarioErrorsAddress;

	// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
	?>
	<br />
	<table class="postbox cellspacing-0">
		<thead><tr><th class="center">
			<h3><?php echo function_exists( '_' ) ?
	_( 'We have a problem, please contact technical support ...' ) :
	// PHP gettext extension not loaded, and polyfill either (PHPCompatibility functions not loaded yet).
	'We have a problem, please contact technical support ...'; ?></h3>
		</th></tr></thead>
	<tbody><tr><td class="popTable">
		<table>
			<tr>
				<td><?php echo date( 'm/d/Y H:i:s' ); ?><br />
					<span class="legend-gray">Date</span></td>
			</tr>
			<tr>
				<td><?php echo $failnote; ?> <?php echo $additional; ?><br />
					<span class="legend-gray">Failure Notice</span></td>
			</tr>
			<tr>
				<td><pre class="size-1" style="max-width: 65vw; overflow: auto;"><?php echo str_replace( "\t\t", '', $sql ); ?></pre>
					<span class="legend-gray">SQL query</span></td>
			</tr>
		</table>
	</td></tr></tbody></table>
	<?php

	// Send notification email if $RosarioNotifyAddress set & functions loaded.
	$db_error_email = ! empty( $RosarioErrorsAddress ) ? $RosarioErrorsAddress : $RosarioNotifyAddress;

	if ( function_exists( 'ErrorSendEmail' ) )
	{
		$db_error = [
			'Failure Notice: ' . $failnote,
			'Additional Info: ' . $additional,
			$sql,
		];

		ErrorSendEmail( $db_error, 'Database Error' );
	}

	die();
}

/**
 * Escapes single quotes by using two for every one (PostgreSQL).
 * More characters are escaped with a backslash in MySQL: ',",\r,\n & others.
 *
 * @example $safe_string = DBEscapeString( $string );
 * @since 9.0 Fix PHP8.1 deprecated use PostgreSQL $db_connection global variable
 * @since 10.0 Add MySQL support
 *
 * @global $db_connection PgSql or MySQLi connection instance
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param  string $input  Input string.
 * @return string escaped string
 */
function DBEscapeString( $input )
{
	global $db_connection,
		$DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return mysqli_real_escape_string( $db_connection, (string) $input );
	}

	// return str_replace("'","''",$input);
	return pg_escape_string( $db_connection, (string) $input );
}

/**
 * Unescape string escaped with DBEscapeString()
 * Useful for example to display search terms.
 * Do not use for database purposes, display purposes only!
 *
 * @example $_ROSARIO['SearchTerms'] .= _( 'Address contains' ) . ': ' . DBUnescapeString( $_REQUEST['addr'] );
 *
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param string $input Input string.
 * @return Unescaped string
 */
function DBUnescapeString( $input )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return stripcslashes( (string) $input );
	}

	return str_replace( "''", "'", (string) $input );
}

/**
 * Escapes identifiers (table, column) using double quotes.
 * Security function for
 * when you HAVE to use a variable as an identifier.
 *
 * @example $safe_sql = "SELECT COLUMN FROM " . DBEscapeIdentifier( $table ) . " WHERE " . DBEscapeIdentifier( $column ) . "='Y'";
 * @uses pg_escape_identifier(), requires PHP 5.4.4+
 * @since 3.0
 * @since 9.0 Fix PHP8.1 deprecated use PostgreSQL $db_connection global variable
 * @since 10.0 Add MySQL support
 *
 * @global $db_connection PgSql or MySQLi connection instance
 * @global $DatabaseType  Database type: mysql or postgresql
 *
 * @param  string $identifier SQL identifier (table, column).
 * @return string Escaped identifier.
 */
function DBEscapeIdentifier( $identifier )
{
	global $db_connection,
		$DatabaseType;

	$identifier = mb_strtolower( $identifier );

	if ( $DatabaseType === 'mysql' )
	{
		// @link https://stackoverflow.com/questions/2889871/how-do-i-escape-reserved-words-used-as-column-names-mysql-create-table
		return '`' . str_replace( '`', '', $identifier ) . '`';
	}

	return pg_escape_identifier( $db_connection, $identifier );
}

/**
 * Remove delimiter declarations inside SQL file (MySQL)
 * Delimiter are used for functions or procedures
 * when importing an SQL file from the command line.
 * They generate errors when the SQL is sent from PHP
 *
 * Used in InstallDatabase.php, Modules.inc.php & Plugins.inc.php
 *
 * @since 10.0
 *
 * @param  string $sql SQL from an .sql file.
 * @return string      SQL without delimiter declarations
 */
function MySQLRemoveDelimiter( $sql )
{
	// https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php
	$separator = "\r\n";

	$lines = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $sql ) );

	$sql_without_delimiter = '';

	$delimiter = ';';

	foreach ( $lines as $line )
	{
		if ( stripos( $line, 'DELIMITER' ) !== false )
		{
			$delimiter = ';';

			if ( $line !== 'DELIMITER ;'
				&& $line !== 'delimiter ;' )
			{
				// Declaring custom delimiter, get it.
				$line = trim( $line );

				$line_exploded = explode( ' ', $line );

				$delimiter = trim( $line_exploded[1] );
			}

			// DELIMITER declaration, skip.
			continue;
		}

		$line_without_delimiter = $line;

		if ( $delimiter !== ';' )
		{
			// Replace custom DELIMITER with ;
			$line_without_delimiter = str_replace( $delimiter, ';', $line );
		}

		$sql_without_delimiter .= $line_without_delimiter . $separator;
	}

	return $sql_without_delimiter;
}

/**
 * SQL result as comma separated list
 *
 * @since 10.8
 * @link https://dev.mysql.com/doc/refman/5.7/en/aggregate-functions.html#function_group-concat
 *
 * @example "SELECT " . DBSQLCommaSeparatedResult( 's.STUDENT_ID' ) . " AS STUDENTS_LIST FROM STUDENTS s"
 *
 * @param string $column    SQL column.
 * @param string $separator List separator, default to comma.
 *
 * @return string MySQL or PostgreSQL function
 */
function DBSQLCommaSeparatedResult( $column, $separator = ',' )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return "GROUP_CONCAT(" . $column . " SEPARATOR '" . DBEscapeString( $separator ) . "')";
	}

	return "ARRAY_TO_STRING(ARRAY_AGG(" . $column . "), '" . DBEscapeString( $separator ) . "')";
}
