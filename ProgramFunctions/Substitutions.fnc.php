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

	$code = ' <input id="substitutions_code_' . $id . '" type="text" readonly size="' . AttrEscape( strlen( $code_value ) - 1 ) .
		'" value="' . AttrEscape( $code_value ) . '" autocomplete="off">';

	$code .= '<label for="substitutions_code_' . $id . '" class="a11y-hidden">' . _( 'Code' ) . '</label>';

	$copy_button = '<input id="substitutions_button_' . $id . '" type="button" value="' . AttrEscape( _( 'Copy' ) ) . '">';

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
				codeValue = select.value,
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

	return str_replace(
		array_keys( $substitutions ),
		$substitutions,
		$text
	);
}

/**
 * Get Custom fields from DB for Substitutions.
 *
 * @since 5.5
 *
 * @param  string $table student, staff, school...
 *
 * @return array         Custom fields array from DB.
 */
function _substitutionsDBGetCustomFields( $table )
{
	$table = mb_strtolower( $table );

	$table_name = ( $table === 'student' ? 'custom' : $table ) . '_fields';

	$has_categories = [ 'student', 'address', 'people', 'staff' ];

	if ( ! in_array( $table, $has_categories ) )
	{
		return DBGet( "SELECT '' AS CATEGORY,ID,TITLE,TYPE,SELECT_OPTIONS
			FROM " . DBEscapeIdentifier( $table_name ) . "
			WHERE TYPE<>'files'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );
	}

	$category_table_name = $table . '_field_categories';

	$profile_category_sql = '';

	if ( User( 'STAFF_ID' )
		&& ( $table === 'student' || $table === 'staff' ) )
	{
		// Only get fields in categories which user profile can access.
		$profile_category_sql = " AND (SELECT CAN_USE FROM " .
		( User( 'PROFILE_ID' ) ?
			"profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
			"staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'" ) .
		" AND MODNAME=CONCAT('" . ( $table === 'student' ? 'Students/Student.php' : 'Users/User.php' ) .
		"&category_id=', f.CATEGORY_ID)
		LIMIT 1)='Y'";
	}

	return DBGet( "SELECT c.TITLE AS CATEGORY,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS
		FROM " . DBEscapeIdentifier( $table_name ) . " f," . DBEscapeIdentifier( $category_table_name ) . " c
		WHERE f.TYPE<>'files'
		AND c.ID=f.CATEGORY_ID" . $profile_category_sql .
		" ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER,c.TITLE,f.SORT_ORDER IS NULL,f.SORT_ORDER,f.TITLE" );
}

/**
 * Get custom Fields codes for Substitutions
 *
 * @since 5.5
 *
 * @example $substitutions += SubstitutionsCustomFields( 'student' );
 *
 * @uses _substitutionsDBGetCustomFields()
 *
 * @param array $table student, staff, school...
 *
 * @return array       Substitution code as key and Field title as value.
 */
function SubstitutionsCustomFields( $table )
{
	$fields = _substitutionsDBGetCustomFields( $table );

	$custom_fields = [];

	foreach ( $fields as $field )
	{
		// TODO instert CATEGORY as separator..., SelectInput() not ready for it yet.
		$code = '__' . mb_strtoupper( $table ) . '_' . $field['ID'] . '__';

		$custom_fields[ $code ] = ParseMLField( $field['TITLE'] );
	}

	return $custom_fields;
}

/**
 * Get Custom Fields values to be substituted. For use before SubstitutionsTextMake().
 * Format field value for display, depending on field type.
 *
 * @since 5.5
 * @since 10.5.2 Remove .00 decimal from value of numeric type
 *
 * @example $substitutions += SubstitutionsCustomFieldsValues( 'student', $student );
 *
 * @uses _substitutionsDBGetCustomFields()
 *
 * @param string $table  student, staff, school...
 * @param array  $values Custom field values from DB, for current student, user, school...
 *
 * @return array         Substitution code as key and formatted Field value as value.
 */
function SubstitutionsCustomFieldsValues( $table, $values )
{
	if ( ! $table
		|| ! $values )
	{
		return [];
	}

	$fields = _substitutionsDBGetCustomFields( $table );

	$custom_values = [];

	foreach ( $fields as $field )
	{
		$column = 'CUSTOM_' . $field['ID'];

		// Do not use isset here as returns false when has null value.
		if ( ! array_key_exists( $column, $values ) )
		{
			continue;
		}

		$code = '__' . str_replace( 'CUSTOM', mb_strtoupper( $table ), $column ) . '__';

		$custom_values[ $code ] = $value = $values[ $column ];

		if ( $field['TYPE'] === 'text' )
		{
			// No formatting, use raw for text & numeric types.
			continue;
		}

		$title = ParseMLField( $field['TITLE'] );

		// Process value based on field type.
		switch ( $field['TYPE'] )
		{
			case 'numeric':

				$custom_values[ $code ] = $value ? (float) $value : $value;

				break;

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
