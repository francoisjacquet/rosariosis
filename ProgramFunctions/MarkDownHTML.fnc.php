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
 *          $_REQUEST['values']['textarea'] = DBEscapeString( SanitizeMarkDown( $_POST['values']['textarea'] ) );
 *
 * @since   2.9
 * @since   4.3 Prevent XSS.
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
			echo 'Sanitized HTML:<br>';
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
		$html_sanitized_md = $markdownify->parseString( $sanitized_html );

		// Prevent XSS: Sanitize the newly created MarkDown text.
		$return = $security->xss_clean( $html_sanitized_md );
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
 * TinyMCE now accepts image upload.
 * Uploaded images are encoded in base64.
 * This function also saves the images to $image_path.
 *
 * @see     assets/js/tinymce/
 * @uses    Security class
 * @uses    UploadImage()
 *
 * @example require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
 *          $_REQUEST['values']['textarea'] = DBEscapeString( SanitizeHTML( $_POST['values']['textarea'] ) );
 *
 * @example SaveTemplate( DBEscapeString( SanitizeHTML( $_POST['email_text'], '', true ) ) );
 *          $email_text_template = GetTemplate();
 *
 * @since 2.9
 * @since 5.5.3 Better base64 images detection.
 * @since 8.3   Add RosarioSIS URL to image path.
 *
 * @global object $security
 *
 * @param  string $html                  HTML text.
 * @param  string $image_path            Path where to upload base64 images. Defaults to "assets/FileUploads/[Syear]/[staff_or_student_ID]/" (optional).
 * @param  bool   $add_url_to_image_path Add RosarioSIS URL to image path. Useful when HTML used in email to display remote images.
 *
 * @return string Sanitized input with HTML encoded single quotes
 */
function SanitizeHTML( $html, $image_path = '', $add_url_to_image_path = false )
{
	global $security;

	if ( ! is_string( $html )
		|| empty( $html ) )
	{
		return $html;
	}

	// Create $security object once.
	if ( ! ( $security instanceof Security ) )
	{
		require_once 'classes/Security.php';

		$security = new Security();
	}

	$has_base64_images = preg_match_all(
		'/src=\"(data:image\/[a-z.\+]{3,};base64[^\"\']*)\"[ |>]/i',
		$html,
		$base64_images
	);

	if ( $has_base64_images )
	{
		$base64_replace = [];

		foreach ( (array) $base64_images[1] as $key => $data )
		{
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

	if ( ! $has_base64_images )
	{
		if ( ROSARIO_DEBUG
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			echo 'Sanitized HTML:<br>';
			var_dump( $sanitized_html_quotes );
		}

		return $sanitized_html_quotes;
	}

	require_once 'ProgramFunctions/FileUpload.fnc.php';

	// Upload base64 images.
	foreach ( (array) $base64_images[1] as $key => $data )
	{
		// Get width & height attr if any.
		$img_tag = mb_substr( $html_no_base64, strpos( $html_no_base64, $base64_replace[ $key ] ) );

		$img_tag = mb_substr( $img_tag, 0, strpos( $img_tag, ' />' ) );

		$target_dim = [];

		$target_width_pos = strpos( $img_tag, 'width="' );

		if ( $target_width_pos )
		{
			$target_dim['width'] = mb_substr(
				$img_tag,
				$target_width_pos + 7,
				strpos( mb_substr( $img_tag, $target_width_pos + 7 ), '"' )
			);
		}

		$target_height_pos = strpos( $img_tag, 'height="' );

		if ( $target_height_pos )
		{
			$target_dim['height'] = mb_substr(
				$img_tag,
				$target_height_pos + 8,
				strpos( mb_substr( $img_tag, $target_height_pos + 8 ), '"' )
			);
		}

		$image_path = ImageUpload( $data, $target_dim, $image_path );

		if ( $add_url_to_image_path )
		{
			// Add URL to image path.
			$image_path = RosarioURL() . $image_path;
		}

		$base64_images[1][ $key ] = $image_path;
	}

	// Replace TinyMCE base64 images.
	$sanitized_html_quotes = str_replace( $base64_replace, $base64_images[1], $sanitized_html_quotes );

	if ( ROSARIO_DEBUG
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		if ( function_exists( 'd' ) )
		{
			// Dump using Kint.
			d( $sanitized_html_quotes );
		}
		else
		{
			echo 'Sanitized HTML:<br>';
			var_dump( $sanitized_html_quotes );
		}
	}

	return $sanitized_html_quotes;
}

