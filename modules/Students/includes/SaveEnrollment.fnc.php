<?php

/*
 * save student enrollment
 * create new enrollment when adding student
 */
function SaveEnrollment()
{
	global $error, $student_id;

	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values' );

	if ( empty( $_REQUEST['values']['student_enrollment'] ) )
	{
		return;
	}

	foreach ( (array) $_REQUEST['values']['student_enrollment'] as $stu_enrol_id => $stu_enrol )
	{
		if ( $stu_enrol_id == 'new' && ! $stu_enrol['START_DATE'] )
		{
			unset( $_REQUEST['values']['student_enrollment'][$stu_enrol_id] );

			continue;
		}

		if ( $stu_enrol_id == 'new'
			&& $stu_enrol['SCHOOL_ID'] )
		{
			// Check if student already enrolled on that date when inserting START_DATE.
			$found = DBGetOne( "SELECT ID
				FROM student_enrollment
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND SYEAR='" . UserSyear() . "'
				AND '" . $stu_enrol['START_DATE'] . "' BETWEEN START_DATE AND END_DATE" );

			if ( $found )
			{
				unset( $_REQUEST['values']['student_enrollment'][$stu_enrol_id] );

				$error[] = _( 'The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.' );
			}
			elseif ( $stu_enrol['SCHOOL_ID'] != UserSchool() )
			{
				// @since 5.4 Update current school to enrollment school.
				$_SESSION['UserSchool'] = DBGetOne( "SELECT ID FROM schools
					WHERE SYEAR='" . UserSyear() . "'
					AND ID='" . (int) $stu_enrol['SCHOOL_ID'] . "'" );
			}

			continue;
		}

		if ( ! UserStudentID() )
		{
			continue;
		}

		if ( ! empty( $stu_enrol['START_DATE'] ) )
		{
			// Check if student already enrolled on that date when updating START_DATE.
			$found = DBGetOne( "SELECT ID
				FROM student_enrollment
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND SYEAR='" . UserSyear() . "'
				AND '" . $stu_enrol['START_DATE'] . "' BETWEEN START_DATE AND END_DATE" );

			if ( $found )
			{
				unset( $_REQUEST['values']['student_enrollment'][$stu_enrol_id] );

				$error[] = _( 'The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.' );
			}

			continue;
		}

		// @since 5.4 Delete enrollment record if start date is empty.
		// Check first if Student has previous enrollment records.
		$previous_enrollment_school_id = DBGetOne( "SELECT SCHOOL_ID
			FROM student_enrollment
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND ID<>'" . (int) $stu_enrol_id . "'
			AND START_DATE<(SELECT START_DATE
				FROM student_enrollment
				WHERE ID='" . (int) $stu_enrol_id . "')
			ORDER BY START_DATE DESC;" );

		if ( $previous_enrollment_school_id )
		{
			DBQuery( "DELETE FROM student_enrollment
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND SYEAR='" . UserSyear() . "'
				AND ID='" . (int) $stu_enrol_id . "';" );

			unset( $_REQUEST['values']['student_enrollment'][$stu_enrol_id] );

			if ( $previous_enrollment_school_id != UserSchool() )
			{
				// @since 11.5 Update current school to enrollment school.
				$_SESSION['UserSchool'] = DBGetOne( "SELECT ID FROM schools
					WHERE SYEAR='" . UserSyear() . "'
					AND ID='" . (int) $has_previous_enrollment_school_id . "'" );
			}
		}
	}

	foreach ( $_REQUEST['values']['student_enrollment'] as $id => $columns )
	{
		$columns['SYEAR'] = UserSyear();

		$where_columns = [ 'STUDENT_ID' => ( UserStudentID() ? UserStudentID() : $student_id ) ];

		if ( $id !== 'new' )
		{
			$where_columns['ID'] = $id;
		}

		DBUpsert(
			'student_enrollment',
			$columns,
			$where_columns,
			( $id === 'new' ? 'insert' : 'update' )
		);
	}
}
