<?php

/**
 * Draw Header
 *
 * The first call draws the Primary Header
 * Next calls draw Secondary Headers
 * unset( $_ROSARIO['DrawHeader'] ) to reset
 *
 * @example DrawHeader( ProgramTitle() );
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['AllowUse']
 *
 * @param  string $left     Left part of the Header
 * @param  string $right    Right part of the Header (optional)
 * @param  string $center   Center part of the Header (optional)
 *
 * @return void   outputs Header HTML
 */
function DrawHeader( $left, $right = '', $center = '' )
{
	global $_ROSARIO;

	// Primary Header
	if ( !isset( $_ROSARIO['DrawHeader'] ) )
	{
		$_ROSARIO['DrawHeader'] = '';
	}

	echo '<table class="width-100p cellspacing-0"><tr class="st">';

	if ( $left !== '' )
	{
		// Add H2 + Module icon to Primary Header
		if ( $_ROSARIO['DrawHeader'] === '' )
		{
			if ( isset( $_ROSARIO['HeaderIcon'] )
				&& $_ROSARIO['HeaderIcon'] !== false )
				$left = '<img src="' . $_ROSARIO['HeaderIcon'] . '" class="headerIcon" /> ' . $left;

			$left = '<h2>' . $left . '</h2>';
		}

		echo '<td' . $_ROSARIO['DrawHeader'] . '>' .
			$left .
		'</td>';
	}

	if ( $center !== '' )
		echo '<td' . $_ROSARIO['DrawHeader'] . ' style="text-align:center">' .
			$center .
		'</td>';

	if ( $right !== '' )
		echo '<td' . $_ROSARIO['DrawHeader'] . ' style="text-align:right">' .
			$right .
		'</td>';

	echo '</tr></table>';

	// Secondary Headers
	$_ROSARIO['DrawHeader'] = ' class="header2"';
}
