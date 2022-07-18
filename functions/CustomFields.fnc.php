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
 *
 * @example Use in the where section of the query:
 *          $extra['WHERE'] .= CustomFields( 'where' );
 *
 * @uses SearchField()
 *
 * @since 8.8 Fix PHP Fatal error Unsupported operand types in Teacher Programs: do not search Students List, unset!
 * @since 10.0 SQL rename $field COLUMN (reserved keyword) to COLUMN_NAME for MySQL compatibility
 *
 * @param  string $location part of the SQL statement (always 'where').
 * @param  string $type     student|staff (optional).
 * @param  array  $extra    disable search terms: array( 'NoSearchTerms' => true ) (optional).
 *
 * @return string           Custom Fields SQL WHERE
 */
function CustomFields( $location, $type = 'student', $extra = [] )
{
	$return = '';

	// If location === 'from', return.
	if ( $location !== 'where' )
	{
		return $return;
	}

	// if location === 'where':

	// Unset empty values.
	$cust = [];

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
	$cust_begin = issetVal( $_REQUEST['cust_begin'] );

	// Format & Verify end dates.
	AddRequestedDates( 'cust_end' );

	// Add end dates and end Number.
	$cust_end = issetVal( $_REQUEST['cust_end'] );

	// Get custom (staff) fields.
	if ( ! empty( $cust )
		|| ! empty( $cust_begin )
		|| ! empty( $cust_end )
		|| ! empty( $_REQUEST['cust_null'] ) )
	{
		$fields = ParseMLArray( DBGet( "SELECT TITLE,CONCAT('CUSTOM_', ID) AS COLUMN_NAME,
			TYPE,SELECT_OPTIONS
			FROM " . ( $type === 'staff' ? 'staff' : 'custom' ) . "_fields",
			[], [ 'COLUMN_NAME' ] ), 'TITLE' );

		if ( $type !== 'staff' )
		{
			// Student Fields: search Username.
			$fields['USERNAME'][1] = [
				'TITLE' => _( 'Username' ),
				'COLUMN_NAME' => 'USERNAME',
				'TYPE' => 'text',
				'SELECT_OPTIONS' => null,
			];
		}
	}

	foreach ( $cust as $column => $value )
	{
		if ( $type === 'staff'
			&& $column === 'EMAIL' )
		{
			// @since 5.9 Move Email & Phone Staff Fields to custom fields.
			$column = 'CUSTOM_200000000';

			$fields[ $column ][1]['COLUMN_NAME'] = 'EMAIL';
		}

		if ( ! isset( $fields[ $column ] ) )
		{
			continue;
		}

		$field = $fields[ $column ][1] + [ 'VALUE' => $value ];

		$return .= SearchField( $field, $type, $extra );

		if ( $type === 'staff'
			&& $_REQUEST['modname'] === 'Users/TeacherPrograms.php' )
		{
			// Fix for Teacher Programs: do not search Students List, unset!
			$_REQUEST['cust'][ $column ] = '';
		}
	}

	// Begin Dates / Number.
	foreach ( (array) $cust_begin as $column => $value )
	{
		if ( ! isset( $fields[ $column ] ) )
		{
			continue;
		}

		$field = $fields[ $column ][1] + [ 'VALUE' => $value ];

		$field['PART'] = 'begin';

		$return .= SearchField( $field, $type, $extra );

		if ( $type === 'staff'
			&& $_REQUEST['modname'] === 'Users/TeacherPrograms.php' )
		{
			// Fix for Teacher Programs: do not search Students List, unset!
			$_REQUEST['cust_begin'][ $column ] = '';
		}
	}

	// End Dates / Number.
	foreach ( (array) $cust_end as $column => $value )
	{
		if ( ! isset( $fields[ $column ] ) )
		{
			continue;
		}

		$field = $fields[ $column ][1] + [ 'VALUE' => $value ];

		$field['PART'] = 'end';

		$return .= SearchField( $field, $type, $extra );

		if ( $type === 'staff'
			&& $_REQUEST['modname'] === 'Users/TeacherPrograms.php' )
		{
			// Fix for Teacher Programs: do not search Students List, unset!
			$_REQUEST['cust_end'][ $column ] = '';
		}
	}

	return $return;
}
