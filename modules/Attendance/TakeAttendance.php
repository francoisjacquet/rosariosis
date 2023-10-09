<?php
//FJ move Attendance.php from functions/ to modules/Attendance/includes
require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';
require_once 'ProgramFunctions/SchoolPeriodsSelectInput.fnc.php';

if ( ! empty( $_REQUEST['period'] ) )
{
	// @since 10.9 Set current User Course Period before Secondary Teacher logic.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

DrawHeader( ProgramTitle() );

// Set date.
$date = RequestedDate( 'date', DBDate(), 'set' );

// Fix PostgreSQL error invalid ORDER BY, only result column names can be used
// Do not use ORDER BY SORT_ORDER IS NULL,SORT_ORDER (nulls last) in UNION.
// Fix MySQL 5.6 syntax error when WHERE without FROM clause, use dual table
$categories_RET = DBGet( "SELECT '0' AS ID,'" . DBEscapeString( _( 'Attendance' ) ) . "' AS TITLE,0,NULL AS SORT_ORDER
	FROM dual
	WHERE position(',0,' IN
		(SELECT DOES_ATTENDANCE
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
	)>0
	UNION SELECT ID,TITLE,1,SORT_ORDER
	FROM attendance_code_categories
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND position(CONCAT(',', ID, ',') IN
		(SELECT DOES_ATTENDANCE
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
	)>0
	ORDER BY 3,SORT_ORDER,TITLE" );

$cp_title = DBGetOne( "SELECT TITLE
	FROM course_periods
	WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

if ( empty( $categories_RET ) )
{
	if ( $cp_title )
	{
		// Add Course Period title header.
		DrawHeader( $cp_title );
	}

	ErrorMessage( [ _( 'You cannot take attendance for this course period.' ) ], 'fatal' );
}

if ( ! isset( $_REQUEST['table'] )
	|| $_REQUEST['table'] == '' )
{
	$_REQUEST['table'] = $categories_RET[1]['ID'];
}

if ( $_REQUEST['table'] == '0' )
{
	$table = 'attendance_period';
}
else
{
	$table = 'lunch_period';
}

$school_periods_select = SchoolPeriodsSelectInput(
	issetVal( $_REQUEST['school_period'] ),
	'school_period',
	'',
	'autocomplete="off" onchange="' . AttrEscape( 'ajaxLink(' . json_encode( PreparePHP_SELF( [], [ 'school_period' ] ) ) . ' + "&school_period=" + this.value);' ) . '"'
);

if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
{
	// FJ days numbered.
	// FJ multiple school periods for a course period.
	$course_RET = DBGet( "SELECT 1
	FROM attendance_calendar acc,course_periods cp,school_periods sp,course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND acc.SYEAR='" . UserSyear() . "'
	AND cp.SCHOOL_ID=acc.SCHOOL_ID
	AND cp.SYEAR=acc.SYEAR
	AND acc.SCHOOL_DATE='" . $date . "'
	AND cp.CALENDAR_ID=acc.CALENDAR_ID
	AND cpsp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
	AND cpsp.PERIOD_ID='" . (int) $_REQUEST['school_period'] . "'
	AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
	AND SCHOOL_ID=acc.SCHOOL_ID
	AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
	AND sp.PERIOD_ID=cpsp.PERIOD_ID
	AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
		(SELECT CASE COUNT(SCHOOL_DATE)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
		FROM attendance_calendar
		WHERE SCHOOL_DATE<=acc.SCHOOL_DATE
		AND SCHOOL_DATE>=(SELECT START_DATE
			FROM school_marking_periods
			WHERE START_DATE<=acc.SCHOOL_DATE
			AND END_DATE>=acc.SCHOOL_DATE
			AND MP='QTR'
			AND SCHOOL_ID=acc.SCHOOL_ID
			AND SYEAR=acc.SYEAR)
		AND CALENDAR_ID=cp.CALENDAR_ID)
	" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
	" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))
	AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0" );
}
else
{
	// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
	$course_RET = DBGet( "SELECT 1
	FROM attendance_calendar acc,course_periods cp,school_periods sp, course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND acc.SYEAR='" . UserSyear() . "'
	AND cp.SCHOOL_ID=acc.SCHOOL_ID
	AND cp.SYEAR=acc.SYEAR
	AND acc.SCHOOL_DATE='" . $date . "'
	AND cp.CALENDAR_ID=acc.CALENDAR_ID
	AND cpsp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
	AND cpsp.PERIOD_ID='" . (int) $_REQUEST['school_period'] . "'
	AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
	AND sp.PERIOD_ID=cpsp.PERIOD_ID
	AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM " .
	( $DatabaseType === 'mysql' ?
		"DAYOFWEEK(acc.SCHOOL_DATE)" :
		"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
	" FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
	AND position('," . $_REQUEST['table'] . ",' IN cp.DOES_ATTENDANCE)>0" );
}

// Instead of displaying a fatal error which could confuse user, display a warning and exit.
$fatal_warning = [];

if ( empty( $course_RET ) )
{
	$fatal_warning[] = _( 'You cannot take attendance for this period on this day.' );
}

$qtr_id = GetCurrentMP( 'QTR', $date, false );

if ( ! $qtr_id )
{
	$fatal_warning[] = _( 'The selected date is not in a school quarter.' );
}

if ( $fatal_warning )
{
	if ( $cp_title )
	{
		// Add Course Period title header.
		DrawHeader( $cp_title );
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&table=' . $_REQUEST['table']  ) . '" method="POST">';

	DrawHeader( $school_periods_select );

	DrawHeader(
		PrepareDate(
			$date,
			'_date',
			false,
			[ 'submit' => true ]
		)
	);

	echo '</form>';

	echo ErrorMessage( $fatal_warning, 'warning' );

	// Use return instead of exit. Allows Warehouse( 'footer' ) to run.
	return;
}

// If running as a teacher program then rosario[allow_edit] will already be set according to admin permissions.

if ( ! isset( $_ROSARIO['allow_edit'] ) )
{
	// Allow teacher edit if selected date is in the current quarter or in the corresponding grade posting period.
	$current_qtr_id = GetCurrentMP( 'QTR', DBDate(), false );

	$time = strtotime( DBDate() );

	if (  ( $current_qtr_id
		&& $qtr_id == $current_qtr_id
		|| GetMP( $qtr_id, 'POST_START_DATE' )
		&& DBDate() <= GetMP( $qtr_id, 'POST_END_DATE' ) )
		&& ( ! ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_BEFORE' )
			|| strtotime( $date ) <= $time + ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_BEFORE' ) * 86400 )
		&& ( ! ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_AFTER' )
			|| strtotime( $date ) >= $time - ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_AFTER' ) * 86400 ) )
	{
		$_ROSARIO['allow_edit'] = true;
	}
}

$current_Q = "SELECT ATTENDANCE_TEACHER_CODE,ATTENDANCE_CODE,STUDENT_ID,ADMIN,COMMENT,COURSE_PERIOD_ID,ATTENDANCE_REASON,
	(SELECT COMMENT
		FROM attendance_day
		WHERE STUDENT_ID=t.STUDENT_ID
		AND SCHOOL_DATE='" . $date . "') AS DAILY_COMMENT
	FROM " . DBEscapeIdentifier( $table ) . " t
	WHERE SCHOOL_DATE='" . $date . "'
	AND PERIOD_ID='" . (int) $_REQUEST['school_period'] . "'" .
	( $table == 'lunch_period' ? " AND TABLE_NAME='" . (int) $_REQUEST['table'] . "'" : '' );

$current_RET = DBGet( $current_Q, [], [ 'STUDENT_ID' ] );

if ( ! empty( $_REQUEST['attendance'] )
	&& ! empty( $_POST['attendance'] ) )
{
	foreach ( (array) $_REQUEST['attendance'] as $student_id => $value )
	{
		$student_attendance_code = (int) mb_substr( $value, 5 );

		if ( ! empty( $current_RET[$student_id] ) )
		{
			$columns = [
				'ATTENDANCE_TEACHER_CODE' => $student_attendance_code,
				'COURSE_PERIOD_ID' => UserCoursePeriod(),
			];

			if ( $current_RET[$student_id][1]['ADMIN'] != 'Y'
				// SQL Update ATTENDANCE_CODE (admin) when is NULL.
				|| empty( $current_RET[$student_id][1]['ATTENDANCE_CODE'] ) )
			{
				$columns += [ 'ATTENDANCE_CODE' => $student_attendance_code ];
			}

			if ( isset( $_REQUEST['comment'][$student_id] ) )
			{
				$columns += [ 'COMMENT' => trim( $_REQUEST['comment'][$student_id] ) ];
			}

			DBUpdate(
				$table,
				$columns,
				[
					'STUDENT_ID' => (int) $student_id,
					'SCHOOL_DATE' => $date,
					'PERIOD_ID' => (int) $_REQUEST['school_period'],
				]
			);
		}
		else
		{
			$columns = [
				'STUDENT_ID' => (int) $student_id,
				'SCHOOL_DATE' => $date,
				'PERIOD_ID' => (int) $_REQUEST['school_period'],
				'MARKING_PERIOD_ID' => (int) $qtr_id,
				'COURSE_PERIOD_ID' => UserCoursePeriod(),
				'ATTENDANCE_CODE' => $student_attendance_code,
				'ATTENDANCE_TEACHER_CODE' => $student_attendance_code,
				'COMMENT' => trim( $_REQUEST['comment'][$student_id] ),
			];

			if ( $table === 'lunch_period' )
			{
				$columns += [ 'TABLE_NAME' => (int) $_REQUEST['table'] ];
			}

			DBInsert( $table, $columns );
		}

		if ( $_REQUEST['table'] == '0' )
		{
			UpdateAttendanceDaily( $student_id, $date );
		}
	}

	$completed_RET = DBGet( "SELECT 'Y' AS COMPLETED
		FROM attendance_completed
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND SCHOOL_DATE='" . $date . "'
		AND PERIOD_ID='" . (int) $_REQUEST['school_period'] . "'
		AND TABLE_NAME='" . (int) $_REQUEST['table'] . "'" );

	if ( empty( $completed_RET ) )
	{
		DBInsert(
			'attendance_completed',
			[
				'STAFF_ID' => User( 'STAFF_ID' ),
				'SCHOOL_DATE' => $date,
				'PERIOD_ID' => (int) $_REQUEST['school_period'],
				'TABLE_NAME' => (int) $_REQUEST['table'],
			]
		);

		// Hook.
		do_action( 'Attendance/TakeAttendance.php|insert_attendance' );
	}
	else
	{
		// Hook.
		do_action( 'Attendance/TakeAttendance.php|update_attendance' );
	}

	$current_RET = DBGet( $current_Q, [], [ 'STUDENT_ID' ] );

	// Unset attendance & redirect URL.
	RedirectURL( 'attendance' );
}

$codes_RET = DBGet( "SELECT ID,TITLE,DEFAULT_CODE,STATE_CODE
	FROM attendance_codes
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	AND TYPE='teacher'
	AND TABLE_NAME='" . (int) $_REQUEST['table'] . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

$columns = [];

$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

foreach ( (array) $codes_RET as $code )
{
	$extra['SELECT'] .= ",'" . $code['STATE_CODE'] . "' AS CODE_" . $code['ID'];

	if ( $code['DEFAULT_CODE'] == 'Y' )
	{
		$extra['functions']['CODE_' . $code['ID']] = '_makeRadioSelected';
	}
	else
	{
		$extra['functions']['CODE_' . $code['ID']] = '_makeRadio';
	}

	$columns['CODE_' . $code['ID']] = $code['TITLE'];
}

$extra['SELECT'] .= ',s.STUDENT_ID AS COMMENT,s.STUDENT_ID AS ATTENDANCE_REASON,s.STUDENT_ID AS DAILY_COMMENT';

$columns += [
	'COMMENT' => _( 'Teacher Comment' ),
];

if ( ! isset( $extra['functions'] )
	|| ! is_array( $extra['functions'] ) )
{
	$extra['functions'] = [];
}

$extra['functions'] += [
	'FULL_NAME' => 'makePhotoTipMessage',
	'COMMENT' => 'makeCommentInput',
	'ATTENDANCE_REASON' => 'makeAttendanceReason',
	// @since 3.9.1 Add Daily Comment column.
	'DAILY_COMMENT' => 'makeDailyComment',
];

$extra['DATE'] = $date;

$stu_RET = GetStuList( $extra );

if ( ! empty( $attendance_reason ) )
{
	$columns += [
		'ATTENDANCE_REASON' => _( 'Office Comment' ),
	];
}

// @since 3.9.1 Add Daily Comment column.

if ( ! empty( $daily_comment ) )
{
	$columns += [
		'DAILY_COMMENT' => _( 'Day Comment' ),
	];
}

DrawHeader( $cp_title );

/**
 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
 * Then teacher submits the form, data would be saved for CP B...
 *
 * Must be used in combination with
 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
 */
echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&table=' . $_REQUEST['table'] . '&period=' . UserCoursePeriod() ) . '" method="POST">';

DrawHeader( $school_periods_select, SubmitButton() );

$date_note = $date != DBDate() ? ' <span style="color:red" class="nobr">' .
_( 'The selected date is not today' ) . '</span> |' : '';

$date_note .= AllowEdit() ? ' <span style="color:green" class="nobr">' .
_( 'You can edit this attendance' ) . '</span>' :
' <span style="color:red" class="nobr">' . _( 'You cannot edit this attendance' ) . '</span>';

$completed_RET = DBGet( "SELECT 'Y' AS COMPLETED
	FROM attendance_completed
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND SCHOOL_DATE='" . $date . "'
	AND PERIOD_ID='" . (int) $_REQUEST['school_period'] . "'
	AND TABLE_NAME='" . (int) $_REQUEST['table'] . "'" );

if ( $completed_RET )
{
	$note[] = button( 'check' ) . '&nbsp;' .
	_( 'You already have taken attendance today for this period.' );
}

DrawHeader( PrepareDate( $date, '_date', false, [ 'submit' => true ] ) . $date_note );

// Hook.
do_action( 'Attendance/TakeAttendance.php|header' );

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

$LO_columns = [
	'FULL_NAME' => _( 'Student' ),
	'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
	'GRADE_ID' => _( 'Grade Level' ),
] + $columns;

foreach ( (array) $categories_RET as $category )
{
	$tabs[] = [
		'title' => ParseMLField( $category['TITLE'] ),
		'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&table=' . $category['ID'] .
		'&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'] .
		'&year_date=' . $_REQUEST['year_date'],
	];
}

if ( ! empty( $categories_RET ) )
{
	$LO_options = [
		'download' => false,
		'search' => false,
		'header' => WrapTabs(
			$tabs,
			'Modules.php?modname=' . $_REQUEST['modname'] . '&table=' . $_REQUEST['table'] .
			'&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'] .
			'&year_date=' . $_REQUEST['year_date']
		),
	];
}
else
{
	$LO_options = [];
}

echo '<br />';

ListOutput(
	$stu_RET,
	$LO_columns,
	'Student',
	'Students',
	false,
	[],
	$LO_options
);

echo '<br /><div class="center">' . SubmitButton() . '</div>';
echo '</form>';

/**
 * @param $value
 * @param $title
 */
function _makeRadio( $value, $title )
{
	global $THIS_RET,
		$current_RET;

	$classes = [
		'P' => 'present',
		'A' => 'absent',
		'H' => 'half-day',
		// 'T' => '#0000FF',
	];

	if ( isset( $current_RET[$THIS_RET['STUDENT_ID']][1]['ATTENDANCE_TEACHER_CODE'] )
		&& $current_RET[$THIS_RET['STUDENT_ID']][1]['ATTENDANCE_TEACHER_CODE'] == mb_substr( $title, 5 ) )
	{
		if ( isset( $_REQUEST['LO_save'] ) )
		{
			return _( 'Yes' );
		}
		else
		{
			$class = issetVal( $classes[$value], '' );

			return '<div class="attendance-code ' . $class . '">
				<input type="radio" name="attendance[' . $THIS_RET['STUDENT_ID'] . ']"
					value="' . AttrEscape( $title ) . '" checked /></div>';
		}
	}
	else
	{
		return '<div class="attendance-code">
			<input type="radio" name="attendance[' . $THIS_RET['STUDENT_ID'] . ']"
				value="' . AttrEscape( $title ) . '"' . ( AllowEdit() ? '' : ' disabled' ) . '></div>';
	}
}

/**
 * @param $value
 * @param $title
 */
function _makeRadioSelected( $value, $title )
{
	global $THIS_RET,
		$current_RET;

	$classes = [
		'P' => 'present',
		'A' => 'absent',
		'H' => 'half-day',
		// 'T' => '#0000FF',
	];

	$class = issetVal( $classes[$value], '' );

	$classes_alt = [
		'P' => 'present-alt',
		'A' => 'absent-alt',
		'H' => 'half-day-alt',
		// 'T' => '#DDDDFF',
	];

	$class_alt = issetVal( $classes_alt[$value], '' );

	if ( ! empty( $current_RET[$THIS_RET['STUDENT_ID']][1]['ATTENDANCE_TEACHER_CODE'] ) )
	{
		if ( $current_RET[$THIS_RET['STUDENT_ID']][1]['ATTENDANCE_TEACHER_CODE'] == mb_substr( $title, 5 ) )
		{
			if ( isset( $_REQUEST['LO_save'] ) )
			{
				return _( 'Yes' );
			}

			return '<div class="attendance-code ' . $class . '">
				<input type="radio" name="attendance[' . $THIS_RET['STUDENT_ID'] . ']"
					value="' . AttrEscape( $title ) . '" checked /></div>';
		}

		return '<div class="attendance-code">
			<input type="radio" name="attendance[' . $THIS_RET['STUDENT_ID'] . ']"
				value="' . AttrEscape( $title ) . '"' . ( AllowEdit() ? '' : ' disabled' ) . '></div>';
	}

	if ( isset( $_REQUEST['LO_save'] ) )
	{
		return _( 'Yes' );
	}

	return '<div class="attendance-code ' . $class_alt . '">
		<input type="radio" name="attendance[' . $THIS_RET['STUDENT_ID'] . ']"
		value="' . AttrEscape( $title ) . '" checked /></div>';
}

/**
 * @param $student_id
 * @param $column
 */
function makeCommentInput( $student_id, $column )
{
	global $current_RET;

	return TextInput(
		( isset( $current_RET[$student_id][1]['COMMENT'] ) ?
			$current_RET[$student_id][1]['COMMENT'] :
			'' ),
		'comment[' . $student_id . ']',
		'',
		'maxlength="100" size="20"',
		true,
		true
	);
}

/**
 * @param $student_id
 * @param $column
 * @return mixed
 */
function makeAttendanceReason( $student_id, $column )
{
	global $current_RET,
		$attendance_reason;

	if ( ! empty( $current_RET[$student_id][1]['ATTENDANCE_REASON'] ) )
	{
		$attendance_reason = true;

		return $current_RET[$student_id][1]['ATTENDANCE_REASON'];
	}
}

// @since 3.9.1 Add Daily Comment column.
/**
 * @param $student_id
 * @param $column
 * @return mixed
 */
function makeDailyComment( $student_id, $column )
{
	global $current_RET,
		$daily_comment;

	if ( ! empty( $current_RET[$student_id][1]['DAILY_COMMENT'] ) )
	{
		$daily_comment = true;

		return $current_RET[$student_id][1]['DAILY_COMMENT'];
	}
}
