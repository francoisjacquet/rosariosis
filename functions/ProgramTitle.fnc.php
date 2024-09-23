<?php
/**
 * Program Title function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Get Program Title
 *
 * @example DrawHeader( ProgramTitle() );
 *
 * @since 12.0 Only load module's Menu.php file
 *
 * @global Set $_ROSARIO['HeaderIcon']
 * @global $RosarioModules
 * @global $RosarioCoreModules
 *
 * @param  string $modname  Specify program/modname (optional).
 *
 * @return string Program title or 'RosarioSIS' if not found
 */
function ProgramTitle( $modname = '' )
{
	global $_ROSARIO,
		$RosarioModules,
		$RosarioCoreModules;

	if ( empty( $modname ) )
	{
		$modname = $_REQUEST['modname'];
	}

	if ( $modname === 'misc/Portal.php' )
	{
		$_ROSARIO['HeaderIcon'] = 'misc';

		return ParseMLField( Config( 'TITLE' ) );
	}

	$module = mb_substr( $modname, 0, mb_strpos( $modname, '/' ) );

	// Get right module for Teacher Programs.
	if ( mb_substr( $modname, 0, 25 ) === 'Users/TeacherPrograms.php'
		&& mb_strlen( $modname ) > 25 )
	{
		// Use max() to fix PHP fatal error mb_strpos(): $offset must be contained in $haystack
		$module = mb_substr( $modname, 34, mb_strpos( $modname, '/', 34 ) - 34 );

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$modname = mb_substr( $modname, 34 );
		}
	}

	if ( empty( $RosarioModules[ $module ] )
		|| strpos( $module, '..' ) !== false )
	{
		// Module not found!
		return 'RosarioSIS';
	}

	if ( in_array( $module, $RosarioCoreModules ) )
	{
		require 'modules/' . $module . '/Menu.php';
	}
	else // Add-on.
	{
		set_error_handler( function( $errno, $errstr, $errfile, $errline )
		{
			throw new ErrorException( $errstr, $errno, 0, $errfile, $errline );
		} );

		try
		{
			// Performance: up to 10% faster compared to loading root Menu.php.
			require 'modules/' . $module . '/Menu.php';
		}
		catch ( ErrorException $ex )
		{
			/**
			 * Old Menu.php throws a PHP fatal error
			 * Load core modules Menu.php in case add-on adds entries to existing modules.
			 *
			 * @deprecated since May 2024
			 *
			 * @link https://stackoverflow.com/questions/8261756/how-to-catch-error-of-require-or-include-in-php
			 */
			foreach ( $RosarioCoreModules as $core_module )
			{
				require 'modules/' . $core_module . '/Menu.php';
			}

			require 'modules/' . $module . '/Menu.php';
		}

		restore_error_handler();
	}

	$profile = User( 'PROFILE' ) === 'student' ? 'parent' : User( 'PROFILE' );

	// Loop programs.
	foreach ( (array) $menu as $modcat => $menu_module )
	{
		if ( empty( $menu_module[ $profile ] ) )
		{
			continue;
		}

		foreach ( $menu_module[ $profile ] as $program => $title )
		{
			if ( $program !== $modname )
			{
				continue;
			}

			// Set Header Icon.
			if ( ! isset( $_ROSARIO['HeaderIcon'] )
				|| $_ROSARIO['HeaderIcon'] !== false )
			{
				$_ROSARIO['HeaderIcon'] = $modcat;

				// Get right icon for Teacher Programs.
				if ( mb_substr( $modname, 0, 25 ) === 'Users/TeacherPrograms.php' )
				{
					$_ROSARIO['HeaderIcon'] = mb_substr( $modname, 34, mb_strpos( $modname, '/', 34 ) - 34 );
				}
			}

			return $title;
		}
	}

	// Program not found!
	return 'RosarioSIS';
}
