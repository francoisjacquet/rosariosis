<?php
/**
 * School functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Update School Array
 * Fill `$_SESSION['SchoolData']` session var with schools data
 *
 * @example UpdateSchoolArray( UserSchool() );
 *
 * @param  integer $school_id School ID (optional). Defaults to User School ID.
 *
 * @return void    Fill $_SESSION['SchoolData'] session var
 */
function UpdateSchoolArray( $school_id = null )
{
	if ( ! $school_id )
	{
		$school_id = UserSchool();
	}

	$_SESSION['SchoolData'] = DBGet( "SELECT *,
		(SELECT COUNT(*) FROM schools WHERE SYEAR = '" . UserSyear() . "') AS SCHOOLS_NB
		FROM schools
		WHERE ID = '" . (int) $school_id . "'
		AND SYEAR = '" . UserSyear() . "'" );

	$_SESSION['SchoolData'] = isset( $_SESSION['SchoolData'][1] ) ? $_SESSION['SchoolData'][1] : [];
}


/**
 * Get School Info
 *
 * @example DrawHeader( SchoolInfo( 'TITLE' ) );
 *
 * @param  string       $field schools DB table field name (optional). Defaults to every School Fields.
 *
 * @return string|array School Field or array with every School Fields
 */
function SchoolInfo( $field = null )
{
	if ( ! isset( $_SESSION['SchoolData'] )
		|| $_SESSION['SchoolData']['ID'] != UserSchool()
		|| $_SESSION['SchoolData']['SYEAR'] != UserSyear() )
	{
		UpdateSchoolArray( UserSchool() );
	}

	if ( $field )
	{
		return $_SESSION['SchoolData'][ (string) $field ];
	}

	return $_SESSION['SchoolData'];
}
