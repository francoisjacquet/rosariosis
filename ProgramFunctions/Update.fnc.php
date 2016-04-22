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

	// Check if version in DB >= ROSARIO_VERSION.
	if ( version_compare( $from_version, $to_version, '>=' ) )
	{
		return false;
	}

	$return = true;

	switch ( true )
	{
		case version_compare( $from_version, '2.9-alpha', '<' ):

			if ( function_exists( '_update29alpha' ) )
			{
				$return = _update29alpha();
			}
	}

	// Update version in DB CONFIG table.
	DBGet( DBQuery( "UPDATE CONFIG
		SET CONFIG_VALUE='" . ROSARIO_VERSION . "'
		WHERE TITLE='VERSION'" ) );

	return $return;
}


/**
 * Update to version 2.9-alpha
 *
 * 1. Add VERSION to CONFIG table
 * 2. Add STUDENTS_EMAIL_FIELD to CONFIG table.
 * 3. Add course_period_school_periods_id column to course_period_school_periods table PRIMARY KEY
 * 4. Update STUDENT_MP_COMMENTS table
 * 5. Create school_fields_seq Sequence
 * 6. Add STUDENT_ASSIGNMENTS table & SUBMISSION column to GRADEBOOK_ASSIGNMENTS table
 *
 * Local function
 *
 * @since 2.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update29alpha()
{
	$callers = debug_backtrace();

	if ( ! isset( $callers[1]['function'] )
		|| $callers[1]['function'] !== 'Update' )
	{
		return false;
	}

	$return = true;


	/**
	 * 1. Add VERSION to CONFIG table.
	 */
	$version_added = DBGet( DBQuery( "SELECT 1 FROM CONFIG WHERE TITLE='VERSION'" ) );

	if ( ! $version_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'VERSION', '2.9-alpha');" );
	}


	/**
	 * 2. Add STUDENTS_EMAIL_FIELD to CONFIG table.
	 */
	$students_email_field_added = DBGet( DBQuery( "SELECT 1 FROM CONFIG WHERE TITLE='STUDENTS_EMAIL_FIELD'" ) );

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
	 * 4. Update STUDENT_MP_COMMENTS table
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
	$comments_RET = DBGet( DBQuery( "SELECT SYEAR, MARKING_PERIOD_ID, STUDENT_ID, COMMENT
		FROM STUDENT_MP_COMMENTS
		WHERE COMMENT IS NOT NULL
		AND COMMENT!=''" ) );

	if ( is_array( $comments_RET )
		&& ! unserialize( $comments_RET[0]['COMMENT'] ) )
	{
		$ser_comments = array();

		$SQL_updt_coms = '';

		foreach ( (array) $comments_RET as $comment )
		{
			$coms = explode( '||', $comment['COMMENT'] );
			$ser_coms = array();
			$i = 0;

			foreach ( (array) $coms as $com )
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

			$SQL_updt_coms .= "UPDATE STUDENT_MP_COMMENTS
				SET COMMENT='" . $ser_coms . "'
				WHERE SYEAR='" . $comment['SYEAR'] . "'
				AND MARKING_PERIOD_ID='" . $comment['MARKING_PERIOD_ID'] . "'
				AND STUDENT_ID='" . $comment['STUDENT_ID'] . "';";
		}

		if ( $SQL_updt_coms )
		{
			DBGet( DBQuery( $SQL_updt_coms ) );
		}
	}
	else
		$return = false;


	// 5. Create school_fields_seq Sequence.
	$sequence_exists = DBGet( DBQuery( "SELECT 1 FROM pg_class
		WHERE relname = 'school_fields_seq'" ) );

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
	 * 6. Add STUDENT_ASSIGNMENTS table (& its composite primary key)
	 * & add SUBMISSION column to GRADEBOOK_ASSIGNMENTS table
	 * & add StudentAssignments.php to PROFILE_EXCEPTIONS table.
	 */
	DBQuery( "CREATE TABLE IF NOT EXISTS student_assignments (
		assignment_id numeric NOT NULL,
		student_id numeric NOT NULL,
		data text
	);");

	$sa_constraint_exists = DBGet( DBQuery( "SELECT 1
		FROM information_schema.constraint_column_usage
		WHERE table_name = 'student_assignments'
		AND constraint_name = 'student_assignments_pkey'" ) );

	if ( ! $sa_constraint_exists )
	{
		DBQuery( "ALTER TABLE ONLY student_assignments
			ADD CONSTRAINT student_assignments_pkey PRIMARY KEY (assignment_id, student_id);" );
	}

	$submission_column_exists = DBGet( DBQuery( "SELECT 1 FROM pg_attribute
		WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'gradebook_assignments')
		AND attname = 'submission';" ) );

	if ( ! $submission_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY gradebook_assignments
			ADD COLUMN submission character varying(1);" );
	}

	$sa_exceptions_exists = DBGet( DBQuery( "SELECT 1
		FROM profile_exceptions
		WHERE profile_id IN (0,3)
		AND modname='Grades/StudentAssignments.php'" ) );

	if ( ! $sa_exceptions_exists )
	{
		DBQuery( "INSERT INTO profile_exceptions VALUES (0, 'Grades/StudentAssignments.php', 'Y', NULL);
			INSERT INTO profile_exceptions VALUES (3, 'Grades/StudentAssignments.php', 'Y', NULL);" );
	}

	return $return;
}
