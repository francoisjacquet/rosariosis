<?php
/**
 * PDF functions
 *
 * @example $handle = PDFStart();
 *          // echo HTML
 *          PDFStop( $handle );
 *
 * @package RosarioSIS
 * @subpackage functions
 */


/**
 * Start buffer and set PDF options
 *
 * Note: for landscape format, set $_SESSION['orientation'] = 'landscape'
 *
 * Modes: 2, MODE_EMBEDDED (default) | 3, MODE_SAVE | 0, MODE_DOWNLOAD
 *
 * @example $pdf_options = array( 'css' => false, 'margins' => array( 'top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0) 'mode' => 3 );
 *          PDFStart( $pdf_options );
 *
 * @param  array $options PDF options (optional). Defaults see $default_options.
 *
 * @return array PDF options
 */
function PDFStart( $options = [] )
{
	global $pdf_options;

	$_REQUEST['_ROSARIO_PDF'] = true;

	$default_options = [
		'css' => true, // Include CSS.
		'margins' => [], // Default margins.
		'mode' => 2, // MODE_EMBEDDED.
        'header_html' => '', // No HTML header.
        'footer_html' => '', // No HTML footer.
        'orientation' => '', // Portrait, can be set to 'landscape'.
	];

	$pdf_options = array_replace_recursive( $default_options, (array) $options );

	// Do hook.
	do_action( 'functions/PDF.php|pdf_start' );

	// Start buffering.
	ob_start();

	return $pdf_options;
}


/**
 * Get buffer and generate PDF
 * Renders HTML if not wkhtmltopdf
 *
 * @since 3.4 Handle HTML header & footer.
 * @since 4.3 CSS Add .wkhtmltopdf-header, .wkhtmltopdf-footer, .wkhtmltopdf-portrait & .wkhtmltopdf-landscape classes
 * @since 7.5 Use phpwkhtmltopdf class instead of Wkhtmltopdf (more reliable & faster)
 * @since 10.9 CSS Add modname class, ie .modname-grades-reportcards-php for modname=Grades/ReportCards.php
 * @since 11.2 Security remove $wkhtmltopdfAssetsPath & --enable-local-file-access, use base URL instead
 *
 * @link https://github.com/mikehaertl/phpwkhtmltopdf
 *
 * @global string $wkhtmltopdfPath
 *
 * @param  array $handle from PDFStart(), PDF options.
 *
 * @return string Full path to file if Save mode, else outputs HTML if not wkhtmltopdf or Embed / Download PDF
 */
