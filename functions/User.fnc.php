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
 * @since 7.6.1 Remove use of `$_SESSION['STAFF_ID'] === '-1'`.
 * @since 11.1 Return EMAIL column for students too (empty if "Student email field" not set)
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['User']
 *
 * @param  string $item     User info item; see staff table fields for Admin/Parent/Teacher; STUDENT & student_enrollment fields for Student.
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
	if ( ! isset( $_ROSARIO['User'][1]['SYEAR'] )
		|| UserSyear() !== $_ROSARIO['User'][1]['SYEAR'] )
	{
		// Get User Info.
		if ( ! empty( $_SESSION['STAFF_ID'] )
			&& $_SESSION['STAFF_ID'] > 0 )
		{
			$sql = "SELECT STAFF_ID,USERNAME," . DisplayNameSQL() . " AS NAME,
				PROFILE,PROFILE_ID,SCHOOLS,CURRENT_SCHOOL_ID,EMAIL,SYEAR,LAST_LOGIN,ROLLOVER_ID
				FROM staff
				WHERE SYEAR='" . UserSyear() . "'
				AND USERNAME=(SELECT USERNAME
					FROM staff
					WHERE SYEAR='" . Config( 'SYEAR' ) . "'
					AND STAFF_ID='" . (int) $_SESSION['STAFF_ID'] . "')";

			$_ROSARIO['User'] = DBGet( $sql );
		}
		// Get Student Info.
		elseif ( ! empty( $_SESSION['STUDENT_ID'] )
			&& $_SESSION['STUDENT_ID'] > 0 )
		{
			$email_column = "''";

			if ( Config( 'STUDENTS_EMAIL_FIELD' ) )
			{
				$email_column = Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
					's.USERNAME' : 's.CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );
			}

			$sql = "SELECT '0' AS STAFF_ID,s.USERNAME," . DisplayNameSQL( 's' ) . " AS NAME,
				'student' AS PROFILE,'0' AS PROFILE_ID,LAST_LOGIN,
				" . $email_column . " AS EMAIL,
				CONCAT(',', se.SCHOOL_ID, ',') AS SCHOOLS,se.SYEAR,se.SCHOOL_ID
				FROM students s,student_enrollment se
				WHERE s.STUDENT_ID='" . (int) $_SESSION['STUDENT_ID'] . "'
				AND se.SYEAR='" . UserSyear() . "'
				AND se.STUDENT_ID=s.STUDENT_ID
				ORDER BY se.END_DATE IS NULL DESC,se.END_DATE DESC LIMIT 1";

			$_ROSARIO['User'] = DBGet( $sql );

			if ( ! empty( $_ROSARIO['User'][1]['SCHOOL_ID'] )
				&& $_ROSARIO['User'][1]['SCHOOL_ID'] !== UserSchool() )
			{
				$_SESSION['UserSchool'] = $_ROSARIO['User'][1]['SCHOOL_ID'];
			}
		}
		else
		{
			return false;
		}
	}

	return issetVal( $_ROSARIO['User'][1][ $item ] );
}


/**
 * Get User Preference
 *
 * @example  Preferences( 'THEME' )
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['Preferences']
 *
 * @since 5.8 Preferences overridden with USER_ID='-1', see ProgramUserConfig().
 *
 * @param  string $item     Preference item.
 * @param  string $program  Preferences|Gradebook (optional).
 *
 * @return string          Preference value
 */
function Preferences( $item, $program = 'Preferences' )
{
	global $_ROSARIO,
		$locale;

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
			FROM program_user_config
			WHERE (USER_ID='" . User( 'STAFF_ID' ) . "' OR USER_ID='-1')
			AND PROGRAM='" . $program . "'
			ORDER BY USER_ID", [], [ 'TITLE' ] );
	}

	$defaults = [
		'SORT' => 'Name',
		'SEARCH' => 'Y',
		'DELIMITER' => 'Tab',
		'HEADER' => '#333366',
		'HIGHLIGHT' => '#FFFFFF',
		'THEME' => Config( 'THEME' ),
		// @since 7.1 Select Date Format: Add Preferences( 'DATE' ).
		// @link https://www.w3.org/International/questions/qa-date-format
		'DATE' => ( $locale === 'en_US.utf8' ? '%B %d %Y' : '%d %B %Y' ),
		// @deprecated since 7.1 Use Preferences( 'DATE' ).
		'MONTH' => '%B', 'DAY' => '%d', 'YEAR' => '%Y',
		'DEFAULT_ALL_SCHOOLS' => 'N',
		'ASSIGNMENT_SORTING' => 'ASSIGNMENT_ID',
		'ANOMALOUS_MAX' => '100',
		'PAGE_SIZE' => 'A4',
		'HIDE_ALERTS' => 'N',
		'DEFAULT_FAMILIES' => 'N',
	];

	if ( ! isset( $_ROSARIO['Preferences'][ $program ][ $item ][1]['VALUE'] ) )
	{
		$_ROSARIO['Preferences'][ $program ][ $item ][1]['VALUE'] = issetVal( $defaults[ $item ] );
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

	if ( $item === 'THEME' )
	{
		if ( Config( 'THEME_FORCE' )
			&& ! empty( $_SESSION['STAFF_ID'] ) )
		{
			/**
			 * Force Default Theme.
			 * Override user preference if any.
			 */
			$_ROSARIO['Preferences'][ $program ]['THEME'][1]['VALUE'] = $defaults['THEME'];
		}

		// Sanitize / escape URL as THEME is often included for button img src attribute.
		$_ROSARIO['Preferences'][ $program ]['THEME'][1]['VALUE'] = URLEscape( $_ROSARIO['Preferences'][ $program ]['THEME'][1]['VALUE'] );
	}

	return $_ROSARIO['Preferences'][ $program ][ $item ][1]['VALUE'];
}

/**
 * Impersonate Teacher User
 * So User() function returns UserCoursePeriod() teacher
 * instead of admin or secondary teacher.
 *
 * @since 6.9 Add Secondary Teacher: set User to main teacher.
 *
 * @example if ( ! empty( $_SESSION['is_secondary_teacher'] ) ) UserImpersonateTeacher();
 *
 * @param int $teacher_id Teacher User ID (optional). Defaults to UserCoursePeriod() teacher.
 *
 * @return bool False if no $teacher_id & no UserCoursePeriod(), else true.
 */
function UserImpersonateTeacher( $teacher_id = 0 )
{
	global $_ROSARIO;

	if ( ! $teacher_id
		&& ! UserCoursePeriod() )
	{
		return false;
	}

	if ( ! $teacher_id )
	{
		$teacher_id = DBGetOne( "SELECT TEACHER_ID
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );
	}

	$_ROSARIO['User'] = [
		0 => $_ROSARIO['User'][1],
		1 => [
			'STAFF_ID' => $teacher_id,
			'NAME' => GetTeacher( $teacher_id ),
			'USERNAME' => GetTeacher( $teacher_id, 'USERNAME' ),
			'PROFILE' => 'teacher',
			'SCHOOLS' => ',' . UserSchool() . ',',
			'SYEAR' => UserSyear(),
		],
	];

	return true;
}
