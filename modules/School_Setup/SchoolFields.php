<?php
/**
 * School Fields
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'ProgramFunctions/Fields.fnc.php';

DrawHeader( ProgramTitle() );

if ( isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] )
	&& AllowEdit() )
{
	$table = $_REQUEST['table'];

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
				// Update Field.
				if ( $id !== 'new' )
				{
					$sql = 'UPDATE ' . $table . ' SET ';

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= $column . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

					$go = true;
				}
				// New Field.
				else
				{
					$sql = 'INSERT INTO ' . $table . ' ';

					// New Field.
					if ( $table === 'SCHOOL_FIELDS' )
					{
						$_REQUEST['id'] = AddDBField( 'SCHOOLS', 'school_fields_seq', $columns['TYPE'] );

						$fields = 'ID,';

						$values = $_REQUEST['id'] . ",";
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

	unset( $_REQUEST['tables'] );
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( isset( $_REQUEST['id'] )
		&& intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'School Field' ) ) )
		{
			DeleteDBField( 'SCHOOLS', $_REQUEST['id'] );

			$_REQUEST['modfunc'] = false;

			unset( $_REQUEST['id'] );
		}
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( isset( $error ) ) 
	{
		echo ErrorMessage( $error );
	}

	// ADDING & EDITING FORM.
	if ( $_REQUEST['id']
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( DBQuery( "SELECT ID,(SELECT NULL) AS CATEGORY_ID,TITLE,TYPE,
			DEFAULT_SELECTION,SORT_ORDER,REQUIRED
			FROM SCHOOL_FIELDS
			WHERE ID='" . $_REQUEST['id'] . "'" ) );

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
		array(),
		array(
			'text' => _( 'Text' ),
			'numeric' => _( 'Number' ),
			'date' => _( 'Date' ),
			'textarea' => _( 'Long Text' ),
		)
	);

	// DISPLAY THE MENU.
	// FIELDS.
	$fields_RET = DBGet( DBQuery( "SELECT ID,TITLE,TYPE,SORT_ORDER
		FROM SCHOOL_FIELDS
		ORDER BY SORT_ORDER,TITLE" ), array( 'TYPE' => 'MakeFieldType' ) );

	echo '<div class="st">';

	FieldsMenuOutput( $fields_RET, $_REQUEST['id'], false );

	echo '</div>';
}
