<?php
/**
 * Generate Menu entries
 *
 * Depending on:
 * Activated modules
 * User profile & exceptions
 *
 * Save it in $_ROSARIO['Menu'] global var
 *
 * @package RosarioSIS
 */

if ( empty( $_ROSARIO['Menu'] ) )
{
	if ( ! isset( $RosarioModules ) )
	{
		global $RosarioModules;
	}

	// Include Menu.php for each active module.
	foreach ( (array) $RosarioModules as $module => $active )
	{
		if ( $active )
		{
			if ( ROSARIO_DEBUG )
			{
				include 'modules/' . $module . '/Menu.php';
			}
			else
				@include 'modules/' . $module . '/Menu.php';
		}
	}

	$profile = User( 'PROFILE' );

	if ( User( 'PROFILE_ID' ) != '' )
	{
		$allow_use_sql = "SELECT MODNAME
			FROM profile_exceptions
			WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
			AND CAN_USE='Y'";
	}
	// If user has custom exceptions.
	else
	{
		$allow_use_sql = "SELECT MODNAME
			FROM staff_exceptions
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND CAN_USE='Y'";
	}

	if ( $profile == 'student' )
	{
		// Force student profile to parent (same rights in Menu.php files).
		$profile = 'parent';
	}

	$_ROSARIO['AllowUse'] = DBGet( $allow_use_sql, [], [ 'MODNAME' ] );

	$_ROSARIO['Menu'] = [];

	// Loop menu entries for each module & profile.
	// Save menu entries in $_ROSARIO['Menu'] global var.
	foreach ( (array) $menu as $modcat => $profiles )
	{
		// FJ bugfix remove modules with no programs.
		$no_programs_in_module = true;

		$programs = issetVal( $profiles[ $profile ], [] );

		foreach ( (array) $programs as $program => $title )
		{
			if ( $program === 'title' // Module title.
				|| $program === 'default' // Default program when opening module.
				|| is_numeric( $program ) ) // If program is numeric, it is a section.
			{
				$_ROSARIO['Menu'][ $modcat ][ $program ] = $title;

				continue;
			}

			// if ($_ROSARIO['AllowUse'][ $program ] && ($profile!='admin' || ! $exceptions[ $modcat ][ $program ] || AllowEdit($program)))
			// If program allowed, add it.
			if ( ! empty( $_ROSARIO['AllowUse'][ $program ] )
					&& ( $profile !== 'admin'
						|| empty( $exceptions[ $modcat ][ $program ] )
						|| AllowEdit( $program ) ) )
			{
				$_ROSARIO['Menu'][ $modcat ][ $program ] = $title;

				// Default to first allowed program if default not allowed.
				if ( ! isset( $_ROSARIO['Menu'][ $modcat ]['default'] )
					|| empty( $_ROSARIO['AllowUse'][ $_ROSARIO['Menu'][ $modcat ]['default'] ] ) )
				{
					$_ROSARIO['Menu'][ $modcat ]['default'] = $program;
				}

				$no_programs_in_module = false;
			}
		}

		if ( $no_programs_in_module )
		{
			unset( $_ROSARIO['Menu'][ $modcat ] );
		}
		// Compat with Modules < 2.9: no title entry for Menu.
		elseif ( ! isset( $_ROSARIO['Menu'][ $modcat ]['title'] ) )
		{
			$_ROSARIO['Menu'][ $modcat ]['title'] = _( str_replace( '_', ' ', $modcat ) );
		}
	}
}
