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
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Text Input
 */
function _makeTextInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;

		$req = $field['REQUIRED'] === 'Y' ? array( '<span class="legend-red">', '</span>' ) : array( '', '' );
	}
	else
	{
		$div = true;

		$req = $field['REQUIRED'] === 'Y' && ! $value[ $column ] ?
			array( '<span class="legend-red">', '</span>' ) :
			array( '', '' );
	}

	if ( $field['TYPE'] === 'numeric' )
	{
		$value[ $column ] = str_replace( '.00', '', $value[ $column ] );

		$options = 'size=9 maxlength=18';
	}
	else
		$options = 'maxlength=255';

	// FJ text field is required.
	$options .= $field['REQUIRED'] === 'Y' ? ' required' : '';

	return TextInput(
		$value[ $column ],
		$request . '[' . $column . ']',
		$req[0] . $name . $req[1],
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
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Date Input
 */
function _makeDateInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;

		$req = $field['REQUIRED'] === 'Y' ? array( '<span class="legend-red">', '</span>' ) : array( '', '' );
	}
	else
	{
		$div = true;

		$req = $field['REQUIRED'] === 'Y' && ! $value[ $column ] ?
			array( '<span class="legend-red">', '</span>' ) :
			array( '', '' );
	}

	// FJ date field is required.
	$required = false;

	if ( $field['REQUIRED'] === 'Y' )
	{
		$required = true;
	}

	return DateInput(
		$value[ $column ],
		$request . '[' . $column . ']',
		$req[0] . $name . $req[1],
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
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Select Input
 */
function _makeSelectInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;

		$req = $field['REQUIRED'] === 'Y' ? array( '<span class="legend-red">', '</span>' ) : array( '', '' );
	}
	else
	{
		$div = true;

		$req = $field['REQUIRED'] === 'Y' && ! $value[ $column ] ?
			array( '<span class="legend-red">', '</span>' ) :
			array( '', '' );
	}

	$select_options = array();

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( (array) $select_options as $option )
	{
		if ( $field['TYPE'] === 'codeds' )
		{
			$option = explode( '|', $option );

			if ( $option[0] != ''
				&& $option[1] != '' )
				$options[$option[0]] = $option[1];
		}
		elseif ( $field['TYPE'] === 'exports' )
		{
			$option = explode( '|', $option );

			if ( $option[0] != '' )
				$options[$option[0]] = $option[0];
		}
		else
			$options[ $option ] = $option;
	}

	// FJ select field is required.
	$extra = 'style="max-width:250px;"' . ( $field['REQUIRED'] === 'Y' ? ' required': '' );

	return SelectInput(
		$value[ $column ],
		$request . '[' . $column . ']',
		$req[0] . $name . $req[1],
		$options,
		_( 'N/A' ),
		$extra,
		$div
	);
}


/**
 * Make Auto Select Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Auto Select Input
 */
