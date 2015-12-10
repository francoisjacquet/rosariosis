<?php

/**
 * Update School Array
 * Fill $_SESSION['SchoolData'] session var with SCHOOLS data
 *
 * @example UpdateSchoolArray( UserSchool() );
 *
 * @param  integer $school_id School ID (optional). Defaults to User School ID
 *
 * @return void    Fill $_SESSION['SchoolData'] session var
 */
function UpdateSchoolArray( $school_id = null )
{
	if ( is_null( $school_id ) )
	{
		$school_id = UserSchool();
	}

	$_SESSION['SchoolData'] = DBGet( DBQuery( "SELECT *
		FROM SCHOOLS
		WHERE ID = '" . $school_id . "'
		AND SYEAR = '" . UserSyear() . "'" ) );

	$_SESSION['SchoolData'] = $_SESSION['SchoolData'][1];
	
	//FJ if only one school, no Search All Schools option
	$schools_nb = DBGet( DBQuery( "SELECT COUNT(*)
		AS SCHOOLS_NB
		FROM SCHOOLS
		WHERE SYEAR = '" . UserSyear() . "';" ) );

	$_SESSION['SchoolData']['SCHOOLS_NB'] = $schools_nb[1]['SCHOOLS_NB'];
}

/**
 * Get School Info
 *
 * @example DrawHeader( SchoolInfo( 'TITLE' ) );
 *
 * @param  string       $field SCHOOLS DB table field name (optional). Defaults to every School Fields
 * @return string|array School Field or array with every School Fields
 */
function SchoolInfo( $field = null )
{
	if ( !isset( $_SESSION['SchoolData'] )
		|| $_SESSION['SchoolData']['ID'] != UserSchool() )
	{
		UpdateSchoolArray( UserSchool() );
	}
		
	if ( !is_null( $field ) )
	{
		return $_SESSION['SchoolData'][ $field ];
	}
	else
		return $_SESSION['SchoolData'];
}
