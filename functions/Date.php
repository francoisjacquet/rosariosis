<?php
/**
 * Date functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */


/**
 * Get the Date of the day
 * Database ISO format, ready for SQL
 *
 * @example "SELECT SCHOOL_DATE
 *               FROM attendance_calendar
 *               WHERE SCHOOL_DATE<'" . DBDate() . "'";
 *
 * @example DBDate() > $res['DATE']
 *
 * @return string Date of the day
 */
function DBDate()
{
	// ISO, eg. 2015-07-10.
	return date( 'Y-m-d' );
}


/**
 * Localized & preferred date
 * Accepts Oracle or Postgres date
 *
 * @since 3.8 CSS add .proper-date class.
 * @since 7.1 Export (Excel) date to YYYY-MM-DD format (ISO).
 * @since 7.1 Select Date Format: Add Preferences( 'DATE' ).
 * @since 9.0 Fix PHP8.1 deprecated strftime() use strftime_compat() instead
 *
 * @see Preferences
 *
 * @param  string $date   Date.
 * @param  string $length long|short Month name length (optional).
 *
 * @return string Formatted & localized date or empty string if invalid format
 */
function ProperDate( $date, $length = 'long' )
{
	if ( empty( $date )
		|| ! VerifyDate( $date ) )
	{
		return '';
	}

	$date_exploded = ExplodeDate( (string) $date );

	$comment = '<!-- ' . $date . ' -->';

	// Export (Excel) date to MM/DD/YYYY format.
	if ( isset( $_REQUEST['LO_save'] )
		&& Preferences( 'E_DATE' ) === 'MM/DD/YYYY' )
	{
		return $comment .
			$date_exploded['month'] . '/' .
			$date_exploded['day'] . '/' .
			$date_exploded['year'];
	}

	// @since 7.1 Export (Excel) date to YYYY-MM-DD format (ISO).
	if ( isset( $_REQUEST['LO_save'] )
		&& Preferences( 'E_DATE' ) === 'YYYY-MM-DD' )
	{
		return $comment .
			$date_exploded['year'] . '-' .
			$date_exploded['month'] . '-' .
			$date_exploded['day'];
	}

	// Display localized date with strftime().
	// CSS add .proper-date class.
	// @since 9.0 Fix PHP8.1 deprecated strftime() use strftime_compat() instead
	return $comment .
		'<span class="proper-date">' . strftime_compat(
			Preferences( 'DATE' ),
			$date_exploded['year'] . '-' . $date_exploded['month'] . '-' . $date_exploded['day']
		) . '</span>';
}


/**
 * Localized & preferred Date & Time
 * "2015-09-21 16:35:42" => "September 21 2015 04:35:42 PM"
 *
 * If 'short' length & day = today, only time is returned.
 *
 * @since 2.9
 *
 * @example ProperDateTime( $value, 'short' );
 *
 * @uses ProperDate()
 *
 * @param string $datetime Datetime / Timestamp.
 * @param string $length   long|short (optional). Defaults to 'long'.
 */
function ProperDateTime( $datetime, $length = 'long' )
{
	try
	{
		// @since 9.0 Fix PHP8.1 deprecated strftime() use strftime_compat() instead
		$locale_time = strftime_compat( '%X', $datetime );
	}
	catch ( \Exception $e )
	{
		$local_time = '12:00:00';
	}

	// Remove trailing seconds :00.
	$locale_time = mb_substr( $locale_time, -3 ) === ':00' ? mb_substr( $locale_time, 0, -3 ) : $locale_time;

	// 0x202f is hex for "narrow no-break space" unicode character.
	$locale_time = str_replace( [ ':00 ', ':00 ' ], [ ' ', ' ' ], $locale_time );

	$date = mb_substr( $datetime, 0, 10 );

	// Add raw Datetime inside HTML comment. Used for sorting in ListOutput()
	$comment = '<!-- ' . $datetime . ' -->';

	if ( $length === 'short'
		&& DBDate() === $date )
	{
		// Today: only time!
		return $comment . $locale_time;
	}

	return $comment . ProperDate( $date, $length ) . ' ' . $locale_time;
}


