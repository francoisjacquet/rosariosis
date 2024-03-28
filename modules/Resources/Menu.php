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
] + issetVal( $menu['Resources']['admin'], [] );

$menu['Resources']['teacher'] = [
	'title' => _( 'Resources' ),
	'default' => 'Resources/Resources.php',
	'Resources/Resources.php' => _( 'Resources' ),
] + issetVal( $menu['Resources']['teacher'], [] );

$menu['Resources']['parent'] = $menu['Resources']['teacher'] + issetVal( $menu['Resources']['parent'], [] );
