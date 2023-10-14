<?php
/**
 * Student / User / Address / People / Medical / Enrollment fields
 * Make functions
 * Wrappers for Inputs functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

// OTHER INFO.
/**
 * Make Text Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return string          Text Input
 */
function _makeTextInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	if ( $field['TYPE'] === 'numeric' )
	{
		$value[ $column ] = str_replace( '.00', '', issetVal( $value[ $column ], '' ) );

		// Fix Number Field SQL column limit: type numeric(20,2).
		$options = ' type="number" step="any" max="999999999999999999" min="-999999999999999999"';
	}
	elseif ( Config( 'STUDENTS_EMAIL_FIELD' ) === str_replace( 'CUSTOM_', '', $column ) )
	{
		$options = 'maxlength=255 type="email" placeholder="' . AttrEscape( _( 'Email' ) ) . '"';

		if ( ! empty( $_REQUEST['moodle_create_student'] ) )
		{
			// Moodle integrator, email required.
			$options .= ' required';

			$div = false;
		}
	}
	else
		$options = 'maxlength=1000';

	// FJ text field is required.
	$options .= $field['REQUIRED'] === 'Y' ? ' required' : '';

	return TextInput(
		issetVal( $value[ $column ], '' ),
		$request . '[' . $column . ']',
		$name,
		$options,
		$div
	);
}


/**
 * Make Date Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return string          Date Input
 */
function _makeDateInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	// FJ date field is required.
	$required = false;

	if ( $field['REQUIRED'] === 'Y' )
	{
		$required = true;
	}

	return DateInput(
		issetVal( $value[ $column ], '' ),
		$request . '[' . $column . ']',
		$name,
		$div,
		true,
		$required
	);
}


/**
 * Make Select Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return string          Select Input
 */
function _makeSelectInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	$select_options = $options = [];

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( $select_options as $option )
	{
		if ( $field['TYPE'] === 'exports' )
		{
			$option = explode( '|', $option );

			if ( $option[0] != '' )
				$options[$option[0]] = $option[0];
		}
		else
			$options[ $option ] = $option;
	}

	// FJ select field is required.
	$extra = ( $field['REQUIRED'] === 'Y' ? 'required': '' );

	return SelectInput(
		issetVal( $value[ $column ], '' ),
		$request . '[' . $column . ']',
		$name,
		$options,
		'N/A',
		$extra,
		$div
	);
}


/**
 * Make Auto Select Input
 *
 * @since 4.6 Add $options_RET parameter.
 * @since 10.2.1 When -Edit- option selected, change the auto pull-down to text field
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column      Field column.
 * @param  string $name        Field name.
 * @param  string $request     students|staff|values[people]|values[address].
 * @param  array  $options_RET Options array (optional).
 *
 * @return string          Auto Select Input
 */
