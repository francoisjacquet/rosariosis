<?php

/**
 * PopTable
 *
 * @global array  $_ROSARIO  Uses $_ROSARIO['selected_tab']
 *
 * @param  string $action    'header' or 'footer'
 * @param  string $title     PopTable Tab(s) title(s) (optional)
 * @param  string $table_att <table [attributes]> (optional)
 *
 * @return string outputs PopTable HTML
 */
function PopTable( $action, $title = 'Search', $table_att = '' )
{
	global $_ROSARIO;

	if ( $action == 'header' )
	{
		echo '<table class="postbox cellspacing-0" ' . $table_att . '>
			<thead><tr><th class="center">';

		// multiple Tabs
		if ( is_array( $title ) )
			echo WrapTabs( $title, $_ROSARIO['selected_tab'] );
		// one Tab
		else
			echo DrawTab( $title );

		echo '</th></tr></thead>
			<tbody><tr><td class="popTable">';
	}
	elseif ( $action == 'footer' )
	{
		echo '</td></tr></tbody></table>';
	}
}
