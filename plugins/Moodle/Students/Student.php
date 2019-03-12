<?php
//FJ Moodle integrator

//core_user_create_users function
function core_user_create_users_object()
{
	//first, gather the necessary variables
	global $student_id, $locale, $_REQUEST;

	//then, convert variables for the Moodle object:
/*
list of (
	object {
		username string   //Username policy is defined in Moodle security config. Must be lowercase.
		password string   //Plain text password consisting of any characters
		//note Francois: the password must respect the Moodle policy: 8 chars min., 1 number, 1 min, 1 maj and 1 non-alphanum at least.
		firstname string   //The first name(s) of the user
		lastname string   //The family name of the user
		email string   //A valid and unique email address
		auth string  Default to "manual" //Auth plugins include manual, ldap, imap, etc
		idnumber string  Default to "" //An arbitrary ID code number perhaps from the institution
		lang string  Default to "fr" //Language code such as "en", must exist on server
		theme string  Optional //Theme name such as "standard", must exist on server
		timezone string  Optional //Timezone code such as Australia/Perth, or 99 for default
		mailformat int  Optional //Mail format code is 0 for plain text, 1 for HTML etc
		description string  Optional //User profile description, no HTML
		city string  Optional //Home city of the user
		country string  Optional //Home country code of the user, such as AU or CZ
		preferences  Optional //User preferences
			list of (
				object {
					type string   //The name of the preference
					value string   //The value of the preference
				}
		)customfields  Optional //User custom fields (also known as user profil fields)
			list of (
				object {
					type string   //The name of the custom field
					value string   //The value of the custom field
				}
		)}
)
*/
	$username = mb_strtolower($_REQUEST['students']['USERNAME']);
	$password = isset( $_REQUEST['students']['PASSWORD'] ) ? $_REQUEST['students']['PASSWORD'] : null;
	$firstname = isset( $_REQUEST['students']['FIRST_NAME'] ) ? $_REQUEST['students']['FIRST_NAME'] : null;
	$lastname = isset( $_REQUEST['students']['LAST_NAME'] ) ? $_REQUEST['students']['LAST_NAME'] : null;
	$email = isset( $_REQUEST['students'][ ROSARIO_STUDENTS_EMAIL_FIELD ] ) ? $_REQUEST['students'][ ROSARIO_STUDENTS_EMAIL_FIELD ] : null;
	$auth = 'manual';
	$idnumber = (string)(!empty($student_id) ? $student_id : UserStudentID());

	$users = array(
				array(
					'username' => $username,
					'password' => $password,
					'firstname' => $firstname,
					'lastname' => $lastname,
					'email' => $email,
					'auth' => $auth,
					'idnumber' => $idnumber,
				)
			);

	return array($users);
}


function core_user_create_users_response($response)
{
	//first, gather the necessary variables
	global $student_id;

	//then, save the ID in the moodlexrosario cross-reference table:
/*
Array
	(
	[0] =>
		Array
			(
			[id] => int
			[username] => string
			)
	)
*/

	DBQuery("INSERT INTO MOODLEXROSARIO (\"column\", rosario_id, moodle_id) VALUES ('student_id', '".(!empty($student_id) ? $student_id : UserStudentID())."', ".$response[0]['id'].")");

	$_REQUEST['moodle_create_student'] = false;

	return null;
}



//core_user_update_users function
function core_user_update_users_object()
{
	//first, gather the necessary variables
	global $_REQUEST;

	//gather the Moodle user ID
	$rosario_id = UserStudentID();
	$moodle_id = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$rosario_id."'
		AND \"column\"='student_id'" );

	if (empty($moodle_id))
	{
		return null;
	}

	//then, convert variables for the Moodle object:
/*
list of (
	object {
		id double   //ID of the user
		username string  Optional //Username policy is defined in Moodle security config. Must be lowercase.
		password string  Optional //Plain text password consisting of any characters
		//note Francois: the password must respect the Moodle policy: 8 chars min., 1 number, 1 min, 1 maj and 1 non-alphanum at least.
		firstname string  Optional //The first name(s) of the user
		lastname string  Optional //The family name of the user
		email string  Optional //A valid and unique email address
		auth string  Optional //Auth plugins include manual, ldap, imap, etc
		idnumber string  Optional //An arbitrary ID code number perhaps from the institution
		lang string  Optional //Language code such as "en", must exist on server
		theme string  Optional //Theme name such as "standard", must exist on server
		timezone string  Optional //Timezone code such as Australia/Perth, or 99 for default
		mailformat int  Optional //Mail format code is 0 for plain text, 1 for HTML etc
		description string  Optional //User profile description, no HTML
		city string  Optional //Home city of the user
		country string  Optional //Home country code of the user, such as AU or CZ
		customfields  Optional //User custom fields (also known as user profil fields)
			list of (
				object {
					type string   //The name of the custom field
					value string   //The value of the custom field
				}
		)preferences  Optional //User preferences
			list of (
			object {
				type string   //The name of the preference
				value string   //The value of the preference
			}
	)}
)
*/
	$username = (!empty($_REQUEST['students']['USERNAME']) ? mb_strtolower($_REQUEST['students']['USERNAME']) : false);
	$password = (!empty($_REQUEST['students']['PASSWORD']) ? $_REQUEST['students']['PASSWORD'] : false);
	$firstname = (!empty($_REQUEST['students']['FIRST_NAME']) ? $_REQUEST['students']['FIRST_NAME'] : false);
	$lastname = (!empty($_REQUEST['students']['LAST_NAME']) ? $_REQUEST['students']['LAST_NAME'] : false);
	$email = (!empty($_REQUEST['students'][ ROSARIO_STUDENTS_EMAIL_FIELD ]) ? $_REQUEST['students'][ ROSARIO_STUDENTS_EMAIL_FIELD ] : false);

	$user = array('id' => $moodle_id);

	if ($username)
		$user['username'] = $username;
	if ($password)
		$user['password'] = $password;
	if ($firstname)
		$user['firstname'] = $firstname;
	if ($lastname)
		$user['lastname'] = $lastname;
	if ($email)
		$user['email'] = $email;


	//Update Address
	//Residence only
	//Address and Phone not possible...
	//$address = (!empty($_REQUEST['values']['ADDRESS']['ADDRESS']) ? $_REQUEST['values']['ADDRESS']['ADDRESS'] : false);
	$city = (!empty($_REQUEST['values']['ADDRESS']['CITY']) ? $_REQUEST['values']['ADDRESS']['CITY'] : false);
	//$country = 'CO'; //Hardcoded (Colombia)
	//$phone1 = (!empty($_REQUEST['values']['ADDRESS']['PHONE']) ? $_REQUEST['values']['ADDRESS']['PHONE'] : false);

	/*if ($address)
		$user['address'] = $address;*/
	if ($city)
		$user['city'] = $city;
	//$user['country'] = $country;
	/*if ($phone1)
		$user['phone1'] = $phone1;*/

	//if none of the above user fields are updated, no object returned
	if (count($user) < 2)
		return null;

	$users = array($user);

	return array($users);
}


