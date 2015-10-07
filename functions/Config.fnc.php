<?php

/**
 * Get Configuration value
 *
 * @example  Config( 'SYEAR' )
 *
 * @param  string $item Config title
 *
 * @return string       Config value
 */
function Config( $item )
{
	global $_ROSARIO,
		$DefaultSyear;

	// Get General & School Config
	if ( !isset( $_ROSARIO['Config'][$item] ) )
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

	return $_ROSARIO['Config'][$item][1]['CONFIG_VALUE'];
}
