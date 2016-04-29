<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

DrawHeader( ProgramTitle() );

// FJ days numbered.
if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) > 0 )
{
	require_once 'modules/School_Setup/includes/DayToNumber.inc.php';
}

// Set Month.
if ( ! isset( $_REQUEST['month'] )
	|| mb_strlen( $_REQUEST['month'] ) !== 2 )
{
	$_REQUEST['month'] = date( 'm' );
}

// Set Year.
if ( ! isset( $_REQUEST['year'] )
	|| mb_strlen( $_REQUEST['year'] ) !== 4 )
{
	$_REQUEST['year'] = date( 'Y' );
}

// Set Time = First Day of Month.
$time = mktime( 0, 0, 0, $_REQUEST['month'], 1, $_REQUEST['year'] );

// Create / Recreate Calendar.
if ( $_REQUEST['modfunc'] === 'create'
	&& AllowEdit() )
{
	$fy_RET = DBGet( DBQuery( "SELECT START_DATE,END_DATE
		FROM SCHOOL_MARKING_PERIODS
		WHERE MP='FY'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" ) );

	$fy = $fy_RET[1];

	// Get Calendars Info.
	$title_RET = DBGet( DBQuery( "SELECT ac.CALENDAR_ID,ac.TITLE,ac.DEFAULT_CALENDAR,ac.SCHOOL_ID,
		(SELECT coalesce(SHORT_NAME,TITLE)
			FROM SCHOOLS
			WHERE SYEAR=ac.SYEAR
			AND ID=ac.SCHOOL_ID) AS SCHOOL_TITLE,
		(SELECT min(SCHOOL_DATE)
			FROM ATTENDANCE_CALENDAR
			WHERE CALENDAR_ID=ac.CALENDAR_ID) AS START_DATE,
		(SELECT max(SCHOOL_DATE)
			FROM ATTENDANCE_CALENDAR
			WHERE CALENDAR_ID=ac.CALENDAR_ID) AS END_DATE
		FROM ATTENDANCE_CALENDARS ac,STAFF s
		WHERE ac.SYEAR='" . UserSyear() . "'
		AND s.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (s.SCHOOLS IS NULL OR position(','||ac.SCHOOL_ID||',' IN s.SCHOOLS)>0)
		ORDER BY " . db_case( array( 'ac.SCHOOL_ID', "'" . UserSchool() . "'", 0, 'ac.SCHOOL_ID' ) ) . ",ac.DEFAULT_CALENDAR ASC,ac.TITLE" ), array(), array( 'CALENDAR_ID' ) );

	// Prepare table for Copy Calendar & add ' (Default)' mention.
	$copy_calendar_options = array();

	foreach ( (array) $title_RET as $id => $title )
	{
		$copy_calendar_options[ $id ] = $title[1]['TITLE'];

		if ( AllowEdit()
			&& $title[1]['DEFAULT_CALENDAR'] === 'Y'
			&& $title[1]['SCHOOL_ID'] === UserSchool() )
		{
			$default_calendar = $title[1];

			$copy_calendar_options[ $id ] .= ' (' . _( 'Default' ) . ')';
		}
	}

	$div = false;

	$message = '<table class="width-100p valign-top"><tr class="st"><td>';

	// Title.
	$message .= TextInput(
		( $_REQUEST['calendar_id'] ? $default_calendar['TITLE'] : '' ),
		'title',
		'<span class="legend-red">' . _( 'Title' ) . '</span>',
		'required',
		$div
	);

	$message .= '</td><td>';

	// Default.
	$message .= CheckboxInput(
		$_REQUEST['calendar_id'] && $default_calendar['DEFAULT_CALENDAR'] == 'Y',
		'default',
		_( 'Default Calendar for this School' ),
		'',
		true
	);

	$message .= '</td><td>';

	// Copy calendar.
	$message .= SelectInput(
		$_REQUEST['calendar_id'],
		'copy_id',
		_( 'Copy Calendar' ),
		$copy_calendar_options,
		'N/A',
		'',
		$div
	);

	$message .= '</td></tr></table>';

	// From date.
	$message .= '<table class="width-100p valign-top"><tr class="st"><td>' . _( 'From' ) . ' ';

	$message .= DateInput(
		$_REQUEST['calendar_id'] && $default_calendar['START_DATE'] ?
			$default_calendar['START_DATE'] :
			$fy['START_DATE'],
		'min',
		'',
		$div,
		true,
		!( $_REQUEST['calendar_id'] && $default_calendar['START_DATE'] )
	);

	// to date
	$message .= '</td><td>' . _( 'To' )  . ' ';
	$message .= DateInput(
		$_REQUEST['calendar_id'] && $default_calendar['END_DATE'] ?
			$default_calendar['END_DATE'] :
			$fy['END_DATE'],
		'max',
		'',
		$div,
		true,
		!( $_REQUEST['calendar_id'] && $default_calendar['END_DATE'] )
	);

	$message .= '</td></tr></table>';

	$message .= '<table class="width-100p valign-top"><tr class="st"><td>';

	// weekdays
	$weekdays = array(
		_( 'Sunday' ),
		_( 'Monday' ),
		_( 'Tuesday' ),
		_( 'Wednesday' ),
		_( 'Thursday' ),
		_( 'Friday' ),
		_( 'Saturday' ),
	);

	$weekdays_inputs = array();

	foreach ( (array) $weekdays as $id => $weekday )
	{
		$value = 'Y';

		// unckeck Saturday & Sunday
		if ( ( $id === 0
				|| $id === 6 )
			&& $_REQUEST['calendar_id'] )
			$value = 'N';

		$weekdays_inputs[] .= CheckboxInput(
			$value,
			'weekdays[' . $id . ']',
			$weekday,
			'',
			true
		);
	}

	$message .= implode( '</td><td>', $weekdays_inputs );

	$message .= '</td></tr></table>';

	$message .= '<table class="width-100p"><tr class="st valign-top"><td>';

	// minutes
	$minutes_tip_text = ( $_REQUEST['calendar_id'] ?
		_( 'Default is Full Day if Copy Calendar is N/A.' ) . ' ' . _( 'Otherwise Default is minutes from the Copy Calendar' ) :
		_( 'Default is Full Day' )
	);

	$message .= TextInput(
		'',
		'minutes',
		_( 'Minutes' ) .
			'<div class="tooltip"><i>' . $minutes_tip_text . '</i></div>',
		'size="3" maxlength="3"',
		$div
	);

	$message .= '</td></tr></table>';

	$OK = Prompt(
		$_REQUEST['calendar_id'] ?
		sprintf( _( 'Recreate %s calendar' ), $prompt ) :
		_( 'Create new calendar' ),
		'',
		$message
	);

	// If Confirm Create / Recreate
	if ( $OK )
	{
		// Set Calendar ID
		if ( $_REQUEST['calendar_id'] )
		{
			$calendar_id = $_REQUEST['calendar_id'];
		}
		else
		{
			$calendar_id = DBGet( DBQuery( "SELECT " . db_seq_nextval( 'CALENDARS_SEQ' ) . " AS CALENDAR_ID " ) );

			$calendar_id = $calendar_id[1]['CALENDAR_ID'];
		}

		if ( $_REQUEST['default'] )
		{
			DBQuery( "UPDATE ATTENDANCE_CALENDARS
				SET DEFAULT_CALENDAR=NULL
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );
		}

		// Recreate
		if ( $_REQUEST['calendar_id'] )
		{
			DBQuery( "UPDATE ATTENDANCE_CALENDARS
				SET TITLE='" . $_REQUEST['title'] . "',DEFAULT_CALENDAR='" . $_REQUEST['default'] . "'
				WHERE CALENDAR_ID='" . $calendar_id . "'" );
		}
		// Create
		else
		{
			DBQuery( "INSERT INTO ATTENDANCE_CALENDARS
				(CALENDAR_ID,SYEAR,SCHOOL_ID,TITLE,DEFAULT_CALENDAR)
				values('" . $calendar_id . "','" . UserSyear() . "','" . UserSchool() . "','" . $_REQUEST['title'] . "','" . $_REQUEST['default'] . "')" );
		}

		//FJ fix bug MINUTES not numeric
		$minutes = '999';

		if ( isset( $_REQUEST['minutes'] )
			&& intval( $_REQUEST['minutes'] ) > 0 )
		{
			$minutes = intval( $_REQUEST['minutes'] );
		}

		// Copy Calendar
		if ( $_REQUEST['copy_id'] )
		{
			$weekdays_list = '\'' . implode( '\',\'', array_keys( $_REQUEST['weekdays'] ) ) . '\'';

			if ( $_REQUEST['calendar_id']
				&& $_REQUEST['calendar_id'] === $_REQUEST['copy_id'] )
			{
				$date_min = RequestedDate(
					$_REQUEST['year_min'],
					$_REQUEST['month_min'],
					$_REQUEST['day_min']
				);

				$date_max = RequestedDate(
					$_REQUEST['year_max'],
					$_REQUEST['month_max'],
					$_REQUEST['day_max']
				);

				DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
					WHERE CALENDAR_ID='" . $calendar_id . "'
					AND (SCHOOL_DATE NOT BETWEEN '" . $date_min . "' AND '" . $date_max . "'
						OR extract(DOW FROM SCHOOL_DATE) NOT IN (" . $weekdays_list . "))" );

				if ( $minutes != '999' )
				{
					DBQuery( "UPDATE ATTENDANCE_CALENDAR
						SET MINUTES='" . $minutes . "'
						WHERE CALENDAR_ID='" . $calendar_id . "'" );
				}
			}
			else
			{
				if ( $_REQUEST['calendar_id'] )
				{
					DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
						WHERE CALENDAR_ID='" . $calendar_id . "'" );
				}

				// Insert Days.
				$create_calendar_sql = "INSERT INTO ATTENDANCE_CALENDAR
					(SYEAR,SCHOOL_ID,SCHOOL_DATE,MINUTES,CALENDAR_ID)
					(SELECT '" . UserSyear() . "','" . UserSchool() . "',SCHOOL_DATE," . $minutes . ",'" . $calendar_id . "'
						FROM ATTENDANCE_CALENDAR
						WHERE CALENDAR_ID='" . $_REQUEST['copy_id'] . "'
						AND extract(DOW FROM SCHOOL_DATE) IN (" . $weekdays_list . ")";

				// FJ bugfix SQL bug empty school dates.
				if ( isset( $_REQUEST['day_min'] )
					&& isset( $_REQUEST['month_min'] )
					&& isset( $_REQUEST['year_min'] )
					&& isset( $_REQUEST['day_max'] )
					&& isset( $_REQUEST['month_max'] )
					&& isset( $_REQUEST['year_max'] ) )
				{
					$date_min = RequestedDate(
						$_REQUEST['year_min'],
						$_REQUEST['month_min'],
						$_REQUEST['day_min']
					);

					$date_max = RequestedDate(
						$_REQUEST['year_max'],
						$_REQUEST['month_max'],
						$_REQUEST['day_max']
					);

					if ( ! empty( $date_min )
						&& ! empty( $date_max ) )
					{
						$create_calendar_sql .= " AND SCHOOL_DATE
							BETWEEN '" . $date_min . "'
							AND '" . $date_max . "'";
					}
				}

				$create_calendar_sql .= ")";

				DBQuery( $create_calendar_sql );
			}
		}
		// Create Calendar
		else
		{
			$begin = mktime(0,0,0,$_REQUEST['month_min'],$_REQUEST['day_min']*1,$_REQUEST['year_min']) + 43200;

			$end = mktime(0,0,0,$_REQUEST['month_max'],$_REQUEST['day_max']*1,$_REQUEST['year_max']) + 43200;

			$weekday = date( 'w', $begin );

			if ( $_REQUEST['calendar_id'] )
			{
				DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
					WHERE CALENDAR_ID='" . $calendar_id . "'" );
			}

			// Insert Days.
			for ( $i = $begin; $i <= $end; $i += 86400 )
			{
				if ( $_REQUEST['weekdays'][ $weekday ] == 'Y' )
				{
					DBQuery( "INSERT INTO ATTENDANCE_CALENDAR
						(SYEAR,SCHOOL_ID,SCHOOL_DATE,MINUTES,CALENDAR_ID)
						values('" . UserSyear() . "','" . UserSchool() . "','" . date( 'Y-m-d', $i ) . "'," . $minutes . ",'" . $calendar_id . "')" );
				}

				$weekday++;

				if ( $weekday == 7 )
					$weekday = 0;
			}
		}

		// Set Current Calendar
		$_REQUEST['calendar_id'] = $calendar_id;

		unset( $_REQUEST['modfunc'] );
		unset( $_SESSION['_REQUEST_vars']['modfunc'] );
		unset( $_REQUEST['weekdays']);
		unset( $_SESSION['_REQUEST_vars']['weekdays'] );
		unset( $_REQUEST['title'] );
		unset( $_SESSION['_REQUEST_vars']['title'] );
		unset( $_REQUEST['minutes'] );
		unset( $_SESSION['_REQUEST_vars']['minutes'] );
		unset( $_REQUEST['copy_id'] );
		unset( $_SESSION['_REQUEST_vars']['copy_id'] );
	}
}

// Delete Calendar
if ( $_REQUEST['modfunc'] === 'delete_calendar'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Calendar' ) ) )
	{
		DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
			WHERE CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );

		DBQuery( "DELETE FROM ATTENDANCE_CALENDARS
			WHERE CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );

		$default_RET = DBGet( DBQuery( "SELECT CALENDAR_ID
			FROM ATTENDANCE_CALENDARS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND DEFAULT_CALENDAR='Y'" ) );

		unset( $_REQUEST['calendar_id'] );
		unset( $_REQUEST['modfunc'] );
		unset( $_SESSION['_REQUEST_vars']['modfunc'] );
	}
}

// Set non admin Current Calendar
if ( User( 'PROFILE' ) !== 'admin' )
{
	$course_RET = DBGet( DBQuery( "SELECT CALENDAR_ID
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" ) );

	if ( isset( $course_RET[1]['CALENDAR_ID'] ) )
	{
		$_REQUEST['calendar_id'] = $course_RET[1]['CALENDAR_ID'];
	}
}

// Set Current Calendar
if ( ! isset( $_REQUEST['calendar_id'] )
	|| intval( $_REQUEST['calendar_id'] ) < 1 )
{
	$default_RET = DBGet( DBQuery( "SELECT CALENDAR_ID
		FROM ATTENDANCE_CALENDARS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND DEFAULT_CALENDAR='Y'" ) );

	if ( $default_RET )
	{
		$_REQUEST['calendar_id'] = $default_RET[1]['CALENDAR_ID'];
	}
	else
	{
		$calendars_RET = DBGet( DBQuery( "SELECT CALENDAR_ID
			FROM ATTENDANCE_CALENDARS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ) );

		if ( $calendars_RET )
		{
			$_REQUEST['calendar_id'] = $calendars_RET[1]['CALENDAR_ID'];
		}
		else
			$error[] = _( 'There are no calendars setup yet.' );
	}
}

unset( $_SESSION['_REQUEST_vars']['calendar_id'] );

// Event / Assignment details
if ( $_REQUEST['modfunc'] === 'detail' )
{
	if ( isset( $_REQUEST['month_values']['SCHOOL_DATE'] )
		&& isset( $_REQUEST['day_values']['SCHOOL_DATE'] )
		&& isset( $_REQUEST['year_values']['SCHOOL_DATE'] ) )
	{
		$_REQUEST['values']['SCHOOL_DATE'] = RequestedDate(
			$_REQUEST['year_values']['SCHOOL_DATE'],
			$_REQUEST['month_values']['SCHOOL_DATE'],
			$_REQUEST['day_values']['SCHOOL_DATE']
		);
	}

	if ( $_POST['button'] === _( 'Save' )
		&& AllowEdit() )
	{
		if ( $_REQUEST['values'] )
		{
			// FJ textarea fields MarkDown sanitize.
			if ( $_REQUEST['values']['DESCRIPTION'] )
			{
				$_REQUEST['values']['DESCRIPTION'] = SanitizeMarkDown( $_POST['values']['DESCRIPTION'] );
			}

			// Update Event.
			if ( $_REQUEST['event_id'] !== 'new' )
			{
				$sql = "UPDATE CALENDAR_EVENTS SET ";

				foreach ( (array) $_REQUEST['values'] as $column => $value )
				{
					$sql .= $column . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $_REQUEST['event_id'] . "'";

				DBQuery( $sql );

				// Hook.
				do_action('School_Setup/Calendar.php|update_calendar_event');
			}
			// Create Event.
			else
			{
				// FJ add event repeat.
				$i = 0;

				do {
					if ( $i > 0 ) // School date + 1 day.
					{
						$_REQUEST['values']['SCHOOL_DATE'] = date(
							'd-M-Y',
							mktime( 0, 0, 0,
								$_REQUEST['month_values']['SCHOOL_DATE'],
 								$_REQUEST['day_values']['SCHOOL_DATE'] + $i,
								$_REQUEST['year_values']['SCHOOL_DATE']
							)
 						);
					}

					$sql = "INSERT INTO CALENDAR_EVENTS ";

					$fields = 'ID,SYEAR,SCHOOL_ID,';

					$calendar_event_RET = DBGet( DBQuery( "SELECT " . db_seq_nextval( 'CALENDAR_EVENTS_SEQ' ) . ' AS CALENDAR_EVENT_ID ' ) );

					$calendar_event_id = $calendar_event_RET[1]['CALENDAR_EVENT_ID'];

					$values = $calendar_event_id . ",'" . UserSyear() . "','" . UserSchool() . "',";

					$go = false;

					foreach ( (array) $_REQUEST['values'] as $column => $value )
					{
						if ( ! empty( $value )
							|| $value == '0' )
						{
							$fields .= $column . ',';
							$values .= "'" . $value . "',";
							$go = true;
						}
					}

					$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

					if ( $go )
					{
						DBQuery( $sql );

						// Hook.
						do_action( 'School_Setup/Calendar.php|create_calendar_event' );
					}

					$i++;

				} while( is_numeric( $_REQUEST['REPEAT'] )
					&& $i <= $_REQUEST['REPEAT'] );
			}

			// Reload Calendar & close popup
			$opener_URL = "Modules.php?modname=" . $_REQUEST['modname'] . "&year=" . $_REQUEST['year'] . "&month=" . $_REQUEST['month'];
			?>
<script>
	window.opener.ajaxLink(<?php echo json_encode( $opener_URL ); ?>);
	window.close();
</script>
			<?php

			unset( $_REQUEST['values'] );
			unset( $_SESSION['_REQUEST_vars']['values'] );
		}
	}
	// Delete Event
	elseif ( $_REQUEST['button'] == _( 'Delete' ) )
	{
		if ( DeletePrompt( _( 'Event' ) ) )
		{
			DBQuery( "DELETE FROM CALENDAR_EVENTS
				WHERE ID='" . $_REQUEST['event_id'] . "'" );

			//hook
			do_action( 'School_Setup/Calendar.php|delete_calendar_event' );

			// Reload Calendar & close popup
			$opener_URL = "Modules.php?modname=" . $_REQUEST['modname'] . "&year=" . $_REQUEST['year'] . "&month=" . $_REQUEST['month'];
			?>
<script>
	window.opener.ajaxLink(<?php echo json_encode( $opener_URL ); ?>);
	window.close();
</script>
			<?php

			unset( $_REQUEST['values'] );
			unset( $_SESSION['_REQUEST_vars']['values'] );
			unset( $_REQUEST['button'] );
			unset( $_SESSION['_REQUEST_vars']['button'] );
		}
	}
	// Display Event / Assignment
	else
	{
		// Event
		if ( $_REQUEST['event_id'] )
		{
			if ( $_REQUEST['event_id'] !== 'new' )
			{
				$RET = DBGet( DBQuery( "SELECT TITLE,DESCRIPTION,SCHOOL_DATE
					FROM CALENDAR_EVENTS
					WHERE ID='" . $_REQUEST['event_id'] . "'"), array() );

				$title = $RET[1]['TITLE'];
			}
			else
			{
				//FJ add translation
				$title = _( 'New Event' );

				$RET[1]['SCHOOL_DATE'] = $_REQUEST['school_date'];
			}

			echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=detail&event_id=' . $_REQUEST['event_id'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] . '" method="POST">';
		}
		// Assignment
		elseif ( $_REQUEST['assignment_id'] )
		{
			//FJ add assigned date
			$RET = DBGet( DBQuery( "SELECT a.TITLE,a.STAFF_ID,a.DUE_DATE AS SCHOOL_DATE,
				a.DESCRIPTION,a.ASSIGNED_DATE,c.TITLE AS COURSE,a.SUBMISSION
				FROM GRADEBOOK_ASSIGNMENTS a,COURSES c
				WHERE (a.COURSE_ID=c.COURSE_ID
					OR c.COURSE_ID=(SELECT cp.COURSE_ID
						FROM COURSE_PERIODS cp
						WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
				AND a.ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'") );

			$title = $RET[1]['TITLE'];

			$RET[1]['STAFF_ID'] = GetTeacher( $RET[1]['STAFF_ID'] );
		}

		echo '<br />';

		PopTable( 'header', $title );

		echo '<table class="cellpadding-5"><tr><td>' .
			DateInput( $RET[1]['SCHOOL_DATE'], 'values[SCHOOL_DATE]', _( 'Date' ), false ) .
		'</td></tr>';

		// Add assigned date.
		if ( $RET[1]['ASSIGNED_DATE'] )
		{
			echo '<tr><td>' .
				DateInput( $RET[1]['ASSIGNED_DATE'], 'values[ASSIGNED_DATE]', _( 'Assigned Date' ), false ) .
			'</td></tr>';
		}

		// Add submit Assignment link.
		if ( $RET[1]['SUBMISSION']
			&& AllowUse( 'Grades/StudentAssignments.php' ) )
		{
			echo '<tr><td>
				<a href="Modules.php?modname=Grades/StudentAssignments.php&assignment_id=' .
					$_REQUEST['assignment_id'] . '" onclick="window.opener.ajaxLink(this.href); window.close();">' .
				_( 'Submit Assignment' ) .
			'</a></td></tr>';
		}

		//FJ add event repeat
		if ( $_REQUEST['event_id'] === 'new' )
		{
			echo '<tr><td>
				<input name="REPEAT" id="REPEAT" value="0" maxlength="3" size="1" type="number" min="0" />&nbsp;' . _( 'Days' ) .
				FormatInputTitle( _( 'Event Repeat' ), 'REPEAT' ) .
			'</td></tr>';
		}

		//hook
		do_action( 'School_Setup/Calendar.php|event_field' );


		// FJ bugfix SQL bug value too long for type character varying(50).
		echo '<tr><td>' .
			TextInput( $RET[1]['TITLE'], 'values[TITLE]', _( 'Title' ), 'required maxlength="50"' ) .
		'</td></tr>';

		// FJ add course.
		if ( $RET[1]['COURSE'] )
		{
			echo '<tr><td>' .
				NoInput( $RET[1]['COURSE'], _( 'Course' ) ) .
			'</td></tr>';
		}

		if ( $RET[1]['STAFF_ID'] )
		{
			echo '<tr><td>' .
				TextInput( $RET[1]['STAFF_ID'], 'values[STAFF_ID]', _( 'Teacher' ) ) .
			'</td></tr>';
		}

		echo '<tr><td>' .
			TextAreaInput( $RET[1]['DESCRIPTION'], 'values[DESCRIPTION]', _( 'Notes' ) ) .
		'</td></tr>';

		if ( AllowEdit() )
		{
			echo '<tr><td colspan="2">' . SubmitButton( _( 'Save' ), 'button' );

			if ( $_REQUEST['event_id'] !== 'new' )
			{
				echo SubmitButton( _( 'Delete' ), 'button' );
			}

			echo '</td></tr>';
		}

		echo '</table>';

		PopTable( 'footer' );

		if ( $_REQUEST['event_id'] )
			echo '</form>';

		unset( $_REQUEST['values'] );
		unset( $_SESSION['_REQUEST_vars']['values'] );
		unset( $_REQUEST['button'] );
		unset( $_SESSION['_REQUEST_vars']['button'] );
	}
}

// List Events
if ( $_REQUEST['modfunc'] === 'list_events' )
{
	if ( $_REQUEST['day_start']
		&& $_REQUEST['month_start']
		&& $_REQUEST['year_start'] )
	{
		$start_date = RequestedDate(
			$_REQUEST['year_start'],
			$_REQUEST['month_start'],
			$_REQUEST['day_start']
		);
	}
	else
	{
		$min_date = DBGet( DBQuery( "SELECT min(SCHOOL_DATE) AS MIN_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ) );

		if ( isset( $min_date[1]['MIN_DATE'] ) )
		{
			$start_date = $min_date[1]['MIN_DATE'];
		}
		else
			$start_date = date( 'Y-m' ) . '-01';
	}

	if ( $_REQUEST['day_end']
		&& $_REQUEST['month_end']
		&& $_REQUEST['year_end'] )
	{
		$end_date = RequestedDate(
			$_REQUEST['year_end'],
			$_REQUEST['month_end'],
			$_REQUEST['day_end']
		);
	}
	else
	{
		$max_date = DBGet( DBQuery( "SELECT max(SCHOOL_DATE) AS MAX_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ) );

		if ( isset( $max_date[1]['MAX_DATE'] ) )
		{
			$end_date = $max_date[1]['MAX_DATE'];
		}
		else
			$end_date = DBDate();
	}

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] . '" method="POST">';

	DrawHeader( '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] . '" >' . _( 'Back to Calendar' ) . '</a>' );

	DrawHeader(
		_( 'Timeframe' ) . ': ' .
		PrepareDate( $start_date, '_start' ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end' ) . ' ' .
		Buttons( _( 'Go' ) )
	);


	$functions = array( 'SCHOOL_DATE' => 'ProperDate', 'DESCRIPTION' => 'makeTextarea' );

	$events_RET = DBGet( DBQuery( "SELECT ID,SCHOOL_DATE,TITLE,DESCRIPTION
		FROM CALENDAR_EVENTS
		WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'"), $functions );

	$column_names = array(
		'SCHOOL_DATE' => _( 'Date' ),
		'TITLE' => _('Event'),
		'DESCRIPTION' => _( 'Description' )
	);

	ListOutput( $events_RET, $column_names, 'Event', 'Events');

	echo '</form>';
}

// Display Calendar View
if ( empty( $_REQUEST['modfunc'] ) )
{

	echo ErrorMessage( $error );

	$last = 31;

	while( !checkdate( $_REQUEST['month'], $last, $_REQUEST['year'] ) )
		$last--;

	$first_day_month = date( 'Y-m-d', $time );

	$last_day_month = date(
		'Y-m-d',
		mktime( 0, 0, 0, $_REQUEST['month'], $last, $_REQUEST['year'] )
	);

	$calendar_SQL = "SELECT SCHOOL_DATE,MINUTES,BLOCK
		FROM ATTENDANCE_CALENDAR
		WHERE SCHOOL_DATE BETWEEN '" . $first_day_month . "'
		AND '" . $last_day_month . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'";

	$calendar_RET = DBGet( DBQuery( $calendar_SQL ), array(), array( 'SCHOOL_DATE' ) );

	$update_calendar = false;

	// Update School Day minutes
	if ( isset( $_REQUEST['minutes'] ) )
	{
		foreach ( (array) $_REQUEST['minutes'] as $date => $minutes )
		{
			if ( $calendar_RET[ $date ] )
			{
				//if ( $minutes!='0' && $minutes!='')
				//FJ fix bug MINUTES not numeric
				if ( intval( $minutes ) > 0 )
				{
					DBQuery( "UPDATE ATTENDANCE_CALENDAR
						SET MINUTES='" . intval( $minutes ) . "'
						WHERE SCHOOL_DATE='" . $date . "'
						AND SYEAR='" . UserSyear() . "'
						AND SCHOOL_ID='" . UserSchool() . "'
						AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );
				}
				else
				{
					DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
						WHERE SCHOOL_DATE='" . $date . "'
						AND SYEAR='" . UserSyear() . "'
						AND SCHOOL_ID='" . UserSchool() . "'
						AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );
				}

				$update_calendar = true;
			}
			//elseif ( $minutes!='0' && $minutes!='')
			//FJ fix bug MINUTES not numeric
			elseif ( intval( $minutes ) > 0 )
			{
				DBQuery( "INSERT INTO ATTENDANCE_CALENDAR
					(SYEAR,SCHOOL_ID,SCHOOL_DATE,CALENDAR_ID,MINUTES)
					values('" . UserSyear() . "','" . UserSchool() . "','" . $date . "','" . $_REQUEST['calendar_id'] . "','" . intval( $minutes ) . "')" );

				$update_calendar = true;
			}
		}

		unset( $_REQUEST['minutes'] );
		unset( $_SESSION['_REQUEST_vars']['minutes'] );
	}

	// Update All day school
	if ( isset( $_REQUEST['all_day'] ) )
	{
		foreach ( (array) $_REQUEST['all_day'] as $date => $yes )
		{
			if ( $yes === 'Y' )
			{
				if ( $calendar_RET[ $date ] )
				{
					DBQuery( "UPDATE ATTENDANCE_CALENDAR
						SET MINUTES='999'
						WHERE SCHOOL_DATE='" . $date . "'
						AND SYEAR='" . UserSyear() . "'
						AND SCHOOL_ID='" . UserSchool() . "'
						AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'
						AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );
				}
				else
				{
					DBQuery( "INSERT INTO ATTENDANCE_CALENDAR
						(SYEAR,SCHOOL_ID,SCHOOL_DATE,CALENDAR_ID,MINUTES)
						values('" . UserSyear() . "','" . UserSchool()."','" . $date . "','" . $_REQUEST['calendar_id'] . "','999')" );
				}
			}
			else
			{
				DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
					WHERE SCHOOL_DATE='" . $date . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );
			}

			$update_calendar = true;
		}

		unset( $_REQUEST['all_day'] );
		unset( $_SESSION['_REQUEST_vars']['all_day'] );
	}

	// Update Blocks
	if ( isset( $_REQUEST['blocks'] ) )
	{
		foreach ( (array) $_REQUEST['blocks'] as $date => $block )
		{
			if ( $calendar_RET[ $date ] )
			{
				DBQuery( "UPDATE ATTENDANCE_CALENDAR
					SET BLOCK='" . $block . "'
					WHERE SCHOOL_DATE='" . $date . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );

				$update_calendar = true;
			}
		}

		unset( $_REQUEST['blocks'] );
		unset( $_SESSION['_REQUEST_vars']['blocks'] );
	}

	// Update Calendar RET
	if ( $update_calendar )
	{
		$calendar_RET = DBGet( DBQuery( $calendar_SQL ), array(), array( 'SCHOOL_DATE' ) );
	}


	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

	// Admin Headers
	if ( AllowEdit() )
	{
		$title_RET = DBGet( DBQuery( "SELECT CALENDAR_ID,TITLE,DEFAULT_CALENDAR
			FROM ATTENDANCE_CALENDARS WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY DEFAULT_CALENDAR ASC,TITLE" ) );

		foreach ( (array) $title_RET as $title )
		{
			$options[$title['CALENDAR_ID']] = $title['TITLE'] . ( $title['DEFAULT_CALENDAR']=='Y' ? ' (' . _( 'Default' ) . ')' : '' );

			if ( $title['DEFAULT_CALENDAR'] === 'Y' )
				$defaults++;
		}

		//FJ bugfix erase calendar onchange
		$calendar_onchange_URL = "'Modules.php?modname=" . $_REQUEST['modname'] . "&calendar_id='";

		$links = SelectInput(
			$_REQUEST['calendar_id'],
			'calendar_id',
			'',
			$options,
			false,
			' onchange="ajaxLink(' . $calendar_onchange_URL . ' + document.getElementById(\'calendar_id\').value);" ',
			false
		) .
		'<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=create" class="nobr">' .
			button( 'add' ) . _( 'Create new calendar' ) .
		'</a> | ' .
		'<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=create&calendar_id=' . $_REQUEST['calendar_id'] . '" class="nobr">' .
			_( 'Recreate this calendar' ) .
		'</a>&nbsp; ' .
		'<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete_calendar&calendar_id=' . $_REQUEST['calendar_id'] . '" class="nobr">' .
			button( 'remove' ) . _( 'Delete this calendar' ) .
		'</a>';
	}

	$list_events_URL = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=list_events&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'];

	DrawHeader(
		PrepareDate( mb_strtoupper( $first_day_month ), '', false, array( 'M' => 1, 'Y' => 1, 'submit' => true ) ) .
		' <a href="' . $list_events_URL . '">' .
			_( 'List Events' ) .
		'</a>',
		SubmitButton( _( 'Save' ) )
	);

	if ( $links )
		DrawHeader( $links );

	if ( AllowEdit()
		&& $defaults != 1 )
	{
		echo ErrorMessage(
			array( $defaults ?
				_( 'This school has more than one default calendar!' ) :
				_( 'This school does not have a default calendar!' )
			)
		);
	}

	echo '<br />';

	// Get Events
	$events_RET = DBGet( DBQuery( "SELECT ID,SCHOOL_DATE,TITLE,DESCRIPTION
		FROM CALENDAR_EVENTS
		WHERE SCHOOL_DATE BETWEEN '" . $first_day_month . "'
		AND '" . $last_day_month . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" ), array(), array( 'SCHOOL_DATE' ) );

	// Get Assignments
	$assignments_RET = null;

	if ( User( 'PROFILE' ) === 'parent'
		|| User( 'PROFILE' ) === 'student' )
	{

		$assignments_SQL = "SELECT ASSIGNMENT_ID AS ID,a.DUE_DATE AS SCHOOL_DATE,a.TITLE,'Y' AS ASSIGNED
			FROM GRADEBOOK_ASSIGNMENTS a,SCHEDULE s
			WHERE (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
			AND s.STUDENT_ID='" . UserStudentID() . "'
			AND (a.DUE_DATE BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
			AND a.DUE_DATE BETWEEN '" . $first_day_month . "' AND '" . $last_day_month . "'";
	}
	elseif ( User( 'PROFILE' ) === 'teacher' )
	{
		$assignments_SQL = "SELECT ASSIGNMENT_ID AS ID,a.DUE_DATE AS SCHOOL_DATE,a.TITLE,CASE WHEN a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL THEN 'Y' ELSE NULL END AS ASSIGNED
			FROM GRADEBOOK_ASSIGNMENTS a
			WHERE a.STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND a.DUE_DATE BETWEEN '".$first_day_month."' AND '" . $last_day_month . "'";

	}

	if ( isset( $assignments_SQL ) )
		$assignments_RET = DBGet( DBQuery( $assignments_SQL ), array(), array( 'SCHOOL_DATE' ) );

	// Calendar Events onclick popup
	$popup_URL = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=detail&year=' . $_REQUEST['year'] . '&month=' . $_REQUEST['month'];
?>
<script>
	var popupURL = <?php echo json_encode( $popup_URL ); ?>;

	function CalEventPopup(url) {
		popups.open( url, "scrollbars=yes,resizable=yes,width=500,height=400" );
	}
</script>
<?php

	if ( $_REQUEST['_ROSARIO_PDF'] )
	{
		// Landscape PDF.
		$_SESSION['orientation'] = 'landscape';
	}

	// Calendar Header
	echo '<table id="calendar" class="width-100p valign-top">
		<thead><tr class="center">';

	echo '<th>' . _( 'Sunday' ) . '</th>' .
		'<th>' . _( 'Monday' ) . '</th>' .
		'<th>' . _( 'Tuesday' ) . '</th>' .
		'<th>' . _( 'Wednesday' ) . '</th>' .
		'<th>' . _( 'Thursday' ) . '</th>' .
		'<th>' . _( 'Friday' ) . '</th>' .
		'<th>' . _( 'Saturday' ) . '</th>';

	echo '</tr></thead><tbody><tr>';

	// Get Blocks
	$blocks_RET = DBGet( DBQuery( "SELECT DISTINCT BLOCK
		FROM SCHOOL_PERIODS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND BLOCK IS NOT NULL
		ORDER BY BLOCK" ) );

	$block_options = array();

	foreach ( (array) $blocks_RET as $block )
	{
		$block_options[ $block['BLOCK'] ] = $block['BLOCK'];
	}

	// Skip until first Day of Month
	$skip = date( "w", $time );

	if ( $skip )
	{
		echo '<td colspan="' . $skip . '" class="calendar-skip">&nbsp;</td>';

		$return_counter = $skip;
	}

	// Days
	for ( $i = 1; $i <= $last; $i++ )
	{
		$day_time = mktime( 0, 0, 0, $_REQUEST['month'], $i, $_REQUEST['year'] );

		$date = date( 'Y-m-d', $day_time );

		$day_classes = '';

		if ( $calendar_RET[ $date ][1]['MINUTES'] )
		{
			// Full School Day.
			if ( $calendar_RET[ $date ][1]['MINUTES'] === '999' )
			{
				$day_classes .= ' full';
			}
			// Minutes School Day.
			else
				$day_classes .= ' minutes';
		}
		// No School Day.
		else
			$day_classes .= ' no-school';

		// Thursdays, Fridays, Saturdays.
		if ( ($return_counter + 1) % 7 === 0
			|| ($return_counter + 1) % 7 > 4 )
		{
			$day_classes .= ' thu-fri-sat';
		}

		$day_inner_classes = '';

		// Hover CSS class.
		if ( AllowEdit()
			|| $calendar_RET[ $date ][1]['MINUTES']
			|| count( $events_RET[ $date ] )
			|| count( $assignments_RET[ $date ] ) )
		{
			$day_inner_classes .= ' hover';
		}

		echo '<td class="calendar-day' . $day_classes . '">
			<table class="' . $day_inner_classes . '"><tr>';

		$day_number_classes = 'number';

		// Bold class
		if ( count( $events_RET[ $date ] )
			|| count( $assignments_RET[ $date ] ) )
		{
			$day_number_classes .= ' bold';
		}

		// Calendar Day number
		echo '<td class="' . $day_number_classes . '">' . $i . '</td>
		<td class="width-100p align-right">';

		if ( AllowEdit() )
		{
			// Minutes
			if ( $calendar_RET[ $date ][1]['MINUTES'] === '999' )
			{
				//FJ icons
				echo CheckboxInput(
					$calendar_RET[ $date ],
					"all_day[" . $date . "]",
					'',
					'',
					false,
					button( 'check' ),
					'',
					true,
					'title="' . _( 'All Day' ) . '"'
				);
			}
			elseif ( $calendar_RET[ $date ][1]['MINUTES'] )
			{
				echo TextInput( $calendar_RET[ $date ][1]['MINUTES'], "minutes[" . $date . "]", '', 'size=3' );
			}
			else
			{
				echo '<input type="checkbox" name="all_day[' . $date . ']" value="Y" title="' . _( 'All Day' ) . '" />&nbsp;';

				//FJ fix bug MINUTES not numeric
				echo '<input type="number" min="1" max="998" name="minutes[' . $date . ']" size="3" title="' . _( 'Minutes' ) . '" />';
			}
		}

		// Blocks
		if ( count( $blocks_RET )
			&& ( $calendar_RET[ $date ][1]['BLOCK']
				|| User( 'PROFILE' ) === 'admin' ) )
		{
			echo SelectInput(
				$calendar_RET[ $date ][1]['BLOCK'],
				"blocks[" . $date . "]",
				'',
				$block_options
			);
		}

		echo '</td></tr>
		<tr><td colspan="2" class="calendar-event valign-top">';

		// Events.
		foreach ( (array) $events_RET[ $date ] as $event )
		{
			$title = ( $event['TITLE'] ? $event['TITLE'] : '***' );

			echo '<div>' .
				( AllowEdit() || $event['DESCRIPTION'] ?
					'<a href="#" onclick="CalEventPopup(popupURL + \'&event_id=' . $event['ID'] . '\'); return false;" title="' . htmlentities( $title ) . '">' .
					$title . '</a>'
					: '<span title="' . htmlentities( $title ) . '">' . $title . '</span>'
				) .
			'</div>';
		}

		// Assignments.
		foreach ( (array) $assignments_RET[ $date ] as $assignment )
		{
			echo '<div class="calendar-event assignment' . ( $assignment['ASSIGNED'] == 'Y' ? ' assigned' : '' ) . '">' .
				'<a href="#" onclick="CalEventPopup(popupURL + \'&assignment_id=' . $assignment['ID'] . '\'); return false;" title="' . htmlentities( $assignment['TITLE'] ) . '">' .
					$assignment['TITLE'] .
				'</a>
			</div>';
		}

		echo '</td></tr>';

		if ( AllowEdit() )
		{
			// New Event
			echo '<td style="vertical-align:bottom;">' .
				button(
					'add',
					'',
					'"#" onclick="CalEventPopup(popupURL + \'&school_date=' . $date . '&event_id=new\'); return false;" title="' . htmlentities( _( 'New Event' ) ) . '"'
				) .
			'</td>';
		}

		//FJ Days Numbered
		if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) > 0 )
		{
			echo '<td class="align-right" style="vertical-align:bottom;">' .
				( ( $dayNumber = dayToNumber( $day_time ) ) ? _( 'Day' ) . '&nbsp;' . $dayNumber : '&nbsp;' ) .
			'</td>';
		}

		echo '</table></td>';

		$return_counter++;

		if ( $return_counter % 7 === 0 )
			echo '</tr><tr>';
	}

	// Skip from Last Day of Month until end of Calendar
	if ( $return_counter %7 !== 0 )
	{
		$skip = 7 - $return_counter % 7;

		echo '<td colspan="' . $skip . '" class="calendar-skip">&nbsp;</td>';
	}

	echo '</tr></tbody></table>';

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
	echo '<br /><br /></form>';
}
