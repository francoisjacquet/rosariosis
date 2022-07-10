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

	$delete_sql = "DELETE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM attendance_calendar WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM attendance_calendars WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM attendance_codes WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM SCHOOL_MARKING_PERIODS WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM eligibility_activities WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM REPORT_CARD_GRADE_SCALES WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM REPORT_CARD_GRADES WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM discipline_field_usage WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "UPDATE STAFF SET CURRENT_SCHOOL_ID=NULL WHERE CURRENT_SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "UPDATE STAFF SET SCHOOLS=REPLACE(SCHOOLS,'," . $school_id . ",',',');";

	$delete_sql .= "DELETE FROM config WHERE SCHOOL_ID='" . (int) $school_id . "';";

	$delete_sql .= "DELETE FROM program_config WHERE SCHOOL_ID='" . (int) $school_id . "';";

	// Fix SQL error when Parent have students enrolled in deleted school.
	$delete_sql .= "DELETE FROM STUDENTS_JOIN_USERS WHERE STUDENT_ID IN(SELECT STUDENT_ID
		FROM STUDENT_ENROLLMENT
		WHERE SCHOOL_ID='" . (int) $school_id . "'
		AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL ) );";

	$delete_sql .= "DELETE FROM SCHOOLS WHERE ID='" . (int) $school_id . "';";

	return $delete_sql;
}
