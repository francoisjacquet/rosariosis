<?php

// Should be included first, in case modfunc is Class Rank Calculate AJAX.
require_once 'modules/Grades/includes/ClassRank.inc.php';
require_once 'modules/Grades/includes/ReportCards.fnc.php';

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( isset( $_REQUEST['mp_arr'] )
		&& isset( $_REQUEST['st_arr'] ) )
	{
		if ( ! empty( $_REQUEST['elements']['freetext'] ) )
		{
			// Bypass strip_tags on the $_REQUEST vars.
			$REQUEST_inputfreetext = DBEscapeString( SanitizeHTML( $_POST['inputfreetext'] ) );

			SaveTemplate( $REQUEST_inputfreetext );
		}

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
		{
			BackPrompt(
				sprintf(
					_( 'No %s were found.' ),
					mb_strtolower( ngettext( 'Final Grade', 'Final Grades', 0 ) )
				)
			);
		}
	}
	else
	{
		BackPrompt( _( 'You must choose at least one student and one marking period.' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . PreparePHP_SELF(
			$_REQUEST,
			[ 'search_modfunc' ],
			[ 'modfunc' => 'save', '_ROSARIO_PDF' => 'true' ]
		) .	'" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Report Cards for Selected Students' ) );

		$extra['extra_header_left'] = ReportCardsIncludeForm();

		// @since 4.5 Add Report Cards header action hook.
		do_action( 'Grades/ReportCards.php|header' );
	}

	$extra['new'] = true;

	$extra['link'] = [ 'FULL_NAME' => false ];

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];

	$extra['columns_before'] = [
		'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' )
	];

	$extra['options']['search'] = false;

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	Widgets( 'course' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' .
			Buttons( _( 'Create Report Cards for Selected Students' ) ) . '</div>';

		echo '</form>';

		// MPs, including History MPs, only for current School Year.
		$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID
			FROM marking_periods
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND DOES_GRADES='Y'" );

		foreach ( (array) $mps_RET as $mp )
		{
			// @since 4.7 Automatic Class Rank calculation.
			ClassRankMaybeCalculate( $mp['MARKING_PERIOD_ID'] );
		}
	}
}
