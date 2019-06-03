
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
 *
 * @example $_REQUEST['id'] = AddDBField( 'SCHOOLS', 'school_fields_seq', $columns['TYPE'] );
 *
 * @param string  $table    DB Table name.
 * @param string  $sequence DB Sequence name.
 * @param string  $type     Field Type: radio|text|exports|select|autos|edits|codeds|multiple|numeric|date|textarea|files.
 *
 * @return string Field ID or empty string
 */
function AddDBField( $table, $sequence, $type )
{
	// Please add your TABLE here.
	/*$allowed_tables = array(
		'STUDENTS',
		'ADDRESS',
		'PEOPLE',
		'STAFF',
		'SCHOOLS',
	);*/

	if ( ! AllowEdit()
		|| empty( $table )
		|| empty( $type ) )
	{
		return '';
	}

	$id = DBSeqNextID( $sequence );

	$fields = 'ID,CATEGORY_ID,';

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

			$create_index = false;

		break;
	}

	DBQuery( 'ALTER TABLE ' . DBEscapeIdentifier( $table ) . ' ADD ' .
		DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) . ' ' . $sql_type );

	if ( $create_index )
	{
		$index_name = $table === 'STUDENTS' ? 'CUSTOM_IND' : $table . '_IND';

		DBQuery( 'CREATE INDEX ' . DBEscapeIdentifier( $index_name . (int) $id ) .
			' ON ' . DBEscapeIdentifier( $table ) .
			' (' . DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) . ')' );
	}

	return $id;
}


/**
 * Delete Field from DB
 *
 * @example DeleteDBField( 'STUDENTS', $_REQUEST['id'] );
 *
 * @param  string  $table DB Table name.
 * @param  string  $id    Field ID.
 *
 * @return boolean true on success
 */
function DeleteDBField( $table, $id )
{
	// Please add your TABLE here.
	/*$allowed_tables = array(
		'STUDENTS',
		'ADDRESS',
		'PEOPLE',
		'STAFF',
		'SCHOOLS',
	);*/

	if ( ! AllowEdit()
		|| empty( $table )
		|| empty( $id )
		|| (string) (int) $id !== $id )
	{
		return false;
	}

	$fields_table = $table === 'STUDENTS' ? 'CUSTOM' : $table;

	// Remove trailing / plural 'S', excepted for ADDRESS.
	$fields_table = mb_substr( $fields_table, -1 ) === 'S' && mb_substr( $fields_table, -2 ) !== 'SS' ?
		mb_substr( $fields_table, 0, -1 ) :
		$fields_table;

	DBQuery( "DELETE FROM " . DBEscapeIdentifier( $fields_table . '_FIELDS' ) .
		" WHERE ID='" . (int) $id . "'" );

	DBQuery( 'ALTER TABLE ' . DBEscapeIdentifier( $table ) . '
		DROP COLUMN ' . DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) );

	return true;
}


/**
 * Delete Field Category from DB
 * And all fields in Category
 *
 * @example DeleteDBFieldCategory( 'STUDENTS', $_REQUEST['category_id'] );
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
	// Please add your TABLE here.
	/*$allowed_tables = array(
		'STUDENTS',
		'ADDRESS',
		'PEOPLE',
		'STAFF',
	);*/

	if ( ! AllowEdit()
		|| empty( $table )
		|| empty( $id )
		|| (string) (int) $id !== $id )
	{
		return false;
	}

	$fields_table = $table === 'STUDENTS' ? 'CUSTOM' : $table;

	// Delete all fields in Category.
	$fields = DBGet( "SELECT ID
		FROM " . DBEscapeIdentifier( $fields_table . '_FIELDS' ) .
		" WHERE CATEGORY_ID='" . (int) $id . "'" );

	foreach ( (array) $fields as $field )
	{
		DeleteDBField( $table, $field['ID'] );
	}

	// Remove trailing / plural 'S', excepted for ADDRESS.
	$field_categories_table = mb_substr( $table, -1 ) === 'S' && mb_substr( $table, -2 ) !== 'SS' ?
		mb_substr( $table, 0, -1 ) :
		$table;

	DBQuery( "DELETE FROM " . DBEscapeIdentifier( $field_categories_table . '_FIELD_CATEGORIES' ) .
		" WHERE ID='" . (int) $id . "'" );

	return true;
}


/**
 * Get Field or Field Category Form
 *
 * @since 4.6 Add Files type.
 *
 * @example echo GetFieldsForm( 'STUDENT', $title, $RET, $extra_fields );
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
 * @param  string $table                 DB Table name, without trailing / plural 'S'.
 * @param  string $title                 Form Title.
 * @param  array  $RET                   Field or Field Category Data.
 * @param  array  $extra_category_fields Extra fields for Field Category.
 * @param  array  $type_options          Associative array of Field Types (optional). Defaults to null.
 *
 * @return string Field or Field Category Form HTML
 */
