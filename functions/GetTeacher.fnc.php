<?php
/**
 * Get Teacher Info function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Get Teacher Info
 *
 * @param  string $teacher_id Teacher ID
 * @param  string  $column     FULL_NAME|LAST_NAME|FIRST_NAME|USERNAME|PROFILE Column name (optional). Defaults to FULL_NAME.
 * @param  boolean $schools    Is Teacher in current School (optional). Defaults to true.
 *
 * @return string  Teacher Column content
 */
function GetTeacher( $teacher_id, $column = 'FULL_NAME', $schools = true )
{
	static $teachers = null;

	// Column defaults to FULL_NAME.
	if ( $column !== 'FULL_NAME'
		&& ( $column === 'STAFF_ID'
			|| $column === 'TEACHER_ID'
			|| ! in_array( $column, array( 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'USERNAME', 'PROFILE' ) ) ) )
	{
		$column = 'FULL_NAME';
	}

	if ( is_null( $teachers ) )
	{
		$teachers = DBGet( DBQuery(
			"SELECT STAFF_ID,FIRST_NAME,LAST_NAME,MIDDLE_NAME,
			" . DisplayNameSQL() . " AS FULL_NAME,USERNAME,PROFILE
			FROM STAFF
			WHERE SYEAR='" . UserSyear() . "'" .
			( $schools ? " AND (SCHOOLS IS NULL OR SCHOOLS LIKE '%," . UserSchool() . ",%')" : '' ) ),
			array(),
			array( 'STAFF_ID' )
		);
	}

	return $teachers[ $teacher_id ][1][ $column ];
}
