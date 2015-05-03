<?php

/**
 * Wrap PopTable Tabs
 *
 * @param array $tabs           Tabs, titles + links
 * @param string $selected      Selected Tab link
 *
 * @return string PopTable Tabs HTML
 */
function WrapTabs( $tabs, $selected = '' )
{
	$tabs_html = array();

	foreach( (array)$tabs as $key => $tab )
	{
		if( $tab['link'] == PreparePHP_SELF()
			|| $tab['link'] == $selected )
		{
			$selected_tab = true;
		}
		else
			$selected_tab = false;

		$tabs_html[] = DrawTab( $tab['title'], $tab['link'], $selected_tab );
	}

	$return = '<div class="h3multi">' . implode( $tabs_html ) . '</div>';

	return $return;
}


/**
 * Draw PopTable tab
 *
 * @param string  $title      Tab title
 * @param string  $link       Tab link
 * @param boolean $selected   Selected Tab
 *
 * @return  string Tab HTML
 */
function DrawTab( $title, $link = '', $selected = false )
{
	$title = ParseMLField( $title );

	// non breaking spaces in title
	if( mb_substr( $title, 0, 1) != '<' )
		$title = str_replace( ' ', '&nbsp;', $title );

	// link if not printing PDF
	if( $link && !isset( $_REQUEST['_ROSARIO_PDF'] ) )
		$block_table = '<h3' . ( $selected ? ' class="h3selected"' : '' ) . '>
			<a href="' . $link . '">'
			. _( $title )
			. '</a>
			</h3>';
	else
		$block_table = '<h3>' . $title . '</h3>';
		
	return $block_table;
}

?>