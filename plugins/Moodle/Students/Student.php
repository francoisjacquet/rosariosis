<?php
//FJ Moodle integrator

//core_user_create_users function
function core_user_get_users_object()
{
	$username = DBGetOne( "SELECT USERNAME FROM students
		WHERE STUDENT_ID='" . UserStudentID() . "'" );

	$criteria = [
		'key' => 'username',
		'value' => $username,
	];

	$object = [ 'criteria' => $criteria ];

	return $object;
}

// @since 5.9 Moodle circumvent bug: no response or error but User created.
// Get User ID right after creation and try to save it.
function core_user_get_users_response( $response )
{
	if ( empty( $response['users'][0]['id'] ) )
	{
		return -1;
	}

	// Saving Moodle User ID at last.
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
	DBQuery( "INSERT INTO moodlexrosario (" . DBEscapeIdentifier( 'column' ) . ",rosario_id,moodle_id)
		VALUES('student_id','" . UserStudentID() . "'," . $response['users'][0]['id'] . ")" );

	$_REQUEST['moodle_create_student'] = false;

	return $response['users'][0]['id'];
}

//core_user_create_users function
function core_user_create_users_object()
{
	//first, gather the necessary variables
	global $_REQUEST;

	//then, convert variables for the Moodle object:
	/*
	list of (
		object {
			createpassword int  Optional //True if password should be created and mailed to user.
			username string   //Username policy is defined in Moodle security config.
			auth string  Default to "manual" //Auth plugins include manual, ldap, etc
			password string  Optional //Plain text password consisting of any characters
			firstname string   //The first name(s) of the user
			lastname string   //The family name of the user
			email string   //A valid and unique email address
			maildisplay int  Optional //Email display
			city string  Optional //Home city of the user
			country string  Optional //Home country code of the user, such as AU or CZ
			timezone string  Optional //Timezone code such as Australia/Perth, or 99 for default
			description string  Optional //User profile description, no HTML
			firstnamephonetic string  Optional //The first name(s) phonetically of the user
			lastnamephonetic string  Optional //The family name phonetically of the user
			middlename string  Optional //The middle name of the user
			alternatename string  Optional //The alternate name of the user
			interests string  Optional //User interests (separated by commas)
			url string  Optional //User web page
			icq string  Optional //ICQ number
			skype string  Optional //Skype ID
			aim string  Optional //AIM ID
			yahoo string  Optional //Yahoo ID
			msn string  Optional //MSN ID
			idnumber string  Default to "" //An arbitrary ID code number perhaps from the institution
			institution string  Optional //institution
			department string  Optional //department
			phone1 string  Optional //Phone 1
			phone2 string  Optional //Phone 2
			address string  Optional //Postal address
			lang string  Default to "en" //Language code such as "en", must exist on server
			calendartype string  Default to "gregorian" //Calendar type such as "gregorian", must exist on server
			theme string  Optional //Theme name such as "standard", must exist on server
			mailformat int  Optional //Mail format code is 0 for plain text, 1 for HTML etc
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
			)
		}
	)
	 */
	$username = mb_strtolower( $_REQUEST['students']['USERNAME'] );
	$password = issetVal( $_REQUEST['students']['PASSWORD'] );
	$firstname = issetVal( $_REQUEST['students']['FIRST_NAME'] );
	$lastname = issetVal( $_REQUEST['students']['LAST_NAME'] );
	$email = issetVal( $_REQUEST['students'][ROSARIO_STUDENTS_EMAIL_FIELD] );
	$auth = 'manual';
	$idnumber = (string) UserStudentID();

	// @since 5.9 Moodle creates user password if left empty.
	$createpassword = empty( $password ) ? 1 : 0;

	$users = [
		[
			'username' => $username,
			'password' => $password,
			'firstname' => $firstname,
			'lastname' => $lastname,
			'email' => $email,
			'auth' => $auth,
			'idnumber' => $idnumber,
			'createpassword' => $createpassword,
		],
	];

	return [ $users ];
}

/**
 * @param $response
 *
 * @return int -1 if no User ID, else Moodle User ID.
 */
function core_user_create_users_response( $response )
{
	if ( empty( $response[0]['id'] ) )
	{
		// @since 5.9 Moodle circumvent bug: no response or error but User created.
		// Return -1 as distinctive error code.
		return -1;
	}

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
	DBQuery( "INSERT INTO moodlexrosario (" . DBEscapeIdentifier( 'column' ) . ",rosario_id,moodle_id)
		VALUES('student_id','" . UserStudentID() . "'," . $response[0]['id'] . ")" );

	$_REQUEST['moodle_create_student'] = false;

	return $response[0]['id'];
}

//core_user_update_users function
function core_user_update_users_object()
{
	//first, gather the necessary variables
	global $_REQUEST;

	//gather the Moodle user ID
	$moodle_id = MoodleXRosarioGet( 'student_id', UserStudentID() );

	if ( empty( $moodle_id ) )
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
	$username = ( ! empty( $_REQUEST['students']['USERNAME'] ) ? mb_strtolower( $_REQUEST['students']['USERNAME'] ) : false );
	$password = issetVal( $_REQUEST['students']['PASSWORD'], false );
	$firstname = issetVal( $_REQUEST['students']['FIRST_NAME'], false );
	$lastname = issetVal( $_REQUEST['students']['LAST_NAME'], false );
	$email = issetVal( $_REQUEST['students'][ROSARIO_STUDENTS_EMAIL_FIELD], false );

	$user = [ 'id' => $moodle_id ];

	if ( $username )
	{
		$user['username'] = $username;
	}

	// @since 5.9 Do not update Moodle user password.
	/*if ( $password )
	{
		$user['password'] = $password;
	}*/

	if ( $firstname )
	{
		$user['firstname'] = $firstname;
	}

	if ( $lastname )
	{
		$user['lastname'] = $lastname;
	}

	if ( $email )
	{
		$user['email'] = $email;
	}

	//Update Address
	//Residence only
	//Address and Phone not possible...
	//$address = (!empty($_REQUEST['values']['address']['ADDRESS']) ? $_REQUEST['values']['address']['ADDRESS'] : false);
	$city = issetVal( $_REQUEST['values']['address']['CITY'], false );
	//$country = 'CO'; //Hardcoded (Colombia)
	//$phone1 = (!empty($_REQUEST['values']['address']['PHONE']) ? $_REQUEST['values']['address']['PHONE'] : false);

	/*if ($address)
	$user['address'] = $address;*/

	if ( $city )
	{
		$user['city'] = $city;
	}

	//$user['country'] = $country;
	/*if ($phone1)
	$user['phone1'] = $phone1;*/

	//if none of the above user fields are updated, no object returned

	if ( count( $user ) < 2 )
	{
		return null;
	}

	$users = [ $user ];

	return [ $users ];
}

/**
 * @param $response
 */
function core_user_update_users_response( $response )
{
	return null;
}

// core_user_delete_users function.
function core_user_delete_users_object()
{
	// Gather the Moodle user ID.
	$moodle_id = MoodleXRosarioGet( 'student_id', UserStudentID() );

	if ( empty( $moodle_id ) )
	{
		return null;
	}

	// Then, convert variables for the Moodle object:.
	/*
	list of (
	int   //user ID
	)
	 */

	$user_ids = [ $moodle_id ];

	return [ $user_ids ];
}

/**
 * @param $response
 */
function core_user_delete_users_response( $response )
{
	// Delete the reference the moodlexrosario cross-reference table.
	DBQuery( "DELETE FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'column' ) . "='student_id'
		AND rosario_id='" . UserStudentID() . "'" );

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
		WHERE rosario_id=(SELECT STAFF_ID FROM students_join_users
			WHERE STUDENT_ID='" . (int) $student_id . "' LIMIT 1)
		AND " . DBEscapeIdentifier( 'column' ) . "='staff_id'" );

	if ( empty( $userid ) )
	{
		return null;
	}

	//gather the Moodle student ID
	$studentid = MoodleXRosarioGet( 'student_id', $student_id );

	if ( empty( $studentid ) )
	{
		return null;
	}

	$contextlevel = 'user';
	$roleid = MOODLE_PARENT_ROLE_ID;
	$instanceid = $studentid;

	$assignments = [
		[
			'roleid' => $roleid,
			'userid' => $userid,
			'contextlevel' => $contextlevel,
			'instanceid' => $instanceid,
		],
	];

	return [ $assignments ];
}

/**
 * @param $response
 */
function core_role_assign_roles_response( $response )
{
	return null;
}

//core_files_upload function
/**
 * @return mixed
 */
function core_files_upload_object()
{
	//first, gather the necessary variables
	global $_REQUEST;

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

	$rosario_id = $_REQUEST['userId'];
	//gather the Moodle user ID
	$column = ( mb_strpos( $_REQUEST['modname'], 'Users' ) !== false ? 'staff_id' : 'student_id' );
	$instanceid = MoodleXRosarioGet( $column, $rosario_id );

	if ( empty( $instanceid ) )
	{
		return null;
	}

	$contextlevel = 'user';
	$component = 'user';
	$filearea = 'draft';
	$itemid = 1;
	$filepath = '/';
	$filename = $_REQUEST['userId'] . '.jpg';

	function base64_encode_file( $file )
	{
		if ( ! file_exists( $file ) )
		{
			return false;
		}
		else
		{
			$filename = htmlentities( $file );
		}

		$filetype = pathinfo( $filename, PATHINFO_EXTENSION );
		$filebinary = fread( fopen( $filename, "r" ), filesize( $filename ) );

		return base64_encode( $filebinary );
	}

	global $RosarioPath;
	$filecontent = base64_encode_file(
		$RosarioPath . $_REQUEST['photoPath'] . $_REQUEST['sYear'] . '/' . $_REQUEST['userId'] . '.jpg'
	);

	if ( ! $filecontent )
	{
		global $error;

		$error[] = 'Moodle: ' . 'File does not exist'; //should never be displayed, so do not translate

		return false;
	}

	$file = [
		$component,
		$filearea,
		$itemid,
		$filepath,
		$filename,
		$filecontent,
		$contextlevel,
		$instanceid,
	];

	return $file;
}

/**
 * @param $response
 */
function core_files_upload_response( $response )
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
