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
	if ( version_compare( '4.0-beta', ROSARIO_VERSION, '<' ) )
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
	}

	// Update version in DB CONFIG table.
	DBGet( DBQuery( "UPDATE CONFIG
		SET CONFIG_VALUE='" . ROSARIO_VERSION . "'
		WHERE TITLE='VERSION'" ) );

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
