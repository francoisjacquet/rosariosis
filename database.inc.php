<?php
//FJ remove DatabaseType (oracle and mysql cases)

/**
 * Establish DB connection
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

	$connectstring = '';

	if ( $DatabaseServer != 'localhost' )
		$connectstring = 'host=' . $DatabaseServer . ' ';

	if ( $DatabasePort != '5432' )
		$connectstring .= 'port=' . $DatabasePort .' ';

	$connectstring .= 'dbname=' . $DatabaseName . ' user=' . $DatabaseUsername;

	if ( $DatabasePassword !== '' )
		$connectstring .= ' password=' . $DatabasePassword;

	$connection = pg_connect( $connectstring );

	// Error code for both.
	if ( $connection === false )
	{
		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
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
 * @param  string   $sql SQL statement
 *
 * @return resource PostgreSQL result resource
 */
function DBQuery( $sql )
{
	$connection = db_start();

	// replace empty strings ('') with NULL values
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

	$result = @pg_exec( $connection, $sql );

	if ( $result === false )
	{
		$errstring = pg_last_error( $connection );

		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
		db_show_error( $sql, 'DB Execute Failed.', $errstring );
	}
	
	return $result;
}


/**
 * return next row
 *
 * @param  PostgreSQL result resource $result
 *
 * @return array 	next row in result set
 */
function db_fetch_row( $result )
{
	$return = @pg_fetch_array( $result );

	if ( is_array( $return ) )
	{
		//modify loop: use for instead of foreach
		$key = array_keys( $return );

		$size = sizeOf( $key );

		for ( $i = 0; $i < $size; $i++ )
			if ( is_int( $key[$i] ) )
				unset( $return[$key[$i]] );
		
		/*foreach ( (array)$return as $key => $value )
		{
			if (is_int($key))
				unset($return[$key]);
		}*/
	}
	
	return @array_change_key_case( $return, CASE_UPPER );
}


/**
 * returns code to go into SQL statement for accessing the next value of a sequence
 *
 * @param  string $seqname PostgreSQL sequence name
 *
 * @return sting          nextval code
 */
function db_seq_nextval( $seqname )
{
	return "nextval('" . $seqname . "')";
}


/**
 * start transaction
 *
 * @param  PostgreSQL connection resource $connection
 *
 * @return void
 */
function db_trans_start( $connection )
{
	db_trans_query( $connection, 'BEGIN WORK' );
}


/**
 * run query on transaction -- if failure, runs rollback.
 *
 * @param  PostgreSQL connection resource $connection
 * @param  string $sql SQL statement
 *
 * @return PostgreSQL result resource
 */
function db_trans_query( $connection, $sql )
{
	// replace empty strings ('') with NULL values
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
		// rollback commands.
		pg_query( $connection, 'ROLLBACK' );

		// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
		db_show_error( $sql, 'DB Transaction Execute Failed.' );
	}

	return $result;
}


/**
 * commit changes.
 *
 * @param  PostgreSQL connection resource $connection
 *
 * @return void
 */
function db_trans_commit($connection)
{
	pg_query( $connection, 'COMMIT' );
}


// keyword mapping.
define( 'FROM_DUAL', ' ' );


/**
 * Generate CASE-WHEN condition
 *
 * @example db_case( array( 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ) )
 * will return ' CASE WHEN FAILED_LOGIN  IS NULL THEN 1 ELSE FAILED_LOGIN+1 END '
 *
 * @param  array $array
 *
 * @return string        CASE-WHEN condition
 */
function db_case( $array )
{
	$counter = 0;

	$array_count = count( $array );

	$string = ' CASE WHEN ' . $array[0] . ' =';

	$counter++;

	$arr_count = count( $array );

	for( $i = 1; $i < $arr_count; $i++ )
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
			$string .= ' ELSE ';

		elseif ( $counter == ( $array_count - 1 ) )
			$string .= ' END ';

		elseif ( $counter % 2 == 0 )
			$string .= ' WHEN ' . $array[0] . '=';

		elseif ( $counter % 2 == 1 )
			$string .= ' THEN ';

		$counter++;
	}
	
	return $string;
}


// greatest/least - builtin to postgres 8 but not 7
/**
 * GREATEST function
 *
 * @param  value $a
 * @param  value $b
 *
 * @return value    largest value
 */
function db_greatest( $a, $b )
{
	return 'GREATEST(' . $a . ', ' . $b . ')';
}


/**
 * LEAST function
 *
 * @param  value $a
 * @param  value $b
 *
 * @return value    smallest value
 */
function db_least( $a, $b )
{
	return 'LEAST(' . $a . ', ' . $b . ')';
}


