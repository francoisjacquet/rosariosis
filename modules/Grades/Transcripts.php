<?php

// Should be included first, in case modfunc is Class Rank Calculate AJAX.
require_once 'modules/Grades/includes/ClassRank.inc.php';
require_once 'modules/Grades/includes/Transcripts.fnc.php';

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['mp_type_arr'] )
		&& ! empty( $_REQUEST['st_arr'] ) )
	{
		if ( ! empty( $_REQUEST['showcertificate'] ) )
		{
			// FJ bypass strip_tags on the $_REQUEST vars.
			$REQUEST_inputcertificatetext = DBEscapeString( SanitizeHTML( $_POST['inputcertificatetext'] ) );

			SaveTemplate( $REQUEST_inputcertificatetext );
		}

		$transcripts = TranscriptsGenerate(
			$_REQUEST['st_arr'],
			$_REQUEST['mp_type_arr'],
			issetVal( $_REQUEST['syear_arr'], [] )
		);

		/**
		 * Report Cards array hook action.
		 *
		 * @since 4.8
		 */
		do_action( 'Grades/Transcripts.php|transcripts_html_array' );

		if ( $transcripts )
		{
			$transcripts_html = '<style type="text/css"> * {font-size:large; line-height:1.2;} </style>';

			// Insert page breaks
			$transcripts_html .= implode(
				'<div style="page-break-after: always;"></div>',
				$transcripts
			);

			// PDF
			$handle = PDFStart();

			echo $transcripts_html;

			PDFStop( $handle );
		}
		else
		{
			BackPrompt(
				sprintf(
					_( 'No %s were found.' ),
					mb_strtolower( ngettext( 'Grade', 'Grades', 0 ) )
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
		//FJ include gentranscript.php in Transcripts.php
		//echo '<form action="modules/Grades/gentranscript.php" method="POST">';
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true' ) . '" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Transcripts for Selected Students' ) );

		$extra['extra_header_left'] = TranscriptsIncludeForm();

		// @since 4.8 Add Transcripts header action hook.
		do_action( 'Grades/Transcripts.php|header' );
	}

	$extra['new'] = true;

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) ];
	$extra['options']['search'] = false;

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	Widgets( 'course' );
	Widgets( 'gpa' );
	Widgets( 'class_rank' );
	Widgets( 'letter_grade' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . Buttons( _( 'Create Transcripts for Selected Students' ) ) . '</div>';
		echo '</form>';

		// MPs, including History MPs, excluding Progress Periods.
		$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID
			FROM marking_periods
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND MP_TYPE IN ('semester','year','quarter')
			AND DOES_GRADES='Y'" );

		foreach ( (array) $mps_RET as $mp )
		{
			// @since 4.7 Automatic Class Rank calculation.
			ClassRankMaybeCalculate( $mp['MARKING_PERIOD_ID'] );
		}
	}
}
