<?php
/**
 * Date functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Set Postgres Session Date Format / Datestyle to ISO.
 *
 * @since 2.9
 */
DBQuery( "SET DATESTYLE='ISO'" );


/**
 * Get the Date of the day
 * Database ISO format, ready for SQL
 *
 * @example "SELECT SCHOOL_DATE
 *               FROM ATTENDANCE_CALENDAR
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
 *
 * @see Preferences & http://php.net/manual/en/function.strftime.php
 *
 * @param  string $date   Date.
 * @param  string $length long|short Month name length (optional).
 *
 * @return string Formatted & localized date or empty string if invalid format
 */
function ProperDate( $date, $length = 'long' )
{
	if ( empty( $date )
		|| mb_strlen( $date ) > 11
		|| mb_strlen( $date ) < 9 )
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

	// FJ display locale with strftime().
	if ( ( Preferences( 'MONTH' ) === '%m'
			|| Preferences( 'MONTH' ) === '%b' )
		&& Preferences( 'DAY' ) === '%d'
		&& Preferences( 'YEAR' ) )
	{
		$sep = '/';
	}
	else
		$sep = ' ';

	// Short month name, eg.: "Sep".
	if ( $length === 'short' )
	{
		$pref_month = '%b';
	}
	else
		$pref_month = Preferences( 'MONTH' );

	$pref_date = $pref_month . $sep . Preferences( 'DAY' ) . $sep . Preferences( 'YEAR' );

	$time = mktime(
		0,
		0,
		0,
		$date_exploded['month'] + 0,
		$date_exploded['day'] + 0,
		$date_exploded['year'] + 0
	);

	// Display localized date with strftime().
	// CSS add .proper-date class.
	return $comment .
		'<span class="proper-date">' .
			strftime( $pref_date, $time ) .
		'</span>';
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
	$time = mb_substr( $datetime, 11, 8 );

	$time = mktime(
		mb_substr( $time, 0, 2 ) + 0,
		mb_substr( $time, 3, 2 ) + 0,
		mb_substr( $time, 6, 2 ) + 0
	);

	$locale_time = strftime( '%X', $time );

	$date = mb_substr( $datetime, 0, 10 );

	if ( $length === 'short'
		&& DBDate() === $date )
	{
		// Today: only time!
		return $locale_time;
	}

	return ProperDate( $date, $length ) . ' ' . $locale_time;
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
 * @global array   $_ROSARIO Sets $_ROSARIO['PrepareDate']
 *
 * @param  string  $date      Date to prepare.
 * @param  string  $name_attr select inputs name attribute suffix (optional).
 * @param  boolean $allow_na  Allow N/A, defaults to true (optional).
 * @param  array   $options   Keys: Y|M|D|C|short|submit|required (optional).
 *
 * @return string  ProperDate (PDF) or date selection series of pull-down menus with an optional JS calendar
 */
function PrepareDate( $date, $name_attr = '', $allow_na = true, $options = array() )
{
	global $_ROSARIO;

	// PDF printing, display text date.
	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ProperDate( (string) $date );
	}

	$return = $extraY = $extraM = $extraD = '';

	$defaults = array(
		'Y' => false, // Year.
		'M' => false, // Month.
		'D' => false, // Day.
		'C' => false, // JS Calendar.
		'short' => false, // Short month.
		'submit' => false, // Submit onchange.
		'required' => false, // Required fields.
	);

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
		$URL_args = array( 'month' . $name_attr, 'day' . $name_attr, 'year' . $name_attr );

		$date_onchange_href = PreparePHP_SELF(
			$_REQUEST,
			$URL_args
		);

		// Create date onchange link
		// Add year / month / day parameters to href.
		$add_args_js = array();

		foreach ( (array) $URL_args as $URL_arg )
		{
			$add_args_js[] = '(this.form.' . $URL_arg . ' ? \'&' . $URL_arg . '=\' + this.form.' . $URL_arg . '.value : \'\')';
		}

		$e = ' onchange="ajaxLink( \'' . $date_onchange_href . '\' + ' . implode( '+', $add_args_js ) . ' );"';

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

	$date_exploded = ExplodeDate( $date );

	$return .= '<!-- ' . implode( '', $date_exploded ) . ' -->';

	// MONTH  ---------------.
	if ( $options['M'] )
	{
		$return .= '<select name="month' . $name_attr . '" id="monthSelect' . $_ROSARIO['PrepareDate'] . '"' . $extraM . '>';

		if ( $allow_na )
		{
			if ( $date_exploded['month'] < 1 )
			{
				$return .= '<option value="" selected>' . _( 'N/A' );
			}
			else
				$return .= '<option value="">' . _( 'N/A' );
		}

		$months_locale = array(
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
		);

		foreach ( (array) $months_locale as $key => $name )
		{
			$return .= '<option value="' . $key . '"' . ( $date_exploded['month'] == $key ? ' selected' : '' ) . '>' . $name;
		}

		$return .= '</select>';

		$return .= '<label for="monthSelect' . $_ROSARIO['PrepareDate'] . '" class="a11y-hidden">' .
			_( 'Month' ) . '</label>';
	}

	// DAY  ---------------.
	if ( $options['D'] )
	{
		$return .= '<select name="day' . $name_attr . '" id="daySelect' . $_ROSARIO['PrepareDate'] . '"' . $extraD . '>';

		if ( $allow_na )
		{
			if ( $date_exploded['day'] < 1 )
			{
				$return .= '<option value="" selected>' . _( 'N/A' );
			}
			else
				$return .= '<option value="">' . _( 'N/A' );
		}

		for ( $i = 1; $i <= 31; $i++ )
		{
			$print = $i;

			if ( $i < 10 )
				$print = '0' . $i;

			$return .= '<option value="' . $print . '"' . ( $date_exploded['day'] == $print ? ' selected' : '' ) . '>' . $i;
		}

		$return .= '</select>';

		$return .= '<label for="daySelect' . $_ROSARIO['PrepareDate'] . '" class="a11y-hidden">' .
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
			$end = $date_exploded['year'] + 5;
		}

		$return .= '<select name="year' . $name_attr . '" id="yearSelect' . $_ROSARIO['PrepareDate'] . '"' . $extraY . '>';

		if ( $allow_na )
		{
			$return .= $date_exploded['year'] < 1 ?
					'<option value="" selected>' . _( 'N/A' ) :
					'<option value="">' . _( 'N/A' );
		}

		for ( $i = $begin; $i <= $end; $i++ )
		{
			$return .= '<option value="' . $i . '"' . ( $date_exploded['year'] == $i ?' selected' : '' ) . '>' . $i;
		}

		$return .= '</select>';

		$return .= '<label for="yearSelect' . $_ROSARIO['PrepareDate'] . '" class="a11y-hidden">' .
			_( 'Year' ) . '</label>';
	}

	// CALENDAR  ---------------.
	if ( $options['C'] )
	{
		$return .= '<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/calendar.png" title="' . _( 'Open calendar' ) . '" class="button cal" alt="' . _( 'Open calendar' ) . '" id="trigger' . $_ROSARIO['PrepareDate'] . '" />';
	}

	// NOBR on date input.
	$return = '<span class="nobr">' . $return . '</span>';

	return $return;
}


