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

	$delete_sql .= "DELETE FROM staff_exceptions
		WHERE USER_ID='" . (int) $staff_id . "';";

	$delete_sql .= "DELETE FROM students_join_users
		WHERE STAFF_ID='" . (int) $staff_id . "';";

	$delete_sql .= "DELETE FROM food_service_staff_accounts
		WHERE STAFF_ID='" . (int) $staff_id . "';";

	$delete_sql .= "DELETE FROM staff
		WHERE STAFF_ID='" . (int) $staff_id . "';";

	return $delete_sql;
}
