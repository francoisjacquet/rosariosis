<?php
/**
 * Update functions for versions 4 to 5
 *
 * Incremental updates
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Update to version 4.0
 *
 * 0. Create plpgsql language in case it does not exist.
 * 1. Fix SQL error in calc_gpa_mp function on INSERT Final Grades for students with various enrollment records.
 * enroll_grade view was returning various rows
 * while primary key contraints to a unique (student_id,marking_period_id) pair
 *
 * Local function
 *
 * @since 4.0
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update40beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	// 0. Create plpgsql language in case it does not exist.
	// 1. Fix SQL error in calc_gpa_mp function on INSERT Final Grades for students with various enrollment records.
	DBQuery( "CREATE FUNCTION create_language_plpgsql()
	RETURNS BOOLEAN AS $$
		CREATE LANGUAGE plpgsql;
		SELECT TRUE;
	$$ LANGUAGE SQL;

	SELECT CASE WHEN NOT
		(
			SELECT  TRUE AS exists
			FROM    pg_language
			WHERE   lanname = 'plpgsql'
			UNION
			SELECT  FALSE AS exists
			ORDER BY exists DESC
			LIMIT 1
		)
	THEN
		create_language_plpgsql()
	ELSE
		FALSE
	END AS plpgsql_created;

	DROP FUNCTION create_language_plpgsql();

	CREATE OR REPLACE FUNCTION calc_gpa_mp(integer, character varying) RETURNS integer AS $$
	DECLARE
		s_id ALIAS for $1;
		mp_id ALIAS for $2;
		oldrec student_mp_stats%ROWTYPE;
	BEGIN
	  SELECT * INTO oldrec FROM student_mp_stats WHERE student_id = s_id and cast(marking_period_id as text) = mp_id;

	  IF FOUND THEN
		UPDATE student_mp_stats SET
			sum_weighted_factors = rcg.sum_weighted_factors,
			sum_unweighted_factors = rcg.sum_unweighted_factors,
			cr_weighted_factors = rcg.cr_weighted,
			cr_unweighted_factors = rcg.cr_unweighted,
			gp_credits = rcg.gp_credits,
			cr_credits = rcg.cr_credits

		FROM (
		select
			sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors,
			sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors,
			sum(credit_attempted) as gp_credits,
			sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
			sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
			sum( case when class_rank = 'Y' THEN credit_attempted END) as cr_credits

			from student_report_card_grades where student_id = s_id
			and cast(marking_period_id as text) = mp_id
			 and not gp_scale = 0 group by student_id, marking_period_id
			) as rcg
	WHERE student_id = s_id and cast(marking_period_id as text) = mp_id;
		RETURN 1;
	ELSE
		INSERT INTO student_mp_stats (student_id, marking_period_id, sum_weighted_factors, sum_unweighted_factors, grade_level_short, cr_weighted_factors, cr_unweighted_factors, gp_credits, cr_credits)

			select
				srcg.student_id, (srcg.marking_period_id::text)::int,
				sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors,
				sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors,
				(select eg.short_name
					from enroll_grade eg, marking_periods mp
					where eg.student_id = s_id
					and eg.syear = mp.syear
					and eg.school_id = mp.school_id
					and eg.start_date <= mp.end_date
					and cast(mp.marking_period_id as text) = mp_id
					order by eg.start_date desc
					limit 1),
				sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
				sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
				sum(credit_attempted) as gp_credits,
				sum(case when class_rank = 'Y' THEN credit_attempted END) as cr_credits
			from student_report_card_grades srcg
			where srcg.student_id = s_id and cast(srcg.marking_period_id as text) = mp_id and not srcg.gp_scale = 0
			group by srcg.student_id, srcg.marking_period_id, short_name;
		END IF;
		RETURN 0;
	END
	$$
		LANGUAGE plpgsql;" );

	return $return;
}


/**
 * Update to version 4.2
 *
 * 1. config table:
 * Change config_value column type to text
 * Was character varying(2550) which could prevent saving rich text with base64 images
 * in case there is an issue with the image upload.
 *
 * Local function
 *
 * @since 4.2
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update42beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. config table:
	 * Change config_value column type to text
	 * Was character varying(2550) which could prevent saving rich text with base64 images
	 * in case there is an issue with the image upload.
	 */
	DBQuery( "ALTER TABLE config
		ALTER COLUMN config_value TYPE text;" );

	return $return;
}


/**
 * Update to version 4.3
 *
 * 1. courses table: Add DESCRIPTION column.
 *
 * Local function
 *
 * @since 4.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update43beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. courses table: Add DESCRIPTION column.
	 */
	$description_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'courses')
		AND attname = 'description';" );

	if ( ! $description_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY courses
			ADD COLUMN description text;" );
	}

	return $return;
}


/**
 * Update to version 4.4
 *
 * 1. gradebook_assignments table: Add FILE column.
 * 2. gradebook_assignments table: Change DESCRIPTION column type to text.
 * 3. gradebook_assignments table: Convert DESCRIPTION values from MarkDown to HTML.
 *
 * Local function
 *
 * @since 4.4
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update44beta()
{
	_isCallerUpdate( debug_backtrace() );

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	$return = true;

	/**
	 * 1. gradebook_assignments table:
	 * Add FILE column
	 */
	$file_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'gradebook_assignments')
		AND attname = 'file';" );

	if ( ! $file_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY gradebook_assignments
			ADD COLUMN file character varying(1000);" );
	}

	/**
	 * 2. gradebook_assignments table:
	 * Change DESCRIPTION column type to text
	 * Was character varying(1000) which could prevent saving rich text with base64 images
	 */
	DBQuery( "ALTER TABLE gradebook_assignments
		ALTER COLUMN description TYPE text;" );

	/**
	 * 3. gradebook_assignments table:
	 * Convert DESCRIPTION values from MarkDown to HTML.
	 */
	$assignments_RET = DBGet( "SELECT assignment_id,description
		FROM gradebook_assignments
		WHERE description IS NOT NULL;" );

	$assignment_update_sql = "UPDATE gradebook_assignments
		SET DESCRIPTION='%s'
		WHERE ASSIGNMENT_ID='%d';";

	$assignments_update_sql = '';

	foreach ( (array) $assignments_RET as $assignment )
	{
		$description_html = MarkDownToHTML( $assignment['DESCRIPTION'] );

		$assignments_update_sql .= sprintf(
			$assignment_update_sql,
			DBEscapeString( $description_html ),
			$assignment['ASSIGNMENT_ID']
		);
	}

	if ( $assignments_update_sql )
	{
		DBQuery( $assignments_update_sql );
	}

	return $return;
}


/**
 * Update to version 4.4
 *
 * 1. Add PASSWORD_STRENGTH to config table.
 *
 * Local function
 *
 * @since 4.4
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update44beta2()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add PASSWORD_STRENGTH to config table.
	 */
	$password_strength_added = DBGet( "SELECT 1 FROM config WHERE TITLE='PASSWORD_STRENGTH'" );

	if ( ! $password_strength_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'PASSWORD_STRENGTH', '1');" );
	}

	return $return;
}


