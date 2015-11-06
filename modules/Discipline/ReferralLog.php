<?php

//FJ create ReferralLog functions for reuse
require_once 'modules/Discipline/includes/ReferralLog.fnc.php';

$extra['new'] = true;

$extra['action'] .= '&_ROSARIO_PDF=true';

if ( !$_REQUEST['search_modfunc'] )
{
	DrawHeader( ProgramTitle() );
	
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
	
	$student_RET = GetStuList( $extra );

	if ( count( $student_RET ) )
	{
		$PDF = '';

		foreach ( (array)$student_RET as $student_id => $student )
		{
			$referral_log = ReferralLogGenerate( $student_id, $extra );

			if ( $referral_log )
			{
				// New page
				$PDF .=  $referral_log . '<div style="page-break-after: always;"></div>';
			}
		}

		if ( !empty( $PDF ) )
		{
			$handle = PDFStart();

			echo $PDF;

			PDFStop( $handle );
		}
		else
			BackPrompt( _( 'No Students were found.' ) );
	}
	else
		BackPrompt( _( 'No Students were found.' ) );
}
