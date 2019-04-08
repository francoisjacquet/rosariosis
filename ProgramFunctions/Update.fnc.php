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
	if ( version_compare( '4.5', ROSARIO_VERSION, '<' ) )
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
	 * 1. Add VERSION to PASSWORD_STRENGTH table.
	 */
	$version_added = DBGet( "SELECT 1 FROM CONFIG WHERE TITLE='PASSWORD_STRENGTH'" );

	if ( ! $version_added )
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
