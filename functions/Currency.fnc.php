<?php

/**
 * Format Money amount and add Currency symbol
 *
 * @example Currency( $total )
 *
 * @param  float   $num  Money amount
 * @param  string  $sign Minus sign or credit (CR) (optional)
 * @param  boolean $red  Red if negative amount (optional)
 *
 * @return string  Formatted number
 */
function Currency( $num, $sign = 'before', $red = false )
{
	$original = $num;

	$negative = $cr = false;

	// FJ Bugfix Currency direct call via $extra['functions']
	if ( $sign === 'CR'
		&& $num < 0 )
	{
		$cr = true;

		$num *= -1;
	}
	elseif ( $num < 0 )
	{
		$negative = true;

		$num *= -1;
	}

	// Add currency symbol & format amount
	$num = Config( 'CURRENCY' ) . number_format( $num, 2, '.', ',' );

	// Add minus if negative
	if ( $negative )
		$num = '-' . $num;

	// Add CR if credit
	elseif ( $cr )
		$num = $num . 'CR';

	// Red if negative amount
	if ( $red
		&& $original < 0 )
		$num = '<span style="color: red;">' . $num . '</span>';

	return '<!-- ' . $original . ' -->' . $num;
}