/**
 * Update to version 4.5
 *
 * 1. gradebook_assignment_types table: Add CREATED_MP column.
 *
 * Local function
 *
 * @since 4.5
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update45beta2()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. gradebook_assignment_types table: Add CREATED_MP column.
	 */
	$created_at_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'gradebook_assignment_types')
		AND attname = 'created_mp';" );

	if ( ! $created_at_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY gradebook_assignment_types
			ADD COLUMN created_mp integer;" );
	}

	return $return;
}


/**
 * Update to version 4.6
 *
 * 1. eligibility_activities table: Add COMMENT column.
 *
 * Local function
 *
 * @since 4.6
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update46beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. eligibility_activities table: Add COMMENT column.
	 */
	$comment_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'eligibility_activities')
		AND attname = 'comment';" );

	if ( ! $comment_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY eligibility_activities
			ADD COLUMN comment text;" );
	}

	return $return;
}


/**
 * Update to version 4.7
 *
 * 1. Convert "Edit Pull-Down" fields to "Auto Pull-Down":
 * address_fields, custom_fields, people_fields, school_fields & staff_fields tables
 *
 * 2. Convert "Coded Pull-Down" fields to "Export Pull-Down":
 * address_fields, custom_fields, people_fields, school_fields & staff_fields tables
 *
 * 3. Change Pull-Down (Auto & Export), Select Multiple from Options, Text, Long Text columns type to text:
 * ADDRESS, STUDENTS, people, schools & staff tables
 *
 * Local function
 *
 * @since 4.7
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update47beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Convert "Edit Pull-Down" fields to "Auto Pull-Down":
	 * address_fields, custom_fields, people_fields, school_fields & staff_fields tables
	 */
	$sql_convert_fields = "UPDATE address_fields SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE custom_fields SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE people_fields SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE school_fields SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE staff_fields SET TYPE='autos' WHERE TYPE='edits';";

	DBQuery( $sql_convert_fields );


	/**
	 * 2. Convert "Coded Pull-Down" fields to "Export Pull-Down":
	 * address_fields, custom_fields, people_fields, school_fields & staff_fields tables
	 */
	$sql_convert_fields = "UPDATE address_fields SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE custom_fields SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE people_fields SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE school_fields SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE staff_fields SET TYPE='codeds' WHERE TYPE='exports';";

	DBQuery( $sql_convert_fields );

	$sql_fields_column_type = '';


	/**
	 * 3. Change Pull-Down (Auto & Export), Select Multiple from Options, Text, Long Text columns type to text:
	 * ADDRESS, STUDENTS, people, schools & staff tables
	 */
	$types = "'select','autos','exports','multiple','text','textarea'";

	$fields_column_RET = DBGet( "SELECT ID FROM address_fields WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE address
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM custom_fields WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE STUDENTS
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM people_fields WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE people
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM school_fields WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE schools
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM staff_fields WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE STAFF
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	if ( $sql_fields_column_type )
	{
		DBQuery( $sql_fields_column_type );
	}

	return $return;
}


