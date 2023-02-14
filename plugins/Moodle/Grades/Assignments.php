<?php
//FJ Moodle integrator

//core_calendar_create_calendar_events function
function core_calendar_create_calendar_events_object()
{
	//first, gather the necessary variables
	global $columns;

	//then, convert variables for the Moodle object:
	/*
	list of (
	//event
	object {
	name string   //event name
	description string  Default to "null" //Description
	format int  Default to "1" //description format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	courseid int  Default to "0" //course id
	groupid int  Default to "0" //group id
	repeats int  Default to "0" //number of repeats
	eventtype string  Default to "user" //Event type
	timestart int  Default to "1370827707" //timestart
	timeduration int  Default to "0" //time duration (in minutes)
	visible int  Default to "1" //visible
	sequence int  Default to "1" //sequence
	}
	)
	 */

	//assignment due date must be set (due date = Moodle event time start)

	if ( empty( $columns['DUE_DATE'] ) )
	{
		return null;
	}

	$name = $columns['TITLE'];

	$description = ( ! empty( $columns['ASSIGNED_DATE'] ) ?
		_( 'Assigned Date' ) . ': ' . ProperDate( $columns['ASSIGNED_DATE'] ) . '<br />' : '' ) .
		$columns['DESCRIPTION'];

	$format = 1;

	//gather the Moodle course ID
	$courseid = MoodleXRosarioGet( 'course_period_id', UserCoursePeriod() );

	if ( empty( $courseid ) )
	{
		return null;
	}

	$eventtype = 'course';

	$timestart = strtotime( $columns['DUE_DATE'] );

	$events = [
		[
			'name' => $name,
			'description' => $description,
			'format' => $format,
			'courseid' => $courseid,
			'timestart' => $timestart,
			'eventtype' => $eventtype,
		],
	];

	return [ $events ];
}

/**
 * @param $response
 */
function core_calendar_create_calendar_events_response( $response )
{
	//first, gather the necessary variables
	global $id;

	//then, save the ID in the moodlexrosario cross-reference table if no error:
	/*
	object {
	events list of (
	//event
	object {
	id int   //event id
	name string   //event name
	description string  Optional //Description
	format int   //description format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
	courseid int   //course id
	groupid int   //group id
	userid int   //user id
	repeatid int  Optional //repeat id
	modulename string  Optional //module name
	instance int   //instance id
	eventtype string   //Event type
	timestart int   //timestart
	timeduration int   //time duration
	visible int   //visible
	uuid string  Optional //unique id of ical events
	sequence int   //sequence
	timemodified int   //time modified
	subscriptionid int  Optional //Subscription id
	}
	)warnings  Optional //list of warnings
	list of (
	//warning
	object {
	item string  Optional //item
	itemid int  Optional //item id
	warningcode string   //the warning code can be used by the client app to implement specific behaviour
	message string   //untranslated english message to explain the warning
	}
	)}
	 */

	if ( isset( $response['warnings'][0] )
		&& is_array( $response['warnings'][0] ) )
	{
		global $error;

		$error[] = 'Moodle: ' . 'Code: ' . $response['warnings'][0]['warningcode'] . ' - ' .
			$response['warnings'][0]['message'];

		return false;
	}

	$assignment_id = $id;
	DBQuery( "INSERT INTO moodlexrosario (" . DBEscapeIdentifier( 'column' ) . ", rosario_id, moodle_id)
		VALUES('assignment_id', '" . $assignment_id . "', " . $response['events'][0]['id'] . ")" );

	return null;
}

//core_calendar_delete_calendar_events function
function core_calendar_delete_calendar_events_object()
{
	//first, gather the necessary variables
	global $id, $_REQUEST;

	//then, convert variables for the Moodle object:
	/*
	list of (
	//List of events to delete
	object {
	eventid int   //Event ID
	repeat int   //Delete comeplete series if repeated event
	}
	)
	 */

	//gather the Moodle Event ID
	$assignment_id = issetVal( $_REQUEST['assignment_id'] );

	//update

	if ( ! empty( $id ) )
	{
		$assignment_id = $id;
	}

	$eventid = MoodleXRosarioGet( 'assignment_id', $assignment_id );

	if ( empty( $eventid ) )
	{
		return null;
	}

	$repeat = 0;

	$events = [
		[
			'eventid' => $eventid,
			'repeat' => $repeat,
		],
	];

	return [ $events ];
}

/**
 * @param $response
 */
function core_calendar_delete_calendar_events_response( $response )
{
	//first, gather the necessary variables
	global $id, $columns, $_REQUEST;

	$assignment_id = issetVal( $_REQUEST['assignment_id'] );

	if ( ! empty( $id ) ) //update
	{
		$assignment_id = $id;
		//get the Gradebook Assignment columns needed by the core_calendar_create_calendar_events function
		$gradebook_assignment = DBGet( "SELECT ASSIGNED_DATE,DUE_DATE,DESCRIPTION,TITLE
			FROM gradebook_assignments
			WHERE ASSIGNMENT_ID='" . (int) $id . "'" );

		$columns = $gradebook_assignment[1];
	}

	//delete the reference the moodlexrosario cross-reference table:
	DBQuery( "DELETE FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'column' ) . "='assignment_id'
		AND rosario_id='" . $assignment_id . "'" );

	return null;
}