function core_user_update_users_response($response)
{
	return null;
}



//core_role_assign_roles function
function core_role_assign_roles_object()
{
	//first, gather the necessary variables

	//then, convert variables for the Moodle object:
/*
list of (
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int  Optional //The context to assign the user role in
		contextlevel string  Optional //The context level to assign the user role in
				                      (block, course, coursecat, system, user, module)
		instanceid int  Optional //The Instance id of item where the role needs to be assigned
	}
)*/

	//gather the Moodle user ID
	$student_id = UserStudentID();
	$userid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id=(SELECT STAFF_ID FROM STUDENTS_JOIN_USERS WHERE STUDENT_ID='".$student_id."' LIMIT 1)
		AND \"column\"='staff_id'" );

	if (empty($userid))
	{
		return null;
	}

	//gather the Moodle student ID
	$studentid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$student_id."'
		AND \"column\"='student_id'" );

	if (empty($studentid))
	{
		return null;
	}

	$contextlevel = 'user';
	$roleid = MOODLE_PARENT_ROLE_ID;
	$instanceid = $studentid;

	$assignments = array(
				array(
					'roleid' => $roleid,
					'userid' => $userid,
					'contextlevel' => $contextlevel,
					'instanceid' => $instanceid,
				)
			);

	return array($assignments);
}


function core_role_assign_roles_response($response)
{
	return null;
}



//core_files_upload function
function core_files_upload_object()
{
	//first, gather the necessary variables
	global $_POST;


	//then, convert variables for the Moodle object:
/*
contextid int  Default to "null" //context id
component string   //component
filearea string   //file area
itemid int   //associated id
filepath string   //file path
filename string   //file name
filecontent string   //file content
contextlevel string  Default to "null" //The context level to put the file in,
                        (block, course, coursecat, system, user, module)
instanceid int  Default to "null" //The Instance id of item associated
                         with the context level
*/

//For a User Avatar, looking at mdl_files table for example:
/*
contextid = 5 (context = USER, userid = instance = 2), use local_getcontexts_get_contexts function
component = user
filearea = draft
itemid = 230987549 or 1
filepath = /
filename = xxx.jpeg
filecontent = base64_encode
*/

//For the moment, component = user && filearea = private is hardcoded...
// see http://tracker.moodle.org/browse/MDL-31116
	return null;

	$rosario_id = $_POST['userId'];
	//gather the Moodle user ID
	$column = (mb_strpos($_POST['modname'], 'Users') !== false ? 'staff_id' : 'student_id');
	$instanceid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$rosario_id."'
		AND \"column\"='".$column."'" );

	if (empty($instanceid))
	{
		return null;
	}

	$contextlevel = 'user';
	$component = 'user';
	$filearea = 'draft';
	$itemid = 1;
	$filepath = '/';
	$filename = $_POST['userId'].'.jpg';

	function base64_encode_file ($file) {
		if ( !file_exists($file))
			return false;
		else
			$filename = htmlentities($file);

		$filetype = pathinfo($filename, PATHINFO_EXTENSION);
		$filebinary = fread(fopen($filename, "r"), filesize($filename));

		return base64_encode($filebinary);
	}

	global $RosarioPath;
	$filecontent = base64_encode_file ($RosarioPath.$_POST['photoPath'].$_POST['sYear'].'/'.$_POST['userId'].'.jpg');

	if ( ! $filecontent)
	{
		global $error;

		$error[] = 'Moodle: '.'File does not exist';//should never be displayed, so do not translate

		return false;
	}

	$file = array(
					$component,
					$filearea,
					$itemid,
					$filepath,
					$filename,
					$filecontent,
					$contextlevel,
					$instanceid,
				);

	return $file;
}


function core_files_upload_response($response)
{
/*
    Array
        (
        [contextid] => int
        [component] => string
        [filearea] => string
        [itemid] => int
        [filepath] => string
        [filename] => string
        [url] => string
        )
*/
	return null;
}
