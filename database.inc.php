<?php
/**
 * Database functions
 *
 * FJ remove DatabaseType (oracle and mysql cases)
 *
 * @package RosarioSIS
 */

/**
 * Establish DB connection
 *
 * @global $DatabaseServer   Database server hostname
 * @global $DatabaseUsername Database username
 * @global $DatabasePassword Database password
 * @global $DatabaseName     Database name
 * @global $DatabasePort     Database port
 * @see config.inc.php file for globals definitions
 *
 * @return PostgreSQL connection resource
 */
function db_start()
{
	global $DatabaseServer,
		$DatabaseUsername,
		$DatabasePassword,
		$DatabaseName,
		$DatabasePort;

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

	if ( $DatabasePort !== '5432' )
	{
		$connectstring .= 'port=' . $DatabasePort . ' ';
	}

	$connectstring .= 'dbname=' . $DatabaseName . ' user=' . $DatabaseUsername;

	if ( $DatabasePassword !== '' )
	{
		$connectstring .= ' password=' . $DatabasePassword;
	}

	$connection = pg_connect( $connectstring );

	// Error code for both.

	if ( $connection === false )
	{
		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
		db_show_error(
			'',
			sprintf( "Could not Connect to Database Server '%s'.", $DatabaseServer ),
			pg_last_error()
		);
	}

	return $connection;
}

/**
 * This function connects, and does the passed query, then returns a result resource
 * Not receiving the return == unusable search.
 *
 * @example $processable_results = DBQuery( "SELECT * FROM students" );
 *
 * @since 3.7 INSERT INTO case to Replace empty strings ('') with NULL values.
 * @since 4.3 Performance: static DB $connection.
 * @since 4.3 Do DBQuery after action hook.
 *
 * @param  string   $sql       SQL statement.
 * @return resource PostgreSQL result resource
 */
function DBQuery( $sql )
{
	static $connection;

	if ( ! isset( $connection ) )
	{
		$connection = db_start();
	}

	// Replace empty strings ('') with NULL values.

	if ( stripos( $sql, 'INSERT INTO ' ) !== false )
	{
		// Check for ( or , character before empty string ''.
		$sql = preg_replace( "/([,\(])[\r\n\t ]*''(?!')/", '\\1NULL', $sql );
	}

	// Check for <> or = character before empty string ''.
	$sql = preg_replace( "/(<>|=)[\r\n\t ]*''(?!')/", '\\1NULL', $sql );

	/**
	 * IS NOT NULL cases
	 *
	 * Replace <>NULL & !=NULL with IS NOT NULL
	 *
	 * @link http://www.postgresql.org/docs/current/static/functions-comparison.html
	 */
	$sql = str_replace(
		array( '<>NULL', '!=NULL' ),
		array( ' IS NOT NULL', ' IS NOT NULL' ),
		$sql
	);

	$result = @pg_exec( $connection, $sql );

	if ( $result === false )
	{
		$errstring = pg_last_error( $connection );

		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
		db_show_error( $sql, 'DB Execute Failed.', $errstring );
	}

	// Do DBQuery after action hook.
	do_action( 'database.inc.php|dbquery_after', array( $sql, $result ) );

	return $result;
}

/**
 * Return next row
 *
 * @param  resource PostgreSQL result resource $result Result.
 * @return array    	Next row in result set.
 */
function db_fetch_row( $result )
{
	$return = @pg_fetch_array( $result, null, PGSQL_ASSOC );

	return is_array( $return ) ? @array_change_key_case( $return, CASE_UPPER ) : $return;
}

/**
 * Returns code to go into SQL statement for accessing the next value of a sequence
 *
 * @param  string $seqname PostgreSQL sequence name.
 * @return sting  nextval code
 */
function db_seq_nextval( $seqname )
{
	return "nextval('" . DBEscapeString( $seqname ) . "')";
}


/**
 * DB Sequence Next ID
 *
 * @example $id = DBSeqNextID( 'PEOPLE_SEQ' );
 *
 * @param string $seqname Sequence name.
 *
 * @return int Next ID.
 */
function DBSeqNextID( $seqname )
{
	$QI = DBQuery( "SELECT " . db_seq_nextval( $seqname ) . ' AS ID' );

	$seq_next_RET = db_fetch_row( $QI );

	return $seq_next_RET['ID'];
}


/**
 * Start transaction
 *
 * @param  PostgreSQL connection resource $connection Connection.
 * @return void
 */
function db_trans_start( $connection )
{
	db_trans_query( $connection, 'BEGIN WORK' );
}

/**
 * Run query on transaction -- if failure, runs rollback
 *
 * @param  PostgreSQL connection resource $connection Connection.
 * @param  string     $sql       SQL statement.
 * @return PostgreSQL result resource
 */
