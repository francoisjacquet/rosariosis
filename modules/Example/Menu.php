<?php

/***********************************************************
 Menu.php file
 Required
 - Menu entries for the Example module
 - Add Menu entries to other modules
 - Override programs from other modules
   Note: overriding programs could create incompatibilities
   between 2 modules overriding the same program.
   Please prefer adding a menu entry instead.
***********************************************************/

//use dgettext() function instead of _() for Module specific strings translation
//see locale/README file for more information
$module_name = dgettext('Example', 'Example');

//menu entries for the Example module
$menu['Example']['admin'] = array( //admin menu
						'default'=>'Example/ExampleWidget.php', //Program loaded by default when menu opened
						'Example/ExampleWidget.php'=>dgettext('Example', 'Example Widget'),
						1=>dgettext('Example', 'Setup'), //add sub-menu 1 (only for admins)
						'Example/Setup.php'=>dgettext('Example', 'Setup'),
					);

$menu['Example']['teacher'] = array( //teacher menu
						'default'=>'Example/ExampleWidget.php', //Program loaded by default when menu opened
						'Example/ExampleWidget.php'=>dgettext('Example', 'Example Widget'),
					);
$menu['Example']['parent'] = $menu['Example']['teacher']; //parent & student menu

//add a Menu entry to the Resources module
if ($RosarioModules['Resources']) //verify Resources module is activated
	$menu['Resources']['admin'] += array(
							1=>dgettext('Example', 'Example'), //add sub-menu 1
							'Example/ExampleResource.php'=>dgettext('Example', 'Example Resource'), 
						);

//override a Program from Resources modules
if ($RosarioModules['Resources']) //verify Resources module is activated
{
	$Resources_Menu2 = array();
	foreach ($menu['Resources']['admin'] as $key => $value) { //only for the admin menu
		if ($value == 'Resources/Resources.php')
			$Resources_Menu2['default'] = 'Example/Resources.php'; //Resources.php is default for Resources module so replace Default program too
		else
		{
			if ($key == 'Resources/Resources.php')
				$Resources_Menu2['Example/Resources.php'] = $value; //replace Resources program
			else
				$Resources_Menu2[$key] = $value;
		}
	}
	$menu['Resources']['admin'] = $Resources_Menu2; //update Resources menu
}

?>
