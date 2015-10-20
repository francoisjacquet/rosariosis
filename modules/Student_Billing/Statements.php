<?php

//Widgets('all');
Widgets('mailing_labels');
//Widgets('document_template');

if (!$_REQUEST['search_modfunc'])
{
	DrawHeader(ProgramTitle());

	$extra['new'] = true;
	$extra['action'] .= "&_ROSARIO_PDF=true";
	Search('student_id',$extra);
}
else
{
	// For the Student Fees / Student Payments programs
	$_REQUEST['print_statements'] = true;
	if ($_REQUEST['mailing_labels']=='Y')
		$extra['group'][] = 'ADDRESS_ID';	
	
//FJ fix Advanced Search
	$extra['WHERE'] .= appendSQL('',$extra);
	$extra['WHERE'] .= CustomFields('where');
	$RET = GetStuList($extra);
	if (count($RET))
	{
		$SESSION_student_id_save = UserStudentID();
		$handle = PDFStart();
		foreach ( (array)$RET as $student)
		{
			if ($_REQUEST['mailing_labels']=='Y')
			{
				foreach ( (array)$student as $address)
				{
					echo '<BR /><BR /><BR />';
					unset($_ROSARIO['DrawHeader']);
					DrawHeader(_('Statement'));
					DrawHeader($address['FULL_NAME'],$address['STUDENT_ID']);
					DrawHeader($address['GRADE_ID']);
					DrawHeader(SchoolInfo('TITLE'));
					DrawHeader(ProperDate(DBDate()));
		
					echo '<BR /><BR /><TABLE class="width-100p"><TR><TD style="width:50px;"> &nbsp; </TD><TD>'.$address['MAILING_LABEL'].'</TD></TR></TABLE><BR />';
					
					SetUserStudentID($address['STUDENT_ID']);

					include('modules/Student_Billing/StudentFees.php');
					include('modules/Student_Billing/StudentPayments.php');
					echo '<div style="page-break-after: always;"></div>';				
				}
			}
			else
			{
				SetUserStudentID($student['STUDENT_ID']);

				unset($_ROSARIO['DrawHeader']);
				DrawHeader(_('Statement'));
				DrawHeader($student['FULL_NAME'],$student['STUDENT_ID']);
				DrawHeader($student['GRADE_ID']);
				DrawHeader(SchoolInfo('TITLE'));
				DrawHeader(ProperDate(DBDate()));
				include('modules/Student_Billing/StudentFees.php');
				include('modules/Student_Billing/StudentPayments.php');
				echo '<div style="page-break-after: always;"></div>';
			}
		}

		$_SESSION['student_id'] = $SESSION_student_id_save;

		PDFStop($handle);
	}
	else
		BackPrompt(_('No Students were found.'));
}
