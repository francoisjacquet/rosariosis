<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'modules/School_Setup/includes/CalendarDay.inc.php';

DrawHeader( ProgramTitle() );

// Set Month.
if ( ! isset( $_REQUEST['month'] )
	|| mb_strlen( $_REQUEST['month'] ) !== 2
	|| (string) (int) $_REQUEST['month'] != $_REQUEST['month'] )
{
	$_REQUEST['month'] = date( 'm' );
}

// Set Year.
if ( ! isset( $_REQUEST['year'] )
	|| mb_strlen( $_REQUEST['year'] ) !== 4
	|| (string) (int) $_REQUEST['year'] != $_REQUEST['year'] )
{
	$_REQUEST['year'] = date( 'Y' );
}

if ( isset( $_REQUEST['calendar_id'] ) )
{
	$_REQUEST['calendar_id'] = (string) (int) $_REQUEST['calendar_id'];
}
else
{
	$_REQUEST['calendar_id'] = '';
}

// Create / Recreate Calendar.
if ( $_REQUEST['modfunc'] === 'create'
	&& AllowEdit() )
{
	$fy_RET = DBGet( "SELECT START_DATE,END_DATE
		FROM school_marking_periods
		WHERE MP='FY'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$fy = $fy_RET[1];

	// Get Calendars Info.
	$title_RET = DBGet( "SELECT ac.CALENDAR_ID,ac.TITLE,ac.DEFAULT_CALENDAR,ac.SCHOOL_ID,
		(SELECT coalesce(SHORT_NAME,TITLE)
			FROM schools
			WHERE SYEAR=ac.SYEAR
			AND ID=ac.SCHOOL_ID) AS SCHOOL_TITLE,
		(SELECT min(SCHOOL_DATE)
			FROM attendance_calendar
			WHERE CALENDAR_ID=ac.CALENDAR_ID) AS START_DATE,
		(SELECT max(SCHOOL_DATE)
			FROM attendance_calendar
			WHERE CALENDAR_ID=ac.CALENDAR_ID) AS END_DATE
		FROM attendance_calendars ac,staff s
		WHERE ac.SYEAR='" . UserSyear() . "'
		AND s.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (s.SCHOOLS IS NULL OR position(CONCAT(',', ac.SCHOOL_ID, ',') IN s.SCHOOLS)>0)
		ORDER BY " . db_case( [ 'ac.SCHOOL_ID', "'" . UserSchool() . "'", 0, 'ac.SCHOOL_ID' ] ) . ",ac.DEFAULT_CALENDAR IS NULL,ac.DEFAULT_CALENDAR ASC,ac.TITLE" );

	// Prepare table for Copy Calendar & add ' (Default)' mention.
	$copy_calendar_options = [];

	$recreate_calendar = false;

	foreach ( (array) $title_RET as $title )
	{
		$copy_calendar_options[ $title['CALENDAR_ID'] ] = $title['TITLE'];

		if ( AllowEdit()
			&& $title['DEFAULT_CALENDAR'] === 'Y'
			&& $title['SCHOOL_ID'] === UserSchool() )
		{
			$copy_calendar_options[ $title['CALENDAR_ID'] ] .= ' (' . _( 'Default' ) . ')';
		}

		if ( AllowEdit()
			&& isset( $_REQUEST['calendar_id'] )
			&& $title['CALENDAR_ID'] == $_REQUEST['calendar_id'] )
		{
			$recreate_calendar = $title;
		}
	}

	$div = false;

	$message = '<table class="valign-top fixed-col width-100p"><tr class="st">';

	// Title.
	$message .= '<td>' . TextInput(
		( $recreate_calendar ? $recreate_calendar['TITLE'] : '' ),
		'title',
		_( 'Title' ),
		'required maxlength="100"',
		$div
	) . '</td>';

	// Copy calendar.
	$message .= '<td>' . SelectInput(
		'',
		'copy_id',
		_( 'Copy Calendar' ),
		$copy_calendar_options,
		'N/A',
		'',
		$div
	) . '</td></tr>';

	// Check default if recreate default calendar.
	$default_checked = $recreate_calendar && $recreate_calendar['DEFAULT_CALENDAR'] == 'Y';

	if ( ! $default_checked )
	{
		// @since 9.0 Check default if school has no default calendar.
		$default_checked = ! DBGetOne( "SELECT 1
			FROM attendance_calendars
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND DEFAULT_CALENDAR='Y'" );
	}

	// Default.
	$message .= '<tr><td colspan="2">' . CheckboxInput(
		$default_checked,
		'default',
		_( 'Default Calendar for this School' ),
		'',
		true
	) . '</td></tr>';

	$message .= '<tr><td colspan="2"><hr></td></tr>';

	// From date.
	$message .= '<tr class="st"><td>' . DateInput(
		$recreate_calendar && $recreate_calendar['START_DATE'] ?
			$recreate_calendar['START_DATE'] :
			$fy['START_DATE'],
		'min',
		_( 'From' ),
		$div,
		true,
		!( $recreate_calendar && $recreate_calendar['START_DATE'] )
	) . '</td>';

	// To date.
	$message .= '<td>' . DateInput(
		$recreate_calendar && $recreate_calendar['END_DATE'] ?
			$recreate_calendar['END_DATE'] :
			$fy['END_DATE'],
		'max',
		_( 'To' ),
		$div,
		true,
		!( $recreate_calendar && $recreate_calendar['END_DATE'] )
	) . '</td></tr>';

	$message .= '<tr class="st"><td colspan="2"><table class="valign-top cellpadding-5"><tr class="st"><td>';

	// Weekdays.
	$weekdays = [
		_( 'Sunday' ),
		_( 'Monday' ),
		_( 'Tuesday' ),
		_( 'Wednesday' ),
		_( 'Thursday' ),
		_( 'Friday' ),
		_( 'Saturday' ),
	];

	$weekdays_inputs = [];

	foreach ( (array) $weekdays as $id => $weekday )
	{
		$value = 'Y';

		// Unckeck Saturday & Sunday.
		if ( $id === 0
			|| $id === 6 )
		{
			$value = '';
		}

		$weekdays_inputs[] .= CheckboxInput(
			$value,
			'weekdays[' . $id . ']',
			$weekday,
			'',
			true
		);
	}

	$message .= implode( '</td><td>', $weekdays_inputs );

	$message .= '</td></tr></table></td></tr>';

	// Minutes.
	$minutes_tip_text = ( $recreate_calendar ?
		_( 'Default is Full Day if Copy Calendar is N/A.' ) . ' ' . _( 'Otherwise Default is minutes from the Copy Calendar' ) :
		_( 'Default is Full Day' )
	);

	$message .= '<tr class="st valign-top"><td colspan="2">' . TextInput(
		'',
		'minutes',
		_( 'Minutes' ) .
			'<div class="tooltip"><i>' . $minutes_tip_text . '</i></div>',
		' type="number" min="1" max="998"',
		$div
	) . '</td></tr></table>';

	$OK = Prompt(
		! empty( $_REQUEST['calendar_id'] ) ?
		sprintf( _( 'Recreate %s calendar' ), $recreate_calendar['TITLE'] ) :
		_( 'Create new calendar' ),
		'',
		$message
	);

	// If Confirm Create / Recreate
	if ( $OK )
	{
		if ( ! empty( $_REQUEST['default'] ) )
		{
			DBUpdate(
				'attendance_calendars',
				[ 'DEFAULT_CALENDAR' => '' ],
				[
					'SYEAR' => UserSyear(),
					'SCHOOL_ID' => UserSchool(),
				]
			);
		}

		// Recreate
		if ( ! empty( $_REQUEST['calendar_id'] ) )
		{
			$calendar_id = $_REQUEST['calendar_id'];

			DBUpdate(
				'attendance_calendars',
				[
					'TITLE' => $_REQUEST['title'],
					'DEFAULT_CALENDAR' => $_REQUEST['default'],
				],
				[ 'CALENDAR_ID' => (int) $calendar_id ]
			);
		}
		// Create
		else
		{
			// Set Calendar ID
			$calendar_id = DBInsert(
				'attendance_calendars',
				[
					'SYEAR' => UserSyear(),
					'SCHOOL_ID' => UserSchool(),
					'TITLE' => $_REQUEST['title'],
					'DEFAULT_CALENDAR' => $_REQUEST['default'],
				],
				'id'
			);
		}

		//FJ fix bug MINUTES not numeric
		$minutes = '999';

		if ( isset( $_REQUEST['minutes'] )
			&& intval( $_REQUEST['minutes'] ) > 0 )
		{
			$minutes = intval( $_REQUEST['minutes'] );
		}

		// Copy Calendar
		if ( ! empty( $_REQUEST['copy_id'] ) )
		{
			$weekdays_list = [];

			// FJ remove empty weekdays.
			foreach ( (array) $_REQUEST['weekdays'] as $weekday_id => $yes )
			{
				if ( $yes )
				{
					$weekdays_list[] = $weekday_id;
				}
			}

			$weekdays_list = $weekdays_list ?
				"'" . implode( "','", $weekdays_list ) . "'" : '';

			$date_min = RequestedDate( 'min', '' );

			$date_max = RequestedDate( 'max', '' );

			if ( $_REQUEST['calendar_id']
				&& $_REQUEST['calendar_id'] === $_REQUEST['copy_id'] )
			{
				// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
				DBQuery( "DELETE FROM attendance_calendar
					WHERE CALENDAR_ID='" . (int) $calendar_id . "'
					AND (SCHOOL_DATE NOT BETWEEN '" . $date_min . "' AND '" . $date_max . "'" .
					( $weekdays_list ?
						" OR " . ( $DatabaseType === 'mysql' ?
							"DAYOFWEEK(SCHOOL_DATE)-1" :
							"cast(extract(DOW FROM SCHOOL_DATE) AS int)" ) .
						" NOT IN (" . $weekdays_list . ")" : '' ) .
					")" );

				if ( $minutes != '999' )
				{
					DBQuery( "UPDATE attendance_calendar
						SET MINUTES='" . $minutes . "'
						WHERE CALENDAR_ID='" . (int) $calendar_id . "'" );
				}
			}
			else
			{
				if ( ! empty( $_REQUEST['calendar_id'] ) )
				{
					DBQuery( "DELETE FROM attendance_calendar
						WHERE CALENDAR_ID='" . (int) $calendar_id . "'" );
				}

				// Insert Days.
				// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
				$create_calendar_sql = "INSERT INTO attendance_calendar
					(SYEAR,SCHOOL_ID,SCHOOL_DATE,MINUTES,CALENDAR_ID)
					(SELECT '" . UserSyear() . "','" . UserSchool() . "',SCHOOL_DATE," . $minutes . ",'" . $calendar_id . "'
						FROM attendance_calendar
						WHERE CALENDAR_ID='" . (int) $_REQUEST['copy_id'] . "'" .
						( $weekdays_list ?
							" AND " . ( $DatabaseType === 'mysql' ?
								"DAYOFWEEK(SCHOOL_DATE)-1" :
								"cast(extract(DOW FROM SCHOOL_DATE) AS int)" ) .
							" IN (" . $weekdays_list . ")" : '' );

				if ( $date_min && $date_max )
				{
					$create_calendar_sql .= " AND SCHOOL_DATE
						BETWEEN '" . $date_min . "'
						AND '" . $date_max . "'";
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

			if ( ! empty( $_REQUEST['calendar_id'] ) )
			{
				DBQuery( "DELETE FROM attendance_calendar
					WHERE CALENDAR_ID='" . (int) $calendar_id . "'" );
			}

			$sql_calendar_days = '';

			// Insert Days.
			for ( $i = $begin; $i <= $end; $i += 86400 )
			{
				if ( $_REQUEST['weekdays'][ $weekday ] == 'Y' )
				{
					$sql_calendar_days .= DBInsertSQL(
						'attendance_calendar',
						[
							'SYEAR' => UserSyear(),
							'SCHOOL_ID' => UserSchool(),
							'SCHOOL_DATE' => date( 'Y-m-d', $i ),
							'MINUTES' => $minutes,
							'CALENDAR_ID' => (int) $calendar_id,
						]
					);
				}

				$weekday++;

				if ( $weekday == 7 )
					$weekday = 0;
			}

			if ( $sql_calendar_days )
			{
				DBQuery( $sql_calendar_days );
			}
		}

		// Set Current Calendar
		$_REQUEST['calendar_id'] = $calendar_id;

		// Unset modfunc & weekdays & title & minutes & copy ID & redirect URL.
		RedirectURL( [ 'modfunc', 'weekdays', 'title', 'minutes', 'copy_id' ] );
	}
}

// Delete Calendar
if ( $_REQUEST['modfunc'] === 'delete_calendar'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Calendar' ) ) )
	{
		$delete_sql = "DELETE FROM attendance_calendar
			WHERE CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "';";

		$delete_sql .= "DELETE FROM attendance_calendars
			WHERE CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "';";

		DBQuery( $delete_sql );

		$default_RET = DBGet( "SELECT CALENDAR_ID
			FROM attendance_calendars
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND DEFAULT_CALENDAR='Y'" );

		// Unset modfunc & calendar ID & redirect URL.
		RedirectURL( [ 'modfunc', 'calendar_id' ] );
	}
}

// Set non admin Current Calendar.
if ( User( 'PROFILE' ) !== 'admin'
	&& UserCoursePeriod() )
{
	$calendar_id = DBGetOne( "SELECT CALENDAR_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

	if ( $calendar_id )
	{
		$_REQUEST['calendar_id'] = $calendar_id;
	}
}

// Set Current Calendar.
if ( ! isset( $_REQUEST['calendar_id'] )
	|| intval( $_REQUEST['calendar_id'] ) < 1 )
{
	$default_calendar_id = DBGetOne( "SELECT CALENDAR_ID
		FROM attendance_calendars
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND DEFAULT_CALENDAR='Y'" );

	if ( $default_calendar_id )
	{
		$_REQUEST['calendar_id'] = $default_calendar_id;
	}
	else
	{
		$calendar_id = DBGetOne( "SELECT CALENDAR_ID
			FROM attendance_calendars
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( $calendar_id )
		{
			$_REQUEST['calendar_id'] = $calendar_id;
		}
		else
			$no_calendars_error[] = _( 'There are no calendars setup yet.' );
	}
}

unset( $_SESSION['_REQUEST_vars']['calendar_id'] );

// Event / Assignment details
if ( $_REQUEST['modfunc'] === 'detail' )
{
	if ( isset( $_POST['button'] )
		&& $_POST['button'] === _( 'Save' )
		&& AllowEdit() )
	{
		// Add eventual Dates to $_REQUEST['values'].
		AddRequestedDates( 'values' );

		if ( ! empty( $_REQUEST['values'] ) )
		{
			// FJ textarea fields MarkDown sanitize.
			if ( ! empty( $_REQUEST['values']['DESCRIPTION'] ) )
			{
				$_REQUEST['values']['DESCRIPTION'] = DBEscapeString( SanitizeMarkDown( $_POST['values']['DESCRIPTION'] ) );
			}

			// Update Event.
			if ( $_REQUEST['event_id'] !== 'new' )
			{
				DBUpdate(
					'calendar_events',
					$_REQUEST['values'],
					[ 'ID' => (int) $_REQUEST['event_id'] ]
				);

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
							'Y-m-d',
							mktime( 0, 0, 0,
								$_REQUEST['month_values']['SCHOOL_DATE'],
 								$_REQUEST['day_values']['SCHOOL_DATE'] + $i,
								$_REQUEST['year_values']['SCHOOL_DATE']
							)
 						);
					}

					$insert_columns = [ 'SYEAR' => UserSyear() , 'SCHOOL_ID' => UserSchool() ];

					$calendar_event_id = DBInsert(
						'calendar_events',
						$insert_columns + $_REQUEST['values'],
						'id'
					);

					if ( $calendar_event_id )
					{
						// Hook.
						do_action( 'School_Setup/Calendar.php|create_calendar_event' );
					}

					$i++;

				} while( is_numeric( $_REQUEST['REPEAT'] )
					&& $i <= $_REQUEST['REPEAT'] );
			}

			// Reload Calendar & close popup
			// @since 10.2.1 Maintain Calendar when closing event popup
			$opener_url = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . "&year=" .
				$_REQUEST['year'] . "&month=" . $_REQUEST['month'] . "&calendar_id=" . $_REQUEST['calendar_id'] );
			?>
<script>
	window.opener.ajaxLink(<?php echo json_encode( $opener_url ); ?>);
	window.close();
</script>
			<?php
		}
	}
	// Delete Event
	elseif ( isset( $_REQUEST['button'] )
		&& $_REQUEST['button'] == _( 'Delete' )
		&& ! isset( $_REQUEST['delete_cancel'] )
		&& AllowEdit() )
	{
		if ( DeletePrompt( _( 'Event' ), 'Delete', false ) )
		{
			DBQuery( "DELETE FROM calendar_events
				WHERE ID='" . (int) $_REQUEST['event_id'] . "'" );

			//hook
			do_action( 'School_Setup/Calendar.php|delete_calendar_event' );

			// Reload Calendar & close popup
			// @since 10.2.1 Maintain Calendar when closing Event popup
			$opener_url = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . "&year=" .
				$_REQUEST['year'] . "&month=" . $_REQUEST['month'] . "&calendar_id=" . $_REQUEST['calendar_id'] );
			?>
<script>
	window.opener.ajaxLink(<?php echo json_encode( $opener_url ); ?>);
	window.close();
</script>
			<?php
		}
	}
	// Display Event / Assignment
	else
	{
		// Event
		if ( ! empty( $_REQUEST['event_id'] ) )
		{
			if ( $_REQUEST['event_id'] !== 'new' )
			{
				$RET = DBGet( "SELECT TITLE,DESCRIPTION,SCHOOL_DATE
					FROM calendar_events
					WHERE ID='" . (int) $_REQUEST['event_id'] . "'" );

				$title = $RET[1]['TITLE'];
			}
			else
			{
				//FJ add translation
				$title = _( 'New Event' );

				$RET[1]['SCHOOL_DATE'] = issetVal( $_REQUEST['school_date'] );
			}

			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=detail&event_id=' . $_REQUEST['event_id'] . '&month=' . $_REQUEST['month'] .
				'&year=' . $_REQUEST['year'] . '&calendar_id=' . $_REQUEST['calendar_id']  ) . '" method="POST">';
		}
		// Assignment
		elseif ( ! empty( $_REQUEST['assignment_id'] ) )
		{
			//FJ add assigned date
			$RET = DBGet( "SELECT a.TITLE,a.STAFF_ID,a.DUE_DATE AS SCHOOL_DATE,
				a.DESCRIPTION,a.ASSIGNED_DATE,c.TITLE AS COURSE,a.SUBMISSION
				FROM gradebook_assignments a,courses c
				WHERE (a.COURSE_ID=c.COURSE_ID
					OR c.COURSE_ID=(SELECT cp.COURSE_ID
						FROM course_periods cp
						WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
				AND a.ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" );

			$title = $RET[1]['TITLE'];

			$RET[1]['STAFF_ID'] = GetTeacher( $RET[1]['STAFF_ID'] );
		}

		echo '<br />';

		PopTable( 'header', $title );

		echo '<table class="cellpadding-5"><tr><td>'  . DateInput(
			$RET[1]['SCHOOL_DATE'],
			'values[SCHOOL_DATE]',
			( empty( $_REQUEST['assignment_id'] ) ? _( 'Date' ) : _( 'Due Date' ) ),
			false
		) . '</td></tr>';

		// Add assigned date.
		if ( ! empty( $RET[1]['ASSIGNED_DATE'] ) )
		{
			echo '<tr><td>' .
				DateInput( $RET[1]['ASSIGNED_DATE'], 'values[ASSIGNED_DATE]', _( 'Assigned Date' ), false ) .
			'</td></tr>';
		}

		// Add submit Assignment link.
		if ( ! empty( $RET[1]['SUBMISSION'] )
			&& $RosarioModules['Grades']
			&& AllowUse( 'Grades/StudentAssignments.php' ) )
		{
			echo '<tr><td>
				<a href="' . URLEscape( 'Modules.php?modname=Grades/StudentAssignments.php&assignment_id=' .
					$_REQUEST['assignment_id'] ) . '" onclick="window.opener.ajaxLink(this.href); window.close();">' .
				_( 'Submit Assignment' ) .
			'</a></td></tr>';
		}

		// FJ add event repeat.
		if ( ! empty( $_REQUEST['event_id'] )
			&& $_REQUEST['event_id'] === 'new' )
		{
			echo '<tr><td>
				<input name="REPEAT" id="REPEAT" value="0" type="number" min="0" max="300" />&nbsp;' . _( 'Days' ) .
				FormatInputTitle( _( 'Event Repeat' ), 'REPEAT' ) .
			'</td></tr>';
		}

		// Hook.
		do_action( 'School_Setup/Calendar.php|event_field' );


		// FJ bugfix SQL bug value too long for type varchar(50).
		echo '<tr><td>' .
			TextInput(
				issetVal( $RET[1]['TITLE'], '' ),
				'values[TITLE]',
				_( 'Title' ),
				'required size="20" maxlength="50"'
			) .
		'</td></tr>';

		if ( ! empty( $RET[1]['COURSE'] ) )
		{
			echo '<tr><td>' .
				NoInput( $RET[1]['COURSE'], _( 'Course' ) ) .
			'</td></tr>';
		}

		if ( ! empty( $RET[1]['STAFF_ID'] )
			&& User( 'PROFILE' ) !== 'teacher' )
		{
			echo '<tr><td>' .
				TextInput( $RET[1]['STAFF_ID'], 'values[STAFF_ID]', _( 'Teacher' ) ) .
			'</td></tr>';
		}

		echo '<tr><td>' .
			TextAreaInput( issetVal( $RET[1]['DESCRIPTION'], '' ), 'values[DESCRIPTION]', _( 'Notes' ) ) .
		'</td></tr>';

		if ( AllowEdit() )
		{
			echo '<tr><td colspan="2">' . SubmitButton( _( 'Save' ), 'button' );

			if ( $_REQUEST['event_id'] !== 'new' )
			{
				echo SubmitButton( _( 'Delete' ), 'button', '' );
			}

			echo '</td></tr>';
		}

		echo '</table>';

		PopTable( 'footer' );

		if ( ! empty( $_REQUEST['event_id'] ) )
			echo '</form>';
	}

	// Unset button & values & redirect URL.
	RedirectURL( [ 'button', 'values' ] );
}

// List Events
if ( $_REQUEST['modfunc'] === 'list_events' )
{
	$start_date = RequestedDate( 'start', '' );

	if ( ! $start_date )
	{
		$min_date = DBGet( "SELECT min(SCHOOL_DATE) AS MIN_DATE
			FROM attendance_calendar
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( isset( $min_date[1]['MIN_DATE'] ) )
		{
			$start_date = $min_date[1]['MIN_DATE'];
		}
		else
			$start_date = date( 'Y-m' ) . '-01';
	}

	$end_date = RequestedDate( 'end', '' );

	if ( ! $end_date )
	{
		$max_date = DBGet( "SELECT max(SCHOOL_DATE) AS MAX_DATE
			FROM attendance_calendar
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( isset( $max_date[1]['MAX_DATE'] ) )
		{
			$end_date = $max_date[1]['MAX_DATE'];
		}
		else
			$end_date = DBDate();
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year']  ) . '" method="POST">';

	DrawHeader( '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] ) . '" >' . _( 'Back to Calendar' ) . '</a>' );

	DrawHeader(
		_( 'Timeframe' ) . ': ' .
		PrepareDate( $start_date, '_start', false ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end', false ) . ' ' .
		Buttons( _( 'Go' ) )
	);


	$functions = [ 'SCHOOL_DATE' => 'ProperDate', 'DESCRIPTION' => 'makeTextarea' ];

	$events_RET = DBGet( "SELECT ID,SCHOOL_DATE,TITLE,DESCRIPTION
		FROM calendar_events
		WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'", $functions );

	$column_names = [
		'SCHOOL_DATE' => _( 'Date' ),
		'TITLE' => _('Event'),
		'DESCRIPTION' => _( 'Description' )
	];

	ListOutput( $events_RET, $column_names, 'Event', 'Events');

	echo '</form>';
}

// Display Calendar View.
if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$last = 31;

	while( ! checkdate( (int) $_REQUEST['month'], $last, (int) $_REQUEST['year'] ) )
	{
		$last--;
	}

	$first_day_month = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-01';

	$last_day_month = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . $last;

	$calendar_SQL = "SELECT SCHOOL_DATE,MINUTES,BLOCK
		FROM attendance_calendar
		WHERE SCHOOL_DATE BETWEEN '" . $first_day_month . "'
		AND '" . $last_day_month . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "'";

	$calendar_RET = DBGet( $calendar_SQL, [], [ 'SCHOOL_DATE' ] );

	$update_calendar = false;

	// Update School Day minutes
	if ( AllowEdit()
		&& isset( $_REQUEST['minutes'] ) )
	{
		foreach ( (array) $_REQUEST['minutes'] as $date => $minutes )
		{
			// Fix SQL error when all-day checked & minutes.
			if ( ! empty( $_REQUEST['all_day'][ $date ] ) )
			{
				continue;
			}

			if ( ! empty( $calendar_RET[ $date ] ) )
			{
				//if ( $minutes!='0' && $minutes!='')
				//FJ fix bug MINUTES not numeric
				if ( intval( $minutes ) > 0 )
				{
					DBUpdate(
						'attendance_calendar',
						[ 'MINUTES' => intval( $minutes ) ],
						[
							'SCHOOL_DATE' => $date,
							'SYEAR' => UserSyear(),
							'SCHOOL_ID' => UserSchool(),
							'CALENDAR_ID' => (int) $_REQUEST['calendar_id'],
						]
					);
				}
				else
				{
					DBQuery( "DELETE FROM attendance_calendar
						WHERE SCHOOL_DATE='" . $date . "'
						AND SYEAR='" . UserSyear() . "'
						AND SCHOOL_ID='" . UserSchool() . "'
						AND CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "'" );
				}

				$update_calendar = true;
			}
			//elseif ( $minutes!='0' && $minutes!='')
			//FJ fix bug MINUTES not numeric
			elseif ( intval( $minutes ) > 0 )
			{
				// Fix MySQL 5.6 syntax error when WHERE without FROM clause, use dual table
				DBQuery( "INSERT INTO attendance_calendar
					(SYEAR,SCHOOL_ID,SCHOOL_DATE,CALENDAR_ID,MINUTES)
					SELECT '" . UserSyear() . "','" . UserSchool() . "','" . $date . "','" . $_REQUEST['calendar_id'] . "','" . intval( $minutes ) . "'
					FROM dual
					WHERE NOT EXISTS(SELECT 1
					FROM attendance_calendar
					WHERE SCHOOL_DATE='" . $date . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "')" );

				$update_calendar = true;
			}
		}

		// Unset minutes & redirect URL.
		RedirectURL( 'minutes' );
	}

	// Update All day school.
	if ( AllowEdit()
		&& isset( $_REQUEST['all_day'] ) )
	{
		foreach ( (array) $_REQUEST['all_day'] as $date => $yes )
		{
			if ( $yes === 'Y' )
			{
				if ( ! empty( $calendar_RET[ $date ] ) )
				{
					DBUpdate(
						'attendance_calendar',
						[ 'MINUTES' => '999' ],
						[
							'SCHOOL_DATE' => $date,
							'SYEAR' => UserSyear(),
							'SCHOOL_ID' => UserSchool(),
							'CALENDAR_ID' => (int) $_REQUEST['calendar_id'],
						]
					);
				}
				else
				{
					// Fix MySQL 5.6 syntax error when WHERE without FROM clause, use dual table
					DBQuery( "INSERT INTO attendance_calendar
						(SYEAR,SCHOOL_ID,SCHOOL_DATE,CALENDAR_ID,MINUTES)
						SELECT '" . UserSyear() . "','" . UserSchool()."','" . $date . "','" . $_REQUEST['calendar_id'] . "','999'
						FROM dual
						WHERE NOT EXISTS(SELECT 1
						FROM attendance_calendar
						WHERE SCHOOL_DATE='" . $date . "'
						AND SYEAR='" . UserSyear() . "'
						AND SCHOOL_ID='" . UserSchool() . "'
						AND CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "')" );
				}

				$update_calendar = true;
			}
			elseif ( ! empty( $calendar_RET[ $date ] ) )
			{
				DBQuery( "DELETE FROM attendance_calendar
					WHERE SCHOOL_DATE='" . $date . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "'" );

				$update_calendar = true;
			}
		}

		// Unset all day & redirect URL.
		RedirectURL( 'all_day' );
	}

	// Update Blocks.
	if ( AllowEdit()
		&& isset( $_REQUEST['blocks'] ) )
	{
		foreach ( (array) $_REQUEST['blocks'] as $date => $block )
		{
			if ( $calendar_RET[ $date ] )
			{
				DBQuery( "UPDATE attendance_calendar
					SET BLOCK='" . $block . "'
					WHERE SCHOOL_DATE='" . $date . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "'" );

				$update_calendar = true;
			}
		}

		// Unset blocks & redirect URL.
		RedirectURL( 'blocks' );
	}

	// Update Calendar RET
	if ( $update_calendar )
	{
		$calendar_RET = DBGet( $calendar_SQL, [], [ 'SCHOOL_DATE' ] );
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

	// Admin Headers
	if ( AllowEdit() )
	{
		$title_RET = DBGet( "SELECT CALENDAR_ID,TITLE,DEFAULT_CALENDAR
			FROM attendance_calendars WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY DEFAULT_CALENDAR IS NULL,DEFAULT_CALENDAR ASC,TITLE" );

		$defaults = 0;

		$options = [];

		foreach ( (array) $title_RET as $title )
		{
			$options[ $title['CALENDAR_ID'] ] = $title['TITLE'] .
				( $title['DEFAULT_CALENDAR'] === 'Y' ? ' (' . _( 'Default' ) . ')' : '' );

			if ( $title['DEFAULT_CALENDAR'] === 'Y' )
			{
				$defaults++;
			}
		}

		// @since 10.2.1 Maintain current month on calendar change.
		$calendar_onchange_URL = PreparePHP_SELF( [], [ 'calendar_id' ] ) . '&calendar_id=';

		$links = SelectInput(
			$_REQUEST['calendar_id'],
			'calendar_id',
			'<span class="a11y-hidden">' . _( 'Calendar' ) . '</span>',
			$options,
			false,
			' onchange="' . AttrEscape( 'ajaxLink(' . json_encode( $calendar_onchange_URL ) .
				' + document.getElementById("calendar_id").value);' ) . '" ',
			false
		);

		$links_right = button(
			'add',
			_( 'Create' ),
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=create' )
		);

		if ( $_REQUEST['calendar_id'] )
		{
			$links_right .= ' &nbsp; ' .
			button(
				'pencil',
				_( 'Edit' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=create&calendar_id=' . $_REQUEST['calendar_id'] )
			) . ' &nbsp; ' .
			button(
				'remove',
				_( 'Delete' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete_calendar&calendar_id=' . $_REQUEST['calendar_id'] )
			);
		}
	}

	$list_events_URL = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=list_events&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'];

	DrawHeader(
		PrepareDate( mb_strtoupper( $first_day_month ), '', false, [ 'M' => 1, 'Y' => 1, 'submit' => true ] ) .
		' <a href="' . URLEscape( $list_events_URL ) . '">' .
			_( 'List Events' ) .
		'</a>',
		SubmitButton()
	);

	if ( ! empty( $links ) )
	{
		DrawHeader( $links, $links_right );
	}

	// @since 4.5 Calendars header hook.
	do_action( 'School_Setup/Calendar.php|header' );

	if ( AllowEdit()
		&& $defaults != 1 )
	{
		echo ErrorMessage(
			[ $defaults ?
				_( 'This school has more than one default calendar!' ) :
				_( 'This school does not have a default calendar!' )
			]
		);
	}

	if ( isset( $no_calendars_error ) )
	{
		// No calendars, die.
		echo ErrorMessage( $no_calendars_error, 'fatal' );
	}

	echo '<br />';

	// Get Events
	$events_RET = DBGet( "SELECT ID,SCHOOL_DATE,TITLE,DESCRIPTION
		FROM calendar_events
		WHERE SCHOOL_DATE BETWEEN '" . $first_day_month . "'
		AND '" . $last_day_month . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'", [], [ 'SCHOOL_DATE' ] );

	// Get Assignments
	$assignments_RET = null;

	if ( User( 'PROFILE' ) === 'parent'
		|| User( 'PROFILE' ) === 'student' )
	{
		$assignments_SQL = "SELECT ASSIGNMENT_ID AS ID,a.DUE_DATE AS SCHOOL_DATE,a.TITLE,'Y' AS ASSIGNED
			FROM gradebook_assignments a,schedule s
			WHERE (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
			AND s.STUDENT_ID='" . UserStudentID() . "'
			AND (a.DUE_DATE BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
			AND a.DUE_DATE BETWEEN '" . $first_day_month . "' AND '" . $last_day_month . "'";
	}
	elseif ( User( 'PROFILE' ) === 'teacher' )
	{
		$assignments_SQL = "SELECT ASSIGNMENT_ID AS ID,a.DUE_DATE AS SCHOOL_DATE,a.TITLE,
				CASE WHEN a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL THEN 'Y' ELSE NULL END AS ASSIGNED
			FROM gradebook_assignments a
			WHERE a.STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND a.DUE_DATE BETWEEN '" . $first_day_month . "' AND '" . $last_day_month . "'";
	}

	if ( isset( $assignments_SQL ) )
	{
		$assignments_RET = DBGet( $assignments_SQL, [], [ 'SCHOOL_DATE' ] );
	}

	// Calendar Events onclick popup.
	$popup_url = URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=detail&year=' .
		$_REQUEST['year'] . '&month=' . $_REQUEST['month'] . '&calendar_id=' . $_REQUEST['calendar_id'] );
?>
<script>
	var popupURL = <?php echo json_encode( $popup_url ); ?>;

	function CalEventPopup(url) {
		popups.open( url, "scrollbars=yes,resizable=yes,width=500,height=400" );
	}
</script>
<?php

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
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

	$return_counter = 0;

	// Skip until first Day of Month.
	$skip = date( "w", strtotime( $first_day_month ) );

	if ( $skip )
	{
		echo '<td colspan="' . $skip . '" class="calendar-skip">&nbsp;</td>';

		$return_counter = $skip;
	}

	// Days.
	for ( $i = 1; $i <= $last; $i++ )
	{
		$date = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . str_pad( $i, 2, '0', STR_PAD_LEFT );

		$minutes = isset( $calendar_RET[ $date ][1]['MINUTES'] ) ? $calendar_RET[ $date ][1]['MINUTES'] : '';

		$events_date = issetVal( $events_RET[ $date ], [] );

		$assignments_date = issetVal( $assignments_RET[ $date ], [] );

		$day_classes = CalendarDayClasses( $date, $minutes );

		$day_inner_classes = CalendarDayClasses(
			$date,
			$minutes,
			$events_date,
			$assignments_date,
			'inner'
		);

		$day_number_classes = CalendarDayClasses(
			$date,
			$minutes,
			$events_date,
			$assignments_date,
			'number'
		);

		echo '<td class="calendar-day' . AttrEscape( $day_classes ) . '">
			<table class="' . AttrEscape( $day_inner_classes ) . '"><tr>';


		// Calendar Day number.
		echo '<td class="' . AttrEscape( $day_number_classes ) . '">' . $i . '</td>
		<td class="width-100p align-right">';

		echo CalendarDayMinutesHTML( $date, $minutes );

		$block = issetVal( $calendar_RET[ $date ][1]['BLOCK'] );

		echo CalendarDayBlockHTML( $date, $minutes, $block );

		echo '</td></tr>
			<tr><td colspan="2" class="calendar-event valign-top">';

		echo CalendarDayEventsHTML( $date, $events_date );

		echo CalendarDayAssignmentsHTML( $date, $assignments_date );

		echo '</td></tr><tr class="valign-bottom">';

		echo CalendarDayNewAssignmentHTML( $date, $assignments_date );

		echo CalendarDayRotationNumberHTML( $date, $minutes );

		echo '</tr></table></td>';

		$return_counter++;

		if ( $return_counter % 7 === 0 )
		{
			echo '</tr><tr>';
		}
	}

	// Skip from Last Day of Month until end of Calendar
	if ( $return_counter %7 !== 0 )
	{
		$skip = 7 - $return_counter % 7;

		echo '<td colspan="' . $skip . '" class="calendar-skip">&nbsp;</td>';
	}

	echo '</tr></tbody></table>';

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '<br /><br /></form>';

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// @since 9.0 Add Calendar days legend.
		$legend_html = '<table class="width-100p"><tr><td class="legend-square full"></td><td>' . _( 'Full school day' ) . '</td></tr>';
		$legend_html .= '<tr><td class="legend-square minutes"></td><td>' . _( 'Partial school day (minutes)' ) . '</td></tr>';
		$legend_html .= '<tr><td class="legend-square no-school"></td><td>' . _( 'No school day' ) . '</td></tr></table>';

		DrawHeader(
			$legend_html .
			FormatInputTitle( _( 'Legend' ), '', false, '' )
		);
	}
}