function _makeAutoSelectInput( $column, $name, $request, $options_RET = [] )
{
	static $js_included = false;

	global $value,
		$field;

	$div = true;

	$value[ $column ] = issetVal( $value[ $column ] );

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	// Build the select list...
	// Get the standard selects.
	$select_options = [];

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( $select_options as $option )
	{
		if ( $option != '' )
		{
			$options[ $option ] = $option;
		}
	}

	// Add the 'new' option, is also the separator.
	$options['---'] = '-' . _( 'Edit' ) . '-';

	$col_name = DBEscapeIdentifier( $column );

	if ( $field['TYPE'] === 'autos'
		&& AllowEdit() ) // We don't really need the select list if we can't edit anyway.
	{
		// Add values found in current and previous year.
		if ( $request === 'values[address]' )
		{
			$options_SQL = "SELECT DISTINCT a." . $col_name . ",upper(a." . $col_name . ") AS SORT_KEY
				FROM address a,students_join_address sja,students s,student_enrollment sse
				WHERE a.ADDRESS_ID=sja.ADDRESS_ID
				AND s.STUDENT_ID=sja.STUDENT_ID
				AND sse.STUDENT_ID=s.STUDENT_ID
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND a." . $col_name . " IS NOT NULL
				AND a." . $col_name . "<>'---'
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'values[people]' )
		{
			$options_SQL = "SELECT DISTINCT p." . $col_name . ",upper(p." . $col_name . ") AS SORT_KEY
				FROM people p,students_join_people sjp,students s,student_enrollment sse
				WHERE p.PERSON_ID=sjp.PERSON_ID
				AND s.STUDENT_ID=sjp.STUDENT_ID
				AND sse.STUDENT_ID=s.STUDENT_ID
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND p." . $col_name . " IS NOT NULL
				AND p." . $col_name . "<>'---'
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'students' )
		{
			$options_SQL = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
				FROM students s,student_enrollment sse
				WHERE sse.STUDENT_ID=s.STUDENT_ID
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND s." . $col_name . " IS NOT NULL
				AND s." . $col_name . "<>'---'
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'staff' )
		{
			$options_SQL = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
				FROM staff s
				WHERE (s.SYEAR='" . UserSyear() . "' OR s.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND s." . $col_name . " IS NOT NULL
				AND s." . $col_name . "<>'---'
				ORDER BY SORT_KEY";
		}

		if ( empty( $options_RET )
			&& ! empty( $options_SQL ) )
		{
			$options_RET = DBGet( $options_SQL );
		}

		foreach ( (array) $options_RET as $option )
		{
			$option_value = $option[ 'CUSTOM_' . $field['ID'] ];

			if ( $option_value != ''
				&& ! isset( $options[ $option_value ] ) )
			{
				$options[ $option_value ] = [
					$option_value,
					'<span style="color:blue">' . $option_value . '</span>'
				];
			}
		}
	}

	// Make sure the current value is in the list.
	if ( $value[ $column ] != ''
		&& ! isset( $options[ $value[ $column ] ] ) )
	{
		$options[ $value[ $column ] ] = [
			$value[ $column ],
			'<span style="color:' . ( $field['TYPE'] === 'autos' ? 'blue' : 'green' ) . '">' . $value[ $column ] . '</span>'
		];
	}

	$input_name = $request . '[' . $column . ']';

	if ( $value[ $column ] === '---'
		|| count( $options ) < 1 )
	{
		// FJ new option.
		return TextInput(
			$value[ $column ] === '---' ?
				[ '---', '<span style="color:red">-' . _( 'Edit' ) . '-</span>' ] :
				$value[ $column ],
			$input_name,
			$name,
			( $field['REQUIRED'] === 'Y' ? 'required' : '' ),
			$div
		);
	}

	// When -Edit- option selected, change the auto pull-down to text field.
	$return = '';

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ! $js_included )
	{
		$js_included = true;

		ob_start();?>
		<script>
		function maybeEditTextInput(el) {

			// -Edit- option's value is ---.
			if ( el.value === '---' ) {

				var $el = $( el );

				// Remove parent <div> if any
				if ( $el.parent('div').length ) {
					$el.unwrap();
				}
				// Remove the select input.
				$el.remove();

				// Show & enable the text input of the same name.
				$( '[name="' + el.name + '_text"]' ).prop('name', el.name).prop('disabled', false).show().focus();
			}
		}
		</script>
		<?php $return = ob_get_clean();
	}

	// FJ select field is required.
	$extra = ( $field['REQUIRED'] === 'Y' ? 'required' : '' );

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Add hidden & disabled Text input in case user chooses -Edit-.
		$return .= TextInput(
			'',
			$input_name . '_text',
			'',
			$extra . ' disabled style="display:none;"',
			false
		);
	}

	$return .= SelectInput(
		$value[ $column ],
		$input_name,
		$name,
		$options,
		'N/A',
		$extra . ' onchange="maybeEditTextInput(this);"',
		$div
	);

	return $return;
}


/**
 * Make Checkbox Input
 *
 * @since 6.4.2 Fix Required & div for checkbox input.
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return string          Checkbox Input
 */
function _makeCheckboxInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	$new = _isNew( $request );

	if ( $new
		|| ( $field['REQUIRED'] === 'Y'
			&& empty( $value[ $column ] ) ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		// No div if new or if Required AND not checked.
		$div = false;
	}

	return CheckboxInput(
		issetVal( $value[ $column ] ),
		$request . '[' . $column . ']',
		$name,
		'',
		$new,
		'Yes',
		'No',
		$div,
		( $field['REQUIRED'] === 'Y' ? 'required' : '' )
	);
}


/**
 * Make Textarea Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return string          Textarea Input
 */
function _makeTextAreaInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	// FJ text area is required.
	// FJ textarea field maxlength=50000 (soft limit).
	return TextAreaInput(
		issetVal( $value[ $column ] ),
		$request . '[' . $column . ']',
		$name,
		'maxlength=50000' . ( $field['REQUIRED'] == 'Y' ? ' required': '' ),
		$div
	);
}


