<?php
if ($RosarioModules['Students'])
	$menu['Students']['admin'] += array(
							3=>_('Utilities'),
							'Custom/MyReport.php'=>_('My Report'),
							'Custom/CreateParents.php'=>_('Create Parent Users'),
						);

if ($RosarioModules['Users'])
	$menu['Users']['admin'] += array(
							3=>_('Utilities'),
							'Custom/NotifyParents.php'=>_('Notify Parents'),
						);

if ($RosarioModules['Attendance'])
	$menu['Attendance']['admin'] += array(
							'Custom/AttendanceSummary.php'=>_('Attendance Summary')
						);

if ($RosarioModules['Students'])
	$exceptions['Students'] += array(
							'Custom/CreateParents.php'=>true,
							'Custom/NotifyParents.php'=>true,
						);
?>
