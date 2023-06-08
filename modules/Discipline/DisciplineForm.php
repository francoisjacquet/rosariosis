<?php

DrawHeader( ProgramTitle() );

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		// FJ fix SQL bug invalid sort order.

		if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
		{
			if ( $id !== 'new' )
			{
				DBUpdate(
					'discipline_field_usage',
					$columns,
					[ 'ID' => (int) $id ]
				);
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				$insert_columns = [
					// ID is added to CATEGORY_ after INSERT, when we retrieve the ID...
					'COLUMN_NAME' => 'CATEGORY_',
				];

				foreach ( (array) $columns as $column => $value )
				{
					if ( $value && $column != 'SORT_ORDER' && $column != 'SELECT_OPTIONS' )
					{
						$insert_columns[ $column ] = $value;
					}
				}

				$id = DBInsert(
					'discipline_fields',
					$insert_columns,
					'id'
				);

				if ( ! $id )
				{
					continue;
				}

				// Update CATEGORY_ with ID now we have it.
				DBQuery( "UPDATE discipline_fields
					SET COLUMN_NAME='CATEGORY_" . $id . "'
					WHERE ID='" . (int) $id . "'" );

				$create_index = true;

				switch ( $columns['DATA_TYPE'] )
				{
					case 'checkbox':
						$sql_type = 'VARCHAR(1)';
						break;

					case 'text':
					case 'multiple_radio':
					case 'multiple_checkbox':
					case 'select':
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
						$sql_type = 'TEXT';

						if ( $DatabaseType === 'mysql' )
						{
							/**
							 * MySQL LONGTEXT type is limited to 4GB whereas TEXT is limited to 64KB.
							 *
							 * @since 10.0 MySQL use LONGTEXT type for textarea field
							 *
							 * @link https://stackoverflow.com/questions/6766781/maximum-length-for-mysql-type-text
							 */
							$sql_type = 'LONGTEXT';
						}

						$create_index = false; //FJ SQL bugfix index row size exceeds maximum 2712 for index
						break;
				}

				DBQuery( 'ALTER TABLE discipline_referrals ADD ' .
					DBEscapeIdentifier( 'CATEGORY_' . (int) $id ) . ' ' . $sql_type );

				$max_indices_reached = false;

				if ( $DatabaseType === 'mysql' )
				{
					/**
					 * Fix MySQL error 1069 Too many keys specified; max 64 keys allowed
					 * Count columns having an index
					 *
					 * @since 10.3
					 */
					$indices = DBGet( DBQuery( "SHOW INDEX FROM " . DBEscapeIdentifier( 'discipline_referrals' ) ) );

					$max_indices_reached = count( $indices ) >= 64;
				}

				if ( $create_index
					&& ! $max_indices_reached )
				{
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

					DBQuery( 'CREATE INDEX ' . DBEscapeIdentifier( 'discipline_referrals_ind' . (int) $id ) .
						' ON discipline_referrals (' .
						DBEscapeIdentifier( 'CATEGORY_' . (int) $id ) . $key_length . ')' );
				}

				$usage_columns = [];

				foreach ( (array) $columns as $column => $value )
				{
					if ( $value && $column != 'DATA_TYPE' )
					{
						$usage_columns[ $column ] = $value;
					}
				}

				DBInsert(
					'discipline_field_usage',
					[
						'DISCIPLINE_FIELD_ID' => (int) $id,
						'SYEAR' => UserSyear(),
						'SCHOOL_ID' => UserSchool(),
					] + $usage_columns
				);
			}
		}
		else
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Category' ) ) )
	{
		$id = issetVal( $_REQUEST['id'] );

		$delete_sql = "DELETE FROM discipline_fields
			WHERE ID='" . (int) $id . "';";

		$delete_sql .= "DELETE FROM discipline_field_usage
			WHERE DISCIPLINE_FIELD_ID='" . (int) $id . "';";

		DBQuery( $delete_sql );

		$column_name = DBEscapeIdentifier( 'CATEGORY_' . $id );

		DBQuery( "ALTER TABLE discipline_referrals
			DROP COLUMN " . $column_name );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'delete_usage'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Category' ), _( 'Don\'t use' ) ) )
	{
		$id = issetVal( $_REQUEST['id'] );
		DBQuery( "DELETE FROM discipline_field_usage WHERE ID='" . (int) $id . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'add_usage'
	&& AllowEdit() )
{
	DBQuery( "INSERT INTO discipline_field_usage (DISCIPLINE_FIELD_ID,SYEAR,SCHOOL_ID,TITLE,SELECT_OPTIONS,SORT_ORDER)
		SELECT '" . (int) $_REQUEST['id'] . "' AS DISCIPLINE_FIELD_ID,
		'" . UserSyear() . "' AS SYEAR,'" . UserSchool() . "' AS SCHOOL_ID,TITLE,
		NULL AS SELECT_OPTIONS,NULL AS SORT_ORDER
		FROM discipline_fields WHERE ID='" . (int) $_REQUEST['id'] . "'" );

	// Unset modfunc & ID & redirect URL.
	RedirectURL( [ 'modfunc', 'id' ] );
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$referrals_RET = DBGet(
		"SELECT NULL AS REMOVE,du.ID AS USAGE_ID,df.ID,COALESCE(du.TITLE,df.TITLE) AS TITLE,
		du.SORT_ORDER,df.DATA_TYPE,du.SELECT_OPTIONS
		FROM discipline_fields df LEFT
		OUTER JOIN discipline_field_usage du
		ON (du.DISCIPLINE_FIELD_ID=df.ID
			AND du.SYEAR='" . UserSyear() . "'
			AND du.SCHOOL_ID='" . UserSchool() . "')
		ORDER BY du.SORT_ORDER IS NULL,du.SORT_ORDER,du.ID",
		[
			'REMOVE' => '_makeRemove',
			'TITLE' => '_makeTextInput',
			'SORT_ORDER' => '_makeTextInput',
			'DATA_TYPE' => '_makeType',
			'SELECT_OPTIONS' => '_makeTextAreaInput',
		]
	);

	foreach ( (array) $referrals_RET as $key => $item )
	{
		if ( ! $item['USAGE_ID'] )
		{
			// $referrals_RET[$key]['row_color'] = 'CCCCCC';
		}
	}

	if ( ! empty( $referrals_RET ) )
	{
		$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ];
	}
	else
	{
		$columns = [];
	}

	$columns += [
		'TITLE' => _( 'Title' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'DATA_TYPE' => _( 'Data Type' ),
		'SELECT_OPTIONS' => _( 'Pull-Down' ) . '/' . _( 'Select Multiple from Options' ) . '/' .
			_( 'Select One from Options' ),
	];

	$link['add']['html'] = [
		'REMOVE' => button( 'add' ),
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'SELECT_OPTIONS' => _makeTextAreaInput( '', 'SELECT_OPTIONS' ),
		'DATA_TYPE' => _makeType( '', 'DATA_TYPE' ),
	];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );

	ListOutput( $referrals_RET, $columns, 'Referral Form Category', 'Referral Form Categories', $link );
	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeType( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['USAGE_ID'] ) )
	{
		$id = $THIS_RET['USAGE_ID'];
	}
	else
	{
		$id = 'new';
	}

	$new_options = [
		'select' => _( 'Pull-Down' ),
		'multiple_radio' => _( 'Select One from Options' ),
		'multiple_checkbox' => _( 'Select Multiple from Options' ),
		'text' => _( 'Text' ),
		'textarea' => _( 'Long Text' ),
		'checkbox' => _( 'Checkbox' ),
		'numeric' => _( 'Number' ),
		'date' => _( 'Date' ),
	];

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		return $new_options[$value];
	}
	else
	{
		return SelectInput( $value, 'values[new][' . $name . ']', '', $new_options, false );
	}
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['USAGE_ID'] ) )
	{
		$id = $THIS_RET['USAGE_ID'];
	}
	elseif ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = 'usage';
	}
	else
	{
		$id = 'new';
	}

	$extra = 'maxlength=100';

	if ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=2';
	}
	elseif ( $id !== 'new' )
	{
		$extra .= ' required';
	}

	$comment = '';

	if ( $name === 'SORT_ORDER' )
	{
		$extra .= ' type="number" min="-9999" max="9999"';

		$comment = '<!-- ' . $value . ' -->';
	}

	if ( $id === 'usage' )
	{
		return $value;
	}
	else
	{
		return $comment .
		TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
	}
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeTextAreaInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['USAGE_ID'] ) )
	{
		$id = $THIS_RET['USAGE_ID'];
	}
	elseif ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = 'usage';
	}
	else
	{
		$id = 'new';
	}

	if ( $id === 'usage' )
	{
		return $value;
	}
	elseif ( $id === 'new'
		|| $THIS_RET['DATA_TYPE'] === 'multiple_checkbox'
		|| $THIS_RET['DATA_TYPE'] === 'multiple_radio'
		|| $THIS_RET['DATA_TYPE'] === 'select' )
	{
		$return = TextAreaInput( $value, 'values[' . $id . '][' . $name . ']', '', '', $id !== 'new', 'text' );

		//FJ responsive rt td too large
		$return = '<div id="divTextAreaContent' . $id . '" class="rt2colorBox">' .
			$return .
			'</div>';

		return $return;
	}
	else
	{
		return _( 'N/A' );
	}
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _makeRemove( $value, $column )
{
	global $THIS_RET;

	$return = '';

	if ( AllowEdit() )
	{
		if ( ! empty( $THIS_RET['USAGE_ID'] ) )
		{
			$return = button(
				'remove', _( 'Don\'t use' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete_usage&id=' . $THIS_RET['USAGE_ID'] )
			);

			$return .= '<br class="rbr"> ' . button(
				'remove',
				_( 'Delete' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete&id=' . $THIS_RET['ID'] )
			);
		}
		else
		{
			$return = button(
				'add',
				_( 'Use at this school' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=add_usage&id=' . $THIS_RET['ID'] )
			);
		}
	}

	return $return;
}
