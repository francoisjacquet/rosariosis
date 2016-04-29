<?php
/**
 * RosarioSIS & Program Configuration functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Get Configuration value
 *
 * @example  Config( 'SYEAR' )
 *
 * @global array  $_ROSARIO     Sets $_ROSARIO['Config']
 * @global string $DefaultSyear
 *
 * @param  string $item         Config title.
 *
 * @return string Config value
 */
function Config( $item )
{
	global $_ROSARIO,
		$DefaultSyear;

	if ( ! $item )
	{
		return '';
	}

	// Get General & School Config.
	if ( !isset( $_ROSARIO['Config'][ (string) $item ] ) )
	{
		// General (for every school) Config is stored with SCHOOL_ID=0.
		$school_where = "SCHOOL_ID='0'";

		// If user logged in.
		if ( UserSchool() > 0 )
		{
			$school_where = "SCHOOL_ID='" . UserSchool() . "' OR " . $school_where;
		}

		$_ROSARIO['Config'] = DBGet( DBQuery( "SELECT TITLE, CONFIG_VALUE
			FROM CONFIG
			WHERE " . $school_where ), array(), array( 'TITLE' ) );

		$_ROSARIO['Config']['SYEAR'][1]['CONFIG_VALUE'] = $DefaultSyear;
	}

	return $_ROSARIO['Config'][ (string) $item ][1]['CONFIG_VALUE'];
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
 * @param  string $program eligibility|grades|students|moodle|food_service|attendance... Program name.
 * @param  string $item    Program Config title (optional). Defaults to 'all'.
 *
 * @return string|array Program Configuration value, or Program values in array
 */
function ProgramConfig( $program, $item = 'all'  )
{
	global $_ROSARIO;

	if ( ! $program
		|| ! $item )
	{
		return '';
	}

	if ( ! isset( $_ROSARIO['ProgramConfig'] ) )
	{
		$_ROSARIO['ProgramConfig'] = DBGet( DBQuery( "SELECT PROGRAM,TITLE,VALUE
			FROM PROGRAM_CONFIG
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ), array(), array( 'PROGRAM', 'TITLE' ) );
	}

	if ( $item === 'all' )
	{
		return $_ROSARIO['ProgramConfig'][ (string) $program ];
	}
	else
		return $_ROSARIO['ProgramConfig'][ (string) $program ][ (string) $item ][1]['VALUE'];
}



/**
 * Program User Config
 * To get all config options at once
 * If you want only one option, prefer `Preferences()`
 *
 * @example $gradebook_config = ProgramUserConfig( 'Gradebook' );
 *
 * @see Preferences()
 * @see PROGRAM_USER_CONFIG table
 *
 * @since 2.9
 *
 * @param string  $program  Gradebook|WidgetsSearch|StaffWidgetsSearch|
 * @param integer $staff_id Staff ID (optional). Defaults to User( 'STAFF_ID' ).
 *
 * @return array Program User Config, associative array( '[title]' => '[value]' ).
 */
function ProgramUserConfig( $program, $staff_id = 0 )
{
	static $program_config;

	if ( ! $program )
	{
		return array();
	}

	$staff_id = $staff_id ? $staff_id : User( 'STAFF_ID' );

	$config_RET = DBGet( DBQuery( "SELECT TITLE,VALUE
		FROM PROGRAM_USER_CONFIG
		WHERE USER_ID='" . $staff_id . "'
		AND PROGRAM='" . $program . "'" ), array(), array( 'TITLE' ) );

	if ( $config_RET )
	{
		foreach ( (array) $config_RET as $title => $value )
		{
			$program_config[ $program ][ $staff_id ][ $title ] = $value[1]['VALUE'];
		}
	}
	else
		$program_config[ $program ][ $staff_id ] = null;

	return $program_config[ $program ][ $staff_id ];
}
