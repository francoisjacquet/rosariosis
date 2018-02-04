<?php
/**
 * Implementation for PHP xml extension functions not included by default.
 *
 * @since 3.8
 *
 * @copyright Symfony
 *
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @link https://github.com/symfony/polyfill/blob/master/src/Php72/Php72.php
 *
 * @package RosarioSIS
 * @subpackage functions
 */

if ( ! function_exists( 'utf8_encode' ) ) :

	function utf8_encode($s)
	{
		$s .= $s;
		$len = strlen($s);
		for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
			switch (true) {
				case $s[$i] < "\x80": $s[$j] = $s[$i]; break;
				case $s[$i] < "\xC0": $s[$j] = "\xC2"; $s[++$j] = $s[$i]; break;
				default: $s[$j] = "\xC3"; $s[++$j] = chr(ord($s[$i]) - 64); break;
			}
		}
		return substr($s, 0, $j);
	}

endif;

if ( ! function_exists( 'utf8_decode' ) ) :

	function utf8_decode($s)
	{
		$s = (string) $s;
		$len = strlen($s);
		for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
			switch ($s[$i] & "\xF0") {
				case "\xC0":
				case "\xD0":
					$c = (ord($s[$i] & "\x1F") << 6) | ord($s[++$i] & "\x3F");
					$s[$j] = $c < 256 ? chr($c) : '?';
					break;
				case "\xF0": ++$i;
				case "\xE0":
					$s[$j] = '?';
					$i += 2;
					break;
				default:
					$s[$j] = $s[$i];
			}
		}
		return substr($s, 0, $j);
	}

endif;