/**
 * Localized Time
 * AM/PM: "16:35:42" => "04:35:42 PM"
 * 24H: "22:30:00" => "22:30"
 * Datetime: "2024-02-29 16:35:42" => "04:35:42 PM"
 *
 * @since 11.5
 *
 * @example ProperTime( $value );
 * @example DBGet( "SELECT ON_TIME FROM meetings", [ 'ON_TIME' => 'ProperTime' ] );
 *
 * @param string $time   Time or datetime.
 * @param string $column DB column (optional). When using this function as DBGet() callback.
 *
 * @return string Localized Time
 */
function ProperTime( $time, $column = '' )
{
	if ( ! $time )
	{
		return $time;
	}

	try
	{
		// Preferred time based on locale.
		$locale_time = strftime_compat( '%X', strtotime( $time ) );
	}
	catch ( \Exception $e )
	{
		return $time;
	}

	// Add raw time inside HTML comment (only if different from locale time). Used for sorting in ListOutput()
	$comment = $locale_time === $time ? '' : '<!-- ' . $time . ' -->';

	// Strip seconds :00.
	$locale_time = mb_substr( $locale_time, -3 ) === ':00' ? mb_substr( $locale_time, 0, -3 ) : $locale_time;

	// Strip seconds when AM/PM :00.
	// 0x202f is hex for "narrow no-break space" unicode character.
	$locale_time = str_replace( [ ':00 ', ':00 ' ], [ ' ', ' ' ], $locale_time );

	return $comment . $locale_time;
}


/**
 * Verify date
 *
 * Accepts 3 dates formats (Oracle & Postgres)
 *
 * @param  string $date date to verify.
 *
 * @return boolean true if valid date, else false
 */
function VerifyDate( $date )
{
	$date_exploded = ExplodeDate( (string) $date );

	if ( ! $date_exploded['month'] )
	{
		return false;
	}

	return checkdate(
		(int) $date_exploded['month'],
		(int) $date_exploded['day'],
		(int) $date_exploded['year']
	);
}


/**
 * Generate date pull-downs
 *
 * Send PrepareDate a date as the selected date
 * to have returned a date selection series of pull-down menus
 *
 * 3 date formats are accepted (Oracle & Postgres)
 *
 * For the default to be Not Specified,
 * send a date of 00-000-00 or send nothing
 *
 * @since 7.2 Order Day, Month & Year inputs depending on User date preference.
 *
 * @global array   $_ROSARIO Sets $_ROSARIO['PrepareDate']
 *
 * @param  string  $date      Date to prepare.
 * @param  string  $name_attr select inputs name attribute suffix (optional).
 * @param  boolean $allow_na  Allow N/A, defaults to true (optional).
 * @param  array   $options   Keys: Y|M|D|C|short|submit|required (optional).
 *
 * @return string  ProperDate (PDF) or date selection series of pull-down menus with an optional JS calendar
 */
