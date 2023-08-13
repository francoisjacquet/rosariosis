<?php
/**
 * Attendance Codes functions
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Make Attendance Code
 *
 * @todo use in TakeAttendance & other reports than DailySummary.php.
 *
 * @since 3.8
 * @since 9.0 Allow empty State code.
 *
 * @param array  $state_code Attendance State code.
 * @param string $name       Name or HTML to display. Defaults to localized attendance code.
 * @param string $title      Title attribute. Defaults to localized attendance code.
 *
 * @return string Attendance code HMTL.
 */
function MakeAttendanceCode( $state_code, $name = '', $title = '' )
{
	$attendance_codes_locale = [
		// Attendance codes.
		'P' => _( 'Present' ),
		'A' => _( 'Absent' ),
		'H' => _( 'Half' ),
		// Daily attendance.
		'1.0' => _( 'Present' ),
		'0.0' => _( 'Absent' ),
		'0.5' => _( 'Half Day' ),
	];

	$attendance_code_classes = [
		// Attendance codes.
		'P' => 'present',
		'A' => 'absent',
		'H' => 'half-day',
		// Daily attendance.
		'1.0' => 'present',
		'0.0' => 'absent',
		'0.5' => 'half-day',
	];

	$class = 'attendance-code';

	if ( is_numeric( $state_code )
		&& $state_code > 0
		&& $state_code < 1 )
	{
		// Round to 0.5 for Half Day.
		$state_code = '0.5';
	}

	if ( $state_code )
	{
		if ( $title === '' )
		{
			$title = $attendance_codes_locale[ $state_code ];
		}

		$class .= ' ' . $attendance_code_classes[ $state_code ];
	}

	if ( $name === '' )
	{
		$name = $title;

		$class .= ' size-1';
	}

	return '<div class="' . $class . '" title="' . AttrEscape( $title ) . '">' . $name . '</div>';
}


/**
 * Attendance Codes Tip Message
 *
 * @since 3.8
 *
 * @since 3.9 Added $type param.
 * @since 9.0 Added $table param.
 *
 * @uses MakeAttendanceCode
 * @uses MakeTipMessage
 *
 * @param string $type Type: 'teacher' or 'official'. Defaults to ''. Optional.
 *
 * @return string Attendance Codes Tip Message.
 */
function AttendanceCodesTipMessage( $type = '', $table = '0' )
{
	static $attendance_codes_RET;

	require_once 'ProgramFunctions/TipMessage.fnc.php';

	$message = '';

	if ( empty( $attendance_codes_RET ) )
	{
		$type_where = '';

		if ( $type === 'teacher'
			|| $type === 'official' )
		{
			$type_where = " AND TYPE='" . $type . "' ";
		}

		$attendance_codes_RET = DBGet( "SELECT ID,DEFAULT_CODE,STATE_CODE,SHORT_NAME,TITLE
		FROM attendance_codes
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND TABLE_NAME='" . (int) $table . "'" .
		$type_where .
		" ORDER BY TABLE_NAME,SORT_ORDER IS NULL,SORT_ORDER" );
	}

	foreach ( (array) $attendance_codes_RET as $attendance_code )
	{
		$title = $attendance_code['TITLE'];

		if ( $attendance_code['DEFAULT_CODE'] === 'Y' )
		{
			$title = '<i>' . $attendance_code['TITLE'] . '</i>';
		}

		$message .= MakeAttendanceCode( $attendance_code['STATE_CODE'], $attendance_code['SHORT_NAME'] ) . ' ' . $title . '<br />';
	}

	$tip_message = MakeTipMessage(
		$message,
		_( 'Attendance Codes' ),
		button( 'comment', _( 'Attendance Codes' ) )
	);

	return $tip_message;
}
