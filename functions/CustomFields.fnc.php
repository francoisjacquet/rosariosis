<?php
/**
 * Custom (staff) Fields function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Custom (staff) fields query
 * Call in an SQL statement to select students / staff based on custom fields
 * Also sets $_ROSARIO['SearchTerms'] to display search terms
 *
 * @example Use in the where section of the query:
 *          $extra['WHERE'] .= CustomFields( 'where' );
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['SearchTerms']
 *
 * @uses SearchField()
 *
 * @param  string $location part of the SQL statement (always 'where').
 * @param  string $type     student|staff (optional).
 * @param  array  $extra    disable search terms: array( 'NoSearchTerms' => true ) (optional).
 *
 * @return string           Custom Fields SQL WHERE
 */
function CustomFields( $location, $type = 'student', $extra = array() )
{
	global $_ROSARIO;

	$return = '';

	// If location === 'from', return.
	if ( $location !== 'where' )
	{
		return $return;
	}

	// if location === 'where':

	// Unset empty values.
	$cust = array();

	if ( isset( $_REQUEST['cust'] ) )
	{
		foreach ( (array) $_REQUEST['cust'] as $key => $value )
		{
			if ( $value !== '' )
			{
				$cust[ $key ] = $value;
			}
		}
	}

	// Format & Verify begin dates.
	AddRequestedDates( 'cust_begin' );

	// Add begin dates and begin Number.
	$cust_begin = $_REQUEST['cust_begin'];

	// Format & Verify end dates.
	AddRequestedDates( 'cust_end' );

	// Add end dates and end Number.
	$cust_end = $_REQUEST['cust_end'];

	// Get custom (staff) fields.
	if ( count( $cust )
		|| count( $cust_begin )
		|| count( $cust_end )
		|| ( isset( $_REQUEST['cust_null'] )
			&& count( (array) $_REQUEST['cust_null'] ) ) )
	{
		$fields = ParseMLArray( DBGet( DBQuery( "SELECT TITLE,'CUSTOM_'||ID AS COLUMN,
			TYPE,SELECT_OPTIONS
			FROM " . ( $type === 'staff' ? 'STAFF' : 'CUSTOM' ) . "_FIELDS" ),
			array(), array( 'COLUMN' )	), 'TITLE' );

		if ( $type === 'staff' )
		{
			// User Fields: search Email Address & Phone.
			$fields['EMAIL'][1] = array(
				'TITLE' => _( 'Email Address' ),
				'COLUMN' => 'EMAIL',
				'TYPE' => 'text',
				'SELECT_OPTIONS' => null,
			);

			$fields['PHONE'][1] = array(
				'TITLE' => _( 'Phone Number' ),
				'COLUMN' => 'PHONE',
				'TYPE' => 'text',
				'SELECT_OPTIONS' => null,
			);
		}
		else
		{
			// Student Fields: search Username.
			$fields['USERNAME'][1] = array(
				'TITLE' => _( 'Username' ),
				'COLUMN' => 'USERNAME',
				'TYPE' => 'text',
				'SELECT_OPTIONS' => null,
			);
		}
	}

	foreach ( (array) $cust as $column => $value )
	{
		$field = $fields[ $column ][1] + array( 'VALUE' => $value );

		$return .= SearchField( $field, $type, $extra );
	}

	// Begin Dates / Number.
	foreach ( (array) $cust_begin as $column => $value )
	{
		$field = $fields[ $column ][1] + array( 'VALUE' => $value );

		$field['PART'] = 'begin';

		$return .= SearchField( $field, $type, $extra );
	}

	// End Dates / Number.
	foreach ( (array) $cust_end as $column => $value )
	{
		$field = $fields[ $column ][1] + array( 'VALUE' => $value );

		$field['PART'] = 'end';

		$return .= SearchField( $field, $type, $extra );
	}

	return $return;
}
