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
 * @param  array $options PDF options (optional). Defaults to array( 'css' => true, 'margins' => array(), 'mode' => 2 ).
 *
 * @return array PDF options
 */
function PDFStart( $options = array() )
{
	global $pdf_options;

	$_REQUEST['_ROSARIO_PDF'] = true;

	$default_options = array(
		'css' => true, // Include CSS.
		'margins' => array(), // Default margins.
		'mode' => 2, // MODE_EMBEDDED.
	);

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
 * @global string $wkhtmltopdfPath
 * @global string $wkhtmltopdfAssetsPath
 * @global string $RosarioPath
 *
 * @param  array $handle from PDFStart().
 *
 * @return string Full path to file if Save mode, else outputs HTML if not wkhtmltopdf or Embed / Download PDF
 */
function PDFStop( $handle )
{
	global $wkhtmltopdfPath,
		$wkhtmltopdfAssetsPath,
		$RosarioPath;

	static $file_number = 1;

	if ( ! $handle )
	{
		return '';
	}

	$handle['orientation'] = $_SESSION['orientation'];

	unset( $_SESSION['orientation'] );

	// Get buffer.
	$html_content = ob_get_clean();

	$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

	// Right to left direction.
	$RTL_languages = array( 'ar', 'he', 'dv', 'fa', 'ur' );

	$dir_RTL = in_array( $lang_2_chars, $RTL_languages ) ? ' dir="RTL"' : '';

	// Page width.
	$page_width = '994'; // Originally 1024px.

	if ( ! empty( $handle['orientation'] )
		&& $handle['orientation'] === 'landscape' )
	{
		$page_width = '1405'; // Originally 1448px.
	}

	// Page title.
	$page_title = str_replace( _( 'Print' ) . ' ', '', ProgramTitle() );

	$_html = array();

	// Convert to HTML page with CSS.
	$_html['head'] = '<!doctype html>
		<html lang="' . $lang_2_chars . '" ' . $dir_RTL . '>
		<head>
			<meta charset="UTF-8" />';

	if ( $handle['css'] )
	{
		$_html['head'] .= '<link rel="stylesheet" type="text/css" href="assets/themes/' . Preferences( 'THEME' ) . '/stylesheet_wkhtmltopdf.css" />';
	}

	// Include Markdown to HTML.
	$_html['head'] .= '<script src="assets/js/showdown/showdown.min.js"></script>';

	// Include wkhtmltopdf Warehouse JS functions.
	$_html['head'] .= '<script src="assets/js/warehouse_wkhtmltopdf.js"></script>';

	// FJ bugfix wkhtmltopdf screen resolution on linux
	// see: https://code.google.com/p/wkhtmltopdf/issues/detail?id=118
	$_html['head'] .= '<title>' . $page_title . '</title>
		</head>
		<body>
			<div style="width:' . $page_width . 'px" id="pdf">';

	$_html['foot'] = '</div>
		</body>
		</html>';

	$html = $_html['head'] . $html_content . $_html['foot'];

	// Create PDF in the temporary files system directory.
	$path = sys_get_temp_dir();

	// File name.
	$filename = utf8_decode( str_replace(
		array( _( 'Print' ) . ' ', ' ' ),
		array( '', '_' ),
		ProgramTitle()
	)) . ( $file_number++ );

	// FJ wkhtmltopdf.
	if ( ! empty( $wkhtmltopdfPath ) )
	{
		// You can override the Path definition in the config.inc.php file.
		if ( ! isset( $wkhtmltopdfAssetsPath ) )
		{
			// Way wkhtmltopdf accesses the assets/ directory, empty string means no translation.
			$wkhtmltopdfAssetsPath = $RosarioPath . 'assets/';
		}

		if ( ! empty( $wkhtmltopdfAssetsPath ) )
		{
			// Fix wkhtmltopdf error on Windows: prepend file:///.
			$html = str_replace( '"assets/', '"file:///' . $wkhtmltopdfAssetsPath, $html );

			$_html['head'] = str_replace( '"assets/', '"file:///' . $wkhtmltopdfAssetsPath, $_html['head'] );
		}

		// Fix wkhtmltopdf error on Windows: prepend file:///.
		$html = str_replace( '"modules/', '"file:///' . $RosarioPath . 'modules/', $html );

		require_once 'classes/Wkhtmltopdf.php';

		try {
			// Indicate to create PDF in the temporary files system directory.
			$wkhtmltopdf = new Wkhtmltopdf( array( 'path' => $path ) );

			$wkhtmltopdf->setBinPath( $wkhtmltopdfPath );

			if ( Preferences( 'PAGE_SIZE' ) != 'A4' )
			{
				$wkhtmltopdf->setPageSize( Preferences( 'PAGE_SIZE' ) );
			}

			if ( ! empty( $handle['orientation'] )
				&& $handle['orientation'] === 'landscape' )
			{
				$wkhtmltopdf->setOrientation( Wkhtmltopdf::ORIENTATION_LANDSCAPE );
			}

			if ( ! empty( $handle['margins'] )
				&& is_array( $handle['margins'] ) )
			{
				$wkhtmltopdf->setMargins( $handle['margins'] );
			}

			if ( isset( $handle['header_html'] )
				&& $handle['header_html'] )
			{
				$header_html = $handle['header_html'];

				if ( mb_stripos( $header_html, '<html' ) === false )
				{
					// Build full HMTL page.
					// Fix HTML header not showing, remove CSS width & height 100%.
					$header_html = str_replace(
						'<html',
						'<html style="width: auto; height: auto;"',
						$_html['head']
						) .	$header_html . $_html['foot'];
				}

				$wkhtmltopdf->setHeaderHtml( $header_html );
			}

			if ( isset( $handle['footer_html'] )
				&& $handle['footer_html'] )
			{
				$footer_html = $handle['footer_html'];

				if ( mb_stripos( $footer_html, '<html' ) === false )
				{
					// Build full HMTL page.
					// Fix HTML footer, remove CSS width & height 100%.
					$footer_html = str_replace(
						'<html',
						'<html style="width: auto; height: auto;"',
						$_html['head']
						) .	$footer_html . $_html['foot'];
				}

				$wkhtmltopdf->setFooterHtml( $footer_html );
			}

			$wkhtmltopdf->setTitle( utf8_decode( $page_title ) );

			// Directly pass HTML code.
			$wkhtmltopdf->setHtml( $html );

			$wkhtmltopdf->output( $handle['mode'], $filename . '.pdf' );

			$full_path = $path . DIRECTORY_SEPARATOR . $filename . '.pdf';

		} catch ( Exception $e ) {

			echo ErrorMessage( array( $e->getMessage() ) );
		}
	}
	// If no wkhtmltopdf, render in html.
	else
	{
		if ( $handle['mode'] === 3 ) // Save.
		{
			$base_url = sprintf(
				'%s://%s%s/',
				isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
				$_SERVER['SERVER_NAME'],
				dirname( $_SERVER['PHP_SELF'] )
			);

			// Set Absolute URLs to images, CSS...
			$html = str_replace( '"assets/', '"' . $base_url . 'assets/', $html );

			$html = str_replace( '"modules/', '"' . $base_url . 'modules/', $html );

			file_put_contents( $path . DIRECTORY_SEPARATOR . $filename . '.html', $html );

			$full_path = $path . DIRECTORY_SEPARATOR . $filename . '.html';
		}
		else
			echo $html;
	}

	if ( $handle['mode'] === 3 ) // Save.
	{
		return $full_path;
	}
}
