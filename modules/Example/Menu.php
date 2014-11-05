<?php

/***************************************
 Menu.php file
 Required
 - Menu entries for the Example module
 - Add Menu entries to other modules
 - Override programs from other modules
****************************************/

//menu entries for the Example module
$menu['Example']['admin'] = array( //admin menu
						'default'=>'Example/ExampleWidget.php', //Program loaded by default when menu opened
						'Example/ExampleWidget.php'=>_('Example Widget'),
						1=>_('Setup'), //add sub-menu 1 (only for admins)
						'Example/Setup.php'=>_('Setup'),
					);

$menu['Example']['teacher'] = array( //teacher menu
						'default'=>'Example/ExampleWidget.php', //Program loaded by default when menu opened
						'Example/ExampleWidget.php'=>_('Example Widget'),
					);
$menu['Example']['parent'] = $menu['Example']['teacher']; //parent & student menu

//add Menu entry to the Resources module
if ($RosarioModules['Resources']) //verify Resources module is activated
	$menu['Resources']['admin'] += array(
							1=>_('Example'), //add sub-menu 1
							'Example/ExampleResource.php'=>_('Example Resource'), 
						);

//override Program from Resources modules
if ($RosarioModules['Resources']) //verify Resources module is activated
{
	$Resources_Menu2 = array();
	foreach ($menu['Resources']['admin'] as $key => $value) { //only for the admin menu
		if ($key == 'Resources/Resources.php')
			$Resources_Menu2['Example/Resources.php'] = $value; //replace Resources program
		else
			$Resources_Menu2[$key] = $value;
	}
	$menu['Resources']['admin'] = $Resources_Menu2; //update Resources menu
}

?>
