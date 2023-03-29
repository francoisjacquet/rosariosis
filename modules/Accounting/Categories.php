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
			// FJ fix SQL bug invalid sort order.

			if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
			{

				if ( $id !== 'new' )
				{
					
					$sql = "UPDATE accounting_categories SET ";
					
					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
					DBQuery( $sql );
				}

				// New: check for Title & Short Name.
				elseif ( $columns['TITLE']
					&& $columns['SHORT_NAME'] )
				{
					$sql = "INSERT INTO accounting_categories ";
					$fields = 'SCHOOL_ID,SYEAR,';
					$values = "'" . UserSchool() . "','" . UserSyear() . "',";

					$go = false;

					foreach ( (array) $columns as $column => $value )
					{
						if ( isset( $value ) && $value != '' )
						{
							$fields .= DBEscapeIdentifier( $column ) . ',';
							$values .= "'" . $value . "',";
							$go = true;
						}
					}

					$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

					if ( $go )
					{
						DBQuery( $sql );
					}
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
	if ( DeletePrompt( _( 'Accounting' ) .' ' . _( 'Category' ) ) )
	{
		DBQuery( "DELETE FROM accounting_categories WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$sql = "SELECT ID,TITLE,SHORT_NAME,TYPE,SORT_ORDER,
	(SELECT 1
		FROM accounting_incomes ai,accounting_payments ap
		WHERE ai.CATEGORY_ID=ac.ID
		OR ap.CATEGORY_ID=ac.ID
		LIMIT 1) AS REMOVE
	FROM accounting_categories ac
	WHERE ac.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE";
	
	$functions = [];
	
	if ( AllowEdit() )
	{
		$functions['REMOVE'] = '_makeRemoveButton';
	}
	
	$functions += [
		'TITLE' => '_makeTextInput',
		'SHORT_NAME' => '_makeTextInput',
		'SORT_ORDER' => '_makeTextInput',
		'TYPE' => '_makeSelectInput',
	];
	
	$LO_columns = [];
	
	if ( empty( $_REQUEST['LO_save'] ) 
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


	$link['add']['html'] = [];
	
	if ( AllowEdit() )
	{
		$link['add']['html']['REMOVE'] = _makeRemoveButton( '', 'REMOVE' );
	}

	$link['add']['html'] += [
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'TYPE' => _makeSelectInput( '', 'TYPE' ),
	];

	$LO_RET = DBGet( $sql, $functions );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );
	echo '<br />';
	ListOutput( $LO_RET, $LO_columns, '.', '.', $link, []);

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	$extra = '';

	if ( $name === 'SORT_ORDER' )
	{
		$extra .= ' type="number" min="-9999" max="9999"';
	}
	elseif ( $name === 'SHORT_NAME' )
	{
		$extra .= 'size=4 maxlength=6';
	}
	elseif ( $name === 'TITLE' )
	{
		$extra .= 'maxlength=100';
	}

	if ( $id !== 'new'
		&& ( $name === 'TITLE'
			|| $name === 'SHORT_NAME' ) )
	{
		$extra .= ' required';
	}

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

/**
 * @param $value
 * @param $name
 */
function _makeSelectInput( $value, $name)
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}
	
	if ( $name === 'TYPE' )
	{
		$options = [
			'0' => _( 'Incomes' ) .' & '. _( 'Expenses' ),
			'1' => _( 'Incomes' ),
			'2' => _( 'Expenses' ),
		];
	}
	
	if ( $id !== 'new' && $name === 'TYPE' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$options,
			false,
			disabled
		);
	}
	else {
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$options,
			false
		);
	}
}

/**
 * @param $value
 * @param $name
 */
function _makeCheckBoxInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	return CheckBoxInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		'',
		( $id === 'new' )
	);
}

/**
 * @param $value
 * @param $name
 */
function _makeRemoveButton( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		return button( 'add' );
	}

	if ( $value )
	{
		// Do NOT remove School Period as existing Course Periods use it.
		return '';
	}

	$button_link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' .
		$THIS_RET['ID'];

	return button( 'remove', '', '"' . URLEscape( $button_link ) . '"' );
}

