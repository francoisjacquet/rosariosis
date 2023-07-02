<?php

$date = RequestedDate( 'date', DBDate(), 'set' );

DrawHeader( ProgramTitle() );

$course_RET = DBGet( "SELECT DOES_FS_COUNTS,DAYS,CALENDAR_ID,MP,MARKING_PERIOD_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );
//echo '<pre>'; var_dump($course_RET); echo '</pre>';

if ( ! trim( $course_RET[1]['DOES_FS_COUNTS'], ',' ) )
{
	ErrorMessage( [ _( 'You cannot take meal counts for this period.' ) ], 'fatal' );
}

// the following query is for when doea_fs_counts is a comma quoted string of meal_id's, ex. ,1,2,4,
//$menus_RET = DBGet( 'SELECT MENU_ID,TITLE FROM food_service_menus WHERE SCHOOL_ID=\''.UserSchool().'\' AND MENU_ID IN ('.trim($course_RET[1]['DOES_FS_COUNTS'],',').') ORDER BY SORT_ORDER IS NULL,SORT_ORDER'),array(),array('MENU_ID'));
// use all meal_id's for now
$menus_RET = DBGet( 'SELECT MENU_ID,TITLE FROM food_service_menus WHERE SCHOOL_ID=\'' . UserSchool() . '\' ORDER BY SORT_ORDER IS NULL,SORT_ORDER', [], [ 'MENU_ID' ] );
//echo '<pre>'; var_dump($menus_RET); echo '</pre>';

if ( empty( $_REQUEST['menu_id'] ) )
{
	if ( ! $_SESSION['FSA_menu_id'] || ! $menus_RET[$_SESSION['FSA_menu_id']] )
	{
		if ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( [ _( 'You cannot take meal counts for this period.' ) ], 'fatal' );
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

if ( $course_RET[1]['CALENDAR_ID'] )
{
	$calendar_id = $course_RET[1]['CALENDAR_ID'];
}
else
{
	$calendar_id = DBGet( "SELECT CALENDAR_ID FROM attendance_calendars WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND DEFAULT_CALENDAR='Y'" );
	$calendar_id = $calendar_id['CALENDAR_ID'];
}

$calendar_RET = DBGet( "SELECT MINUTES FROM attendance_calendar WHERE CALENDAR_ID='" . (int) $calendar_id . "' AND SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND SCHOOL_DATE='" . $date . "'" );
//echo '<pre>'; var_dump($calendar_RET); echo '</pre>';

if ( ! $calendar_RET[1]['MINUTES'] )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id']  ) . '" method="POST">';
	DrawHeader( PrepareDate( $date, '_date', false, [ 'submit' => true ] ) );
	echo '</form>';
	ErrorMessage( [ _( 'The selected date is not a school day!' ) ], 'fatal' );
}

if ( GetCurrentMP( $course_RET[1]['MP'], $date ) != $course_RET[1]['MARKING_PERIOD_ID'] )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id']  ) . '" method="POST">';
	DrawHeader( PrepareDate( $date, '_date', false, [ 'submit' => true ] ) );
	echo '</form>';
	ErrorMessage( [ _( 'This period does not meet in the marking period of the selected date.' ) ], 'fatal' );
}

$qtr_id = GetCurrentMP( 'QTR', $date );

$days = $course_RET[1]['DAYS'];
$day = date( 'D', strtotime( $date ) );

switch ( $day )
{
	case 'Sun':
		$day = 'U';
		break;
	case 'Thu':
		$day = 'H';
		break;
	default:
		$day = mb_substr( $day, 0, 1 );
		break;
}

if ( mb_strpos( $days, $day ) === false )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&table=' . $_REQUEST['table']  ) . '" method="POST">';
	DrawHeader( PrepareDate( $date, '_date', false, [ 'submit' => true ] ) );
	echo '</form>';
	ErrorMessage( [ _( 'This period does not meet on the selected date.' ) ], 'fatal' );
}

// if running as a teacher program then rosario[allow_edit] will already be set according to admin permissions

if ( ! isset( $_ROSARIO['allow_edit'] ) )
{
	$time = strtotime( DBDate() );

	if ( GetMP( $qtr_id, 'POST_START_DATE' )
		&& ( DBDate() <= GetMP( $qtr_id, 'POST_END_DATE' ) ) )
	{
		$_ROSARIO['allow_edit'] = true;
	}
}

