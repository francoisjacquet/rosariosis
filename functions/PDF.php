<?php

/**
 * PDF functions
 *
 * @example $handle = PDFStart();
 *          // echo HTML
 *          PDFStop( $handle );
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
 * @param  array $options PDF options (optional). Defaults to array( 'css' => true, 'margins' => array(), 'mode' => 2 )
 *
 * @return array PDF options
 */
function PDFStart( $options = array() )
{
	$_REQUEST['_ROSARIO_PDF'] = true;

	$default_options = array(
		'css' => true, // Include CSS
		'margins' => array(), // Default margins
		'mode' => 2, // MODE_EMBEDDED
	);

	$pdf_options = array_replace_recursive( $default_options, $options );

	// start buffering
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
 * @param  array  $handle from PDFStart()
 *
 * @return string Full path to file if Save mode, else outputs HTML if not wkhtmltopdf or Embed / Download PDF
 */
function PDFStop( $handle )
{
	global $wkhtmltopdfPath,
		$wkhtmltopdfAssetsPath,
		$RosarioPath;
	
	$handle['orientation'] = $_SESSION['orientation'];
	unset( $_SESSION['orientation'] );

	// get buffer
	$html_content = ob_get_clean();
	
	$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

	// Right to left direction
	$RTL_languages = array( 'ar', 'he', 'dv', 'fa', 'ur' );

	$dir_RTL = in_array( $lang_2_chars, $RTL_languages ) ? ' dir="RTL"' : '';

	// page width
	$page_width = '1024';

	if ( !empty( $handle['orientation'] )
		&& $handle['orientation'] == 'landscape' )
	{
		$page_width = '1448';
	}

	// page title
	$page_title = str_replace( _( 'Print' ) . ' ', '', ProgramTitle() );

	//convert to HTML page with CSS
	$html = '<!doctype html>
		<html lang="' . $lang_2_chars . '" ' . $dir_RTL . '>
		<head>
			<meta charset="UTF-8" />';

	if ( $handle['css'] )
	{
		$html .= '<link rel="stylesheet" type="text/css" href="assets/themes/' . Preferences( 'THEME' ) . '/stylesheet_wkhtmltopdf.css" />';
	}

	// Include Markdown to HTML
	$html .= '<script src="assets/js/showdown/showdown.min.js"></script>';

	// Include wkhtmltopdf Warehouse JS functions
	$html .= '<script src="assets/js/warehouse_wkhtmltopdf.js"></script>';

	//FJ bugfix wkhtmltopdf screen resolution on linux
	//see: https://code.google.com/p/wkhtmltopdf/issues/detail?id=118
	$html .= '<title>' . $page_title . '</title>
		</head>
		<body>
			<div style="width:' . $page_width . 'px" id="pdf">'
			. $html_content
			. '</div>
		</body>
		</html>';

	// create PDF in the temporary files system directory
	$path = sys_get_temp_dir();

	// File name
	$filename = utf8_decode( str_replace(
		array( _( 'Print' ) . ' ', ' ' ),
		array( '', '_' ),
		ProgramTitle()
	));

	//FJ wkhtmltopdf
	if ( !empty( $wkhtmltopdfPath ) )
	{		
		// You can override the Path definition in the config.inc.php file
		if ( !isset( $wkhtmltopdfAssetsPath ) )
		{
			// way wkhtmltopdf accesses the assets/ directory, empty string means no translation
			$wkhtmltopdfAssetsPath = $RosarioPath . 'assets/';
		}

		if ( !empty( $wkhtmltopdfAssetsPath ) )
		{
			$html = str_replace( 'assets/', $wkhtmltopdfAssetsPath, $html );
		}
			
		$html = str_replace( 'modules/', $RosarioPath . 'modules/', $html );
		
		require_once 'classes/Wkhtmltopdf.php';
		
		try {
			//indicate to create PDF in the temporary files system directory
			$wkhtmltopdf = new Wkhtmltopdf( array( 'path' => $path ) );
			
			$wkhtmltopdf->setBinPath( $wkhtmltopdfPath );
			
			if ( Preferences( 'PAGE_SIZE' ) != 'A4' )
			{
				$wkhtmltopdf->setPageSize( Preferences( 'PAGE_SIZE' ) );
			}
				
			if ( !empty( $handle['orientation'] )
				&& $handle['orientation'] == 'landscape' )
			{
				$wkhtmltopdf->setOrientation( Wkhtmltopdf::ORIENTATION_LANDSCAPE );
			}
			
			if ( !empty( $handle['margins'] )
				&& is_array( $handle['margins'] ) )
			{
				$wkhtmltopdf->setMargins( $handle['margins'] );
			}
			
			$wkhtmltopdf->setTitle( utf8_decode( $page_title ) );
			
			//directly pass HTML code
			$wkhtmltopdf->setHtml( $html );

			$wkhtmltopdf->output( $handle['mode'], $filename . '.pdf' );

		} catch ( Exception $e ) {

			echo ErrorMessage( array( $e->getMessage() ) );
		}
	}
	// if no wkhtmltopdf, render in html
	else
	{
		if ( $handle['mode'] === 3 ) // Save
		{
			$base_url = sprintf(
				"%s://%s%s/",
				isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
				$_SERVER['SERVER_NAME'],
				dirname( $_SERVER['PHP_SELF'] )
			);

			// Set Absolute URLs to images, CSS...
			$html = str_replace( 'assets/', $base_url . 'assets/', $html );

			$html = str_replace( 'modules/', $base_url . 'modules/', $html);

			file_put_contents( $path . DIRECTORY_SEPARATOR . $filename . '.html', $html );

			$full_path = $path . DIRECTORY_SEPARATOR . $filename . '.html';
		}
		else
			echo $html;
	}

	if ( $handle['mode'] === 3 ) // Save
	{
		return $full_path;
	}
}
