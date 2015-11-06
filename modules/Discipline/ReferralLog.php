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

	if ( isset( $_REQUEST['advanced'] )
		&& $_REQUEST['advanced'] === 'Y' )
	{
		Widgets( 'all' );
	}

	$extra['WHERE'] .= appendSQL( '', $extra );

	$student_RET = GetStuList( $extra );

	if ( count( $student_RET ) )
	{
		$handle = PDFStart();

		foreach ( (array)$student_RET as $student_id => $student )
		{
			echo ReferralLogGenerate( $student_id, $extra );

			// New page
			echo '<div style="page-break-after: always;"></div>';
		}

		PDFStop( $handle );
	}
	else
		BackPrompt( _( 'No Students were found.' ) );
}
