<?php
require_once 'modules/Food_Service/includes/FS_Icons.inc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'upload'
	&& AllowEdit() )
{
	// @since 8.9 Food Service icon upload.
	$icon_path = ImageUpload(
		'upload',
		[ 'witdh' => 256, 'height' => 256 ],
		$FS_IconsPath,
		[ '.jpg', '.jpeg', '.png', '.gif' ]
	);

	if ( $icon_path )
	{
		$note[] = button( 'check' ) . '&nbsp;' . _( 'Icon successfully uploaded.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&&  ! empty( $_REQUEST['tab_id'] )
		&& AllowEdit() )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			// FJ fix SQL bug invalid sort order.

			if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
			{
				if ( $_REQUEST['tab_id'] === 'new'
					&& ! empty( $columns['SHORT_NAME'] ) )
				{
					// Fix SQL error when SHORT_NAME already in use.
					$short_name_exists = DBGetOne( "SELECT 1 FROM food_service_items
						WHERE SCHOOL_ID='" . UserSchool() . "'
						AND SHORT_NAME='" . $columns['SHORT_NAME'] . "'" );

					if ( $short_name_exists )
					{
						$columns['SHORT_NAME'] = '';
					}
				}

				$table = $_REQUEST['tab_id'] !== 'new' ? 'food_service_menu_items' : 'food_service_items';

				if ( $id !== 'new' )
				{
					// Fix SQL bug PRICE_STAFF & PRICE not null
					// Fix SQL bug PRICE_FREE & PRICE_REDUCED numeric
					if ( $_REQUEST['tab_id'] !== 'new'
						|| ( ( empty( $columns['PRICE_FREE'] ) || is_numeric( $columns['PRICE_FREE'] ) )
							&& ( empty( $columns['PRICE_REDUCED'] ) || is_numeric( $columns['PRICE_REDUCED'] ) )
							&& ( empty( $columns['PRICE_STAFF'] ) || is_numeric( $columns['PRICE_STAFF'] ) )
							&& ( empty( $columns['PRICE'] ) || is_numeric( $columns['PRICE'] ) ) ) )
					{
						$where_columns = $_REQUEST['tab_id'] !== 'new' ?
							[ 'MENU_ITEM_ID' => (int) $id ] : [ 'ITEM_ID' => (int) $id ];

						DBUpdate(
							$table,
							$columns,
							$where_columns
						);
					}
					else
					{
						$error[] = _( 'Please enter valid Numeric data.' );
					}
				}
				elseif ( ( $_REQUEST['tab_id'] !== 'new'
						&& ! empty( $columns['ITEM_ID'] ) )
					|| ( ! empty( $columns['DESCRIPTION'] )
						&& ! empty( $columns['SHORT_NAME'] ) ) )
				{
					if ( $_REQUEST['tab_id'] === 'new'
						&& ( ( ! empty( $columns['PRICE_FREE'] ) && ! is_numeric( $columns['PRICE_FREE'] ) )
							|| ( ! empty( $columns['PRICE_REDUCED'] ) && ! is_numeric( $columns['PRICE_REDUCED'] ) )
							|| ! is_numeric( $columns['PRICE_STAFF'] ) || ! is_numeric( $columns['PRICE'] ) ) )
					{
						// Fix SQL bug PRICE_STAFF & PRICE not null
						// Fix SQL bug PRICE_FREE & PRICE_REDUCED numeric
						$error[] = _( 'Please enter valid Numeric data.' );

						continue;
					}

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

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		if ( DeletePrompt( _( 'Meal Item' ) ) )
		{
			DBQuery( "DELETE FROM food_service_menu_items
				WHERE MENU_ID='" . (int) $_REQUEST['tab_id'] . "'
				AND MENU_ITEM_ID='" . (int) $_REQUEST['menu_item_id'] . "'" );

			// Unset modfunc & menu item ID & redirect URL.
			RedirectURL( [ 'modfunc', 'menu_item_id' ] );
		}
	}
	elseif ( DeletePrompt( _( 'Item' ) ) )
	{
		$delete_sql = "DELETE FROM food_service_menu_items
			WHERE ITEM_ID='" . (int) $_REQUEST['item_id'] . "';";

		$delete_sql .= "DELETE FROM food_service_items
			WHERE ITEM_ID='" . (int) $_REQUEST['item_id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & item ID & redirect URL.
		RedirectURL( [ 'modfunc', 'item_id' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$menus_RET = DBGet( "SELECT MENU_ID,TITLE
		FROM food_service_menus
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'MENU_ID' ] );

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
		$items_RET = DBGet( "SELECT ITEM_ID,DESCRIPTION
			FROM food_service_items
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		$items_select = [];

		foreach ( (array) $items_RET as $item )
		{
			$items_select += [ $item['ITEM_ID'] => $item['DESCRIPTION'] ];
		}

		$categories_RET = DBGet( "SELECT CATEGORY_ID,TITLE
			FROM food_service_categories
			WHERE MENU_ID='" . (int) $_REQUEST['tab_id'] . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		$categories_select = [];

		foreach ( (array) $categories_RET as $category )
		{
			$categories_select += [ $category['CATEGORY_ID'] => $category['TITLE'] ];
		}

		$sql = "SELECT MENU_ITEM_ID,ITEM_ID,CATEGORY_ID,DOES_COUNT,SORT_ORDER,
		(SELECT ICON FROM food_service_items WHERE ITEM_ID=fsmi.ITEM_ID) AS ICON
		FROM food_service_menu_items fsmi
		WHERE fsmi.MENU_ID='" . (int) $_REQUEST['tab_id'] . "'
		ORDER BY (SELECT SORT_ORDER FROM food_service_categories WHERE CATEGORY_ID=fsmi.CATEGORY_ID),
		SORT_ORDER IS NULL,SORT_ORDER";

		$functions = [
			'ITEM_ID' => 'makeSelectInput',
			'ICON' => 'makeIcon',
			'CATEGORY_ID' => 'makeSelectInput',
			'DOES_COUNT' => 'makeCheckboxInput',
			'SORT_ORDER' => 'makeTextInput',
		];

		$LO_columns = [
			'ITEM_ID' => _( 'Menu Item' ),
			'ICON' => _( 'Icon' ),
			'CATEGORY_ID' => _( 'Category' ),
			'DOES_COUNT' => _( 'Include in Counts' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		];

		$link['add']['html'] = [
			'ITEM_ID' => makeSelectInput( '', 'ITEM_ID' ),
			'CATEGORY_ID' => makeSelectInput( '', 'CATEGORY_ID' ),
			'DOES_COUNT' => makeCheckboxInput( '', 'DOES_COUNT' ),
			'SORT_ORDER' => makeTextInput( '', 'SORT_ORDER' )
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=' . $_REQUEST['tab_id'];
		$link['remove']['variables'] = [ 'menu_item_id' => 'MENU_ITEM_ID' ];

		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = [
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		];

		$singular = sprintf( _( '%s Item' ), $menus_RET[$_REQUEST['tab_id']][1]['TITLE'] );
		$plural = sprintf( _( '%s Items' ), $menus_RET[$_REQUEST['tab_id']][1]['TITLE'] );
	}
	else
	{
		$icons_select = getFSIcons( $FS_IconsPath );

		$sql = "SELECT ITEM_ID,DESCRIPTION,SHORT_NAME,ICON,SORT_ORDER,
		PRICE,PRICE_REDUCED,PRICE_FREE,PRICE_STAFF
		FROM food_service_items fsmi
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER";

		$functions = [
			'DESCRIPTION' => 'makeTextInput',
			'SHORT_NAME' => 'makeTextInput',
			'ICON' => 'makeSelectInput',
			'SORT_ORDER' => 'makeTextInput',
			'PRICE' => 'makeTextInput',
			'PRICE_REDUCED' => 'makeTextInput',
			'PRICE_FREE' => 'makeTextInput',
			'PRICE_STAFF' => 'makeTextInput',
		];

		if ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' )
		{
			$LO_columns = [
				'DESCRIPTION' => _( 'Item Description' ),
				'SHORT_NAME' => _( 'Short Name' ),
				'ICON' => _( 'Icon' ),
				'SORT_ORDER' => _( 'Sort Order' ),
				'PRICE' => _( 'Student Price' ),
				'PRICE_REDUCED' => _( 'Reduced Price' ),
				'PRICE_FREE' => _( 'Free Price' ),
				'PRICE_STAFF' => _( 'Staff Price' ),
			];
		}
		else
		{
			$LO_columns = [
				'DESCRIPTION' => _( 'Item Description' ),
				'SHORT_NAME' => _( 'Short Name' ),
				'ICON' => _( 'Icon' ),
				'PRICE' => _( 'Student Price' ),
			];

			if ( UserStudentID() )
			{
				$discount = DBGetOne( "SELECT DISCOUNT
					FROM food_service_student_accounts
					WHERE STUDENT_ID='" . UserStudentID() . "'" );

				if ( $discount == 'Reduced' )
				{
					$LO_columns += [ 'PRICE_REDUCED' => _( 'Reduced Price' ) ];
				}
				elseif ( $discount == 'Free' )
				{
					$LO_columns += [ 'PRICE_FREE' => _( 'Free Price' ) ];
				}
			}

			$LO_columns += [ 'PRICE_STAFF' => _( 'Staff Price' ) ];
		}

		$link['add']['html'] = [
			'DESCRIPTION' => makeTextInput( '', 'DESCRIPTION' ),
			'SHORT_NAME' => makeTextInput( '', 'SHORT_NAME' ),
			'ICON' => makeSelectInput( '', 'ICON' ),
			'SORT_ORDER' => makeTextInput( '', 'SORT_ORDER' ),
			'PRICE' => makeTextInput( '', 'PRICE' ),
			'PRICE_REDUCED' => makeTextInput( '', 'PRICE_REDUCED' ),
			'PRICE_FREE' => makeTextInput( '', 'PRICE_FREE' ),
			'PRICE_STAFF' => makeTextInput( '', 'PRICE_STAFF' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=' . $_REQUEST['tab_id'];
		$link['remove']['variables'] = [ 'item_id' => 'ITEM_ID' ];
		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = [
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		];
	}

	$LO_ret = DBGet( $sql, $functions );
	//echo '<pre>'; var_dump($LO_ret); echo '</pre>';

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&tab_id=' . $_REQUEST['tab_id']  ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );

	echo '<br />';

	// FJ fix SQL bug invalid sort order
	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	$extra = [ 'save' => false, 'search' => false,
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $_REQUEST['tab_id'] ) ];

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		ListOutput( $LO_ret, $LO_columns, $singular, $plural, $link, [], $extra );
	}
	else
	{
		ListOutput( $LO_ret, $LO_columns, 'Meal Item', 'Meal Items', $link, [], $extra );
	}

	echo '<br /><div class="center">' . SubmitButton() . '</div></form>';


	if ( AllowEdit()
		&& $_REQUEST['tab_id'] === 'new'
		&& is_writable( $FS_IconsPath ) )
	{
		// @since 8.9 Food Service icon upload.
		echo '<br /><form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new&modfunc=upload' ) . '" method="POST" enctype="multipart/form-data">';

		echo FileInput(
			'upload',
			'',
			// Fix photo use mime types, not file extensions so mobile browsers allow camera
			'required accept="image/jpeg, image/png, image/gif"'
		);

		echo SubmitButton( _( 'Upload' ), '', '' );

		echo FormatInputTitle(
			button( 'add', '', '', 'smaller' ) . ' ' . _( 'Icon' ) . ' (.jpg, .png, .gif)',
			'upload'
		);

		echo '</form>';
	}
}

/**
 * @param $value
 * @param $name
 */
function makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['MENU_ITEM_ID'] ) )
	{
		$id = $THIS_RET['MENU_ITEM_ID'];
	}
	elseif ( ! empty( $THIS_RET['ITEM_ID'] ) )
	{
		$id = $THIS_RET['ITEM_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name == 'DESCRIPTION' )
	{
		$extra = 'size=20 maxlength=25';
	}
	elseif ( $name == 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	elseif ( mb_strpos( $name, 'PRICE' ) !== false )
	{
		$extra = ' type="number" step="0.01" min="-999999999" max="999999999"';
	}
	else
	{
		$extra = 'size=6 maxlength=8';
	}

	if ( $id !== 'new'
		&& ( $name === 'DESCRIPTION'
			|| $name === 'SHORT_NAME'
			|| $name === 'PRICE'
			|| $name === 'PRICE_STAFF' ) )
	{
		$extra .= ' required';
	}

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

/**
 * @param $value
 * @param $name
 */
function makeSelectInput( $value, $name )
{
	global $THIS_RET, $items_select, $categories_select, $icons_select;

	if ( ! empty( $THIS_RET['MENU_ITEM_ID'] ) )
	{
		$id = $THIS_RET['MENU_ITEM_ID'];
	}
	elseif ( ! empty( $THIS_RET['ITEM_ID'] ) )
	{
		$id = $THIS_RET['ITEM_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name == 'ITEM_ID' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$items_select,
			( $id === 'new' ? 'N/A' : false )
		);
	}
	elseif ( $name == 'CATEGORY_ID' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$categories_select
		);
	}
	else
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$icons_select
		);
	}
}

/**
 * @param $value
 * @param $name
 */
function makeCheckboxInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['MENU_ITEM_ID'] ) )
	{
		$id = $THIS_RET['MENU_ITEM_ID'];
	}
	else
	{
		$id = 'new';
	}

	return CheckboxInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$value,
		$id == 'new',
		button( 'check' ),
		button( 'x' )
	);
}

/**
 * @param $path
 * @return array
 */
function getFSIcons( $path )
{
	$icons = [];

	if ( is_dir( $path ) )
	{
		$icons = scandir( $path );
	}

	$files = [];

	foreach ( $icons as $icon )
	{
		// Filter images.
		if ( in_array( mb_strtolower( mb_strrchr( $icon, '.' ) ), [ '.jpg', '.jpeg', '.png', '.gif' ] ) )
		{
			$files[$icon] = [ $icon, '<img src="' . URLEscape( $path . $icon ) . '" width="48" />' ];
		}
	}

	ksort( $files );

	return $files;
}
