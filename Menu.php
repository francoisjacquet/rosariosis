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
	if ( is_null( $RosarioModules ) )
		global $RosarioModules;

	// include Menu.php for each active module
	foreach ( $RosarioModules as $module => $active )
		if ( $active )
			include( 'modules/' . $module . '/Menu.php' );

	$profile = User( 'PROFILE' );

	if ( $profile != 'student' )
	{
		if ( User( 'PROFILE_ID' ) )
		{
			$_ROSARIO['AllowUse'] = DBGet( DBQuery( "SELECT MODNAME
				FROM PROFILE_EXCEPTIONS
				WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
				AND CAN_USE='Y'" ),	array(), array( 'MODNAME' )	);
		}
		// if user has custom exceptions
		else
		{
			$_ROSARIO['AllowUse'] = DBGet( DBQuery( "SELECT MODNAME
				FROM STAFF_EXCEPTIONS
				WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
				AND CAN_USE='Y'" ),	array(), array( 'MODNAME' ) );
		}
	}
	else
	{
		$_ROSARIO['AllowUse'] = DBGet( DBQuery( "SELECT MODNAME
			FROM PROFILE_EXCEPTIONS
			WHERE PROFILE_ID='0'
			AND CAN_USE='Y'" ), array(), array( 'MODNAME' ) );

		// force student profile to parent (same rights in Menu.php files)
		$profile = 'parent';
	}

	// Loop menu entries for each module & profile
	// Save menu entries in $_ROSARIO['Menu'] global var
	foreach ( $menu as $modcat => $profiles )
	{
		//FJ bugfix remove modules with no programs
		$no_programs_in_module = true;
		
		$programs = $profiles[$profile];

		foreach ( $programs as $program => $title )
		{
			if ( !is_numeric( $program ) )
			{
				// if($_ROSARIO['AllowUse'][$program] && ($profile!='admin' || !$exceptions[$modcat][$program] || AllowEdit($program)))
				// default program when opening module
				if ( $program == 'default'
					&& ( !empty( $_ROSARIO['AllowUse'][$title] )
						&& ( $profile != 'admin'
							|| empty( $exceptions[$modcat][$title] )
							|| AllowEdit( $title ) ) ) )
				{
					$_ROSARIO['Menu'][$modcat]['default'] = $title;
				}
				// if program allowed, add it
				elseif( !empty( $_ROSARIO['AllowUse'][$program] )
					&& ( $profile != 'admin'
						|| empty( $exceptions[$modcat][$program] )
						|| AllowEdit( $program ) ) )
				{
					$_ROSARIO['Menu'][$modcat][$program] = $title;

					// default to first allowed program if default not allowed
					if ( !isset($_ROSARIO['Menu'][$modcat]['default'] ) )
						$_ROSARIO['Menu'][$modcat]['default'] = $program;

					$no_programs_in_module = false;
				}
			}
			// if program is numeric, it is a section
			// eg.: [1] => _( 'Setup' )
			else
				$_ROSARIO['Menu'][$modcat][$program] = $title;
		}
		
		if ( $no_programs_in_module )
			unset( $_ROSARIO['Menu'][$modcat] );
	}

	//FJ enable password change for students
	if ( User( 'PROFILE' ) == 'student' )
		//unset($_ROSARIO['Menu']['Users']);
		unset( $_ROSARIO['Menu']['Users']['parent']['Users/User.php'] );
}
?>
