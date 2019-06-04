<?php

require_once 'ProgramFunctions/Fields.fnc.php';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values', 'post' );

	if ( ! empty( $_POST['values'] )
		&& ! empty( $_POST['student'] ) )
	{
		if ( $_REQUEST['values']['GRADE_ID'] != '' )
		{
			$grade_id = $_REQUEST['values']['GRADE_ID'];
			unset( $_REQUEST['values']['GRADE_ID'] );
		}

		if ( $_REQUEST['values']['NEXT_SCHOOL'] != '' )
		{
			$next_school = $_REQUEST['values']['NEXT_SCHOOL'];
			unset( $_REQUEST['values']['NEXT_SCHOOL'] );
		}

		if ( ! empty( $_REQUEST['values']['CALENDAR_ID'] ) )
		{
			$calendar = $_REQUEST['values']['CALENDAR_ID'];
			unset( $_REQUEST['values']['CALENDAR_ID'] );
		}

		if ( $_REQUEST['values']['START_DATE'] != '' )
		{
			$start_date = $_REQUEST['values']['START_DATE'];
			unset( $_REQUEST['values']['START_DATE'] );
		}

		if ( $_REQUEST['values']['ENROLLMENT_CODE'] != '' )
		{
			$enrollment_code = $_REQUEST['values']['ENROLLMENT_CODE'];
			unset( $_REQUEST['values']['ENROLLMENT_CODE'] );
		}

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['values'] = FilterCustomFieldsMarkdown( 'CUSTOM_FIELDS', 'values' );

		foreach ( (array) $_REQUEST['values'] as $field => $value )
		{
			if ( isset( $value ) && $value != '' )
			{
				$update .= ',' . DBEscapeIdentifier( $field ) . "='" . $value . "'";
				$values_count++;
			}
		}

		foreach ( (array) $_REQUEST['student'] as $student_id )
		{
			$students .= ",'" . $student_id . "'";
			$students_count++;

			//enrollment: update only the LAST enrollment record

			if ( $grade_id != '' )
			{
				DBQuery( "UPDATE STUDENT_ENROLLMENT SET GRADE_ID='" . $grade_id . "' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND STUDENT_ID='" . $student_id . "' ORDER BY START_DATE DESC LIMIT 1)" );
			}

			if ( $next_school != '' )
			{
				DBQuery( "UPDATE STUDENT_ENROLLMENT SET NEXT_SCHOOL='" . $next_school . "' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND STUDENT_ID='" . $student_id . "' ORDER BY START_DATE DESC LIMIT 1)" );
			}

			if ( $calendar )
			{
				DBQuery( "UPDATE STUDENT_ENROLLMENT SET CALENDAR_ID='" . $calendar . "' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND STUDENT_ID='" . $student_id . "' ORDER BY START_DATE DESC LIMIT 1)" );
			}

			if ( $start_date != '' )
			{
				//FJ check if student already enrolled on that date when updating START_DATE
				$found_RET = DBGet( "SELECT ID
					FROM STUDENT_ENROLLMENT
					WHERE STUDENT_ID='" . $student_id . "'
					AND SYEAR='" . UserSyear() . "'
					AND '" . $start_date . "' BETWEEN START_DATE AND END_DATE" );

				if ( ! empty( $found_RET ) )
				{
					$error[] = _( 'The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.' );
				}
				else
				{
					DBQuery( "UPDATE STUDENT_ENROLLMENT SET START_DATE='" . $start_date . "' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND STUDENT_ID='" . $student_id . "' ORDER BY START_DATE DESC LIMIT 1)" );
				}
			}

			if ( $enrollment_code != '' )
			{
				DBQuery( "UPDATE STUDENT_ENROLLMENT SET ENROLLMENT_CODE='" . $enrollment_code . "' WHERE ID=(SELECT ID FROM STUDENT_ENROLLMENT WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND STUDENT_ID='" . $student_id . "' ORDER BY START_DATE DESC LIMIT 1)" );
			}
		}

		if ( $values_count && $students_count )
		{
			DBQuery( 'UPDATE STUDENTS SET ' . mb_substr( $update, 1 ) . ' WHERE STUDENT_ID IN (' . mb_substr( $students, 1 ) . ')' );
		}
		elseif ( $warning )
		{
			$warning[0] = mb_substr( $warning, 0, mb_strpos( $warning, '. ' ) );
		}
		elseif ( $grade_id == '' && $next_school == '' && ! $calendar && $start_date == '' && $enrollment_code == '' )
		{
			$warning[] = _( 'No data was entered.' );
		}

		if ( ! $warning )
		{
			$note[] = button( 'check' ) . '&nbsp;' . _( 'The specified information was applied to the selected students.' );
		}
	}
	else
	{
		$error[] = _( 'You must choose at least one field and one student' );
	}

	// Unset modfunc & values & redirect URL.
	RedirectURL( array( 'modfunc', 'values' ) );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $warning, 'warning' );

