<?php
/**
 * School Fields
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'ProgramFunctions/Fields.fnc.php';

DrawHeader( ProgramTitle() );

$_REQUEST['id'] = issetVal( $_REQUEST['id'], false );

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

				// Update Field.
				if ( $id !== 'new' )
				{
					$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

					$go = true;
				}
				// New Field.
				else
				{
					$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

					// New Field.
					if ( $table === 'SCHOOL_FIELDS' )
					{
						$_REQUEST['id'] = AddDBField( 'SCHOOLS', 'school_fields_id_seq', $columns['TYPE'] );

						$fields = 'ID,';

						$values = $_REQUEST['id'] . ",";
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

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'School Field' ) ) )
		{
			DeleteDBField( 'SCHOOLS', $_REQUEST['id'] );

			$_REQUEST['modfunc'] = false;

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
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
		$RET = DBGet( "SELECT ID,(SELECT NULL) AS CATEGORY_ID,TITLE,TYPE,
			SELECT_OPTIONS,DEFAULT_SELECTION,SORT_ORDER,REQUIRED
			FROM SCHOOL_FIELDS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['id'] === 'new' )
	{
		$title = _( 'New School Field' );

		$RET['ID'] = 'new';
	}

	echo GetFieldsForm(
		'SCHOOL',
		$title,
		$RET,
		[]
	);

	// DISPLAY THE MENU.
	// FIELDS.
	$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SORT_ORDER
		FROM SCHOOL_FIELDS
		ORDER BY SORT_ORDER,TITLE", [ 'TYPE' => 'MakeFieldType' ] );

	echo '<div class="st">';

	FieldsMenuOutput( $fields_RET, $_REQUEST['id'], false );

	echo '</div>';
}
