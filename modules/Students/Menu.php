<?php
$menu['Students']['admin'] = array(
						'Students/Student.php'=>_('Student Info'),
						'Students/Student.php&include=General_Info&student_id=new'=>_('Add a Student'),
						'Students/AssignOtherInfo.php'=>_('Group Assign Student Info'),
						'Students/AddUsers.php'=>_('Associate Parents with Students'),
						1=>_('Reports'),
						'Students/AdvancedReport.php'=>_('Advanced Report'),
						'Students/AddDrop.php'=>_('Add / Drop Report'),
//modif Francois: add Student Breakdown
						'Students/StudentBreakdown.php'=>_('Student Breakdown'),
						'Students/Letters.php'=>_('Print Letters'),
						'Students/MailingLabels.php'=>_('Print Mailing Labels'),
						'Students/StudentLabels.php'=>_('Print Student Labels'),
						'Students/PrintStudentInfo.php'=>_('Print Student Info'),
						2=>_('Setup'),
						'Students/StudentFields.php'=>_('Student Fields'),
						'Students/AddressFields.php'=>_('Address Fields'),
						'Students/PeopleFields.php'=>_('Contact Fields'),
						'Students/EnrollmentCodes.php'=>_('Enrollment Codes')
					);

$menu['Students']['teacher'] = array(
						'Students/Student.php'=>_('Student Info'),
						'Students/AddUsers.php'=>_('Associated Parents'),
						1=>_('Reports'),
						'Students/AdvancedReport.php'=>_('Advanced Report'),
						'Students/StudentLabels.php'=>_('Print Student Labels'),
						'Students/Letters.php'=>_('Print Letters')
					);

$menu['Students']['parent'] = array(
						'Students/Student.php'=>_('Student Info')
					);

$exceptions['Students'] = array(
						'Students/Student.php&include=General_Info&student_id=new'=>true,
						'Students/AssignOtherInfo.php'=>true
					);
?>