function PDFStop( $handle )
{
	global $wkhtmltopdfPath;

	static $file_number = 1;

	if ( ! $handle )
	{
		return '';
	}

	$handle['orientation'] = empty( $_SESSION['orientation'] ) ? $handle['orientation'] : $_SESSION['orientation'];

	unset( $_SESSION['orientation'] );

	// Get buffer.
	$html_content = ob_get_clean();

	$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

	// Right to left direction.
	$RTL_languages = [ 'ar', 'he', 'dv', 'fa', 'ur', 'ps' ];

	$dir_RTL = in_array( $lang_2_chars, $RTL_languages ) ? ' dir="RTL"' : '';

	// Page width.
	// @see wkhtmltopdf.css.
	$orientation_class = 'wkhtmltopdf-portrait'; // 994px, originally 1024px.

	if ( $handle['orientation'] === 'landscape' )
	{
		$orientation_class = 'wkhtmltopdf-landscape'; // 1405px, originally 1448px.
	}

	$modname_class = '';

	if ( $_REQUEST['modname'] )
	{
		$modname_class = 'modname-' . mb_strtolower( preg_replace(
			'/([^\-a-z0-9]+)/i',
			'-',
			$_REQUEST['modname']
		) );
	}

	// Page title.
	$page_title = str_replace( _( 'Print' ) . ' ', '', ProgramTitle() );

	$_html = [];

	// Convert to HTML page with CSS.
	$_html['head'] = '<!doctype html>
		<html lang="' . $lang_2_chars . '" ' . $dir_RTL . '>
		<head>
			<meta charset="UTF-8">
			<base href="' . RosarioURL() . '" />';

	if ( $handle['css'] )
	{
		$_html['head'] .= '<link rel="stylesheet" type="text/css" href="assets/themes/' . Preferences( 'THEME' ) . '/stylesheet_wkhtmltopdf.css">';
	}

	// Include Markdown to HTML.
	// @since 6.0 JS MarkDown use marked instead of showdown (15KB smaller).
	$_html['head'] .= '<script src="assets/js/marked/marked.min.js"></script>';

	// Include wkhtmltopdf Warehouse JS functions.
	$_html['head'] .= '<script src="assets/js/warehouse_wkhtmltopdf.js"></script>';

	// FJ bugfix wkhtmltopdf screen resolution on linux
	// see: https://code.google.com/p/wkhtmltopdf/issues/detail?id=118
	$_html['head'] .= '<title>' . $page_title . '</title>
		</head>
		<body>
			<div class="wkhtmltopdf-body-wrapper ' . $orientation_class . ' ' . $modname_class . '" id="pdf">';

	$_html['foot'] = '</div>
		</body>
		</html>';

	$html = $_html['head'] . $html_content . $_html['foot'];

	// Create PDF in the temporary files system directory.
	$path = sys_get_temp_dir();

	// File name.
	// Fix PHP8.2 utf8_decode() function deprecated
	// Decode UTF8 is useful for Windows only.
	$filename = iconv(
		'UTF-8',
		'ISO-8859-1',
		str_replace(
			[ _( 'Print' ) . ' ', ' ' ],
			[ '', '_' ],
			ProgramTitle()
		)
	) . ( $file_number++ );

	if ( empty( $wkhtmltopdfPath ) )
	{
		// If no wkhtmltopdf, render in HTML.
		if ( $handle['mode'] !== 3 ) // Display HTML.
		{
			echo $html;

			return '';
		}

		// Save.
		file_put_contents( $path . DIRECTORY_SEPARATOR . $filename . '.html', $html );

		return $path . DIRECTORY_SEPARATOR . $filename . '.html';
	}

	// Load phpwkhtmltopdf class.
	require_once 'classes/phpwkhtmltopdf/php-shellcommand/Command.php';
	require_once 'classes/phpwkhtmltopdf/php-tmpfile/File.php';
	require_once 'classes/phpwkhtmltopdf/Command.php';
	require_once 'classes/phpwkhtmltopdf/Pdf.php';

	// Set wkhtmltopdf options.
	$pdf_options = [
		'title' => $page_title,
	];

	if ( Preferences( 'PAGE_SIZE' ) != 'A4' )
	{
		$pdf_options['page-size'] = Preferences( 'PAGE_SIZE' );
	}

	if ( ! empty( $handle['orientation'] )
		&& $handle['orientation'] === 'landscape' )
	{
		$pdf_options['orientation'] = 'Landscape';
	}

	if ( ! empty( $handle['margins'] )
		&& is_array( $handle['margins'] ) )
	{
		foreach ( $handle['margins'] as $position => $margin )
		{
			if ( is_null( $margin ) )
			{
				continue;
			}

			$pdf_options['margin-' . $position] = $margin;
		}
	}

	if ( $handle['header_html'] )
	{
		$header_html = $handle['header_html'];

		if ( mb_stripos( $header_html, '<html' ) === false )
		{
			// Build full HMTL page.
			// Fix HTML header not showing, remove CSS width & height 100%.
			$header_html = str_replace(
				'<html',
				'<html class="wkhtmltopdf-header"',
				$_html['head']
			) .
			$header_html . $_html['foot'];
		}

		$pdf_options['header-html'] = $header_html;
	}

	if ( $handle['footer_html'] )
	{
		$footer_html = $handle['footer_html'];

		if ( mb_stripos( $footer_html, '<html' ) === false )
		{
			// Build full HMTL page.
			// Fix HTML footer, remove CSS width & height 100%.
			$footer_html = str_replace(
				'<html',
				'<html class="wkhtmltopdf-footer"',
				$_html['head']
			) .
			$footer_html . $_html['foot'];
		}

		$pdf_options['footer-html'] = $footer_html;
	}

	$pdf = new mikehaertl\wkhtmlto\Pdf( $pdf_options );

	if ( ! function_exists( 'proc_open' ) )
	{
		// @since 8.8 Fix proc_open() PHP function not allowed.
		// Use `exec()` instead of `proc_open()`.
		$pdf->commandOptions['useExec'] = true;

		if ( ! function_exists( 'exec' ) )
		{
			// exec() PHP function not allowed either, error.
			echo ErrorMessage( [ 'proc_open and exec PHP functions are disabled. Cannot call wkhtmltopdf and generate PDF. Contact your server administrator for more information.' ] );

			return '';
		}
	}

	$pdf->binary = $wkhtmltopdfPath;

	$pdf->addPage( $html );

	if ( $handle['mode'] === 3 ) // Save.
	{
		$full_path = $path . DIRECTORY_SEPARATOR . $filename . '.pdf';

		// Save the PDF.
		if ( ! $pdf->saveAs( $full_path ) )
		{
			echo ErrorMessage( [ $pdf->getError() ] );
		}

		return $full_path;
	}

	// Send to client as file download.
	if ( ! $pdf->send( $filename . '.pdf', (bool) $handle['mode'] ) ) // Embed or Download.
	{
		echo ErrorMessage( [ $pdf->getError() ] );
	}

	return '';
}
