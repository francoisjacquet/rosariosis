<?php
/**
 * Resources module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 */

$menu['Resources']['admin'] = array(
	'title' => _( 'Resources' ),
	'default' => 'Resources/Resources.php',
	'Resources/Resources.php' => _( 'Resources' ),
);

$menu['Resources']['teacher'] = array(
	'title' => _( 'Resources' ),
	'default' => 'Resources/Resources.php',
	'Resources/Resources.php' => _( 'Resources' ),
);

$menu['Resources']['parent'] = $menu['Resources']['teacher'];
