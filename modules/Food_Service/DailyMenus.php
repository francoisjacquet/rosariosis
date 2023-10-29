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
	FROM attendance_calendars
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
		FROM attendance_calendars
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( ! $calendar_id )
	{
		ErrorMessage( [ _( 'There are no calendars yet setup.' ) ], 'fatal' );
	}
}

$menus_RET = DBGet( "SELECT MENU_ID,TITLE
	FROM food_service_menus
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'MENU_ID' ] );

if ( empty( $_REQUEST['menu_id'] ) )
{
	if ( empty( $_SESSION['FSA_menu_id'] ) )
	{
		if ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( [ _( 'There are no menus yet setup.' ) ], 'fatal' );
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

$menu_title = $menus_RET[$_REQUEST['menu_id']][1]['TITLE'];

if ( ! empty( $_REQUEST['submit']['save'] )
	&& $_REQUEST['food_service']
	&& $_POST['food_service']
	&& AllowEdit() )
{
	$events_RET = DBGet( "SELECT ID,SCHOOL_DATE
	FROM calendar_events
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND TITLE='" . DBEscapeString( $menu_title ) . "'", [], [ 'SCHOOL_DATE' ] );
	//echo '<pre>'; var_dump($events_RET); echo '</pre>';

	foreach ( (array) $_REQUEST['food_service'] as $school_date => $description )
	{
		if ( ! empty( $events_RET[$school_date] ) )
		{
			if ( ! empty( $description['text'] ) || ! empty( $description['select'] ) )
			{
				DBUpdate(
					'calendar_events',
					[ 'DESCRIPTION' => $description['text'] . issetVal( $description['select'], '' ) ],
					[ 'ID' => (int) $events_RET[$school_date][1]['ID'] ]
				);
			}
			else
			{
				DBQuery( "DELETE FROM calendar_events
					WHERE ID='" . (int) $events_RET[$school_date][1]['ID'] . "'" );
			}
		}
		elseif ( ! empty( $description['text'] ) || ! empty( $description['select'] ) )
		{
			DBInsert(
				'calendar_events',
				[
					'SYEAR' => UserSyear(),
					'SCHOOL_ID' => UserSchool(),
					'SCHOOL_DATE' => $school_date,
					'TITLE' => DBEscapeString( $menu_title ),
					'DESCRIPTION' => $description['text'] . issetVal( $description['select'], '' ),
				]
			);
		}
	}

	// Unset food_service & redirect URL.
	RedirectURL( 'food_service' );
}

if ( ! empty( $_REQUEST['submit']['print'] ) )
{
	$events_RET = DBGet( "SELECT TITLE,DESCRIPTION,SCHOOL_DATE
	FROM calendar_events
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND (TITLE='" . DBEscapeString( $menu_title ) . "' OR TITLE='No School')", [], [ 'SCHOOL_DATE' ] );

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
			echo '<tr class="center"><td colspan="3"><h3>' . SchoolInfo( 'TITLE' ) . '</h3></td></tr>';
		}
	}

	// Remove dummy day from proper date.
	$proper_month_year = ucfirst( strftime_compat(
		trim( str_replace( [ '%d', '//' ], [ '', '/'], Preferences( 'DATE' ) ), '-./ ' ),
		strtotime( $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-28' )
	) );

	echo '<tr class="center"><td>' . $menu_title . '</td>
		<td><h3>' . $proper_month_year . '</h3></td>
		<td>' . $menu_title . '</td></tr></table>';

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

		if ( ! empty( $events_RET[$date] ) )
		{
			$day_classes .= ' full';
		}
		else
		{
			$day_classes .= ' no-school';
		}

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

		// Bold class.
		if ( ! empty( $events_RET[$date] ) )
		{
			$day_number_classes .= ' bold';
		}

		echo '<td class="calendar-day' . AttrEscape( $day_classes ) . '">
			<table class="' . AttrEscape( $day_inner_classes ) . '">
				<tr><td class="' . AttrEscape( $day_number_classes ) . '">' . $i . '</td></tr>';

		echo '<tr><td class="calendar-menu">';

		if ( ! empty( $events_RET[$date] ) )
		{
			foreach ( (array) $events_RET[$date] as $event )
			{
				if ( $event['TITLE'] != $menu_title )
				{
					echo '<i>' . $event['TITLE'] . '</i><br />';
				}

				// Allow MarkDown (& HTML) in Description
				echo makeTextarea( $event['DESCRIPTION'], 'DESCRIPTION' );
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

	// Fix description overflow hidden.
	echo '</tbody></table><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />';
}
else
{
	$description_select = '';

	if ( AllowEdit() )
	{
		$description_RET = DBGet( "SELECT DISTINCT DESCRIPTION
			FROM calendar_events
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND TITLE='" . DBEscapeString( $menu_title ) . "'
			AND DESCRIPTION IS NOT NULL
			ORDER BY DESCRIPTION" );

		if ( ! empty( $description_RET ) )
		{
			$description_select .= '<option value="">' . _( 'or select previous meal' ) . '</option>';

			foreach ( (array) $description_RET as $description )
			{
				$description_select .= '<option value="' . AttrEscape( $description['DESCRIPTION'] ) . '">' . $description['DESCRIPTION'] . '</option>';
			}

			$description_select .= '</select>';
		}
	}

	$calendar_RET = DBGet( "SELECT SCHOOL_DATE
	FROM attendance_calendar
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND CALENDAR_ID='" . (int) $calendar_id . "'
	AND MINUTES>0
	ORDER BY SCHOOL_DATE", [], [ 'SCHOOL_DATE' ] );

	$events_RET = DBGet( "SELECT ID,TITLE,DESCRIPTION,SCHOOL_DATE
	FROM calendar_events
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time ) . "' AND '" . date( 'Y-m-d', $time_last ) . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND TITLE='" . DBEscapeString( $menu_title ) . "'
	ORDER BY SCHOOL_DATE", [ 'DESCRIPTION' => 'makeDescriptionInput', 'SCHOOL_DATE' => 'ProperDate' ] );

	if ( AllowEdit() )
	{
		$events_RET[0] = []; // make sure indexing from 1

		foreach ( (array) $calendar_RET as $school_date => $value )
		{
			$events_RET[] = [
				'ID' => '',
				'SCHOOL_DATE' => ProperDate( $school_date ),
				'DESCRIPTION' => TextInput( '', 'food_service[' . $school_date . '][text]', '', 'size=20' ) .
				( $description_select ?
					'<select name="' . AttrEscape( 'food_service[' . $school_date . '][select]' ) . '" style="width: 217px">' .
					$description_select :
					'' ),
			];
		}

		unset( $events_RET[0] );
	}

	$LO_columns = [ 'ID' => _( 'ID' ), 'SCHOOL_DATE' => _( 'Date' ), 'DESCRIPTION' => _( 'Description' ) ];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] .
		'&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year']  ) . '" method="POST">';

	DrawHeader(
		PrepareDate(
			mb_strtoupper( date( "d-M-y", $time ) ),
			'',
			false,
			[
				'M' => 1,
				'Y' => 1,
				'submit' => true,
			]
		),
		SubmitButton( _( 'Save' ), 'submit[save]' ) .
		// No .primary button class.
		// @since 11.3 Allow non admin users & students to Generate Menu (no AllowEdit() required)
		'<input type="submit" value="' .
			AttrEscape( _( 'Generate Menu' ) ) . '" name="' . AttrEscape( 'submit[print]' ) . '" />'
	);

	echo '<br />';

	$tabs = [];

	foreach ( (array) $menus_RET as $id => $meal )
	{
		$tabs[] = [
			'title' => $meal[1]['TITLE'],
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&menu_id=' . $id . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'],
		];
	}

	$extra = [
		'save' => false,
		'search' => false,
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&menu_id=' . $_REQUEST['menu_id'] . '&month=' . $_REQUEST['month'] . '&year=' . $_REQUEST['year'] ),
	];

	$singular = sprintf( _( '%s Day' ), $menu_title );
	$plural = sprintf( _( '%s Days' ), $menu_title );

	ListOutput( $events_RET, $LO_columns, $singular, $plural, [], [], $extra );

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
		unset( $calendar_RET[$THIS_RET['SCHOOL_DATE']] );
	}

	return TextInput( $value, 'food_service[' . $THIS_RET['SCHOOL_DATE'] . '][text]', '', 'size=20' );
}
