<?php
/**
 * Rollover functions
 *
 * @package RosarioSIS
 */

/**
 * Delete Courses SQL
 * Deletes Gradebook Assignments, Schedule Requests, Subjects, Courses & Course Periods
 * But not Schedules, Eligibility entries, or any other DB entries related to Courses (foreign key)
 *
 * @since 11.7
 *
 * @param  int $next_syear Next School Year.
 *
 * @return string SQL queries
 */
function RolloverDeleteCoursesSQL( $next_syear )
{
	/**
	 * Fix SQL error foreign key exists on tables
	 * gradebook_assignments,gradebook_assignment_types,schedule_requests
	 *
	 * Error happens when an Assignment, or a Schedule Request was added for a rolled Course.
	 */
	$delete_sql = "DELETE FROM gradebook_assignments
		WHERE COURSE_ID IN(SELECT COURSE_ID FROM courses
			WHERE SYEAR='" . $next_syear . "'
			AND SCHOOL_ID='" . UserSchool() . "')
		OR COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID FROM course_periods
			WHERE SYEAR='" . $next_syear . "'
			AND SCHOOL_ID='" . UserSchool() . "');";

	$delete_sql .= "DELETE FROM gradebook_assignment_types
		WHERE COURSE_ID IN(SELECT COURSE_ID FROM courses
			WHERE SYEAR='" . $next_syear . "'
			AND SCHOOL_ID='" . UserSchool() . "');";

	$delete_sql .= "DELETE FROM schedule_requests
		WHERE SYEAR='" . $next_syear . "'
		AND SCHOOL_ID='" . UserSchool() . "';";

	/**
	 * Fix MySQL syntax error: no table alias in DELETE.
	 *
	 * @link https://stackoverflow.com/questions/34353799/can-aliases-be-used-in-a-sql-delete-query
	 */
	$delete_sql .= "DELETE FROM course_period_school_periods
		WHERE COURSE_PERIOD_ID IN (SELECT COURSE_PERIOD_ID
			FROM course_periods
			WHERE SYEAR='" . $next_syear . "'
			AND SCHOOL_ID='" . UserSchool() . "');";

	$delete_sql .= "DELETE FROM course_periods
		WHERE SYEAR='" . $next_syear . "'
		AND SCHOOL_ID='" . UserSchool() . "';";

	$delete_sql .= "DELETE FROM courses
		WHERE SYEAR='" . $next_syear . "'
		AND SCHOOL_ID='" . UserSchool() . "';";

	$delete_sql .= "DELETE FROM course_subjects
		WHERE SYEAR='" . $next_syear . "'
		AND SCHOOL_ID='" . UserSchool() . "';";

	return $delete_sql;
}

/**
 * Do Rollover warning
 * Check if config.inc.php's $DefaultSyear matches user School Year
 * Check if Rollover is not done and if school year has ended
 *
 * @since 11.7
 *
 * @global $DefaultSyear
 *
 * @return string Empty if school year has not ended yet else warning text.
 */