function PrepareDate( $date, $name_attr = '', $allow_na = true, $options = [] )
{
	global $_ROSARIO;

	// PDF printing, display text date.
	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ProperDate( (string) $date );
	}

	$return = $extraY = $extraM = $extraD = '';

	$defaults = [
		'Y' => false, // Year.
		'M' => false, // Month.
		'D' => false, // Day.
		'C' => false, // JS Calendar.
		'short' => false, // Short month.
		'submit' => false, // Submit onchange.
		'required' => false, // Required fields.
	];

	if ( ! VerifyDate( $date ) )
	{
		// Fix PHP fatal error when not a date
		$date = '';
	}

	$options = array_replace_recursive( $defaults, (array) $options );

	/**
	 * If none of the Y|M|D|C options are true
	 * set them all to true.
	 */
	if ( ! $options['Y']
		&& ! $options['M']
		&& ! $options['D']
		&& ! $options['C'] )
	{
		$options['Y'] = $options['M'] = $options['D'] = $options['C'] = true;
	}

	// Short month select input.
	if ( $options['short'] )
	{
		$extraM = ' style="max-width: 65px;"';
	}

	// Submit on date change.
	if ( $options['submit'] )
	{
		$URL_args = [ 'month' . $name_attr, 'day' . $name_attr, 'year' . $name_attr ];

		$date_onchange_href = PreparePHP_SELF(
			$_REQUEST,
			$URL_args
		);

		// Create date onchange link
		// Add year / month / day parameters to href.
		$add_args_js = [];

		foreach ( $URL_args as $URL_arg )
		{
			$add_args_js[] = '(this.form.' . $URL_arg . ' ? \'&' . $URL_arg . '=\' + this.form.' . $URL_arg . '.value : \'\')';
		}

		$e = ' onchange="' . AttrEscape( 'ajaxLink(' . json_encode( $date_onchange_href ) .
			' + ' . implode( '+', $add_args_js ) . ' );' ) . '"';

		$extraM .= $e;

		$extraD .= $e;

		$extraY .= $e;
	}

	$_ROSARIO['PrepareDate'] = isset( $_ROSARIO['PrepareDate'] ) ?
		++$_ROSARIO['PrepareDate'] :
		1;

	// Required fields.
	if ( $options['required'] )
	{
		$extraM .= ' required';

		$extraD .= ' required';

		$extraY .= ' required';
	}

	$date_exploded = ExplodeDate( (string) $date );

	$return .= '<!-- ' . implode( '', $date_exploded ) . ' -->';

	$return_m = $return_d = $return_y = '';

	// MONTH  ---------------.
	if ( $options['M'] )
	{
		$return_m .= '<select name="month' . $name_attr . '" id="monthSelect' . $_ROSARIO['PrepareDate'] . '"' .
			$extraM . ' autocomplete="off">';

		if ( $allow_na )
		{
			if ( $date_exploded['month'] < 1 )
			{
				$return_m .= '<option value="" selected>' . _( 'N/A' );
			}
			else
				$return_m .= '<option value="">' . _( 'N/A' );
		}

		$months_locale = [
			'01' => _( 'January' ),
			'02' => _( 'February' ),
			'03' => _( 'March' ),
			'04' => _( 'April' ),
			'05' => _( 'May' ),
			'06' => _( 'June' ),
			'07' => _( 'July' ),
			'08' => _( 'August' ),
			'09' => _( 'September' ),
			'10' => _( 'October' ),
			'11' => _( 'November' ),
			'12' => _( 'December' ),
		];

		foreach ( $months_locale as $key => $name )
		{
			$return_m .= '<option value="' . AttrEscape( $key ) . '"' . ( $date_exploded['month'] == $key ? ' selected' : '' ) . '>' . $name;
		}

		$return_m .= '</select>';

		$return_m .= '<label for="monthSelect' . $_ROSARIO['PrepareDate'] . '" class="a11y-hidden">' .
			_( 'Month' ) . '</label>';
	}

	// DAY  ---------------.
	if ( $options['D'] )
	{
		$return_d .= '<select name="day' . $name_attr . '" id="daySelect' . $_ROSARIO['PrepareDate'] . '"' .
			$extraD . ' autocomplete="off">';

		if ( $allow_na )
		{
			if ( $date_exploded['day'] < 1 )
			{
				$return_d .= '<option value="" selected>' . _( 'N/A' );
			}
			else
				$return_d .= '<option value="">' . _( 'N/A' );
		}

		for ( $i = 1; $i <= 31; $i++ )
		{
			$print = $i;

			if ( $i < 10 )
				$print = '0' . $i;

			$return_d .= '<option value="' . AttrEscape( $print ) . '"' . ( $date_exploded['day'] == $print ? ' selected' : '' ) . '>' . $i;
		}

		$return_d .= '</select>';

		$return_d .= '<label for="daySelect' . $_ROSARIO['PrepareDate'] . '" class="a11y-hidden">' .
			_( 'Day' ) . '</label>';
	}

	// YEAR  ---------------.
	if ( $options['Y'] )
	{
		if ( $date_exploded['year'] < 1 )
		{
			// Show 80 previous years.
			$begin = date( 'Y' ) - 80;
			$end = date( 'Y' ) + 5;
		}
		else
		{
			// Show 20 previous years.
			$begin = $date_exploded['year'] - 20;
			$end = $date_exploded['year'] + 20;
		}

		$return_y .= '<select name="year' . $name_attr . '" id="yearSelect' . $_ROSARIO['PrepareDate'] . '"' .
			$extraY . ' autocomplete="off">';

		if ( $allow_na )
		{
			$return_y .= $date_exploded['year'] < 1 ?
					'<option value="" selected>' . _( 'N/A' ) :
					'<option value="">' . _( 'N/A' );
		}

		for ( $i = $begin; $i <= $end; $i++ )
		{
			$return_y .= '<option value="' . AttrEscape( $i ) . '"' . ( $date_exploded['year'] == $i ?' selected' : '' ) . '>' . $i;
		}

		$return_y .= '</select>';

		$return_y .= '<label for="yearSelect' . $_ROSARIO['PrepareDate'] . '" class="a11y-hidden">' .
			_( 'Year' ) . '</label>';
	}

	// @since 7.2 Order Day, Month & Year inputs depending on User date preference.
	$preferred_date_first = mb_substr( Preferences( 'DATE' ), 0, 2 );

	if ( $preferred_date_first === '%Y' )
	{
		// Year, Month, Day.
		$return .= $return_y . $return_m . $return_d;
	}
	elseif ( $preferred_date_first === '%d' )
	{
		// Day, Month, Year.
		$return .= $return_d . $return_m . $return_y;
	}
	else
	{
		// Month, Day, Year.
		$return .= $return_m . $return_d . $return_y;
	}

	// CALENDAR  ---------------.
	if ( $options['C'] )
	{
		$return .= '<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/calendar.png" title="' . AttrEscape( _( 'Open calendar' ) ) . '" class="button cal" alt="' . AttrEscape( _( 'Open calendar' ) ) . '" id="trigger' . $_ROSARIO['PrepareDate'] . '">';
	}

	// NOBR on date input.
	$return = '<span class="nobr">' . $return . '</span>';

	return $return;
}


