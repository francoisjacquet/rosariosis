<?php
/**
 * Schools functions
 *
 * @subpackage modules
 * @package RosarioSIS
 */

/**
 * School DELETE SQL queries
 *
 * @since 5.2
 *
 * @param int $school_id School ID.
 *
 * @return string School DELETE SQL queries.
 */
function SchoolDeleteSQL( $school_id )
{
	$school_id = intval( $school_id );

	$delete_sql = "DELETE FROM school_gradelevels WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM attendance_calendar WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM attendance_calendars WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM attendance_codes WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM school_periods WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM school_marking_periods WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM eligibility_activities WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM report_card_comments WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM report_card_grade_scales WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM report_card_grades WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM discipline_field_usage WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "UPDATE staff SET CURRENT_SCHOOL_ID=NULL WHERE CURRENT_SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "UPDATE staff SET SCHOOLS=REPLACE(SCHOOLS,'," . $school_id . ",',',');";

	$delete_sql .= "DELETE FROM config WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM program_config WHERE SCHOOL_ID='" . (int) $school_id . "';";

	// Fix SQL error when Parent have students enrolled in deleted school.
	$delete_sql .= "DELETE FROM students_join_users WHERE STUDENT_ID IN(SELECT STUDENT_ID
		FROM student_enrollment
		WHERE SCHOOL_ID='" . (int) $school_id . "'
		AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL ) );";

	$delete_sql .= "DELETE FROM schools WHERE ID='" . (int) $school_id . "';";

	return $delete_sql;
}
