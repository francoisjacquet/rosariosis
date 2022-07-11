<?php
/**
 * Student functions
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Student Delete SQL queries
 *
 * @since 5.2
 *
 * @param int $student_id Student ID.
 *
 * @return string Delete SQL queries.
 */
function StudentDeleteSQL( $student_id )
{
	// Do not try to delete Grades, Attendance, or Schedule records
	// in case records exist, we must keep them.
	$delete_sql = "DELETE FROM students_join_address
		WHERE STUDENT_ID='" . (int) $student_id . "';";

	$delete_sql .= "DELETE FROM students_join_people
		WHERE STUDENT_ID='" . (int) $student_id . "';";

	$delete_sql .= "DELETE FROM students_join_users
		WHERE STUDENT_ID='" . (int) $student_id . "';";

	$delete_sql .= "DELETE FROM student_enrollment
		WHERE STUDENT_ID='" . (int) $student_id . "';";

	$delete_sql .= "DELETE FROM food_service_accounts
		WHERE ACCOUNT_ID='" . (int) $student_id . "';";

	$delete_sql .= "DELETE FROM food_service_student_accounts
		WHERE STUDENT_ID='" . (int) $student_id . "';";

	$delete_sql .= "DELETE FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "';";

	return $delete_sql;
}
