<?php
/**
 * Substitutions functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Substitutions Input
 * Substitution select input + help tooltip & Copy button.
 *
 * @since 4.3
 *
 * @example echo SubstitutionsInput( array( '__FULL_NAME__' => _( 'Display Name' ), '__SCHOOL_ID__' => _( 'School' ) ) );
 *
 * @uses SelectInput
 *
 * @param array $substitutions Associative array containing code as key and
 *
 * @return string Input HTML.
 */
function SubstitutionsInput( $substitutions )
{
	static $id = 0;

	if ( empty( $substitutions )
		|| ! is_array( $substitutions ) )
	{
		return '';
	}

	$allow_na = $div = $required = false;

	$id++;

	$input_html = SelectInput(
		'',
		'substitutions_input_' . $id,
		'',
		$substitutions,
		$allow_na,
		'autocomplete="off"',
		$div
	);

	$code_value = key( $substitutions );

	$code = ' <input id="substitutions_code_' . $id . '" type="text" readonly size="' . ( strlen( $code_value ) - 1 ) .
		'" value="' . $code_value . '" autocomplete="off" />';

	$code .= '<label for="substitutions_code_' . $id . '" class="a11y-hidden">' . _( 'Code' ) . '</label>';

	$copy_button = '<input id="substitutions_button_' . $id . '" type="button" value="' . _( 'Copy' ) . '" />';

	$tooltip_html = '<div class="tooltip"><i>' .
		_( 'Copy the substitution code and paste it into your text. The code will be dynamically replaced with the corresponding value.' ) .
	'</i></div>';

	$title_html = FormatInputTitle(
		_( 'Substitutions' ) . $tooltip_html,
		'substitutions_input_' . $id
	);

	ob_start(); ?>

	<script>
		var substitutionsUpdateCode = function(event) {

			var select = event.target,
				codeValue = select.options[ select.selectedIndex ].value,
				code = $('#substitutions_code_' + <?php echo $id; ?>);

			// Update code with corresponding selected input value.
			code.val( codeValue );

			code.attr( 'size', codeValue.length - 1 );
		};

		var substitutionsCopyCode = function(event) {

			var code = $('#substitutions_code_' + <?php echo $id; ?>);

			code.focus().select();

			// Copy code into clipboard.
			document.execCommand("copy");
		};

		// Set select onchange & button onclick functions.
		$('#substitutions_input_' + <?php echo $id; ?>).change(substitutionsUpdateCode);
		$('#substitutions_button_' + <?php echo $id; ?>).click(substitutionsCopyCode);
	</script>

	<?php
	$js_update_copy_code = ob_get_clean();

	return $input_html . $code . $copy_button . $title_html . $js_update_copy_code;
}


/**
 * Make Substitions for text.
 *
 * @since 4.3
 *
 * @example $text_s = SubstitutionsTextMake( array( '__FIRST_NAME__' => 'Student', '__SCHOOL_ID__' => 'My School' ), $text );
 *
 * @param array  $substitutions Associative array containing code as key and
 * @param string $text          Text with substitution codes.
 *
 * @return text Substituted text.
 */
function SubstitutionsTextMake( $substitutions, $text )
{
	if ( empty( $text )
		|| empty( $substitutions )
		|| ! is_array( $substitutions ) )
	{
		return $text;
	}

	$text_substituted = str_replace(
		array_keys( $substitutions ),
		$substitutions,
		$text
	);

	return $text_substituted;
}

/**
 * Get Custom fields from DB for Substitutions.
 *
 * @since 5.5
 *
 * @param  string $table STUDENT, STAFF, SCHOOL...
 *
 * @return array         Custom fields array from DB.
 */
