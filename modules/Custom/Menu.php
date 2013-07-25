<?php
$menu['Students']['admin'] += array(
						3=>_('Utilities'),
						'Custom/MyReport.php'=>_('My Report'),
						'Custom/CreateParents.php'=>_('Create Parent Users'),
					);
$menu['Users']['admin'] += array(
						3=>_('Utilities'),
						'Custom/NotifyParents.php'=>_('Notify Parents'),
					);
$menu['Attendance']['admin'] += array(
						'Custom/AttendanceSummary.php'=>_('Attendance Summary')
					);

$exceptions['Students'] += array(
						'Custom/CreateParents.php'=>true,
						'Custom/NotifyParents.php'=>true,
					);
?>