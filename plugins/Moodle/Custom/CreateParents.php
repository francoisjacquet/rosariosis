<?php
//modif Francois: Moodle integrator

//core_user_create_users function
function core_user_create_users_object()
{
	//first, gather the necessary variables
	global $id, $username, $password, $locale, $user, $students;
	
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
	$username = mb_strtolower($username);
	$password = $password;
	$firstname = $user['FIRST_NAME'];
	$lastname = $user['LAST_NAME'];
	$email = $students[1]['EMAIL'];
	$auth = 'manual';
	$idnumber = (string)$id;
	
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
	global $id;
	
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
	
	DBQuery("INSERT INTO MOODLEXROSARIO (\"column\", rosario_id, moodle_id) VALUES ('staff_id', '".$id."', ".$response[0]['id'].")");
	return null;
}


//core_role_assign_roles function
function core_role_assign_roles_object()
{
	//first, gather the necessary variables
	global $id, $student;
	
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	object {
		roleid int   //Role to assign to the user
		userid int   //The user that is going to be assigned
		contextid int   //The context to assign the user role in
	} 
)*/

	//gather the Moodle user ID
	$userid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$id."' AND \"column\"='staff_id'"));
	if (count($userid))
	{
		$userid = (int)$userid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	
	//get the contextid
	global $moodle_contextlevel, $moodle_instance;
	$moodle_contextlevel = CONTEXT_USER;
	//gather the Moodle user ID
	$moodle_instance = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$student['STUDENT_ID']."' AND \"column\"='student_id'"));
	if (count($moodle_instance))
	{
		$moodle_instance = (int)$moodle_instance[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}

	$contexts = Moodle('Global/functions.php', 'local_getcontexts_get_contexts');
	
	$contextid = $contexts[0]['id'];
	$roleid = MOODLE_PARENT_ROLE_ID;

	$assignments = array(
						array(
							'roleid' => $roleid,
							'userid' => $userid,
							'contextid' => $contextid,
						)
					);
	
	return array($assignments);
}


function core_role_assign_roles_response($response)
{
	return null;
}
?>