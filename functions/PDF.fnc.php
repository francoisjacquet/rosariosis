<?php

//modif Francois: wkhtmltopdf
//@param $css = true
//include theme CSS in HTML output
//@param $margins = array('top'=> 1, 'bottom'=> 1, 'left'=> 1, 'right'=> 1)
//margins unit in millimeters
//Note: for landscape format, set $_SESSION['orientation'] = 'landscape'
function PDFStart($css = true, $margins = array())
{
	$_REQUEST['_ROSARIO_PDF'] = true;
	$pdfitems['css'] = $css;
	$pdfitems['margins'] = $margins;
	ob_start();
	return $pdfitems;
}

function PDFStop($handle)
{	//global $htmldocPath,$htmldocAssetsPath;
	global $wkhtmltopdfPath,$wkhtmltopdfAssetsPath,$locale;
	
	$handle['orientation'] = $_SESSION['orientation'];
	unset($_SESSION['orientation']);

	$html_content = ob_get_clean();
	
	//convert to HTML page with CSS		
	$html = '<!DOCTYPE html><HTML lang="'.mb_substr($locale,0,2).'" '.(mb_substr($locale,0,2)=='he' || mb_substr($locale,0,2)=='ar'?' dir="RTL"':'').'><HEAD><meta charset="UTF-8" />';
	if ($handle['css'])
		$html .= '<link rel="stylesheet" type="text/css" href="assets/themes/'.Preferences('THEME').'/stylesheet_wkhtmltopdf.css" />';
	//modif Francois: bugfix wkhtmltopdf screen resolution on linux
	//see: https://code.google.com/p/wkhtmltopdf/issues/detail?id=118
	$html .= '<TITLE>'.str_replace(_('Print').' ','',ProgramTitle()).'</TITLE></HEAD><BODY><div style="width:'.((!empty($handle['orientation']) && $handle['orientation'] == 'landscape') ? '1448' : '1024').'px" id="pdf">'.$html_content.'</div></BODY></HTML>';

	//modif Francois: wkhtmltopdf
	if (!empty($wkhtmltopdfPath))
	{		
		if(!empty($wkhtmltopdfAssetsPath))
			$html = str_replace('assets/', $wkhtmltopdfAssetsPath, $html);
		
		require('classes/Wkhtmltopdf.php');
		
		try {
			//indicate to create PDF in the temporary files system directory
			$wkhtmltopdf = new Wkhtmltopdf(array('path' => sys_get_temp_dir()));
			
			$wkhtmltopdf->setBinPath($wkhtmltopdfPath);
			
			if (!empty($handle['orientation']) && $handle['orientation'] == 'landscape')
				$wkhtmltopdf->setOrientation(Wkhtmltopdf::ORIENTATION_LANDSCAPE);
			
			if (!empty($handle['margins']) && is_array($handle['margins']))
				$wkhtmltopdf->setMargins($handle['margins']);
			
			$wkhtmltopdf->setTitle(utf8_decode(str_replace(_('Print').' ','',ProgramTitle())));
			
			//directly pass HTML code
			$wkhtmltopdf->setHtml($html);
			
			//MODE_EMBEDDED displays PDF in browser, MODE_DOWNLOAD forces PDF download
			//modif Francois: force PDF DOWNLOAD for Android mobile & tablet
			if (mb_stripos($_SERVER['HTTP_USER_AGENT'],'android') !== false)
				$wkhtmltopdf->output(Wkhtmltopdf::MODE_DOWNLOAD, str_replace(array(_('Print').' ', ' '),array('', '_'),utf8_decode(ProgramTitle())).'.PDF');
			else
				$wkhtmltopdf->output(Wkhtmltopdf::MODE_EMBEDDED, str_replace(array(_('Print').' ', ' '),array('', '_'),utf8_decode(ProgramTitle())).'.pdf');
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	else
		echo $html;
}
?>