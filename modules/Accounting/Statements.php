<?php

StaffWidgets( 'staff_balance' );

if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) && ! $_REQUEST['search_modfunc'] )
{
	DrawHeader( ProgramTitle() );

	$extra['new'] = true;

	$extra['action'] = issetVal( $extra['action'], '' );

	$extra['action'] .= "&_ROSARIO_PDF=true";

	Search( 'staff_id', issetVal( $extra ) );
}
else
{
	// For the Salaries / Staff Payments programs
	$_REQUEST['print_statements'] = true;

	if ( User( 'PROFILE' ) === 'teacher' ) //limit to teacher himself
	{
		$extra['WHERE'] = issetVal( $extra['WHERE'], '' );
		$extra['WHERE'] .= " AND s.STAFF_ID = '" . User( 'STAFF_ID' ) . "'";
	}

	$RET = GetStaffList( $extra );

	if ( ! empty( $RET ) )
	{
		$SESSION_staff_id_save = UserStaffID();
		$handle = PDFStart();

		foreach ( (array) $RET as $staff )
		{
			SetUserStaffID( $staff['STAFF_ID'] );

			unset( $_ROSARIO['DrawHeader'] );
			DrawHeader( _( 'Statement' ) );
			DrawHeader( $staff['FULL_NAME'], $staff['STAFF_ID'] );
			DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( DBDate() ) );

			require 'modules/Accounting/Salaries.php';
			require 'modules/Accounting/StaffPayments.php';

			echo '<div style="page-break-after: always;"></div>';
		}

		$_SESSION['staff_id'] = $SESSION_staff_id_save;

		PDFStop( $handle );
	}
	else
	{
		BackPrompt( _( 'No Staff were found.' ) );
	}
}