if ( ! $_REQUEST['modfunc'] )
{
	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';
		DrawHeader( '', SubmitButton() );
		echo '<br />';

		if ( ! empty( $_REQUEST['category_id'] ) )
		{
			$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS
				FROM CUSTOM_FIELDS
				WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'", array(), array( 'TYPE' ) );
		}
		else
		{
			$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS
				FROM CUSTOM_FIELDS", array(), array( 'TYPE' ) );
		}

		// Only display Categories having fields.
		$categories_RET = DBGet( "SELECT sfc.ID,sfc.TITLE
			FROM STUDENT_FIELD_CATEGORIES sfc
			WHERE EXISTS(SELECT 1 FROM CUSTOM_FIELDS cf
				WHERE cf.CATEGORY_ID=sfc.ID)" );

		echo '<table class="widefat center"><tr><td><div class="center">';

		$category_onchange_URL = "'" . PreparePHP_SELF( $_REQUEST, array( 'category_id' ) ) . "&category_id='";

		echo '<select name="category_id" id="category_id" onchange="ajaxLink(' .
			$category_onchange_URL . ' + this.options[selectedIndex].value);">';

		echo '<option value="">' . _( 'All Categories' ) . '</option>';

		foreach ( (array) $categories_RET as $category )
		{
			echo '<option value="' . $category['ID'] . '"' .
				( $_REQUEST['category_id'] == $category['ID'] ? ' selected' : '' ) . '>' .
				ParseMLField( $category['TITLE'] ) . '</option>';
		}

		echo '</select>';

		echo FormatInputTitle(
			'<span class="a11y-hidden">' . _( 'Student Info' ) . '</span>',
			'category_id'
		);

		echo '</div></td></tr>';

		if ( isset( $fields_RET['text'] ) )
		{
			foreach ( (array) $fields_RET['text'] as $field )
			{
				echo '<tr><td>' .
					_makeTextInput( 'CUSTOM_' . $field['ID'], false, ParseMLField( $field['TITLE'] ) ) .
					'</td></tr>';
			}
		}

		if ( isset( $fields_RET['numeric'] ) )
		{
			foreach ( (array) $fields_RET['numeric'] as $field )
			{
				echo '<tr><td>' .
					_makeTextInput( 'CUSTOM_' . $field['ID'], true, ParseMLField( $field['TITLE'] ) ) .
					'</td></tr>';
			}
		}

		if ( isset( $fields_RET['date'] ) )
		{
			foreach ( (array) $fields_RET['date'] as $field )
			{
				echo '<tr><td>' .
					_makeDateInput( 'CUSTOM_' . $field['ID'], ParseMLField( $field['TITLE'] ) ) .
					'</td></tr>';
			}
		}

		// Merge select, autos, exports
		// (same or similar SELECT output).
		$fields_RET['select_autos_exports'] = array_merge(
			(array) $fields_RET['select'],
			(array) $fields_RET['autos'],
			(array) $fields_RET['exports']
		);

		// Select.

		foreach ( (array) $fields_RET['select_autos_exports'] as $field )
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

				if ( $value !== ''
					&& $option !== '' )
				{
					$select_options[$value] = $option;
				}
			}

			if ( $field['TYPE'] === 'autos' )
			{
				// Get autos pull-down edited options.
				$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
					FROM STUDENTS s,STUDENT_ENROLLMENT sse
					WHERE sse.STUDENT_ID=s.STUDENT_ID
					AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
					AND s." . $col_name . " IS NOT NULL
					AND s." . $col_name . " != ''
					ORDER BY SORT_KEY";

				$options_RET = DBGet( $sql_options );

				// Add the 'new' option, is also the separator.
				$select_options['---'] = '-' . _( 'Edit' ) . '-';

				foreach ( (array) $options_RET as $option )
				{
					if ( ! in_array( $option[$col_name], $select_options ) )
					{
						$select_options[$option[$col_name]] = '<span style="color:blue">' . $option[$col_name] . '</span>';
					}
				}
			}

			echo '<tr><td>' .
				_makeSelectInput( $col_name, $select_options, ParseMLField( $field['TITLE'] ) ) .
				'</td></tr>';
		}

		if ( isset( $fields_RET['textarea'] ) )
		{
			foreach ( (array) $fields_RET['textarea'] as $field )
			{
				echo '<tr><td>' .
					_makeTextAreaInput( 'CUSTOM_' . $field['ID'], ParseMLField( $field['TITLE'] ) ) .
					'</td></tr>';
			}
		}

		if ( ! $_REQUEST['category_id'] || $_REQUEST['category_id'] == '1' )
		{
			$gradelevels_RET = DBGet( "SELECT ID,TITLE
				FROM SCHOOL_GRADELEVELS
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER" );

			$options = array();

			foreach ( (array) $gradelevels_RET as $gradelevel )
			{
				$options[$gradelevel['ID']] = $gradelevel['TITLE'];
			}

			echo '<tr><td>' .
				_makeSelectInput( 'GRADE_ID', $options, _( 'Grade Level' ) ) .
				'</td></tr>';

			$schools_RET = DBGet( "SELECT ID,TITLE
				FROM SCHOOLS
				WHERE ID!='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" );

			$options = array(
				UserSchool() => _( 'Next grade at current school' ),
				'0' => _( 'Retain' ),
				'-1' => _( 'Do not enroll after this school year' ),
			);

			foreach ( (array) $schools_RET as $school )
			{
				$options[$school['ID']] = $school['TITLE'];
			}

			echo '<tr><td>' .
				_makeSelectInput( 'NEXT_SCHOOL', $options, _( 'Rolling / Retention Options' ) ) .
				'</td></tr>';

			$calendars_RET = DBGet( "SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE
				FROM ATTENDANCE_CALENDARS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY DEFAULT_CALENDAR ASC" );

			$options = array();

			foreach ( (array) $calendars_RET as $calendar )
			{
				$options[$calendar['CALENDAR_ID']] = $calendar['TITLE'];
			}

			echo '<tr><td>' .
				_makeSelectInput( 'CALENDAR_ID', $options, _( 'Calendar' ) ) .
				'</td></tr>';

			$enrollment_codes_RET = DBGet( "SELECT ID,TITLE AS TITLE
				FROM STUDENT_ENROLLMENT_CODES
				WHERE SYEAR='" . UserSyear() . "'
				AND TYPE='Add'
				ORDER BY SORT_ORDER" );

			$options = array();

			foreach ( (array) $enrollment_codes_RET as $enrollment_code )
			{
				$options[$enrollment_code['ID']] = $enrollment_code['TITLE'];
			}

			echo '<tr><td class="nobr">' .
				_makeDateInput( 'START_DATE' ) . ' - ' .
				_makeSelectInput( 'ENROLLMENT_CODE', $options ) .
				FormatInputTitle( _( 'Attendance Start Date this School Year' ) ) .
				'</td></tr>';
		}

		if ( isset( $fields_RET['radio'] ) )
		{
			foreach ( $fields_RET['radio'] as $field )
			{
				echo '<tr><td>' .
					_makeCheckboxInput( 'CUSTOM_' . $field['ID'], ParseMLField( $field['TITLE'] ) ) .
					'</td></tr>';
			}
		}

		echo '</table><br />';
	}

	//Widgets('activity');
	//Widgets('course');
	//Widgets('absences');

	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
	}
}

/**
 * @param $column
 * @param $numeric
 */
function _makeTextInput( $column, $numeric = false, $title = '' )
{
	if ( $numeric === true )
	{
		$options = 'size=10 maxlength=11';
	}
	else
	{
		$options = 'size=20';
	}

	return TextInput( '', 'values[' . $column . ']', $title, $options );
}

/**
 * @param $column
 * @param $title
 */
function _makeTextAreaInput( $column, $title = '' )
{
	return TextAreaInput( '', 'values[' . $column . ']', $title );
}

/**
 * @param $column
 * @param $title
 */
function _makeDateInput( $column, $title = '' )
{
	return DateInput( '', 'values[' . $column . ']', $title );
}

/**
 * @param $column
 * @param $options
 * @param $title
 */
function _makeSelectInput( $column, $options, $title = '' )
{
	return SelectInput( '', 'values[' . $column . ']', $title, $options, 'N/A', "style='max-width:190px;'" );
}

/**
 * @param $column
 * @param $title
 */
function _makeCheckboxInput( $column, $title = '' )
{
	return CheckboxInput( '', 'values[' . $column . ']', $title, '', true );
}
