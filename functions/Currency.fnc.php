<?php
/**
 * Currency function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Format Money amount and add Currency symbol
 *
 * @example Currency( $total )
 *
 * @param  float   $num  Money amount.
 * @param  string  $sign Minus sign or credit (CR) (optional). Defaults to 'before'.
 * @param  boolean $red  Red if negative amount (optional).
 *
 * @return string  Formatted number & unformatted number inside HTML comment
 */
function Currency( $num, $sign = 'before', $red = false )
{
	$num = (float) $num;

	$original = $num;

	$negative = $cr = false;

	// FJ Bugfix Currency direct call via $extra['functions'].
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

	// Add currency symbol & format amount.
	// @since 9.1 Add decimal & thousands separator configuration.
	// @link https://en.wikipedia.org/wiki/Decimal_separator
	$num = number_format(
		$num,
		2,
		Config( 'DECIMAL_SEPARATOR' ),
		Config( 'THOUSANDS_SEPARATOR' )
	);

	$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

	$currency_after_lang = [ 'fr', 'es', 'de', 'cs', 'hu', 'ru', 'sv', 'tr', 'vi' ];

	if ( in_array( $lang_2_chars, $currency_after_lang ) )
	{
		// @since 10.0 Place currency symbol after amount for some locales
		// @link https://fastspring.com/blog/how-to-format-30-currencies-from-countries-all-over-the-world/
		$num .= '&nbsp;' . Config( 'CURRENCY' );
	}
	else
	{
		$num = Config( 'CURRENCY' ) . $num;
	}

	// Add minus if negative.
	if ( $negative )
	{
		$num = '-' . $num;
	}

	// Add CR if credit.
	elseif ( $cr )
	{
		$num = $num . 'CR';
	}

	// Red if negative amount.
	if ( $red
		&& $original < 0 )
	{
		$num = '<span style="color: red;">' . $num . '</span>';
	}

	return '<!-- ' . $original . ' -->' . $num;
}
