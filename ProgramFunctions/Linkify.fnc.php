<?php
/**
 * Linkify function
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Linkify
 * Transforms the URLs present a in text to anchors tags
 * and truncate the link text of URLs > 100 chars
 *
 * @example $text_linkified = Linkify( $text );
 *
 * @link http://stackoverflow.com/questions/15928606/php-converting-text-links-to-anchor-tags
 *
 * @param string $text Text to linkify.
 *
 * @return string Linkified text
 */
function Linkify( $text )
{
	$pattern = '((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,8}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';

	return preg_replace_callback( "#$pattern#i", function( $matches )
	{
		$input = $matches[0];

		$url = preg_match( '!^https?://!i', $input ) ? $input : "http://$input";

		if ( mb_strlen( $input ) > 100
			&& ! mb_strpos( $input, ' ' ) )
		{
			$separator = '...';

			$maxlength = 97; // 100 - $separator length.

			$input = substr_replace(
				$input,
				$separator,
				( $maxlength / 2 ),
				( mb_strlen( $input ) - $maxlength )
			);
		}

		return '<a href="' . URLEscape( $url ) . '" target="_blank">' . $input . '</a>';
	}, $text );
}
