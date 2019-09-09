<?php
/**
 * Marking Periods functions
 *
 * @subpackage modules
 * @package RosarioSIS
 */

/**
 * Marking Period DELETE SQL queries
 *
 * @since 5.2
 *
 * @param int    $mp_id   Marking Period ID.
 * @param string $mp_term Term: FY, SEM, QTR or PRO.
 *
 * @return string Marking Period DELETE SQL queries.
 */
function MarkingPeriodDeleteSQL( $mp_id, $mp_term )
{
	$delete_sql = '';

	switch ( $mp_term )
	{
		case 'FY':

			$delete_sql .= "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID IN
					(SELECT MARKING_PERIOD_ID
						FROM SCHOOL_MARKING_PERIODS
						WHERE PARENT_ID IN
							(SELECT MARKING_PERIOD_ID
								FROM SCHOOL_MARKING_PERIODS
								WHERE PARENT_ID='" . $mp_id . "'));";

		case 'SEM':

			$delete_sql .= "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID IN
					(SELECT MARKING_PERIOD_ID
						FROM SCHOOL_MARKING_PERIODS
						WHERE PARENT_ID='" . $mp_id . "');";

		case 'QTR':

			$delete_sql .= "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID='" . $mp_id . "';";

		case 'PRO':
		break;
	}

	$delete_sql .= "DELETE FROM SCHOOL_MARKING_PERIODS
		WHERE MARKING_PERIOD_ID='" . $mp_id . "';";

	return $delete_sql;
}