/**
 * Explode a ISO or Oracle date
 *
 * @since 11.6 Use strtotime()
 * @link https://www.php.net/strtotime
 *
 * @param  string $date Postgres or Oracle date.
 *
 * @return array  [ 'year' => '4_digits_year', 'month' => 'numeric_month', 'day' => 'day' ]
 */
function ExplodeDate( $date )
{
	$timestamp = strtotime( $date );

	if ( empty( $timestamp ) )
	{
		return [ 'year' => '', 'month' => '', 'day' => '' ];
	}

	return [
		'year' => date( 'Y', $timestamp ),
		'month' => date( 'm', $timestamp ),
		'day' => date( 'd', $timestamp ),
	];
}

/**
 * Get date requested by User
 * Returns an empty string if date is malformed / incomplete
 * Returns a corrected date
 * if day does not exist in month,
 * for example, 2015-02-31 will return 2015-02-28
 *
 * @since 2.9
 * @since 4.5 Recursive function: use request index and default value.
 *
 * @example RequestedDate( $year, $month, $day );
 * @example $date_end = RequestedDate( 'end', DBDate() );
 * @example $date = RequestedDate( 'date', DBDate(), 'set' );
 *
 * @param  string $year_or_request_index Requested year.
 * @param  string $month_or_default      Requested month or default date.
 * @param  string $day_or_mode           Requested day or mode: [empty]|add|set.
 *
 * @return string Empty string if malformed/incomplete date or date or default.
 */
