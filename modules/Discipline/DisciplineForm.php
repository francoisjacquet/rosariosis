<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['values']
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
				$sql = "UPDATE DISCIPLINE_FIELD_USAGE SET ";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
				$go = true;
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				$id = DBSeqNextID( 'DISCIPLINE_FIELDS_SEQ' );
				$sql = "INSERT INTO DISCIPLINE_FIELDS ";

				$fields = "ID,COLUMN_NAME,";
				$values = "'" . $id . "','CATEGORY_" . $id . "',";

				$go = 0;

				foreach ( (array) $columns as $column => $value )
				{
					if ( $value && $column != 'SORT_ORDER' && $column != 'SELECT_OPTIONS' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';
						$values .= "'" . $value . "',";
						$go = true;
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				$usage_sql = "INSERT INTO DISCIPLINE_FIELD_USAGE ";

				$fields = "ID,DISCIPLINE_FIELD_ID,SYEAR,SCHOOL_ID,";
				$values = db_seq_nextval( 'DISCIPLINE_FIELD_USAGE_SEQ' ) . ",'" . $id . "','" . UserSyear() . "','" . UserSchool() . "',";

				foreach ( (array) $columns as $column => $value )
				{
					if ( $value && $column != 'DATA_TYPE' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';
						$values .= "'" . $value . "',";
					}
				}

				$usage_sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				$create_index = true;

				switch ( $columns['DATA_TYPE'] )
				{
					case 'checkbox':
						DBQuery( "ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_" . $id . " VARCHAR(1)" );
						break;

					case 'text':
					case 'multiple_radio':
					case 'multiple_checkbox':
					case 'select':
						DBQuery( "ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_" . $id . " TEXT" );
						break;

					case 'numeric':
						DBQuery( "ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_" . $id . " NUMERIC(20,2)" );
						break;

					case 'date':
						DBQuery( "ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_" . $id . " DATE" );
						break;

					case 'textarea':
						DBQuery( "ALTER TABLE DISCIPLINE_REFERRALS ADD CATEGORY_" . $id . " TEXT" );
						$create_index = false; //FJ SQL bugfix index row size exceeds maximum 2712 for index
						break;
				}

				if ( $create_index )
				{
					DBQuery( "CREATE INDEX DISCIPLINE_REFERRALS_IND" . $id . "
						ON DISCIPLINE_REFERRALS (CATEGORY_" . $id . ")" );
				}

				DBQuery( $usage_sql );
			}

			if ( $go )
			{
				DBQuery( $sql );
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
		$id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : null;

		$delete_sql = "DELETE FROM DISCIPLINE_FIELDS
			WHERE ID='" . $id . "';";

		$delete_sql .= "DELETE FROM DISCIPLINE_FIELD_USAGE
			WHERE DISCIPLINE_FIELD_ID='" . $id . "';";

		DBQuery( $delete_sql );

		$column_name = DBEscapeIdentifier( 'CATEGORY_' . $id );

		DBQuery( "ALTER TABLE DISCIPLINE_REFERRALS
			DROP COLUMN " . $column_name );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'delete_usage'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Category' ), _( 'Don\'t use' ) ) )
	{
		$id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : null;
		DBQuery( "DELETE FROM DISCIPLINE_FIELD_USAGE WHERE ID='" . $id . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'add_usage'
	&& AllowEdit() )
{
	DBQuery( "INSERT INTO DISCIPLINE_FIELD_USAGE (ID,DISCIPLINE_FIELD_ID,SYEAR,SCHOOL_ID,TITLE,SELECT_OPTIONS,SORT_ORDER) SELECT " . db_seq_nextval( 'DISCIPLINE_FIELD_USAGE_SEQ' ) . " AS ID,'" . $_REQUEST['id'] . "' AS DISCIPLINE_FIELD_ID,
		'" . UserSyear() . "' AS SYEAR,'" . UserSchool() . "' AS SCHOOL_ID,TITLE,
		NULL AS SELECT_OPTIONS,NULL AS SORT_ORDER
		FROM DISCIPLINE_FIELDS WHERE ID='" . $_REQUEST['id'] . "'" );

	// Unset modfunc & ID & redirect URL.
	RedirectURL( array( 'modfunc', 'id' ) );
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$referrals_RET = DBGet(
		"SELECT NULL AS REMOVE,du.ID AS USAGE_ID,df.ID,COALESCE(du.TITLE,df.TITLE) AS TITLE,
		du.SORT_ORDER,df.DATA_TYPE,du.SELECT_OPTIONS
		FROM DISCIPLINE_FIELDS df LEFT
		OUTER JOIN DISCIPLINE_FIELD_USAGE du
		ON (du.DISCIPLINE_FIELD_ID=df.ID
			AND du.SYEAR='" . UserSyear() . "'
			AND du.SCHOOL_ID='" . UserSchool() . "')
		ORDER BY du.SORT_ORDER,du.ID",
		array(
			'REMOVE' => '_makeRemove',
			'TITLE' => '_makeTextInput',
			'SORT_ORDER' => '_makeTextInput',
			'DATA_TYPE' => '_makeType',
			'SELECT_OPTIONS' => '_makeTextAreaInput',
		)
	);

	foreach ( (array) $referrals_RET as $key => $item )
	{
		if ( ! $item['USAGE_ID'] )
		{
			$referrals_RET[$key]['row_color'] = 'CCCCCC';
		}
	}

	if ( ! empty( $referrals_RET ) )
	{
		$columns = array( 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' );
	}
	else
	{
		$columns = array();
	}

	$columns += array(
		'TITLE' => _( 'Title' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'DATA_TYPE' => _( 'Data Type' ),
		'SELECT_OPTIONS' => _( 'Pull-Down' ) . '/' . _( 'Select Multiple from Options' ) . '/' .
			_( 'Select One from Options' ),
	);

	$link['add']['html'] = array(
		'REMOVE' => button( 'add' ),
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'SELECT_OPTIONS' => _makeTextAreaInput( '', 'SELECT_OPTIONS' ),
		'DATA_TYPE' => _makeType( '', 'DATA_TYPE' ),
	);

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

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

	if ( $THIS_RET['USAGE_ID'] )
	{
		$id = $THIS_RET['USAGE_ID'];
	}
	else
	{
		$id = 'new';
	}

	$new_options = array(
		'checkbox' => _( 'Checkbox' ),
		'text' => _( 'Text' ),
		'multiple_checkbox' => _( 'Select Multiple from Options' ),
		'multiple_radio' => _( 'Select One from Options' ),
		'select' => _( 'Pull-Down' ),
		'date' => _( 'Date' ),
		'numeric' => _( 'Number' ),
		'textarea' => _( 'Long Text' ),
	);

	if ( $THIS_RET['ID'] )
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

	if ( $THIS_RET['USAGE_ID'] )
	{
		$id = $THIS_RET['USAGE_ID'];
	}
	elseif ( $THIS_RET['ID'] )
	{
		$id = 'usage';
	}
	else
	{
		$id = 'new';
	}

	if ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=2';
	}
	elseif ( $id !== 'new' )
	{
		$extra = 'required';
	}

	$comment = '';

	if ( $name === 'SORT_ORDER' )
	{
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

	if ( $THIS_RET['USAGE_ID'] )
	{
		$id = $THIS_RET['USAGE_ID'];
	}
	elseif ( $THIS_RET['ID'] )
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
		if ( $THIS_RET['USAGE_ID'] )
		{
			$return = button(
				'remove', _( 'Don\'t use' ),
				'"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete_usage&id=' . $THIS_RET['USAGE_ID'] . '"'
			);

			$return .= ' ' . button(
				'remove',
				_( 'Delete' ),
				'"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete&id=' . $THIS_RET['ID'] . '"'
			);
		}
		else
		{
			$return = button(
				'add',
				_( 'Use at this school' ),
				'"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=add_usage&id=' . $THIS_RET['ID'] . '"'
			);
		}
	}

	return $return;
}