/**
 * Make Files Input
 *
 * @since 4.6
 * @since 7.0 CSS Add .widefat.files class
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return string          Files Input
 */
function _makeFilesInput( $column, $name, $request, $remove_url = '' )
{
	global $value,
		$field;

	require_once 'ProgramFunctions/FileUpload.fnc.php';

	$div = true;

	$file_paths = explode( '||', trim( issetVal( $value[ $column ], '' ), '||' ) );

	$files = [];

	foreach ( $file_paths as $file_path )
	{
		if ( ! file_exists( $file_path ) )
		{
			continue;
		}

		$file_name = basename( $file_path );

		$file_size = HumanFilesize( filesize( $file_path ) );

		// Truncate file name if > 36 chars.
		$file_name_display = mb_strlen( $file_name ) <= 36 ?
			$file_name :
			mb_substr( $file_name, 0, 30 ) . '..' . mb_strrchr( $file_name, '.' );

		$file = button(
			'download',
			$file_name_display,
			'"' . URLEscape( $file_path ) . '" target="_blank" title="' . AttrEscape( $file_name . ' (' . $file_size . ')' ) . '"',
			'bigger'
		);

		if ( AllowEdit() && $remove_url )
		{
			$file = button(
				'remove',
				'',
				'"' . URLEscape( $remove_url . $file_name ) . '" title="' . AttrEscape( _( 'Delete' ) ) . '"'
			) . '&nbsp;' . $file;
		}

		$files[] = $file;
	}

	$files_html = '';

	if ( $files )
	{
		$files_html = '<table class="widefat files"><tbody><tr><td>' .
			implode( '</td></tr><tr><td>', $files ) . '</td></tr></tbody></table>';
	}

	$required = $field['REQUIRED'] == 'Y' && AllowEdit() && ! $files;

	if ( AllowEdit() )
	{
		$files_html .= FileInput(
			$request . $column,
			'',
			( $required ? ' required': '' )
		);
	}
	elseif ( ! $files )
	{
		$files_html = '-';
	}

	return $files_html . FormatInputTitle(
		$name,
		( AllowEdit() ? $request . $column : '' ),
		$required,
		( AllowEdit() || ! $files ? '<br>' : '' )
	);
}


/**
 * Make Multiple Input
 *
 * @since 6.1 Return HTML instead of echo.
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return string Multiple Input
 */
function _makeMultipleInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ( ! empty( $value[ $column ] ) ?
			str_replace( '||', ', ', mb_substr( $value[ $column ], 2, -2 ) ) :
			'-' ) .
			FormatInputTitle( $name );
	}

	$select_options = $options = [];

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( $select_options as $option )
	{
		$options[ $option ] = $option;
	}

	$table = '<table class="cellpadding-5">';

	if ( count( $options ) > 12 )
	{
		$table .= '<tr><td colspan="2">';
		$table .= FormatInputTitle( $name, '', false, '' );
		$table .= '<table class="width-100p" style="height: 7px; border:1;border-style: solid solid none solid;"><tr><td></td></tr></table>';
		$table .= '</td></tr>';
	}

	$table .= '<tr>';

	$i = 0;

	foreach ( (array) $options as $option )
	{
		if ( $i % 2 === 0 )
		{
			$table .= '</tr><tr>';
		}

		// FJ add <label> on checkbox.
		$table .= '<td><label>
			<input type="checkbox" name="' . AttrEscape( $request . '[' . $column . '][]' ) . '" value="' .
				AttrEscape( $option ) . '"' .
				( ! empty( $value[ $column ] )
					&& mb_strpos( $value[ $column ], '||' . $option . '||' ) !== false ? ' checked' : '' ) . '> ' .
				$option .
		'</label></td>';

		$i++;
	}

	$table .= '</tr><tr><td colspan="2">';

	// FJ fix bug none selected not saved.
	$table .= '<input type="hidden" name="' . AttrEscape( $request . '[' . $column . '][none]' ) . '" value="">';

	$table .= '<table class="width-100p" style="height:7px; border:1; border-style:none solid solid solid;"><tr><td></td></tr></table>';

	$table .= '</td></tr></table>';

	$table .= FormatInputTitle( $name, '', false, '' );

	if ( empty( $value[ $column ] ) )
	{
		return $table;
	}

	return InputDivOnclick(
		GetInputID( $request . $column ),
		$table,
		str_replace( '||', ', ', mb_substr( $value[ $column ], 2, -2 ) ),
		FormatInputTitle( $name )
	);
}