function RequestedDate( $year_or_request_index, $month_or_default, $day_or_mode = '' )
{
	if ( $day_or_mode === ''
		|| $day_or_mode === 'add'
		|| $day_or_mode === 'set' )
	{
		$request_index = $year_or_request_index;

		$default = strlen( (string) $month_or_default ) > 7 && VerifyDate( $month_or_default ) ? $month_or_default : '';

		$mode = $day_or_mode;

		if (  isset( $_REQUEST['day_' . $request_index ] )
			&& isset( $_REQUEST['month_' . $request_index ] )
			&& isset( $_REQUEST['year_' . $request_index ] ) )
		{
			$requested_date = RequestedDate(
				$_REQUEST['year_' . $request_index ],
				$_REQUEST['month_' . $request_index ],
				$_REQUEST['day_' . $request_index ]
			);

			if ( ! empty( $requested_date ) )
			{
				return $requested_date;
			}
		}

		if ( $mode === 'set'
			&& $default )
		{
			$exploded_date = ExplodeDate( (string) $default );

			$_REQUEST['year_' . $request_index ] = $exploded_date['year'];
			$_REQUEST['month_' . $request_index ] = $exploded_date['month'];
			$_REQUEST['day_' . $request_index ] = $exploded_date['day'];
		}

		if ( $mode === 'add' )
		{
			$_REQUEST[ $request_index ] = $default;
		}

		return $default;
	}

	$year = (int) $year_or_request_index;

	$month = str_pad( (int) $month_or_default, 2, '0', STR_PAD_LEFT );

	$day = str_pad( (int) $day_or_mode, 2, '0', STR_PAD_LEFT );

	$date = $year . '-' . $month . '-' . $day;

	/**
	 * Verify first this is an ISO date: YYYY-MM-DD
	 * Day between 1 and 31
	 * Month between 1 and 12
	 * Year between 1000 and 9999
	 */
	if ( mb_strlen( $date ) !== 10
		|| $day < 1
		|| $day > 31
		|| $month > 12
		|| $month < 1
		|| $year < 1000
		|| $year > 9999 )
	{
		return '';
	}

	// Correct date if day does not exist in month.
	while ( ! checkdate( $month, $day, $year )
		&& $day > 0 )
	{
		$day--;

		$date = $year . '-' . $month . '-' . $day;
	}

	return $date;
}


/**
 * Get dates requested by User
 *
 * @uses RequestedDate() function
 * Recursive function
 *
 * @since 2.9
 *
 * @example RequestedDates( $_REQUEST['year_tables'], $_REQUEST['month_tables'], $_REQUEST['day_tables'] );
 *
 * @param  array $year_array  Requested years.
 * @param  array $month_array Requested months.
 * @param  array $day_array   Requested days.
 *
 * @return array Requested dates, or empty if no dates found or malformed/incomplete dates
 */
function RequestedDates( $year_array, $month_array, $day_array )
{
	$return = [];

	foreach ( (array) $month_array as $field_name => $month )
	{
		if ( $month === false
			|| ! isset( $year_array[ $field_name ] )
			|| ! isset( $day_array[ $field_name ] ) )
		{
			// Fix PHP Notice: Array to string conversion when month set to false.
			continue;
		}

		if ( ! is_array( $month ) )
		{
			$return[ $field_name ] = RequestedDate(
				$year_array[ $field_name ],
				$month,
				$day_array[ $field_name ]
			);

			continue;
		}

		$dates = RequestedDates( $year_array[ $field_name ], $month, $day_array[ $field_name ] );

		if ( ! empty( $dates ) )
		{
			$return[ $field_name ] = $dates;
		}
	}

	return $return;
}


/**
 * Add dates requested by User
 * to the $_REQUEST (and $_POST) array index specified.
 *
 * @since 3.8
 *
 * @example AddRequestedDates( 'values', 'post' );
 *
 * @uses RequestedDates() function
 *
 * @param string $request_index $_REQUEST array index where we add requested dates.
 * @param string $add_to_post   Add to $_POST array too. Defaults to '' (optional).
 */
function AddRequestedDates( $request_index, $add_to_post = '' )
{
	if ( ! $request_index
		|| ! isset(
			$_REQUEST[ 'day_' . $request_index ],
			$_REQUEST[ 'month_' . $request_index ],
			$_REQUEST[ 'year_' . $request_index ]
		) )
	{
		return;
	}

	$requested_dates = RequestedDates(
		$_REQUEST[ 'year_' . $request_index ],
		$_REQUEST[ 'month_' . $request_index ],
		$_REQUEST[ 'day_' . $request_index ]
	);

	if ( isset( $_REQUEST[ $request_index ] ) && is_array( $_REQUEST[ $request_index ] ) )
	{
		$_REQUEST[ $request_index ] = array_replace_recursive(
			$_REQUEST[ $request_index ],
			$requested_dates
		);

		if ( $add_to_post === 'post' )
		{
			$_POST[ $request_index ] = array_replace_recursive(
				isset( $_POST[ $request_index ] ) ? $_POST[ $request_index ] : [],
				$requested_dates
			);
		}

		return;
	}

	$_REQUEST[ $request_index ] = $requested_dates;

	if ( $add_to_post === 'post' )
	{
		$_POST[ $request_index ] = $requested_dates;
	}
}
