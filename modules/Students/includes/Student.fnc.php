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
	$student_id = intval( $student_id );

	// Do not try to delete Grades, Attendance, or Schedule records
	// in case records exist, we must keep them.
	$delete_sql = "DELETE FROM STUDENTS_JOIN_ADDRESS
		WHERE STUDENT_ID='" . $student_id . "';";

	$delete_sql .= "DELETE FROM STUDENTS_JOIN_PEOPLE
		WHERE STUDENT_ID='" . $student_id . "';";

	$delete_sql .= "DELETE FROM STUDENTS_JOIN_USERS
		WHERE STUDENT_ID='" . $student_id . "';";

	$delete_sql .= "DELETE FROM STUDENT_ENROLLMENT
		WHERE STUDENT_ID='" . $student_id . "';";

	$delete_sql .= "DELETE FROM FOOD_SERVICE_ACCOUNTS
		WHERE ACCOUNT_ID='" . $student_id . "';";

	$delete_sql .= "DELETE FROM FOOD_SERVICE_STUDENT_ACCOUNTS
		WHERE STUDENT_ID='" . $student_id . "';";

	$delete_sql .= "DELETE FROM STUDENTS
		WHERE STUDENT_ID='" . $student_id . "';";

	return $delete_sql;
}