/**
 * Make Student Age
 * FJ display age next to birthdate
 *
 * @global array  $value
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 *
 * @return string          Student Age
 */
function _makeStudentAge( $column, $name )
{
	global $value;

	if ( $_REQUEST['student_id'] !== 'new'
		&& date_create( (string) $value[ $column ] ) )
	{
		$datetime1 = date_create( (string) $value[ $column ] );

		$datetime2 = date_create( 'now' );

		$interval = date_diff( $datetime1, $datetime2 );

		$age_text = _( '%Y Years %m Months %d Days' );

		$age_text = $interval->format( $age_text );

		return NoInput( $age_text, $name );
	}

	return '';
}


// MEDICAL.
/**
 * Make Medical Immunization or Physical type Select Input
 *
 * @since 11.3 No N/A value for existing entries
 *
 * @global array  $THIS_RET
 *
 * @param  string $value    Field value.
 * @param  string $column   Field column.
 *
 * @return string Medical Immunization or Physical type Select Input
 */
function _makeType( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	return SelectInput(
		$value,
		'values[student_medical][' . $THIS_RET['ID'] . '][TYPE]',
		'',
		[ 'Immunization' => _( 'Immunization' ), 'Physical' => _( 'Physical' ) ],
		( $THIS_RET['ID'] === 'new' ? 'N/A' : false )
	);
}


/**
 * Make Medical Date Input
 *
 * @since 11.3 No N/A value for existing entries (Nurse Visit only)
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Medical Date Input
 */
function _makeDate( $value, $column = 'MEDICAL_DATE' )
{
	global $THIS_RET,
		$table;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	return DateInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']',
		'',
		true,
		// No N/A value for existing entries (Nurse Visit only).
		( $THIS_RET['ID'] !== 'new' && $column !== 'MEDICAL_DATE' ? false : 'N/A' )
	);
}


/**
 * Make Medical Comments Input
 *
 * @since 3.6 Add custom input size per column.
 * @since 11.3 Required TITLE value for existing entries
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Medical Comments Input
 */
function _makeComments( $value, $column )
{
	global $THIS_RET,
		$table;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	$input_size = 12;

	if ( $column === 'TIME_IN'
		|| $column === 'TIME_OUT' )
	{
		$input_size = 5;
	}
	elseif ( $column === 'COMMENTS'
		|| $column === 'TITLE' )
	{
		$input_size = 20;
	}

	$required = '';

	if ( $column === 'TITLE'
		&& $THIS_RET['ID'] !== 'new' )
	{
		$required = ' required';
	}

	return TextInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']',
		'',
		'size="' . AttrEscape( $input_size ) . '"' . $required
	);
}


// ENROLLMENT.
/**
 * Make Enrollment Start Date & Code Inputs
 *
 * @since 5.4 Enrollment Start: No N/A option if already has Drop date.
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment Start Date & Code Inputs
 */
