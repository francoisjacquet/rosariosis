<?php
/**
 * User functions
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * User Delete SQL queries
 *
 * @since 5.2
 *
 * @param int $staff_id Staff ID.
 *
 * @return string Delete SQL queries.
 */
function UserDeleteSQL( $staff_id )
{
	$delete_sql = "DELETE FROM program_user_config
		WHERE USER_ID='" . (int) $staff_id . "';";

	$delete_sql .= "DELETE FROM STAFF_EXCEPTIONS
		WHERE USER_ID='" . (int) $staff_id . "';";

	$delete_sql .= "DELETE FROM STUDENTS_JOIN_USERS
		WHERE STAFF_ID='" . (int) $staff_id . "';";

	$delete_sql .= "DELETE FROM FOOD_SERVICE_STAFF_ACCOUNTS
		WHERE STAFF_ID='" . (int) $staff_id . "';";

	$delete_sql .= "DELETE FROM STAFF
		WHERE STAFF_ID='" . (int) $staff_id . "';";

	return $delete_sql;
}
