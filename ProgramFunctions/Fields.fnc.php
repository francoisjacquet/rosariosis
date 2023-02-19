<?php
/**
 * Fields (and Field Categories) functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Add Field to DB Table
 * And create INDEX
 *
 * @since 4.6 Add Files type
 * @since 5.0 SQL fix Change index suffix from '_IND' to '_IDX' to avoid collision.
 * @since 9.2.1 Change $sequence param to $field_id, adapted for use with DBLastInsertID()
 * @since 10.0 MySQL use LONGTEXT type for textarea field
 *
 * @example AddDBField( 'schools', $school_fields_id, $columns['TYPE'] );
 *
 * @param string  $table    DB Table name.
 * @param int     $field_id Field ID (or DB Sequence name: deprecated).
 * @param string  $type     Field Type: radio|text|exports|select|autos|edits|codeds|multiple|numeric|date|textarea|files.
 *
 * @return string Field ID or empty string
 */
function AddDBField( $table, $field_id, $type )
{
	global $DatabaseType;

	if ( ! AllowEdit()
		|| empty( $table )
		|| empty( $type ) )
	{
		return '';
	}

	$table = mb_strtolower( $table );

	if ( (string) (int) $field_id == $field_id )
	{
		$id = $field_id;
	}
	else
	{
		/**
		 * Field ID is actually a DB Sequence name (old param).
		 * So get ID from sequence for compatibility with old signature.
		 *
		 * @deprecated since 9.2.1
		 */
		$id = DBSeqNextID( $field_id );
	}

	if ( empty( $id ) )
	{
		return '';
	}

	$create_index = true;

	switch ( $type )
	{
		case 'radio':

			$sql_type = 'VARCHAR(1)';

		break;

		case 'multiple':
		case 'text':
		case 'exports':
		case 'select':
		case 'autos':

			/**
			 * MySQL TEXT is limited to 64KB.
			 * With utf8mb4 taking up to 4bytes per characters, there is a limit of
			 * Maximum 65 535 chars (using single-byte characters)
			 * Minimum 16 383 chars (using 4-bytes characters)
			 *
			 * @link https://stackoverflow.com/questions/6766781/maximum-length-for-mysql-type-text
			 */
			$sql_type = 'TEXT';

		break;

		case 'numeric':

			$sql_type = 'NUMERIC(20,2)';
		break;


		case 'date':

			$sql_type = 'DATE';

		break;

		case 'textarea':
		case 'files':

			$sql_type = 'TEXT';

			if ( $type === 'textarea'
				&& $DatabaseType === 'mysql' )
			{
				/**
				 * MySQL LONGTEXT type is limited to 4GB whereas TEXT is limited to 64KB.
				 *
				 * @link https://stackoverflow.com/questions/6766781/maximum-length-for-mysql-type-text
				 */
				$sql_type = 'LONGTEXT';
			}

			$create_index = false;

		break;
	}

	DBQuery( 'ALTER TABLE ' . DBEscapeIdentifier( $table ) . ' ADD ' .
		DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) . ' ' . $sql_type );

	$max_indices_reached = false;

	if ( $DatabaseType === 'mysql' )
	{
		/**
		 * Fix MySQL error 1069 Too many keys specified; max 64 keys allowed
		 * Count columns having an index
		 *
		 * @since 10.3
		 */
		$indices = DBGet( DBQuery( "SHOW INDEX FROM " . DBEscapeIdentifier( $table ) ) );

		$max_indices_reached = count( $indices ) >= 64;
	}

	if ( $create_index
		&& ! $max_indices_reached )
	{
		// @since 5.0 SQL fix Change index suffix from '_IND' to '_IDX' to avoid collision.
		$index_name = $table === 'students' ? 'CUSTOM_IND' : $table . '_IDX';

		$key_length = '';

		if ( $sql_type === 'TEXT'
			&& $DatabaseType === 'mysql' )
		{
			/**
			 * Fix MySQL error TEXT column used in key specification without a key length
			 *
			 * @since 10.2.3
			 */
			$key_length = '(255)';
		}

		DBQuery( 'CREATE INDEX ' . DBEscapeIdentifier( $index_name . (int) $id ) .
			' ON ' . DBEscapeIdentifier( $table ) .
			' (' . DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) . $key_length . ')' );
	}

	return $id;
}


