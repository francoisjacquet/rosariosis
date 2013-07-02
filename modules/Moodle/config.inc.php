<?php
//modif Francois: Moodle integrator

/// MOODLE 2.5 SITE ADMINISTRATION SETUP STEPS
// 1- Download and install the Web Service Get Contexts local plugin (https://moodle.org/plugins/view.php?plugin=local_getcontexts)
// 2- Follow the "Allow an external system to control Moodle" steps (Admin > Plugins > Web services > Overview)
// 	a- The protocol to be enabled is XML-RPC
// 	b- When you add the Service, select all the web service functions available (except the DEPRECATED ones), then add the required capabilities to the Web Services role
// 	c- Enter the token and the domain name below
// 3- Create the Parent role (http://docs.moodle.org/23/en/Parent_role) and then enter the Parent role ID below
// 4- Allow the Web Services role to assign the Teacher, Student and Parent roles (Admin > Users > Permissions > Define roles > Allow role assignments)
// 5- Create an email field for the students in RosarioSIS and enter the field ID below

global $moodle_domainnames, $moodle_tokens;
/// SETUP - NEED TO BE CHANGED
//Note: the array key is the RosarioSIS School ID
$moodle_domainnames = array(1 => 'http://localhost/moodle25');
$moodle_tokens = array(1 => 'd6c51ea6ffd9857578722831bcb070e1');
define('MOODLE_PARENT_ROLE_ID', 10);
define('ROSARIO_STUDENTS_EMAIL_FIELD_ID', 11);


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