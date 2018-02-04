<?php
/**
 * Implementation for PHP gettext extension functions not included by default.
 *
 * @since 3.8
 *
 * @copyright PhpMyAdmin
 *
 * @link https://github.com/phpmyadmin/motranslator
 *
 * @package RosarioSIS
 * @subpackage functions
 */

require_once 'classes/MoTranslator/Loader.php';
require_once 'classes/MoTranslator/StringReader.php';
require_once 'classes/MoTranslator/Translator.php';

if ( ! function_exists( 'gettext' ) )
{
	// Load compatibility layer.
	PhpMyAdmin\MoTranslator\Loader::loadFunctions();
}