function _makeAutoSelectInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;

		$req = $field['REQUIRED'] === 'Y' ? array( '<span class="legend-red">', '</span>' ) : array( '', '' );
	}
	else
	{
		$div = true;

		$req = $field['REQUIRED'] === 'Y' && ( ! $value[ $column ] || $value[ $column ] === '---' ) ?
			array( '<span class="legend-red">', '</span>' ) :
			array( '', '' );
	}

	// Build the select list...
	// Get the standard selects.
	$select_options = array();

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( (array) $select_options as $option )
	{
		if ( $option != '' )
		{
			$options[ $option ] = $option;
		}
	}

	// Add the 'new' option, is also the separator.
	$options['---'] = '-' . _( 'Edit' ) . '-';

	if ( ( $field['TYPE'] === 'autos'
			|| $field['TYPE'] === 'edits' )
		&& AllowEdit() ) // We don't really need the select list if we can't edit anyway.
	{
		// Add values found in current and previous year.
		if ( $request === 'values[ADDRESS]' )
		{
			$options_SQL = "SELECT DISTINCT a.CUSTOM_" . $field['ID'] . ",upper(a.CUSTOM_" . $field['ID'] . ") AS SORT_KEY 
				FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja,STUDENTS s,STUDENT_ENROLLMENT sse 
				WHERE a.ADDRESS_ID=sja.ADDRESS_ID 
				AND s.STUDENT_ID=sja.STUDENT_ID 
				AND sse.STUDENT_ID=s.STUDENT_ID 
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "') 
				AND a.CUSTOM_" . $field['ID'] . " IS NOT NULL 
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'values[PEOPLE]' )
		{
			$options_SQL = "SELECT DISTINCT p.CUSTOM_" . $field['ID'] . ",upper(p.CUSTOM_" . $field['ID'] . ") AS SORT_KEY 
				FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp,STUDENTS s,STUDENT_ENROLLMENT sse 
				WHERE p.PERSON_ID=sjp.PERSON_ID 
				AND s.STUDENT_ID=sjp.STUDENT_ID 
				AND sse.STUDENT_ID=s.STUDENT_ID 
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "') 
				AND p.CUSTOM_" . $field['ID'] . " IS NOT NULL 
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'students' )
		{
			$options_SQL = "SELECT DISTINCT s.CUSTOM_" . $field['ID'] . ",upper(s.CUSTOM_" . $field['ID'] . ") AS SORT_KEY 
				FROM STUDENTS s,STUDENT_ENROLLMENT sse 
				WHERE sse.STUDENT_ID=s.STUDENT_ID 
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "') 
				AND s.CUSTOM_" . $field['ID'] . " IS NOT NULL 
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'staff' )
		{
			$options_SQL = "SELECT DISTINCT s.CUSTOM_" . $field['ID'] . ",upper(s.CUSTOM_".$field['ID'].") AS KEY
				FROM STAFF s
				WHERE (s.SYEAR='" . UserSyear() . "' OR s.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND s.CUSTOM_" . $field['ID'] . " IS NOT NULL
				ORDER BY KEY";
		}

		$options_RET = DBGet( DBQuery( $options_SQL ) );

		foreach ( (array) $options_RET as $option )
		{
			$option_value = $option[ 'CUSTOM_' . $field['ID'] ];

			if ( $option_value != ''
				&& ! $options[ $option_value ] )
			{
				$options[ $option_value ] = array(
					$option_value,
					'<span style="color:blue">' . $option_value . '</span>'
				);
			}
		}
	}

	// Make sure the current value is in the list.
	if ( ! $value[ $column ]
		&& ! isset( $options[ $value[ $column ] ] ) )
	{
		$options[ $value[ $column ] ] = array(
			$value[ $column ],
			'<span style="color:' . ( $field['TYPE'] === 'autos' ? 'blue' : 'green' ) . '">' . $value[ $column ] . '</span>'
		);
	}

	if ( $value[ $column ] != '---'
		&& count( $options ) > 1 )
	{
		// FJ select field is required.
		$extra = 'style="max-width:250px;"' . ( $field['REQUIRED'] === 'Y' ? ' required' : '' );

		return SelectInput(
			$value[ $column ],
			$request . '[' . $column . ']',
			$req[0] . $name . $req[1],
			$options,
			_( 'N/A' ),
			$extra,
			$div
		);
	}
	else
	{
		// FJ new option.
		return TextInput(
			$value[ $column ] === '---' ?
				array( '---', '<span style="color:red">-' . _( 'Edit' ) . '-</span>' ) :
				$value[ $column ],
			$request . '[' . $column . ']',
			$req[0] . $name . $req[1],
			'',
			$div
		);
	}
}


/**
 * Make Checkbox Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Checkbox Input
 */
function _makeCheckboxInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( $field['DEFAULT_SELECTION'] === 'Y'
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$new = true;
	}
	else
		$new = false;

	return CheckboxInput(
		$value[ $column ],
		$request . '[' . $column . ']',
		$name,
		'',
		$new
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
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Textarea Input
 */
function _makeTextAreaInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}
	else
		$div = true;

	// FJ text area is required.
	// FJ textarea field maxlength=5000.
	return TextAreaInput(
		$value[ $column ],
		$request . '[' . $column . ']',
		$name,
		'maxlength=5000' . ( $field['REQUIRED'] == 'Y' ? ' required': '' ),
		$div
	);
}


