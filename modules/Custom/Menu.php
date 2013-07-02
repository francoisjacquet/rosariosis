<?php
$menu['Students']['admin'] += array(
						3=>_('Utilities'),
						'Custom/MyReport.php'=>_('My Report'),
						'Custom/CreateParents.php'=>_('Create Parent Users'),
					);
$menu['Attendance']['admin'] += array(
						'Custom/AttendanceSummary.php'=>_('Attendance Summary')
					);

$exceptions['Students'] += array(
						'Custom/CreateParents.php'=>true
					);
?>