/**
 * returns an array with the field names for the specified table as key with subkeys
 * of SIZE, TYPE, SCALE and NULL.  TYPE: varchar, numeric, etc.
 *
 * @param  string $table
 *
 * @return array        table properties
 */
function db_properties( $table )
{
	$sql = "SELECT a.attnum,a.attname AS field,t.typname AS type,
			a.attlen AS length,a.atttypmod AS lengthvar,
			a.attnotnull AS notnull
		FROM pg_class c, pg_attribute a, pg_type t
		WHERE c.relname = '" . mb_strtolower( $table ) . "'
			and a.attnum > 0 and a.attrelid = c.oid
			and a.atttypid = t.oid ORDER BY a.attnum";

	$result = DBQuery( $sql );

	while( $row = db_fetch_row( $result ) )
	{
		$properties[mb_strtoupper( $row['FIELD'] )]['TYPE'] = mb_strtoupper( $row['TYPE'] );

		if ( mb_strtoupper( $row['TYPE'] ) == 'NUMERIC' )
		{
			$properties[mb_strtoupper($row['FIELD'])]['SIZE'] = ( $row['LENGTHVAR'] >> 16 ) & 0xffff;
			$properties[mb_strtoupper($row['FIELD'])]['SCALE'] = ( $row['LENGTHVAR'] - 4 ) & 0xffff;
		}
		else
		{
			if ( $row['LENGTH'] > 0 )
				$properties[mb_strtoupper( $row['FIELD'] )]['SIZE'] = $row['LENGTH'];

			elseif ( $row['LENGTHVAR'] > 0 )
				$properties[mb_strtoupper( $row['FIELD'] )]['SIZE'] = $row['LENGTHVAR'] - 4;
		}

		if ( $row['NOTNULL'] == 't' )
			$properties[mb_strtoupper( $row['FIELD'] )]['NULL'] = 'N';

		else
			$properties[mb_strtoupper( $row['FIELD'] )]['NULL'] = 'Y';
	}
			
	return $properties;
}


/**
 * Show SQL error message
 * Send notification email if $RosarioNotifyAddress set
 *
 * @param  string $sql        SQL statement
 * @param  string $failnote   Failure Notice
 * @param  string $additional Additional Information
 *
 * @return die
 */
function db_show_error( $sql, $failnote, $additional = '' )
{
	global $RosarioNotifyAddress;

    echo '<br />';

	PopTable( 'header', _('We have a problem, please contact technical support ...') );

	// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
	?>
		<table class="col1-align-right" style="border-collapse:separate; border-spacing:10px;">
			<tr>
				<td><b>Date:</b></td>
				<td><pre><?php echo date( 'm/d/Y h:i:s' ); ?></pre></td>
			</tr>
			<tr>
				<td><b>Failure Notice:</b></td>
				<td><pre><?php echo $failnote; ?></pre></td>
			</tr>
			<tr>
				<td><b>Additional Information:</b></td>
				<td><?php echo $additional; ?></td>
			</tr>
		</table>
	<?php
	// Something you have asked the system to do has thrown a database error.
	// A system administrator has been notified, and the problem will be fixed as soon as possible.
	// It might be that changing the input parameters sent to this program will cause it to run properly.
	// Thanks for your patience.

	PopTable( 'footer' );

	// dump SQL statement in an HTML comment
	echo '<!-- SQL STATEMENT: ' . "\n\n" . $sql . "\n\n" . ' -->';

	// send notification email if $RosarioNotifyAddress set
	if ( filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
	{
		//FJ add SendEmail function
		require_once 'ProgramFunctions/SendEmail.fnc.php';
		
		$message = 'System: ' . ParseMLField( Config( 'TITLE' ) ) . "\n";
		$message .= 'Date: ' . date( 'm/d/Y h:i:s' ) . "\n";
		$message .= 'Page: ' . $_SERVER['PHP_SELF'] . ' ' . ProgramTitle() . "\n\n";
		$message .= 'Failure Notice: ' . $failnote . "\n";
		$message .= 'Additional Info: ' . $additional . "\n";
		$message .= "\n" . $sql . "\n";
		$message .= "\n\n" . 'Request Array: ' . "\n" . print_r( $_REQUEST, true );
		$message .= "\n\n" . 'Session Array: ' . "\n" . print_r( $_SESSION, true );
		
		SendEmail( $RosarioNotifyAddress, 'Database Error', $message );
	}

	die();
}


/**
 * Escapes single quotes by using two for every one.
 *
 * @example $safe_string = DBEscapeString( $string );
 *
 * @param string $input
 *
 * @return string escaped string
 */
function DBEscapeString( $input )
{
	return pg_escape_string( $input );
	//return str_replace("'","''",$input);
}
