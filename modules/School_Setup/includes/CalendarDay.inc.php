<?php
/**
 * Calendar Day functions
 * Functions can be redefined in custom plugin or module
 *
 * @since 4.5
 *
 * @package RosarioSIS
 */

if ( ! function_exists( 'CalendarDayClasses' ) )
{
	/**
	 * Calendar Day CSS classes
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param int    $minutes     Minutes.
	 * @param array  $events      Events array (optional).
	 * @param array  $assignments Assignments array (optional).
	 * @param string $mode        Mode: day|inner|number (optional).
	 *
	 * @return string HTML
	 */
	function CalendarDayClasses( $date, $minutes, $events = [], $assignments = [], $mode = 'day' )
	{
		return CalendarDayClassesDefault( $date, $minutes, $events, $assignments, $mode );
	}
}

/**
 * Calendar Day CSS classes
 * Default function
 *
 * @since 4.5
 *
 * @param string $date        ISO date.
 * @param int    $minutes     Minutes.
 * @param array  $events      Events array (optional).
 * @param array  $assignments Assignments array (optional).
 * @param string $mode        Mode: day|inner|number (optional).
 *
 * @return string HTML
 */
function CalendarDayClassesDefault( $date, $minutes, $events = [], $assignments = [], $mode = 'day' )
{
	global $return_counter;

	$day_classes = '';

	if ( $mode === 'inner' )
	{
		if ( AllowEdit()
			|| ! empty( $minutes )
			|| ! empty( $events )
			|| ! empty( $assignments ) )
		{
			// Hover CSS class.
			$day_classes .= ' hover';
		}

		return $day_classes;
	}

	if ( $mode === 'number' )
	{
		$day_classes = 'number';

		if ( ! empty( $events )
			|| ! empty( $assignments ) )
		{
			// Bold class.
			$day_classes .= ' bold';
		}

		return $day_classes;
	}

	if ( ! empty( $minutes ) )
	{
		if ( $minutes === '999' )
		{
			// Full School Day.
			$day_classes .= ' full';
		}
		else
		{
			// Minutes School Day.
			$day_classes .= ' minutes';
		}
	}
	else
	{
		// No School Day.
		$day_classes .= ' no-school';
	}

	// Thursdays, Fridays, Saturdays.
	if ( ($return_counter + 1) % 7 === 0
		|| ($return_counter + 1) % 7 > 4 )
	{
		$day_classes .= ' thu-fri-sat';
	}

	return $day_classes;
}


if ( ! function_exists( 'CalendarDayMinutesHTML' ) )
{
	/**
	 * Calendar Day Minutes HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param int    $minutes     Minutes.
	 *
	 * @return string HTML
	 */
	function CalendarDayMinutesHTML( $date, $minutes )
	{
		return CalendarDayMinutesHTMLDefault( $date, $minutes );
	}
}

/**
 * Calendar Day Minutes HTML
 * Default function
 *
 * @since 4.5
 *
 * @param string $date        ISO date.
 * @param int    $minutes     Minutes.
 *
 * @return string HTML
 */
function CalendarDayMinutesHTMLDefault( $date, $minutes )
{
	$html = '';

	if ( ! AllowEdit() )
	{
		return $html;
	}

	$div = ! empty( $minutes );

	// Minutes.
	if ( empty( $minutes ) && ! isset( $_REQUEST['_ROSARIO_PDF'] )
		|| $minutes === '999' )
	{
		$html .= CheckboxInput(
			$minutes,
			'all_day[' . $date . ']',
			'<span class="a11y-hidden">' . _( 'All Day' ) . '</span>',
			'',
			false,
			button( 'check' ),
			'',
			$div,
			'title="' . AttrEscape( _( 'All Day' ) ) . '"'
		);

		if ( empty( $minutes ) )
		{
			$html .= '&nbsp;';
		}
	}

	if ( empty( $minutes ) && ! isset( $_REQUEST['_ROSARIO_PDF'] )
		|| $minutes !== '999' )
	{
		$html .= TextInput(
			$minutes,
			'minutes[' . $date . ']',
			'<span class="a11y-hidden">' . _( 'Minutes' ) . '</span>',
			'size="3" type="number" min="1" max="998" title="' . AttrEscape( _( 'Minutes' ) ) . '"',
			$div
		);
	}

	return $html;
}

if ( ! function_exists( 'CalendarDayBlockHTML' ) )
{
	/**
	 * Calendar Day Minutes HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param int    $minutes     Minutes.
	 * @param string $day_block   Day block.
	 *
	 * @return string HTML
	 */
	function CalendarDayBlockHTML( $date, $minutes, $day_block )
	{
		return CalendarDayBlockHTMLDefault( $date, $minutes, $day_block );
	}
}

/**
 * Calendar Day Minutes HTML
 * Default function
 *
 * @since 4.5
 *
 * @param string $date        ISO date.
 * @param int    $minutes     Minutes.
 * @param string $day_block   Day block.
 *
 * @return string HTML
 */
