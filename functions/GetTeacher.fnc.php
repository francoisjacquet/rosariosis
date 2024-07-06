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
 * @since 11.7 Fix SQL when "Search All Schools" checked
 *
 * @param string $teacher_id Teacher ID
 * @param string $column FULL_NAME|TITLE|LAST_NAME|FIRST_NAME|MIDDLE_NAME|USERNAME|PROFILE|PROFILE_ID Column name (optional). Defaults to FULL_NAME.
 * @param boolean $schools Is Teacher in current School (optional). Defaults to true.
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
			|| ! in_array( $column, [ 'TITLE', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'USERNAME', 'PROFILE', 'PROFILE_ID' ] ) ) )
	{
		$column = 'FULL_NAME';
	}

	$schools_sql = $schools ? " AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" : '';

	if ( $schools
		&& User( 'PROFILE' ) === 'admin'
		&& ! empty( $_REQUEST['_search_all_schools'] ) )
	{
		// Search All Schools
		$schools_sql = '';

		// Search All Schools: if user is not assigned to "All Schools".
		if ( trim( User( 'SCHOOLS' ), ',' ) )
		{
			// Restrict Search All Schools to user schools.
			$sql_schools_like = explode( ',', trim( User( 'SCHOOLS' ), ',' ) );

			$sql_schools_like = implode( ",' IN SCHOOLS)>0 OR position(',", $sql_schools_like );

			$sql_schools_like = "position('," . $sql_schools_like . ",' IN SCHOOLS)>0";

			$schools_sql = " AND (SCHOOLS IS NULL OR " . $sql_schools_like . ") ";
		}
	}

	if ( is_null( $teachers ) )
	{
		$teachers = DBGet( "SELECT STAFF_ID,TITLE,FIRST_NAME,LAST_NAME,MIDDLE_NAME,
			" . DisplayNameSQL() . " AS FULL_NAME,USERNAME,PROFILE,PROFILE_ID
			FROM staff
			WHERE SYEAR='" . UserSyear() . "'
			AND PROFILE='teacher'" . $schools_sql,
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
			WHERE STAFF_ID='" . (int) $teacher_id . "'" . $schools_sql );

		if ( ! empty( $teacher[1] ) )
		{
			$teachers[ $teacher_id ] = $teacher;
		}
	}

	return $teachers[ $teacher_id ][1][ $column ];
}
