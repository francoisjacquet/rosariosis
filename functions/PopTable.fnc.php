<?php
/**
 * Pop Table
 * Used for Login screen, Student Information (multiple tabs)...
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * PopTable
 *
 * @example PopTable( 'header', _( 'Confirm Successful Installation' ) );
 * @example PopTable( 'footer' );
 *
 * @global array  $_ROSARIO  Uses $_ROSARIO['selected_tab']
 *
 * @since 10.6 CSS responsive add .postbox-wrapper class for overflow-x scroll
 *
 * @param  string       $action        'header' or 'footer'.
 * @param  string|array $tabs_or_title PopTable Title or Tabs: array( array( 'link' => 'tab-link.php', 'title' => 'Tab Title' ) ) (optional).
 * @param  string       $table_att     <table [attributes]> (optional).
 *
 * @return string outputs PopTable HTML
 */
function PopTable( $action, $tabs_or_title = 'Search', $table_att = '' )
{
	global $_ROSARIO;

	if ( $action === 'header' )
	{
		echo '<div class="postbox-wrapper"><table class="postbox cellspacing-0" ' . $table_att . '>
			<thead><tr><th class="center">';

		// Multiple Tabs.
		if ( is_array( $tabs_or_title ) )
		{
			echo WrapTabs( $tabs_or_title, issetVal( $_ROSARIO['selected_tab'] ) );
		}
		// One Tab.
		else
		{
			echo DrawTab( $tabs_or_title );
		}

		echo '</th></tr></thead>
			<tbody><tr><td class="popTable">';
	}
	elseif ( $action === 'footer' )
	{
		echo '</td></tr></tbody></table></div>';
	}
}