function CalendarDayBlockHTMLDefault( $date, $minutes, $day_block )
{
	static $block_options = null;

	if ( is_null( $block_options ) )
	{
		// Get Blocks
		$blocks_RET = DBGet( "SELECT DISTINCT BLOCK
			FROM school_periods
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND BLOCK IS NOT NULL
			ORDER BY BLOCK" );

		$block_options = [];

		foreach ( (array) $blocks_RET as $block )
		{
			$block_options[ $block['BLOCK'] ] = $block['BLOCK'];
		}
	}

	$html = '';

	// Blocks.
	if ( $day_block
		|| ( User( 'PROFILE' ) === 'admin' && ! empty( $minutes ) && $block_options ) )
	{
		$html .= SelectInput(
			$day_block,
			'blocks[' . $date . ']',
			'<span class="a11y-hidden">' . _( 'Block' ) . '</span>',
			$block_options,
			( isset( $_REQUEST['_ROSARIO_PDF'] ) || ! AllowEdit() ? '' : 'N/A' ),
			'title="' . AttrEscape( _( 'Block' ) ) . '"'
		);
	}

	return $html;
}

if ( ! function_exists( 'CalendarDayEventsHTML' ) )
{
	/**
	 * Calendar Day Events HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param array  $events      Events array.
	 *
	 * @return string HTML
	 */
	function CalendarDayEventsHTML( $date, $events )
	{
		return CalendarDayEventsHTMLDefault( $date, $events );
	}
}

/**
 * Calendar Day Events HTML
 * Default function
 *
 * @since 4.5
 *
 * @param string $date        ISO date.
 * @param array  $events      Events array.
 *
 * @return string HTML
 */
function CalendarDayEventsHTMLDefault( $date, $events )
{
	$html = '';

	foreach ( (array) $events as $event )
	{
		$title = ( $event['TITLE'] ? $event['TITLE'] : '***' );

		$onclick_js = 'CalEventPopup(popupURL + ' .
			json_encode( URLEscape( '&event_id=' . $event['ID'] ) ) . '); return false;';

		$html .= '<div>' .
			( AllowEdit() || $event['DESCRIPTION'] ?
				'<a href="#" onclick="' . AttrEscape( $onclick_js ) . '" title="' . AttrEscape( $title ) . '">' .
				$title . '</a>'
				: '<span title="' . AttrEscape( $title ) . '">' . $title . '</span>'
			) .
		'</div>';
	}

	return $html;
}


if ( ! function_exists( 'CalendarDayAssignmentsHTML' ) )
{
	/**
	 * Calendar Day Assignments HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param array  $assignments Assignments array.
	 *
	 * @return string HTML
	 */
	function CalendarDayAssignmentsHTML( $date, $assignments )
	{
		return CalendarDayAssignmentsHTMLDefault( $date, $assignments );
	}
}

/**
 * Calendar Day Assignments HTML
 * Default function
 *
 * @since 4.5
 *
 * @param string $date        ISO date.
 * @param array  $assignments Assignments array.
 *
 * @return string HTML
 */
function CalendarDayAssignmentsHTMLDefault( $date, $assignments )
{
	$html = '';

	foreach ( (array) $assignments as $assignment )
	{
		$onclick_js = 'CalEventPopup(popupURL + ' .
			json_encode( URLEscape( '&assignment_id=' . $assignment['ID'] ) ) . '); return false;';

		$html .= '<div class="calendar-event assignment' . ( $assignment['ASSIGNED'] == 'Y' ? ' assigned' : '' ) . '">' .
			'<a href="#" onclick="' . AttrEscape( $onclick_js ) . '" title="' . AttrEscape( $assignment['TITLE'] ) . '">' .
				$assignment['TITLE'] .
			'</a>
		</div>';
	}

	return $html;
}


if ( ! function_exists( 'CalendarDayNewAssignmentHTML' ) )
{
	/**
	 * Calendar Day New Assignment HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param array  $assignments Assignments array.
	 *
	 * @return string HTML
	 */
	function CalendarDayNewAssignmentHTML( $date, $assignments )
	{
		return CalendarDayNewAssignmentHTMLDefault( $date, $assignments );
	}
}

/**
 * Calendar Day New Assignment HTML
 * Default function
 *
 * @since 4.5
 *
 * @param string $date        ISO date.
 * @param array  $assignments Assignments array.
 *
 * @return string HTML
 */
function CalendarDayNewAssignmentHTMLDefault( $date, $assignments )
{
	$html = '';

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$onclick_js = 'CalEventPopup(popupURL + ' .
			json_encode( URLEscape( '&school_date=' . $date . '&event_id=new' ) ) . '); return false;';

		// New Event.
		$html .= '<td>' .
			button(
				'add',
				'',
				'"#" onclick="' . AttrEscape( $onclick_js ) . '" title="' . AttrEscape( _( 'New Event' ) ) . '"'
			) .
		'</td>';
	}

	return $html;
}


if ( ! function_exists( 'CalendarDayRotationNumberHTML' ) )
{
	/**
	 * Calendar Day Rotation Number HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param int    $minutes     Minutes.
	 *
	 * @return string HTML
	 */
	function CalendarDayRotationNumberHTML( $date, $minutes )
	{
		return CalendarDayRotationNumberHTMLDefault( $date, $minutes );
	}
}

/**
 * Calendar Day Rotation Number HTML
 * Default function
 *
 * @since 4.5
 *
 * @param string $date        ISO date.
 * @param int    $minutes     Minutes.
 *
 * @return string HTML
 */
function CalendarDayRotationNumberHTMLDefault( $date, $minutes )
{
	$html = '';

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) > 0 )
	{
		require_once 'modules/School_Setup/includes/DayToNumber.inc.php';

		$html .= '<td class="align-right">' .
			( ( $day_number = dayToNumber( $date, $_REQUEST['calendar_id'] ) ) ? _( 'Day' ) . '&nbsp;' . $day_number : '&nbsp;' ) .
		'</td>';
	}

	return $html;
}
