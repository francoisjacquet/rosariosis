<?php
$menu['Eligibility']['admin'] = array(
						'Eligibility/Student.php'=>_('Student Screen'),
						'Eligibility/AddActivity.php'=>_('Add Activity'),
						1=>_('Reports'),
						'Eligibility/StudentList.php'=>_('Student List'),
						'Eligibility/TeacherCompletion.php'=>_('Teacher Completion'),
						2=>_('Setup'),
						'Eligibility/Activities.php'=>_('Activities'),
						'Eligibility/EntryTimes.php'=>_('Entry Times')
					);

$menu['Eligibility']['teacher'] = array(
						'Eligibility/EnterEligibility.php'=>_('Enter Eligibility')
					);

$menu['Eligibility']['parent'] = array(
						'Eligibility/Student.php'=>_('Student Screen'),
						'Eligibility/StudentList.php'=>_('Student List')
					);

$menu['Users']['admin'] += array(
						'Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php'=>_('Enter Eligibility')
					);

$exceptions['Eligibility'] = array(
						'Eligibility/AddActivity.php'=>true
					);

$exceptions['Users'] += array(
						'Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php'=>true
					);
?>