function _substitutionsDBGetCustomFields( $table )
{
	$table_name = ( $table === 'STUDENT' ? 'CUSTOM' : $table ) . '_FIELDS';

	$has_categories = array( 'STUDENT', 'ADDRESS', 'PEOPLE', 'STAFF' );

	if ( ! in_array( $table, $has_categories ) )
	{
		return DBGet( "SELECT '' AS CATEGORY,ID,TITLE,TYPE,SELECT_OPTIONS
			FROM " . DBEscapeIdentifier( $table_name ) . "
			WHERE TYPE<>'file'" );
	}

	$category_table_name = $table . '_FIELD_CATEGORIES';

	$profile_category_sql = '';

	if ( $table === 'STUDENT' || $table === 'STAFF' )
	{
		// Only get fields in categories which user profile can access.
		$profile_category_sql = " AND (SELECT CAN_USE FROM " .
		( User( 'PROFILE_ID' ) ?
			"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
			"STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'" ) .
		" AND MODNAME='" . ( $table === 'STUDENT' ? 'Students/Student.php' : 'Users/User.php' ) .
		"&category_id='||f.CATEGORY_ID
		LIMIT 1)='Y'";
	}

	return DBGet( "SELECT c.TITLE AS CATEGORY,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS
		FROM " . DBEscapeIdentifier( $table_name ) . " f," . DBEscapeIdentifier( $category_table_name ) . " c
		WHERE f.TYPE<>'file'
		AND c.ID=f.CATEGORY_ID" . $profile_category_sql );
}

/**
 * Get custom Fields codes for Substitutions
 *
 * @since 5.5
 *
 * @example $substitutions += SubstitutionsCustomFields( 'STUDENT' );
 *
 * @uses _substitutionsDBGetCustomFields()
 *
 * @param array $table STUDENT, STAFF, SCHOOL...
 *
 * @return array       Substitution code as key and Field title as value.
 */
function SubstitutionsCustomFields( $table )
{
	$fields = _substitutionsDBGetCustomFields( $table );

	$custom_fields = array();

	foreach ( $fields as $field )
	{
		// TODO instert CATEGORY as separator..., SelectInput() not ready for it yet.
		$code = '__' . $table . '_' . $field['ID'] . '__';

		$custom_fields[ $code ] = ParseMLField( $field['TITLE'] );
	}

	return $custom_fields;
}

/**
 * Get Custom Fields values to be substituted. For use before SubstitutionsTextMake().
 * Format field value for display, depending on field type.
 *
 * @since 5.5
 *
 * @example $substitutions += SubstitutionsCustomFieldsValues( 'STUDENT', $student );
 *
 * @uses _substitutionsDBGetCustomFields()
 *
 * @param staing $table  STUDENT, STAFF, SCHOOL...
 * @param array  $values Custom field values from DB, for current student, user, school...
 *
 * @return array         Substitution code as key and formatted Field value as value.
 */
function SubstitutionsCustomFieldsValues( $table, $values )
{
	$fields = _substitutionsDBGetCustomFields( $table );

	$custom_values = array();

	foreach ( $fields as $field )
	{
		$column = 'CUSTOM_' . $field['ID'];

		// Do not use isset here as returns false when has null value.
		if ( ! array_key_exists( $column, $values ) )
		{
			continue;
		}

		$code = '__' . str_replace( 'CUSTOM', $table, $column ) . '__';

		$custom_values[ $code ] = $value = $values[ $column ];

		if ( in_array( $field['TYPE'], array( 'text', 'numeric' ) ) )
		{
			// No formatting, use raw for text & numeric types.
			continue;
		}

		$title = ParseMLField( $field['TITLE'] );

		// Process value based on field type.
		switch ( $field['TYPE'] )
		{
			case 'date':

				$custom_values[ $code ] = ProperDate( $value );

				break;

			case 'textarea':

				$custom_values[ $code ] = '<div class="markdown-to-html">' . $value . '</div>';;

				break;

			case 'radio':

				$custom_values[ $code ] = $value ? _( 'Yes' ) : _( 'No' ) . ( $title !== '' ? ' ' . $title : '' );

				break;

			case 'multiple':

				if ( $value == '' )
				{
					$custom_values[ $code ] = _( 'N/A' );

					break;
				}

				$options = explode( '||', trim( $value, '||' ) );

				$custom_values[ $code ] = implode( ', ', $options );

				break;

			case 'autos':
			case 'exports':
			case 'select':

				$custom_values[ $code ] = $value ? $value : _( 'N/A' );

				break;
		}
	}

	return $custom_values;
}
