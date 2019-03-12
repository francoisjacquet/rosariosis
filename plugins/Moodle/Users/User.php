<?php
//FJ Moodle integrator

//core_user_create_users function
function core_user_create_users_object()
{
	//first, gather the necessary variables
	global $locale, $_REQUEST;

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
	$username = mb_strtolower($_REQUEST['staff']['USERNAME']);
	$password = isset( $_REQUEST['staff']['PASSWORD'] ) ? $_REQUEST['staff']['PASSWORD'] : null;
	$firstname = isset( $_REQUEST['staff']['FIRST_NAME'] ) ? $_REQUEST['staff']['FIRST_NAME'] : null;
	$lastname = isset( $_REQUEST['staff']['LAST_NAME'] ) ? $_REQUEST['staff']['LAST_NAME'] : null;
	$email = isset( $_REQUEST['staff']['EMAIL'] ) ? $_REQUEST['staff']['EMAIL'] : null;
	$auth = 'manual';
	$idnumber = (string)UserStaffID();

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

	DBQuery("INSERT INTO MOODLEXROSARIO (\"column\", rosario_id, moodle_id) VALUES ('staff_id', '".UserStaffID()."', ".$response[0]['id'].")");

	$_REQUEST['moodle_create_user'] = false;

	return null;
}



//core_user_update_users function
function core_user_update_users_object()
{
	//first, gather the necessary variables
	global $_REQUEST;

	//gather the Moodle user ID
	$rosario_id = UserStaffID();
	$moodle_id = (double) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$rosario_id."'
		AND \"column\"='staff_id'" );

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
	$username = (!empty($_REQUEST['staff']['USERNAME']) ? mb_strtolower($_REQUEST['staff']['USERNAME']) : false);
	$password = (!empty($_REQUEST['staff']['PASSWORD']) ? $_REQUEST['staff']['PASSWORD'] : false);
	$firstname = (!empty($_REQUEST['staff']['FIRST_NAME']) ? $_REQUEST['staff']['FIRST_NAME'] : false);
	$lastname = (!empty($_REQUEST['staff']['LAST_NAME']) ? $_REQUEST['staff']['LAST_NAME'] : false);
	$email = (!empty($_REQUEST['staff']['EMAIL']) ? $_REQUEST['staff']['EMAIL'] : false);

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



//core_user_delete_users function
function core_user_delete_users_object()
{
	//gather the Moodle user ID
	$rosario_id = UserStaffID();
	$moodle_id = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".$rosario_id."'
		AND \"column\"='staff_id'" );

	if (empty($moodle_id))
	{
		return null;
	}

	//then, convert variables for the Moodle object:
/*
list of (
	int   //user ID
)
*/

	$user_ids = array($moodle_id);

	return array($user_ids);
}


function core_user_delete_users_response($response)
{

	$rosario_id = UserStaffID();

	//delete the reference the moodlexrosario cross-reference table:
	DBQuery("DELETE FROM MOODLEXROSARIO WHERE \"column\" = 'staff_id' AND rosario_id = '".$rosario_id."'");

	return null;
}



//core_role_assign_roles function
function core_role_assign_roles_object()
{
	//first, gather the necessary variables
	global $staff_id, $_REQUEST;


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
	$userid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".(!empty($staff_id) ? $staff_id : UserStaffID())."'
		AND \"column\"='staff_id'" );

	if (empty($userid))
	{
		return null;
	}


	//admin's roleid = manager = 1
	//teacher's roleid = teacher = 3
	//parent's roleid = parent = RoleToBeCreated
	//student's roleid = student = 5

	//Moodle contexts doc: http://docs.moodle.org/dev/Roles_and_modules#Context

	if ( $_REQUEST['staff']['PROFILE']=='admin')
	{
		$roleid = 1;
		$contextlevel = 'system'; // System
	}
	elseif ( $_REQUEST['staff']['PROFILE']=='teacher')
	{
		$roleid = 3;
		/* Course context, level 50

			Settings > Course administration > Enrolled users
			Click the "Enrol users" button and click those users you wish to enrol.
			The dropdown menu at the top shows roles for which you are allowed to enrol; typically those users with lower roles than you.
		*/
		// => see function enrol_manual_enrol_users
		return null;
	}
	elseif ( $_REQUEST['staff']['PROFILE']=='parent')
	{
		$roleid = MOODLE_PARENT_ROLE_ID;
		/* User context, level 30

			The most common use of this is for the Parent role.
			When the Parent role is created via Admin > Users > Permissions > Define roles the "user" context box is checked.
			To assign a parent the role in the context of their child (so they can see their child's grades etc) click the child's profile and then go to Settings > Roles > Assign roles relative to this user
			http://docs.moodle.org/23/en/Parent_role
		*/
		return null;
	}
	else
	{
		return null;
	}

	$instanceid = $userid;

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



//core_role_unassign_roles function
function core_role_unassign_roles_object()
{
	//first, gather the necessary variables
	global $_REQUEST;


	//then, convert variables for the Moodle object:
/*
list of (
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int  Optional //The context to unassign the user role from
		contextlevel string  Optional //The context level to unassign the user role in
		+                                    (block, course, coursecat, system, user, module)
		instanceid int  Optional //The Instance id of item where the role needs to be unassigned
	}
)*/
	//gather the Moodle user ID
	$userid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='".UserStaffID()."'
		AND \"column\"='staff_id'" );

	if (empty($userid))
	{
		return null;
	}

	//only unassign if profile not manager
	if ( !isset($_REQUEST['staff']['PROFILE']) || $_REQUEST['staff']['PROFILE'] == 'admin')
	{
		return null;
	}

	//admin's roleid = manager = 1

	//only unassign manager role
	$roleid = 1;
	$contextlevel = 'system'; // System
	$instanceid = $userid;

	$unassignments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'contextlevel' => $contextlevel,
							'instanceid' => $instanceid,
						)
					);

	return array($unassignments);
}


function core_role_unassign_roles_response($response)
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
contextid = 5 (context = USER, userid = instanceid = 2), use local_getcontexts_get_contexts function
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
