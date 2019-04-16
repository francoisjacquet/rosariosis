<?php

DrawHeader( ProgramTitle() );

if ( empty( $_REQUEST['month'] ) )
{
	$_REQUEST['month'] = date( 'm' );
}

if ( empty( $_REQUEST['year'] ) )
{
	$_REQUEST['year'] = date( 'Y' );
}

$last = 31;

while ( ! checkdate( $_REQUEST['month'], $last, $_REQUEST['year'] ) )
{
	$last--;
}

$time = mktime( 0, 0, 0, $_REQUEST['month'], 1, $_REQUEST['year'] );
$time_last = mktime( 0, 0, 0, $_REQUEST['month'], $last, $_REQUEST['year'] );

// Use default calendar.
$default_calendar_id = DBGetOne( "SELECT CALENDAR_ID
	FROM ATTENDANCE_CALENDARS
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND DEFAULT_CALENDAR='Y'" );

if ( $default_calendar_id )
{
	$calendar_id = $default_calendar_id;
}
else
{
	$calendar_id = DBGetOne( "SELECT CALENDAR_ID
		FROM ATTENDANCE_CALENDARS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( ! $calendar_id )
	{
		ErrorMessage( array( _( 'There are no calendars yet setup.' ) ), 'fatal' );
	}
}

$menus_RET = DBGet( "SELECT MENU_ID,TITLE
	FROM FOOD_SERVICE_MENUS
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER", array(), array( 'MENU_ID' ) );

if ( empty( $_REQUEST['menu_id'] ) )
{
	if ( ! $_SESSION['FSA_menu_id'] )
	{
		if ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( array( _( 'There are no menus yet setup.' ) ), 'fatal' );
		}
	}
	else
	{
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
	}
}
else
{
	$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
}

if ( $_REQUEST['submit']['save']
	&& $_REQUEST['food_service']
	&& $_POST['food_service']
	&& AllowEdit() )
{
	$events_RET = DBGet( "SELECT ID,SCHOOL_DATE
	FROM CALENDAR_EVENTS
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND TITLE='" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "'", array(), array( 'SCHOOL_DATE' ) );
	//echo '<pre>'; var_dump($events_RET); echo '</pre>';

	foreach ( (array) $_REQUEST['food_service'] as $school_date => $description )
	{
		if ( $events_RET[$school_date] )
		{
			if ( $description['text'] || $description['select'] )
			{
				DBQuery( "UPDATE CALENDAR_EVENTS SET DESCRIPTION='" . $description['text'] . $description['select'] . "' WHERE ID='" . $events_RET[$school_date][1]['ID'] . "'" );
			}
			else
			{
				DBQuery( "DELETE FROM CALENDAR_EVENTS WHERE ID='" . $events_RET[$school_date][1]['ID'] . "'" );
			}
		}
		elseif ( $description['text'] || $description['select'] )
		{
			DBQuery( "INSERT INTO CALENDAR_EVENTS (ID,SYEAR,SCHOOL_ID,SCHOOL_DATE,TITLE,DESCRIPTION) values(" . db_seq_nextval( 'CALENDAR_EVENTS_SEQ' ) . ",'" . UserSyear() . "','" . UserSchool() . "','" . $school_date . "','" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "','" . $description['text'] . $description['select'] . "')" );
		}
	}

	// Unset food_service & redirect URL.
	RedirectURL( 'food_service' );
}

if ( ! empty( $_REQUEST['submit']['print'] ) )
{
	$events_RET = DBGet( "SELECT TITLE,DESCRIPTION,SCHOOL_DATE
	FROM CALENDAR_EVENTS
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND (TITLE='" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "' OR TITLE='No School')", array(), array( 'SCHOOL_DATE' ) );

	$skip = date( "w", $time );

	echo '<br /><table class="width-100p">';

	if ( ! empty( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Landscape PDF.
		$_SESSION['orientation'] = 'landscape';

		if ( is_file( 'assets/dailymenu' . UserSchool() . '.jpg' ) )
		{
			echo '<tr class="center"><td colspan="3"><img src="assets/dailymenu' . UserSchool() . '.jpg" /></td></tr>';
		}
		else
		{
			echo '<tr class="center"><td colspan="3"><b class="sizep2">' . SchoolInfo( 'TITLE' ) . '</b></td></tr>';
		}
	}

	//FJ display locale with strftime()
	echo '<tr class="center"><td>' . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . '</td>
		<td><b class="sizep2">' . ProperDate( date( 'Y-m-d', mktime( 0, 0, 0, $_REQUEST['month'], 1, $_REQUEST['year'] ) ) ) . '</b></td>
		<td>' . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . '</td></tr></table>';

	echo '<table id="calendar" class="width-100p valign-top"><thead><tr class="center">';

	echo '<th>' . _( 'Sunday' ) . '</th>' .
	'<th>' . _( 'Monday' ) . '</th>' .
	'<th>' . _( 'Tuesday' ) . '</th>' .
	'<th>' . _( 'Wednesday' ) . '</th>' .
	'<th>' . _( 'Thursday' ) . '</th>' .
	'<th>' . _( 'Friday' ) . '</th>' .
	'<th>' . _( 'Saturday' ) . '</th>';

	echo '</tr></thead><tbody>';

	if ( $skip )
	{
		echo '<tr><td colspan="' . $skip . '" class="calendar-skip">&nbsp;</td>';
	}

	for ( $i = 1; $i <= $last; $i++ )
	{
		if ( $skip % 7 == 0 )
		{
			echo '<tr>';
		}

		$day_time = mktime( 0, 0, 0, $_REQUEST['month'], $i, $_REQUEST['year'] );

		$date = date( 'Y-m-d', $day_time );

		$day_classes = '';

		// Thursdays, Fridays, Saturdays.

		if (  ( $i + 1 ) % 7 === 0
			|| ( $i + 1 ) % 7 > 4 )
		{
			$day_classes .= ' thu-fri-sat';
		}

		$day_inner_classes = 'width-100p';

		if ( ! empty( $events_RET[$date] ) )
		{
			$day_inner_classes .= ' hover';
		}

		$day_number_classes = 'number';

		// Bold class

		if ( ! empty( $events_RET[$date] )
			|| ! empty( $assignments_RET[$date] ) )
		{
			$day_number_classes .= ' bold';
		}

		echo '<td class="calendar-day' . $day_classes . '" style="background-color:' . ( ! empty( $events_RET[$date] ) ? '#ffaaaa;' : '#fff' ) . '">
			<table class="' . $day_inner_classes . '">
				<tr><td class="' . $day_number_classes . '">' . $i . '</td></tr>';

		echo '<tr><td class="calendar-menu">';

		if ( ! empty( $events_RET[$date] ) )
		{
			foreach ( (array) $events_RET[$date] as $event )
			{
				if ( $event['TITLE'] != $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] )
				{
					echo '<i>' . $event['TITLE'] . '</i><br />';
				}

				echo htmlspecialchars( $event['DESCRIPTION'], ENT_QUOTES );
			}
		}

		echo '</td></tr></table></td>';

		$skip++;

		if ( $skip % 7 == 0 )
		{
			echo '</tr>';
		}
	}

	if ( $skip % 7 != 0 )
	{
		echo '<td colspan="' . ( 7 - $skip % 7 ) . '" class="calendar-skip">&nbsp;</td></tr>';
	}

	echo '</tbody></table></p>';
}
else
{
	if ( AllowEdit() )
	{
		$description_RET = DBGet( "SELECT DISTINCT DESCRIPTION FROM CALENDAR_EVENTS WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND TITLE='" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "' AND DESCRIPTION IS NOT NULL ORDER BY DESCRIPTION" );

		if ( ! empty( $description_RET ) )
		{
			$description_select = '<option value="">' . _( 'or select previous meal' ) . '</option>';

			foreach ( (array) $description_RET as $description )
			{
				$description_select .= '<option value="' . $description['DESCRIPTION'] . '">' . $description['DESCRIPTION'] . '</option>';
			}

			$description_select .= '</select>';
		}
	}

	$calendar_RET = DBGet( "SELECT SCHOOL_DATE
	FROM ATTENDANCE_CALENDAR
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND CALENDAR_ID='" . $calendar_id . "'
	AND MINUTES>0
	ORDER BY SCHOOL_DATE", array(), array( 'SCHOOL_DATE' ) );

	$events_RET = DBGet( "SELECT ID,TITLE,DESCRIPTION,SCHOOL_DATE
	FROM CALENDAR_EVENTS
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND TITLE='" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "'
	ORDER BY SCHOOL_DATE", array( 'DESCRIPTION' => 'makeDescriptionInput', 'SCHOOL_DATE' => 'ProperDate' ) );

	$events_RET[0] = array(); // make sure indexing from 1

	foreach ( (array) $calendar_RET as $school_date => $value )
	{
		$events_RET[] = array(
			'ID' => '',
			'SCHOOL_DATE' => ProperDate( $school_date ),
			'DESCRIPTION' => TextInput( '', 'food_service[' . $school_date . '][text]', '', 'size=20' ) .
			( $description_select ? '<select name="food_service[' . $school_date . '][select]">' .
				$description_select : '' ),
		);
	}

	unset( $events_RET[0] );

	$LO_columns = array( 'ID' => _( 'ID' ), 'SCHOOL_DATE' => _( 'Date' ), 'DESCRIPTION' => _( 'Description' ) );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] . '" method="POST">';

	DrawHeader(
		PrepareDate(
			mb_strtoupper( date( "d-M-y", $time ) ),
			'',
			false,
			array(
				'M' => 1,
				'Y' => 1,
				'submit' => true,
			)
		),
		SubmitButton( _( 'Save' ), 'submit[save]' ) .
		SubmitButton( _( 'Generate Menu' ), 'submit[print]', '' ) // No .primary button class.
	);

	echo '<br />';

	$tabs = array();

	foreach ( (array) $menus_RET as $id => $meal )
	{
		$tabs[] = array(
			'title' => $meal[1]['TITLE'],
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&menu_id=' . $id . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'],
		);
	}

	$extra = array( 'save' => false, 'search' => false,
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] ) );
	$singular = sprintf( _( '%s Day' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );
	$plural = sprintf( _( '%s Days' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );

//FJ add translation
	ListOutput( $events_RET, $LO_columns, $singular, $plural, array(), array(), $extra );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ), 'submit[save]' ) . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function makeDescriptionInput( $value, $name )
{
	global $THIS_RET, $calendar_RET;

	if ( $calendar_RET[$THIS_RET['SCHOOL_DATE']] )
	{
		unset( $calendar_RET[$THIS_RET{
			'SCHOOL_DATE'}
		] );
	}

	return TextInput( $value, 'food_service[' . $THIS_RET['SCHOOL_DATE'] . '][text]', '', 'size=20' );
}
