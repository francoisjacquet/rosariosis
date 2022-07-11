<?php
/**
 * Save Data function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Save Data
 * INSERT or UPDATE data in Database
 *
 * @example SaveData( array( 'student_medical' => "ID='__ID__'", 'fields' => array( 'student_medical' => 'STUDENT_ID,' ), 'values' => array( 'student_medical' => "'" . UserStudentID() . "'," ) ) );
 *
 * @todo use SaveData in EVERY module
 *
 * @param  array $iu_extra    WHERE part of UPDATE & Extra fields for INSERT. Associative array( 'table_name' => "ID='__ID__'", 'fields' => array( 'table_name' => "FIELD1,FIELD2," ), 'values' => array( 'table_name' => "'value1','value2'," ) ).
 * @param  array $field_names Proper, translated field names used for errors. Associative array( 'table_name' => $columns ) (optional).
 *
 * @return void  INSERT or UPDATE data
 */
function SaveData( $iu_extra, $field_names = [] )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values' );

	// For each DB table.
	foreach ( (array) $_REQUEST['values'] as $table => $values )
	{
		// Get DB table columns properties.
		$table_properties = db_properties( $table );

		// For each table entry.
		foreach ( (array) $values as $id => $columns )
		{
			// Reset vars.
			$error = $sql = $ins_fields = $ins_values = [];

			$sql[ $table ] = $ins_fields[ $table ] = $ins_values[ $table ] = '';

			$go = false;

			if ( empty( $columns ) )
			{
				// Fix Error: There is no column for The value for 0. This value was not saved.
				continue;
			}

			// For each column.
			foreach ( (array) $columns as $column => $value )
			{
				if ( isset( $field_names[ $table ][ $column ] ) )
				{
					$name = sprintf( _( 'The value for %s' ), $field_names[ $table ][ $column ] );
				}
				else
					$name = sprintf(
						_( 'The value for %s' ),
						_( ucwords( mb_strtolower( str_replace( '_', ' ', $column ) ) ) )
					);

				// COLUMN DOESN'T EXIST.
				if ( ! isset( $table_properties[ $column ] ) )
				{
					$error[] = sprintf( _( 'There is no column for %s. This value was not saved.' ), $name );

					continue;
				}

				// VALUE IS TOO LONG.
				elseif ( $table_properties[ $column ]['TYPE'] === 'VARCHAR'
					&& mb_strlen( $value ) > $table_properties[ $column ]['SIZE'] )
				{
					$value = mb_substr( $value, 0, $table_properties[ $column ]['SIZE'] );

					$error[] = sprintf( _( '%s was too long. It was truncated to fit in the field.' ), $name );
				}

				// FIELD IS NUMERIC, VALUE CONTAINS NON-NUMERICAL CHARACTERS.
				elseif ( $table_properties[ $column ]['TYPE'] === 'NUMERIC'
					&& preg_match( '/[^0-9-.]/', $value ) )
				{
					$value = preg_replace( '/[^0-9-.]/', '', $value );

					$error[] = sprintf( _( '%s, a numerical field, contained non-numerical characters. These characters were removed.' ), $name );
				}

				// FIELD IS INTEGER, VALUE CONTAINS NON-INTEGER CHARACTERS.
				elseif ( strpos( $table_properties[ $column ]['TYPE'], 'INT' ) === 0
					&& preg_match( '/[^0-9-]/', $value ) )
				{
					$value = (int) $value;

					$error[] = sprintf( _( '%s, a numerical field, contained non-numerical characters. These characters were removed.' ), $name );
				}

				// FIELD IS DATE, DATE IS WRONG.
				elseif ( $table_properties[ $column ]['TYPE'] === 'DATE'
					&& $value
					&& ! VerifyDate( $value ) )
				{
					$error[] = sprintf( _( '%s, a date field, was not a valid date. This value could not be saved.' ), $name );

					continue;
				}

				if ( $id === 'new' )
				{
					if ( ! empty( $value )
						|| $value == '0' )
					{
						$ins_fields[ $table ] .= DBEscapeIdentifier( $column ) . ',';

						$ins_values[ $table ] .= "'" . $value . "',";

						$go = true;
					}

					continue;
				}

				$sql[ $table ] .= DBEscapeIdentifier( $column ) . "='" . $value . "',";

				$go = true;
			}

			// INSERT new data.
			if ( $id === 'new'
				&& $go )
			{
				$sql[ $table ] = 'INSERT INTO ' . DBEscapeIdentifier( $table ) .
					' (' . $iu_extra['fields'][ $table ] . mb_substr( $ins_fields[ $table ], 0, -1 ) . ')
					VALUES (' . $iu_extra['values'][ $table ] . mb_substr( $ins_values[ $table ], 0, -1 ) . ')';
			}

			// UPDATE data.
			elseif ( $go )
			{
				$sql[ $table ] = 'UPDATE ' . DBEscapeIdentifier( $table ) .
					' SET ' . mb_substr( $sql[ $table ], 0, -1 ) .
					' WHERE ' . str_replace( '__ID__', $id, $iu_extra[ $table ] );
			}

			echo ErrorMessage( $error );

			if ( $go )
			{
				DBQuery( $sql[ $table ] );
			}
		}
	}
}
