<?php

//FJ wkhtmltopdf
//@param $options['css'] = true
//include theme CSS in HTML output
//@param $options['margins'] = array( 'top' => 1, 'bottom' => 1, 'left' => 1, 'right' => 1 )
//margins unit in millimeters
//@mode $options['mode'] = 2 (MODE_EMBEDDED) | 3 (MODE_SAVE) | 0 (MODE_DOWNLOAD)
//Note: for landscape format, set $_SESSION['orientation'] = 'landscape'
function PDFStart( $options = array() )
{
	$_REQUEST['_ROSARIO_PDF'] = true;

	$default_options = array(
		'css' => true,
		'margins' => array(),
		'mode' => 2, // MODE_EMBEDDED
	);

	$pdf_options = array_replace_recursive( $default_options, (array) $options );

	ob_start();

	return $pdf_options;
}

function PDFStop($handle)
{
	global $wkhtmltopdfPath,$wkhtmltopdfAssetsPath,$RosarioPath,$locale;
	
	static $file_number;

	$handle['orientation'] = $_SESSION['orientation'];
	unset($_SESSION['orientation']);

	$html_content = ob_get_clean();
	
	//convert to HTML page with CSS		
	$RTL_languages = array('ar', 'he', 'dv', 'fa', 'ur');
	$html = '<!DOCTYPE html><HTML lang="'.mb_substr($locale,0,2).'" '.(in_array(mb_substr($locale,0,2), $RTL_languages)?' dir="RTL"':'').'><HEAD><meta charset="UTF-8" />';

	if ($handle['css'])
		$html .= '<link rel="stylesheet" type="text/css" href="assets/themes/'.Preferences('THEME').'/stylesheet_wkhtmltopdf.css" />';

	//FJ bugfix wkhtmltopdf screen resolution on linux
	//see: https://code.google.com/p/wkhtmltopdf/issues/detail?id=118
	$html .= '<TITLE>'.str_replace(_('Print').' ','',ProgramTitle()).'</TITLE></HEAD><BODY><div style="width:'.((!empty($handle['orientation']) && $handle['orientation'] == 'landscape') ? '1448' : '1024').'px" id="pdf">'.$html_content.'</div></BODY></HTML>';

	// create PDF in the temporary files system directory
	$path = sys_get_temp_dir();

	// File name
	$filename = utf8_decode( str_replace(
		array( _( 'Print' ) . ' ', ' ' ),
		array( '', '_' ),
		ProgramTitle()
	)) . ( $file_number++ );

	//FJ wkhtmltopdf
	if (!empty($wkhtmltopdfPath))
	{		
		// You can override the Path definition in the config.inc.php file
		if (!isset($wkhtmltopdfAssetsPath))
			$wkhtmltopdfAssetsPath = $RosarioPath.'assets/'; // way wkhtmltopdf accesses the assets/ directory, empty string means no translation

		if(!empty($wkhtmltopdfAssetsPath))
			$html = str_replace('assets/', $wkhtmltopdfAssetsPath, $html);

		$html = str_replace('modules/', $RosarioPath.'modules/', $html);

		require_once('classes/Wkhtmltopdf.php');
		
		try{
			$wkhtmltopdf = new Wkhtmltopdf( array( 'path' => $path ) );
			
			$wkhtmltopdf->setBinPath($wkhtmltopdfPath);
			
			if (Preferences('PAGE_SIZE') != 'A4')
				$wkhtmltopdf->setPageSize(Preferences('PAGE_SIZE'));
				
			if (!empty($handle['orientation']) && $handle['orientation'] == 'landscape')
				$wkhtmltopdf->setOrientation(Wkhtmltopdf::ORIENTATION_LANDSCAPE);
			
			if (!empty($handle['margins']) && is_array($handle['margins']))
				$wkhtmltopdf->setMargins($handle['margins']);
			
			$wkhtmltopdf->setTitle(utf8_decode(str_replace(_('Print').' ','',ProgramTitle())));
			
			//directly pass HTML code
			$wkhtmltopdf->setHtml($html);
			
			$wkhtmltopdf->output( $handle['mode'], $filename . '.pdf' );

			$full_path = $path . DIRECTORY_SEPARATOR . $filename . '.pdf';

		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
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
