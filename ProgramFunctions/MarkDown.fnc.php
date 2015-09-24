<?php
/**
 * MarkDown functions
 *
 */

/**
 * Convert MarkDown text to HTML
 *
 * Note:
 * Prefer showdown.js plugin
 * Hooked by adding the "markdown-to-html" class
 * to your containing DIV
 *
 * @uses Parsedown Markdown Parser class in PHP
 *
 * @example include_once( 'ProgramFunctions/MarkDown.fnc.php' );
 *          echo MarkDownToHTML( 'Hello _Parsedown_!' );
 *          will print: <p>Hello <em>Parsedown</em>!</p>
 *
 * @since  2.9
 *
 * @param  string $MD     MarkDown text
 * @param  string $column DBGet() COLUMN formatting compatibility (optional)
 *
 * @return string HTML
 */
function MarkDownToHTML( $MD, $column = '' )
{
	if ( !is_string( $MD )
		|| empty( $MD ) )
		return $MD;

	global $Parsedown;

	// Create $Parsedown object once
	if ( ! ( $Parsedown instanceof Parsedown ) )
	{
		require_once( 'classes/Parsedown.php' );

		$Parsedown = new Parsedown();
	}

	return $Parsedown->setBreaksEnabled( true )->text( $MD );
}


/**
 * Sanitize MarkDown user input
 *
 * @uses    Security class
 *
 * @example include_once( 'ProgramFunctions/MarkDown.fnc.php' );
 *          $_REQUEST['values']['textarea'] = SanitizeMarkDown( $_REQUEST['values']['textarea'] );
 *
 * @since   2.9
 *
 * @param   string $MD MarkDown text
 *
 * @return  string Input or empty string if Sanitized MD != Input MD
 */
function SanitizeMarkDown( $MD )
{
	if ( !is_string( $MD )
		|| empty( $MD ) )
		return $MD;

	$HTML = MarkDownToHTML( $MD );

	global $Security;

	// Create $Security object once
	if ( ! ( $Security instanceof Security ) )
	{
		require_once( 'classes/Security.php' );

		$Security = new Security();
	}

	$sanitizedHTML = $Security->xss_clean( $HTML );

	if ( $sanitizedHTML === $HTML )
		return $MD;
	else
		return ''; // anyone has an idea to get sanitized MD back?
}
