<?php

/**
 * @example  $handle = PDFStart(); // print HTML PDFStop( $handle );
 */


/**
 * Start buffer and set PDF options
 *
 * Note: for landscape format, set $_SESSION['orientation'] = 'landscape'
 *
 * @param  boolean $css     include theme CSS in HTML output (optional)
 * @param  array   $margins margins unit in millimeters (optional)
 *
 * @return array   PDF options
 */
function PDFStart( $css = true, $margins = array() )
{
	$handle = array();

	$_REQUEST['_ROSARIO_PDF'] = true;

	$handle['css'] = $css;

	$handle['margins'] = $margins;

	// start buffering
	ob_start();

	return $handle;
}


/**
 * Get buffer and generate PDF
 *
 * @global string $wkhtmltopdfPath
 * @global string $wkhtmltopdfAssetsPath
 * @global string $RosarioPath
 *
 * @param  array  $handle                from PDFStart()
 *
 * @return string outputs HTML if not wkhtmltopdf or embed PDF
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
		$page_width = '1448';

	// page title
	$page_title = str_replace( _( 'Print' ) . ' ', '', ProgramTitle() );

	//convert to HTML page with CSS
	$html = '<!doctype html>
		<html lang="' . $lang_2_chars . '" '.$dir_RTL.'>
		<head>
			<meta charset="UTF-8" />';

	if ( $handle['css'] )
		$html .= '<link rel="stylesheet" type="text/css" href="assets/themes/' . Preferences( 'THEME' ).'/stylesheet_wkhtmltopdf.css" />';

	$html .= '<script src="assets/js/showdown/showdown.min.js"></script>';
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

	//FJ wkhtmltopdf
	if ( !empty( $wkhtmltopdfPath ) )
	{		
		// You can override the Path definition in the config.inc.php file
		if ( !isset( $wkhtmltopdfAssetsPath ) )
			// way wkhtmltopdf accesses the assets/ directory, empty string means no translation
			$wkhtmltopdfAssetsPath = $RosarioPath . 'assets/';

		if ( !empty( $wkhtmltopdfAssetsPath ) )
			$html = str_replace( 'assets/', $wkhtmltopdfAssetsPath, $html );
			
		$html = str_replace( 'modules/', $RosarioPath . 'modules/', $html );
		
		require_once 'classes/Wkhtmltopdf.php';
		
		try {
			//indicate to create PDF in the temporary files system directory
			$wkhtmltopdf = new Wkhtmltopdf( array( 'path' => sys_get_temp_dir() ) );
			
			$wkhtmltopdf->setBinPath( $wkhtmltopdfPath );
			
			if ( Preferences( 'PAGE_SIZE' ) != 'A4' )
				$wkhtmltopdf->setPageSize( Preferences( 'PAGE_SIZE' ) );
				
			if ( !empty( $handle['orientation'] )
				&& $handle['orientation'] == 'landscape' )
				$wkhtmltopdf->setOrientation( Wkhtmltopdf::ORIENTATION_LANDSCAPE );
			
			if ( !empty( $handle['margins'] )
				&& is_array( $handle['margins'] ) )
				$wkhtmltopdf->setMargins( $handle['margins'] );
			
			$wkhtmltopdf->setTitle( utf8_decode( $page_title ) );
			
			//directly pass HTML code
			$wkhtmltopdf->setHtml( $html );
			
			$file_name = str_replace(
					array( _( 'Print' ) . ' ', ' ' ),
					array( '', '_' ),
					utf8_decode( ProgramTitle() )
				) . '.pdf';

			//MODE_EMBEDDED displays PDF in browser, MODE_DOWNLOAD forces PDF download
			$wkhtmltopdf->output( Wkhtmltopdf::MODE_EMBEDDED, $file_name );

		} catch ( Exception $e ) {

			echo ErrorMessage( array( $e->getMessage() ) );
		}
	}
	// if no wkhtmltopdf, render in html
	else
		echo $html;
}
