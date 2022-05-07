<?php
/**
 * Update functions
 *
 * Incremental updates
 *
 * Update() function called if ROSARIO_VERSION != version in DB
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Update manager function
 *
 * Call the specific versions functions
 *
 * @since 2.9
 *
 * @return boolean false if wrong version or update failed, else true
 */
function Update()
{
	$from_version = Config( 'VERSION' );

	$to_version = ROSARIO_VERSION;

	/**
	 * Check if Update() version < ROSARIO_VERSION.
	 *
	 * Prevent DB version update if new Update.fnc.php file has NOT been uploaded YET.
	 * Update must be run once both new Warehouse.php & Update.fnc.php files are uploaded.
	 */
	if ( version_compare( '8.9.6', ROSARIO_VERSION, '<' ) )
	{
		return false;
	}

	// Check if version in DB >= ROSARIO_VERSION.
	if ( version_compare( $from_version, $to_version, '>=' ) )
	{
		return false;
	}

	require_once 'ProgramFunctions/UpdateV2_3.fnc.php';
	require_once 'ProgramFunctions/UpdateV4_5.fnc.php';

	$return = true;

	switch ( true )
	{
		case version_compare( $from_version, '2.9-alpha', '<' ) :

			$return = _update29alpha();

		case version_compare( $from_version, '2.9.2', '<' ) :

			$return = _update292();

		case version_compare( $from_version, '2.9.5', '<' ) :

			$return = _update295();

		case version_compare( $from_version, '2.9.12', '<' ) :

			$return = _update2912();

		case version_compare( $from_version, '2.9.13', '<' ) :

			$return = _update2913();

		case version_compare( $from_version, '2.9.14', '<' ) :

			$return = _update2914();

		case version_compare( $from_version, '3.0', '<' ) :

			$return = _update30();

		case version_compare( $from_version, '3.1', '<' ) :

			$return = _update31();

		case version_compare( $from_version, '3.5', '<' ) :

			$return = _update35();

		case version_compare( $from_version, '3.7-beta', '<' ) :

			$return = _update37beta();

		case version_compare( $from_version, '3.9', '<' ) :

			$return = _update39();

		case version_compare( $from_version, '4.0-beta', '<' ) :

			$return = _update40beta();

		case version_compare( $from_version, '4.2-beta', '<' ) :

			$return = _update42beta();

		case version_compare( $from_version, '4.3-beta', '<' ) :

			$return = _update43beta();

		case version_compare( $from_version, '4.4-beta', '<' ) :

			$return = _update44beta();

		case version_compare( $from_version, '4.4-beta2', '<' ) :

			$return = _update44beta2();

		case version_compare( $from_version, '4.5-beta2', '<' ) :

			$return = _update45beta2();

		case version_compare( $from_version, '4.6-beta', '<' ) :

			$return = _update46beta();

		case version_compare( $from_version, '4.7-beta', '<' ) :

			$return = _update47beta();

		case version_compare( $from_version, '4.7-beta2', '<' ) :

			$return = _update47beta2();

		case version_compare( $from_version, '4.9-beta', '<' ) :

			$return = _update49beta();

		case version_compare( $from_version, '5.0-beta', '<' ) :

			$return = _update50beta();

		case version_compare( $from_version, '5.0.1', '<' ) :

			$return = _update501();

		case version_compare( $from_version, '5.2-beta', '<' ) :

			$return = _update52beta();

		case version_compare( $from_version, '5.3-beta', '<' ) :

			$return = _update53beta();

		case version_compare( $from_version, '5.4.1', '<' ) :

			$return = _update541();

		case version_compare( $from_version, '5.4.2', '<' ) :

			$return = _update542();

		case version_compare( $from_version, '5.5-beta3', '<' ) :

			$return = _update55beta3();

		case version_compare( $from_version, '5.7', '<' ) :

			$return = _update57();

		case version_compare( $from_version, '5.8-beta5', '<' ) :

			$return = _update58beta5();

		case version_compare( $from_version, '5.9-beta', '<' ) :

			$return = _update59beta();

		case version_compare( $from_version, '5.9-beta2', '<' ) :

			$return = _update59beta2();

		case version_compare( $from_version, '5.9', '<' ) :

			$return = _update59();

		case version_compare( $from_version, '5.9.1', '<' ) :

			$return = _update591();

		case version_compare( $from_version, '6.3', '<' ) :

			$return = _update63();

		case version_compare( $from_version, '6.6', '<' ) :

			$return = _update66();

		case version_compare( $from_version, '6.9-beta', '<' ) :

			$return = _update69beta();

		case version_compare( $from_version, '8.1', '<' ) :

			$return = _update81();

		case version_compare( $from_version, '8.3', '<' ) :

			$return = _update83();

		case version_compare( $from_version, '8.4', '<' ) :

			$return = _update84();

		case version_compare( $from_version, '8.5', '<' ) :

			$return = _update85();

		case version_compare( $from_version, '8.7', '<' ) :

			$return = _update87();
	}

	// Update version in DB CONFIG table.
	Config( 'VERSION', ROSARIO_VERSION );

	return $return;
}


