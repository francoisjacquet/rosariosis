<?php
//modif Francois: Moodle integrator


//core_user_update_users function
function core_user_update_users_object()
{
	//first, gather the necessary variables
	global $_REQUEST;
	
	//gather the Moodle user ID
	if (User('PROFILE')=='student')
	{
		$rosario_id = UserStudentID();
		$moodle_id = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rosario_id."' AND \"column\"='student_id'"));
	}
	else
	{
		$rosario_id = User('STAFF_ID');
		$moodle_id = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$rosario_id."' AND \"column\"='staff_id'"));
	}
	if (count($moodle_id))
	{
		$moodle_id = (double)$moodle_id[1]['MOODLE_ID'];
	}
	else
	{
		return '';
	}
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	object {
		id double   //ID of the user
		password string  Optional //Plain text password consisting of any characters
		//note Francois: the password must respect the Moodle policy: 8 chars min., 1 number, 1 min, 1 maj and 1 non-alphanum at least.
	} 
)
*/
	$password = $_REQUEST['values']['new'];

	$users = array(
				array(
					'id' => $moodle_id,
					'password' => $password
					)
				);
	
	return array($users);
}


function core_user_update_users_response($response)
{
	return '';
}

?>