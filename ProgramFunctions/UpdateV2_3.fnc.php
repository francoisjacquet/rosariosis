<?php
/**
 * Update functions for versions 2 to 3
 *
 * Incremental updates
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Update to version 2.9-alpha
 *
 * 1. Add VERSION to config table
 * 2. Add STUDENTS_EMAIL_FIELD to config table.
 * 3. Add course_period_school_periods_id column to course_period_school_periods table PRIMARY KEY
 * 4. Update student_mp_comments table
 * 5. Create school_fields_seq Sequence
 * 6. Add student_assignments table & SUBMISSION column to gradebook_assignments table
 *
 * Local function
 *
 * @since 2.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update29alpha()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;


	/**
	 * 1. Add VERSION to config table.
	 */
	$version_added = DBGet( "SELECT 1 FROM config WHERE TITLE='VERSION'" );

	if ( ! $version_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'VERSION', '2.9-alpha');" );
	}


	/**
	 * 2. Add STUDENTS_EMAIL_FIELD to config table.
	 */
	$students_email_field_added = DBGet( "SELECT 1 FROM config WHERE TITLE='STUDENTS_EMAIL_FIELD'" );

	if ( ! $students_email_field_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'STUDENTS_EMAIL_FIELD', NULL);" );
	}


	/**
	 * 3. Add course_period_school_periods_id column to course_period_school_periods table PRIMARY KEY
	 *
	 * DROP PRIMARY KEY
	 * And ADD it again with course_period_school_periods_id
	 */
	$SQL_add_ID = 'ALTER TABLE ONLY course_period_school_periods
		DROP CONSTRAINT course_period_school_periods_pkey;
	ALTER TABLE ONLY course_period_school_periods
		ADD CONSTRAINT course_period_school_periods_pkey
			PRIMARY KEY (course_period_school_periods_id, course_period_id, period_id);';

	DBQuery( $SQL_add_ID );


	/**
	 * 4. Update student_mp_comments table
	 *
	 * WARNING: no Downgrade possible!
	 *
	 * Serialize comments from:
	 * [date1]|[staff_id1]||[comment1]||[date1]|[staff_id1]||[comment1]
	 *
	 * to array of comments:
	 * array(
	 * 		'date' => 'date1',
	 * 		'staff_id' => 'staff_id1',
	 * 		'comment' => 'comment1',
	 * )
	 */
	$comments_RET = DBGet( "SELECT SYEAR, MARKING_PERIOD_ID, STUDENT_ID, COMMENT
		FROM student_mp_comments
		WHERE COMMENT IS NOT NULL
		AND COMMENT!=''" );

	if ( is_array( $comments_RET )
		&& ! unserialize( $comments_RET[0]['COMMENT'] ) )
	{
		$ser_comments = [];

		$SQL_updt_coms = '';

		foreach ( $comments_RET as $comment )
		{
			$coms = explode( '||', $comment['COMMENT'] );
			$ser_coms = [];
			$i = 0;

			foreach ( $coms as $com )
			{
				if ( is_array( list( $date, $staff_id ) = explode( '|', $com ) )
					&& (int) $staff_id > 0 )
				{
					$ser_coms[ $i ]['date'] = $date;
					$ser_coms[ $i ]['staff_id'] = $staff_id;
				}
				else
				{
					$ser_coms[ $i ]['comment'] = $com;
					$i++;
				}
			}

			$ser_coms = DBEscapeString( serialize( array_reverse( $ser_coms ) ) );

			$SQL_updt_coms .= "UPDATE student_mp_comments
				SET COMMENT='" . $ser_coms . "'
				WHERE SYEAR='" . $comment['SYEAR'] . "'
				AND MARKING_PERIOD_ID='" . $comment['MARKING_PERIOD_ID'] . "'
				AND STUDENT_ID='" . $comment['STUDENT_ID'] . "';";
		}

		if ( $SQL_updt_coms )
		{
			DBGet( $SQL_updt_coms );
		}
	}
	else
		$return = false;


	// 5. Create school_fields_seq Sequence.
	$sequence_exists = DBGet( "SELECT 1 FROM pg_class
		WHERE relname = 'school_fields_seq'" );

	if ( ! $sequence_exists )
	{
		DBQuery( "CREATE SEQUENCE school_fields_seq
		START WITH 1
		INCREMENT BY 1
		NO MINVALUE
		NO MAXVALUE
		CACHE 1;

		SELECT pg_catalog.setval('school_fields_seq', 99, true);" );
	}


	/**
	 * 6. Add student_assignments table (& its composite primary key)
	 * & add SUBMISSION column to gradebook_assignments table
	 * & add StudentAssignments.php to profile_exceptions table.
	 */
	DBQuery( "CREATE TABLE IF NOT EXISTS student_assignments (
		assignment_id numeric NOT NULL,
		student_id numeric NOT NULL,
		data text
	);");

	$sa_constraint_exists = DBGet( "SELECT 1
		FROM information_schema.constraint_column_usage
		WHERE table_name = 'student_assignments'
		AND constraint_name = 'student_assignments_pkey'" );

	if ( ! $sa_constraint_exists )
	{
		DBQuery( "ALTER TABLE ONLY student_assignments
			ADD CONSTRAINT student_assignments_pkey PRIMARY KEY (assignment_id, student_id);" );
	}

	$submission_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'gradebook_assignments')
		AND attname = 'submission';" );

	if ( ! $submission_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY gradebook_assignments
			ADD COLUMN submission character varying(1);" );
	}

	$sa_exceptions_exists = DBGet( "SELECT 1
		FROM profile_exceptions
		WHERE profile_id IN (0,3)
		AND modname='Grades/StudentAssignments.php'" );

	if ( ! $sa_exceptions_exists )
	{
		DBQuery( "INSERT INTO profile_exceptions VALUES (0, 'Grades/StudentAssignments.php', 'Y', NULL);
			INSERT INTO profile_exceptions VALUES (3, 'Grades/StudentAssignments.php', 'Y', NULL);" );
	}

	return $return;
}


/**
 * Update to version 2.9.2
 *
 * 1. Add GP_PASSING_VALUE to report_card_grade_scales table
 *
 * Local function
 *
 * @since 2.9.2
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update292()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;


	/**
	 * 1. Add GP_PASSING_VALUE to report_card_grade_scales table
	 * & Set minimum passing grade to '0' for already present scales.
	 */
	$gppassingvalue_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'report_card_grade_scales')
		AND attname = 'gp_passing_value';" );

	if ( ! $gppassingvalue_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY report_card_grade_scales
			ADD COLUMN gp_passing_value numeric(10,3);
			UPDATE report_card_grade_scales
			SET gp_passing_value=0;" );
	}

	return $return;
}


/**
 * Update to version 2.9.5
 *
 * 1. Add LIMIT_EXISTING_CONTACTS_ADDRESSES to config table.
 *
 * Local function
 *
 * @since 2.9.5
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update295()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;


	/**
	 * 1. Add LIMIT_EXISTING_CONTACTS_ADDRESSES to config table.
	 */
	$limit_existing_contacts_addresses_field_added = DBGet( "SELECT 1 FROM config
		WHERE TITLE='LIMIT_EXISTING_CONTACTS_ADDRESSES'" );

	if ( ! $limit_existing_contacts_addresses_field_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'LIMIT_EXISTING_CONTACTS_ADDRESSES', NULL);" );
	}

	return $return;
}


/**
 * Update to version 2.9.12
 *
 * 1. Add THEME_FORCE to config table.
 *
 * Local function
 *
 * @since 2.9.12
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update2912()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add THEME_FORCE to config table.
	 */
	$theme_force_field_added = DBGet( "SELECT 1 FROM config
		WHERE TITLE='THEME_FORCE'" );

	if ( ! $theme_force_field_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'THEME_FORCE', NULL);" );
	}

	return $return;
}


/**
 * Update to version 2.9.13
 *
 * Admin Schools restriction.
 * 1. Add Users/User.php&category_id=1&schools to profile_exceptions table.
 * 2. Add Users/User.php&category_id=1&schools to staff_exceptions table.
 *
 * Local function
 *
 * @since 2.9.13
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update2913()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add Users/User.php&category_id=1&schools to profile_exceptions table.
	 */
	$admin_profiles_RET = DBGet( "SELECT id
		FROM profile_exceptions, user_profiles
		WHERE profile='admin'" );

	foreach ( (array) $admin_profiles_RET as $admin_profile )
	{
		$profile_id = $admin_profile['ID'];

		$as_profile_exceptions_exists = DBGet( "SELECT 1
			FROM profile_exceptions
			WHERE profile_id='" . $profile_id . "'
			AND modname='Users/User.php&category_id=1&schools'" );

		if ( ! $as_profile_exceptions_exists )
		{
			DBQuery( "INSERT INTO profile_exceptions
				VALUES ('" . $profile_id . "', 'Users/User.php&category_id=1&schools', 'Y', 'Y');" );
		}
	}

	/**
	 * 2. Add Users/User.php&category_id=1&schools to staff_exceptions table.
	 */
	$as_staff_exceptions_exists = DBGet( "SELECT 1
		FROM staff_exceptions
		WHERE modname='Users/User.php&category_id=1&schools'" );

	// Check if we have staff_exceptions.
	$staff_exceptions_user_ids = DBGet( "SELECT user_id
		FROM staff_exceptions
		WHERE modname='Users/User.php&category_id=1'" );

	if ( ! $as_staff_exceptions_exists
		&& $staff_exceptions_user_ids )
	{
		foreach ( (array) $staff_exceptions_user_ids as $user_id )
		{
			DBQuery( "INSERT INTO staff_exceptions
				VALUES ('" . $user_id['USER_ID'] . "', 'Users/User.php&category_id=1&schools', 'Y', 'Y');" );
		}
	}

	return $return;
}


/**
 * Update to version 2.9.14
 *
 * Add School Field types.
 * 1. Add SELECT_OPTIONS column to school_fields table.
 * Admin User Profile restriction.
 * 2. Add Users/User.php&category_id=1&user_profile to profile_exceptions table.
 * 3. Add Users/User.php&category_id=1&user_profile to staff_exceptions table.
 *
 * Local function
 *
 * @since 2.9.14
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update2914()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add SELECT_OPTIONS column to school_fields table.
	 */
	$select_options_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'school_fields')
		AND attname = 'select_options';" );

	if ( ! $select_options_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY school_fields
			ADD COLUMN select_options character varying(10000);" );
	}

	/**
	 * 2. Add Users/User.php&category_id=1&user_profile to profile_exceptions table.
	 */
	$admin_profiles_RET = DBGet( "SELECT id
		FROM user_profiles
		WHERE profile='admin'" );

	foreach ( (array) $admin_profiles_RET as $admin_profile )
	{
		$profile_id = $admin_profile['ID'];

		$up_profile_exceptions_exists = DBGet( "SELECT 1
			FROM profile_exceptions
			WHERE profile_id='" . $profile_id . "'
			AND modname='Users/User.php&category_id=1&user_profile'" );

		if ( ! $up_profile_exceptions_exists )
		{
			DBQuery( "INSERT INTO profile_exceptions
				VALUES ('" . $profile_id . "', 'Users/User.php&category_id=1&user_profile', 'Y', 'Y');" );
		}
	}

	/**
	 * 3. Add Users/User.php&category_id=1&user_profile to staff_exceptions table.
	 */
	$up_staff_exceptions_exists = DBGet( "SELECT 1
		FROM staff_exceptions
		WHERE modname='Users/User.php&category_id=1&user_profile'" );

	// Check if we have staff_exceptions.
	$staff_exceptions_user_ids = DBGet( "SELECT user_id
		FROM staff_exceptions
		WHERE modname='Users/User.php&category_id=1'" );

	if ( ! $up_staff_exceptions_exists
		&& $staff_exceptions_user_ids )
	{
		foreach ( (array) $staff_exceptions_user_ids as $user_id )
		{
			DBQuery( "INSERT INTO staff_exceptions
				VALUES ('" . $user_id['USER_ID'] . "', 'Users/User.php&category_id=1&user_profile', 'Y', 'Y');" );
		}
	}

	return $return;
}


/**
 * Update to version 3.0
 *
 * Add Access Log.
 * 1. Add access_log table.
 * Will not grant access to the program to Admins.
 * Go to Users > User Profiles for that.
 *
 * Local function
 *
 * @since 3.0
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update30()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add access_log table.
	 */
	$access_log_table_exists = DBGet( "SELECT 1
		FROM pg_catalog.pg_tables
		WHERE tablename  = 'access_log'" );

	if ( ! $access_log_table_exists )
	{
		DBQuery( "CREATE TABLE access_log (
			syear numeric(4,0),
			username character varying(100),
			profile character varying(30),
			login_time timestamp(0) without time zone,
			ip_address character varying(50),
			user_agent text,
			status character varying(50)
		);" );
	} else {

		// Add user_agent column.
		$user_agent_column_exists = DBGet( "SELECT 1 FROM pg_attribute
			WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'access_log')
			AND attname = 'user_agent';" );

		if ( ! $user_agent_column_exists )
		{
			DBQuery( "ALTER TABLE ONLY access_log
				ADD COLUMN user_agent text;" );
		}
	}

	return $return;
}


/**
 * Update to version 3.1
 *
 * Fix SQL error when entering (Unweighted) GPA Value > 99.99
 * 1. report_card_grades table:
 * Change gpa_value & unweighted_gp columns type to numeric
 *
 * 2. report_card_grade_scales table:
 * Change hhr_gpa_value & hr_gpa_value & hrs_gpa_value columns type to numeric
 * Was numeric(4,2) which would prevent to enter values like 100 (or above).
 *
 * Add Mass Create Assignments program.
 * 3. Add Grades/MassCreateAssignments.php to profile_exceptions table.
 *
 * Local function
 *
 * @since 3.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update31()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. report_card_grades table:
	 * Change gpa_value & unweighted_gp columns type to numeric
	 * Was numeric(4,2) which would prevent to enter values like 100.
	 */
	DBQuery( "ALTER TABLE report_card_grades
		ALTER COLUMN gpa_value TYPE numeric;" );

	DBQuery( "ALTER TABLE report_card_grades
		ALTER COLUMN unweighted_gp TYPE numeric;" );

	/**
	 * 2. report_card_grade_scales table:
	 * Change hhr_gpa_value & hr_gpa_value & hrs_gpa_value columns type to numeric
	 * Was numeric(4,2) which would prevent to enter values like 100 (or above).
	 */
	DBQuery( "ALTER TABLE report_card_grade_scales
		ALTER COLUMN hhr_gpa_value TYPE numeric;" );

	DBQuery( "ALTER TABLE report_card_grade_scales
		ALTER COLUMN hr_gpa_value TYPE numeric;" );

	DBQuery( "ALTER TABLE report_card_grade_scales
		ALTER COLUMN hrs_gpa_value TYPE numeric;" );

	/**
	 * 3. Add Grades/MassCreateAssignments.php to profile_exceptions table.
	 */
	$admin_profiles_RET = DBGet( "SELECT id
		FROM user_profiles
		WHERE profile='admin'" );

	foreach ( (array) $admin_profiles_RET as $admin_profile )
	{
		$profile_id = $admin_profile['ID'];

		$mca_profile_exceptions_exists = DBGet( "SELECT 1
			FROM profile_exceptions
			WHERE profile_id='" . $profile_id . "'
			AND modname='Grades/MassCreateAssignments.php'" );

		if ( ! $mca_profile_exceptions_exists )
		{
			DBQuery( "INSERT INTO profile_exceptions
				VALUES ('" . $profile_id . "', 'Grades/MassCreateAssignments.php', 'Y', 'Y');" );
		}
	}

	return $return;
}


/**
 * Update to version 3.5
 *
 * 1. Add FAILED_LOGIN_LIMIT to config table
 *
 * Local function
 *
 * @since 3.5
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update35()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add FAILED_LOGIN_LIMIT to config table.
	 */
	$failed_login_limit_added = DBGet( "SELECT 1 FROM config
		WHERE TITLE='FAILED_LOGIN_LIMIT'" );

	if ( ! $failed_login_limit_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'FAILED_LOGIN_LIMIT', NULL);" );
	}

	return $return;
}


/**
 * Update to version 3.7-beta
 *
 * 1. Add DISPLAY_NAME to config table
 *
 * Local function
 *
 * @since 3.7
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update37beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add DISPLAY_NAME to config table.
	 */
	$display_name_added = DBGet( "SELECT 1 FROM config WHERE TITLE='DISPLAY_NAME'" );

	if ( ! $display_name_added )
	{
		// Fix empty string to NULL using Posix escape string syntax (E + backslash).
		DBQuery( "INSERT INTO config VALUES (0, 'DISPLAY_NAME', E'FIRST_NAME||coalesce(\' \'||MIDDLE_NAME||\' \',\' \')||LAST_NAME');" );
	}

	return $return;
}


/**
 * Update to version 3.9
 *
 * 1. Add DISPLAY_NAME to config table for every school.
 *
 * Local function
 *
 * @since 3.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update39()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add DISPLAY_NAME to config table for every school.
	 */
	$display_name_added = DBGet( "SELECT 1 FROM config WHERE TITLE='DISPLAY_NAME'
		AND SCHOOL_ID<>0" );

	if ( ! $display_name_added )
	{
		// Fix empty string to NULL using Posix escape string syntax (E + backslash).
		DBQuery( "INSERT INTO config SELECT DISTINCT ID, 'DISPLAY_NAME', E'FIRST_NAME||coalesce(\' \'||MIDDLE_NAME||\' \',\' \')||LAST_NAME' FROM schools;" );
	}

	return $return;
}
