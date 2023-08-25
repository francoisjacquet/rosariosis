<?php

/*
 * save student enrollment
 * create new enrollment when adding student
 */
function SaveEnrollment()
{
	global $error, $student_id;

	if ( ! empty( $_POST['month_values']['student_enrollment'] )
		|| ! empty( $_POST['values']['student_enrollment'] ) )
	{
		issetVal( $_REQUEST['month_values']['student_enrollment'] );

		foreach ( (array) $_REQUEST['month_values']['student_enrollment'] as $stu_enrol_id => $stu_enrol_month )
		{
			if ( $stu_enrol_id == 'new' && ! $stu_enrol_month['START_DATE'] )
			{
				unset( $_REQUEST['values']['student_enrollment'][$stu_enrol_id] );
				unset( $_REQUEST['day_values']['student_enrollment'][$stu_enrol_id] );
				unset( $_REQUEST['month_values']['student_enrollment'][$stu_enrol_id] );
				unset( $_REQUEST['year_values']['student_enrollment'][$stu_enrol_id] );
			}
			elseif ( $stu_enrol_id == 'new'
				&& $_REQUEST['values']['student_enrollment']['new']['SCHOOL_ID'] )
			{
				$enrollment_school_id = $_REQUEST['values']['student_enrollment']['new']['SCHOOL_ID'];

				if ( $enrollment_school_id != UserSchool() )
				{
					// @since 5.4 Update current school to enrollment school.
					$_SESSION['UserSchool'] = DBGetOne( "SELECT ID FROM schools
						WHERE SYEAR='" . UserSyear() . "'
						AND ID='" . (int) $enrollment_school_id . "'" );
				}

				if ( ! empty( $stu_enrol_month['START_DATE'] ) )
				{
					$found_RET = 1;

					$date = $_REQUEST['values']['student_enrollment'][$stu_enrol_id]['START_DATE'];

					if ( $date )
					{
						// Check if student already enrolled on that date when inserting START_DATE.
						$found_RET = DBGet( "SELECT ID
							FROM student_enrollment
							WHERE STUDENT_ID='" . UserStudentID() . "'
							AND SYEAR='" . UserSyear() . "'
							AND '" . $date . "' BETWEEN START_DATE AND END_DATE" );
					}

					if ( $found_RET )
					{
						unset( $_REQUEST['values']['student_enrollment'][$stu_enrol_id] );
						unset( $_REQUEST['day_values']['student_enrollment'][$stu_enrol_id] );
						unset( $_REQUEST['month_values']['student_enrollment'][$stu_enrol_id] );
						unset( $_REQUEST['year_values']['student_enrollment'][$stu_enrol_id] );

						if ( $date )
						{
							$error[] = _( 'The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.' );
						}
					}
				}
			}
			elseif ( UserStudentID() && ! empty( $stu_enrol_month['START_DATE'] ) )
			{
				$date = $_REQUEST['values']['student_enrollment'][$stu_enrol_id]['START_DATE'];

				$found_RET = 1;

				if ( $date )
				{
					// Check if student already enrolled on that date when updating START_DATE.
					$found_RET = DBGet( "SELECT ID
						FROM student_enrollment
						WHERE STUDENT_ID='" . UserStudentID() . "'
						AND SYEAR='" . UserSyear() . "'
						AND '" . $date . "' BETWEEN START_DATE AND END_DATE" );
				}

				if ( $found_RET )
				{
					unset( $_REQUEST['values']['student_enrollment'][$stu_enrol_id] );
					unset( $_REQUEST['day_values']['student_enrollment'][$stu_enrol_id] );
					unset( $_REQUEST['month_values']['student_enrollment'][$stu_enrol_id] );
					unset( $_REQUEST['year_values']['student_enrollment'][$stu_enrol_id] );

					if ( $date )
					{
						$error[] = _( 'The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.' );
					}
				}
			}
			elseif ( UserStudentID()
				&& isset( $stu_enrol_month['START_DATE'] )
				&& empty( $stu_enrol_month['START_DATE'] ) )
			{
				// @since 5.4 Delete enrollment record if start date is empty.
				// Check first if Student has previous enrollment records.
				$has_previous_enrollment = DBGetOne( "SELECT 1
					FROM student_enrollment
					WHERE STUDENT_ID='" . UserStudentID() . "'
					AND SYEAR='" . UserSyear() . "'
					AND ID<>'" . (int) $stu_enrol_id . "'
					AND START_DATE<(SELECT START_DATE
						FROM student_enrollment
						WHERE ID='" . (int) $stu_enrol_id . "');" );

				if ( $has_previous_enrollment )
				{
					DBQuery( "DELETE FROM student_enrollment
						WHERE STUDENT_ID='" . UserStudentID() . "'
						AND SYEAR='" . UserSyear() . "'
						AND ID='" . (int) $stu_enrol_id . "';" );
				}
			}
		}

		$iu_extra['student_enrollment'] = "STUDENT_ID='" . ( UserStudentID() ? UserStudentID() : $student_id ) . "' AND ID='__ID__'";
		$iu_extra['fields']['student_enrollment'] = 'SYEAR,STUDENT_ID,';
		$iu_extra['values']['student_enrollment'] = "'" . UserSyear() . "','" . ( UserStudentID() ? UserStudentID() : $student_id ) . "',";

		SaveData( $iu_extra );
	}
}
