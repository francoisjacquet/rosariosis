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
	$cust_begin = array();

	if ( isset( $_REQUEST['day_cust_begin'] )
		&& isset( $_REQUEST['month_cust_begin'] )
		&& isset( $_REQUEST['year_cust_begin'] ) )
	{
		$cust_begin = RequestedDates(
			$_REQUEST['day_cust_begin'],
			$_REQUEST['month_cust_begin'],
			$_REQUEST['year_cust_begin']
		);
	}

	if ( isset( $_REQUEST['cust_begin'] ) )
	{
		// Add begin Number.
		$cust_begin += (array) $_REQUEST['cust_begin'];
	}


	// Format & Verify end dates.
	$cust_end = array();

	if ( isset( $_REQUEST['day_cust_end'] )
		&& isset( $_REQUEST['month_cust_end'] )
		&& isset( $_REQUEST['year_cust_end'] ) )
	{
		$cust_end = RequestedDates(
			$_REQUEST['day_cust_end'],
			$_REQUEST['month_cust_end'],
			$_REQUEST['year_cust_end']
		);
	}

	if ( isset( $_REQUEST['cust_end'] ) )
	{
		// Add end Number.
		$cust_end += (array) $_REQUEST['cust_end'];
	}


	// Get custom (staff) fields.
	if ( count( $cust )
		|| count( $cust_begin )
		|| count( $cust_end )
		|| ( isset( $_REQUEST['cust_null'] )
			&& count( (array) $_REQUEST['cust_null'] ) ) )
	{
		$fields = ParseMLArray( DBGet( DBQuery( "SELECT TITLE,ID,TYPE,SELECT_OPTIONS
			FROM " . ( $type === 'staff' ? 'STAFF' : 'CUSTOM' ) . "_FIELDS" ), array(), array( 'ID' ) ), 'TITLE' );
	}

	foreach ( (array) $cust as $field_name => $value )
	{
		$field_id = mb_substr( $field_name, 7 );

		$field_title = $fields[ $field_id ][1]['TITLE'];

		if ( ! $extra['NoSearchTerms'] )
		{
			$_ROSARIO['SearchTerms'] .= '<b>' . $field_title . ': </b>';
		}

		switch ( $fields[ $field_id ][1]['TYPE'] )
		{
			// Checkbox.
			case 'radio':

				// Yes.
				if ( $value == 'Y' )
				{
					$return .= ' AND s.' . $field_name . "='" . $value . "' ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= _( 'Yes' );
					}
				}
				// No.
				elseif ( $value == 'N' )
				{
					$return .= ' AND (s.' . $field_name . "!='Y' OR s." . $field_name . " IS NULL) ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= _( 'No' );
					}
				}

			break;

			// Export Pull-Down.
			case 'exports':
			// Coded Pull-Down.
			case 'codeds':

				// No Value.
				if ( $value === '!' )
				{
					$return .= ' AND (s.' . $field_name . "='' OR s." . $field_name . " IS NULL) ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= _( 'No Value' );
					}
				}
				else
				{
					$return .= ' AND s.' . $field_name . "='" . $value . "' ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $fields[ $field_id ][1]['SELECT_OPTIONS'] ) );

						foreach ( (array) $select_options as $option )
						{
							$option = explode( '|', $option );

							if ( $fields[ $field_id ][1]['TYPE'] == 'exports'
								&& $option[0] !== ''
								&& $value == $option[0] )
							{
								$value = $option[0];
								break;
							}
							// Codeds.
							elseif ( $option[0] !== ''
								&& $option[1] !== ''
								&& $value == $option[0] )
							{
								$value = $option[1];
								break;
							}
						}

						$_ROSARIO['SearchTerms'] .= $value;
					}
				}

			break;

			// Pull-Down.
			case 'select':
			// Auto Pull-Down.
			case 'autos':
			// Edit Pull-Down.
			case 'edits':

				// No Value.
				if ( $value === '!' )
				{
					$return .= ' AND (s.' . $field_name . "='' OR s." . $field_name . " IS NULL) ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= _( 'No Value' );
					}
				}
				// Other Value (Edit Pull-Down only).
				elseif ( $fields[ $field_id ][1]['TYPE'] == 'edits'
					&& $value === '~' )
				{
					$return .= " AND position('\r'||s." . $field_name . "||'\r'
						IN '\r'||(SELECT SELECT_OPTIONS
							FROM " . ( $type === 'staff' ? 'STAFF' : 'CUSTOM' ) . "_FIELDS
							WHERE ID='" . $field_id . "')||'\r')=0 ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= _( 'Other Value' );
					}
				}
				else
				{
					$return .= ' AND s.' . $field_name . "='" . $value . "' ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= $value;
					}
				}

			break;

			// Text
			// Enter '!' for No Value
			// Enter text inside double quotes "" for exact search.
			case 'text':

				// No value.
				if ( $value === '!' )
				{
					$return .= ' AND (s.' . $field_name . "='' OR s." . $field_name . " IS NULL) ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= _( 'No Value' );
					}
				}
				// Matches "searched expression".
				elseif ( mb_substr( $value, 0, 1 ) === '"'
					&& mb_substr( $value, -1 ) === '"' )
				{
					$return .= ' AND s.' . $field_name . "='" . mb_substr( $value, 1, -1 ) . "' ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= mb_substr( $value, 1, -1 );
					}
				}
				// Starts with.
				else
				{
					$return .= ' AND LOWER(s.' . $field_name . ") LIKE '" . mb_strtolower( $value ) . "%' ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= _('starts with') . ' ' .
							str_replace( "''", "'", $value );
					}
				}

			break;
		}

		if ( ! $extra['NoSearchTerms'] )
		{
			$_ROSARIO['SearchTerms'] .= '<br />';
		}
	}

	// Begin Dates / Number.
	foreach ( (array) $cust_begin as $field_name => $value )
	{
		$field_id = mb_substr( $field_name, 7 );

		$field_title = $fields[ $field_id ][1]['TITLE'];

		if ( $fields[ $field_id ][1]['TYPE'] === 'numeric' )
		{
			$value = preg_replace( '/[^0-9.-]+/', '', $value );
		}

		if ( $value !== '' )
		{
			$return .= ' AND s.' . $field_name . " >= '" . $value . "' ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$_ROSARIO['SearchTerms'] .= '<b>' . $field_title . ': </b>' .
					'<span class="sizep2">&ge;</span> ';

				if ( $fields[ $field_id ][1]['TYPE'] === 'date' )
				{
					$_ROSARIO['SearchTerms'] .= ProperDate( $value );
				}
				else
					$_ROSARIO['SearchTerms'] .= $value;

				$_ROSARIO['SearchTerms'] .= '<br />';
			}
		}
	}

	// End Dates / Number.
	foreach ( (array) $cust_end as $field_name => $value )
	{
		$field_id = mb_substr( $field_name, 7 );

		$field_title = $fields[ $field_id ][1]['TITLE'];

		if ( $fields[ $field_id ][1]['TYPE'] === 'numeric' )
		{
			$value = preg_replace( '/[^0-9.-]+/', '', $value );
		}

		if ( $value !== '' )
		{
			$return .= ' AND s.' . $field_name . " <= '" . $value . "' ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$_ROSARIO['SearchTerms'] .= '<b>' . $field_title . ': </b>' .
					'<span class="sizep2">&le;</span> ';

				if ( $fields[ $field_id ][1]['TYPE'] === 'date' )
				{
					$_ROSARIO['SearchTerms'] .= ProperDate( $value );
				}
				else
					$_ROSARIO['SearchTerms'] .= $value;

				$_ROSARIO['SearchTerms'] .= '<br />';
			}
		}
	}

	if ( isset( $_REQUEST['cust_null'] ) )
	{
		// No Value for Dates & Number.
		foreach ( (array) $_REQUEST['cust_null'] as $field_name => $y )
		{
			$field_id = mb_substr( $field_name, 7 );

			$field_title = $fields[ $field_id ][1]['TITLE'];

			$return .= ' AND s.' . $field_name . " IS NULL ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$_ROSARIO['SearchTerms'] .= '<b>' . $field_title . ': </b>' .
					_( 'No Value' ) . '<br />';
			}
		}
	}

	return $return;
}
