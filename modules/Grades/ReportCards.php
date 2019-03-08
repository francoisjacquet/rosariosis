<?php

require_once 'modules/Grades/includes/ReportCards.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( isset( $_REQUEST['mp_arr'] )
		&& isset( $_REQUEST['st_arr'] ) )
	{
		$report_cards = ReportCardsGenerate( $_REQUEST['st_arr'], $_REQUEST['mp_arr'] );

		/**
		 * Report Cards array hook action.
		 *
		 * @since 4.0
		 */
		do_action( 'Grades/ReportCards.php|report_cards_html_array' );

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

		// @since 4.5 Add Report Cards header action hook.
		do_action( 'Grades/ReportCards.php|header' );
	}

	$extra['new'] = true;

	$extra['link'] = array( 'FULL_NAME' => false );

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );

	$extra['columns_before'] = array(
		'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' )
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