function _makeStartInput( $value, $column )
{
	global $THIS_RET,
		$_ROSARIO;

	static $add_codes = [],
		$RET_i = 0;

	$RET_i++;

	$add = '';

	$na = 'N/A';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];

		if ( ( $RET_i === 1 && ! empty( $value ) ) // @since 10.9 Enrollment Start: No N/A option for first entry.
			|| ! empty( $THIS_RET['END_DATE'] ) )
		{
			// @since 5.4 Enrollment Start: No N/A option if already has Drop date.
			$na = false;
		}
	}
	elseif ( $_REQUEST['student_id'] === 'new' )
	{
		$id = 'new';

		// @since 10.0 Enrollment Start: No N/A option for new student.
		$na = false;

		$default = DBGetOne( "SELECT min(SCHOOL_DATE) AS START_DATE
			FROM attendance_calendar
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( ! $default
			|| DBDate() > $default )
		{
			$default = DBDate();
		}

		$value = $default;
	}
	else
	{
		$add = button( 'add' ) . ' ';

		$id = 'new';
	}

	if ( ! $add_codes )
	{
		$options_RET = DBGet( "SELECT ID,TITLE AS TITLE
			FROM student_enrollment_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Add'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		foreach ( (array) $options_RET as $option )
		{
			$add_codes[$option['ID']] = $option['TITLE'];
		}
	}

	$div = true;

	if ( $_REQUEST['student_id'] === 'new' )
	{
		$div = false;
	}

	if ( AllowEdit()
		&& ( User( 'PROFILE' ) === 'parent'
			|| User( 'PROFILE' ) === 'student' ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = false;

		$disallow_edit_parent_student = true;
	}

	$return = '<div class="nobr">' . $add .
		DateInput(
			$value,
			'values[student_enrollment][' . $id . '][' . $column . ']',
			'',
			$div,
			$na
		) . ' - ' .
		SelectInput(
			issetVal( $THIS_RET['ENROLLMENT_CODE'] ),
			'values[student_enrollment][' . $id . '][ENROLLMENT_CODE]',
			'',
			$add_codes,
			$na,
			'style="max-width:150px;"'
		) .
	'</div>';

	if ( ! empty( $disallow_edit_parent_student ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = true;
	}

	return $return;
}


/**
 * Make Enrollment End Date & Code Inputs
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment End Date & Code Inputs
 */
function _makeEndInput( $value, $column )
{
	global $THIS_RET,
		$_ROSARIO;

	static $drop_codes;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	if ( empty( $THIS_RET['START_DATE'] )
		&& empty( $value ) )
	{
		// @since 10.9 Hide End Date input for Inactive Students (no Attendance Start Date)
		return '';
	}

	if ( ! $drop_codes )
	{
		$options_RET = DBGet( "SELECT ID,TITLE AS TITLE
			FROM student_enrollment_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Drop'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		foreach ( (array) $options_RET as $option )
		{
			$drop_codes[$option['ID']] = $option['TITLE'];
		}
	}

	if ( AllowEdit()
		&& ( User( 'PROFILE' ) === 'parent'
			|| User( 'PROFILE' ) === 'student' ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = false;

		$disallow_edit_parent_student = true;
	}

	$return = '<div class="nobr">' .
		DateInput(
			$value,
			'values[student_enrollment][' . $id . '][' . $column . ']'
		) . ' - ' .
		SelectInput(
			$THIS_RET['DROP_CODE'],
			'values[student_enrollment][' . $id . '][DROP_CODE]',
			'',
			$drop_codes,
			'N/A',
			'style="max-width:150px;"'
		) .
	'</div>';

	if ( ! empty( $disallow_edit_parent_student ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = true;
	}

	return $return;
}


/**
 * Make Enrollment School Select Input
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment School Select Input
 */
function _makeSchoolInput( $value, $column )
{
	global $THIS_RET;

	static $schools;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	if ( ! isset( $schools )
		|| ! is_array( $schools ) )
	{
		$schools = DBGet( "SELECT ID,TITLE
			FROM schools
			WHERE SYEAR='" . UserSyear() . "'", [], [ 'ID' ] );
	}

	foreach ( (array) $schools as $sid => $school )
	{
		$options[ $sid ] = $school[1]['TITLE'];
	}

	// Mab - allow school to be edited if illegal value.
	if ( $_REQUEST['student_id'] !== 'new' )
	{
		if ( $id !== 'new' )
		{
			if ( is_array( $schools[ $value ] ) )
			{
				return $schools[ $value ][1]['TITLE'];
			}

			return SelectInput(
				$value,
				'values[student_enrollment][' . $id . '][SCHOOL_ID]',
				'',
				$options
			);
		}

		return SelectInput(
			UserSchool(),
			'values[student_enrollment][' . $id . '][SCHOOL_ID]',
			'',
			$options,
			false,
			'',
			false
		);
	}

	// FJ save new Student's Enrollment in Enrollment.inc.php.
	return '<input type="hidden" name="values[student_enrollment][new][SCHOOL_ID]" value="' . AttrEscape( UserSchool() ) . '">' .
		$schools[ UserSchool() ][1]['TITLE'];
}


/**
 * Is New Student / User / People / Address?
 * Local function
 *
 * @param  string $request students|staff|values[people]|values[address].
 *
 * @return boolean true if new, else false
 */
function _isNew( $request )
{
	switch ( $request )
	{
		case 'students':

			$request_key = 'student_id';

		break;

		case 'staff':

			$request_key = 'staff_id';

		break;

		case 'values[people]':

			$request_key = 'person_id';

		break;

		case 'values[address]':

			$request_key = 'address_id';

		break;

		default:

			return false;
	}

	return isset( $_REQUEST[ $request_key ] ) && $_REQUEST[ $request_key ] === 'new';
}
