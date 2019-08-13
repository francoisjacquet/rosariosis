<?php

//FJ create ReferralLog functions for reuse
require_once 'modules/Discipline/includes/ReferralLog.fnc.php';

$extra['new'] = true;

$extra['action'] = issetVal( $extra['action'], '' );

$extra['action'] .= '&_ROSARIO_PDF=true';

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	DrawHeader( ProgramTitle() );

	$extra['second_col'] = issetVal( $extra['second_col'], '' );

	$extra['second_col'] .= ReferralLogIncludeForm();

	Search( 'student_id', $extra );
}
else
{
	if ( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		DrawHeader( ProgramTitle() );

		echo '<BR /><BR />';
	}

	$PDF = '';

	// Generate and get Discipline Logs
	$referral_logs = ReferralLogsGenerate( $extra );

	if ( empty( $referral_logs ) )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	// New page
	$PDF =  implode( '<div style="page-break-after: always;"></div>', $referral_logs );

	if ( !empty( $PDF ) )
	{
		$handle = PDFStart();

		echo $PDF;

		PDFStop( $handle );
	}
}
