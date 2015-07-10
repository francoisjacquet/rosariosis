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
 * @param  string $left   Left part of the Header
 * @param  string $right  Right part of the Header (optional)
 * @param  string $center Center part of the Header (optional)
 *
 * @return void           outputs Header HTML
 */
function DrawHeader( $left, $right = '', $center = '' )
{
	global $_ROSARIO;

	// Primary Header
	if ( !isset( $_ROSARIO['DrawHeader'] ) )
	{
		$_ROSARIO['DrawHeader'] = '';
	}

	echo '<TABLE class="width-100p cellspacing-0"><TR class="st">';

	if ( $left !== '' )
	{
		// Add H2 + Module icon to Primary Header
		if ( $_ROSARIO['DrawHeader'] === '' )
		{
			if ( isset( $_ROSARIO['HeaderIcon'] )
				&& $_ROSARIO['HeaderIcon'] !== false )
				$left = '<IMG src="' . $_ROSARIO['HeaderIcon'] . '" class="headerIcon" /> ' . $left;

			$left = '<h2>' . $left . '</h2>';
		}

		echo '<TD' . $_ROSARIO['DrawHeader'] . '>&nbsp;' .
			$left .
		'</TD>';
	}

	if ( $center !== '' )
		echo '<TD' . $_ROSARIO['DrawHeader'] . ' style="text-align:center">' .
			$center .
		'</TD>';

	if ( $right !== '' )
		echo '<TD' . $_ROSARIO['DrawHeader'] . ' style="text-align:right">' .
			$right .
		'</TD>';

	echo '</TR></TABLE>';

	// Secondary Headers
	$_ROSARIO['DrawHeader'] = ' class="header2"';
}

?>