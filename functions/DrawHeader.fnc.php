<?php
/**
 * Draw Header function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Draw Header
 *
 * The first call draws the Primary Header
 * Next calls draw Secondary Headers
 * unset( $_ROSARIO['DrawHeader'] ) to reset
 *
 * @example DrawHeader( ProgramTitle() );
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['DrawHeader']
 *
 * @param  string $left     Left part of the Header.
 * @param  string $right    Right part of the Header (optional).
 * @param  string $center   Center part of the Header (optional).
 *
 * @return void   outputs Header HTML
 */
function DrawHeader( $left, $right = '', $center = '' )
{
	global $_ROSARIO,
		$RosarioCoreModules;

	// Primary Header.
	if ( ! isset( $_ROSARIO['DrawHeader'] )
		|| ! $_ROSARIO['DrawHeader'] )
	{
		$_ROSARIO['DrawHeader'] = 'header1';
	}

	echo '<table class="header"><tr class="st">';

	if ( $left )
	{
		// Add H2 + Module icon to Primary Header.
		if ( $_ROSARIO['DrawHeader'] === 'header1' )
		{
			$header_icon = '';

			if ( isset( $_ROSARIO['HeaderIcon'] )
				&& $_ROSARIO['HeaderIcon'] !== false )
			{
				$header_icon = '<span class="module-icon ' . $_ROSARIO['HeaderIcon'] . '"';

				if ( $_ROSARIO['HeaderIcon'] !== 'misc'
					&& ! in_array( $_ROSARIO['HeaderIcon'], $RosarioCoreModules ) )
				{
					// Modcat is addon module, set custom module icon.
					$header_icon .= ' style="background-image: url(modules/' . $_ROSARIO['HeaderIcon'] . '/icon.png);"';
				}

				$header_icon .= '></span> ';
			}

			$left = '<h2>' . $header_icon . $left . '</h2>';
		}

		echo '<td class="' . AttrEscape( $_ROSARIO['DrawHeader'] ) . '">' .
			$left .
		'</td>';
	}

	if ( $center )
	{
		echo '<td class="' . AttrEscape( $_ROSARIO['DrawHeader'] ) . ' center">' .
			$center .
		'</td>';
	}

	if ( $right )
	{
		echo '<td class="' . AttrEscape( $_ROSARIO['DrawHeader'] ) . ' align-right">' .
			$right .
		'</td>';
	}

	echo '</tr></table>';

	// Secondary Headers.
	$_ROSARIO['DrawHeader'] = 'header2';
}
