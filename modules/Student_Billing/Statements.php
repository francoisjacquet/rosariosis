<?php
/**
* @file $Id: Statements.php 422 2007-02-10 22:08:22Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

//Widgets('all');
Widgets('mailing_labels');
//Widgets('document_template');

if(!$_REQUEST['search_modfunc'] || $_REQUEST['search_modfunc']=='search' || $_ROSARIO['modules_search'])
{
	DrawHeader(ProgramTitle());

	$extra['force_search'] = true;
	$extra['new'] = true;
	$extra['action'] .= "&_ROSARIO_PDF=true";
	Search('student_id',$extra);
}
else
{
	// For the Student Fees / Student Payments programs
	$_REQUEST['print_statements'] = true;
	if($_REQUEST['mailing_labels']=='Y')
		$extra['group'][] = 'ADDRESS_ID';	
	
//modif Francois: fix Advanced Search
	$extra['WHERE'] .= appendSQL('',$extra);
	$extra['WHERE'] .= CustomFields('where');
	$RET = GetStuList($extra);
	if(count($RET))
	{
		$SESSION_student_id_save = $_SESSION['student_id'];
		$handle = PDFStart();
		foreach($RET as $student)
		{
			if($_REQUEST['mailing_labels']=='Y')
			{
				foreach($student as $address)
				{
					echo '<BR /><BR /><BR />';
					unset($_ROSARIO['DrawHeader']);
					DrawHeader(_('Statement'));
					DrawHeader($address['FULL_NAME'],$address['STUDENT_ID']);
					DrawHeader($address['GRADE_ID']);
					DrawHeader(GetSchool(UserSchool()));
					DrawHeader(ProperDate(DBDate()));
		
					echo '<BR /><BR /><TABLE class="width-100p"><TR><TD style="width:50px;"> &nbsp; </TD><TD>'.$address['MAILING_LABEL'].'</TD></TR></TABLE><BR />';
					
					$_SESSION['student_id'] = $address['STUDENT_ID'];
					include('modules/Student_Billing/StudentFees.php');
					include('modules/Student_Billing/StudentPayments.php');
					echo '<div style="page-break-after: always;"></div>';				
				}
			}
			else
			{
				$_SESSION['student_id'] = $student['STUDENT_ID'];
				unset($_ROSARIO['DrawHeader']);
				DrawHeader(_('Statement'));
				DrawHeader($student['FULL_NAME'],$student['STUDENT_ID']);
				DrawHeader($student['GRADE_ID']);
				DrawHeader(GetSchool(UserSchool()));
				DrawHeader(ProperDate(DBDate()));
				include('modules/Student_Billing/StudentFees.php');
				include('modules/Student_Billing/StudentPayments.php');
				echo '<div style="page-break-after: always;"></div>';
			}
		}
		//unset($_SESSION['student_id']);
		$_SESSION['student_id'] = $SESSION_student_id_save;
		PDFStop($handle);
	}
	else
		BackPrompt(_('No Students were found.'));
}
?>