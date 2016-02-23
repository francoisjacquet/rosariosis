<?php
/**
 * MarkDown & HTML functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Convert MarkDown text to HTML
 *
 * Note:
 * Prefer `showdown.js` plugin, hooked by adding
 * the `class="markdown-to-html"` containing DIV
 *
 * @uses Parsedown Markdown Parser class in PHP
 *
 * @example require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
 *          echo MarkDownToHTML( 'Hello _Parsedown_!' );
 *          will print: <p>Hello <em>Parsedown</em>!</p>
 *
 * @since  2.9
 *
 * @global object $Parsedown
 *
 * @param  string $md     MarkDown text.
 * @param  string $column DBGet() COLUMN formatting compatibility (optional).
 *
 * @return string HTML
 */
function MarkDownToHTML( $md, $column = '' )
{
	if ( ! is_string( $md )
		|| empty( $md ) )
	{
		return $md;
	}

	global $Parsedown;

	// Create $Parsedown object once.
	if ( ! ( $Parsedown instanceof Parsedown ) )
	{
		require_once 'classes/Parsedown.php';

		$Parsedown = new Parsedown();
	}

	return $Parsedown->text( $md );
}


/**
 * Sanitize MarkDown user input
 *
 * @uses    Security class
 * @uses    Markdownify class
 *
 * @example require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
 *          $_REQUEST['values']['textarea'] = SanitizeMarkDown( $_POST['values']['textarea'] );
 *
 * @since   2.9
 *
 * @global object $security
 * @global object $markdownify
 *
 * @param  string $md MarkDown text.
 *
 * @return string Sanitized input with HTML encoded single quotes
 */
function SanitizeMarkDown( $md )
{
	if ( ! is_string( $md )
		|| empty( $md ) )
	{
		return $md;
	}

	// Convert MarkDown to HTML.
	$html = MarkDownToHTML( $md_quotes );

	global $security;

	// Create $security object once.
	if ( ! ( $security instanceof Security ) )
	{
		require_once 'classes/Security.php';

		$security = new Security();
	}

	$sanitized_html = $security->xss_clean( $html );

	if ( $sanitized_html !== $html )
	{
		if ( ROSARIO_DEBUG )
		{
			echo 'Sanitized HTML:<br />';
			var_dump( $sanitized_html );
		}

		global $markdownify;

		// Create $markdownify object once.
		if ( ! ( $markdownify instanceof Markdownify\ConverterExtra ) )
		{
			require_once 'classes/Markdownify/Converter.php';
			require_once 'classes/Markdownify/ConverterExtra.php'; // Handles HTML tables.
			require_once 'classes/Markdownify/Parser.php';

			$markdownify = new Markdownify\ConverterExtra;
		}

		// HTML to Markdown.
		$return = $markdownify->parseString( $sanitized_html );
	}
	else
	{
		$return = $md;
	}

	/**
	 * Convert single quotes to HTML entities
	 *
	 * Fixes bug related to:
	 * replace empty strings ('') with NULL values
	 *
	 * @see DBQuery()
	 */
	return str_replace( "'", '&#039;', $return );
}



/**
 * Sanitize HTML user input
 * Use for example to sanitize TinyMCE input
 *
 * @see     assets/js/tinymce/
 * @uses    Security class
 *
 * @example require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
 *          $_REQUEST['values']['textarea'] = SanitizeHTML( $_POST['values']['textarea'] );
 *
 * @since   2.9
 *
 * @global object $security
 *
 * @param  string $html HTML text.
 *
 * @return string Sanitized input with HTML encoded single quotes
 */
function SanitizeHTML( $html )
{
	if ( ! is_string( $html )
		|| empty( $html ) )
	{
		return $html;
	}

	global $security;

	// Create $security object once.
	if ( ! ( $security instanceof Security ) )
	{
		require_once 'classes/Security.php';

		$security = new Security();
	}

	$sanitized_html = $security->xss_clean( $html );

	/**
	 * Convert single quotes to HTML entities
	 *
	 * Fixes bug related to:
	 * replace empty strings ('') with NULL values
	 *
	 * @see DBQuery()
	 */
	$sanitized_html_quotes = str_replace( "'", '&#039;', $sanitized_html );

	return $sanitized_html_quotes;
}