/**
 * Make Multiple Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string Multiple Input
 */
function _makeMultipleInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( AllowEdit()
		&& !isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$select_options = array();

		if ( $field['SELECT_OPTIONS'] )
		{
			$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );
		}

		foreach ( (array) $select_options as $option )
		{
			$options[ $option ] = $option;
		}

		if ( $value[ $column ] != '' )
		{
			$return = '<div id="div' . $request . '[' . $column . ']">
				<div class="onclick" onclick=\'javascript:addHTML(html' . $request . $column;
		}

		$table = '<table class="cellpadding-5">';

		if ( count( $options ) > 12 )
		{
			$table .= '<tr><td colspan="2">';
			$table .= '<span class="legend-gray">' . $name . '</span>';
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
				<input type="checkbox" name="' . $request . '[' . $column . '][]" value="' .
					htmlspecialchars( $option, ENT_QUOTES ) . '"' .
					( mb_strpos( $value[ $column ], '||' . $option . '||' ) !== false ? ' checked' : '' ) . ' /> ' .
					$option .
			'</label></td>';

			$i++;
		}

		$table .= '</tr><tr><td colspan="2">';

		// FJ fix bug none selected not saved.
		$table .= '<input type="hidden" name="' . $request . '[' . $column . '][none]" value="" />';

		$table .= '<table class="width-100p" style="height:7px; border:1; border-style:none solid solid solid;"><tr><td></td></tr></table>';

		$table .= '</td></tr></table>';

		if ( $value[ $column ] != '' )
		{
			echo '<script>var html' . $request . $column . '=' . json_encode( $table ) . ';</script>' . $return;
			echo ',"div' . $request . '[' . $column . ']",true);\' >';
			echo '<span class="underline-dots">' . ($value[ $column ] != '' ? str_replace( '||', ', ', mb_substr( $value[ $column ], 2, -2 ) ) : '-' ) . '</span>';
			echo '</div></div>';
		}
		else
			echo $table;
	}
	else
		echo ( $value[ $column ] != '' ? str_replace( '||', ', ', mb_substr( $value[ $column ], 2, -2 ) ) : '-' ) . '<br />';

	echo '<span class="legend-gray">' . $name . '</span>';
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
		&& date_create( $value[ $column ] ) )
	{
		$datetime1 = date_create( $value[ $column ] );

		$datetime2 = date_create( 'now' );

		$interval = date_diff( $datetime1, $datetime2 );

		$age_text = _( '%Y Years %m Months %d Days' );

		$age_text = $interval->format( $age_text );

		return NoInput( $age_text, $name );
	}
	else
		return '';
}


