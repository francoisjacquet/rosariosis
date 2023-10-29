<?php

require_once 'modules/Food_Service/includes/FS_Icons.inc.php';

$_REQUEST['cat_id'] = issetVal( $_REQUEST['cat_id'] );

DrawHeader( ProgramTitle() );

$menus_RET = DBGet( "SELECT MENU_ID,TITLE
	FROM food_service_menus
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'MENU_ID' ] );

if ( empty( $_REQUEST['menu_id'] ) )
{
	if ( empty( $_SESSION['FSA_menu_id'] ) )
	{
		if ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( [ _( 'There are no menus yet setup.' ) ], 'fatal' );
		}
	}
	else
	{
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
	}
}
else
{
	$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
}

$categories_RET = DBGet( "SELECT MENU_ID,CATEGORY_ID,TITLE
	FROM food_service_categories
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'MENU_ID', 'CATEGORY_ID' ] );
//FJ fix error Warning: key() expects parameter 1 to be array, null given
//if ( ! $_REQUEST['cat_id'] || ! $categories_RET[$_REQUEST['menu_id']][$_REQUEST['cat_id']])

if ( ( ! $_REQUEST['cat_id'] || ! $categories_RET[$_REQUEST['menu_id']][$_REQUEST['cat_id']] ) && isset( $categories_RET[$_REQUEST['menu_id']] ) )
{
	$_REQUEST['cat_id'] = key( $categories_RET[$_REQUEST['menu_id']] );
}

$meals = [];

foreach ( (array) $menus_RET as $id => $menu )
{
	$meals[] = [
		'title' => $menu[1]['TITLE'],
		'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id,
	];
}

$cats = [];
//FJ fix error Warning: Invalid argument supplied for foreach()

if ( isset( $categories_RET[$_REQUEST['menu_id']] ) )
{
	foreach ( (array) $categories_RET[$_REQUEST['menu_id']] as $category_id => $category )
	{
		$cats[ $category_id ] = [
			'title' => $category[1]['TITLE'],
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] .
				'&cat_id=' . $category_id,
		];
	}
}

$items_RET = DBGet( "SELECT MENU_ITEM_ID,
	(SELECT DESCRIPTION FROM food_service_items WHERE ITEM_ID=fsmi.ITEM_ID) AS DESCRIPTION,
	(SELECT ICON FROM food_service_items WHERE ITEM_ID=fsmi.ITEM_ID) AS ICON
FROM food_service_menu_items fsmi
WHERE MENU_ID='" . (int) $_REQUEST['menu_id'] . "'
AND CATEGORY_ID='" . (int) $_REQUEST['cat_id'] . "'
ORDER BY (SELECT SORT_ORDER FROM food_service_categories WHERE CATEGORY_ID=fsmi.CATEGORY_ID),
SORT_ORDER IS NULL,SORT_ORDER" );

echo '<br />';

$_ROSARIO['selected_tab'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'];

PopTable( 'header', $meals );

if ( ! empty( $items_RET ) )
{
	$per_row = ceil( sqrt( count( $items_RET ) ) );

	echo '<table class="center cellpadding-5">';

	$i = 0;

	foreach ( (array) $items_RET as $item )
	{
		if ( ! $i )
		{
			echo '<tr>';
			$i = $per_row;
		}

		$kiosk_menu_item = '<td style="border: 1px solid" title="' . AttrEscape( $item['DESCRIPTION'] ) . '">' .
			makeIcon( $item['ICON'], '', '128' ) . '</td>';

		// @since 11.2 Action hook Filter each menu item in the loop
		do_action( 'Food_Service/Kiosk.php|menu_item_loop', [ &$kiosk_menu_item, $item ] );

		echo $kiosk_menu_item;
		$i--;

		if ( ! $i )
		{
			echo '</tr>';
		}
	}

	if ( $i )
	{
		echo '</tr>';
	}

	echo '</table>';
}

echo '<br /><div class="center">';

$i = 0;

if ( count( $cats ) === 1 )
{
	$cat = reset( $cats );

	echo $cat['title'];
}
else
{
	foreach ( $cats as $cat_id => $cat )
	{
		if ( $i++ > 0 )
		{
			echo ' | ';
		}

		echo '<a href="' . URLEscape( $cat['link'] ) . '">' .
			( $_REQUEST['cat_id'] == $cat_id ? '<b>' . $cat['title'] . '</b>' : $cat['title'] ) .
			'</a>';
	}
}

echo '</div>';

PopTable( 'footer' );
