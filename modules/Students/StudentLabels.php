<?php

require_once 'modules/Students/includes/StudentLabels.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( empty( $_REQUEST['st_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}

	if ( empty( $extra ) )
	{
		$extra = [];
	}

	if ( ! empty( $_REQUEST['mailing_labels'] ) )
	{
		$extra = GetMailingLabelsExtra( $extra );
	}
	else
	{
		$extra = GetStudentLabelsExtra( $extra );
	}

	$RET = GetStuList( $extra );

	if ( empty( $RET ) )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	$handle = PDFstart();

	if ( ! empty( $_REQUEST['mailing_labels'] ) )
	{
		echo MailingLabelsHTML( $RET );
	}
	else
	{
		echo StudentLabelsHTML( $RET );
	}

	PDFstop( $handle );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		$action = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' .
			( empty( $_REQUEST['include_inactive'] ) ?
				'' :
				'&include_inactive=' . $_REQUEST['include_inactive'] ) .
			( empty( $_REQUEST['_search_all_schools'] ) ?
				'' :
				'&_search_all_schools=' . $_REQUEST['_search_all_schools'] ) .
			( User( 'PROFILE' ) === 'admin' ?
				( empty( $_REQUEST['w_course_period_id_which'] ) ?
					'' :
					'&w_course_period_id_which=' . $_REQUEST['w_course_period_id_which'] ) .
				( empty( $_REQUEST['w_course_period_id'] ) ?
					'' :
					'&w_course_period_id=' . $_REQUEST['w_course_period_id'] ) :
				'' ) .
			'&_ROSARIO_PDF=true';

		echo '<form action="' . URLEscape( $action ) . '" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Labels for Selected Students' ) );

		$extra['extra_header_left'] = GetStudentLabelsFormHTML();

		$extra['extra_header_left'] .= GetMailingLabelsFormHTML();

		$extra['extra_header_left'] .= GetStudentLabelsFormJS();

		$max_cols = 3;
		$max_rows = 10;

		$extra['extra_header_left'] .= GetStudentLabelsStartingRowColumnFormHTML( $max_rows, $max_cols );
	}

	Widgets( 'course' );

	$extra['link'] = [ 'FULL_NAME' => false ];

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];

	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) ];

	$extra['options']['search'] = false;

	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . Buttons( _( 'Create Labels for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}
