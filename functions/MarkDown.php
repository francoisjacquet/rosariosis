<?php

/**
 * MarkDown functions
 *
 */

/**
 * Convert MarkDown textarea content or MD file into HTML
 *
 * @uses Parsedown Markdown Parser class in PHP
 *
 * @example echo MarkDownToHTML( 'Hello _Parsedown_!' );
 *          will print: <p>Hello <em>Parsedown</em>!</p>
 *
 * @since  2.9
 *
 * @param  string $md     MarkDown text
 * @param  string $column DBGet() COLUMN formatting compatibility (optional)
 *
 * @return string HTML
 */
function MarkDownToHTML( $md, $column = '' )
{
	if ( !is_string( $md ) )
		return $md;

	global $Parsedown;

	// Create $Parsedown object once
	if ( ! ( $Parsedown instanceof Parsedown ) )
	{
		require_once( 'classes/Parsedown.php' );

		$Parsedown = new Parsedown();
	}

	return $Parsedown->setBreaksEnabled( true )->text( $md );
}
