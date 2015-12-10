<?php

/**
 * Get Configuration value
 *
 * @example  Config( 'SYEAR' )
 *
 * @global array  $_ROSARIO     Sets $_ROSARIO['Config']
 * @global string $DefaultSyear
 *
 * @param  string $item         Config title
 *
 * @return string Config value
 */
function Config( $item )
{
	global $_ROSARIO,
		$DefaultSyear;

	// Get General & School Config
	if ( !isset( $_ROSARIO['Config'][ $item ] ) )
	{
		// General (for every school) Config is stored with SCHOOL_ID=0
		$school_where = "SCHOOL_ID='0'";

		// If user logged in
		if ( UserSchool() > 0 )
			$school_where = "SCHOOL_ID='" . UserSchool() . "' OR " . $school_where;

		$_ROSARIO['Config'] = DBGet( DBQuery( "SELECT TITLE, CONFIG_VALUE
			FROM CONFIG
			WHERE " . $school_where ), array(), array( 'TITLE' ) );

		$_ROSARIO['Config']['SYEAR'][1]['CONFIG_VALUE'] = $DefaultSyear;
	}

	return $_ROSARIO['Config'][ $item ][1]['CONFIG_VALUE'];
}


/**
 * Get Program Configuration
 * Get 1 value if item specified,
 * else get Program values
 *
 * Values set in School Configuration or directly in Module (ex.: Eligibility Entry times)
 *
 * @example if ( ProgramConfig( 'STUDENTS_SEMESTER_COMMENTS', 'students' ) )
 *
 * @since 2.9
 *
 * @global array        $_ROSARIO Sets $_ROSARIO['ProgramConfig']
 *
 * @param  string       $program  eligibility|grades|students|moodle|food_service|attendance... Program name
 * @param  string       $item     Program Config title (optional). Defaults to 'all'
 *
 * @return string|array Program Configuration value, or Program values in array
 */
function ProgramConfig( $program, $item = 'all'  )
{
	global $_ROSARIO;

	if ( !isset( $_ROSARIO['ProgramConfig'] ) )
	{
		$_ROSARIO['ProgramConfig'] = DBGet( DBQuery( "SELECT PROGRAM,TITLE,VALUE
			FROM PROGRAM_CONFIG
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ), array(), array( 'PROGRAM', 'TITLE' ) );
	}

	if ( $item === 'all' )
	{
		return $_ROSARIO['ProgramConfig'][ $program ];
	}
	else
		return $_ROSARIO['ProgramConfig'][ $program ][ $item ][1]['VALUE'];
}