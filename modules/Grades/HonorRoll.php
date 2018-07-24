<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'modules/Grades/includes/HonorRoll.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( count( $_REQUEST['st_arr'] ) )
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
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=save&include_inactive=' .
		( empty( $_REQUEST['include_inactive'] ) ? '' : $_REQUEST['include_inactive'] ) .
		'&_ROSARIO_PDF=true" method="POST" enctype="multipart/form-data">';

		$extra['header_right'] = SubmitButton( _( 'Create Honor Roll for Selected Students' ) );

		$extra['extra_header_left'] = '<table>';

		//FJ add <label> on radio
		$extra['extra_header_left'] .= '<tr><td><label><input type="radio" name="list" value="list"> '._('List').'</label></td></tr>';

		$extra['extra_header_left'] .= '<tr><td><label><input type="radio" name="list" value="" checked /> '._('Certificates').':</label></td></tr>';

		//FJ add TinyMCE to the textarea
		$extra['extra_header_left'] .= '<tr><td>&nbsp;</td></tr>
		<tr class="st"><td class="valign-top">' . _( 'Text' ) . '</td>
		<td class="width-100p">' .
		TinyMCEInput(
			GetTemplate(),
			'honor_roll_text',
			'',
			'class="tinymce-horizontal"'
		) . '</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">'._('Substitutions').':</td><td><table><tr class="st">';
		$extra['extra_header_left'] .= '<td>__FULL_NAME__</td><td>= '._( 'Display Name' ).'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__LAST_NAME__</td><td>= '._('Last Name').'</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>__FIRST_NAME__</td><td>= '._('First Name').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__MIDDLE_NAME__</td><td>= '._('Middle Name').'</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>__SCHOOL_ID__</td><td>= '._('School').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__GRADE_ID__</td><td>= '._('Grade Level').'</td>';
		$extra['extra_header_left'] .= '</tr></table></td></tr>';

		$extra['extra_header_left'] .= HonorRollFrame();

		$extra['extra_header_left'] .= '</table>';
	}

	$extra['new'] = true;

	if ( !isset($_REQUEST['_ROSARIO_PDF']))
	{
		$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
		$extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
		$extra['columns_before'] = array('CHECKBOX' => '</a><input type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.checked,\'st_arr\');"><A>');
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['options']['search'] = false;

	Widgets('course');

	HonorRollWidgets( 'honor_roll' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Create Honor Roll for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}

function _makeChooseCheckbox($value,$title)
{
	if ( $_REQUEST['honor_roll']=='Y' || $_REQUEST['high_honor_roll']=='Y')
		return '<input type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
	else
		return '';
}
