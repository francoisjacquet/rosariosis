<?php
/**
 * Theme functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */


/**
 * Theme live update.
 * Configured theme has changed? Update it live!
 * Reload the page so the new theme stylesheet is loaded.
 *
 * @since  3.0
 *
 * @param  string  $new_theme New theme name / directory.
 * @param  string  $old_theme Old theme name / directory.
 * @param  boolean $default   Is default theme (Configuration.php) or Preferred theme (Preferences.php)?
 *
 * @return boolean            False if has not changed, else DIES before reloading page.
 */
function ThemeLiveUpdate( $new_theme, $old_theme, $default = true )
{
	if ( ! $new_theme
		|| ! $old_theme
		|| ( $new_theme === $old_theme && ! $default ) )
	{
		// Theme has not changed in My Preferences.
		return false;
	}

	if ( ! $default
		&& Config( 'THEME_FORCE' ) )
	{
		// Theme forced, we should not be able to change it anyway from My Preferences!
		return false;
	}


	if ( $default )
	{
		// Check if loaded stylesheet is the same as the new one.
		// Get the value from DB as Preferences() value is overridden by THEME_FORCE!
		$real_user_preferred_theme = DBGetOne( "SELECT VALUE
			FROM program_user_config
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND PROGRAM='Preferences'
			AND TITLE='THEME'" );

		if ( ( ! $real_user_preferred_theme && $new_theme === $old_theme )
			|| $real_user_preferred_theme === $new_theme )
		{
			// User already had that Theme loaded, do nothing.
			return false;
		}
	}

	if ( $default
		&& ! Config( 'THEME_FORCE' )
		&& Preferences( 'THEME' ) !== $new_theme )
	{
		// If not Forcing theme, update admin Preferred theme too.
		DBQuery( "UPDATE program_user_config
			SET VALUE='" . $new_theme . "'
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND PROGRAM='Preferences'
			AND TITLE='THEME'" );
	}

	// Reload the page so the new theme stylesheet gets loaded. ?>
	<script>
		location.reload(true);
	</script>
	<?php

	Warehouse( 'footer' );

	die();
}