/**
 * Is function called by Update()?
 *
 * Local function
 *
 * @example _isCallerUpdate( debug_backtrace() );
 *
 * @since 2.9.13
 *
 * @param  array   $callers debug_backtrace().
 *
 * @return boolean          Exit with error message if not called by Update().
 */
function _isCallerUpdate( $callers )
{
	if ( ! isset( $callers[1]['function'] )
		|| $callers[1]['function'] !== 'Update' )
	{
		exit( 'Error: the update functions must be called by Update() only!' );
	}

	return true;
}


/**
 * Update to version 6.3
 *
 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to CONFIG table.
 *
 * Local function
 *
 * @since 6.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update63()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to CONFIG table.
	 */
	$default_school_added = DBGetOne( "SELECT 1 FROM CONFIG
		WHERE TITLE='CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL'" );

	if ( ! $default_school_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL', NULL);" );
	}

	return $return;
}

/**
 * Update to version 6.6
 *
 * Add Registration program for Administrators.
 * 1. Add Custom/Registration.php to profile_exceptions table.
 *
 * Local function
 *
 * @since 6.6
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update66()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add Custom/Registration.php to profile_exceptions table.
	 */
	$admin_profiles_RET = DBGet( "SELECT id
		FROM user_profiles
		WHERE profile='admin'" );

	foreach ( (array) $admin_profiles_RET as $admin_profile )
	{
		$profile_id = $admin_profile['ID'];

		$registration_profile_exceptions_exists = DBGet( "SELECT 1
			FROM profile_exceptions
			WHERE profile_id='" . $profile_id . "'
			AND modname='Custom/Registration.php'" );

		if ( ! $registration_profile_exceptions_exists )
		{
			DBQuery( "INSERT INTO profile_exceptions
				VALUES ('" . $profile_id . "', 'Custom/Registration.php', 'Y', 'Y');" );
		}
	}

	return $return;
}


/**
 * Update to version 6.9
 *
 * 1. COURSE_PERIODS table: Add SECONDARY_TEACHER_ID column.
 *
 * Local function
 *
 * @since 6.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update69beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. COURSE_PERIODS table: Add SECONDARY_TEACHER_ID column.
	 */
	$secondary_teacher_id_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='course_periods')
		AND attname='secondary_teacher_id';" );

	if ( ! $secondary_teacher_id_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY course_periods
			ADD COLUMN secondary_teacher_id integer REFERENCES staff(staff_id);" );
	}

	return $return;
}


