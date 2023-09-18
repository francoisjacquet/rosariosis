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

	if ( ! in_array( $table, [ 'student_field_categories', 'custom_fields' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

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

					DBUpdate(
						$table,
						$columns,
						[ 'ID' => (int) $id ]
					);
				}
				// New Field / Category.
				else
				{
					$insert_columns = [];

					// New Field.
					if ( $table === 'custom_fields' )
					{
						if ( isset( $columns['CATEGORY_ID'] ) )
						{
							$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

							unset( $columns['CATEGORY_ID'] );
						}

						$insert_columns = [ 'CATEGORY_ID' => (int) $_REQUEST['category_id'] ];
					}

					$id = DBInsert(
						$table,
						$insert_columns + $columns,
						'id'
					);

					if ( $id )
					{
						if ( $table === 'custom_fields' )
						{
							AddDBField( 'students', $id, $columns['TYPE'] );

							$_REQUEST['id'] = $id;
						}
						elseif ( $table === 'student_field_categories' )
						{
							// Add to profile or permissions of user creating it.
							DBInsert(
								User( 'PROFILE_ID' ) ? 'profile_exceptions' : 'staff_exceptions',
								[
									( User( 'PROFILE_ID' ) ?
										'PROFILE_ID' : 'USER_ID' ) => ( User( 'PROFILE_ID' ) ?
											User( 'PROFILE_ID' ) : User( 'STAFF_ID' ) ),
									'MODNAME' => 'Students/Student.php&category_id=' . $id,
									'CAN_USE' => 'Y',
									'CAN_EDIT' => 'Y',
								]
							);

							$_REQUEST['category_id'] = $id;
						}
					}
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
			DeleteDBField( 'students', $_REQUEST['id'] );

			if ( Config( 'STUDENTS_EMAIL_FIELD' ) === $_REQUEST['id'] )
			{
				// Fix SQL error if delete Student email field, reset.
				Config( 'STUDENTS_EMAIL_FIELD', '' );
			}

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
			DeleteDBFieldCategory( 'students', $_REQUEST['category_id'] );

			// Remove from profiles and permissions.
			$delete_sql = "DELETE FROM profile_exceptions
				WHERE MODNAME='Students/Student.php&category_id=" . $_REQUEST['category_id'] . "';";

			$delete_sql .= "DELETE FROM staff_exceptions
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
				FROM student_field_categories
				WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
			FROM custom_fields
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['CATEGORY_TITLE'] ) . ' - ' . ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !== 'new'
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,SORT_ORDER,INCLUDE,COLUMNS
			FROM student_field_categories
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'" );

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
		'student',
		$title,
		$RET,
		issetVal( $extra_fields, [] )
	);

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM student_field_categories
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

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
			FROM custom_fields
			WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [ 'TYPE' => 'MakeFieldType' ] );

		echo '<div class="st">';

		FieldsMenuOutput( $fields_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}