/**
 * Explode a ISO or Oracle date
 *
 * @todo use strtotime()?
 *
 * @param  string $date Postgres or Oracle date.
 *
 * @return array  array( 'year' => '4_digits_year', 'month' => 'numeric_month', 'day' => 'day' )
 */
function ExplodeDate( $date )
{
	// Invalid format.
	$year = $month = $day = '';

	// Oracle format DD-MMM-YY.
	if ( mb_strlen( $date ) === 9 )
	{
		$year = mb_substr( $date, 7, 2 );

		$year = ( $year < 30 ? '20' : '19' ) . $year;

		$month = MonthNWSwitch( mb_substr( $date, 3, 3 ), 'tonum' );

		$day = mb_substr( $date, 0, 2 );
	}
	// ISO format YYYY-MM-DD.
	elseif ( mb_strlen( $date ) === 10 )
	{
		$year = mb_substr( $date, 0, 4 );

		$month = mb_substr( $date, 5, 2 );

		$day = mb_substr( $date, 8, 2 );

		if ( ! is_numeric( $year )
			&& is_numeric( mb_substr( $date, 6, 4 ) ) )
		{
			if ( mb_substr( $date, 2, 1 ) === '/' )
			{
				// US Format: MM/DD/YYYY.
				$year = mb_substr( $date, 6, 4 );

				$month = mb_substr( $date, 0, 2 );

				$day = mb_substr( $date, 3, 2 );
			}

			if ( mb_substr( $date, 2, 1 ) === '-'
				|| $month > 12 )
			{
				// European Format: DD-MM-YYYY.
				$year = mb_substr( $date, 6, 4 );

				$month = mb_substr( $date, 3, 2 );

				$day = mb_substr( $date, 0, 2 );
			}
		}
	}
	// Oracle with 4-digits year DD-MMM-YYYY.
	elseif ( mb_strlen( $date ) === 11 )
	{
		$year = mb_substr( $date, 7, 4 );

		$month = MonthNWSwitch( mb_substr( $date, 3, 3 ), 'tonum' );

		$day = mb_substr( $date, 0, 2 );
	}
	// Short European Format: DD-MM-YY.
	elseif ( mb_strlen( $date ) === 8 )
	{
		$year = mb_substr( $date, 6, 2 );

		// Add 19 or 20 to complete 4 digits year.
		$year .= 30 >= $year ? '19' : '20';

		$month = mb_substr( $date, 3, 2 );

		$day = mb_substr( $date, 0, 2 );
	}

	return array( 'year' => $year, 'month' => $month, 'day' => $day );
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

		$default = strlen( $month_or_default ) > 7 && VerifyDate( $month_or_default ) ? $month_or_default : '';

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
			$exploded_date = ExplodeDate( $default );

			$_REQUEST['year_' . $request_index ] = $exploded_date['year'];
			$_REQUEST['month_' . $request_index ] = $exploded_date['month'];
			$_REQUEST['day_' . $request_index ] = $exploded_date['day'];
		}

		if ( $mode === 'add' )
		{
			$REQUEST[ $request_index ] = $default;
		}

		return $default;
	}

	$year = $year_or_request_index;

	$month = $month_or_default;

	$day = $day_or_mode;

	$date = $year . '-' . $month . '-' . $day;

	/**
	 * Verify first this is an ISO date: YYYY-MM-DD
	 * Day between 1 and 31
	 * Month between 1 and 12
	 * Year between 1000 and 9999
	 */
	if ( mb_strlen( $date ) !== 10
		|| (int) $day < 1
		|| (int) $day > 31
		|| (int) $month > 12
		|| (int) $month < 1
		|| (int) $year < 1000
		|| (int) $year > 9999 )
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
	$return = array();

	foreach ( (array) $month_array as $field_name => $month )
	{
		if ( ! is_array( $month ) )
		{
			$return[ $field_name ] = RequestedDate(
				$year_array[ $field_name ],
				$month,
				$day_array[ $field_name ]
			);
		}
		else
		{
			$dates = RequestedDates( $year_array[ $field_name ], $month, $day_array[ $field_name ] );

			if ( ! empty( $dates ) )
			{
				$return[ $field_name ] = $dates;
			}
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

	if ( is_array( $_REQUEST[ $request_index ] ) )
	{
		$_REQUEST[ $request_index ] = array_replace_recursive(
			(array) $_REQUEST[ $request_index ],
			(array) $requested_dates
		);

		if ( $add_to_post === 'post' )
		{
			$_POST[ $request_index ] = array_replace_recursive(
				(array) $_POST[ $request_index ],
				(array) $requested_dates
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


/**
 * Switch Month to Number or Characters
 *
 * @deprecated since 2.9 use ISO format.
 *
 * @param  string $month     number or characters month.
 * @param  string $direction tonum|tochar|both (optional). Default to 'both'.
 *
 * @return string            Switched month
 */
function MonthNWSwitch( $month, $direction = 'both' )
{
	// To number.
	if ( $direction === 'tonum' )
	{
		if ( mb_strlen( $month ) < 3 ) // Assume already num.
		{
			return $month;
		}

		return __mnwswitch_char2num( $month );
	}
	// To characters.
	elseif ( $direction === 'tochar' )
	{
		if ( mb_strlen( $month === 3 ) ) // Assume already char.
		{
			return $month;
		}

		return __mnwswitch_num2char( $month );
	}
	// Both.

	$month = __mnwswitch_num2char( $month );
	$month = __mnwswitch_char2num( $month );

	return $month;
}


/**
 * Switch number month to characters
 * Local function
 *
 * @deprecated since 2.9 use ISO format.
 *
 * @param  string $month number month.
 *
 * @return string        characters month
 */
function __mnwswitch_num2char( $month )
{
	$months_number = array(
		'01' => 'JAN',
		'02' => 'FEB',
		'03' => 'MAR',
		'04' => 'APR',
		'05' => 'MAY',
		'06' => 'JUN',
		'07' => 'JUL',
		'08' => 'AUG',
		'09' => 'SEP',
		'10' => 'OCT',
		'11' => 'NOV',
		'12' => 'DEC',
		'00' => 'DEC',
	);

	if ( mb_strlen( $month ) === 1 )
	{
		$month = '0' . $month;
	}

	if ( array_key_exists( $month, $months_number ) )
	{
		return $months_number[ $month ];
	}
	else
		return $month;
}


/**
 * Switch characters month to number
 * Local function
 *
 * @deprecated since 2.9 use ISO format.
 *
 * @param  string $month characters month.
 *
 * @return string        number month
 */
function __mnwswitch_char2num( $month )
{
	$months_number = array(
		'JAN' => '01',
		'FEB' => '02',
		'MAR' => '03',
		'APR' => '04',
		'MAY' => '05',
		'JUN' => '06',
		'JUL' => '07',
		'AUG' => '08',
		'SEP' => '09',
		'OCT' => '10',
		'NOV' => '11',
		'DEC' => '12',
	);

	$month = mb_strtoupper( $month );

	if ( array_key_exists( $month, $months_number ) )
	{
		return $months_number[ $month ];
	}
	else
		return $month;
}