// MEDICAL.
/**
 * Make Medical Immunization or Physical type Select Input
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

	if ( ! $THIS_RET['ID'] )
	{
		$THIS_RET['ID'] = 'new';
	}

	return SelectInput(
		$value,
		'values[STUDENT_MEDICAL][' . $THIS_RET['ID'] . '][TYPE]',
		'',
		array( 'Immunization' => _( 'Immunization' ), 'Physical' => _( 'Physical' ) )
	);
}


/**
 * Make Medical Date Input
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

	if ( ! $THIS_RET['ID'] )
	{
		$THIS_RET['ID'] = 'new';
	}

	return DateInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']'
	);
}


/**
 * Make Medical Comments Input
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

	if ( ! $THIS_RET['ID'] )
	{
		$THIS_RET['ID'] = 'new';
	}

	return TextInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']'
	);
}


// ENROLLMENT.
/**
 * Make Enrollment Start Date & Code Inputs
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
	global $THIS_RET;
		
	static $add_codes = false;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	elseif ( $_REQUEST['student_id'] === 'new' )
	{
		$id = 'new';

		$default = DBGet( DBQuery( "SELECT min(SCHOOL_DATE) AS START_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" ) );

		$default = $default[1]['START_DATE'];

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
		$options_RET = DBGet( DBQuery( "SELECT ID,TITLE AS TITLE
			FROM STUDENT_ENROLLMENT_CODES
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Add'
			ORDER BY SORT_ORDER" ) );

		foreach ( (array) $options_RET as $option )
		{
			$add_codes[$option['ID']] = $option['TITLE'];
		}
	}

	if ( $_REQUEST['student_id'] === 'new' )
	{
		$div = false;
	}
	else
		$div = true;

	// FJ remove LO_field.
	return '<div class="nobr">' . $add .
		DateInput(
			$value,
			'values[STUDENT_ENROLLMENT][' . $id . '][' . $column . ']',
			'',
			$div,
			true
		) . ' - ' .
		SelectInput(
			$THIS_RET['ENROLLMENT_CODE'],
			'values[STUDENT_ENROLLMENT][' . $id . '][ENROLLMENT_CODE]',
			'',
			$add_codes,
			_( 'N/A' ),
			'style="max-width:150px;"'
		) .
	'</div>';
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
	global $THIS_RET;

	static $drop_codes;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	if ( ! $drop_codes )
	{
		$options_RET = DBGet( DBQuery( "SELECT ID,TITLE AS TITLE
			FROM STUDENT_ENROLLMENT_CODES
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Drop'
			ORDER BY SORT_ORDER" ) );

		foreach ( (array) $options_RET as $option )
		{
			$drop_codes[$option['ID']] = $option['TITLE'];
		}
	}

	return '<div class="nobr">' .
		DateInput(
			$value,
			'values[STUDENT_ENROLLMENT][' . $id . '][' . $column . ']'
		) . ' - ' .
		SelectInput(
			$THIS_RET['DROP_CODE'],
			'values[STUDENT_ENROLLMENT][' . $id . '][DROP_CODE]',
			'',
			$drop_codes,
			_( 'N/A' ),
			'style="max-width:150px;"'
		) .
	'</div>';
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

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	if ( ! isset( $schools )
		|| ! is_array( $schools ) )
	{
		$schools = DBGet( DBQuery( "SELECT ID,TITLE
			FROM SCHOOLS
			WHERE SYEAR='" . UserSyear() . "'" ), array(), array( 'ID' ) );
	}

	foreach ( (array) $schools as $sid => $school )
	{
		$options[ $sid ] = $school[1]['TITLE'];
	}

	// Mab - allow school to be edited if illegal value.
	if ( $_REQUEST['student_id'] != 'new' )
	{
		if ( $id != 'new' )
		{
			if ( is_array( $schools[ $value ] ) )
			{
				return $schools[ $value ][1]['TITLE'];
			}
			else
			{
				return SelectInput(
					$value,
					'values[STUDENT_ENROLLMENT][' . $id . '][SCHOOL_ID]',
					'',
					$options
				);
			}
		}
		else
		{
			return SelectInput(
				UserSchool(),
				'values[STUDENT_ENROLLMENT][' . $id . '][SCHOOL_ID]',
				'',
				$options,
				false,
				'',
				false
			);
		}
	}
	else
	{
		// FJ save new Student's Enrollment in Enrollment.inc.php.
		return '<input type="hidden" name="values[STUDENT_ENROLLMENT][new][SCHOOL_ID]" value="' . UserSchool() . '" />' .
			$schools[ UserSchool() ][1]['TITLE'];
	}
}


/**
 * Is New Student / User / People / Address?
 * Local function
 *
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
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

		case 'values[PEOPLE]':

			$request_key = 'person_id';

		break;

		case 'values[ADDRESS]':

			$request_key = 'address_id';

		break;

		default:

			return false;
	}

	return isset( $_REQUEST[ $request_key ] ) && $_REQUEST[ $request_key ] === 'new';
}
