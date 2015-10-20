<?php
/**
 * Update functions
 *
 * Incremental updates
 *
 * Update() function called if ROSARIO_VERSION != version in DB
 *
 * @since 2.9
 */

/**
 * Update manager function
 *
 * Call the specific versions functions
 *
 * @return boolean false if wrong version or update failed, else true
 */
function Update()
{
	$from_version = Config( 'VERSION' );

	$to_version = ROSARIO_VERSION;

	// Check if version in DB >= ROSARIO_VERSION
	// TODO 2.9-beta < 2.9? No, freeze in beta!
	if ( $from_version >= $to_version )
	{
		return false;
	}

	$return = true;

	switch( true )
	{
		case $from_version < 2.9:

			if ( function_exists( '_update29' ) )
				$return = _update29();
	}

	return $return;
}


/**
 * Update to version 2.9
 *
 * 1. Add course_period_school_periods_id column to course_period_school_periods table PRIMARY KEY
 * 3. Update STUDENT_MP_COMMENTS table
 *
 * Local function
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update29()
{
	$callers = debug_backtrace();

	if ( !isset( $callers[1]['function'] )
		|| $callers[1]['function'] !== 'Update' )
		return false;

	$return = true;

	/**
	 * 1. Add course_period_school_periods_id column to course_period_school_periods table PRIMARY KEY
	 *
	 * DROP PRIMARY KEY
	 * And ADD it again with course_period_school_periods_id
	 */
	$SQL_dups = "";
	if ( !$SQL_dups_RET )
		$return = false;
		$SQL_add_ID = "ALTER TABLE ONLY course_period_school_periods
				DROP CONSTRAINT course_period_school_periods_pkey;
			ALTER TABLE ONLY course_period_school_periods
				ADD CONSTRAINT course_period_school_periods_pkey PRIMARY KEY (course_period_school_periods_id, course_period_id, period_id);";

	DBGet( DBQuery( $sql_updt_coms ) );


	/**
	 * 3. Update STUDENT_MP_COMMENTS table
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
		&& !unserialize( $comments_RET[0]['COMMENT'] ) )
	{
		$ser_comments = array();

		$SQL_updt_coms = '';

		foreach ( $comments_RET as $comment )
		{
			$coms = explode( '||', $comment['COMMENT'] );
			$ser_coms = array();
			$i = 0;

			foreach ( (array)$coms as $com )
			{
				if ( is_array( list( $date, $staff_id ) = explode( '|', $com ) ) 
					&& (int)$staff_id > 0 )
				{
					$ser_coms[$i]['date'] = $date;
					$ser_coms[$i]['staff_id'] = $staff_id;
				}
				else
				{
					$ser_coms[$i]['comment'] = $com;
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

		if ( $SQL_updt_coms !== '' )
			var_dump($SQL_updt_coms);
			//DBGet( DBQuery( $sql_updt_coms ) );
	}
	else
		$return = false;

	return $return;
}