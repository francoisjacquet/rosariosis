<?php

require_once 'modules/Grades/includes/ReportCards.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( isset( $_REQUEST['mp_arr'] )
		&& isset( $_REQUEST['st_arr'] ) )
	{
		$report_cards = ReportCardsGenerate( $_REQUEST['st_arr'], $_REQUEST['mp_arr'] );

		if ( $report_cards )
		{
			// Insert page breaks
			$report_cards_html = implode(
				'<div style="page-break-after: always;"></div>',
				$report_cards
			);

			// PDF
			$handle = PDFStart();

			echo $report_cards_html;

			PDFStop( $handle );
		}
		else
			BackPrompt( _( 'No Students were found.' ) );
	}
	else
		BackPrompt( _( 'You must choose at least one student and one marking period.' ) );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<FORM action="' . PreparePHP_SELF(
			$_REQUEST,
			array( 'search_modfunc' ),
			array( 'modfunc' => 'save', '_ROSARIO_PDF' => 'true' )
		) .	'" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Report Cards for Selected Students' ) );

		$extra['extra_header_left'] = ReportCardsIncludeForm();
	}

	$extra['new'] = true;

	$extra['link'] = array( 'FULL_NAME' => false );

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = array( 'CHECKBOX' => '_makeChooseCheckbox' );

	$extra['columns_before'] = array(
		'CHECKBOX' => '</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.checked,\'st_arr\');" /><A>'
	);

	$extra['options']['search'] = false;

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	Widgets( 'course' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<BR /><div class="center">' .
			Buttons( _( 'Create Report Cards for Selected Students' ) ) . '</div>';

		echo '</FORM>';
	}
}

function _makeChooseCheckbox( $value, $title )
{
	return '<INPUT type="checkbox" name="st_arr[]" value="' . $value . '" checked />';
}