/**
 * Update to version 4.7
 *
 * 1. Add CLASS_RANK_CALCULATE_MPS to config table.
 * 2. SQL performance: rewrite set_class_rank_mp() function.
 * 3. SQL move calc_cum_gpa_mp() function into t_update_mp_stats() trigger.
 *
 * Local function
 *
 * @since 4.7
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update47beta2()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add CLASS_RANK_CALCULATE_MPS to config table.
	 */
	$class_rank_added = DBGet( "SELECT 1 FROM config WHERE TITLE='CLASS_RANK_CALCULATE_MPS'" );

	if ( ! $class_rank_added )
	{
		$schools_RET = DBGet( "SELECT ID FROM schools;" );

		foreach ( (array) $schools_RET as $school )
		{
			$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID
				FROM marking_periods
				WHERE SCHOOL_ID='" . $school['ID'] . "'", [], [ 'MARKING_PERIOD_ID' ] );

			$mps = array_keys( $mps_RET );

			$class_rank_mps = '|' . implode( '||', $mps ) . '|';

			DBQuery( "INSERT INTO config
				VALUES('" . $school['ID'] . "','CLASS_RANK_CALCULATE_MPS','" . $class_rank_mps . "');" );
		}
	}

	/**
	 * 2. SQL performance: rewrite set_class_rank_mp() function.
	 * Create plpgsql language first if does not exist.
	 */
	DBQuery( "CREATE FUNCTION create_language_plpgsql()
	RETURNS BOOLEAN AS $$
		CREATE LANGUAGE plpgsql;
		SELECT TRUE;
	$$ LANGUAGE SQL;

	SELECT CASE WHEN NOT
		(
			SELECT  TRUE AS exists
			FROM    pg_language
			WHERE   lanname = 'plpgsql'
			UNION
			SELECT  FALSE AS exists
			ORDER BY exists DESC
			LIMIT 1
		)
	THEN
		create_language_plpgsql()
	ELSE
		FALSE
	END AS plpgsql_created;

	DROP FUNCTION create_language_plpgsql();

	CREATE OR REPLACE FUNCTION set_class_rank_mp(character varying) RETURNS integer
		AS $$
	DECLARE
		mp_id alias for $1;
	BEGIN
	update student_mp_stats
	set cum_rank = rank.rank, class_size = rank.class_size
	from (select mp.marking_period_id, sgm.student_id,
		(select count(*)+1
			from student_mp_stats sgm3
			where sgm3.cum_cr_weighted_factor > sgm.cum_cr_weighted_factor
			and sgm3.marking_period_id = mp.marking_period_id
			and sgm3.student_id in (select distinct sgm2.student_id
				from student_mp_stats sgm2, student_enrollment se2
				where sgm2.student_id = se2.student_id
				and sgm2.marking_period_id = mp.marking_period_id
				and se2.grade_id = se.grade_id)) as rank,
		(select count(*)
			from student_mp_stats sgm4
			where sgm4.marking_period_id = mp.marking_period_id
			and sgm4.student_id in (select distinct sgm5.student_id
				from student_mp_stats sgm5, student_enrollment se3
				where sgm5.student_id = se3.student_id
				and sgm5.marking_period_id = mp.marking_period_id
				and se3.grade_id = se.grade_id)) as class_size
		from student_enrollment se, student_mp_stats sgm, marking_periods mp
		where se.student_id = sgm.student_id
		and sgm.marking_period_id = mp.marking_period_id
		and cast(mp.marking_period_id as text) = mp_id
		and se.syear = mp.syear
		and not sgm.cum_cr_weighted_factor is null) as rank
	where student_mp_stats.marking_period_id = rank.marking_period_id
	and student_mp_stats.student_id = rank.student_id;
	RETURN 1;
	END;
	$$
		LANGUAGE plpgsql;" );


	/**
	 * 3. SQL move calc_cum_gpa_mp() function into t_update_mp_stats() trigger.
	 * Create plpgsql language first if does not exist.
	 */
	DBQuery( "CREATE FUNCTION create_language_plpgsql()
	RETURNS BOOLEAN AS $$
		CREATE LANGUAGE plpgsql;
		SELECT TRUE;
	$$ LANGUAGE SQL;

	SELECT CASE WHEN NOT
		(
			SELECT  TRUE AS exists
			FROM    pg_language
			WHERE   lanname = 'plpgsql'
			UNION
			SELECT  FALSE AS exists
			ORDER BY exists DESC
			LIMIT 1
		)
	THEN
		create_language_plpgsql()
	ELSE
		FALSE
	END AS plpgsql_created;

	DROP FUNCTION create_language_plpgsql();

	CREATE OR REPLACE FUNCTION t_update_mp_stats() RETURNS  \"trigger\"
		AS $$
	begin

	  IF tg_op = 'DELETE' THEN
		PERFORM calc_gpa_mp(OLD.student_id::int, OLD.marking_period_id::varchar);
		PERFORM calc_cum_gpa(OLD.marking_period_id::varchar, OLD.student_id::int);
		PERFORM calc_cum_cr_gpa(OLD.marking_period_id::varchar, OLD.student_id::int);

	  ELSE
		--IF tg_op = 'INSERT' THEN
			--we need to do stuff here to gather other information since it's a new record.
		--ELSE
			--if report_card_grade_id changes, then we need to reset gp values
		--  IF NOT NEW.report_card_grade_id = OLD.report_card_grade_id THEN
				--
		PERFORM calc_gpa_mp(NEW.student_id::int, NEW.marking_period_id::varchar);
		PERFORM calc_cum_gpa(NEW.marking_period_id::varchar, NEW.student_id::int);
		PERFORM calc_cum_cr_gpa(NEW.marking_period_id::varchar, NEW.student_id::int);
	  END IF;
	  return NULL;
	end
	$$
		LANGUAGE plpgsql;" );

	return $return;
}


/**
 * Update to version 4.9
 *
 * 1. program_config table: Add Allow Teachers to edit gradebook grades for past quarters option.
 *
 * Local function
 *
 * @since 4.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update49beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. program_config table: Add Allow Teachers to edit gradebook grades for past quarters option.
	 */
	$config_option_exists = DBGet( "SELECT 1 FROM program_config
		WHERE TITLE='GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT';" );

	if ( ! $config_option_exists )
	{
		DBQuery( "INSERT INTO program_config (VALUE,PROGRAM,TITLE,SCHOOL_ID,SYEAR)
			SELECT 'Y','grades','GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT',ID,SYEAR
			FROM schools;" );
	}

	return $return;
}


/**
 * Update to version 5.0
 *
 * 1. Rename sequences.
 * Use default name generated by serial: "[table]_[serial_column]_seq".
 * 2. Rename sequences for add-on modules.
 * Use default name generated by serial: "[table]_[serial_column]_seq".
 * 3. @since 5.3 Delete obsolete data first to prevent SQL errors when adding foreign keys. Based on reported error.
 * 3. @since 5.4 Test first if can add foreign key based on reported SQL errors:
 * ERROR: column "student_id" referenced in foreign key constraint does not exist
 * 3. Add foreign keys.
 * student_id, staff_id, school_id+syear, marking_period_id, course_period_id, course_id.
 *
 * Local function
 *
 * @since 5.0
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update50beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 0. Convert marking_period_id columns to integer.
	 */
	DBQuery( "ALTER TABLE student_report_card_comments
		ALTER COLUMN marking_period_id TYPE integer USING (marking_period_id::integer);
		ALTER TABLE grades_completed
		ALTER COLUMN marking_period_id TYPE integer USING (marking_period_id::integer);" );


	$rename_sequence = function( $old_sequence, $new_sequence )
	{
		if ( strlen( $new_sequence) > 63 )
		{
			$cut_at_char = ( 63 - strlen( '_seq' ) );

			// Note: sequence name is limited to 63 chars
			// @link https://www.postgresql.org/docs/9.0/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
			$new_sequence = substr( $new_sequence, 0, $cut_at_char ) . '_seq';
		}

		$sequence_exists = DBGetOne( "SELECT 1 FROM pg_class
			WHERE relname='" . DBEscapeString( $old_sequence ) . "';" );

		if ( $sequence_exists )
		{
			DBQuery( "ALTER SEQUENCE " . $old_sequence . " RENAME TO " . $new_sequence . ";" );
		}
	};

	/**
	 * 1. Rename sequences.
	 * Use default name generated by serial: "[table]_[serial_column]_seq".
	 */
	$rename_sequence( 'user_profiles_seq', 'user_profiles_id_seq' );
	$rename_sequence( 'students_join_people_seq', 'students_join_people_id_seq' );
	$rename_sequence( 'students_join_address_seq', 'students_join_address_id_seq' );
	$rename_sequence( 'students_seq', 'students_student_id_seq' );
	$rename_sequence( 'student_report_card_grades_seq', 'student_report_card_grades_id_seq' );
	$rename_sequence( 'student_medical_visits_seq', 'student_medical_visits_id_seq' );
	$rename_sequence( 'student_medical_alerts_seq', 'student_medical_alerts_id_seq' );
	$rename_sequence( 'student_medical_seq', 'student_medical_id_seq' );
	$rename_sequence( 'student_field_categories_seq', 'student_field_categories_id_seq' );
	$rename_sequence( 'student_enrollment_codes_seq', 'student_enrollment_codes_id_seq' );
	$rename_sequence( 'student_enrollment_seq', 'student_enrollment_id_seq' );
	$rename_sequence( 'staff_fields_seq', 'staff_fields_id_seq' );
	$rename_sequence( 'staff_field_categories_seq', 'staff_field_categories_id_seq' );
	$rename_sequence( 'staff_seq', 'staff_staff_id_seq' );
	$rename_sequence( 'school_periods_seq', 'school_periods_period_id_seq' );
	$rename_sequence( 'schools_seq', 'schools_id_seq' );
	$rename_sequence( 'school_gradelevels_seq', 'school_gradelevels_id_seq' );
	$rename_sequence( 'school_fields_seq', 'school_fields_id_seq' );
	$rename_sequence( 'schedule_requests_seq', 'schedule_requests_request_id_seq' );
	$rename_sequence( 'resources_seq', 'resources_id_seq' );
	$rename_sequence( 'report_card_grades_seq', 'report_card_grades_id_seq' );
	$rename_sequence( 'report_card_grade_scales_seq', 'report_card_grade_scales_id_seq' );
	$rename_sequence( 'report_card_comments_seq', 'report_card_comments_id_seq' );
	$rename_sequence( 'report_card_comment_codes_seq', 'report_card_comment_codes_id_seq' );
	$rename_sequence( 'report_card_comment_code_scales_seq', 'report_card_comment_code_scales_id_seq' );
	$rename_sequence( 'report_card_comment_categories_seq', 'report_card_comment_categories_id_seq' );
	$rename_sequence( 'portal_polls_seq', 'portal_polls_id_seq' );
	$rename_sequence( 'portal_poll_questions_seq', 'portal_poll_questions_id_seq' );
	$rename_sequence( 'portal_notes_seq', 'portal_notes_id_seq' );
	$rename_sequence( 'people_join_contacts_seq', 'people_join_contacts_id_seq' );
	$rename_sequence( 'people_fields_seq', 'people_fields_id_seq' );
	$rename_sequence( 'people_field_categories_seq', 'people_field_categories_id_seq' );
	$rename_sequence( 'people_seq', 'people_person_id_seq' );
	$rename_sequence( 'marking_period_seq', 'school_marking_periods_marking_period_id_seq' );
	$rename_sequence( 'gradebook_assignments_seq', 'gradebook_assignments_assignment_id_seq' );
	$rename_sequence( 'gradebook_assignment_types_seq', 'gradebook_assignment_types_assignment_type_id_seq' );
	$rename_sequence( 'food_service_transactions_seq', 'food_service_transactions_transaction_id_seq' );
	$rename_sequence( 'food_service_staff_transactions_seq', 'food_service_staff_transactions_transaction_id_seq' );
	$rename_sequence( 'food_service_menus_seq', 'food_service_menus_menu_id_seq' );
	$rename_sequence( 'food_service_menu_items_seq', 'food_service_menu_items_menu_item_id_seq' );
	$rename_sequence( 'food_service_items_seq', 'food_service_items_item_id_seq' );
	$rename_sequence( 'food_service_categories_seq', 'food_service_categories_category_id_seq' );
	$rename_sequence( 'eligibility_activities_seq', 'eligibility_activities_id_seq' );
	$rename_sequence( 'discipline_referrals_seq', 'discipline_referrals_id_seq' );
	$rename_sequence( 'discipline_fields_seq', 'discipline_fields_id_seq' );
	$rename_sequence( 'discipline_field_usage_seq', 'discipline_field_usage_id_seq' );
	$rename_sequence( 'custom_seq', 'custom_fields_id_seq' );
	$rename_sequence( 'course_subjects_seq', 'course_subjects_subject_id_seq' );
	$rename_sequence( 'course_period_school_periods_seq', 'course_period_school_periods_course_period_school_periods_id_seq' );
	$rename_sequence( 'courses_seq', 'courses_course_id_seq' );
	$rename_sequence( 'course_periods_seq', 'course_periods_course_period_id_seq' );
	$rename_sequence( 'calendar_events_seq', 'calendar_events_id_seq' );
	$rename_sequence( 'billing_payments_seq', 'billing_payments_id_seq' );
	$rename_sequence( 'billing_fees_seq', 'billing_fees_id_seq' );
	$rename_sequence( 'attendance_codes_seq', 'attendance_codes_id_seq' );
	$rename_sequence( 'attendance_code_categories_seq', 'attendance_code_categories_id_seq' );
	$rename_sequence( 'calendars_seq', 'attendance_calendars_calendar_id_seq' );
	$rename_sequence( 'address_fields_seq', 'address_fields_id_seq' );
	$rename_sequence( 'address_field_categories_seq', 'address_field_categories_id_seq' );
	$rename_sequence( 'address_seq', 'address_address_id_seq' );
	$rename_sequence( 'accounting_payments_seq', 'accounting_payments_id_seq' );
	$rename_sequence( 'accounting_salaries_seq', 'accounting_salaries_id_seq' );
	$rename_sequence( 'accounting_incomes_seq', 'accounting_incomes_id_seq' );

	/**
	 * 2. Rename sequences for add-on modules.
	 * Use default name generated by serial: "[table]_[serial_column]_seq".
	 */
	$rename_sequence( 'billing_fees_monthly_seq', 'billing_fees_monthly_id_seq' );
	$rename_sequence( 'school_inventory_categories_seq', 'school_inventory_categories_category_id_seq' );
	$rename_sequence( 'school_inventory_items_seq', 'school_inventory_items_item_id_seq' );
	$rename_sequence( 'saved_reports_seq', 'saved_reports_id_seq' );
	$rename_sequence( 'saved_calculations_seq', 'saved_calculations_id_seq' );
	$rename_sequence( 'messages_seq', 'messages_message_id_seq' );

	$add_foreign_key = function( $table, $column, $reference )
	{
		$fcolumn = str_replace( [ ',', ' ' ], [ '_' ], $column );

		$fk_name = $table . '_' . $fcolumn . '_fk';

		$fk_exists = DBGetOne( "SELECT 1 FROM information_schema.table_constraints
			WHERE constraint_type='FOREIGN KEY'
			AND constraint_name='" . DBEscapeString( $fk_name ) . "';" );

		if ( ! $fk_exists )
		{
			DBQuery( "ALTER TABLE " . DBEscapeIdentifier( $table ) . " ADD CONSTRAINT " . $fk_name .
				" FOREIGN KEY (" . $column . ") REFERENCES " . $reference . ";" );
		}
	};

	/**
	 * 3. Delete obsolete data first to prevent SQL errors when adding foreign keys.
	 * Based on reported error.
	 *
	 * @since 5.3
	 */
	$delete_obsolete_sql = "DELETE FROM schedule
		WHERE student_id NOT IN(SELECT student_id FROM students);";

	$delete_obsolete_sql .= "DELETE FROM food_service_student_accounts
		WHERE student_id NOT IN(SELECT student_id FROM students);";

	$delete_obsolete_sql .= "DELETE FROM gradebook_assignments
		WHERE staff_id NOT IN(SELECT staff_id FROM staff);";

	$delete_obsolete_sql .= "DELETE FROM gradebook_assignment_types
		WHERE staff_id NOT IN(SELECT staff_id FROM staff);";

	DBQuery( $delete_obsolete_sql );

	/**
	 * 3. Test first if can add foreign key based on reported SQL errors:
	 * ERROR: column "student_id" referenced in foreign key constraint does not exist
	 *
	 * @since 5.4
	 */
	$can_add_foreign_key = DBTransDryRun( "ALTER TABLE " . DBEscapeIdentifier( 'students_join_users' ) . "
		ADD CONSTRAINT students_join_users_student_id_fk
		FOREIGN KEY (student_id) REFERENCES students(student_id);" );

	if ( ! $can_add_foreign_key )
	{
		return false;
	}

	/**
	 * 3. Add foreign keys.
	 * student_id
	 */
	$add_foreign_key( 'students_join_users', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'students_join_people', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'students_join_address', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_enrollment', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_report_card_grades', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_report_card_comments', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_mp_stats', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_mp_comments', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_medical_visits', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_medical_alerts', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_medical', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_eligibility_activities', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'student_assignments', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'schedule_requests', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'schedule', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'lunch_period', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'gradebook_grades', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'food_service_transactions', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'food_service_student_accounts', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'eligibility', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'discipline_referrals', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'billing_payments', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'billing_fees', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'attendance_period', 'student_id', 'students(student_id)' );
	$add_foreign_key( 'attendance_day', 'student_id', 'students(student_id)' );

	/**
	 * 3. Add foreign keys.
	 * staff_id
	 */
	$add_foreign_key( 'course_periods', 'teacher_id', 'staff(staff_id)' );
	$add_foreign_key( 'students_join_users', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'staff_exceptions', 'user_id', 'staff(staff_id)' );
	$add_foreign_key( 'grades_completed', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'gradebook_assignments', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'gradebook_assignment_types', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'food_service_staff_transactions', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'food_service_staff_accounts', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'eligibility_completed', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'discipline_referrals', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'attendance_completed', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'accounting_payments', 'staff_id', 'staff(staff_id)' );
	$add_foreign_key( 'accounting_salaries', 'staff_id', 'staff(staff_id)' );

	/**
	 * 3. Add foreign keys.
	 * school_id+syear
	 */
	$add_foreign_key( 'student_enrollment', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'student_report_card_comments', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'school_periods', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'schedule_requests', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'schedule', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'report_card_grades', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'report_card_grade_scales', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'report_card_comments', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'report_card_comment_categories', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'program_config', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'portal_polls', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'portal_notes', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'school_marking_periods', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'food_service_transactions', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'food_service_staff_transactions', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'eligibility_activities', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'discipline_referrals', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'discipline_field_usage', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'course_subjects', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'courses', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'course_periods', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'calendar_events', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'billing_payments', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'billing_fees', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'attendance_codes', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'attendance_code_categories', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'attendance_calendars', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'attendance_calendar', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'accounting_payments', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'accounting_salaries', 'school_id,syear', 'schools(id,syear)' );
	$add_foreign_key( 'accounting_incomes', 'school_id,syear', 'schools(id,syear)' );

	/**
	 * 3. Add foreign keys.
	 * marking_period_id
	 */
	$add_foreign_key( 'student_report_card_comments', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'student_mp_comments', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'schedule_requests', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'schedule', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'lunch_period', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'grades_completed', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'gradebook_assignments', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'course_periods', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'attendance_period', 'marking_period_id', 'school_marking_periods(marking_period_id)' );
	$add_foreign_key( 'attendance_day', 'marking_period_id', 'school_marking_periods(marking_period_id)' );

	/**
	 * 3. Add foreign keys.
	 * course_period_id
	 */
	$add_foreign_key( 'student_report_card_grades', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'student_report_card_comments', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'schedule', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'lunch_period', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'grades_completed', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'gradebook_grades', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'gradebook_assignments', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'eligibility', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'course_period_school_periods', 'course_period_id', 'course_periods(course_period_id)' );
	$add_foreign_key( 'attendance_period', 'course_period_id', 'course_periods(course_period_id)' );

	/**
	 * 3. Add foreign keys.
	 * course_id
	 */
	$add_foreign_key( 'schedule_requests', 'course_id', 'courses(course_id)' );
	$add_foreign_key( 'schedule', 'course_id', 'courses(course_id)' );
	$add_foreign_key( 'report_card_comment_categories', 'course_id', 'courses(course_id)' );
	$add_foreign_key( 'gradebook_assignments', 'course_id', 'courses(course_id)' );
	$add_foreign_key( 'gradebook_assignment_types', 'course_id', 'courses(course_id)' );
	$add_foreign_key( 'course_periods', 'course_id', 'courses(course_id)' );

	return $return;
}


/**
 * Update to version 5.0.1
 *
 * 1. course_periods table:
 * Change title column type to text
 * Was character varying(255) which could prevent saving long Course Period titles
 * Needs to DROP course_details view first to then recreate it.
 *
 * Local function
 *
 * @since 5.0.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update501()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. course_periods table:
	 * Change title column type to text
	 * Was character varying(255) which could prevent saving long Course Period titles
	 * Needs to DROP course_details VIEW first to then recreate it.
	 */
	$sql_drop_view = "DROP VIEW course_details;";

	$sql_alter_table = "ALTER TABLE course_periods
		ALTER COLUMN title TYPE text;";

	$sql_create_view = "CREATE VIEW course_details AS
		SELECT cp.school_id, cp.syear, cp.marking_period_id, c.subject_id, cp.course_id, cp.course_period_id, cp.teacher_id, c.title AS course_title, cp.title AS cp_title, cp.grade_scale_id, cp.mp, cp.credits FROM course_periods cp, courses c WHERE (cp.course_id = c.course_id);";

	DBQuery( $sql_drop_view . $sql_alter_table . $sql_create_view );

	return $return;
}


/**
 * Update to version 5.2
 *
 * 1. Add NOT NULL constraint to TITLE columns.
 * 2. Fix SQL error rename sequence to course_period_school_periods_course_period_school_periods_i_seq.
 *
 * Local function
 *
 * @since 5.2
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update52beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add NOT NULL constraint to TITLE columns.
	 */
	$add_not_null_constraint = function( $table, $column )
	{
		$table_escaped = DBEscapeIdentifier( $table );
		$column_escaped = DBEscapeIdentifier( $column );

		// Set NULL values to '-' first so we avoid SQL errors on ALTER TABLE.
		DBQuery( "UPDATE " . $table_escaped . "
			SET " . $column_escaped . "='-'
			WHERE " . $column_escaped . " IS NULL;
			ALTER TABLE " . $table_escaped . "
			ALTER COLUMN " . $column_escaped . " SET NOT NULL;" );
	};

	$tables_columns = [
		'schools' => 'TITLE',
		'school_marking_periods' => 'TITLE',
		'accounting_salaries' => 'TITLE',
		'address_field_categories' => 'TITLE',
		'address_fields' => 'TITLE',
		'attendance_calendars' => 'TITLE',
		'attendance_code_categories' => 'TITLE',
		'attendance_codes' => 'TITLE',
		'billing_fees' => 'TITLE',
		'calendar_events' => 'TITLE',
		'config' => 'TITLE',
		'custom_fields' => 'TITLE',
		'discipline_field_usage' => 'TITLE',
		'eligibility_activities' => 'TITLE',
		'food_service_categories' => 'TITLE',
		'gradebook_assignment_types' => 'TITLE',
		'gradebook_assignments' => 'TITLE',
		'history_marking_periods' => 'NAME',
		'people_field_categories' => 'TITLE',
		'portal_notes' => 'TITLE',
		'portal_poll_questions' => 'QUESTION',
		'portal_polls' => 'TITLE',
		'program_config' => 'TITLE',
		'program_user_config' => 'TITLE',
		'report_card_comment_categories' => 'TITLE',
		'report_card_comments' => 'TITLE',
		'report_card_grade_scales' => 'TITLE',
		'report_card_grades' => 'TITLE',
		'resources' => 'TITLE',
		'school_fields' => 'TITLE',
		'school_gradelevels' => 'TITLE',
		'school_periods' => 'TITLE',
		'staff_exceptions' => 'MODNAME',
		'student_enrollment_codes' => 'TITLE',
		'student_field_categories' => 'TITLE',
		'student_report_card_grades' => 'COURSE_TITLE',
		'user_profiles' => 'TITLE',
	];

	foreach ( $tables_columns as $table => $column )
	{
		$add_not_null_constraint( $table, $column );
	}

	/**
	 * 2. Fix SQL error rename sequence to course_period_school_periods_course_period_school_periods_i_seq
	 */
	$sequence_exists = DBGetOne( "SELECT 1 FROM pg_class
		WHERE relname='course_period_school_periods_course_period_school_periods_id_se';" );

	if ( $sequence_exists )
	{
		DBQuery( "ALTER SEQUENCE course_period_school_periods_course_period_school_periods_id_se
			RENAME TO course_period_school_periods_course_period_school_periods_i_seq;" );
	}

	return $return;
}


/**
 * Update to version 5.3
 *
 * 1. Add FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN to config table.
 *
 * Local function
 *
 * @since 5.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update53beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN to config table.
	 */
	$force_poassword_change_added = DBGet( "SELECT 1 FROM config WHERE TITLE='FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN'" );

	if ( ! $force_poassword_change_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN', NULL);" );
	}

	return $return;
}


/**
 * Update to version 5.4.1
 *
 * 1. Add CREATED_AT & UPDATED_AT columns to every table, 93 tables.
 * 2. Add set_updated_at() function & set_updated_at trigger.
 * Create plpgsql language in case it does not exist.
 *
 * Local function
 *
 * @since 5.4.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update541()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add CREATED_AT & UPDATED_AT columns to every table, 93 tables.
	 */
	$add_created_updated_at_columns = function( $table )
	{
		$created_at_column_exists = DBGet( "SELECT 1 FROM pg_attribute
			WHERE attrelid = (SELECT oid FROM pg_class WHERE relname='" . $table . "')
			AND attname = 'created_at';" );

		if ( $created_at_column_exists )
		{
			return '';
		}

		return "ALTER TABLE ONLY " . DBEscapeIdentifier( $table ) . "
			ADD COLUMN created_at timestamp DEFAULT current_timestamp;
			ALTER TABLE ONLY " . DBEscapeIdentifier( $table ) . "
			ADD COLUMN updated_at timestamp;";
	};

	$tables = [
		'schools',
		'students',
		'staff',
		'school_marking_periods',
		'courses',
		'course_periods',
		'access_log',
		'accounting_incomes',
		'accounting_salaries',
		'accounting_payments',
		'address',
		'address_field_categories',
		'address_fields',
		'attendance_calendar',
		'attendance_calendars',
		'attendance_code_categories',
		'attendance_codes',
		'attendance_completed',
		'attendance_day',
		'attendance_period',
		'billing_fees',
		'billing_payments',
		'calendar_events',
		'config',
		'course_period_school_periods',
		'course_subjects',
		'custom_fields',
		'discipline_field_usage',
		'discipline_fields',
		'discipline_referrals',
		'eligibility',
		'eligibility_activities',
		'eligibility_completed',
		'food_service_accounts',
		'food_service_categories',
		'food_service_items',
		'food_service_menu_items',
		'food_service_menus',
		'food_service_staff_accounts',
		'food_service_staff_transaction_items',
		'food_service_staff_transactions',
		'food_service_student_accounts',
		'food_service_transaction_items',
		'food_service_transactions',
		'gradebook_assignment_types',
		'gradebook_assignments',
		'gradebook_grades',
		'grades_completed',
		'lunch_period',
		'history_marking_periods',
		'moodlexrosario',
		'people',
		'people_field_categories',
		'people_fields',
		'people_join_contacts',
		'portal_notes',
		'portal_poll_questions',
		'portal_polls',
		'profile_exceptions',
		'program_config',
		'program_user_config',
		'report_card_comment_categories',
		'report_card_comment_code_scales',
		'report_card_comment_codes',
		'report_card_comments',
		'report_card_grade_scales',
		'report_card_grades',
		'resources',
		'schedule',
		'schedule_requests',
		'school_fields',
		'school_gradelevels',
		'school_periods',
		'staff_exceptions',
		'staff_field_categories',
		'staff_fields',
		'student_assignments',
		'student_eligibility_activities',
		'student_enrollment_codes',
		'student_field_categories',
		'student_medical',
		'student_medical_alerts',
		'student_medical_visits',
		'student_mp_comments',
		'student_mp_stats',
		'student_report_card_comments',
		'student_report_card_grades',
		'student_enrollment',
		'students_join_address',
		'students_join_people',
		'students_join_users',
		'templates',
		'user_profiles',
	];

	$sql_add_created_updated_at_columns = '';

	foreach ( $tables as $table )
	{
		$sql_add_created_updated_at_columns .= $add_created_updated_at_columns( $table );
	}

	if ( $sql_add_created_updated_at_columns )
	{
		DBQuery( $sql_add_created_updated_at_columns );
	}

	/**
	 * 2. Add set_updated_at() function & set_updated_at trigger.
	 * Create plpgsql language in case it does not exist.
	 */
	$set_updated_at_trigger_exists = DBGetOne( "SELECT 1
		FROM pg_catalog.pg_proc
		WHERE proname='set_updated_at';" );

	if ( ! $set_updated_at_trigger_exists )
	{
		DBQuery( "CREATE FUNCTION create_language_plpgsql()
		RETURNS BOOLEAN AS $$
			CREATE LANGUAGE plpgsql;
			SELECT TRUE;
		$$ LANGUAGE SQL;

		SELECT CASE WHEN NOT
			(
				SELECT  TRUE AS exists
				FROM    pg_language
				WHERE   lanname = 'plpgsql'
				UNION
				SELECT  FALSE AS exists
				ORDER BY exists DESC
				LIMIT 1
			)
		THEN
			create_language_plpgsql()
		ELSE
			FALSE
		END AS plpgsql_created;

		DROP FUNCTION create_language_plpgsql();

		CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
			BEGIN
				IF row(NEW.*) IS DISTINCT FROM row(OLD.*) THEN
					NEW.updated_at := CURRENT_TIMESTAMP;
					RETURN NEW;
				ELSE
					RETURN OLD;
				END IF;
			END;
		$$ LANGUAGE plpgsql;

		CREATE OR REPLACE FUNCTION set_updated_at_triggers() RETURNS void AS $$
		DECLARE
			t text;
		BEGIN
			FOR t IN
				SELECT table_name FROM information_schema.columns
				WHERE column_name = 'updated_at'
			LOOP
				EXECUTE
					'CREATE TRIGGER set_updated_at
					BEFORE UPDATE ON ' || t || '
					FOR EACH ROW EXECUTE PROCEDURE set_updated_at()';
			END LOOP;
		END;
		$$ LANGUAGE plpgsql;

		SELECT set_updated_at_triggers();

		DROP FUNCTION set_updated_at_triggers();" );
	}

	return $return;
}


/**
 * Update to version 5.4.2
 *
 * 0. Create plpgsql language in case it does not exist.
 * 1. Fix SQL error in calc_gpa_mp function on INSERT Final Grades: column short_name does not exist, PostgreSQL 8.4.
 *
 * Local function
 *
 * @since 5.4.2
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update542()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	// 0. Create plpgsql language in case it does not exist.
	// 1. Fix SQL error in calc_gpa_mp function on INSERT Final Grades: column short_name does not exist, PostgreSQL 8.4.
	DBQuery( "CREATE FUNCTION create_language_plpgsql()
	RETURNS BOOLEAN AS $$
		CREATE LANGUAGE plpgsql;
		SELECT TRUE;
	$$ LANGUAGE SQL;

	SELECT CASE WHEN NOT
		(
			SELECT  TRUE AS exists
			FROM    pg_language
			WHERE   lanname = 'plpgsql'
			UNION
			SELECT  FALSE AS exists
			ORDER BY exists DESC
			LIMIT 1
		)
	THEN
		create_language_plpgsql()
	ELSE
		FALSE
	END AS plpgsql_created;

	DROP FUNCTION create_language_plpgsql();

	CREATE OR REPLACE FUNCTION calc_gpa_mp(integer, character varying) RETURNS integer AS $$
	DECLARE
		s_id ALIAS for $1;
		mp_id ALIAS for $2;
		oldrec student_mp_stats%ROWTYPE;
	BEGIN
	  SELECT * INTO oldrec FROM student_mp_stats WHERE student_id = s_id and cast(marking_period_id as text) = mp_id;

	  IF FOUND THEN
		UPDATE student_mp_stats SET
			sum_weighted_factors = rcg.sum_weighted_factors,
			sum_unweighted_factors = rcg.sum_unweighted_factors,
			cr_weighted_factors = rcg.cr_weighted,
			cr_unweighted_factors = rcg.cr_unweighted,
			gp_credits = rcg.gp_credits,
			cr_credits = rcg.cr_credits

		FROM (
		select
			sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors,
			sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors,
			sum(credit_attempted) as gp_credits,
			sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
			sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
			sum( case when class_rank = 'Y' THEN credit_attempted END) as cr_credits

			from student_report_card_grades where student_id = s_id
			and cast(marking_period_id as text) = mp_id
			 and not gp_scale = 0 group by student_id, marking_period_id
			) as rcg
	WHERE student_id = s_id and cast(marking_period_id as text) = mp_id;
		RETURN 1;
	ELSE
		INSERT INTO student_mp_stats (student_id, marking_period_id, sum_weighted_factors, sum_unweighted_factors, grade_level_short, cr_weighted_factors, cr_unweighted_factors, gp_credits, cr_credits)

			select
				srcg.student_id,
				(srcg.marking_period_id::text)::int,
				sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors,
				sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors,
				(select eg.short_name
					from enroll_grade eg, marking_periods mp
					where eg.student_id = s_id
					and eg.syear = mp.syear
					and eg.school_id = mp.school_id
					and eg.start_date <= mp.end_date
					and cast(mp.marking_period_id as text) = mp_id
					order by eg.start_date desc
					limit 1) as short_name,
				sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
				sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
				sum(credit_attempted) as gp_credits,
				sum(case when class_rank = 'Y' THEN credit_attempted END) as cr_credits
			from student_report_card_grades srcg
			where srcg.student_id = s_id and cast(srcg.marking_period_id as text) = mp_id and not srcg.gp_scale = 0
			group by srcg.student_id, srcg.marking_period_id, short_name;
		END IF;
		RETURN 0;
	END
	$$
		LANGUAGE plpgsql;" );

	return $return;
}


/**
 * Update to version 5.5
 *
 * 0. report_card_grades table: Cut titles > 5 chars.
 * 1. report_card_grades table: Change title column type to character varying(5)
 * Was text which could prevent saving letter grades > 5 chars
 * @see student_report_card_grades letter_grade column.
 *
 * Local function
 *
 * @since 5.5
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update55beta3()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 0. report_card_grades table: Cut titles > 5 chars.
	 */
	DBQuery( "UPDATE report_card_grades
		SET TITLE=SUBSTRING(TITLE FROM 1 FOR 5);" );

	/**
	 * 1. report_card_grades table: Change title column type to character varying(5)
	 * Was text which could prevent saving letter grades > 5 chars
	 * @see student_report_card_grades letter_grade column.
	 */
	DBQuery( "ALTER TABLE report_card_grades
		ALTER COLUMN title TYPE character varying(5);" );

	return $return;
}


/**
 * Update to version 5.7
 *
 * 1. address table:
 * Change city & mail_city column type to text
 * Was character varying(60) which could prevent long city names.
 * 2. address table:
 * Change state & mail_state column type to character varying(50)
 * Was character varying(10). Now allows storing country.
 *
 * Local function
 *
 * @since 5.7
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update57()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. address table:
	 * Change city & mail_city column type to text
	 * Was character varying(60) which could prevent long city names.
	 */
	DBQuery( "ALTER TABLE address
		ALTER COLUMN city TYPE text;
		ALTER TABLE address
		ALTER COLUMN mail_city TYPE text;" );

	/**
	 * 2. address table:
	 * Change state & mail_state column type to character varying(50)
	 * Was character varying(10). Now allows storing country.
	 */
	DBQuery( "ALTER TABLE address
		ALTER COLUMN state TYPE character varying(50);
		ALTER TABLE address
		ALTER COLUMN mail_state TYPE character varying(50);" );

	return $return;
}


/**
 * Update to version 5.8-beta5
 *
 * 1. school_gradelevels table:
 * Change short_name column type to character varying(3)
 * Was character varying(2). Now allows French elementary grade levels.
 *
 * Local function
 *
 * @since 5.8
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update58beta5()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. school_gradelevels table:
	 * Change short_name column type to character varying(3)
	 * Was character varying(2). Now allows French elementary grade levels.
	 *
	 * Must drop enroll_grade view first and recreate it afterwards.
	 */
	DBQuery( "BEGIN;
		DROP VIEW enroll_grade;
		ALTER TABLE school_gradelevels
		ALTER COLUMN short_name TYPE character varying(3);
		CREATE VIEW enroll_grade AS
			SELECT e.id, e.syear, e.school_id, e.student_id, e.start_date, e.end_date, sg.short_name, sg.title FROM student_enrollment e, school_gradelevels sg WHERE (e.grade_id = sg.id);
		COMMIT;" );

	return $return;
}


/**
 * Update to version 5.9-beta
 *
 * 1. staff_fields table:
 * Add Email & Phone to Staff Fields.
 * Eventually translate Field Title to Spanish or French.
 *
 * 2. staff table:
 * Move Email & Phone Staff Fields to custom fields.
 * Rename phone columns to custom_200000001.
 * Change type to character varying(255) for email (was character varying(100))
 * and text for custom_200000001 (was character varying(100)).
 *
 * Local function
 *
 * @since 5.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update59beta()
{
	global $locale;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. staff_fields table:
	 * Add Email & Phone to Staff Fields.
	 */
	$staff_fields_exist = DBGetOne( "SELECT 1 FROM staff_fields
		WHERE ID='200000000'" );

	if ( ! $staff_fields_exist )
	{
		DBQuery( "INSERT INTO staff_fields VALUES (200000000, 'text', 'Email Address', 0, NULL, 1, NULL, NULL);
			INSERT INTO staff_fields VALUES (200000001, 'text', 'Phone Number', 1, NULL, 1, NULL, NULL);" );

		/**
		 * Eventually translate Field Title to Spanish or French.
		 */
		if ( $locale === 'fr_FR.utf8' )
		{
			DBQuery( "UPDATE staff_fields
				SET title='Email Address|fr_FR.utf8:Adresse Email'
				WHERE id=200000000;
				UPDATE staff_fields
				SET title='Phone Number|fr_FR.utf8:Numéro de Téléphone'
				WHERE id=200000001;" );
		}
		elseif ( $locale === 'es_ES.utf8' )
		{
			DBQuery( "UPDATE staff_fields SET title='Email Address|es_ES.utf8:Email'
				WHERE id=200000000;
				UPDATE staff_fields SET title='Phone Number|es_ES.utf8:Número de Teléfono'
				WHERE id=200000001;" );
		}
	}

	/**
	 * 2. staff table:
	 * Move Email & Phone Staff Fields to custom fields.
	 * Rename phone columns to custom_200000001.
	 * Change type to character varying(255) for email (was character varying(100))
	 * and text for custom_200000001 (was character varying(100)).
	 */
	$custom_200000001_column_exists = DBGet( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'staff')
		AND attname = 'custom_200000001';" );

	if ( ! $custom_200000001_column_exists )
	{
		DBQuery( "ALTER TABLE staff
			RENAME COLUMN phone TO custom_200000001;
			ALTER TABLE staff
			ALTER COLUMN email TYPE character varying(255);
			ALTER TABLE staff
			ALTER COLUMN custom_200000001 TYPE text;" );
	}

	return $return;
}


/**
 * Update to version 5.9-beta2
 *
 * 1. Add CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION to config table.
 *
 * Local function
 *
 * @since 5.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update59beta2()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION to config table.
	 */
	$automatic_activation_added = DBGetOne( "SELECT 1 FROM config
		WHERE TITLE='CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION'" );

	if ( ! $automatic_activation_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION', NULL);" );
	}

	return $return;
}


/**
 * Update to version 5.9
 *
 * 1. Move REMOVE_ACCESS_USERNAME_PREFIX_ADD to config table.
 *
 * Local function
 *
 * @since 5.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update59()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Move REMOVE_ACCESS_USERNAME_PREFIX_ADD to config table.
	 */
	$username_prefix_add_added = DBGetOne( "SELECT 1 FROM config
		WHERE TITLE='REMOVE_ACCESS_USERNAME_PREFIX_ADD'" );

	if ( ! $username_prefix_add_added )
	{
		// Move REMOVE_ACCESS_USERNAME_PREFIX_ADD from program_config (per school) to config (all schools, 0).
		$old_program_config_value = ProgramConfig( 'custom', 'REMOVE_ACCESS_USERNAME_PREFIX_ADD' );

		DBQuery( "INSERT INTO config VALUES (0, 'REMOVE_ACCESS_USERNAME_PREFIX_ADD', '" . $old_program_config_value . "');" );
	}

	return $return;
}


/**
 * Update to version 5.9.1
 *
 * 1. transcript_grades view:
 * SQL Fix School Base Grading Scale for Historical Grades in transcript_grades view.
 *
 * Local function
 *
 * @since 5.9.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update591()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. transcript_grades view:
	 * SQL Fix School Base Grading Scale for Historical Grades in transcript_grades view.
	 */
	$sql_drop_view = "DROP VIEW transcript_grades;";

	$sql_create_view = "CREATE VIEW transcript_grades AS
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
				OR mp.syear=(SELECT syear FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1)
	ORDER BY srcg.course_period_id;";

	DBQuery( $sql_drop_view . $sql_create_view );

	return $return;
}
