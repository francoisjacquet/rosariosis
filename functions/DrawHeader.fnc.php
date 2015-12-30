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
	global $_ROSARIO;

	// Primary Header.
	if ( ! isset( $_ROSARIO['DrawHeader'] ) )
	{
		$_ROSARIO['DrawHeader'] = 'header1';
	}

	echo '<table class="width-100p cellspacing-0"><tr class="st">';

	if ( $left !== '' )
	{
		// Add H2 + Module icon to Primary Header.
		if ( $_ROSARIO['DrawHeader'] === 'header1' )
		{
			if ( isset( $_ROSARIO['HeaderIcon'] )
				&& $_ROSARIO['HeaderIcon'] !== false )
			{
				$left = '<img src="' . $_ROSARIO['HeaderIcon'] . '" class="headerIcon" /> ' . $left;
			}

			$left = '<h2>' . $left . '</h2>';
		}

		echo '<td class="' . $_ROSARIO['DrawHeader'] . '">' .
			$left .
		'</td>';
	}

	if ( $center !== '' )
	{
		echo '<td class="' . $_ROSARIO['DrawHeader'] . ' center">' .
			$center .
		'</td>';
	}

	if ( $right !== '' )
	{
		echo '<td class="' . $_ROSARIO['DrawHeader'] . ' align-right">' .
			$right .
		'</td>';
	}

	echo '</tr></table>';

	// Secondary Headers.
	$_ROSARIO['DrawHeader'] = 'header2';
}
