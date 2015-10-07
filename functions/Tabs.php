<?php

/**
 * Wrap Tabs
 * used by PopTable() or ListOutput() header
 *
 * @param  array  $tabs     Tabs, titles + links
 * @param  string $selected Selected Tab link (optional)
 *
 * @return string Tabs HTML
 */
function WrapTabs( $tabs, $selected = '' )
{
	$tabs_html = '<div class="h3multi">';

	foreach( (array)$tabs as $key => $tab )
	{
		if( $tab['link'] == PreparePHP_SELF()
			|| $tab['link'] == $selected )
		{
			$selected_tab = true;
		}
		else
			$selected_tab = false;

		$tabs_html .= DrawTab( $tab['title'], $tab['link'], $selected_tab );
	}

	$tabs_html .= '</div>';

	return $tabs_html;
}


/**
 * Draw Tab
 * used by PopTable() & WrapTabs()
 *
 * @param  string  $title    Tab title
 * @param  string  $link     Tab link (optional)
 * @param  boolean $selected Selected Tab (optional)
 *
 * @return string  Tab HTML
 */
function DrawTab( $title, $link = '', $selected = false )
{
	$title = ParseMLField( $title );

	// non breaking spaces in title
	if( mb_substr( $title, 0, 1) != '<' )
		$title = str_replace( ' ', '&nbsp;', $title );

	// .title CSS class used in warehouse.js to determine document.title
	// link if not printing PDF
	if( $link && !isset( $_REQUEST['_ROSARIO_PDF'] ) )
		$block_table = '<h3' . ( $selected ? ' class="h3selected title"' : '' ) . '>
			<a href="' . $link . '">'
			. _( $title )
			. '</a>
			</h3>';
	else
		$block_table = '<h3 class="title">' . $title . '</h3>';
		
	return $block_table;
}
