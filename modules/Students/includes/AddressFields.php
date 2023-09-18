<?php
/**
 * Address Fields
 *
 * @package RosarioSIS
 * @subpackage modules
 */

if ( isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] )
	&& AllowEdit() )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'address_field_categories', 'address_fields' ] ) )
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
					if ( $table === 'address_fields' )
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
						if ( $table === 'address_fields' )
						{
							AddDBField( 'address', $id, $columns['TYPE'] );

							$_REQUEST['id'] = $id;
						}
						elseif ( $table === 'address_field_categories' )
						{
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
		if ( DeletePrompt( _( 'Address Field' ) ) )
		{
			DeleteDBField( 'address', $_REQUEST['id'] );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& intval( $_REQUEST['category_id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'Address Field Category' ) . ' ' .
				_( 'and all fields in the category' ) ) )
		{
			DeleteDBFieldCategory( 'address', $_REQUEST['category_id'] );

			// Unset modfunc & category ID & redirect URL.
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
				FROM address_field_categories
				WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
			FROM address_fields
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['CATEGORY_TITLE'] ) . ' - ' . ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !== 'new'
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,RESIDENCE,MAILING,BUS,SORT_ORDER
			FROM address_field_categories
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['id'] === 'new' )
	{
		$title = _( 'New Address Field' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = issetVal( $_REQUEST['category_id'] );
	}
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = _( 'New Address Field Category' );

		$RET['CATEGORY_ID'] = 'new';
	}

	if ( $_REQUEST['category_id']
		&& ! $_REQUEST['id'] )
	{
		$extra_fields = [
			'<table class="width-100p cellspacing-0"><tr class="st"><td>' .
			CheckboxInput(
				issetVal( $RET['RESIDENCE'], '' ),
				'tables[' . $_REQUEST['category_id'] . '][RESIDENCE]',
				_( 'Residence' ),
				'',
				$_REQUEST['category_id'] === 'new',
				button( 'check' ),
				button( 'x' )
			) . '</td><td>' .
			CheckboxInput(
				issetVal( $RET['MAILING'], '' ),
				'tables[' . $_REQUEST['category_id'] . '][MAILING]',
				_( 'Mailing' ),
				'',
				$_REQUEST['category_id'] === 'new',
				button( 'check' ),
				button( 'x' )
			) . '</td><td>' .
			CheckboxInput(
				issetVal( $RET['BUS'], '' ),
				'tables[' . $_REQUEST['category_id'] . '][BUS]',
				_( 'Bus' ),
				'',
				$_REQUEST['category_id'] === 'new',
				button( 'check' ),
				button( 'x' )
			) . '</td></tr></table>' .
			FormatInputTitle(
				_( 'Note: All unchecked means applies to all addresses' ),
				'',
				false,
				''
			)
		];
	}

	echo GetFieldsForm(
		'address',
		$title,
		$RET,
		issetVal( $extra_fields, [] )
	);

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM address_field_categories
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
			FROM address_fields
			WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [ 'TYPE' => 'MakeFieldType' ] );

		echo '<div class="st">';

		FieldsMenuOutput( $fields_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}
