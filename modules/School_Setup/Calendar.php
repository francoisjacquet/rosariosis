<?php

DrawHeader( ProgramTitle() );

//FJ days numbered
if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) > 0 )
	include( 'modules/School_Setup/includes/DayToNumber.inc.php' );

// Set Month
if ( !isset( $_REQUEST['month'] )
	|| mb_strlen( $_REQUEST['month'] ) !== 3 )
{
	$_REQUEST['month'] = mb_strtoupper( date( 'M' ) );
}

// Set Year
if ( !isset( $_REQUEST['year'] )
	|| mb_strlen( $_REQUEST['year'] ) !== 4 )
{
	$_REQUEST['year'] = date( 'Y' );
}

// Set Time = First Day of Month
$time = mktime( 0, 0, 0, MonthNWSwitch( $_REQUEST['month'], 'tonum' ), 1, $_REQUEST['year'] );

// Create / Recreate Calendar
if ( $_REQUEST['modfunc'] === 'create'
	&& AllowEdit() )
{
	$fy_RET = DBGet( DBQuery( "SELECT START_DATE,END_DATE
		FROM SCHOOL_MARKING_PERIODS
		WHERE MP='FY'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" ) );

	$fy = $fy_RET[1];

	// Get Calendars Info
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
		ORDER BY " . db_case( array( 'ac.SCHOOL_ID', "'" . UserSchool() . "'", 0, 'ac.SCHOOL_ID' ) ) . ",ac.DEFAULT_CALENDAR ASC,ac.TITLE" ) );

	// prepare table for Copy Calendar & add ' (Default)' mention
	$copy_calendar_options = array();

	foreach( (array)$title_RET as $id => $title )
	{
		$copy_calendar_options[$id] = $title['TITLE'];

		if ( AllowEdit()
			&& $title['DEFAULT_CALENDAR'] === 'Y' )
		{
			$default_id = $id;

			$copy_calendar_options[$id] .= ' (' . _( 'Default' ) . ')';
		}
	}

	$div = false;

	$message = '<table class="width-100p valign-top"><tr class="st"><td>';

	// title
	$message .= TextInput(
		( $_REQUEST['calendar_id'] ? $title_RET[$default_id]['TITLE'] : '' ),
		'title',
		'<span class="legend-red">' . _( 'Title' ) . '</span>',
		'required',
		$div
	);

	$message .= '</td><td>';

	// default
	$message .= CheckboxInput(
		$_REQUEST['calendar_id'] && $title_RET[$default_id]['DEFAULT_CALENDAR'] == 'Y',
		'default',
		_( 'Default Calendar for this School' ),
		'',
		true
	);

	$message .= '</td><td>';

	// copy calendar
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

	// from date
	$message .= '<table class="width-100p valign-top"><tr class="st"><td>' . _( 'From' ) . ' ';

	$message .= DateInput(
		$_REQUEST['calendar_id'] && $title_RET[$default_id]['START_DATE'] ?
			$title_RET[$default_id]['START_DATE'] :
			$fy['START_DATE'],
		'min',
		'',
		$div,
		true,
		!( $_REQUEST['calendar_id'] && $title_RET[$default_id]['START_DATE'] )
	);

	// to date
	$message .= '</td><td>' . _( 'To' )  . ' ';
	$message .= DateInput(
		$_REQUEST['calendar_id'] && $title_RET[$default_id]['END_DATE'] ?
			$title_RET[$default_id]['END_DATE'] :
			$fy['END_DATE'],
		'max',
		'',
		$div,
		true,
		!( $_REQUEST['calendar_id'] && $title_RET[$default_id]['END_DATE'] )
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

	foreach ( (array)$weekdays as $id => $weekday )
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

	$message .= implode( '</TD><TD>', $weekdays_inputs );

	$message .= '</td></tr></table>';

	$message .= '<table class="width-100p"><tr class="st valign-top"><td>';

	// minutes
	$minutes_tip_text = ( $_REQUEST['calendar_id'] ?
		_( 'Default is Full Day if Copy Calendar is N/A.' ) . ' ' . _( 'Otherwise Default is minutes from the Copy Calendar' ) :
		_( 'Default is Full Day' )
	);

	$message .= TextInput(
		( $_REQUEST['calendar_id'] ? $title_RET[$default_id]['MINUTES'] : '' ),
		'minutes',
		'<span class="legend-gray" title="' . $minutes_tip_text . '" style="cursor:help">' . _( 'Minutes' ) . '*</span>',
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
			$calendar_id = $_REQUEST['calendar_id'];
		else
		{
			$calendar_id = DBGet( DBQuery( "SELECT " . db_seq_nextval( 'CALENDARS_SEQ' ) . " AS CALENDAR_ID " . FROM_DUAL ) );

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
			$minutes = intval( $_REQUEST['minutes'] );

		// Copy Calendar
		if ( $_REQUEST['copy_id'] )
		{
			$weekdays_list = '\'' . implode( '\',\'', array_keys( $_REQUEST['weekdays'] ) ) . '\'';

			if ( $_REQUEST['calendar_id']
				&& $_REQUEST['calendar_id'] === $_REQUEST['copy_id'] )
			{
				$date_min = $_REQUEST['day_min'] . '-' . $_REQUEST['month_min'] . '-' . $_REQUEST['year_min'];

				$date_max = $_REQUEST['day_max'].'-'.$_REQUEST['month_max'].'-'.$_REQUEST['year_max'];

				DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
					WHERE CALENDAR_ID='" . $calendar_id . "'
					AND (SCHOOL_DATE NOT BETWEEN '" . $date_min ."' AND '". $date_max ."'
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

				// Insert Days
				$create_calendar_sql = "INSERT INTO ATTENDANCE_CALENDAR
					(SYEAR,SCHOOL_ID,SCHOOL_DATE,MINUTES,CALENDAR_ID)
					(SELECT '" . UserSyear() . "','" . UserSchool() . "',SCHOOL_DATE," . $minutes . ",'" . $calendar_id . "'
						FROM ATTENDANCE_CALENDAR
						WHERE CALENDAR_ID='".$_REQUEST['copy_id']."'
						AND extract(DOW FROM SCHOOL_DATE) IN (" . $weekdays_list . ")";

				//FJ bugfix SQL bug empty school dates
				if ( isset( $_REQUEST['day_min'] )
					&& isset( $_REQUEST['month_min'] )
					&& isset( $_REQUEST['year_min'] )
					&& isset( $_REQUEST['day_max'] )
					&& isset( $_REQUEST['month_max'] )
					&& isset( $_REQUEST['year_max'] ) )
				{
					$_REQUEST['date_min'] = RequestedDate(
						$_REQUEST['day_min'],
						$_REQUEST['month_min'],
						$_REQUEST['year_min']
					);

					$_REQUEST['date_max'] = RequestedDate(
						$_REQUEST['day_max'],
						$_REQUEST['month_max'],
						$_REQUEST['year_max']
					);

					if ( !empty( $_REQUEST['date_min'] )
						&& !empty( $_REQUEST['date_max'] ) )
					{
						$create_calendar_sql .= " AND SCHOOL_DATE
							BETWEEN '" . $_REQUEST['date_min'] . "'
							AND '" . $_REQUEST['date_max'] . "'";
					}
				}

				$create_calendar_sql .= ")";

				DBQuery( $create_calendar_sql );
			}
		}
		// Create Calendar
		else
		{
			$begin = mktime(0,0,0,MonthNWSwitch($_REQUEST['month_min'],'to_num'),$_REQUEST['day_min']*1,$_REQUEST['year_min']) + 43200;

			$end = mktime(0,0,0,MonthNWSwitch($_REQUEST['month_max'],'to_num'),$_REQUEST['day_max']*1,$_REQUEST['year_max']) + 43200;

			$weekday = date('w',$begin);

			if ( $_REQUEST['calendar_id'] )
			{
				DBQuery( "DELETE FROM ATTENDANCE_CALENDAR
					WHERE CALENDAR_ID='" . $calendar_id . "'" );
			}

			// Insert Days
			for ( $i = $begin; $i <= $end; $i += 86400 )
			{
				if ( $_REQUEST['weekdays'][$weekday] == 'Y' )
				{
					DBQuery( "INSERT INTO ATTENDANCE_CALENDAR
						(SYEAR,SCHOOL_ID,SCHOOL_DATE,MINUTES,CALENDAR_ID)
						values('" . UserSyear() . "','" . UserSchool() . "','" . date( 'd-M-Y', $i ) . "'," . $minutes . ",'" . $calendar_id . "')" );
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
if ( !isset( $_REQUEST['calendar_id'] )
	|| intval( $_REQUEST['calendar_id'] ) < 1 )
{
	$default_RET = DBGet( DBQuery( "SELECT CALENDAR_ID
		FROM ATTENDANCE_CALENDARS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND DEFAULT_CALENDAR='Y'" ) );

	if ( count( $default_RET ) )
	{
		$_REQUEST['calendar_id'] = $default_RET[1]['CALENDAR_ID'];
	}
	else
	{
		$calendars_RET = DBGet( DBQuery( "SELECT CALENDAR_ID
			FROM ATTENDANCE_CALENDARS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ) );

		if ( count( $calendars_RET ) )
			$_REQUEST['calendar_id'] = $calendars_RET[1]['CALENDAR_ID'];
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
			$_REQUEST['day_values']['SCHOOL_DATE'],
			$_REQUEST['month_values']['SCHOOL_DATE'],
			$_REQUEST['year_values']['SCHOOL_DATE']
		);
	}

	if ( $_POST['button'] === _( 'Save' )
		&& AllowEdit() )
	{
		if ( $_REQUEST['values'] )
		{
			// Update Event
			if ( $_REQUEST['event_id'] !== 'new' )
			{
				$sql = "UPDATE CALENDAR_EVENTS SET ";
				
				foreach($_REQUEST['values'] as $column=>$value)
					$sql .= $column."='".$value."',";

				$sql = mb_substr($sql,0,-1) . " WHERE ID='" . $_REQUEST['event_id'] . "'";

				DBQuery( $sql );

				//hook
				do_action('School_Setup/Calendar.php|update_calendar_event');
			}
			// Create Event
			else
			{
				//FJ add event repeat
				$i = 0;

				do {
					if ( $i > 0 ) //school date + 1 day
					{
						$_REQUEST['values']['SCHOOL_DATE'] = date(
							'd-M-Y',
							mktime( 0, 0, 0, MonthNWSwitch( $_REQUEST['month_values']['SCHOOL_DATE'], 'tonum' ),
							$_REQUEST['day_values']['SCHOOL_DATE'] + $i,
							$_REQUEST['year_values']['SCHOOL_DATE'] )
						);
					}

					$sql = "INSERT INTO CALENDAR_EVENTS ";

					$fields = 'ID,SYEAR,SCHOOL_ID,';

					$calendar_event_RET = DBGet( DBQuery( "SELECT " . db_seq_nextval( 'CALENDAR_EVENTS_SEQ' ) . ' AS CALENDAR_EVENT_ID ' . FROM_DUAL ) );

					$calendar_event_id = $calendar_event_RET[1]['CALENDAR_EVENT_ID'];

					$values = $calendar_event_id . ",'" . UserSyear() . "','" . UserSchool() . "',";

					$go = false;

					foreach ( (array)$_REQUEST['values'] as $column => $value )
					{
						if ( !empty( $value )
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

						//hook
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
	var opener_reload = document.createElement("a");
	opener_reload.href = <?php echo json_encode( $opener_URL ); ?>;
	opener_reload.target = "body";
	window.opener.ajaxLink(opener_reload);
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
	var opener_reload = document.createElement("a");
	opener_reload.href = <?php echo json_encode( $opener_URL ); ?>;
	opener_reload.target = "body";
	window.opener.ajaxLink(opener_reload);
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
				$RET = DBGet( DBQuery( "SELECT TITLE,DESCRIPTION,to_char(SCHOOL_DATE,'dd-MON-YYYY') AS SCHOOL_DATE
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

			echo '<FORM action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=detail&event_id=' . $_REQUEST['event_id'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] . '" METHOD="POST">';
		}
		// Assignment
		elseif ( $_REQUEST['assignment_id'] )
		{
			//FJ add assigned date
			$RET = DBGet( DBQuery( "SELECT a.TITLE,a.STAFF_ID,to_char(a.DUE_DATE,'dd-MON-YYYY') AS SCHOOL_DATE,a.DESCRIPTION,a.ASSIGNED_DATE,c.TITLE AS COURSE
				FROM GRADEBOOK_ASSIGNMENTS a,COURSES c
				WHERE (a.COURSE_ID=c.COURSE_ID
					OR c.COURSE_ID=(SELECT cp.COURSE_ID
						FROM COURSE_PERIODS cp
						WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
				AND a.ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'") );

			$title = $RET[1]['TITLE'];

			$RET[1]['STAFF_ID'] = GetTeacher( $RET[1]['STAFF_ID'] );
		}

		echo '<BR />';

		PopTable( 'header', $title );

		echo '<TABLE class="cellpadding-5 col1-align-right"><TR><TD>' . _( 'Date' ) . '</TD>' .
			'<TD>' . DateInput( $RET[1]['SCHOOL_DATE'], 'values[SCHOOL_DATE]', '', false ) . '</TD></TR>';

		//FJ add assigned date
		if ( $RET[1]['ASSIGNED_DATE'] )
			echo '<TR><TD>' . _( 'Assigned Date' ) . '</TD>' .
				'<TD>' . DateInput( $RET[1]['ASSIGNED_DATE'], 'values[ASSIGNED_DATE]', '', false ) . '</TD></TR>';

		//FJ add event repeat
		if ( $_REQUEST['event_id'] === 'new' )
		{
			echo '<TR><TD>' . _( 'Event Repeat' ) . '</TD>' .
				'<TD><input name="REPEAT" value="0" maxlength="3" size="1" type="number" min="0" />&nbsp;' . _( 'Days' ) . '</TD></TR>';
		}

		//hook
		do_action( 'School_Setup/Calendar.php|event_field' );

		
		//FJ bugfix SQL bug value too long for type character varying(50)
		echo '<TR><TD>' . _( 'Title' ) . '</TD>' .
			'<TD>' . TextInput( $RET[1]['TITLE'], 'values[TITLE]', '', 'required maxlength="50"' ) . '</TD></TR>';

		//FJ add course
		if ( $RET[1]['COURSE'] )
			echo '<TR><TD>' . _( 'Course' ) . '</TD>' .
				'<TD>' . $RET[1]['COURSE'] . '</TD></TR>';

		if ( $RET[1]['STAFF_ID'] )
			echo '<TR><TD>' . _( 'Teacher' ) . '</TD>' .
				'<TD>' . TextInput( $RET[1]['STAFF_ID'], 'values[STAFF_ID]' ) . '</TD></TR>';

		echo '<TR><TD>' . _( 'Notes' ) . '</TD>' .
			'<TD>' . TextAreaInput( $RET[1]['DESCRIPTION'], 'values[DESCRIPTION]' ) . '</TD></TR>';

		if ( AllowEdit() )
		{
			echo '<TR><TD colspan="2" class="center">' . SubmitButton( _( 'Save' ), 'button' );

			if ( $_REQUEST['event_id'] !== 'new' )
				echo SubmitButton( _( 'Delete' ), 'button' );

			echo '</TD></TR>';
		}

		echo '</TABLE>';

		PopTable( 'footer' );

		if ( $_REQUEST['event_id'] )
			echo '</FORM>';

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
			$_REQUEST['day_start'],
			$_REQUEST['month_start'],
			$_REQUEST['year_start']
		);
	}
	else
	{
		$min_date = DBGet( DBQuery( "SELECT min(SCHOOL_DATE) AS MIN_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ) );

		if ( isset( $min_date[1]['MIN_DATE'] ) )
			$start_date = $min_date[1]['MIN_DATE'];
		else
			$start_date = '01-' . mb_strtoupper( date( 'M-y' ) );
	}

	if ( $_REQUEST['day_end']
		&& $_REQUEST['month_end']
		&& $_REQUEST['year_end'] )
	{
		$end_date = RequestedDate(
			$_REQUEST['day_end'],
			$_REQUEST['month_end'],
			$_REQUEST['year_end']
		);
	}
	else
	{
		$max_date = DBGet( DBQuery( "SELECT max(SCHOOL_DATE) AS MAX_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ) );

		if ( isset( $max_date[1]['MAX_DATE'] ) )
			$end_date = $max_date[1]['MAX_DATE'];
		else
			$end_date = mb_strtoupper( date( 'd-M-Y' ) );
	}

	echo '<FORM action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] . '" METHOD="POST">';

	DrawHeader( '<A HREF="Modules.php?modname=' . $_REQUEST['modname'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] . '" >' . _( 'Back to Calendar' ) . '</A>' );

	DrawHeader(
		_( 'Timeframe' ) . ': ' .
		PrepareDate( $start_date, '_start' ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end' ) . ' ' .
		Buttons( _( 'Go' ) )
	);


	$functions = array( 'SCHOOL_DATE' => 'ProperDate', 'DESCRIPTION' => '_formatDescription' );

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

	echo '</FORM>';
}

// Display Calendar View
if ( empty( $_REQUEST['modfunc'] ) )
{

	if ( isset( $error ) )
		echo ErrorMessage( $error );

	$last = 31;

	while( !checkdate( MonthNWSwitch( $_REQUEST['month'], 'tonum' ), $last, $_REQUEST['year'] ) )
		$last--;

	$first_day_month = date( 'd-M-Y', $time );

	$last_day_month = date(
		'd-M-Y',
		mktime( 0, 0, 0, MonthNWSwitch( $_REQUEST['month'], 'tonum' ), $last, $_REQUEST['year'] )
	);

	$calendar_RET = DBGet( DBQuery( "SELECT to_char(SCHOOL_DATE,'dd-MON-YYYY') AS SCHOOL_DATE,MINUTES,BLOCK
		FROM ATTENDANCE_CALENDAR
		WHERE SCHOOL_DATE BETWEEN '" . $first_day_month . "'
		AND '" . $last_day_month . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" ), array(), array( 'SCHOOL_DATE' ) );

	// Update School Day minutes
	if ( $_REQUEST['minutes'] )
	{
		foreach( (array)$_REQUEST['minutes'] as $date => $minutes )
		{
			if ( $calendar_RET[$date] )
			{
				//if($minutes!='0' && $minutes!='')
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
			}
			//elseif($minutes!='0' && $minutes!='')
			//FJ fix bug MINUTES not numeric
			elseif ( intval( $minutes ) > 0 )
			{
				DBQuery( "INSERT INTO ATTENDANCE_CALENDAR
					(SYEAR,SCHOOL_ID,SCHOOL_DATE,CALENDAR_ID,MINUTES)
					values('" . UserSyear() . "','" . UserSchool() . "','" . $date . "','" . $_REQUEST['calendar_id'] . "','" . intval( $minutes ) . "')" );
			}
		}

		unset( $_REQUEST['minutes'] );
		unset( $_SESSION['_REQUEST_vars']['minutes'] );
	}

	// Update All day school
	if ( $_REQUEST['all_day'] )
	{
		foreach( (array)$_REQUEST['all_day'] as $date => $yes )
		{
			if ( $yes === 'Y' )
			{
				if ( $calendar_RET[$date] )
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
		}

		unset( $_REQUEST['all_day'] );
		unset( $_SESSION['_REQUEST_vars']['all_day'] );
	}

	// Update Blocks
	if ( $_REQUEST['blocks'] )
	{
		foreach( (array)$_REQUEST['blocks'] as $date => $block )
		{
			if ( $calendar_RET[$date] )
			{
				DBQuery( "UPDATE ATTENDANCE_CALENDAR
					SET BLOCK='" . $block . "'
					WHERE SCHOOL_DATE='" . $date . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" );
			}
		}

		unset( $_REQUEST['blocks'] );
		unset( $_SESSION['_REQUEST_vars']['blocks'] );
	}

	// Update Calendar RET
	if ( $_REQUEST['blocks']
		|| $_REQUEST['all_day']
		|| $_REQUEST['minutes'] )
	{
		$calendar_RET = DBGet( DBQuery( "SELECT to_char(SCHOOL_DATE,'dd-MON-YYYY') AS SCHOOL_DATE,MINUTES,BLOCK
			FROM ATTENDANCE_CALENDAR
			WHERE SCHOOL_DATE BETWEEN '" . $first_day_month . "'
			AND '" . $last_day_month . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND CALENDAR_ID='" . $_REQUEST['calendar_id'] . "'" ), array(), array( 'SCHOOL_DATE' ) );
	}


	echo '<FORM action="Modules.php?modname=' . $_REQUEST['modname'] . '" METHOD="POST">';

	// Admin Headers
	if ( AllowEdit() )
	{
		$title_RET = DBGet( DBQuery( "SELECT CALENDAR_ID,TITLE,DEFAULT_CALENDAR
			FROM ATTENDANCE_CALENDARS WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY DEFAULT_CALENDAR ASC,TITLE" ) );

		foreach ( (array)$title_RET as $title )
		{
			$options[$title['CALENDAR_ID']] = $title['TITLE'] . ( $title['DEFAULT_CALENDAR']=='Y' ? ' (' . _( 'Default' ) . ')' : '' );

			if ( $title['DEFAULT_CALENDAR'] === 'Y' )
				$defaults++;
		}

		//FJ bugfix erase calendar onchange
		$calendar_onchange = '<script>
			var calendar_onchange = document.createElement("a");
			calendar_onchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&calendar_id=";
			calendar_onchange.target = "body";
		</script>';

		$links = $calendar_onchange . SelectInput(
			$_REQUEST['calendar_id'],
			'calendar_id',
			'',
			$options,
			false,
			' onchange="calendar_onchange.href += document.getElementById(\'calendar_id\').value; ajaxLink(calendar_onchange);" ',
			false
		) .
		'<A HREF="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=create" class="nobr">' .
			button( 'add' ) . _( 'Create new calendar' ) .
		'</A> | ' .
		'<A HREF="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=create&calendar_id=' . $_REQUEST['calendar_id'] . '" class="nobr">' .
			_( 'Recreate this calendar' ) .
		'</A>&nbsp; ' .
		'<A HREF="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete_calendar&calendar_id=' . $_REQUEST['calendar_id'] . '" class="nobr">' .
			button( 'remove' ) . _( 'Delete this calendar' ) .
		'</A>';
	}

	$list_events_URL = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=list_events&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'];

	DrawHeader(
		PrepareDate( mb_strtoupper( $first_day_month ), '', false, array( 'M' => 1, 'Y' => 1, 'submit' => true ) ) .
		' <A HREF="' . $list_events_URL . '">' .
			_( 'List Events' ) .
		'</A>',
		SubmitButton( _( 'Save' ) )
	);

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

	echo '<BR />';

	// Get Events
	$events_RET = DBGet( DBQuery( "SELECT ID,to_char(SCHOOL_DATE,'dd-MON-YYYY') AS SCHOOL_DATE,TITLE,DESCRIPTION
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
		
		$assignments_SQL = "SELECT ASSIGNMENT_ID AS ID,to_char(a.DUE_DATE,'dd-MON-YYYY') AS SCHOOL_DATE,a.TITLE,'Y' AS ASSIGNED 
			FROM GRADEBOOK_ASSIGNMENTS a,SCHEDULE s 
			WHERE (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID) 
			AND s.STUDENT_ID='" . UserStudentID() . "' 
			AND (a.DUE_DATE BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL) 
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL) 
			AND a.DUE_DATE BETWEEN '" . $first_day_month . "' AND '" . $last_day_month . "'";
	}		
	elseif( User( 'PROFILE' ) === 'teacher' )
	{
		$assignments_SQL = "SELECT ASSIGNMENT_ID AS ID,to_char(a.DUE_DATE,'dd-MON-YYYY') AS SCHOOL_DATE,a.TITLE,CASE WHEN a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL THEN 'Y' ELSE NULL END AS ASSIGNED 
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
		window.open( url, "blank", "width=500,height=400" );
	}
</script>
<?php

	// Calendar Header
	echo '<table id="calendar" class="width-100p valign-top">
		<THEAD><TR class="center">';

	echo '<TH>' . _( 'Sunday' ) . '</TH>' .
		'<TH>' . _( 'Monday' ) . '</TH>' .
		'<TH>' . _( 'Tuesday' ) . '</TH>' .
		'<TH>' . _( 'Wednesday' ) . '</TH>' .
		'<TH>' . _( 'Thursday' ) . '</TH>' .
		'<TH>' . _( 'Friday' ) . '</TH>' .
		'<TH>' . _( 'Saturday' ) . '</TH>';

	echo '</TR></THEAD><TBODY><TR>';

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
		$day_time = mktime( 0, 0, 0, MonthNWSwitch( $_REQUEST['month'], 'tonum' ), $i, $_REQUEST['year'] );

		$date = mb_strtoupper( date( 'd-M-Y', $day_time ) );

		$day_classes = '';

		if ( $calendar_RET[$date][1]['MINUTES'] )
		{
			// Full School Day
			if ( $calendar_RET[$date][1]['MINUTES'] === '999' )
				$day_classes .= ' full';
			// Minutes School Day
			else
				$day_classes .= ' minutes';
		}
		// No School Day
		else
			$day_classes .= ' no-school';

		// Fridays, Saturdays
		if ( ($return_counter + 1) % 7 === 0
			|| ($return_counter + 1) % 7 === 6 )
			$day_classes .= ' fri-sat';

		$day_inner_classes = '';

		// Hover CSS class
		if ( AllowEdit()
			|| $calendar_RET[$date][1]['MINUTES']
			|| count( $events_RET[$date] )
			|| count( $assignments_RET[$date] ) )
			$day_inner_classes .= ' hover';

		echo '<TD class="calendar-day' . $day_classes . '">
			<table class="' . $day_inner_classes . '"><tr>';

		$day_number_classes = '';

		// Bold class
		if ( count( $events_RET[$date] )
			|| count( $assignments_RET[$date] ) )
			$day_number_classes .= ' bold';

		// Calendar Day number
		echo '<td class="' . $day_number_classes . '">' . $i . '</td>
		<td class="width-100p align-right">';

		if ( AllowEdit() )
		{
			// Minutes
			if ( $calendar_RET[$date][1]['MINUTES'] === '999' )
			{
				//FJ icons
				echo CheckboxInput(
					$calendar_RET[$date],
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
			elseif ( $calendar_RET[$date][1]['MINUTES'] )
			{
				echo TextInput( $calendar_RET[$date][1]['MINUTES'], "minutes[" . $date . "]", '', 'size=3' );
			}
			else
			{
				echo '<INPUT type="checkbox" name="all_day[' . $date . ']" value="Y" title="' . _( 'All Day' ) . '" />&nbsp;';

				//FJ fix bug MINUTES not numeric
				echo '<INPUT type="number" min="1" max="998" name="minutes[' . $date . ']" size="3" title="' . _( 'Minutes' ) . '" />';
			}
		}

		// Blocks
		$blocks_RET = DBGet( DBQuery( "SELECT DISTINCT BLOCK
			FROM SCHOOL_PERIODS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND BLOCK IS NOT NULL
			ORDER BY BLOCK" ) );

		if ( count( $blocks_RET ) > 0 )
		{
			unset( $options );

			foreach( (array)$blocks_RET as $block)
				$options[$block['BLOCK']] = $block['BLOCK'];

			echo SelectInput( $calendar_RET[$date][1]['BLOCK'], "blocks[" . $date . "]", '', $options );
		}

		echo '</td></tr>
		<tr><TD colspan="2" class="valign-top">';

		// Events
		foreach( (array)$events_RET[$date] as $event )
		{
			$title = ( $event['TITLE'] ? $event['TITLE'] : '***' );

			echo '<div class="calendar-event">' .
				( AllowEdit() || $event['DESCRIPTION'] ?
					'<A HREF="#" onclick="CalEventPopup(popupURL + \'&event_id=' . $event['ID'] . '\'); return false;" title="' . htmlentities( $title ) . '">' .
					$title . '</A>'
					: $title
				) .
			'</div>';
		}

		// Assignments
		foreach( (array)$assignments_RET[$date] as $assignment )
		{
			echo '<div class="calendar-event assignment' . ( $assignment['ASSIGNED'] == 'Y' ? ' assigned' : '' ) . '">' .
				'<A HREF="#" onclick="CalEventPopup(popupURL + \'&assignment_id=' . $assignment['ID'] . '\'); return false;" title="' . htmlentities( $assignment['TITLE'] ) . '">' .
					$assignment['TITLE'] .
				'</A>
			</div>';
		}

		echo '</TD></TR>';

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

		echo '</table></TD>';

		$return_counter++;

		if ( $return_counter % 7 === 0 )
			echo '</TR><TR>';
	}

	// Skip from Last Day of Month until end of Calendar
	if ( $return_counter %7 !== 0 )
	{
		$skip = 7 - $return_counter % 7;

		echo '<td colspan="' . $skip . '" class="calendar-skip">&nbsp;</td>';
	}

	echo '</TR></TBODY></TABLE>';

	echo '<BR /><span class="center">' . SubmitButton( _( 'Save' ) ) . '</span>';
	echo '<BR /><BR /></FORM>';
}


function _formatContent( $value, $column )
{
	// convert MarkDown to HTML
	return '<div class="markdown-to-html">' . $value . '</div>';
}

function _formatDescription( $value, $column )
{
	global $THIS_RET;

	$id = $THIS_RET['ID'];

	// convert MarkDown to HTML
	$return = '<div class="markdown-to-html">' . $value . '</div>';

	//FJ responsive rt td too large
	return '<div id="divEventDescription' . $id . '" class="rt2colorBox">' . $return . '</div>';
}
