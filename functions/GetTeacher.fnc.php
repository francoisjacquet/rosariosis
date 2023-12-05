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
 * @since 11.4 SQL performance: limit main query to teacher profile
 * @since 11.4 Smart cache: do not get ALL users database twice if Teacher not found
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

	if ( is_null( $teachers ) )
	{
		$teachers = DBGet( "SELECT STAFF_ID,TITLE,FIRST_NAME,LAST_NAME,MIDDLE_NAME,
			" . DisplayNameSQL() . " AS FULL_NAME,USERNAME,PROFILE
			FROM staff
			WHERE SYEAR='" . UserSyear() . "'
			AND PROFILE='teacher'" .
			( $schools ? " AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" : '' ),
			[],
			[ 'STAFF_ID' ]
		);
	}

	if ( empty( $teachers[ $teacher_id ] )
		&& $teacher_id )
	{
		// Smart cache: do not get ALL users database twice if Teacher not found.
		// Note: SQL request has no profile & school year WHERE clause.
		$teacher = DBGet( "SELECT STAFF_ID,TITLE,FIRST_NAME,LAST_NAME,MIDDLE_NAME,
			" . DisplayNameSQL() . " AS FULL_NAME,USERNAME,PROFILE
			FROM staff
			WHERE STAFF_ID='" . (int) $teacher_id . "'" .
			( $schools ? " AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" : '' ) );

		if ( ! empty( $teacher[1] ) )
		{
			$teachers[ $teacher_id ] = $teacher;
		}
	}

	return $teachers[ $teacher_id ][1][ $column ];
}
