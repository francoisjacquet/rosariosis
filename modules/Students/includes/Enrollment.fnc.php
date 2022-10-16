<?php
/**
 * Student Enrollment functions
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Check if can enroll student in next school year, true if:
 * - Students have already been rolled
 * - This student has not been rolled
 * - This student has enrollment records
 * - If not "Do not enroll after this school year"
 * - If "Retain" or ("Next grade at current school" and Next Grade Level is not null)
 *
 * @since 10.2 Add "Enroll student for next school year"
 *
 * @param int $student_id SStudent ID.
 *
 * @return bool True if can enroll student in next school year.
 */
function StudentCanEnrollNextSchoolYear( $student_id )
{
	$students_rolled = DBGetOne( "SELECT 1 FROM student_enrollment se,schools s
		WHERE se.SYEAR='" . UserSyear() . "'+1
		AND se.LAST_SCHOOL='" . UserSchool() . "'
		AND s.SYEAR='" . UserSyear() . "'+1" );

	if ( ! $students_rolled )
	{
		return false;
	}

	$this_student_rolled = DBGetOne( "SELECT 1 FROM student_enrollment
		WHERE SYEAR='" . UserSyear() . "'+1
		AND LAST_SCHOOL='" . UserSchool() . "'
		AND STUDENT_ID='" . (int) $student_id . "'" );

	if ( $this_student_rolled )
	{
		return false;
	}

	// SQL ORDER BY fix issue when Transferring to another school & new start date is <= old start date.
	$enrollment_RET = DBGet( "SELECT e.ID,e.ENROLLMENT_CODE,e.DROP_CODE,
		e.SCHOOL_ID,e.NEXT_SCHOOL,e.CALENDAR_ID,e.GRADE_ID
		FROM student_enrollment e
		WHERE e.STUDENT_ID='" . (int) $student_id . "'
		AND e.SYEAR='" . UserSyear() . "'
		AND NEXT_SCHOOL IS NOT NULL
		AND GRADE_ID IS NOT NULL
		ORDER BY e.END_DATE IS NULL,e.END_DATE,e.START_DATE IS NULL,e.START_DATE" );

	if ( empty( $enrollment_RET ) )
	{
		return false;
	}

	$next_school = $enrollment_RET[count($enrollment_RET)]['NEXT_SCHOOL'];
	$gradelevel_id = $enrollment_RET[count($enrollment_RET)]['GRADE_ID'];

	if ( $next_school === '-1' )
	{
		// Do not enroll after this school year.
		return false;
	}

	// FJ do NOT roll students where next grade is NULL.
	$next_grade_is_null = $next_school === '1' // Next grade at current school.
		&& DBGetOne( "SELECT 1
		FROM school_gradelevels g
		WHERE g.ID='" . (int) $gradelevel_id . "'
		AND NEXT_GRADE_ID IS NULL" );

	return ! $next_grade_is_null;
}

/**
 * Enroll student in next school year
 * SQL code adapted from Rollover.php
 *
 * @since 10.2 Add "Enroll student for next school year"
 *
 * @param int $student_id Student ID.
 *
 * @return bool False if cannot enroll student in next school year
 */
function StudentEnrollNextSchoolYear( $student_id )
{
	if ( ! StudentCanEnrollNextSchoolYear( $student_id ) )
	{
		return false;
	}

	$next_start_date = DBDate();

	// ROLL STUDENT TO NEXT GRADE.
	// FJ do NOT roll students where next grade is NULL.
	DBQuery( "INSERT INTO student_enrollment
		(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
			DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
		SELECT SYEAR+1,SCHOOL_ID,STUDENT_ID,
			(SELECT NEXT_GRADE_ID
				FROM school_gradelevels g
				WHERE g.ID=e.GRADE_ID
				LIMIT 1),
			'" . $next_start_date . "' AS START_DATE,NULL AS END_DATE,
			(SELECT ID
				FROM student_enrollment_codes
				WHERE SYEAR=e.SYEAR+1
				AND TYPE='Add'
				AND DEFAULT_CODE='Y'
				LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
			(SELECT CALENDAR_ID
				FROM attendance_calendars
				WHERE ROLLOVER_ID=e.CALENDAR_ID
				LIMIT 1),SCHOOL_ID,SCHOOL_ID
		FROM student_enrollment e
		WHERE e.SYEAR='" . UserSyear() . "'
		AND e.SCHOOL_ID='" . UserSchool() . "'
		AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
			AND '" . DBDate() . "'>=e.START_DATE )
		AND e.NEXT_SCHOOL='" . UserSchool() . "'
		AND (SELECT NEXT_GRADE_ID
			FROM school_gradelevels g
			WHERE g.ID=e.GRADE_ID
			LIMIT 1) IS NOT NULL
		AND e.STUDENT_ID='" . (int) $student_id . "'" );

	// ROLL STUDENTS WHO ARE TO BE RETAINED.
	DBQuery( "INSERT INTO student_enrollment
		(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
			DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
		SELECT SYEAR+1,SCHOOL_ID,
			STUDENT_ID,GRADE_ID,'" . $next_start_date . "' AS START_DATE,
			NULL AS END_DATE,
			(SELECT ID
				FROM student_enrollment_codes
				WHERE SYEAR=e.SYEAR+1
				AND TYPE='Add'
				AND DEFAULT_CODE='Y'
				LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
			(SELECT CALENDAR_ID
				FROM attendance_calendars
				WHERE ROLLOVER_ID=e.CALENDAR_ID
				LIMIT 1),SCHOOL_ID,SCHOOL_ID
		FROM student_enrollment e
		WHERE e.SYEAR='" . UserSyear() . "'
		AND e.SCHOOL_ID='" . UserSchool() . "'
		AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
			AND '" . DBDate() . "'>=e.START_DATE)
		AND e.NEXT_SCHOOL='0'
		AND e.STUDENT_ID='" . (int) $student_id . "'" );

	// ROLL STUDENTS TO NEXT SCHOOL.
	// @since 6.4 SQL Roll students to next school: match Grade Level on Title.
	DBQuery( "INSERT INTO student_enrollment
		(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
			DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
		SELECT SYEAR+1,
			NEXT_SCHOOL,STUDENT_ID,
			(SELECT g.ID
				FROM school_gradelevels g
				WHERE (g.TITLE=(SELECT g2.TITLE
						FROM school_gradelevels g2
						WHERE g2.SCHOOL_ID=e.SCHOOL_ID
						AND g2.ID=e.GRADE_ID)
					OR g.SORT_ORDER=1)
				AND g.SCHOOL_ID=e.NEXT_SCHOOL
				ORDER BY g.SORT_ORDER IS NULL,g.SORT_ORDER DESC
				LIMIT 1),
			'" . $next_start_date . "' AS START_DATE,NULL AS END_DATE,
			(SELECT ID
				FROM student_enrollment_codes
				WHERE SYEAR=e.SYEAR+1
				AND TYPE='Add'
				AND DEFAULT_CODE='Y'
				LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
			(SELECT CALENDAR_ID
				FROM attendance_calendars
				WHERE SCHOOL_ID=e.NEXT_SCHOOL
				AND SYEAR=e.SYEAR+1
				AND DEFAULT_CALENDAR='Y'
				LIMIT 1),NEXT_SCHOOL,SCHOOL_ID
		FROM student_enrollment e
		WHERE e.SYEAR='" . UserSyear() . "'
		AND e.SCHOOL_ID='" . UserSchool() . "'
		AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
			AND '" . DBDate() . "'>=e.START_DATE)
		AND e.NEXT_SCHOOL NOT IN ('" . UserSchool() . "','0','-1')
		AND e.STUDENT_ID='" . (int) $student_id . "'" );

	return true;
}
