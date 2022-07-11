<?php
//FJ Moodle integrator

//core_notes_create_notes function
function core_notes_create_notes_object()
{
	//first, gather the necessary variables
	global $columns, $portal_note_id;

	//then, convert variables for the Moodle object:
	/*
	list of (
	object {
	userid int   //id of the user the note is about
	publishstate string   //'personal', 'course' or 'site'
	courseid int   //course id of the note (in Moodle a note can only be created into a course, even for site and personal notes)
	text string   //the text of the message - text or HTML
	format string  Défaut pour « 1 » //text format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	clientnoteid string  Optionnel //your own client id for the note. If this id is provided, the fail message id will be returned to you
	}
	)
	 */

	//gather the Moodle user ID
	$userid = MoodleXRosarioGet( 'staff_id', User( 'STAFF_ID' ) );

	if ( empty( $userid ) )
	{
		return null;
	}

	$publishstate = 'site';
	$courseid = 1;
	$text = $columns['TITLE'] . "\n\n" . $columns['CONTENT'];
	$format = 2;
	$clientnoteid = $portal_note_id;

	$notes = [
		[
			'userid' => $userid,
			'publishstate' => $publishstate,
			'courseid' => $courseid,
			'text' => $text,
			'format' => $format,
			'clientnoteid' => $clientnoteid,
		],
	];

	return [ $notes ];
}

/**
 * @param $response
 */
function core_notes_create_notes_response( $response )
{
	//first, gather the necessary variables
	global $portal_note_id;

	//then, save the ID in the moodlexrosario cross-reference table if no error:
	/*
	list of (
	object {
	clientnoteid string  Optionnel //your own id for the note
	noteid int   //test this to know if it success:  id of the created note when successed, -1 when failed
	errormessage string  Optionnel //error message - if failed
	}
	)
	 */

	if ( $response[0]['noteid'] == -1 )
	{
		global $error;

		$error[] = 'Moodle: ' . $response[0]['errormessage'];

		return false;
	}

	DBQuery( "INSERT INTO moodlexrosario (" . DBEscapeIdentifier( 'column' ) . ",rosario_id,moodle_id)
		VALUES ('portal_note_id','" . $portal_note_id . "'," . $response[0]['noteid'] . ")" );

	return null;
}

//core_notes_delete_notes function
function core_notes_delete_notes_object()
{
	//first, gather the necessary variables
	global $_REQUEST;

	//then, convert variables for the Moodle object:
	/*
	//Array of Note Ids to be deleted.
	list of (
	int   //ID of the note to be deleted
	)
	 */

	//gather the Moodle note ID
	$noteid = MoodleXRosarioGet( 'portal_note_id', $_REQUEST['id'] );

	if ( empty( $noteid ) )
	{
		return null;
	}

	$notes = [
		$noteid,
	];

	return [ $notes ];
}

/**
 * @param $response
 */
function core_notes_delete_notes_response( $response )
{
	//first, gather the necessary variables
	global $_REQUEST;

	//then, delete the ID in the moodlexrosario cross-reference table if no error:
	/*
	Optional //list of warnings
	list of (
	//warning
	object {
	item string  Optional //item is always 'note'
	itemid int  Optional //When errorcode is savedfailed the note could not be modified.When errorcode is badparam, an incorrect parameter was provided.When errorcode is badid, the note does not exist
	warningcode string   //errorcode can be badparam (incorrect parameter), savedfailed (could not be modified), or badid (note does not exist)
	message string   //untranslated english message to explain the warning
	}
	)
	 */

	if ( is_array( $response[0] ) )
	{
		global $error;

		$error[] = 'Moodle: ' . 'Code: ' . $response[0]['warningcode'] . ' - ' . $response[0]['message'];

		return false;
	}

	DBQuery( "DELETE FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'column' ) . "='portal_note_id'
		AND rosario_id='" . (int) $_REQUEST['id'] . "'" );

	return null;
}

//core_notes_update_notes function
function core_notes_update_notes_object()
{
	//first, gather the necessary variables
	global $id;

	//then, convert variables for the Moodle object:
	/*
	//Array of Notes
	list of (
	object {
	id int   //id of the note
	publishstate string   //'personal', 'course' or 'site'
	text string   //the text of the message - text or HTML
	format int  Default to "1" //text format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	}
	)
	 */
	//gather the Moodle note ID
	$rosario_id = $id;
	$moodle_id = MoodleXRosarioGet( 'portal_note_id', $rosario_id );

	if ( empty( $moodle_id ) )
	{
		return null;
	}

	$publishstate = 'site';

	//gather new note title and content as only one of the two maybe updated but the two are needed
	$portal_note = DBGet( "SELECT title,content
		FROM portal_notes
		WHERE id='" . $rosario_id . "'" );

	$text = $portal_note[1]['TITLE'] . "\n\n" . $portal_note[1]['CONTENT'];

	$format = 2;

	$notes = [
		[
			'id' => $moodle_id,
			'publishstate' => $publishstate,
			'text' => $text,
			'format' => $format,
		],
	];

	return [ $notes ];
}

/**
 * @param $response
 */
function core_notes_update_notes_response( $response )
{
/*
Optional //list of warnings
list of (
//warning
object {
item string  Optional //item is always 'note'
itemid int  Optional //When errorcode is savedfailed the note could not be modified.When errorcode is badparam, an incorrect parameter was provided.When errorcode is badid, the note does not exist
warningcode string   //errorcode can be badparam (incorrect parameter), savedfailed (could not be modified), or badid (note does not exist)
message string   //untranslated english message to explain the warning
}
)
 */

	if ( is_array( $response[0] ) )
	{
		global $error;

		$error[] = 'Moodle: ' . 'Code: ' . $response[0]['warningcode'] . ' - ' . $response[0]['message'];

		return false;
	}

	return null;
}
