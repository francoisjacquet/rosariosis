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
	if ( version_compare( '4.9.2', ROSARIO_VERSION, '<' ) )
	{
		return false;
	}

	// Check if version in DB >= ROSARIO_VERSION.
	if ( version_compare( $from_version, $to_version, '>=' ) )
	{
		return false;
	}

	require_once 'ProgramFunctions/UpdateV2_3.fnc.php';

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
		UPDATE STUDENT_MP_STATS SET
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
		INSERT INTO STUDENT_MP_STATS (student_id, marking_period_id, sum_weighted_factors, sum_unweighted_factors, grade_level_short, cr_weighted_factors, cr_unweighted_factors, gp_credits, cr_credits)

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
 * 1. CONFIG table:
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
	 * 1. CONFIG table:
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
 * 1. COURSES table: Add DESCRIPTION column.
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
	 * 1. COURSES table: Add DESCRIPTION column.
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
 * 1. GRADEBOOK_ASSIGNMENTS table: Add FILE column.
 * 2. GRADEBOOK_ASSIGNMENTS table: Change DESCRIPTION column type to text.
 * 3. GRADEBOOK_ASSIGNMENTS table: Convert DESCRIPTION values from MarkDown to HTML.
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
	 * 1. GRADEBOOK_ASSIGNMENTS table:
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
	 * 2. GRADEBOOK_ASSIGNMENTS table:
	 * Change DESCRIPTION column type to text
	 * Was character varying(1000) which could prevent saving rich text with base64 images
	 */
	DBQuery( "ALTER TABLE gradebook_assignments
		ALTER COLUMN description TYPE text;" );

	/**
	 * 3. GRADEBOOK_ASSIGNMENTS table:
	 * Convert DESCRIPTION values from MarkDown to HTML.
	 */
	$assignments_RET = DBGet( "SELECT assignment_id,description
		FROM gradebook_assignments
		WHERE description IS NOT NULL;" );

	$assignment_update_sql = "UPDATE GRADEBOOK_ASSIGNMENTS
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
 * 1. Add PASSWORD_STRENGTH to CONFIG table.
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
	 * 1. Add PASSWORD_STRENGTH to CONFIG table.
	 */
	$password_strength_added = DBGet( "SELECT 1 FROM CONFIG WHERE TITLE='PASSWORD_STRENGTH'" );

	if ( ! $password_strength_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'PASSWORD_STRENGTH', '1');" );
	}

	return $return;
}


/**
 * Update to version 4.5
 *
 * 1. GRADEBOOK_ASSIGNMENT_TYPES table: Add CREATED_MP column.
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
	 * 1. GRADEBOOK_ASSIGNMENT_TYPES table: Add CREATED_MP column.
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
 * 1. ELIGIBILITY_ACTIVITIES table: Add COMMENT column.
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
	 * 1. ELIGIBILITY_ACTIVITIES table: Add COMMENT column.
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
 * ADDRESS_FIELDS, CUSTOM_FIELDS, PEOPLE_FIELDS, SCHOOL_FIELDS & STAFF_FIELDS tables
 *
 * 2. Convert "Coded Pull-Down" fields to "Export Pull-Down":
 * ADDRESS_FIELDS, CUSTOM_FIELDS, PEOPLE_FIELDS, SCHOOL_FIELDS & STAFF_FIELDS tables
 *
 * 3. Change Pull-Down (Auto & Export), Select Multiple from Options, Text, Long Text columns type to text:
 * ADDRESS, STUDENTS, PEOPLE, SCHOOLS & STAFF tables
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
	 * ADDRESS_FIELDS, CUSTOM_FIELDS, PEOPLE_FIELDS, SCHOOL_FIELDS & STAFF_FIELDS tables
	 */
	$sql_convert_fields = "UPDATE ADDRESS_FIELDS SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE CUSTOM_FIELDS SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE PEOPLE_FIELDS SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE SCHOOL_FIELDS SET TYPE='autos' WHERE TYPE='edits';";
	$sql_convert_fields .= "UPDATE STAFF_FIELDS SET TYPE='autos' WHERE TYPE='edits';";

	DBQuery( $sql_convert_fields );


	/**
	 * 2. Convert "Coded Pull-Down" fields to "Export Pull-Down":
	 * ADDRESS_FIELDS, CUSTOM_FIELDS, PEOPLE_FIELDS, SCHOOL_FIELDS & STAFF_FIELDS tables
	 */
	$sql_convert_fields = "UPDATE ADDRESS_FIELDS SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE CUSTOM_FIELDS SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE PEOPLE_FIELDS SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE SCHOOL_FIELDS SET TYPE='codeds' WHERE TYPE='exports';";
	$sql_convert_fields .= "UPDATE STAFF_FIELDS SET TYPE='codeds' WHERE TYPE='exports';";

	DBQuery( $sql_convert_fields );

	$sql_fields_column_type = '';


	/**
	 * 3. Change Pull-Down (Auto & Export), Select Multiple from Options, Text, Long Text columns type to text:
	 * ADDRESS, STUDENTS, PEOPLE, SCHOOLS & STAFF tables
	 */
	$types = "'select','autos','exports','multiple','text','textarea'";

	$fields_column_RET = DBGet( "SELECT ID FROM ADDRESS_FIELDS WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE ADDRESS
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM CUSTOM_FIELDS WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE STUDENTS
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM PEOPLE_FIELDS WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE PEOPLE
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM SCHOOL_FIELDS WHERE TYPE IN(" . $types . ")" );

	foreach ( (array) $fields_column_RET as $field_column )
	{
		$sql_fields_column_type .= "ALTER TABLE SCHOOLS
			ALTER COLUMN " . DBEscapeIdentifier( 'CUSTOM_' . $field_column['ID'] ) . " TYPE text;";
	}

	$fields_column_RET = DBGet( "SELECT ID FROM STAFF_FIELDS WHERE TYPE IN(" . $types . ")" );

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
 * 1. Add CLASS_RANK_CALCULATE_MPS to CONFIG table.
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
	 * 1. Add CLASS_RANK_CALCULATE_MPS to CONFIG table.
	 */
	$class_rank_added = DBGet( "SELECT 1 FROM CONFIG WHERE TITLE='CLASS_RANK_CALCULATE_MPS'" );

	if ( ! $class_rank_added )
	{
		$schools_RET = DBGet( "SELECT ID FROM SCHOOLS;" );

		foreach ( (array) $schools_RET as $school )
		{
			$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID
				FROM MARKING_PERIODS
				WHERE SCHOOL_ID='" . $school['ID'] . "'", array(), array( 'MARKING_PERIOD_ID' ) );

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
 * 1. PROGRAM_CONFIG table: Add Allow Teachers to edit gradebook grades for past quarters option.
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
	 * 1. PROGRAM_CONFIG table: Add Allow Teachers to edit gradebook grades for past quarters option.
	 */
	$config_option_exists = DBGet( "SELECT 1 FROM PROGRAM_CONFIG
		WHERE TITLE='GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT';" );

	if ( ! $config_option_exists )
	{
		DBQuery( "INSERT INTO PROGRAM_CONFIG (VALUE,PROGRAM,TITLE,SCHOOL_ID,SYEAR)
			SELECT 'Y','grades','GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT',ID,SYEAR
			FROM SCHOOLS;" );
	}

	return $return;
}
