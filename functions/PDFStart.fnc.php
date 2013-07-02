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
	$pdfitems['orientation'] = $_SESSION['orientation'];
	unset($_SESSION['orientation']);
	ob_start();
	return $pdfitems;
}
?>