function db_trans_query( $connection, $sql )
{
	// Replace empty strings ('') with NULL values.
	$sql = preg_replace( "/([,\(>=])[\r\n\t ]*''(?!')/", '\\1NULL', $sql );

	/**
	 * IS NOT NULL cases
	 *
	 * Replace <>NULL & !=NULL with IS NOT NULL
	 *
	 * @link http://www.postgresql.org/docs/current/static/functions-comparison.html
	 */
	$sql = str_replace(
		array( '<>NULL', '!=NULL' ),
		array( ' IS NOT NULL', ' IS NOT NULL' ),
		$sql
	);

	$result = pg_query( $connection, $sql );

	if ( $result === false )
	{
		// Rollback commands.
		pg_query( $connection, 'ROLLBACK' );

		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support.
		db_show_error( $sql, 'DB Transaction Execute Failed.' );
	}

	return $result;
}

/**
 * Commit changes
 *
 * @param  PostgreSQL connection resource $connection Connection.
 * @return void
 */
function db_trans_commit( $connection )
{
	pg_query( $connection, 'COMMIT' );
}

/**
 * Generate CASE-WHEN condition
 *
 * @example db_case( array( 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ) )
 * will return ' CASE WHEN FAILED_LOGIN  IS NULL THEN 1 ELSE FAILED_LOGIN+1 END '
 *
 * @param  array  $array    array( Column, IS, THEN, ELSE ).
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
 * @param  string $table DB Table.
 * @return array  Table properties
 */
function db_properties( $table )
{
	$sql = "SELECT a.attnum,a.attname AS field,t.typname AS type,
			a.attlen AS length,a.atttypmod AS lengthvar,
			a.attnotnull AS notnull
		FROM pg_class c, pg_attribute a, pg_type t
		WHERE c.relname = '" . mb_strtolower( DBEscapeString( $table ) ) . "'
			and a.attnum > 0 and a.attrelid = c.oid
			and a.atttypid = t.oid ORDER BY a.attnum";

	$result = DBQuery( $sql );

	while ( $row = db_fetch_row( $result ) )
	{
		$properties[mb_strtoupper( $row['FIELD'] )]['TYPE'] = mb_strtoupper( $row['TYPE'] );

		if ( mb_strtoupper( $row['TYPE'] ) == 'NUMERIC' )
		{
			$properties[mb_strtoupper( $row['FIELD'] )]['SIZE'] = ( $row['LENGTHVAR'] >> 16 ) & 0xffff;
			$properties[mb_strtoupper( $row['FIELD'] )]['SCALE'] = ( $row['LENGTHVAR'] - 4 ) & 0xffff;
		}
		else
		{
			if ( $row['LENGTH'] > 0 )
			{
				$properties[mb_strtoupper( $row['FIELD'] )]['SIZE'] = $row['LENGTH'];
			}
			elseif ( $row['LENGTHVAR'] > 0 )
			{
				$properties[mb_strtoupper( $row['FIELD'] )]['SIZE'] = $row['LENGTHVAR'] - 4;
			}
		}

		if ( $row['NOTNULL'] === 't' )
		{
			$properties[mb_strtoupper( $row['FIELD'] )]['NULL'] = 'N';
		}
		else
		{
			$properties[mb_strtoupper( $row['FIELD'] )]['NULL'] = 'Y';
		}
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
		$db_error = array(
			'Failure Notice: ' . $failnote,
			'Additional Info: ' . $additional,
			$sql,
		);

		ErrorSendEmail( $db_error, 'Database Error' );
	}

	die();
}

/**
 * Escapes single quotes by using two for every one.
 *
 * @example $safe_string = DBEscapeString( $string );
 *
 * @param  string $input  Input string.
 * @return string escaped string
 */
function DBEscapeString( $input )
{
	// return str_replace("'","''",$input);
	return pg_escape_string( $input );
}

/**
 * Escapes identifiers (table, column) using double quotes.
 * Security function for
 * when you HAVE to use a variable as an identifier.
 *
 * @example $safe_sql = "SELECT COLUMN FROM " . DBEscapeIdentifier( $table ) . " WHERE " . DBEscapeIdentifier( $column ) . "='Y'";
 * @uses pg_escape_identifier(), requires PHP 5.4.4+
 * @since 3.0
 *
 * @param  string $identifier SQL identifier (table, column).
 * @return string Escaped identifier.
 */
function DBEscapeIdentifier( $identifier )
{
	$identifier = mb_strtolower( $identifier );

	if ( ! function_exists( 'pg_escape_identifier' ) )
	{
		return '"' . $identifier . '"';
	}

	return pg_escape_identifier( $identifier );
}
