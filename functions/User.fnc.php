<?php
/**
 * User & Preferences functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Get (logged) User info
 *
 * @example User( 'PROFILE' )
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['User']
 *
 * @param  string $item     User info item; see STAFF table fields for Admin/Parent/Teacher; STUDENT & STUDENT_ENROLLMENT fields for Student.
 *
 * @return string User info value
 */
function User( $item )
{
	global $_ROSARIO;

	if ( ! $item )
	{
		return '';
	}

	// Set Current School Year if needed.
	if ( ! UserSyear() )
	{
		$_SESSION['UserSyear'] = Config( 'SYEAR' );
	}

	// Get User Info or Update it if Syear changed.
	if ( ! isset( $_ROSARIO['User'] )
		|| UserSyear() !== $_ROSARIO['User'][1]['SYEAR'] )
	{
		// Get User Info.
		if ( ! empty( $_SESSION['STAFF_ID'] )
			&& $_SESSION['STAFF_ID'] !== '-1' )
		{
			$sql = "SELECT STAFF_ID,USERNAME," . DisplayNameSQL() . " AS NAME,
				PROFILE,PROFILE_ID,SCHOOLS,CURRENT_SCHOOL_ID,EMAIL,SYEAR,LAST_LOGIN
				FROM STAFF
				WHERE SYEAR='" . UserSyear() . "'
				AND USERNAME=(SELECT USERNAME
					FROM STAFF
					WHERE SYEAR='" . Config( 'SYEAR' ) . "'
					AND STAFF_ID='" . $_SESSION['STAFF_ID'] . "')";

			$_ROSARIO['User'] = DBGet( $sql );
		}
		// Get Student Info.
		elseif ( ! empty( $_SESSION['STUDENT_ID'] ) )
		{
			$sql = "SELECT '0' AS STAFF_ID,s.USERNAME," . DisplayNameSQL( 's' ) . " AS NAME,
				'student' AS PROFILE,'0' AS PROFILE_ID,LAST_LOGIN,
				','||se.SCHOOL_ID||',' AS SCHOOLS,se.SYEAR,se.SCHOOL_ID
				FROM STUDENTS s,STUDENT_ENROLLMENT se
				WHERE s.STUDENT_ID='" . $_SESSION['STUDENT_ID'] . "'
				AND se.SYEAR='" . UserSyear() . "'
				AND se.STUDENT_ID=s.STUDENT_ID
				ORDER BY se.END_DATE DESC LIMIT 1";

			$_ROSARIO['User'] = DBGet( $sql );

			if ( $_ROSARIO['User'][1]['SCHOOL_ID'] !== UserSchool() )
			{
				$_SESSION['UserSchool'] = $_ROSARIO['User'][1]['SCHOOL_ID'];
			}
		}
		// FJ create account, diagnostic, PasswordReset.
		elseif ( basename( $_SERVER['PHP_SELF'] ) === 'index.php'
			|| ( isset( $_SESSION['STAFF_ID'] )
				&& $_SESSION['STAFF_ID'] === '-1' ) )
		{
			return false;
		}

		// Fatal error.
		else
		{
			$error[] = 'User not logged in!'; // Should never be displayed, so do not translate.

			return ErrorMessage( $error, 'fatal' );
		}
	}

	return isset( $_ROSARIO['User'][1][ $item ] ) ?
		$_ROSARIO['User'][1][ $item ] :
		null;
}


/**
 * Get User Preference
 *
 * @example  Preferences( 'THEME' )
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['Preferences']
 *
 * @param  string $item     Preference item.
 * @param  string $program  Preferences|Gradebook (optional).
 *
 * @return string          Preference value
 */
function Preferences( $item, $program = 'Preferences' )
{
	global $_ROSARIO;

	if ( ! $item
		|| ! $program )
	{
		return '';
	}

	// Get User Preferences.
	if ( User( 'STAFF_ID' )
		&& ! isset( $_ROSARIO['Preferences'][ $program ] ) )
	{
		$_ROSARIO['Preferences'][ $program ] = DBGet( "SELECT TITLE,VALUE
			FROM PROGRAM_USER_CONFIG
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND PROGRAM='" . $program . "'", array(), array( 'TITLE' ) );
	}

	// FJ add Default Theme to Configuration.
	$default_theme = Config( 'THEME' );

	$defaults = array(
		'SORT' => 'Name',
		'SEARCH' => 'Y',
		'DELIMITER' => 'Tab',
		'HEADER' => '#333366',
		'HIGHLIGHT' => '#FFFFFF',
		'THEME' => $default_theme,
		'MONTH' => '%B',
		'DAY' => '%d',
		'YEAR' => '%Y',
		'DEFAULT_ALL_SCHOOLS' => 'N',
		'ASSIGNMENT_SORTING' => 'ASSIGNMENT_ID',
		'ANOMALOUS_MAX' => '100',
		'PAGE_SIZE' => 'A4',
		'HIDE_ALERTS' => 'N',
		'DEFAULT_FAMILIES' => 'N',
	);

	if ( ! isset( $_ROSARIO['Preferences'][ $program ][ $item ][1]['VALUE'] ) )
	{
		$_ROSARIO['Preferences'][ $program ][ $item ][1]['VALUE'] = isset( $defaults[ $item ] ) ? $defaults[ $item ] : null;
	}

	/**
	 * Force Display student search screen to No
	 * for Parents & Students.
	 */
	if ( $item === 'SEARCH'
		&& ! empty( $_SESSION['STAFF_ID'] )
		&& User( 'PROFILE' ) === 'parent'
		|| ! empty( $_SESSION['STUDENT_ID'] ) )
	{
		$_ROSARIO['Preferences'][ $program ]['SEARCH'][1]['VALUE'] = 'N';
	}

	/**
	 * Force Default Theme.
	 * Override user preference if any.
	 */
	if ( $item === 'THEME'
		&& Config( 'THEME_FORCE' )
		&& ! empty( $_SESSION['STAFF_ID'] ) )
	{
		$_ROSARIO['Preferences'][ $program ]['THEME'][1]['VALUE'] = $defaults['THEME'];
	}

	return $_ROSARIO['Preferences'][ $program ][ $item ][1]['VALUE'];
}
