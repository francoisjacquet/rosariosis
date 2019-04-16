<?php

/*
 * save student enrollment
 * create new enrollment when adding student
 */
function SaveEnrollment()
{
	global $error, $student_id;

	if ( $_POST['month_values']['STUDENT_ENROLLMENT'] || $_POST['values']['STUDENT_ENROLLMENT'] )
	{
		//FJ check if student already enrolled on that date when updating START_DATE

		foreach ( (array) $_REQUEST['month_values']['STUDENT_ENROLLMENT'] as $stu_enrol_id => $stu_enrol_month )
		{
			if ( $stu_enrol_id == 'new' && ! $_REQUEST['values']['STUDENT_ENROLLMENT']['new']['ENROLLMENT_CODE'] && ! $_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']['START_DATE'] )
			{
				unset( $_REQUEST['values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
				unset( $_REQUEST['day_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
				unset( $_REQUEST['month_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
				unset( $_REQUEST['year_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
			}
			elseif ( UserStudentID() && $stu_enrol_month['START_DATE'] )
			{
				$date = RequestedDate(
					$_REQUEST['year_values']['STUDENT_ENROLLMENT'][$stu_enrol_id]['START_DATE'],
					$_REQUEST['month_values']['STUDENT_ENROLLMENT'][$stu_enrol_id]['START_DATE'],
					$_REQUEST['day_values']['STUDENT_ENROLLMENT'][$stu_enrol_id]['START_DATE']
				);

				$found_RET = 1;

				if ( $date )
				{
					$found_RET = DBGet( "SELECT ID
						FROM STUDENT_ENROLLMENT
						WHERE STUDENT_ID='" . UserStudentID() . "'
						AND SYEAR='" . UserSyear() . "'
						AND '" . $date . "' BETWEEN START_DATE AND END_DATE" );
				}

				if ( $found_RET )
				{
					unset( $_REQUEST['values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
					unset( $_REQUEST['day_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
					unset( $_REQUEST['month_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
					unset( $_REQUEST['year_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );

					$error[] = _( 'The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.' );
				}
			}
		}

		$iu_extra['STUDENT_ENROLLMENT'] = "STUDENT_ID='" . ( UserStudentID() ? UserStudentID() : $student_id ) . "' AND ID='__ID__'";
		$iu_extra['fields']['STUDENT_ENROLLMENT'] = 'ID,SYEAR,STUDENT_ID,';
		$iu_extra['values']['STUDENT_ENROLLMENT'] = "nextval('STUDENT_ENROLLMENT_SEQ'),'" . UserSyear() . "','" . ( UserStudentID() ? UserStudentID() : $student_id ) . "',";

		SaveData( $iu_extra );
	}
}
