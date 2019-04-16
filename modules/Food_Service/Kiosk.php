<?php

require_once 'modules/Food_Service/includes/FS_Icons.inc.php';

DrawHeader( ProgramTitle() );

$menus_RET = DBGet( "SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID='" . UserSchool() . "' ORDER BY SORT_ORDER", array(), array( 'MENU_ID' ) );

if ( ! empty( $_REQUEST['menu_id'] ) )
{
	if ( $_REQUEST['menu_id'] !== 'new' )
	{
		if ( $menus_RET[$_REQUEST['menu_id']] )
		{
			$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
		}
		elseif ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( array( _( 'There are no menus yet setup.' ) ), 'fatal' );
		}
	}
	elseif ( ! empty( $menus_RET ) )
	{
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
	}
	else
	{
		ErrorMessage( array( _( 'There are no menus yet setup.' ) ), 'fatal' );
	}
}
else
{
	if ( $_SESSION['FSA_menu_id'] )
	{
		if ( $menus_RET[$_SESSION['FSA_menu_id']] )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
		}
		elseif ( ! empty( $menus_RET ) )
		{
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
		}
		else
		{
			ErrorMessage( array( _( 'There are no menus yet setup.' ) ), 'fatal' );
		}
	}
	elseif ( ! empty( $menus_RET ) )
	{
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key( $menus_RET );
	}
	else
	{
		ErrorMessage( array( _( 'There are no menus yet setup.' ) ), 'fatal' );
	}
}

$categories_RET = DBGet( "SELECT MENU_ID,CATEGORY_ID,TITLE FROM FOOD_SERVICE_CATEGORIES WHERE SCHOOL_ID='" . UserSchool() . "' ORDER BY SORT_ORDER", array(), array( 'MENU_ID', 'CATEGORY_ID' ) );
//FJ fix error Warning: key() expects parameter 1 to be array, null given
//if ( ! $_REQUEST['cat_id'] || ! $categories_RET[$_REQUEST['menu_id']][$_REQUEST['cat_id']])

if (  ( ! $_REQUEST['cat_id'] || ! $categories_RET[$_REQUEST['menu_id']][$_REQUEST['cat_id']] ) && isset( $categories_RET[$_REQUEST['menu_id']] ) )
{
	$_REQUEST['cat_id'] = key( $categories_RET[$_REQUEST['menu_id']] );
}

$meals = array();

foreach ( (array) $menus_RET as $id => $menu )
{
	$meals[] = array( 'title' => $menu[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id );
}

$cats = array();
//FJ fix error Warning: Invalid argument supplied for foreach()

if ( isset( $categories_RET[$_REQUEST['menu_id']] ) )
{
	foreach ( (array) $categories_RET[$_REQUEST['menu_id']] as $category_id => $category )
	{
		$cats[] = array( 'title' => $category[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&cat_id=' . $category_id );
	}
}

$items_RET = DBGet( "SELECT *,(SELECT ICON FROM FOOD_SERVICE_ITEMS WHERE ITEM_ID=fsmi.ITEM_ID) AS ICON
FROM FOOD_SERVICE_MENU_ITEMS fsmi
WHERE MENU_ID='" . $_REQUEST['menu_id'] . "'
AND CATEGORY_ID='" . $_REQUEST['cat_id'] . "'
ORDER BY (SELECT SORT_ORDER FROM FOOD_SERVICE_CATEGORIES WHERE CATEGORY_ID=fsmi.CATEGORY_ID),SORT_ORDER" );

echo '<br />';

echo '<div class="center">' . WrapTabs( $meals, 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] ) . '</div>';

if ( ! empty( $items_RET ) )
{
	$per_row = ceil( sqrt( count( $items_RET ) ) );
//FJ css WPadmin
	echo '<table class="center cellpadding-5">';

	foreach ( (array) $items_RET as $item )
	{
		if ( ! $i )
		{
			echo '<tr>';
			$i = $per_row;
		}

		echo '<td style="border: 1px solid">' . makeIcon( $item['ICON'], '', '128' ) . '</td>';
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

//FJ remove WrapTabs params
echo '<div class="center">' . WrapTabs( $cats, 'Modules.php?modname=' . $_REQUEST['modname'] . '&cat_id=' . $_REQUEST['cat_id'] ) . '</div>';
