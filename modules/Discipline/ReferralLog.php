<?php

//FJ create ReferralLog functions for reuse
require_once 'modules/Discipline/includes/ReferralLog.fnc.php';

$extra['second_col'] .= ReferralLogIncludeForm();

//Widgets('all');
$extra['new'] = true;

$extra['action'] .= '&_ROSARIO_PDF=true';

if ( !$_REQUEST['search_modfunc'] )
{
	DrawHeader( ProgramTitle() );
	
	Search( 'student_id', $extra );
}
else
{
	if ( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		DrawHeader( ProgramTitle() );

		echo '<BR /><BR />';
	}
	
	$extra['WHERE'] .= appendSQL( '', $extra );
	
	$student_RET = GetStuList( $extra );

	if ( count( $student_RET ) )
	{
		$handle = PDFStart();

		foreach ( (array)$student_RET as $student_id => $student )
		{
			echo ReferralLogGenerate( $student_id );

			// New page
			echo '<div style="page-break-after: always;"></div>';
		}

		PDFStop( $handle );
	}
	else
		BackPrompt( _( 'No Students were found.' ) );
}
