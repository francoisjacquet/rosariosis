<?php
/**
 * Generate Menu entries
 *
 * Depending on:
 * Activated modules
 * User profile & exceptions
 *
 * Save it in $_ROSARIO['Menu'] global var
 */

if ( empty( $_ROSARIO['Menu'] ) )
{
	if ( !isset( $RosarioModules ) )
		global $RosarioModules;

	// include Menu.php for each active module
	foreach ( (array)$RosarioModules as $module => $active )
	{
		if ( $active )
			include_once 'modules/' . $module . '/Menu.php';
	}

	$profile = User( 'PROFILE' );

	if ( $profile !== 'student' )
	{
		if ( User( 'PROFILE_ID' ) )
		{
			$AllowUse_SQL = "SELECT MODNAME
				FROM PROFILE_EXCEPTIONS
				WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
				AND CAN_USE='Y'";
		}
		// if user has custom exceptions
		else
		{
			$AllowUse_SQL = "SELECT MODNAME
				FROM STAFF_EXCEPTIONS
				WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
				AND CAN_USE='Y'";
		}
	}
	else
	{
		$AllowUse_SQL = "SELECT MODNAME
			FROM PROFILE_EXCEPTIONS
			WHERE PROFILE_ID='0'
			AND CAN_USE='Y'";

		// force student profile to parent (same rights in Menu.php files)
		$profile = 'parent';
	}

	$_ROSARIO['AllowUse'] = DBGet( DBQuery( $AllowUse_SQL ), array(), array( 'MODNAME' ) );

	// Loop menu entries for each module & profile
	// Save menu entries in $_ROSARIO['Menu'] global var
	foreach ( (array)$menu as $modcat => $profiles )
	{
		//FJ bugfix remove modules with no programs
		$no_programs_in_module = true;
		
		$programs = $profiles[$profile];

		foreach ( (array)$programs as $program => $title )
		{
			if ( $program === 'title' // Module title
				|| $program === 'default' // default program when opening module
				|| is_numeric( $program ) ) // if program is numeric, it is a section
			{
				$_ROSARIO['Menu'][$modcat][$program] = $title;

				continue;
			}

			// if ($_ROSARIO['AllowUse'][$program] && ($profile!='admin' || !$exceptions[$modcat][$program] || AllowEdit($program)))
			// if program allowed, add it
			if ( !empty( $_ROSARIO['AllowUse'][$program] )
					&& ( $profile !== 'admin'
						|| empty( $exceptions[$modcat][$program] )
						|| AllowEdit( $program ) ) )
			{
				$_ROSARIO['Menu'][$modcat][$program] = $title;

				// default to first allowed program if default not allowed
				if ( !isset( $_ROSARIO['Menu'][$modcat]['default'] ) )
				{
					$_ROSARIO['Menu'][$modcat]['default'] = $program;
				}

				$no_programs_in_module = false;
			}
		}
		
		if ( $no_programs_in_module )
			unset( $_ROSARIO['Menu'][$modcat] );
	}
}
