<?php

Widgets( 'mailing_labels' );
Widgets( 'balance' );

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	DrawHeader( ProgramTitle() );

	$extra['new'] = true;
	$extra['action'] = empty( $extra['action'] ) ? '&_ROSARIO_PDF=true' : $extra['action'] . '&_ROSARIO_PDF=true';

	Search( 'student_id', $extra );
}
else
{
	// For the Student Fees / Student Payments programs.
	$_REQUEST['print_statements'] = true;

	if ( isset( $_REQUEST['mailing_labels'] )
		&& $_REQUEST['mailing_labels'] === 'Y' )
	{
		$extra['group'][] = 'ADDRESS_ID';
	}

	$students_RET = GetStuList( $extra );

	if ( ! empty( $students_RET ) )
	{
		$SESSION_student_id_save = UserStudentID();

		$handle = PDFStart();

		foreach ( (array) $students_RET as $student )
		{
			if ( isset( $_REQUEST['mailing_labels'] )
				&& $_REQUEST['mailing_labels'] === 'Y' )
			{
				foreach ( (array) $student as $address )
				{
					echo '<br /><br /><br />';

					unset( $_ROSARIO['DrawHeader'] );

					DrawHeader( _( 'Statement' ) );
					DrawHeader( $address['FULL_NAME'], $address['STUDENT_ID'] );
					DrawHeader( $address['GRADE_ID'] );
					DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( DBDate() ) );

					echo '<br /><br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td>
						<td>' . $address['MAILING_LABEL'] . '</td></tr></table><br />';

					SetUserStudentID( $address['STUDENT_ID'] );

					require 'modules/Student_Billing/StudentFees.php';
					require 'modules/Student_Billing/StudentPayments.php';

					echo '<div style="page-break-after: always;"></div>';
				}
			}
			else
			{
				SetUserStudentID( $student['STUDENT_ID'] );

				unset( $_ROSARIO['DrawHeader'] );

				DrawHeader( _( 'Statement' ) );
				DrawHeader( $student['FULL_NAME'], $student['STUDENT_ID'] );
				DrawHeader( $student['GRADE_ID'] );
				DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( DBDate() ) );

				require 'modules/Student_Billing/StudentFees.php';
				require 'modules/Student_Billing/StudentPayments.php';

				echo '<div style="page-break-after: always;"></div>';
			}
		}

		$_SESSION['student_id'] = $SESSION_student_id_save;

		PDFStop( $handle );
	}
	else
		BackPrompt( _( 'No Students were found.' ) );
}
