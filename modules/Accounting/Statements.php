<?php
if(!isset($_REQUEST['_ROSARIO_PDF']) && (!$_REQUEST['search_modfunc'] || $_REQUEST['search_modfunc']=='search' || $_ROSARIO['modules_search']))
{
	DrawHeader(ProgramTitle());

	$extra['force_search'] = true;
	$extra['new'] = true;
	$extra['action'] .= "&_ROSARIO_PDF=true";
	Search('staff_id',$extra);
}
else
{
	// For the Salaries / Staff Payments programs
	$_REQUEST['print_statements'] = true;

	if(User('PROFILE')=='teacher')//limit to teacher himself
		$extra['WHERE'] .= " AND s.STAFF_ID = '".$_SESSION['STAFF_ID']."'";
		
//modif Francois: fix Advanced Search
	StaffWidgets('all');
	$extra['WHERE'] .= CustomFields('where','staff');
	$RET = GetStaffList($extra);
	if(count($RET))
	{
		$SESSION_staff_id_save = UserStaffID();
		$handle = PDFStart();
		foreach($RET as $staff)
		{
				SetUserStaffID($staff['STAFF_ID']);

				unset($_ROSARIO['DrawHeader']);
				DrawHeader(_('Statement'));
				DrawHeader($staff['FULL_NAME'],$staff['STAFF_ID']);
				DrawHeader($staff['GRADE_ID']);
				DrawHeader(SchoolInfo('TITLE'));
				DrawHeader(ProperDate(DBDate()));
				include('modules/Accounting/Salaries.php');
				include('modules/Accounting/StaffPayments.php');
				echo '<div style="page-break-after: always;"></div>';
		}
		SetUserStaffID($SESSION_staff_id_save);

		PDFStop($handle);
	}
	else
		BackPrompt(_('No Staff were found.'));
}
?>
