<?php

require_once 'ProgramFunctions/Fields.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	// Add eventual Dates to $_REQUEST['values'].
	if ( isset( $_POST['day_values'], $_POST['month_values'], $_POST['year_values'] ) )
	{
		$requested_dates = RequestedDates(
			$_REQUEST['year_values'],
			$_REQUEST['month_values'],
			$_REQUEST['day_values']
		);

		$_REQUEST['values'] = array_replace_recursive( (array) $_REQUEST['values'], $requested_dates );

		$_POST['values'] = array_replace_recursive( (array) $_POST['values'], $requested_dates );
	}

	if ( count( $_POST['values'] )
		&& count( $_POST['student'] ) )
	{
		if ( $_REQUEST['values']['GRADE_ID']!='')
		{
			$grade_id = $_REQUEST['values']['GRADE_ID'];
			unset($_REQUEST['values']['GRADE_ID']);
		}

		if ( $_REQUEST['values']['NEXT_SCHOOL']!='')
		{
			$next_school = $_REQUEST['values']['NEXT_SCHOOL'];
			unset($_REQUEST['values']['NEXT_SCHOOL']);
		}

		if ( $_REQUEST['values']['CALENDAR_ID'])
		{
			$calendar = $_REQUEST['values']['CALENDAR_ID'];
			unset($_REQUEST['values']['CALENDAR_ID']);
		}

		if ( $_REQUEST['values']['START_DATE']!='')
		{
			$start_date = $_REQUEST['values']['START_DATE'];
			unset($_REQUEST['values']['START_DATE']);
		}

		if ( $_REQUEST['values']['ENROLLMENT_CODE']!='')
		{
			$enrollment_code = $_REQUEST['values']['ENROLLMENT_CODE'];
			unset($_REQUEST['values']['ENROLLMENT_CODE']);
		}

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['values'] = FilterCustomFieldsMarkdown( 'CUSTOM_FIELDS', 'values' );

		foreach ( (array) $_REQUEST['values'] as $field => $value)
		{
			if (isset($value) && $value!='')
			{
				$update .= ','.$field."='".$value."'";
				$values_count++;
			}
		}

		foreach ( (array) $_REQUEST['student'] as $student_id => $yes)
		{
			if ( $yes=='Y')
			{
				$students .= ",'".$student_id."'";
				$students_count++;

				//enrollment: update only the LAST enrollment record
				if ( $grade_id!='')
					DBQuery("UPDATE STUDENT_ENROLLMENT SET GRADE_ID='".$grade_id."' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND STUDENT_ID='".$student_id."' ORDER BY START_DATE DESC LIMIT 1)");

				if ( $next_school!='')
					DBQuery("UPDATE STUDENT_ENROLLMENT SET NEXT_SCHOOL='".$next_school."' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND STUDENT_ID='".$student_id."' ORDER BY START_DATE DESC LIMIT 1)");

				if ( $calendar)
					DBQuery("UPDATE STUDENT_ENROLLMENT SET CALENDAR_ID='".$calendar."' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND STUDENT_ID='".$student_id."' ORDER BY START_DATE DESC LIMIT 1)");

				if ( $start_date!='')
				{
					//FJ check if student already enrolled on that date when updating START_DATE
					$found_RET = DBGet(DBQuery("SELECT ID FROM STUDENT_ENROLLMENT WHERE STUDENT_ID='".$student_id."' AND SYEAR='".UserSyear()."' AND '".$start_date."' BETWEEN START_DATE AND END_DATE"));

					if (count($found_RET))
					{
						$error[] = _('The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.');
					}
					else
					{
						DBQuery("UPDATE STUDENT_ENROLLMENT SET START_DATE='".$start_date."' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND STUDENT_ID='".$student_id."' ORDER BY START_DATE DESC LIMIT 1)");
					}
				}

				if ( $enrollment_code!='')
					DBQuery("UPDATE STUDENT_ENROLLMENT SET ENROLLMENT_CODE='".$enrollment_code."' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND STUDENT_ID='".$student_id."' ORDER BY START_DATE DESC LIMIT 1)");

			}
		}

		if ( $values_count && $students_count)
			DBQuery('UPDATE STUDENTS SET '.mb_substr($update,1).' WHERE STUDENT_ID IN ('.mb_substr($students,1).')');
		elseif ( $warning )
			$warning[0] = mb_substr($warning,0,mb_strpos($warning,'. '));
		elseif ( $grade_id=='' && $next_school=='' && ! $calendar && $start_date=='' && $enrollment_code=='')
			$warning[] = _('No data was entered.');

		if ( ! $warning )
			$note[] = button('check') .'&nbsp;'._('The specified information was applied to the selected students.');
	}
	else
		$error[] = _('You must choose at least one field and one student');

	$_REQUEST['modfunc'] = false;
	unset($_REQUEST['values']);
	$_SESSION['_REQUEST_vars']['modfunc'] = false;
	unset($_SESSION['_REQUEST_vars']['values']);
}