function RolloverDoWarning()
{
	global $DefaultSyear;

	if ( $DefaultSyear !== UserSyear() )
	{
		// Default School Year in config.inc.php is not user School Year.
		return '';
	}

	// Check if school year has ended
	$do_rollover = DBGetOne( "SELECT 1 AS DO_ROLLOVER
		FROM school_marking_periods
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND MP='FY'
		AND END_DATE<'" . DBDate() . "'
		AND NOT EXISTS(SELECT 1
			FROM school_marking_periods
			WHERE SYEAR='" . ( UserSyear() + 1 ) . "'
			AND SCHOOL_ID='" . UserSchool() . "')" );

	if ( ! $do_rollover )
	{
		return '';
	}

	return sprintf(
		_( 'The school year has ended. It is time to proceed to %s.' ),
		'<a href="Modules.php?modname=School_Setup/Rollover.php">' . _( 'Rollover' ) . '</a>'
	);
}

/**
 * Make sure Rollover is done for all schools warning
 * Invite user to do Rollover for all schools now
 * because after updating the default school year,
 * it won't be possible unless you edit the config.inc.php file
 *
 * Check if config.inc.php's $DefaultSyear matches user School Year
 * Check if we have more than 1 school
 * Check if Rollover is done for all schools
 *
 * @since 11.7
 *
 * @global $DefaultSyear
 *
 * @return string Empty or warning text.
 */
function RolloverAllSchoolsDoneWarning()
{
	global $DefaultSyear;

	if ( $DefaultSyear !== UserSyear()
		|| SchoolInfo( 'SCHOOLS_NB' ) == 1 )
	{
		// Default School Year in config.inc.php is not user School Year.
		// Or only one school.
		return '';
	}

	// Count schools having FY MP in next school year.
	$rollover_schools_done = (int) DBGetOne( "SELECT COUNT(1)
		FROM school_marking_periods
		WHERE SYEAR='" . ( UserSyear() + 1 ) . "'
		AND MP='FY'" );

	if ( $rollover_schools_done >= SchoolInfo( 'SCHOOLS_NB' ) )
	{
		return '';
	}

	return _( 'Please make sure Rollover is done for all schools.' );
}

/**
 * Update default school year warning
 * Check if config.inc.php's $DefaultSyear matches user School Year
 * Check if Rollover is done
 * Check user exists in the next school year
 *
 * - if user does not exist in the next school year:
 * "Your user does not exist in the next school year, please fix that first."
 * - if config.inc.php file is writable & ROSARIOSIS_YEAR environment var is not set (Docker):
 * "Do not forget to update the default school year to '2023-2024' when ready. [Yes, I am ready]"
 * - else if on rosariosis.com:
 * "Do not forget to update the default school year to '2023-2024' from your account when ready."
 * - else:
 * "Do not forget to update the $DefaultSyear to '2023' in the config.inc.php file when ready."
 *
 * @since 11.7
 * @since 11.8.1 Check user exists in the next school year
 *
 * @global $DefaultSyear
 *
 * @return string Empty if rollover not done else warning text.
 */
function RolloverUpdateDefaultSyearWarning()
{
	global $DefaultSyear;

	if ( $DefaultSyear !== UserSyear() )
	{
		// Default School Year in config.inc.php is not user School Year.
		return '';
	}

	$next_syear = UserSyear() + 1;

	// Update default school year warning when Rollover is done
	$update_syear = DBGetOne( "SELECT 1 AS UPDATE_SYEAR
		FROM school_marking_periods
		WHERE SYEAR='" . $next_syear . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( ! $update_syear )
	{
		return '';
	}

	$user_exists_in_next_syear = DBGetOne( "SELECT 1 AS USER_EXISTS
		FROM staff
		WHERE SYEAR='" . $next_syear . "'
		AND USERNAME='" . DBEscapeString( User( 'USERNAME' ) ) . "'" );

	if ( ! $user_exists_in_next_syear )
	{
		return _( 'Your user does not exist in the next school year, please fix that first.' );
	}

	if ( is_writable( 'config.inc.php' )
		&& ! getenv( 'ROSARIOSIS_YEAR' ) ) // Docker environment variable TODO test!
	{
		// If config.inc.php is writable, offer to update $DefaultSyear now
		$update_syear_warning = sprintf(
			_( 'Do not forget to update the default school year to \'%s\' when ready.' ),
			FormatSyear( $next_syear, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) )
		);

		$update_syear_url = 'Modules.php?modname=School_Setup/Rollover.php&modfunc=update_syear';

		$update_syear_warning .= ' <input type="button" name="update_syear" value="' .
			AttrEscape( _( 'OK, I am ready' ) ) . '" onclick="' .
			AttrEscape( 'ajaxLink(' . json_encode( $update_syear_url ) . ');' ) . '">';

		return $update_syear_warning;
	}

	if ( strpos( $_SERVER['HTTP_HOST'], '.rosariosis.com' ) !== false )
	{
		$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

		$locale_short = $lang_2_chars === 'fr' || $lang_2_chars === 'es' ?
			$lang_2_chars . '/' : '';

		return sprintf(
			_( 'Do not forget to update the default school year to \'%d\' from <a href="%s" target="_blank">your account</a> when ready.' ),
			FormatSyear( $next_syear, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ),
			URLEscape( 'https://www.rosariosis.com/' . $locale_short .	'account/' )
		);
	}

 	return sprintf(
		_( 'Do not forget to update the $DefaultSyear to \'%d\' in the config.inc.php file when ready.' ),
		$next_syear
	);
}

/**
 * Update $DefaultSyear global variable (default school year) in the config.inc.php file
 *
 * @since 11.7
 *
 * @global $DefaultSyear
 *
 * @param int $next_syear Next school year.
 *
 * @return bool False is config.inc.php file is not writable or if $DefaultSyear not updated.
 */
function RolloverUpdateDefaultSyear( $next_syear )
{
	global $DefaultSyear;

	if ( ! strtotime( $next_syear . '-12-31' )
		|| $DefaultSyear == $next_syear
		|| ! is_writable( 'config.inc.php' ) )
	{
		return false;
	}

	$config_contents = file_get_contents( 'config.inc.php' );

	// https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php
	$config_lines = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $config_contents ) );

	$update_contents = [];

	$updated = false;

	foreach ( $config_lines as $line_i => $line )
	{
		if ( mb_strpos( $line, '$DefaultSyear' ) !== false )
		{
			$updated_line = str_replace( $DefaultSyear, $next_syear, $line );

			if ( $line !== $updated_line )
			{
				$updated = true;

				break;
			}
		}
	}

	if ( ! $updated )
	{
		return false;
	}

	$config_lines[ $line_i ] = $updated_line;

	$config_contents = implode( "\r\n", $config_lines );

	return file_put_contents( 'config.inc.php', $config_contents );
}