/**
 * Delete Field from DB
 *
 * @example DeleteDBField( 'students', $_REQUEST['id'] );
 *
 * @since 10.0 Fix SQL error when column already dropped
 *
 * @global $DatabaseType mysql or postgresql
 *
 * @param  string  $table DB Table name.
 * @param  string  $id    Field ID.
 *
 * @return boolean true on success
 */
function DeleteDBField( $table, $id )
{
	global $DatabaseType;

	if ( ! AllowEdit()
		|| empty( $table )
		|| empty( $id )
		|| (string) (int) $id !== $id )
	{
		return false;
	}

	$table = mb_strtolower( $table );

	$fields_table = $table === 'students' ? 'custom' : $table;

	// Remove trailing / plural 's', excepted for address.
	$fields_table = mb_substr( $fields_table, -1 ) === 's' && mb_substr( $fields_table, -2 ) !== 'ss' ?
		mb_substr( $fields_table, 0, -1 ) :
		$fields_table;

	DBQuery( "DELETE FROM " . DBEscapeIdentifier( $fields_table . '_fields' ) .
		" WHERE ID='" . (int) $id . "'" );

	// Fix SQL error when column already dropped.
	$column_exists = DBGetOne( "SELECT 1
		FROM information_schema.COLUMNS
		WHERE TABLE_SCHEMA=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND TABLE_NAME='" . DBEscapeString( $table ) . "'
		AND COLUMN_NAME='" . DBEscapeString( 'CUSTOM_' . (int) $id ) . "'" );

	if ( $column_exists )
	{
		DBQuery( 'ALTER TABLE ' . DBEscapeIdentifier( $table ) . '
			DROP COLUMN ' . DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) );
	}

	return true;
}


/**
 * Delete Field Category from DB
 * And all fields in Category
 *
 * @example DeleteDBFieldCategory( 'students', $_REQUEST['category_id'] );
 *
 * @uses DeleteDBField()
 *
 * @param  string  $table DB Table name.
 * @param  string  $id    Field Category ID.
 *
 * @return boolean true on success
 */
function DeleteDBFieldCategory( $table, $id )
{
	if ( ! AllowEdit()
		|| empty( $table )
		|| empty( $id )
		|| (string) (int) $id !== $id )
	{
		return false;
	}

	$table = mb_strtolower( $table );

	$fields_table = $table === 'students' ? 'custom' : $table;

	// Delete all fields in Category.
	$fields = DBGet( "SELECT ID
		FROM " . DBEscapeIdentifier( $fields_table . '_fields' ) .
		" WHERE CATEGORY_ID='" . (int) $id . "'" );

	foreach ( (array) $fields as $field )
	{
		DeleteDBField( $table, $field['ID'] );
	}

	// Remove trailing / plural 's', excepted for address.
	$field_categories_table = mb_substr( $table, -1 ) === 's' && mb_substr( $table, -2 ) !== 'ss' ?
		mb_substr( $table, 0, -1 ) :
		$table;

	DBQuery( "DELETE FROM " . DBEscapeIdentifier( $field_categories_table . '_field_categories' ) .
		" WHERE ID='" . (int) $id . "'" );

	return true;
}


/**
 * Get Field or Field Category Form
 *
 * @since 4.6 Add Files type.
 *
 * @example echo GetFieldsForm( 'student', $title, $RET, $extra_fields );
 *
 * @example echo GetFieldsForm(
 *              'SCHOOL',
 *              $title,
 *              $RET,
 *              null,
 *              array( 'text' => _( 'Text' ), 'numeric' => _( 'Number' ), 'date' => _( 'Date' ), 'textarea' => _( 'Long Text' ), 'files' => _( 'Files' ) )
 *          );
 *
 * @uses DrawHeader()
 * @uses MakeFieldType()
 *
 * @param  string $table                 DB Table name, without trailing / plural 's'.
 * @param  string $title                 Form Title.
 * @param  array  $RET                   Field or Field Category Data.
 * @param  array  $extra_category_fields Extra fields for Field Category.
 * @param  array  $type_options          Associative array of Field Types (optional). Defaults to null.
 *
 * @return string Field or Field Category Form HTML
 */
function GetFieldsForm( $table, $title, $RET, $extra_category_fields = [], $type_options = null )
{
	$id = issetVal( $RET['ID'] );

	$category_id = issetVal( $RET['CATEGORY_ID'] );

	if ( empty( $table )
		|| ( empty( $id )
			&& empty( $category_id ) ) )
	{
		return '';
	}

	$table = mb_strtolower( $table );

	$new = $id === 'new' || $category_id === 'new';

	$form = '<form action="';

	$form .= PreparePHP_SELF(
		[],
		[ 'category_id', 'id', 'table', 'ML_tables' ]
	);

	if ( $category_id
		&& $category_id !== 'new' )
	{
		$form .= '&category_id=' . $category_id;
	}

	if ( $id
		&& $id !== 'new' )
	{
		$form .= '&id=' . $id;
	}

	if ( $id )
	{
		$full_table = $table === 'student' ? 'custom' : $table;

		$full_table .= '_fields';
	}
	else
	{
		$full_table = $table . '_field_categories';
	}

	$form .= '&table=' . $full_table . '" method="POST">';

	$delete_button = '';

	if ( AllowEdit()
		&& ! $new
		&& ( ( $id
				&& ( $table !== 'staff'
					|| ( $id != 200000000
						&& $id != 200000001 ) ) // Don't Delete Email & Phone User Fields.
				&& ( $table !== 'student'
					|| ( $id != 200000000
						&& $id != 200000004 ) ) ) // Don't Delete Gender & Birthday Student Fields.
			|| ( $category_id
				&& ( $table !== 'student'
					|| $category_id > 4 ) // Don't Delete first 4 Student Fields Categories.
				&& ( $table !== 'staff'
					|| $category_id > 2 ) ) ) ) // Don't Delete first 2 User Fields Categories.
	{
		$delete_url = PreparePHP_SELF(
			[],
			[ 'table', 'ML_tables' ],
			[
				'modfunc' => 'delete',
				'category_id' => $category_id,
				'id' => $id,
			]
		);

		$delete_button = '<input type="button" value="' . AttrEscape( _( 'Delete' ) ) .
			'" onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $delete_url ) . ');' ) . '"> ';
	}

	ob_start();

	DrawHeader( $title, $delete_button . SubmitButton() );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col"><tr class="st">';

	if ( $id )
	{
		// FJ field name required.
		$header .= '<td>' . MLTextInput(
			issetVal( $RET['TITLE'], '' ),
			'tables[' . $id . '][TITLE]',
			( empty( $RET['TITLE'] ) ? '<span class="legend-red">' : '' ) . _( 'Field Name' ) .
				( empty( $RET['TITLE'] ) ? '</span>' : '' ),
			'maxlength="200"'
		) . '</td>';

		if ( ! $type_options )
		{
			$type_options = [
				'select' => _( 'Pull-Down' ),
				'autos' => _( 'Auto Pull-Down' ),
				'exports' => _( 'Export Pull-Down' ),
				'multiple' => _( 'Select Multiple from Options' ),
				'text' => _( 'Text' ),
				'textarea' => _( 'Long Text' ),
				'radio' => _( 'Checkbox' ),
				'numeric' => _( 'Number' ),
				'date' => _( 'Date' ),
				'files' => _( 'Files' ),
			];
		}

		if ( ! $new )
		{
			// Mab - allow changing between select and autos and text and exports.
			if ( ( $table !== 'staff'
					|| $id < 200000000 ) // Don't change Email & Phone User Fields type.
				&& in_array( $RET['TYPE'], [ 'select', 'autos', 'text', 'exports' ] ) )
			{
				$type_options = array_intersect_key(
					$type_options,
					[
						'select' => _( 'Pull-Down' ),
						'autos' => _( 'Auto Pull-Down' ),
						'exports' => _( 'Export Pull-Down' ),
						'text' => _( 'Text' ),
					]
				);
			}
			// You can't change a student field type after it has been created.
			else
			{
				$type_options = false;
			}
		}

		// Data Type field.
		if ( ! $type_options )
		{
			$header .= '<td>' . NoInput(
				MakeFieldType( $RET['TYPE'] ),
				_( 'Data Type' )
			) . '</td>';
		}
		else
		{
			$header .= '<td' . ( ! $category_id ? ' colspan="2"' : '' ) . '>' . SelectInput(
				issetVal( $RET['TYPE'], '' ),
				'tables[' . $id . '][TYPE]',
				_( 'Data Type' ),
				$type_options,
				false,
				'onchange="FieldTypeSwitchSelectOptions(this.value);"'
			) . '</td>';

			// @since 9.0 JS Hide Options textarea if Field not of select type.
			$header .= '<script>function FieldTypeSwitchSelectOptions(type) {
				if (type == "select"
					|| type == "autos"
					|| type == "exports"
					|| type == "multiple" ) {
					$("#select_options_wrapper").show();
				} else {
					$("#select_options_wrapper").hide();
				}
			}
			</script>';
		}

		if ( $category_id )
		{
			// CATEGORIES.
			$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
				FROM " . DBEscapeIdentifier( $table . '_field_categories' ) .
				" ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

			foreach ( (array) $categories_RET as $type )
			{
				$categories_options[ $type['ID'] ] = ParseMLField( $type['TITLE'] );
			}

			if ( $table !== 'staff'
				|| $id < 200000000 ) // Don't change Email & Phone User Fields category.
			{
				$header .= '<td>' . SelectInput(
					$category_id,
					'tables[' . $id . '][CATEGORY_ID]',
					_( 'Field Category' ),
					$categories_options,
					false
				) . '</td>';
			}
			else
			{
				$header .= '<td>' . NoInput(
					$categories_options[ $category_id ],
					_( 'Field Category' )
				) . '</td>';
			}
		}
		// No Fields Categories, ie: School Fields.

		$header .= '</tr><tr class="st">';

		// Select Options TextArea field.
		if ( isset( $RET['TYPE'] )
			&& in_array( $RET['TYPE'], [ 'autos', 'select', 'multiple', 'exports' ] )
			|| ( $new
				&& array_intersect(
					array_keys( $type_options ),
					[ 'autos', 'select', 'multiple', 'exports' ] ) ) )
		{
			$header .= '<td colspan="3"><div id="select_options_wrapper">' . TextAreaInput(
				issetVal( $RET['SELECT_OPTIONS'], '' ),
				'tables[' . $id . '][SELECT_OPTIONS]',
				_( 'Options' ) .
				'<div class="tooltip"><i>' . _( 'One per line' ) . '<br>' .
				_( 'Pull-Down' ) . ' / ' . _( 'Auto Pull-Down' ) . ' / ' . _( 'Export Pull-Down' ) . ' / ' .
				_( 'Select Multiple from Options' ) . '</i></div>',
				'rows=5 cols=40',
				true,
				false
			) . '</div></td>';

			$header .= '</tr><tr class="st">';
		}

		// Default Selection field.
		$header .= '<td>' . TextInput(
			issetVal( $RET['DEFAULT_SELECTION'], '' ),
			'tables[' . $id . '][DEFAULT_SELECTION]',
			_( 'Default' ) .
			'<div class="tooltip"><i>' . _( 'For dates: YYYY-MM-DD' ).'<br>' .
			_( 'for checkboxes: Y' ) . '</i></div>'
		) . '</td>';

		// Required field.
		$header .= '<td>' . CheckboxInput(
			issetVal( $RET['REQUIRED'], '' ),
			'tables[' . $id . '][REQUIRED]',
			_( 'Required' ),
			'',
			$new
		) . '</td>';

		// Sort Order field.
		$header .= '<td>' . TextInput(
			issetVal( $RET['SORT_ORDER'], '' ),
			'tables[' . $id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		$header .= '</tr></table>';
	}
	// Fields Category Form.
	else
	{
		// Title field.
		$header .= '<td>' . MLTextInput(
			issetVal( $RET['TITLE'], '' ),
			'tables[' . $category_id . '][TITLE]',
			( empty( $RET['TITLE'] ) ? '<span class="legend-red">' : '') . _( 'Title' ) .
				( empty( $RET['TITLE'] ) ? '</span>' : '' ),
			'maxlength="36"'
		) . '</td>';

		// Sort Order field.
		$header .= '<td>' . TextInput(
			issetVal( $RET['SORT_ORDER'], '' ),
			'tables[' . $category_id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		// Extra Fields.
		if ( ! empty( $extra_category_fields ) )
		{
			$i = 2;

			foreach ( (array) $extra_category_fields as $extra_field )
			{
				if ( $i % 3 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_category_fields ) + 1 ) )
				{
					$colspan = abs( ( $i % 3 ) - 3 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}
		}

		$header .= '</tr></table>';
	}

	ob_start();

	DrawHeader( $header );

	$form .= ob_get_clean();

	$form .= '</form>';

	return $form;
}


/**
 * Outputs Fields or Field Categories Menu
 *
 * @example FieldsMenuOutput( $fields_RET, $_REQUEST['id'], $_REQUEST['category_id'] );
 * @example FieldsMenuOutput( $categories_RET, $_REQUEST['category_id'] );
 * @example FieldsMenuOutput( $school_fields_RET, $_REQUEST['id'], false );
 *
 * @uses ListOutput()
 *
 * @param array  $RET         Field Categories (ID, TITLE, SORT_ORDER columns) or Fields (+ TYPE column) RET.
 * @param string $id          Field Category ID or Field ID.
 * @param string $category_id Field Category ID. Set to false to disable Categories (optional). Defaults to '0'.
 */
function FieldsMenuOutput( $RET, $id, $category_id = '0' )
{
	if ( $RET
		&& $id
		&& $id !== 'new' )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['ID'] == $id )
			{
				$RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	$LO_columns = [
		'TITLE' => ( $category_id || $category_id === false ? _( 'Field' ) : _( 'Category' ) ),
		'SORT_ORDER' => _( 'Sort Order' ),
	];

	if ( $category_id
		|| $category_id === false )
	{
		$LO_columns['TYPE'] = _( 'Data Type' );
	}

	$LO_link = [];

	$LO_link['TITLE']['link'] = PreparePHP_SELF(
		[],
		[ 'category_id', 'id', 'table', 'ML_tables' ]
	);

	if ( $category_id )
	{
		$LO_link['TITLE']['link'] .= '&category_id=' . $category_id;
	}

	$LO_link['TITLE']['variables'] = [ ( ! $category_id && $category_id !== false ? 'category_id' : 'id' ) => 'ID' ];

	$LO_link['add']['link'] = PreparePHP_SELF(
		[],
		[ 'category_id', 'id', 'table', 'ML_tables' ]
	) . '&category_id=';

	$LO_link['add']['link'] .= $category_id || $category_id === false ? $category_id . '&id=new' : 'new';

	$RET = ParseMLArray( $RET, 'TITLE' );

	if ( ! $category_id
		&& $category_id !== false )
	{
		ListOutput(
			$RET,
			$LO_columns,
			'Field Category',
			'Field Categories',
			$LO_link,
			[],
			$LO_options
		);
	}
	else
	{
		ListOutput(
			$RET,
			$LO_columns,
			'Field',
			'Fields',
			$LO_link,
			[],
			$LO_options
		);
	}
}


/**
 * Make Field Type
 *
 * @example MakeFieldType( 'column' );
 *
 * @see Can be called through DBGet()'s functions parameter
 *
 * @since 4.6 Add Files type.
 *
 * @param  string $value  Field Type value.
 * @param  string $column 'TYPE' (optional). Defaults to ''.
 *
 * @return string Translated Field type
 */
function MakeFieldType( $value, $column = '' )
{
	$type_options = [
		'select' => _( 'Pull-Down' ),
		'autos' => _( 'Auto Pull-Down' ),
		'exports' => _( 'Export Pull-Down' ),
		'multiple' => _( 'Select Multiple from Options' ),
		'text' => _( 'Text' ),
		'textarea' => _( 'Long Text' ),
		'radio' => _( 'Checkbox' ),
		'numeric' => _( 'Number' ),
		'date' => _( 'Date' ),
		'files' => _( 'Files' ),
	];

	return isset( $type_options[ $value ] ) ? $type_options[ $value ] : $value;
}


/**
 * Filter Custom (Textarea / Long text) fields' MarkDown
 * Use before inserting/updating Fields.
 *
 * @example $_REQUEST['staff'] = FilterCustomFieldsMarkdown( 'staff_fields', 'staff' );
 *
 * @since 6.0 Add $request_index_3 param.
 *
 * @uses SanitizeMarkDown()
 *
 * @param string $table           Custom fields TABLE name.
 * @param string $request_index   $_REQUEST var array values index.
 * @param string $request_index_2 $_REQUEST var array values index #2.
 * @param string $request_index_3 $_REQUEST var array values index #3.
 *
 * @return array $request_values with MarkDown filtered.
 */
function FilterCustomFieldsMarkdown( $table, $request_index, $request_index_2 = '', $request_index_3 = '' )
{
	if ( $request_index_2 === '' )
	{
		$request_values = issetVal( $_REQUEST[ $request_index ] );

		$post_values = issetVal( $_POST[ $request_index ] );
	}
	elseif ( $request_index_3 === '' )
	{
		$request_values = issetVal( $_REQUEST[ $request_index ][ $request_index_2 ] );

		$post_values = issetVal( $_POST[ $request_index ][ $request_index_2 ] );
	}
	else
	{
		$request_values = issetVal( $_REQUEST[ $request_index ][ $request_index_2 ][ $request_index_3 ] );

		$post_values = issetVal( $_POST[ $request_index ][ $request_index_2 ][ $request_index_3 ] );
	}

	if ( ! $table )
	{
		return $request_values;
	}

	// Sanitize table name: only alphanumeric & underscore characters.
	$table = preg_replace( "/[^a-zA-Z0-9_]+/", '', $table );

	// FJ textarea fields MarkDown sanitize.
	$textarea_RET = DBGet( "SELECT ID
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE TYPE='textarea'" );

	if ( ! $textarea_RET )
	{
		return $request_values;
	}

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	foreach ( (array) $textarea_RET as $textarea )
	{
		$custom_index = 'CUSTOM_' . $textarea['ID'];

		if ( isset( $post_values[ $custom_index ] )
			&& ! empty( $post_values[ $custom_index ] ) )
		{
			$request_values[ $custom_index ] = DBEscapeString( SanitizeMarkDown(
				$post_values[ $custom_index ]
			) );
		}
	}

	return $request_values;
}


/**
 * Check Required Custom Fields for empty values.
 * Use before inserting/updating Fields.
 *
 * @example $required_error = $required_error || CheckRequiredCustomFields( 'custom_fields', $_REQUEST['students'] );
 *
 * @param string $table          Custom fields TABLE name.
 * @param array  $request_values $_REQUEST var array of fields values.
 *
 * @return boolean true if one Required Custom field is empty, else false.
 */
function CheckRequiredCustomFields( $table, $request_values )
{
	if ( empty( $table ) )
	{
		return false;
	}

	$required_RET = DBGet( "SELECT ID FROM " . DBEscapeIdentifier( $table ) . "
		WHERE REQUIRED='Y'" );

	foreach ( (array) $required_RET as $required )
	{
		if ( isset( $request_values['CUSTOM_' . $required['ID'] ] )
			&& empty( $request_values[ 'CUSTOM_' . $required['ID'] ] )
			&& $request_values[ 'CUSTOM_' . $required['ID'] ] !== '0' )
		{
			return true;
		}
	}

	return false;
}
