<?php

//get config values from PROGRAM_CONFIG table
$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='moodle'"),array(),array('TITLE'));

//define constants for plugin use
define('MOODLE_URL', $program_config['MOODLE_URL'][1]['VALUE']); //example: http://localhost/moodle
define('MOODLE_TOKEN', $program_config['MOODLE_TOKEN'][1]['VALUE']); //example: d6c51ea6ffd9857578722831bcb070e1
define('MOODLE_PARENT_ROLE_ID', $program_config['MOODLE_PARENT_ROLE_ID'][1]['VALUE']); //example: 10
define('ROSARIO_STUDENTS_EMAIL_FIELD_ID', $program_config['ROSARIO_STUDENTS_EMAIL_FIELD_ID'][1]['VALUE']); //example: 11

?>
