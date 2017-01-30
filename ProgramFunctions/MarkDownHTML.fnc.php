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

	return $Parsedown->setBreaksEnabled( true )->text( $md );
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
	$html = MarkDownToHTML( $md );

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
 * @uses    CheckBase64Image()
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

	$has_base64_images = preg_match_all(
		'/<img src=\"(data:image\/[a-z.]{3,};base64[^\"\']*)\"[ |>]/i',
		$html,
		$base64_images
	);

	if ( $has_base64_images )
	{
		$base64_replace = array();

		foreach ( (array) $base64_images[1] as $key => $data )
		{
			// Prevent hacking: check base64 images are valid!
			if ( ! CheckBase64Image( $data ) )
			{
				return '';
			}

			$base64_replace[] = 'base64_image' . $key;
		}

		/**
		 * Temporarily remove TinyMCE base64 images.
		 * FJ fix bug preg_replace_callback returns NULL (in Security.php)
		 *
		 * @link http://php.net/manual/en/function.preg-replace-callback.php#98721
		 */
		$html_no_base64 = str_replace(
			$base64_images[1],
			$base64_replace,
			$html
		);
	}
	else
	{
		$html_no_base64 = $html;
	}

	$sanitized_html = $security->xss_clean( $html_no_base64 );

	/**
	 * Convert single quotes to HTML entities
	 *
	 * Fixes bug related to:
	 * replace empty strings ('') with NULL values
	 *
	 * @see DBQuery()
	 */
	$sanitized_html_quotes = str_replace( "'", '&#039;', $sanitized_html );

	if ( $has_base64_images )
	{
		// Replace TinyMCE base64 images.
		$sanitized_html_quotes = str_replace( $base64_replace, $base64_images[1], $sanitized_html_quotes );
	}

	return $sanitized_html_quotes;
}


/**
 * Check base64 encoded images.
 *
 * @since 3.0
 *
 * @uses getimagesizefromstring(), requires PHP 5.4+
 *
 * @param  string $data Base64 encoded image.
 *
 * @return bool         False if not an image.
 */
function CheckBase64Image( $data )
{
	if ( strpos( $data, 'base64' ) !== false )
	{
		$data = substr( $data, ( strpos( $data, 'base64' ) + 6 ) );
	}

	$decoded_data = base64_decode( $data );

	$img = imagecreatefromstring( $decoded_data );

	if ( ! $img )
	{
		return false;
	}

	if ( ! function_exists( 'getimagesizefromstring' ) )
	{
		return true;
	}

	$size = getimagesizefromstring( $decoded_data );

	if ( ! $size
		|| $size[0] == 0
		|| $size[1] == 0
		|| ! $size['mime'] )
	{
		return false;
	}

	return true;
}
