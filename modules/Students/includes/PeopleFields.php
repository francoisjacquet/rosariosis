<?php
/**
 * People Fields
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
					if ( $table === 'PEOPLE_FIELDS' )
					{
						if ( isset( $columns['CATEGORY_ID'] ) )
						{
							$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

							unset( $columns['CATEGORY_ID'] );
						}

						$_REQUEST['id'] = AddDBField( 'PEOPLE', 'people_fields_id_seq', $columns['TYPE'] );

						$fields = 'ID,CATEGORY_ID,';

						$values = $_REQUEST['id'] . ",'" . $_REQUEST['category_id'] . "',";
					}
					// New Category.
					elseif ( $table === 'PEOPLE_FIELD_CATEGORIES' )
					{
						$id = DBSeqNextID( 'people_field_categories_id_seq' );

						$fields = "ID,";

						$values = $id . ",";

						$_REQUEST['category_id'] = $id;
					}

					$go = false;

					foreach ( (array) $columns as $column => $value )
					{
						if ( ! empty( $value )
							|| $value == '0' )
						{
							$fields .= DBEscapeIdentifier( $column ) . ',';

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
		if ( DeletePrompt( _( 'Contact Field' ) ) )
		{
			DeleteDBField( 'PEOPLE', $_REQUEST['id'] );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& intval( $_REQUEST['category_id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'Contact Field Category' ) . ' ' .
				_( 'and all fields in the category' ) ) )
		{
			DeleteDBFieldCategory( 'PEOPLE', $_REQUEST['category_id'] );

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
				FROM PEOPLE_FIELD_CATEGORIES
				WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
			FROM PEOPLE_FIELDS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['CATEGORY_TITLE'] ) . ' - ' . ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !== 'new'
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,CUSTODY,EMERGENCY,SORT_ORDER
			FROM PEOPLE_FIELD_CATEGORIES
			WHERE ID='" . $_REQUEST['category_id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['id'] === 'new' )
	{
		$title = _( 'New Contact Field' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = issetVal( $_REQUEST['category_id'] );
	}
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = _( 'New Contact Field Category' );

		$RET['CATEGORY_ID'] = 'new';
	}

	if ( $_REQUEST['category_id']
		&& ! $_REQUEST['id'] )
	{
		$extra_fields = [
			'<table class="width-100p cellspacing-0"><tr class="st"><td>' .
			CheckboxInput(
				issetVal( $RET['CUSTODY'], '' ),
				'tables[' . $_REQUEST['category_id'] . '][CUSTODY]',
				_( 'Custody' ),
				'',
				$_REQUEST['category_id'] === 'new',
				button( 'check' ),
				button( 'x' )
			) . '</td><td>' .
			CheckboxInput(
				issetVal( $RET['EMERGENCY'], '' ),
				'tables[' . $_REQUEST['category_id'] . '][EMERGENCY]',
				_( 'Emergency' ),
				'',
				$_REQUEST['category_id'] === 'new',
				button( 'check' ),
				button( 'x' )
			) . '</td></tr></table>' .
			FormatInputTitle(
				_( 'Note: All unchecked means applies to all contacts' ),
				'',
				false,
				''
			)
		];
	}

	echo GetFieldsForm(
		'PEOPLE',
		$title,
		$RET,
		issetVal( $extra_fields, [] )
	);

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM PEOPLE_FIELD_CATEGORIES
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
			FROM PEOPLE_FIELDS
			WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'
			ORDER BY SORT_ORDER,TITLE", [ 'TYPE' => 'MakeFieldType' ] );

		echo '<div class="st">';

		FieldsMenuOutput( $fields_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}
