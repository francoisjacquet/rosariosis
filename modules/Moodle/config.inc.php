<?php
//modif Francois: Moodle integrator

/// MOODLE 2.5 SITE ADMINISTRATION SETUP STEPS
// 1- Download and install the Web Service Get Contexts local plugin (https://moodle.org/plugins/view.php?plugin=local_getcontexts)
// 2- Follow the "Allow an external system to control Moodle" steps (Admin > Plugins > Web services > Overview)
// 	a- The protocol to be enabled is XML-RPC
// 	b- When you add the Service, select all the web service functions available (except the DEPRECATED ones), then add the required capabilities to the Web Services role
// 	c- Enter the token and the Moodle URL in the School Configuration screen
// 3- Create the Parent role (http://docs.moodle.org/23/en/Parent_role) and then enter the Parent role ID in the School Configuration screen
// 4- Allow the Web Services role to assign the Teacher, Student and Parent roles (Admin > Users > Permissions > Define roles > Allow role assignments)
// 5- Create an email field for the students in RosarioSIS and enter the field ID in the School Configuration screen

$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='moodle'"),array(),array('TITLE'));

define('MOODLE_URL', $program_config['MOODLE_URL'][1]['VALUE']); //example: http://localhost/moodle
define('MOODLE_TOKEN', $program_config['MOODLE_TOKEN'][1]['VALUE']); //example: d6c51ea6ffd9857578722831bcb070e1
define('MOODLE_PARENT_ROLE_ID', $program_config['MOODLE_PARENT_ROLE_ID'][1]['VALUE']); //example: 10
define('ROSARIO_STUDENTS_EMAIL_FIELD_ID', $program_config['ROSARIO_STUDENTS_EMAIL_FIELD_ID'][1]['VALUE']); //example: 11


// Context levels definitions
define('CONTEXT_SYSTEM', 10);
define('CONTEXT_PERSONAL', 20);
define('CONTEXT_USER', 30);
define('CONTEXT_COURSECAT', 40);
define('CONTEXT_COURSE', 50);
define('CONTEXT_GROUP', 60);
define('CONTEXT_MODULE', 70);
define('CONTEXT_BLOCK', 80);
?>