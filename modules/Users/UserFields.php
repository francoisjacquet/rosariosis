<?php
/**
 * User Fields
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'ProgramFunctions/Fields.fnc.php';

$_REQUEST['id'] = issetVal( $_REQUEST['id'], '' );
$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'], '' );

DrawHeader( ProgramTitle() );

//$_ROSARIO['allow_edit'] = true;

if ( isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] )
	&& AllowEdit() )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'staff_field_categories', 'staff_fields' ] ) )
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
					// @since 6.0 Trim select options.
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
					if ( $table === 'staff_fields' )
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
						if ( $table === 'staff_fields' )
						{
							AddDBField( 'staff', $id, $columns['TYPE'] );

							$_REQUEST['id'] = $id;
						}
						elseif ( $table === 'staff_field_categories' )
						{
							// Add to profile or permissions of user creating it.
							DBInsert(
								User( 'PROFILE_ID' ) ? 'profile_exceptions' : 'staff_exceptions',
								[
									( User( 'PROFILE_ID' ) ?
										'PROFILE_ID' : 'USER_ID' ) => ( User( 'PROFILE_ID' ) ?
											User( 'PROFILE_ID' ) : User( 'STAFF_ID' ) ),
									'MODNAME' => 'Users/User.php&category_id=' . $id,
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
		if ( DeletePrompt( _( 'User Field' ) ) )
		{
			DeleteDBField( 'staff', $_REQUEST['id'] );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( intval( $_REQUEST['category_id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'User Field Category' ) . ' ' .
				_( 'and all fields in the category' ) ) )
		{
			DeleteDBFieldCategory( 'staff', $_REQUEST['category_id'] );

			// Remove from profiles and permissions.
			$delete_sql = "DELETE FROM profile_exceptions
				WHERE MODNAME='Users/User.php&category_id=" . $_REQUEST['category_id'] . "';";

			$delete_sql .= "DELETE FROM staff_exceptions
				WHERE MODNAME='Users/User.php&category_id=" . $_REQUEST['category_id'] . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & category ID & redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$RET = [];

	// ADDING & EDITING FORM.
	if ( $_REQUEST['id']
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID,CATEGORY_ID,TITLE,TYPE,SELECT_OPTIONS,
			DEFAULT_SELECTION,SORT_ORDER,REQUIRED,
			(SELECT TITLE
				FROM staff_field_categories
				WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
			FROM staff_fields
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['CATEGORY_TITLE'] ) . ' - ' . ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !== 'new'
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,ADMIN,TEACHER,PARENT,NONE,SORT_ORDER,INCLUDE,COLUMNS
			FROM staff_field_categories
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['id'] === 'new' )
	{
		$title = _( 'New User Field' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = $_REQUEST['category_id'];
	}
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = _( 'New User Field Category' );

		$RET['CATEGORY_ID'] = 'new';

		$RET['COLUMNS'] = '';
		$RET['ADMIN'] = $RET['TEACHER'] = $RET['PARENT'] = $RET['NONE'] = '';
		$RET['INCLUDE'] = '';
	}

	if ( $_REQUEST['category_id']
		&& ! $_REQUEST['id'] )
	{
		$extra_fields = [];

		$extra_fields[] = TextInput(
			$RET['COLUMNS'],
			'tables[' . $_REQUEST['category_id'] . '][COLUMNS]',
			_( 'Display Columns' ),
			' type="number" min="1" max="10"'
		);

		if ( $_REQUEST['category_id'] != 1 )
		{
			$extra_fields[] = '<table><tr class="st"><td>' .
				CheckboxInput(
					$RET['ADMIN'],
					'tables[' . $_REQUEST['category_id'] . '][ADMIN]',
					_( 'Administrator' ),
					'',
					$_REQUEST['category_id'] === 'new',
					button( 'check' ),
					button( 'x' )
				) . '</td><td>' .
				CheckboxInput(
					$RET['TEACHER'],
					'tables[' . $_REQUEST['category_id'] . '][TEACHER]',
					_( 'Teacher' ),
					'',
					$_REQUEST['category_id'] === 'new',
					button( 'check' ),
					button( 'x' )
				) . '</td></tr><tr><td>' .
				CheckboxInput(
					$RET['PARENT'],
					'tables[' . $_REQUEST['category_id'] . '][PARENT]',
					_( 'Parent' ),
					'',
					$_REQUEST['category_id'] === 'new',
					button( 'check' ),
					button( 'x' )
				) . '</td><td>' .
				CheckboxInput(
					$RET['NONE'],
					'tables[' . $_REQUEST['category_id'] . '][NONE]',
					_( 'No Access' ),
					'',
					$_REQUEST['category_id'] === 'new',
					button( 'check' ),
					button( 'x' )
				) . '</td></tr></table>' .
				FormatInputTitle(
					_( 'Profiles' ),
					'',
					false,
					''
				);
		}

		if ( $_REQUEST['category_id'] > 2
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
		'staff',
		$title,
		$RET,
		issetVal( $extra_fields, [] )
	);

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM staff_field_categories
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	// DISPLAY THE MENU.
	echo '<div class="st">';

	FieldsMenuOutput( $categories_RET, $_REQUEST['category_id'] );

	echo '</div>';

	// FIELDS.
	if ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !== 'new'
		&& $categories_RET )
	{
		$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SORT_ORDER
			FROM staff_fields
			WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [ 'TYPE' => 'MakeFieldType' ] );

		echo '<div class="st">';

		FieldsMenuOutput( $fields_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}
