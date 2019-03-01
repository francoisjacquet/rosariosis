<?php
/**
 * School functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Update School Array
 * Fill `$_SESSION['SchoolData']` session var with SCHOOLS data
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
		(SELECT COUNT(*) FROM SCHOOLS WHERE SYEAR = '" . UserSyear() . "') AS SCHOOLS_NB
		FROM SCHOOLS
		WHERE ID = '" . (int) $school_id . "'
		AND SYEAR = '" . UserSyear() . "'" );

	$_SESSION['SchoolData'] = $_SESSION['SchoolData'][1];
}


/**
 * Get School Info
 *
 * @example DrawHeader( SchoolInfo( 'TITLE' ) );
 *
 * @param  string       $field SCHOOLS DB table field name (optional). Defaults to every School Fields.
 *
 * @return string|array School Field or array with every School Fields
 */
function SchoolInfo( $field = null )
{
	if ( ! isset( $_SESSION['SchoolData'] )
		|| $_SESSION['SchoolData']['ID'] != UserSchool() )
	{
		UpdateSchoolArray( UserSchool() );
	}

	if ( $field )
	{
		return $_SESSION['SchoolData'][ (string) $field ];
	}
	else
		return $_SESSION['SchoolData'];
}
