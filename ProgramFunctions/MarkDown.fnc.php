<?php
/**
 * MarkDown functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Convert MarkDown text to HTML
 *
 * Note:
 * Prefer `showdown.js` plugin, hooked by adding
 * the `markdown-to-html` CSS class to your containing DIV
 *
 * @uses Parsedown Markdown Parser class in PHP
 *
 * @example require_once 'ProgramFunctions/MarkDown.fnc.php';
 *          echo MarkDownToHTML( 'Hello _Parsedown_!' );
 *          will print: <p>Hello <em>Parsedown</em>!</p>
 *
 * @since  2.9
 *
 * @global object $Parsedown
 *
 * @param  string $MD     MarkDown text.
 * @param  string $column DBGet() COLUMN formatting compatibility (optional).
 *
 * @return string HTML
 */
function MarkDownToHTML( $MD, $column = '' )
{
	if ( ! is_string( $MD )
		|| empty( $MD ) )
	{
		return $MD;
	}

	global $Parsedown;

	// Create $Parsedown object once.
	if ( ! ( $Parsedown instanceof Parsedown ) )
	{
		require_once 'classes/Parsedown.php';

		$Parsedown = new Parsedown();
	}

	return $Parsedown->setBreaksEnabled( true )->text( $MD );
}


/**
 * Sanitize MarkDown user input
 *
 * @uses    Security class
 *
 * @example require_once 'ProgramFunctions/MarkDown.fnc.php';
 *          $_REQUEST['values']['textarea'] = SanitizeMarkDown( $_REQUEST['values']['textarea'] );
 *
 * @since   2.9
 *
 * @todo Anyone has an idea to get sanitized MD back? See last line of function.
 *
 * @global object $Security
 *
 * @param  string $MD MarkDown text.
 *
 * @return string Input with HTML encoded single quotes or empty string if Sanitized MD != Input MD
 */
function SanitizeMarkDown( $MD )
{
	if ( ! is_string( $MD )
		|| empty( $MD ) )
	{
		return $MD;
	}

	/**
	 * Undo DBEscapeString()
	 * $MD is supposed to be USER input
	 */
	$MD = str_replace( "''", "'",	$MD );

	/**
	 * Convert single quotes to HTML entities
	 *
	 * Fixes bug related to:
	 * replace empty strings ('') with NULL values
	 *
	 * @see DBQuery()
	 */
	$MD_quotes = str_replace( "'", '&#039;', $MD );

	// Convert MarkDown to HTML.
	$HTML = MarkDownToHTML( $MD_quotes );

	global $Security;

	// Create $Security object once.
	if ( ! ( $Security instanceof Security ) )
	{
		require_once 'classes/Security.php';

		$Security = new Security();
	}

	$sanitizedHTML = $Security->xss_clean( $HTML );

	if ( $sanitizedHTML === $HTML )
	{
		return $MD_quotes;
	}
	else
	{
		if ( ROSARIO_DEBUG )
		{
			var_dump( $HTML, $sanitizedHTML );
		}

		return ''; // Anyone has an idea to get sanitized MD back?
	}
}
