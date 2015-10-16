<?php

/**
 * Get the Date of the day
 * Database (Oracle) format, ready for SQL
 *
 * @example "SELECT SCHOOL_DATE
 *               FROM ATTENDANCE_CALENDAR
 *               WHERE SCHOOL_DATE<'" . DBDate() . "'";
 *
 * @example strtotime( DBDate() ) > strtotime( $res['DATE'] )
 *
 * @return string Date of the day
 */
function DBDate()
{
	// Oracle, eg. 10-JUL-2015
	return mb_strtoupper( date( 'd-M-Y' ) );
}


/**
 * Localized & preferred date
 * Accepts Oracle or Postgres date
 *
 * @see Preferences & http://php.net/manual/en/function.strftime.php
 *
 * @param  string $date   Date
 * @param  string $length long|short Month name length (optional)
 *
 * @return string Formatted & localized date or empty string if invalid format
 */
function ProperDate( $date, $length = 'long' )
{
	if ( empty( $date )
		|| mb_strlen( $date ) > 11
		|| mb_strlen( $date ) < 9 )
		return '';

	$date_exploded = ExplodeDate( $date );

	$comment = '<!-- ' . implode( '', $date_exploded ) . ' -->';

	// Export (Excel) date to MM/DD/YYYY format
	if ( isset( $_REQUEST['LO_save'] )
		&& Preferences( 'E_DATE' ) === 'MM/DD/YYYY' )
		return $comment .
			$date_exploded['month'] . '/' .
			$date_exploded['day'] . '/' .
			$date_exploded['year'];

	// FJ display locale with strftime()
	if( ( Preferences( 'MONTH' ) === '%m'
			|| Preferences( 'MONTH' ) === '%b' )
		&& Preferences( 'DAY' ) === '%d'
		&& Preferences( 'YEAR' ) )
		$sep = '/';
	else
		$sep = ' ';

	// Short month name, eg.: Sep
	if ( $length === 'short' )
		$pref_month = '%b';

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

	//FJ display locale with strftime()
	//FJ NOBR on date
	return $comment .
		'<span style="white-space:nowrap">' .
			mb_convert_case( iconv( '', 'UTF-8', strftime( $pref_date, $time ) ), MB_CASE_TITLE ) .
		'</span>';
}


/**
 * Verify date
 *
 * Accepts 3 dates formats (Oracle & Postgres)
 * 
 * @param  string  $date date to verify
 *
 * @return boolean true if valid date, else false
 */