DrawHeader(ProgramTitle());

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $warning, 'warning' );


if ( ! $_REQUEST['modfunc'] )
{
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		DrawHeader('',SubmitButton(_('Save')));
		echo '<br />';

		if ( $_REQUEST['category_id'] )
		{
			$fields_RET = DBGet( DBQuery( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS
				FROM CUSTOM_FIELDS
				WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'" ), array(), array( 'TYPE' ) );
		}
		else
		{
			$fields_RET = DBGet( DBQuery( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS
				FROM CUSTOM_FIELDS"), array(), array( 'TYPE' ) );
		}

		$categories_RET = DBGet( DBQuery( "SELECT ID,TITLE
			FROM STUDENT_FIELD_CATEGORIES" ) );

		//FJ css WPadmin
		echo '<div class="center">';

		$category_onchange_URL = "'" . PreparePHP_SELF( $_REQUEST, array( 'category_id' ) ) . "&category_id='";

		echo '<select name="category_id" onchange="ajaxLink(' . $category_onchange_URL . ' + this.options[selectedIndex].value);">';

		echo '<option value="">' . _( 'All Categories' ) . '</option>';

		foreach ( (array) $categories_RET as $category)
			echo '<option value="'.$category['ID'].'"'.($_REQUEST['category_id']==$category['ID']?' selected':'').'>'.ParseMLField($category['TITLE']).'</option>';
		echo '</select>';

		echo '</div><table class="widefat cellspacing-0 center col1-align-right">';

		if ( isset( $fields_RET['text'] ) )
		{
			foreach ( (array) $fields_RET['text'] as $field )
			{
				echo '<tr class="st"><td><b>' . ParseMLField( $field['TITLE'] ) .'</b></td>
				<td>' . _makeTextInput( 'CUSTOM_' . $field['ID'] ) . '</td></tr>';
			}
		}

		if ( isset( $fields_RET['numeric'] ) )
		{
			foreach ( (array) $fields_RET['numeric'] as $field )
			{
				echo '<tr class="st"><td><b>' . ParseMLField( $field['TITLE'] ) . '</b></td>
				<td>' . _makeTextInput( 'CUSTOM_' . $field['ID'], true ) . '</td></tr>';
			}
		}

		if ( isset( $fields_RET['date'] ) )
		{
			foreach ( (array) $fields_RET['date'] as $field )
			{
				echo '<tr class="st"><td><b>' . ParseMLField( $field['TITLE'] ) . '</b></td>
				<td>' . _makeDateInput( 'CUSTOM_' . $field['ID'] ) . '</td></tr>';
			}
		}

		// Merge select, autos, edits, exports & codeds
		// (same or similar SELECT output).
		$fields_RET['select_autos_edits_exports_codeds'] = array_merge(
			(array) $fields_RET['select'],
			(array) $fields_RET['autos'],
			(array) $fields_RET['edits'],
			(array) $fields_RET['exports'],
			(array) $fields_RET['codeds']
		);

		// Select.
		foreach ( (array) $fields_RET['select_autos_edits_exports_codeds'] as $field )
		{
			$options = $select_options = array();

			$col_name = 'CUSTOM_' . $field['ID'];

			if ( $field['SELECT_OPTIONS'] )
			{
				$options = explode(
					"\r",
					str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] )
				);
			}

			foreach ( (array) $options as $option )
			{
				$value = $option;

				// Exports specificities.
				if ( $field['TYPE'] === 'exports' )
				{
					$option = explode( '|', $option );

					$option = $value = $option[0];
				}
				// Codeds specificities.
				elseif ( $field['TYPE'] === 'codeds' )
				{
					list( $value, $option ) = explode( '|', $option );
				}

				if ( $value !== ''
					&& $option !== '' )
				{
					$select_options[ $value ] = $option;
				}
			}

			// Get autos / edits pull-down edited options.
			if ( $field['TYPE'] === 'autos'
				|| $field['TYPE'] === 'edits' )
			{
				$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
					FROM STUDENTS s,STUDENT_ENROLLMENT sse
					WHERE sse.STUDENT_ID=s.STUDENT_ID
					AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
					AND s." . $col_name . " IS NOT NULL
					AND s." . $col_name . " != ''
					ORDER BY SORT_KEY";

				$options_RET = DBGet( DBQuery( $sql_options ) );

				// Add the 'new' option, is also the separator.
				$select_options['---'] = '-' . _( 'Edit' ) . '-';

				foreach ( (array) $options_RET as $option )
				{
					if ( ! in_array( $option[ $col_name ], $select_options ) )
					{
						$select_options[ $option[ $col_name ] ] = '<span style="color:blue">' . $option[ $col_name ] . '</span>';
					}
				}
			}

			echo '<tr class="st"><td><b>' . ParseMLField( $field['TITLE'] ) . '</b></td>
			<td>' . _makeSelectInput( $col_name, $select_options ) . '</td></tr>';
		}

		if ( isset( $fields_RET['textarea'] ) )
		{
			foreach ( (array) $fields_RET['textarea'] as $field )
			{
				echo '<tr class="st"><td><b>' . ParseMLField( $field['TITLE'] ) . '</b></td>
				<td>' . _makeTextAreaInput( 'CUSTOM_' . $field['ID'] ) . '</td></tr>';
			}
		}

		if ( ! $_REQUEST['category_id'] || $_REQUEST['category_id']=='1')
		{
			$gradelevels_RET = DBGet( DBQuery( "SELECT ID,TITLE
				FROM SCHOOL_GRADELEVELS
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER" ) );

			$options = array();

			foreach ( (array) $gradelevels_RET as $gradelevel )
			{
				$options[ $gradelevel['ID'] ] = $gradelevel['TITLE'];
			}

			echo '<tr class="st"><td><b>' . _( 'Grade Level' ) . '</b></td>
			<td>' . _makeSelectInput( 'GRADE_ID', $options ) . '</td></tr>';

			$schools_RET = DBGet( DBQuery( "SELECT ID,TITLE
				FROM SCHOOLS
				WHERE ID!='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" ) );

			$options = array(
				UserSchool() => _( 'Next grade at current school' ),
				'0' => _( 'Retain' ),
				'-1' => _( 'Do not enroll after this school year' ),
			);

			foreach ( (array) $schools_RET as $school )
			{
				$options[ $school['ID'] ] = $school['TITLE'];
			}

			echo '<tr class="st"><td><b>' . _( 'Rolling / Retention Options' ) . '</b></td>
			<td>' . _makeSelectInput( 'NEXT_SCHOOL', $options ) . '</td></tr>';

			$calendars_RET = DBGet( DBQuery( "SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE
				FROM ATTENDANCE_CALENDARS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY DEFAULT_CALENDAR ASC" ) );

			$options = array();

			foreach ( (array) $calendars_RET as $calendar )
			{
				$options[ $calendar['CALENDAR_ID'] ] = $calendar['TITLE'];
			}

			echo '<tr class="st"><td><b>' . _( 'Calendar' ) . '</b></td>
			<td>' . _makeSelectInput( 'CALENDAR_ID', $options ) . '</td></tr>';

			$enrollment_codes_RET = DBGet( DBQuery( "SELECT ID,TITLE AS TITLE
				FROM STUDENT_ENROLLMENT_CODES
				WHERE SYEAR='" . UserSyear() . "'
				AND TYPE='Add'
				ORDER BY SORT_ORDER" ) );

			$options = array();

			foreach ( (array) $enrollment_codes_RET as $enrollment_code )
			{
				$options[ $enrollment_code['ID'] ] = $enrollment_code['TITLE'];
			}

			echo '<tr class="st"><td><b>' . _( 'Attendance Start Date this School Year' ) . '</b></td>
			<td class="nobr">' . _makeDateInput( 'START_DATE' ) . ' - ' .
				_makeSelectInput( 'ENROLLMENT_CODE', $options ) . '</td></tr>';
		}

		if ( isset( $fields_RET['radio'] ) )
		{
			foreach ( $fields_RET['radio'] as $field )
			{
				echo '<tr class="st"><td><b>' . ParseMLField( $field['TITLE'] ) . '</b></td>
				<td>' . _makeCheckboxInput(	'CUSTOM_' . $field['ID'] ) . '</td></tr>';
			}
		}

		echo '</table><br />';
	}

	//Widgets('activity');
	//Widgets('course');
	//Widgets('absences');

	$extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX' => '</a><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'student\');"><A>');
	$extra['new'] = true;

	Search('student_id',$extra);
	if ( $_REQUEST['search_modfunc']=='list')
		echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}


function _makeChooseCheckbox( $value, $title = '' )
{
	global $THIS_RET;

	return '<input type="checkbox" name="student[' . $THIS_RET['STUDENT_ID'] . ']" value="Y" />';
}

function _makeTextInput( $column, $numeric = false )
{
	if ( $numeric === true )
	{
		$options = 'size=10 maxlength=11';
	}
	else
	{
		$options = 'size=20';
	}

	return TextInput( '', 'values[' . $column . ']', '', $options );
}

function _makeTextAreaInput( $column )
{
	return TextAreaInput( '', 'values[' . $column . ']' );
}

function _makeDateInput( $column )
{
	return DateInput( '', 'values[' . $column . ']', '' );
}

function _makeSelectInput( $column, $options )
{
	return SelectInput( '', 'values[' . $column . ']', '', $options, _( 'N/A' ), "style='max-width:190px;'" );
}

function _makeCheckboxInput( $column )
{
	return CheckboxInput( '', 'values[' . $column . ']', '', '', true );
}
