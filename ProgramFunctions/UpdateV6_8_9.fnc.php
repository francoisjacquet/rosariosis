<?php
/**
 * Update functions for versions 6 to 9
 *
 * Incremental updates
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Update to version 6.3
 *
 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to config table.
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
	 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to config table.
	 */
	$default_school_added = DBGetOne( "SELECT 1 FROM config
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
 * 1. course_periods table: Add SECONDARY_TEACHER_ID column.
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
	 * 1. course_periods table: Add SECONDARY_TEACHER_ID column.
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
 * 1. accounting_salaries table: Add FILE_ATTACHED column.
 * 2. billing_fees table: Add FILE_ATTACHED column.
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
	 * 1. accounting_salaries table: Add FILE_ATTACHED column.
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
	 * 2. billing_fees table: Add FILE_ATTACHED column.
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
 * 1. accounting_payments table: Add FILE_ATTACHED column.
 * 2. billing_payments table: Add FILE_ATTACHED column.
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
	 * 1. accounting_payments table: Add FILE_ATTACHED column.
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
	 * 2. billing_payments table: Add FILE_ATTACHED column.
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
 * 1. gradebook_grades table: Change comment column type to text.
 * 2. accounting_incomes table: Add FILE_ATTACHED column.
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
	 * 1. gradebook_grades table:
	 * Change comment column type to text
	 * Was character varying(100) which was too short for teachers.
	 */
	DBQuery( "ALTER TABLE gradebook_grades
		ALTER COLUMN comment TYPE text;" );

	/**
	 * 2. accounting_incomes table: Add FILE_ATTACHED column.
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
 * 1. profile_exceptions table: Add Admin Student Payments Delete restriction.
 * 2. staff_exceptions table: Add Admin Student Payments Delete restriction.
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
	 * 1. profile_exceptions table
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
	 * 2. staff_exceptions table
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
 * 1. Fix SQL transcript_grades view, grades were duplicated for each school year
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

	// 1. Fix SQL transcript_grades view, grades were duplicated for each school year.
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

/**
 * Update to version 9.2
 *
 * 1. Drop transcript_grades view, so we can alter student_report_card_grades table
 * 2. SQL student_report_card_grades table: convert MARKING_PERIOD_ID column to integer
 * 3. Recreate transcript_grades view
 *
 * Local function
 *
 * @since 9.2
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update92()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	// 1. Drop transcript_grades view, so we can alter student_report_card_grades table
	DBQuery( "DROP VIEW transcript_grades;" );

	// 2. SQL student_report_card_grades table: convert MARKING_PERIOD_ID column to integer
	DBQuery( "ALTER TABLE student_report_card_grades
	ALTER marking_period_id TYPE integer USING marking_period_id::integer;" );

	// 3. Recreate transcript_grades view
	DBQuery( "CREATE VIEW transcript_grades AS
	SELECT mp.syear,mp.school_id,mp.marking_period_id,mp.mp_type,
	mp.short_name,mp.parent_id,mp.grandparent_id,
	(SELECT mp2.end_date
		FROM student_report_card_grades
			JOIN marking_periods mp2
			ON mp2.marking_period_id = student_report_card_grades.marking_period_id
		WHERE student_report_card_grades.student_id = sms.student_id
		AND (student_report_card_grades.marking_period_id = mp.parent_id
			OR student_report_card_grades.marking_period_id = mp.grandparent_id)
		AND student_report_card_grades.course_title = srcg.course_title
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
		ON mp.marking_period_id = srcg.marking_period_id
		JOIN student_mp_stats sms
		ON sms.marking_period_id = mp.marking_period_id
			AND sms.student_id = srcg.student_id
		LEFT OUTER JOIN schools
		ON mp.school_id = schools.id
			AND (mp.mp_source<>'History' AND mp.syear = schools.syear)
				OR (mp.mp_source='History' AND mp.syear=(SELECT syear FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))
	ORDER BY srcg.course_period_id;" );

	return $return;
}


/**
 * Update to version 9.2.1
 *
 * 1. SQL set default nextval (auto increment) for RosarioSIS version < 5.0 on install,
 * serial column (auto increment was implemented in RosarioSIS 5.0)
 * 2. SQL set default nextval (auto increment) for old add-on modules.
 *
 * Local function
 *
 * @since 9.2.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update921()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	$set_default_nextval = function( $table, $id_column, $sequence )
	{
		if ( strlen( $sequence) > 63 )
		{
			$cut_at_char = ( 63 - strlen( '_seq' ) );

			// Note: sequence name is limited to 63 chars
			// @link https://www.postgresql.org/docs/9.0/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
			$sequence = substr( $sequence, 0, $cut_at_char ) . '_seq';
		}

		$sequence_exists = DBGetOne( "SELECT 1 FROM pg_class
			WHERE relname='" . DBEscapeString( $sequence ) . "';" );

		if ( $sequence_exists )
		{
			DBQuery( "ALTER TABLE " . DBEscapeIdentifier( $table ) .
				" ALTER COLUMN " . DBEscapeIdentifier( $id_column ) .
				" SET DEFAULT NEXTVAL('" . DBEscapeString( $sequence ) . "');" );
		}
	};


	/**
	 * 1. Set default nextval (auto increment) for RosarioSIS version < 5.0 on install,
	 * serial column (auto increment was implemented in RosarioSIS 5.0)
	 */
	$set_default_nextval( 'user_profiles', 'id', 'user_profiles_id_seq' );
	$set_default_nextval( 'students_join_people', 'id', 'students_join_people_id_seq' );
	$set_default_nextval( 'students_join_address', 'id', 'students_join_address_id_seq' );
	$set_default_nextval( 'students', 'student_id', 'students_student_id_seq' );
	$set_default_nextval( 'student_report_card_grades', 'id', 'student_report_card_grades_id_seq' );
	$set_default_nextval( 'student_medical_visits', 'id', 'student_medical_visits_id_seq' );
	$set_default_nextval( 'student_medical_alerts', 'id', 'student_medical_alerts_id_seq' );
	$set_default_nextval( 'student_medical', 'id', 'student_medical_id_seq' );
	$set_default_nextval( 'student_field_categories', 'id', 'student_field_categories_id_seq' );
	$set_default_nextval( 'student_enrollment_codes', 'id', 'student_enrollment_codes_id_seq' );
	$set_default_nextval( 'student_enrollment', 'id', 'student_enrollment_id_seq' );
	$set_default_nextval( 'staff_fields', 'id', 'staff_fields_id_seq' );
	$set_default_nextval( 'staff_field_categories', 'id', 'staff_field_categories_id_seq' );
	$set_default_nextval( 'staff', 'staff_id', 'staff_staff_id_seq' );
	$set_default_nextval( 'school_periods', 'period_id', 'school_periods_period_id_seq' );
	$set_default_nextval( 'schools', 'id', 'schools_id_seq' );
	$set_default_nextval( 'school_gradelevels', 'id', 'school_gradelevels_id_seq' );
	$set_default_nextval( 'school_fields', 'id', 'school_fields_id_seq' );
	$set_default_nextval( 'schedule_requests', 'request_id', 'schedule_requests_request_id_seq' );
	$set_default_nextval( 'resources', 'id', 'resources_id_seq' );
	$set_default_nextval( 'report_card_grades', 'id', 'report_card_grades_id_seq' );
	$set_default_nextval( 'report_card_grade_scales', 'id', 'report_card_grade_scales_id_seq' );
	$set_default_nextval( 'report_card_comments', 'id', 'report_card_comments_id_seq' );
	$set_default_nextval( 'report_card_comment_codes', 'id', 'report_card_comment_codes_id_seq' );
	$set_default_nextval( 'report_card_comment_code_scales', 'id', 'report_card_comment_code_scales_id_seq' );
	$set_default_nextval( 'report_card_comment_categories', 'id', 'report_card_comment_categories_id_seq' );
	$set_default_nextval( 'portal_polls', 'id', 'portal_polls_id_seq' );
	$set_default_nextval( 'portal_poll_questions', 'id', 'portal_poll_questions_id_seq' );
	$set_default_nextval( 'portal_notes', 'id', 'portal_notes_id_seq' );
	$set_default_nextval( 'people_join_contacts', 'id', 'people_join_contacts_id_seq' );
	$set_default_nextval( 'people_fields', 'id', 'people_fields_id_seq' );
	$set_default_nextval( 'people_field_categories', 'id', 'people_field_categories_id_seq' );
	$set_default_nextval( 'people', 'person_id', 'people_person_id_seq' );
	$set_default_nextval( 'school_marking_periods', 'marking_period_id', 'school_marking_periods_marking_period_id_seq' );
	$set_default_nextval( 'gradebook_assignments', 'assignment_id', 'gradebook_assignments_assignment_id_seq' );
	$set_default_nextval( 'gradebook_assignment_types', 'assignment_type_id', 'gradebook_assignment_types_assignment_type_id_seq' );
	$set_default_nextval( 'food_service_transactions', 'transaction_id', 'food_service_transactions_transaction_id_seq' );
	$set_default_nextval( 'food_service_staff_transactions', 'transaction_id', 'food_service_staff_transactions_transaction_id_seq' );
	$set_default_nextval( 'food_service_menus', 'menu_id', 'food_service_menus_menu_id_seq' );
	$set_default_nextval( 'food_service_menu_items', 'menu_item_id', 'food_service_menu_items_menu_item_id_seq' );
	$set_default_nextval( 'food_service_items', 'item_id', 'food_service_items_item_id_seq' );
	$set_default_nextval( 'food_service_categories', 'category_id', 'food_service_categories_category_id_seq' );
	$set_default_nextval( 'eligibility_activities', 'id', 'eligibility_activities_id_seq' );
	$set_default_nextval( 'discipline_referrals', 'id', 'discipline_referrals_id_seq' );
	$set_default_nextval( 'discipline_fields', 'id', 'discipline_fields_id_seq' );
	$set_default_nextval( 'discipline_field_usage', 'id', 'discipline_field_usage_id_seq' );
	$set_default_nextval( 'custom_fields', 'id', 'custom_fields_id_seq' );
	$set_default_nextval( 'course_subjects', 'subject_id', 'course_subjects_subject_id_seq' );
	$set_default_nextval( 'course_period_school_periods', 'course_period_school_periods_id', 'course_period_school_periods_course_period_school_periods_id_seq' );
	$set_default_nextval( 'courses', 'course_id', 'courses_course_id_seq' );
	$set_default_nextval( 'course_periods', 'course_period_id', 'course_periods_course_period_id_seq' );
	$set_default_nextval( 'calendar_events', 'id', 'calendar_events_id_seq' );
	$set_default_nextval( 'billing_payments', 'id', 'billing_payments_id_seq' );
	$set_default_nextval( 'billing_fees', 'id', 'billing_fees_id_seq' );
	$set_default_nextval( 'attendance_codes', 'id', 'attendance_codes_id_seq' );
	$set_default_nextval( 'attendance_code_categories', 'id', 'attendance_code_categories_id_seq' );
	$set_default_nextval( 'attendance_calendars', 'calendar_id', 'attendance_calendars_calendar_id_seq' );
	$set_default_nextval( 'address_fields', 'id', 'address_fields_id_seq' );
	$set_default_nextval( 'address_field_categories', 'id', 'address_field_categories_id_seq' );
	$set_default_nextval( 'address', 'address_id', 'address_address_id_seq' );
	$set_default_nextval( 'accounting_payments', 'id', 'accounting_payments_id_seq' );
	$set_default_nextval( 'accounting_salaries', 'id', 'accounting_salaries_id_seq' );
	$set_default_nextval( 'accounting_incomes', 'id', 'accounting_incomes_id_seq' );

	/**
	 * 2. Set default nextval (auto increment) for old add-on modules.
	 */
	$set_default_nextval( 'billing_fees_monthly', 'id', 'billing_fees_monthly_id_seq' );
	$set_default_nextval( 'school_inventory_categories', 'category_id', 'school_inventory_categories_category_id_seq' );
	$set_default_nextval( 'school_inventory_items', 'item_id', 'school_inventory_items_item_id_seq' );
	$set_default_nextval( 'saved_reports', 'id', 'saved_reports_id_seq' );
	$set_default_nextval( 'saved_calculations', 'id', 'saved_calculations_id_seq' );
	$set_default_nextval( 'messages', 'message_id', 'messages_message_id_seq' );

	return $return;
}


/**
 * Update to version 9.3
 *
 * 1. config table: update DISPLAY_NAME to use CONCAT() instead of pipes ||.
 *
 * Local function
 *
 * @since 9.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update93()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. config table: update DISPLAY_NAME to use CONCAT() instead of pipes ||.
	 */
	$display_names_update = [
		"FIRST_NAME||' '||LAST_NAME" => "CONCAT(FIRST_NAME,' ',LAST_NAME)",
		"FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,' ')" => "CONCAT(FIRST_NAME,' ',LAST_NAME,coalesce(NULLIF(CONCAT(' ',NAME_SUFFIX),' '),''))",
		"FIRST_NAME||coalesce(' '||MIDDLE_NAME||' ',' ')||LAST_NAME" => "CONCAT(FIRST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME,' '),'  '),' '),LAST_NAME)",
		"FIRST_NAME||', '||LAST_NAME||coalesce(' '||MIDDLE_NAME,' ')" => "CONCAT(FIRST_NAME,', ',LAST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME),' '),''))",
		"LAST_NAME||' '||FIRST_NAME" => "CONCAT(LAST_NAME,' ',FIRST_NAME)",
		"LAST_NAME||', '||FIRST_NAME" => "CONCAT(LAST_NAME,', ',FIRST_NAME)",
		"LAST_NAME||', '||FIRST_NAME||' '||COALESCE(MIDDLE_NAME,' ')" => "CONCAT(LAST_NAME,', ',FIRST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME),' '),''))",
		"LAST_NAME||coalesce(' '||MIDDLE_NAME||' ',' ')||FIRST_NAME" => "CONCAT(LAST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME,' '),'  '),' '),FIRST_NAME)",
	];

	$display_name_sql = '';

	foreach ( $display_names_update as $display_name_pipes => $display_name_concat )
	{
		$display_name_sql .= "UPDATE config SET CONFIG_VALUE='" . DBEscapeString( $display_name_concat ) . "'
			WHERE CONFIG_VALUE='" . DBEscapeString( $display_name_pipes ) . "'
			AND TITLE='DISPLAY_NAME';";
	}

	DBQuery( $display_name_sql );

	return $return;
}
