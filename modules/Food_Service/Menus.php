<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		if ( ! empty( $_REQUEST['tab_id'] ) )
		{
			foreach ( (array) $_REQUEST['values'] as $id => $columns )
			{
				// FJ fix SQL bug invalid sort order.

				if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
				{
					$table = $_REQUEST['tab_id'] !== 'new' ? 'food_service_categories' : 'food_service_menus';

					if ( isset( $columns['TITLE'] ) )
					{
						$title_exists = DBGetOne( "SELECT 1
							FROM " . DBEscapeIdentifier( $table ) . "
							WHERE TITLE='" . $columns['TITLE'] . "'" );

						if ( $title_exists )
						{
							// Fix SQL error duplicate key value violates unique constraint "food_service_menus_title"
							$columns['TITLE'] .= ' (2)';
						}
					}

					if ( $id !== 'new' )
					{
						$where_columns = $_REQUEST['tab_id'] !== 'new' ?
							[ 'CATEGORY_ID' => (int) $id ] : [ 'MENU_ID' => (int) $id ];

						DBUpdate(
							$table,
							$columns,
							$where_columns
						);
					}

					// New: check for Title
					elseif ( $columns['TITLE'] )
					{
						$insert_columns = [ 'SCHOOL_ID' => UserSchool() ];

						if ( $_REQUEST['tab_id'] !== 'new' )
						{
							$insert_columns += [ 'MENU_ID' => (int) $_REQUEST['tab_id'] ];
						}

						DBInsert(
							$table,
							$insert_columns + $columns
						);
					}
				}
				else
				{
					$error[] = _( 'Please enter a valid Sort Order.' );
				}
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		if ( DeletePrompt( _( 'Category' ) ) )
		{
			DBQuery( "UPDATE food_service_menu_items
				SET CATEGORY_ID=NULL
				WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'" );

			DBQuery( "DELETE FROM food_service_categories
				WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'" );

			// Unset modfunc & category ID & redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
	elseif ( DeletePrompt( _( 'Meal' ) ) )
	{
		$delete_sql = "DELETE FROM food_service_menu_items
			WHERE MENU_ID='" . (int) $_REQUEST['menu_id'] . "';";

		$delete_sql .= "DELETE FROM food_service_categories
			WHERE MENU_ID='" . (int) $_REQUEST['menu_id'] . "';";

		$delete_sql .= "DELETE FROM food_service_menus
			WHERE MENU_ID='" . (int) $_REQUEST['menu_id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & menu ID & redirect URL.
		RedirectURL( [ 'modfunc', 'menu_id' ] );
	}
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$menus_RET = DBGet( 'SELECT MENU_ID,TITLE FROM food_service_menus WHERE SCHOOL_ID=\'' . UserSchool() . '\' ORDER BY SORT_ORDER IS NULL,SORT_ORDER', [], [ 'MENU_ID' ] );

	if ( ! empty( $_REQUEST['tab_id'] ) )
	{
		if ( $_REQUEST['tab_id'] !== 'new' )
		{
			if ( $menus_RET[$_REQUEST['tab_id']] )
			{
				$_SESSION['FSA_menu_id'] = $_REQUEST['tab_id'];
			}
			elseif ( ! empty( $menus_RET ) )
			{
				$_REQUEST['tab_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
			}
			else
			{
				$_REQUEST['tab_id'] = 'new';
			}
		}
	}
	else
	{
		if ( ! empty( $_SESSION['FSA_menu_id'] ) )
		{
			if ( $menus_RET[$_SESSION['FSA_menu_id']] )
			{
				$_REQUEST['tab_id'] = $_SESSION['FSA_menu_id'];
			}
			elseif ( ! empty( $menus_RET ) )
			{
				$_REQUEST['tab_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
			}
			else
			{
				$_REQUEST['tab_id'] = 'new';
			}
		}
		elseif ( ! empty( $menus_RET ) )
		{
			$_REQUEST['tab_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			$_REQUEST['tab_id'] = 'new';
		}
	}

	$tabs = [];

	foreach ( (array) $menus_RET as $id => $menu )
	{
		$tabs[] = [ 'title' => $menu[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $id ];
	}

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		$sql = "SELECT CATEGORY_ID,TITLE,SORT_ORDER
		FROM food_service_categories
		WHERE MENU_ID='" . (int) $_REQUEST['tab_id'] . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER";

		$functions = [
			'TITLE' => '_makeTextInput',
			'SORT_ORDER' => '_makeTextInput',
		];

		$LO_columns = [
			'TITLE' => sprintf( _( '%s Category' ), $menus_RET[$_REQUEST['tab_id']][1]['TITLE'] ),
			'SORT_ORDER' => _( 'Sort Order' ),
		];

		$link['add']['html'] = [
			'TITLE' => _makeTextInput( '', 'TITLE' ),
			'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&tab_id=' . $_REQUEST['tab_id'] .
			'&category_id=' . issetVal( $_REQUEST['category_id'], '' );

		$link['remove']['variables'] = [ 'category_id' => 'CATEGORY_ID' ];

		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = [
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		];

		$singular = sprintf( _( '%s Category' ), $menus_RET[$_REQUEST['tab_id']][1]['TITLE'] );

		$plural = sprintf( _( '%s Categories' ), $menus_RET[$_REQUEST['tab_id']][1]['TITLE'] );
	}
	else
	{
		$sql = "SELECT MENU_ID,TITLE,SORT_ORDER
		FROM food_service_menus
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER";

		$functions = [
			'TITLE' => '_makeTextInput',
			'SORT_ORDER' => '_makeTextInput',
		];

		$LO_columns = [
			'TITLE' => _( 'Meal' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		];

		$link['add']['html'] = [
			'TITLE' => _makeTextInput( '', 'TITLE' ),
			'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=new';

		$link['remove']['variables'] = [ 'menu_id' => 'MENU_ID' ];

		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = [ 'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		];
	}

	$LO_ret = DBGet( $sql, $functions );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&tab_id=' . $_REQUEST['tab_id']  ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );
	echo '<br />';

	$extra = [ 'save' => false, 'search' => false,
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $_REQUEST['tab_id'] ) ];

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		ListOutput( $LO_ret, $LO_columns, $singular, $plural, $link, [], $extra );
	}
	else
	{
		ListOutput( $LO_ret, $LO_columns, 'Meal', 'Meals', $link, [], $extra );
	}

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

	if ( ! empty( $THIS_RET['CATEGORY_ID'] ) )
	{
		$id = $THIS_RET['CATEGORY_ID'];
	}
	elseif ( ! empty( $THIS_RET['MENU_ID'] ) )
	{
		$id = $THIS_RET['MENU_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name === 'TITLE' )
	{
		$extra = 'size=20 maxlength=25';

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
	}
	elseif ( $name === 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	else
	{
		$extra = 'size=8 maxlength=8';
	}

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}
