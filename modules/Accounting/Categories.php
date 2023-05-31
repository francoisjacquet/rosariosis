<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] ) )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			// Fix SQL bug invalid sort order.
			if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
			{
				if ( $id !== 'new' )
				{
					DBUpdate(
						'accounting_categories',
						$columns,
						[ 'ID' => (int) $id ]
					);
				}

				// New: check for Title & Short Name.
				elseif ( $columns['TITLE']
					&& $columns['SHORT_NAME'] )
				{
					DBInsert(
						'accounting_categories',
						[ 'SCHOOL_ID' => UserSchool() ] + $columns
					);
				}
			}
			else
			{
				$error[] = _( 'Please enter a valid Sort Order.' );
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( sprintf( _( '%s Category' ), _( 'Accounting' ) ) ) )
	{
		DBQuery( "DELETE FROM accounting_categories
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// Fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$functions = [
		'REMOVE' => '_makeRemoveButton',
		'TITLE' => '_makeTextInput',
		'SHORT_NAME' => '_makeTextInput',
		'SORT_ORDER' => '_makeTextInput',
		'TYPE' => '_makeSelectInput',
	];
	
	$categories_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME,TYPE,SORT_ORDER,
	(SELECT 1
		FROM accounting_incomes ai,accounting_payments ap
		WHERE ai.CATEGORY_ID=ac.ID
		OR ap.CATEGORY_ID=ac.ID
		LIMIT 1) AS REMOVE
	FROM accounting_categories ac
	WHERE ac.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER,SHORT_NAME,TITLE", $functions );

	$LO_columns = [];

	if ( ! empty( $categories_RET )
		&& empty( $_REQUEST['LO_save'] )
		&& AllowEdit() )
	{
		// Do not Export Delete column.
		$LO_columns['REMOVE'] = '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>';
	}

	$LO_columns += [
		'TITLE' => _( 'Title' ),
		'SHORT_NAME' => _( 'Short Name' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'TYPE' => _( 'Type' ),
	];

	$link['add']['html'] = [
		'REMOVE' => button( 'add' ),
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'TYPE' => _makeSelectInput( '', 'TYPE' ),
	];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );

	ListOutput(
		$categories_RET,
		$LO_columns,
		'Category',
		'Categories',
		$link
	);

	echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
}

/**
 * @param $value
 * @param $column
 */
function _makeTextInput( $value, $column )
{
	global $THIS_RET;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	$extra = '';

	if ( $column === 'SORT_ORDER' )
	{
		$extra .= 'type="number" min="-9999" max="9999"';
	}
	elseif ( $column === 'SHORT_NAME' )
	{
		$extra .= 'size=4 maxlength=10';
	}
	elseif ( $column === 'TITLE' )
	{
		$extra .= 'maxlength=100';
	}

	if ( $id !== 'new'
		&& ( $column === 'TITLE'
			|| $column === 'SHORT_NAME' ) )
	{
		$extra .= ' required';
	}

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra );
}

/**
 * @param $value
 * @param $column
 */
function _makeSelectInput( $value, $column )
{
	global $THIS_RET;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	$options = [
		'common' => _( 'Incomes' ) . ' & ' . _( 'Expenses' ),
		'incomes' => _( 'Incomes' ),
		'expenses' => _( 'Expenses' ),
	];

	if ( $id !== 'new' )
	{
		return $options[ $value ];
	}

	return SelectInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$options,
		false
	);
}

/**
 * @param $value
 * @param $column
 */
function _makeRemoveButton( $value, $column )
{
	global $THIS_RET;

	if ( $value )
	{
		// Do NOT remove Category as existing Incomes / Expenses belong to it.
		return '';
	}

	$button_link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' .
		$THIS_RET['ID'];

	return button( 'remove', '', URLEscape( $button_link ) );
}