function GetFieldsForm( $table, $title, $RET, $extra_category_fields = array(), $type_options = null )
{
	// Please add your TABLE here.
	/*$allowed_tables = array(
		'STUDENT',
		'ADDRESS',
		'PEOPLE',
		'STAFF',
		'SCHOOL',
	);*/

	$id = $RET['ID'];

	$category_id = empty( $RET['CATEGORY_ID'] ) ? '' : $RET['CATEGORY_ID'];

	if ( empty( $table )
		|| ( empty( $id )
			&& empty( $category_id ) ) )
	{
		return '';
	}

	$new = $id === 'new' || $category_id === 'new';

	$form = '<form action="';

	$form .= PreparePHP_SELF(
		array(),
		array( 'category_id', 'id', 'table', 'ML_tables' )
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
		$full_table = $table === 'STUDENT' ? 'CUSTOM' : $table;

		$full_table .= '_FIELDS';
	}
	else
	{
		$full_table = $table . '_FIELD_CATEGORIES';
	}

	$form .= '&table=' . $full_table . '" method="POST">';

	if ( AllowEdit()
		&& ! $new
		&& ( $id
			|| ( $category_id
				&& ( $table !== 'STUDENT'
					|| $category_id > 4 ) // Don't Delete first 4 Student Fields Categories.
				&& ( $table !== 'STAFF'
					|| $category_id > 2 ) ) ) ) // Don't Delete first 2 User Fields Categories.
	{
		$delete_url = PreparePHP_SELF(
			array(),
			array( 'table', 'ML_tables' ),
			array(
				'modfunc' => 'delete',
				'category_id' => $category_id,
				'id' => $id,
			)
		);

		$delete_button = '<input type="button" value="' . _( 'Delete' ) . '" onClick="ajaxLink(\'' . $delete_url . '\');" /> ';
	}

	ob_start();

	DrawHeader( $title, $delete_button . SubmitButton() );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col"><tr class="st">';

	if ( $id )
	{
		// FJ field name required.
		$header .= '<td>' . MLTextInput(
			$RET['TITLE'],
			'tables[' . $id . '][TITLE]',
			( ! $RET['TITLE'] ? '<span class="legend-red">' : '' ) . _( 'Field Name' ) . ( ! $RET['TITLE'] ? '</span>' : '' )
		) . '</td>';

		if ( ! $type_options )
		{
			$type_options = array(
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
			);
		}

		if ( ! $new )
		{
			// Mab - allow changing between select and autos and text and exports.
			if ( in_array( $RET['TYPE'], array( 'select', 'autos', 'text', 'exports' ) ) )
			{
				$type_options = array_intersect_key(
					$type_options,
					array(
						'select' => _( 'Pull-Down' ),
						'autos' => _( 'Auto Pull-Down' ),
						'exports' => _( 'Export Pull-Down' ),
						'text' => _( 'Text' ),
					)
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
				$RET['TYPE'],
				'tables[' . $id . '][TYPE]',
				_( 'Data Type' ),
				$type_options,
				false
			) . '</td>';
		}

		if ( $category_id )
		{
			// CATEGORIES.
			$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
				FROM " . DBEscapeIdentifier( $table . '_FIELD_CATEGORIES' ) .
				" ORDER BY SORT_ORDER,TITLE" );

			foreach ( (array) $categories_RET as $type )
			{
				$categories_options[ $type['ID'] ] = ParseMLField( $type['TITLE'] );
			}

			$header .= '<td>' . SelectInput(
				$RET['CATEGORY_ID'] ? $RET['CATEGORY_ID'] : $category_id,
				'tables[' . $id . '][CATEGORY_ID]',
				_( 'Field Category' ),
				$categories_options,
				false
			) . '</td>';
		}
		// No Fields Categories, ie: School Fields.

		$header .= '</tr><tr class="st">';

		// Select Options TextArea field.
		if ( in_array( $RET['TYPE'], array( 'autos', 'select', 'multiple', 'exports' ) )
			|| ( $new
				&& array_intersect(
					array_keys( $type_options ),
					array( 'autos', 'select', 'multiple', 'exports' ) ) ) )
		{
			$header .= '<td colspan="3">' . TextAreaInput(
				$RET['SELECT_OPTIONS'],
				'tables[' . $id . '][SELECT_OPTIONS]',
				_( 'Pull-Down' ) . '/' . _( 'Auto Pull-Down' ) . '/' . _( 'Export Pull-Down' ) . '/' .
				_( 'Select Multiple from Options' ) .
				'<div class="tooltip"><i>' . _( 'One per line' ) . '</i></div>',
				'rows=5 cols=40',
				true,
				false
			) . '</td>';

			$header .= '</tr><tr class="st">';
		}

		// Default Selection field.
		$header .= '<td>' . TextInput(
			$RET['DEFAULT_SELECTION'],
			'tables[' . $id . '][DEFAULT_SELECTION]',
			_( 'Default' ) .
			'<div class="tooltip"><i>' . _( 'For dates: YYYY-MM-DD' ).'<br />' .
			_( 'for checkboxes: Y' ) . '</i></div>'
		) . '</td>';

		// Required field.
		$header .= '<td>' . CheckboxInput(
			$RET['REQUIRED'],
			'tables[' . $id . '][REQUIRED]',
			_( 'Required' ),
			'',
			$new
		) . '</td>';

		// Sort Order field.
		$header .= '<td>' . TextInput(
			$RET['SORT_ORDER'],
			'tables[' . $id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			'size=5'
		) . '</td>';

		$header .= '</tr></table>';
	}
	// Fields Category Form.
	else
	{
		// Title field.
		$header .= '<td>' . MLTextInput(
			$RET['TITLE'],
			'tables[' . $category_id . '][TITLE]',
			( ! $RET['TITLE'] ? '<span class="legend-red">' : '') . _( 'Title' ) . ( ! $RET['TITLE'] ? '</span>' : '' )
		) . '</td>';

		// Sort Order field.
		$header .= '<td>' . TextInput(
			$RET['SORT_ORDER'],
			'tables[' . $category_id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			'size=5'
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

	$LO_options = array( 'save' => false, 'search' => false, 'responsive' => false );

	$LO_columns = array(
		'TITLE' => ( $category_id || $category_id === false ? _( 'Field' ) : _( 'Category' ) ),
		'SORT_ORDER' => _( 'Sort Order' ),
	);

	if ( $category_id
		|| $category_id === false )
	{
		$LO_columns['TYPE'] = _( 'Data Type' );
	}

	$LO_link = array();

	$LO_link['TITLE']['link'] = PreparePHP_SELF(
		array(),
		array( 'category_id', 'id', 'table', 'ML_tables' )
	);

	if ( $category_id )
	{
		$LO_link['TITLE']['link'] .= '&category_id=' . $category_id;
	}

	$LO_link['TITLE']['variables'] = array( ( ! $category_id && $category_id !== false ? 'category_id' : 'id' ) => 'ID' );

	$LO_link['add']['link'] = PreparePHP_SELF(
		array(),
		array( 'category_id', 'id', 'table', 'ML_tables' )
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
			array(),
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
			array(),
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
	$type_options = array(
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
	);

	return isset( $type_options[ $value ] ) ? $type_options[ $value ] : $value;
}


/**
 * Filter Custom (Textarea / Long text) fields' MarkDown
 * Use before inserting/updating Fields.
 *
 * @example $_REQUEST['staff'] = FilterCustomFieldsMarkdown( 'STAFF_FIELDS', 'staff' );
 *
 * @since 4.4 Do not check allowed tables, sanitize table name instead.
 *
 * @uses SanitizeMarkDown()
 *
 * @param string $table           Custom fields TABLE name.
 * @param string $request_index   $_REQUEST var array values index.
 * @param string $request_index_2 $_REQUEST var array values index #2.
 *
 * @return array $request_values with MarkDown filtered.
 */
function FilterCustomFieldsMarkdown( $table, $request_index, $request_index_2 = '' )
{
	// Please add your TABLE here.
	/*$allowed_tables = array(
		'CUSTOM_FIELDS',
		'ADDRESS_FIELDS',
		'PEOPLE_FIELDS',
		'STAFF_FIELDS',
		'SCHOOL_FIELDS',
	);*/

	if ( ! $request_index_2 )
	{
		$request_values = isset( $_REQUEST[ $request_index ] ) ? $_REQUEST[ $request_index ] : null;

		$post_values = $_POST[ $request_index ];
	}
	else
	{
		$request_values = isset( $_REQUEST[ $request_index ][ $request_index_2 ] ) ? $_REQUEST[ $request_index ][ $request_index_2 ] : null;

		$post_values = $_POST[ $request_index ][ $request_index_2 ];
	}

	if ( ! $table
		/*|| ! in_array( (string) $table, $allowed_tables )*/ )
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
			$request_values[ $custom_index ] = SanitizeMarkDown(
				$post_values[ $custom_index ]
			);
		}
	}

	return $request_values;
}


/**
 * Check Required Custom Fields for empty values.
 * Use before inserting/updating Fields.
 *
 * @example $required_error = $required_error || CheckRequiredCustomFields( 'CUSTOM_FIELDS', $_REQUEST['students'] );
 *
 * @param string $table          Custom fields TABLE name.
 * @param string $request_values $_REQUEST var array of fields values.
 *
 * @return boolean true if one Required Custom field is empty, else false.
 */
function CheckRequiredCustomFields( $table, $request_values )
{
	// Please add your TABLE here.
	/*$allowed_tables = array(
		'CUSTOM_FIELDS',
		'ADDRESS_FIELDS',
		'PEOPLE_FIELDS',
		'STAFF_FIELDS',
		'SCHOOL_FIELDS',
	);*/

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
