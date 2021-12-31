<?php
/**
 * Student Fields
 *
 * @package RosarioSIS
 * @subpackage modules
 */

if ( isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] )
	&& AllowEdit() )
{
	$table = issetVal( $_REQUEST['table'] );

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		// FJ fix SQL bug invalid sort order.
		if ( ( empty( $columns['SORT_ORDER'] )
				|| is_numeric( $columns['SORT_ORDER'] ) )
			&& ( empty( $columns['COLUMNS'] )
				|| is_numeric( $columns['COLUMNS'] ) ) )
		{
			// FJ added SQL constraint TITLE is not null.
			if ( ! isset( $columns['TITLE'] )
				|| ! empty( $columns['TITLE'] ) )
			{
				if ( isset( $columns['SELECT_OPTIONS'] )
					&& $columns['SELECT_OPTIONS'] )
				{
					// @since 6.0 Trim select Options.
					$columns['SELECT_OPTIONS'] = trim( $columns['SELECT_OPTIONS'] );
				}

				// FJ Fix PHP fatal error: check Include file exists.
				if ( isset( $columns['INCLUDE'] )
					&& $columns['INCLUDE'] )
				{
					$include_file_path = 'modules/' . $columns['INCLUDE'] . '.inc.php';

					// @since 4.5 Include Student/User Info tab from custom plugin.
					$plugins_include_file_path = 'plugins/' . $columns['INCLUDE'] . '.inc.php';

					if ( ! file_exists( $include_file_path )
						&& ! file_exists( $plugins_include_file_path ) )
					{
						// File does not exist: reset + error.
						unset( $columns['INCLUDE'] );

						$error[] = sprintf(
							_( 'The include file was not found: "%s"' ),
							$include_file_path . ', ' . $plugins_include_file_path
						);
					}
				}

				// Update Field / Category.
				if ( $id !== 'new' )
				{
					if ( isset( $columns['CATEGORY_ID'] )
						&& $columns['CATEGORY_ID'] != $_REQUEST['category_id'] )
					{
						$_REQUEST['category_id'] = $columns['CATEGORY_ID'];
					}

					$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

					$go = true;
				}
				// New Field / Category.
				else
				{
					$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

					// New Field.
					if ( $table === 'CUSTOM_FIELDS' )
					{
						if ( isset( $columns['CATEGORY_ID'] ) )
						{
							$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

							unset( $columns['CATEGORY_ID'] );
						}

						$_REQUEST['id'] = AddDBField( 'STUDENTS', 'custom_fields_id_seq', $columns['TYPE'] );

						$fields = 'ID,CATEGORY_ID,';

						$values = $_REQUEST['id'] . ",'" . $_REQUEST['category_id'] . "',";
					}
					// New Category.
					elseif ( $table === 'STUDENT_FIELD_CATEGORIES' )
					{
						$id = DBSeqNextID( 'student_field_categories_id_seq' );

						$fields = "ID,";

						$values = $id . ",";

						$_REQUEST['category_id'] = $id;

						// Add to profile or permissions of user creating it.
						if ( User( 'PROFILE_ID' ) )
						{
							DBQuery( "INSERT INTO PROFILE_EXCEPTIONS (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
								values('" . User( 'PROFILE_ID' ) . "','Students/Student.php&category_id=" . $id . "','Y','Y')" );
						}
						else
						{
							DBQuery( "INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT)
								values('" . User( 'STAFF_ID' ) . "','Students/Student.php&category_id=" . $id . "','Y','Y')" );
						}
					}

					$go = false;

					foreach ( (array) $columns as $column => $value )
					{
						if ( ! empty( $value )
							|| $value == '0' )
						{
							$fields .= $column . ',';

							$values .= "'" . $value . "',";

							$go = true;
						}
					}
					$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
				}

				if ( $go )
				{
					DBQuery( $sql );
				}
			}
			else
				$error[] = _( 'Please fill in the required fields' );
		}
		else
			$error[] = _( 'Please enter valid Numeric data.' );
	}

	// Unset tables & redirect URL.
	RedirectURL( 'tables' );
}

// Delete Field / Category.
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( isset( $_REQUEST['id'] )
		&& intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'Student Field' ) ) )
		{
			DeleteDBField( 'STUDENTS', $_REQUEST['id'] );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& intval( $_REQUEST['category_id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'Student Field Category' ) . ' ' .
				_( 'and all fields in the category' ) ) )
		{
			DeleteDBFieldCategory( 'STUDENTS', $_REQUEST['category_id'] );

			// Remove from profiles and permissions.
			$delete_sql = "DELETE FROM PROFILE_EXCEPTIONS
				WHERE MODNAME='Students/Student.php&category_id=" . $_REQUEST['category_id'] . "';";

			$delete_sql .= "DELETE FROM STAFF_EXCEPTIONS
				WHERE MODNAME='Students/Student.php&category_id=" . $_REQUEST['category_id'] . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & category ID redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	// Category menu: student|address|contact.
	DrawHeader( _fieldsCategoryMenu( $_REQUEST['category'] ) );

	$RET = [];

	// ADDING & EDITING FORM.
	if ( $_REQUEST['id']
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID,CATEGORY_ID,TITLE,TYPE,SELECT_OPTIONS,
			DEFAULT_SELECTION,SORT_ORDER,REQUIRED,
			(SELECT TITLE
				FROM STUDENT_FIELD_CATEGORIES
				WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
			FROM CUSTOM_FIELDS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['CATEGORY_TITLE'] ) . ' - ' . ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !== 'new'
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,SORT_ORDER,INCLUDE,COLUMNS
			FROM STUDENT_FIELD_CATEGORIES
			WHERE ID='" . $_REQUEST['category_id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['id'] === 'new' )
	{
		$title = _( 'New Student Field' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = issetVal( $_REQUEST['category_id'] );
	}
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = _( 'New Student Field Category' );

		$RET['CATEGORY_ID'] = 'new';

		$RET['COLUMNS'] = '';
		$RET['INCLUDE'] = '';
	}

	if ( $_REQUEST['category_id']
		&& ! $_REQUEST['id'] )
	{
		$extra_fields = [ TextInput(
			$RET['COLUMNS'],
			'tables[' . $_REQUEST['category_id'] . '][COLUMNS]',
			_( 'Display Columns' ),
			' type="number" min="1" max="10"'
		) ];

		if ( $_REQUEST['category_id'] > 4
			|| $_REQUEST['category_id'] === 'new' )
		{
			// TODO: check if INCLUDE file (+ ".inc.php") exsits.
			$extra_fields[] = TextInput(
				$RET['INCLUDE'],
				'tables[' . $_REQUEST['category_id'] . '][INCLUDE]',
				_( 'Include (should be left blank for most categories)' )
			);
		}
	}

	echo GetFieldsForm(
		'STUDENT',
		$title,
		$RET,
		issetVal( $extra_fields, [] )
	);

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM STUDENT_FIELD_CATEGORIES
		ORDER BY SORT_ORDER,TITLE" );

	// DISPLAY THE MENU.
	echo '<div class="st">';

	FieldsMenuOutput( $categories_RET, $_REQUEST['category_id'] );

	echo '</div>';

	// FIELDS.
	if ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !=='new'
		&& $categories_RET )
	{
		$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SORT_ORDER
			FROM CUSTOM_FIELDS
			WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'
			ORDER BY SORT_ORDER,TITLE", [ 'TYPE' => 'MakeFieldType' ] );

		echo '<div class="st">';

		FieldsMenuOutput( $fields_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}

