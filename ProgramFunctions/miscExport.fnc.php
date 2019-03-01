<?php
/**
 * Functions used by the `misc/Export.php` program
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Make Next School
 * Local function for `misc/Export.php` program
 *
 * DBGet() callback function
 *
 * @global $THIS_RET Current row in the results
 *
 * @param  string $value  Next School value.
 * @param  string $column 'NEXT_SCHOOL' Column.
 *
 * @return string         Next school text.
 */
function _makeNextSchool( $value, $column )
{
	global $THIS_RET;

	static $schools_RET;

	if ( $value == '0' )
	{
		return _( 'Retain' );
	}
	elseif ( $value == '-1' )
	{
		return _( 'Do not enroll after this school year' );
	}

	if ( ! $schools_RET )
	{
		$schools_RET = DBGet( "SELECT ID,TITLE
			FROM SCHOOLS WHERE
			SYEAR='" . UserSyear() . "'", array(), array( 'ID' ) );
	}

	$school_title = $schools_RET[ $value ][1]['TITLE'];

	if ( $value == $THIS_RET['SCHOOL_ID'] )
	{
		return _( 'Next Grade at ' ) . $school_title;
	}
	else
		return $school_title;

}


/**
 * Make Calendar
 * Local function for `misc/Export.php` program
 *
 * DBGet() callback function
 *
 * @static $calendars_RET Calendar titles for all schools.
 *
 * @param  string $value  Calendar ID value.
 * @param  string $column 'CALENDAR_ID' Column.
 *
 * @return string         Calendar title.
 */
function _makeCalendar( $value, $column )
{
	static $calendars_RET = false;

	if ( ! $calendars_RET )
	{
		$calendars_RET = DBGet( "SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE
			FROM ATTENDANCE_CALENDARS
			WHERE SYEAR='" . UserSyear() . "'", array(), array( 'CALENDAR_ID' ) );
	}

	return $calendars_RET[ $value ][1]['TITLE'];
}


/**
 * Make Teachers
 * Local function for `misc/Export.php` program
 *
 * DBGet() callback function
 *
 * @param  string $value  Period teachers value.
 * @param  string $column 'PERIOD_' . $period['PERIOD_ID'] Column.
 *
 * @return string         Formatted teachers, one per line.
 */
function _makeTeachers( $value, $column )
{
	if ( $value === '{}' )
	{
		return '';
	}

	$teachers = explode( '","', mb_substr( $value, 2, -2 ) );

	return implode( '<br />', (array) $teachers );
}