/**
 * Update to version 8.1
 *
 * 1. ACCOUNTING_SALARIES table: Add FILE_ATTACHED column.
 * 2. BILLING_FEES table: Add FILE_ATTACHED column.
 *
 * Local function
 *
 * @since 8.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update81()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. ACCOUNTING_SALARIES table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='accounting_salaries')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY accounting_salaries
			ADD COLUMN file_attached text;" );
	}

	/**
	 * 2. BILLING_FEES table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='billing_fees')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY billing_fees
			ADD COLUMN file_attached text;" );
	}

	return $return;
}


/**
 * Update to version 8.3
 *
 * 1. ACCOUNTING_PAYMENTS table: Add FILE_ATTACHED column.
 * 2. BILLING_PAYMENTS table: Add FILE_ATTACHED column.
 *
 * Local function
 *
 * @since 8.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update83()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. ACCOUNTING_PAYMENTS table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='accounting_payments')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY accounting_payments
			ADD COLUMN file_attached text;" );
	}

	/**
	 * 2. BILLING_PAYMENTS table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='billing_payments')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY billing_payments
			ADD COLUMN file_attached text;" );
	}

	return $return;
}


/**
 * Update to version 8.4
 *
 * 1. GRADEBOOK_GRADES table: Change comment column type to text.
 * 2. ACCOUNTING_INCOMES table: Add FILE_ATTACHED column.
 *
 * Local function
 *
 * @since 8.4
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update84()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. GRADEBOOK_GRADES table:
	 * Change comment column type to text
	 * Was character varying(100) which was too short for teachers.
	 */
	DBQuery( "ALTER TABLE gradebook_grades
		ALTER COLUMN comment TYPE text;" );

	/**
	 * 2. ACCOUNTING_INCOMES table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='accounting_incomes')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY accounting_incomes
			ADD COLUMN file_attached text;" );
	}

	return $return;
}


/**
 * Update to version 8.5
 *
 * 1. PROFILE_EXCEPTIONS table: Add Admin Student Payments Delete restriction.
 * 2. STAFF_EXCEPTIONS table: Add Admin Student Payments Delete restriction.
 *
 * Local function
 *
 * @since 8.5
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update85()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. PROFILE_EXCEPTIONS table
	 * Add Admin Student Payments Delete restriction.
	 */
	DBQuery( "INSERT INTO profile_exceptions
		SELECT profile_id,'Student_Billing/StudentPayments.php&modfunc=remove','Y','Y'
		FROM profile_exceptions
		WHERE modname='Student_Billing/StudentPayments.php'
		AND can_edit='Y'
		AND profile_id NOT IN(SELECT profile_id
			FROM profile_exceptions
			WHERE modname='Student_Billing/StudentPayments.php&modfunc=remove');" );

	/**
	 * 2. STAFF_EXCEPTIONS table
	 * Add Admin Student Payments Delete restriction.
	 */
	DBQuery( "INSERT INTO staff_exceptions
		SELECT user_id,'Student_Billing/StudentPayments.php&modfunc=remove','Y','Y'
		FROM staff_exceptions
		WHERE modname='Student_Billing/StudentPayments.php'
		AND can_edit='Y'
		AND user_id NOT IN(SELECT user_id
			FROM staff_exceptions
			WHERE modname='Student_Billing/StudentPayments.php&modfunc=remove');" );

	return $return;
}

/**
 * Update to version 8.7
 *
 * 1. Fix SQL TRANSCRIPT_GRADES view, grades were duplicated for each school year
 *
 * Local function
 *
 * @since 8.7
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update87()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	// 1. Fix SQL TRANSCRIPT_GRADES view, grades were duplicated for each school year.
	DBQuery( "CREATE OR REPLACE VIEW transcript_grades AS
		SELECT mp.syear,mp.school_id,mp.marking_period_id,mp.mp_type,
		mp.short_name,mp.parent_id,mp.grandparent_id,
		(SELECT mp2.end_date
			FROM student_report_card_grades
				JOIN marking_periods mp2
				ON mp2.marking_period_id::text = student_report_card_grades.marking_period_id::text
			WHERE student_report_card_grades.student_id = sms.student_id::numeric
			AND (student_report_card_grades.marking_period_id::text = mp.parent_id::text
				OR student_report_card_grades.marking_period_id::text = mp.grandparent_id::text)
			AND student_report_card_grades.course_title::text = srcg.course_title::text
			ORDER BY mp2.end_date LIMIT 1) AS parent_end_date,
		mp.end_date,sms.student_id,
		(sms.cum_weighted_factor * COALESCE(schools.reporting_gp_scale, (SELECT reporting_gp_scale FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))) AS cum_weighted_gpa,
		(sms.cum_unweighted_factor * schools.reporting_gp_scale) AS cum_unweighted_gpa,
		sms.cum_rank,sms.mp_rank,sms.class_size,
		((sms.sum_weighted_factors / sms.count_weighted_factors) * schools.reporting_gp_scale) AS weighted_gpa,
		((sms.sum_unweighted_factors / sms.count_unweighted_factors) * schools.reporting_gp_scale) AS unweighted_gpa,
		sms.grade_level_short,srcg.comment,srcg.grade_percent,srcg.grade_letter,
		srcg.weighted_gp,srcg.unweighted_gp,srcg.gp_scale,srcg.credit_attempted,
		srcg.credit_earned,srcg.course_title,srcg.school AS school_name,
		schools.reporting_gp_scale AS school_scale,
		((sms.cr_weighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_weighted_gpa,
		((sms.cr_unweighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_unweighted_gpa,
		(sms.cum_cr_weighted_factor * schools.reporting_gp_scale) AS cum_cr_weighted_gpa,
		(sms.cum_cr_unweighted_factor * schools.reporting_gp_scale) AS cum_cr_unweighted_gpa,
		srcg.class_rank,sms.comments,
		srcg.credit_hours
		FROM marking_periods mp
			JOIN student_report_card_grades srcg
			ON mp.marking_period_id::text = srcg.marking_period_id::text
			JOIN student_mp_stats sms
			ON sms.marking_period_id::numeric = mp.marking_period_id
				AND sms.student_id::numeric = srcg.student_id
			LEFT OUTER JOIN schools
			ON mp.school_id = schools.id
				AND (mp.mp_source<>'History' AND mp.syear = schools.syear)
					OR (mp.mp_source='History' AND mp.syear=(SELECT syear FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))
		ORDER BY srcg.course_period_id;" );

	return $return;
}
