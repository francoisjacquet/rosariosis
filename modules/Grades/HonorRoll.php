<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

require_once 'modules/Grades/includes/HonorRoll.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		if ( empty( $_REQUEST['subject_id'] ) )
		{
			HonorRollPDF(
				$_REQUEST['st_arr'],
				! empty( $_REQUEST['list'] ),
				$_POST['honor_roll_text']
			);
		}
		else
		{
			HonorRollSubjectPDF(
				$_REQUEST['st_arr'],
				! empty( $_REQUEST['list'] ),
				$_POST['honor_roll_text']
			);
		}
	}
	else
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=save&include_inactive=' . issetVal( $_REQUEST['include_inactive'] ) .
			'&_ROSARIO_PDF=true' ) . '" method="POST" enctype="multipart/form-data">';

		$extra['header_right'] = SubmitButton( _( 'Create Honor Roll for Selected Students' ) );

		$extra['extra_header_left'] = '<table class="width-100p">';

		//FJ add <label> on radio
		$extra['extra_header_left'] .= '<tr><td><label><input type="radio" name="list" value="list"> ' . _( 'List' ) . '</label></td></tr>';

		$extra['extra_header_left'] .= '<tr><td><label><input type="radio" name="list" value="" checked /> ' . _( 'Certificates' ) . ':</label></td></tr>';

		//FJ add TinyMCE to the textarea
		$extra['extra_header_left'] .= '<tr class="st"><td>' .
		TinyMCEInput(
			GetTemplate(),
			'honor_roll_text',
			_( 'Text' ),
			'class="tinymce-horizontal"'
		) . '</td></tr>';

		$substitutions = [
			'__FULL_NAME__' => _( 'Display Name' ),
			'__LAST_NAME__' => _( 'Last Name' ),
			'__FIRST_NAME__' => _( 'First Name' ),
			'__MIDDLE_NAME__' =>  _( 'Middle Name' ),
			'__SCHOOL_ID__' => _( 'School' ),
			'__GRADE_ID__' => _( 'Grade Level' ),
		];

		if ( ! empty( $_REQUEST['subject_id'] ) )
		{
			$substitutions['__SUBJECT__'] = _( 'Subject' );
		}

		$substitutions += SubstitutionsCustomFields( 'STUDENT' );

		$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">' .
			SubstitutionsInput( $substitutions ) .
		'<hr></td></tr>';

		$extra['extra_header_left'] .= HonorRollFrame();

		$extra['extra_header_left'] .= '</table>';
	}

	$extra['new'] = true;

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
		$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
		$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', '', 'st_arr' ) ];
	}

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['options']['search'] = false;

	Widgets( 'course' );

	HonorRollWidgets( 'honor_roll' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Create Honor Roll for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}

/**
 * Make Choose Checkbox
 *
 * Local function
 * DBGet() callback
 *
 * @uses MakChooseCheckbox
 *
 * @param  string $value  STUDENT_ID value.
 * @param  string $column 'CHECKBOX'.
 *
 * @return string Checkbox or empty string if no (High) Honor Roll requested.
 */
function _makeChooseCheckbox( $value, $column )
{
	if ( $_REQUEST['honor_roll'] === 'Y'
		|| $_REQUEST['high_honor_roll'] === 'Y' )
	{
		return MakeChooseCheckbox( $value, $column );
	}
	else
	{
		return '';
	}
}
