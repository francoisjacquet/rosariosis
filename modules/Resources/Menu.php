<?php
/**
 * Resources module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 * 
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Resources']['admin'] = [
	'title' => _( 'Resources' ),
	'default' => 'Resources/Resources.php',
	'Resources/Resources.php' => _( 'Resources' ),
];

$menu['Resources']['teacher'] = [
	'title' => _( 'Resources' ),
	'default' => 'Resources/Resources.php',
	'Resources/Resources.php' => _( 'Resources' ),
];

$menu['Resources']['parent'] = $menu['Resources']['teacher'];