$current_RET = DBGet( "SELECT ITEM_ID
	FROM FOOD_SERVICE_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND SCHOOL_DATE='" . $date . "'
	AND PERIOD_ID='" . UserPeriod() . "'
	AND MENU_ID='" . (int) $_REQUEST['menu_id'] . "'", [], [ 'ITEM_ID' ] );

//echo '<pre>'; var_dump($current_RET); echo '</pre>';

if ( $_REQUEST['values']
	&& $_POST['values'] )
{
	GetCurrentMP( 'QTR', $date );

	foreach ( (array) $_REQUEST['values'] as $id => $value )
	{
		if ( $current_RET[$id] )
		{
			$sql = 'UPDATE FOOD_SERVICE_COMPLETED SET ';
			$sql .= 'COUNT=\'' . $value['COUNT'] . '\' ';
			$sql .= 'WHERE STAFF_ID=\'' . User( 'STAFF_ID' ) . '\' AND SCHOOL_DATE=\'' . $date . '\' AND PERIOD_ID=\'' . UserPeriod() . '\' AND MENU_ID=\'' . $_REQUEST['menu_id'] . '\' AND ITEM_ID=\'' . $id . '\'';
		}
		else
		{
			$fields = 'STAFF_ID,SCHOOL_DATE,PERIOD_ID,MENU_ID,ITEM_ID,COUNT';
			$values = '\'' . User( 'STAFF_ID' ) . '\',\'' . $date . '\',\'' . UserPeriod() . '\',\'' . $_REQUEST['menu_id'] . '\',\'' . $id . '\',\'' . $value['COUNT'] . '\'';
			$sql = 'INSERT INTO FOOD_SERVICE_COMPLETED (' . $fields . ') values (' . $values . ')';
		}

		DBQuery( $sql );
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $date != DBDate() )
{
	$date_note = ' <span style="color:red">' . _( 'The selected date is not today' ) . '</span>';
}

$completed = DBGet( "SELECT count('Y') AS COMPLETED
	FROM FOOD_SERVICE_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND SCHOOL_DATE='" . $date . "'
	AND PERIOD_ID='" . UserPeriod() . "'
	AND MENU_ID='" . (int) $_REQUEST['menu_id'] . "'" );

if ( $completed[1]['COMPLETED'] )
{
	$note[] = button( 'check' ) . _( 'You have taken lunch counts today for this period.' );
}

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';
DrawHeader( PrepareDate( $date, '_date', false, [ 'submit' => true ] ) . $date_note, SubmitButton() );

echo ErrorMessage( $note, 'note' );

$meal_description = DBGetOne( "SELECT DESCRIPTION
	FROM calendar_events
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND SCHOOL_DATE='" . $date . "'
	AND TITLE='" . DBEscapeString( $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] ) . "'" );

if ( $meal_description )
{
	echo '<table class="width-100p">';
	echo '<tr><td class="center">';
	echo '<b>Today\'s ' . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . ':</b> ' . $meal_description;
	echo '</td></tr></table><hr>';
}

$items_RET = DBGet( 'SELECT fsi.ITEM_ID,fsi.DESCRIPTION,fsmi.DOES_COUNT,(SELECT COUNT FROM FOOD_SERVICE_COMPLETED WHERE STAFF_ID=\'' . User( 'STAFF_ID' ) . '\' AND SCHOOL_DATE=\'' . $date . '\' AND PERIOD_ID=\'' . UserPeriod() . '\' AND ITEM_ID=fsi.ITEM_ID AND MENU_ID=fsmi.MENU_ID) AS COUNT FROM food_service_items fsi,food_service_menu_items fsmi WHERE fsmi.MENU_ID=\'' . $_REQUEST['menu_id'] . '\' AND fsi.ITEM_ID=fsmi.ITEM_ID AND fsmi.DOES_COUNT IS NOT NULL ORDER BY fsmi.SORT_ORDER IS NULL,fsmi.SORT_ORDER', [ 'COUNT' => 'makeTextInput' ] );

echo '<table class="width-100p"><tr><td style="width:50%;">';
$LO_columns = [ 'DESCRIPTION' => _( 'Item' ), 'COUNT' => _( 'Count' ) ];

if ( count( (array) $menus_RET ) > 1 )
{
	$tabs = [];

	foreach ( (array) $menus_RET as $id => $meal )
	{
		$tabs[] = [ 'title' => $meal[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id . '&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] . '&year_date=' . $_REQUEST['year_date'] ];
	}

	echo '<br />';
	echo '<div class="center">' . WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] . '&day_date=' . $_REQUEST['day_date'] . '&month_date=' . $_REQUEST['month_date'] . '&year_date=' . $_REQUEST['year_date'] ) . '</div>';
	$extra = [ 'count' => false, 'download' => false, 'search' => false ];
}
else
{
	$extra = [ 'search' => false ];
	$plural = $menus_RET[1][1]['TITLE'] . ' ' . _( 'Items' );
	$singular = $menus_RET[1][1]['TITLE'] . ' ' . _( 'Item' );
}

ListOutput( $items_RET, $LO_columns, $singular, $plural, false, false, $extra );

echo '<div class="center">' . SubmitButton() . '</div>';
echo '</td><td style="width:50%;">';

$extra['SELECT'] .= ',fsa.BALANCE,fssa.STATUS';
$extra['FROM'] .= ',food_service_accounts fsa,food_service_student_accounts fssa';
$extra['WHERE'] .= ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NOT NULL';

if ( ! $extra['functions'] )
{
	$extra['functions'] = [];
}

$extra['functions'] += [ 'BALANCE' => 'red' ];

$stu_RET = GetStuList( $extra );

$LO_columns = [ 'FULL_NAME' => _( 'Student' ), 'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ), 'GRADE_ID' => _( 'Grade Level' ), 'BALANCE' => _( 'Balance' ), 'STATUS' => _( 'Status' ) ];
ListOutput( $stu_RET, $LO_columns, 'Ineligible Student', 'Ineligible Students', false, false, [ 'save' => false, 'search' => false ] );
echo '</td></tr></table>';
echo '</form>';

/**
 * @param $value
 * @return mixed
 */
function red( $value )
{
	if ( $value < 0 )
	{
		return '<span style="color:red">' . $value . '</span>';
	}
	else
	{
		return $value;
	}
}

/**
 * @param $value
 * @param $name
 */
function makeTextInput( $value, $name )
{
	global $THIS_RET;

	$extra = 'size=6 maxlength=8';

	return TextInput( $value, 'values[' . $THIS_RET['ITEM_ID'] . '][' . $name . ']', '', $extra );
}