function VerifyDate( $date )
{
	$date_exploded = ExplodeDate( $date );

	return checkdate(
		(int)$date_exploded['month'],
		(int)$date_exploded['day'],
		(int)$date_exploded['year']
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
 * @param  string  $date      Date to prepare
 * @param  string  $name_attr select inputs name attribute suffix (optional)
 * @param  boolean $allow_na  Allow N/A, defaults to true (optional)
 * @param  array   $options   Keys: Y|M|D|C|short|submit|required (optional)
 *
 * @return string  ProperDate (PDF) or date selection series of pull-down menus with an optional JS calendar
 */
function PrepareDate( $date, $name_attr = '', $allow_na = true, $options = array() )
{
	global $_ROSARIO;

	// PDF printing, display text date
	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
		return ProperDate( $date );

	$return = $extraY = $extraM = $extraD = '';

	$defaults = array(
		'Y' => false, // Year
		'M' => false, // Month
		'D' => false, // Day
		'C' => false, // JS Calendar
		'short' => false, // Short month
		'submit' => false, // Submit onchange
		'required' => false, // Required fields
	);

	/**
	 * If none of the Y|M|D|C options are set
	 * set them all to true
	 */
	if ( !isset( $options['Y'] )
		&& !isset( $options['M'] )
		&& !isset( $options['D'] )
		&& !isset( $options['C'] ) )
		$defaults = array_merge(
			$defaults,
			array(
				'Y' => true,
				'M' => true,
				'D' => true,
				'C' => true
			)
		);

	$options = array_merge( $defaults, $options );

	// Short month select input
	if ( $options['short'] )
		$extraM = ' style="max-width: 65px;"';

	// Submit on date change
	if ( $options['submit'] )
	{
		$URL_args = array( 'month' . $name_attr, 'day' . $name_attr, 'year' . $name_attr );

		$date_onchange_href = PreparePHP_SELF(
			$_REQUEST,
			$URL_args
		);

		// Create date onchange link
		// Add year / month / day parameters to href
		$add_args_js = array();

		foreach( (array)$URL_args as $URL_arg )
		{
			$add_args_js[] = '(this.form.' . $URL_arg . ' ? \'&' . $URL_arg . '=\' + this.form.' . $URL_arg . '.value : \'\')';
		}

		$e = ' onchange="ajaxLink( \'' . $date_onchange_href . '\' + ' . implode( '+', $add_args_js ) . ' );"';

		$extraM .= $e;

		$extraD .= $e;

		$extraY .= $e;
	}

	if ( $options['C'] )
		$_ROSARIO['PrepareDate']++;

	elseif ( !isset( $_ROSARIO['PrepareDate'] ) )
		$_ROSARIO['PrepareDate'] = null;

	// Required fields
	if ( $options['required'] )
	{
		$extraM .= " required";

		$extraD .= " required";

		$extraY .= " required";
	}

	$date_exploded = ExplodeDate( $date );

	$return .= '<!-- ' . implode( '', $date_exploded ) . ' -->';

	// MONTH  ---------------
	if ( $options['M'] )
	{
		$return .= '<select name="month' . $name_attr . '" id="monthSelect' . $_ROSARIO['PrepareDate'] . '"' . $extraM . '>';

		if ( $allow_na )
		{
			if ( $date_exploded['month'] === ''
				|| $date_exploded['month'] === '000' )
				$return .= '<option value="" selected>' . _( 'N/A' );
			else
				$return .= '<option value="">' . _( 'N/A' );
		}

		$months_locale = array(
			'JAN' => _( 'January' ),
			'FEB' => _( 'February' ),
			'MAR' => _( 'March' ),
			'APR' => _( 'April' ),
			'MAY' => _( 'May' ),
			'JUN' => _( 'June' ),
			'JUL' => _( 'July' ),
			'AUG' => _( 'August' ),
			'SEP' => _( 'September' ),
			'OCT' => _( 'October' ),
			'NOV' => _( 'November' ),
			'DEC' => _( 'December' )
		);

		$month_char = MonthNWSwitch( $date_exploded['month'], 'tochar' );

		foreach( $months_locale as $key => $name )
			$return .= '<option value="' . $key . '"' . ( $month_char == $key ? ' selected' : '' ) . '>' . $name;

		$return .= '</select>';
	}

	// DAY  ---------------
	if ( $options['D'] )
	{
		$return .= '<select name="day' . $name_attr . '" id="daySelect' . $_ROSARIO['PrepareDate'] . '"' . $extraD . '>';

		if ( $allow_na )
		{
			if ( $date_exploded['day'] === ''
				|| $date_exploded['day'] === '00' )
				$return .= '<option value="" selected>' . _( 'N/A' );
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
	}

	// YEAR  ---------------
	if ( $options['Y'] )
	{
		if ( $date_exploded['year'] === ''
			|| $date_exploded['year'] === '0000' )
		{
			//FJ show 80 previous years instead of 20
			$begin = date( 'Y' ) - 80;
			$end = date( 'Y' ) + 5;
		}
		else
		{
			//FJ show 20 previous years instead of 5
			$begin = $date_exploded['year'] - 20;
			$end = $date_exploded['year'] + 5;
		}

		$return .= '<select name="year' . $name_attr . '" id="yearSelect' . $_ROSARIO['PrepareDate'] . '"' . $extraY . '>';

		if ( $allow_na )
		{
			if ( $date_exploded['year'] === ''
				|| $date_exploded['year'] === '0000' )
				$return .= '<option value="" selected>' . _( 'N/A' );
			else
				$return .= '<option value="">' . _( 'N/A' );
		}

		for( $i = $begin; $i <= $end; $i++ )
			$return .= '<option value="' . $i . '"' . ( $date_exploded['year'] == $i ?' selected' : '' ) . '>' . $i;

		$return .= '</select>';
	}

	// CALENDAR  ---------------
	if ( $options['C'] )
		$return .= '<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/calendar.png" class="button cal" id="trigger' . $_ROSARIO['PrepareDate'] . '" />';

	//FJ NOBR on date input
	$return = '<span style="white-space: nowrap;">' . $return . '</span>';
	
	return $return;
}


/**
 * Explode a Postgres or Oracle date
 *
 * @param  string $date Postgres or Oracle date
 *
 * @return array  array( 'year' => '4_digits_year', 'month' => 'numeric_month', 'day' => 'day' ) 
 */
function ExplodeDate( $date )
{
	// Invalid format
	$year = $month = $day = '';

	// Oracle format DD-MMM-YY
	if ( mb_strlen( $date ) === 9 )
	{
		$year = mb_substr( $date, 7, 2 );

		$year = ( $year < 50 && $year > 0 ? '20' : '19' ) . $year;

		$month = MonthNWSwitch( mb_substr( $date, 3, 3 ), 'tonum' );

		$day = mb_substr( $date, 0, 2 );
	}
	// Postgres format YYYY-MM-DD
	elseif ( mb_strlen( $date ) === 10 )
	{
		$year = mb_substr( $date, 0, 4 );

		$month = mb_substr( $date, 5, 2 );

		$day = mb_substr( $date, 8, 2 );

	}
	// Oracle with 4-digits year DD-MMM-YYYY
	elseif ( mb_strlen( $date ) === 11 )
	{
		$year = mb_substr( $date, 7, 4 );

		$month = MonthNWSwitch( mb_substr( $date, 3, 3 ), 'tonum' );

		$day = mb_substr( $date, 0, 2 );
	}

	return array( 'year' => $year, 'month' => $month, 'day' => $day );
}

/**
 * Get date requested by User
 * Returns an empty string if date is malformed / incomplete
 * Returns a corrected date
 * if day does not exist in month,
 * for example, 31-FEB-2015 will return 28-FEB-2015
 *
 * @since 2.9
 *
 * @example RequestedDate( $day, $month, $year );
 *
 * @param  string $day   Requested day
 * @param  string $month Requested month
 * @param  string $year  Requested year
 *
 * @return string Empty string if malformed/incomplete date or date
 */
function RequestedDate( $day, $month, $year )
{
	$date = $day . '-' . $month . '-' . $year;

	/**
	 * Verify first this is a well-formed / complete date:
	 * DD-MMM-YYYY
	 * Day between 1 and 31
	 * Month: valid 3 letters abbreviation
	 * Year between 1 and 9999
	 */
	if ( mb_strlen( $date ) !== 11
		|| (int)$day < 1
		|| (int)$day > 31
		|| __mnwswitch_char2num( $month ) === $month
		|| (int)$year < 1
		|| (int)$year > 9999 )
	{
		$date = '';
	}
	else
	{
		// correct date if day does not exist in month
		while( !VerifyDate( $date ) )
		{
			$day--;

			$date = $day. '-' . $month . '-' . $year;
		}
	}

	return $date;
}


/**
 * Get dates requested by User
 *
 * Calls RequestedDate() function
 * Recursive function
 *
 * @since 2.9
 *
 * @example RequestedDates( $_REQUEST['day_tables'], $_REQUEST['month_tables'], $_REQUEST['year_tables'] );
 *
 * @param  array $day_array   Requested days
 * @param  array $month_array Requested months
 * @param  array $year_array  Requested years
 *
 * @return array Requested dates, or empty if no dates found or malformed/incomplete dates
 */
function RequestedDates( $day_array, $month_array, $year_array )
{
	$return = array();

	foreach ( (array)$month_array as $field_name => $month )
	{
		if ( !is_array( $month ) )
		{
			$date = RequestedDate(
				$day_array[$field_name],
				$month,
				$year_array[$field_name]
			);

			if ( !empty( $date ) )
				$return[$field_name] = $date;
		}
		else
		{
			$dates = RequestedDates( $day_array[$field_name], $month, $year_array[$field_name] );

			if ( !empty( $dates ) )
				$return[$field_name] = $dates;
		}
	}

	return $return;
}


/**
 * Switch Month to Number or Characters
 *
 * @param  string $month     number or characters month
 * @param  string $direction tonum|tochar|both (optional)
 *
 * @return string            Switched month
 */
function MonthNWSwitch( $month, $direction = 'both' )
{
	// To number
	if ( $direction === 'tonum' )
	{
		if ( mb_strlen( $month ) < 3 ) // assume already num.
			return $month;
		else
			return __mnwswitch_char2num( $month );
	}
	// To characters
	elseif ( $direction === 'tochar' )
	{
		if ( mb_strlen( $month === 3 ) ) // assume already char.
			return $month;
		else
			return __mnwswitch_num2char( $month );
	}
	// Both
	else
	{
		$month = __mnwswitch_num2char( $month );
		$month = __mnwswitch_char2num( $month );

		return $month;
	}
}


/**
 * Switch number month to characters
 * Local function
 *
 * @param  string $month number month
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
		'00' => 'DEC'
	);

	if ( mb_strlen( $month ) === 1 )
		$month = '0' . $month;

	if ( array_key_exists( $month, $months_number ) )
		return $months_number[$month];

	else 
		return $month;
}


/**
 * Switch characters month to number
 * Local function
 *
 * @param  string $month characters month
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
		'DEC' => '12'
	);

	$month = mb_strtoupper( $month );

	if ( array_key_exists( $month, $months_number ) )
		return $months_number[$month];

	else 
		return $month;
}
