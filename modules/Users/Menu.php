<?php
$menu['Users']['admin'] = array(
						'Users/User.php'=>_('User Info'),
						'Users/User.php&staff_id=new'=>_('Add a User'),
						'Users/AddStudents.php'=>_('Associate Students with Parents'),
						'Users/Preferences.php'=>_('My Preferences'),
						1=>_('Setup'),
						'Users/Profiles.php'=>_('User Profiles'),
						'Users/Exceptions.php'=>_('User Permissions'),
						'Users/UserFields.php'=>_('User Fields'),
						2=>_('Teacher Programs'),
					);

$menu['Users']['teacher'] = array(
						'Users/User.php'=>_('General Info'),
						'Users/Preferences.php'=>_('My Preferences')
					);

$menu['Users']['parent'] = array(
						'Users/User.php'=>_('General Info'),
						'Users/Preferences.php'=>_('My Preferences')
					);
$exceptions['Users'] = array(
						'Users/User.php&staff_id=new'=>true
					);
?>
