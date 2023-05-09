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
 * @param  string  $column     FULL_NAME|TITLE|LAST_NAME|FIRST_NAME|MIDDLE_NAME|USERNAME|PROFILE Column name (optional). Defaults to FULL_NAME.
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
			|| ! in_array( $column, [ 'TITLE', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'USERNAME', 'PROFILE' ] ) ) )
	{
		$column = 'FULL_NAME';
	}

	if ( empty( $teachers[ $teacher_id ] ) )
	{
		$teachers = DBGet( "SELECT STAFF_ID,TITLE,FIRST_NAME,LAST_NAME,MIDDLE_NAME,
			" . DisplayNameSQL() . " AS FULL_NAME,USERNAME,PROFILE
			FROM staff
			WHERE SYEAR='" . UserSyear() . "'" .
			( $schools ? " AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" : '' ),
			[],
			[ 'STAFF_ID' ]
		);
	}

	return $teachers[ $teacher_id ][1][ $column ];
}
