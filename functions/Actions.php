<?php
/**
 * Actions functions & definitions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Action tags are constructed the following way:
 * [modname]|[action_name]
 *
 * @example Students/Student.php|create_student
 *
 * Each tag contains an array of the functions to be hooked
 *
 * @since 2.9.7 Actions API simplified: register your custom action tag on the fly
 *
 * @var array RosarioSIS actions
 */
$RosarioActions = array();
/*	'Students/Student.php|header' => array(),
	'Students/Student.php|create_student_checks' => array(),
	'Students/Student.php|create_student' => array(),
	'Students/Student.php|update_student_checks' => array(),
	'Students/Student.php|update_student' => array(),
	'Students/Student.php|upload_student_photo' => array(),
	'Students/Student.php|add_student_address' => array(),
	'Students/Student.php|update_student_address' => array(),

	'Students/AddUsers.php|user_assign_role' => array(),
	'Students/AddUsers.php|user_unassign_role' => array(),

	'Users/User.php|header' => array(),
	'Users/User.php|create_user_checks' => array(),
	'Users/User.php|create_user' => array(),
	'Users/User.php|update_user_checks' => array(),
	'Users/User.php|update_user' => array(),
	'Users/User.php|upload_user_photo' => array(),
	'Users/User.php|delete_user' => array(),

	'Users/Preferences.php|update_password_checks' => array(),
	'Users/Preferences.php|update_password' => array(),

	'Users/AddStudents.php|user_assign_role' => array(),
	'Users/AddStudents.php|user_unassign_role' => array(),

	'Custom/CreateParents.php|create_user' => array(),
	'Custom/CreateParents.php|user_assign_role' => array(),

	'Grades/Assignments.php|create_assignment' => array(),
	'Grades/Assignments.php|update_assignment' => array(),
	'Grades/Assignments.php|delete_assignment' => array(),

	'Scheduling/Courses.php|header' => array(),
	'Scheduling/Courses.php|create_course_subject' => array(),
	'Scheduling/Courses.php|create_course' => array(),
	'Scheduling/Courses.php|create_course_period' => array(),
	'Scheduling/Courses.php|update_course_subject' => array(),
	'Scheduling/Courses.php|update_course' => array(),
	'Scheduling/Courses.php|update_course_period' => array(),
	'Scheduling/Courses.php|delete_course_subject' => array(),
	'Scheduling/Courses.php|delete_course' => array(),
	'Scheduling/Courses.php|delete_course_period' => array(),

	'Scheduling/MassSchedule.php|schedule_student' => array(),
	'Scheduling/MassDrops.php|drop_student' => array(),
	'Scheduling/Schedule.php|drop_student' => array(),
	'Scheduling/Schedule.php|schedule_student' => array(),
	'Scheduling/Scheduler.php|schedule_student' => array(),

	'School_Setup/Calendar.php|event_field' => array(),
	'School_Setup/Calendar.php|create_calendar_event' => array(),
	'School_Setup/Calendar.php|update_calendar_event' => array(),
	'School_Setup/Calendar.php|delete_calendar_event' => array(),

	'School_Setup/PortalNotes.php|portal_note_field' => array(),
	'School_Setup/PortalNotes.php|create_portal_note' => array(),
	'School_Setup/PortalNotes.php|update_portal_note' => array(),
	'School_Setup/PortalNotes.php|delete_portal_note' => array(),

	'School_Setup/Rollover.php|rollover_warnings' => array(),
	'School_Setup/Rollover.php|rollover_checks' => array(),
	// @depreacted since 4.5 user School_Setup/Rollover.php|rollover_after action hook!
	'School_Setup/Rollover.php|rollover_staff' => array(),
	// @depreacted since 4.5 user School_Setup/Rollover.php|rollover_after action hook!
	'School_Setup/Rollover.php|rollover_course_subjects' => array(),
	// @depreacted since 4.5 user School_Setup/Rollover.php|rollover_after action hook!
	'School_Setup/Rollover.php|rollover_courses' => array(),
	// @depreacted since 4.5 user School_Setup/Rollover.php|rollover_after action hook!
	'School_Setup/Rollover.php|rollover_course_periods' => array(),

	/**
	 * Portal Alerts.
	 *
	 * @since 2.9
	 */
	/*'misc/Portal.php|portal_alerts' => array(),

	/**
	 * Bottom Buttons.
	 *
	 * @since 2.9
	 */
	/*'Bottom.php|bottom_buttons' => array(),

	/**
	 * Create Student Account.
	 *
	 * @since 2.9.8
	 */
	/*'Students/Student.php|account_created' => array(),

	/**
	 * Login.
	 *
	 * @since 2.9.8
	 */
	/*'index.php|login_check' => array(),

	/**
	 * PDF start.
	 *
	 * @since 3.4
	 */
	/*'functions/PDF.php|pdf_start' => array(),

	/**
	 * Student Payments Header.
	 *
	 * @since 3.4.1
	 */
	/*'Student_Billing/StudentPayments.php|student_payments_header' => array(),

	/**
	 * Before Send.
	 *
	 * @since 3.6.1
	 */
	/*'ProgramFunctions/SendEmail.fnc.php|before_send' => array(),

	/**
	 * Warehouse Header Head.
	 *
	 * @since 3.8
	 */
	/*'Warehouse.php|header_head' => array(),

	/**
	 * Warehouse Header Head.
	 *
	 * @since 3.8
	 */
	/*'Warehouse.php|header_head' => array(),

	/**
	 * Warehouse Footer.
	 *
	 * @since 3.8
	 */
	/*'Warehouse.php|footer' => array(),

	/**
	 * Take Attendance.
	 *
	 * @since 3.9
	 */
	/*'Attendance/TakeAttendance.php|insert_attendance' => array(),
	'Attendance/TakeAttendance.php|update_attendance' => array(),
	'Attendance/TakeAttendance.php|header' => array(),

	/**
	 * Student Fees Header.
	 *
	 * @since 3.9
	 */
	/*'Student_Billing/StudentFees.php|student_fees_header' => array(),

	/**
	 * List Before and After.
	 *
	 * @since 4.0
	 */
	/*'functions/ListOutput.fnc.php|list_before' => array(),
	'functions/ListOutput.fnc.php|list_after' => array(),

	/**
	 * Report Cards array.
	 *
	 * @since 4.0
	 */
	/*'Grades/ReportCards.php|report_cards_array' => array(),

	/**
	 * Assignments & Assignment Submission header.
	 *
	 * @since 4.1
	 */
	/*'Grades/Assignments.php|header' => array(),
	'Grades/includes/StudentAssignments.fnc.php|submission_header' => array(),

	/**
	 * Assignment Grades Submission column.
	 *
	 * @since 4.2
	 */
	/*'Grades/Assignments.php|header' => array(),
	'Grades/includes/StudentAssignments.fnc.php|grades_submission_column' => array(),

	/**
	 * DBQuery after.
	 *
	 * @since 4.4
	 */
	/*'database.inc.php|dbquery_after' => array(),

	/**
	 * Warehouse Header.
	 *
	 * @since 4.4
	 */
	/*'Warehouse.php|header' => array(),

	/**
	 * Calendar Header.
	 *
	 * @since 4.5
	 */
	/*'School_Setup/Calendar.php|header' => array(),

	/**
	 * Report Cards Header.
	 *
	 * @since 4.5
	 */
	/*'Grades/ReportCards.php|header' => array(),

	/**
	 * Report Cards PDF HTML array.
	 *
	 * @since 4.5
	 */
	/*'Grades/ReportCards.php|report_cards_html_array' => array(),

	/**
	 * Report Cards PDF Header.
	 *
	 * @since 4.5
	 */
	/*'Grades/includes/ReportCards.fnc.php|pdf_header' => array(),

	/**
	 * Referral Input.
	 *
	 * @since 4.5
	 */
	/*'Discipline/includes/Referral.fnc.php|referral_input' => array(),

	/**
	 * Rollover After.
	 *
	 * @since 4.5
	 */
	/*'School_Setup/Rollover.php|rollover_after' => array(),

	/**
	 * Transcripts Header.
	 *
	 * @since 4.8
	 */
	/*'Grades/Transcripts.php|header' => array(),

	/**
	 * Transcripts PDF HTML array.
	 *
	 * @since 4.8
	 */
	/*'Grades/Transcripts.php|transcripts_html_array' => array(),

	/**
	 * Transcript PDF Header.
	 *
	 * @since 4.8
	 */
	/*'Grades/includes/Transcripts.fnc.php|pdf_header' => array(),

	/**
	 * Transcript PDF Footer.
	 *
	 * @since 4.8
	 */
	/*'Grades/includes/Transcripts.fnc.php|pdf_footer' => array(),
);*/


/**
 * Hooks a function on to a specific action.
 *
 * Actions are the hooks that the RosarioSIS core launches at specific points
 * during execution, or when specific events occur. Plugins can specify that
 * one or more of its PHP functions are executed at these points, using the
 * Action API.
 *
 * @global array    $RosarioActions
 *
 * @param  string   $tag              The name of the action to which the $function_to_add is hooked.
 * @param  callback $function_to_add  The name of the function you wish to be called.
 * @param  int      $accepted_args    optional. The number of arguments the function accept (default 1).
 * @param  int      $priority         optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
 *
 * @return boolean  true
 */
function add_action( $tag, $function_to_add, $accepted_args = 1, $priority = 10 )
{
	global $RosarioActions;

	// Check if function exists.
	if ( function_exists( (string) $function_to_add ) )
	{
		$RosarioActions[ $tag ][ $priority ][ $function_to_add ] = (int) $accepted_args;
	}

	return true;
}


/**
 * Removes a function from a specified action hook.
 *
 * This function removes a function attached to a specified action hook. This
 * method can be used to remove default functions attached to a specific filter
 * hook and possibly replace them with a substitute.
 *
 * @global array    $RosarioActions
 *
 * @param  string   $tag                The action hook to which the function to be removed is hooked.
 * @param  callback $function_to_remove The name of the function which should be removed.
 *
 * @return boolean  Whether the function is removed.
 */
function remove_action( $tag, $function_to_remove ) {

	global $RosarioActions;

	// Check if tag exists.
	if ( array_key_exists( (string) $tag, $RosarioActions ) )
	{
		// Check if function previously added.
		if ( array_key_exists( (string) $function_to_remove, $RosarioActions[ $tag ] ) )
		{
			unset( $RosarioActions[ $tag ][ $function_to_remove ] );

			return true;
		}
	}

	return false;
}


/**
 * Execute functions hooked on a specific action hook.
 *
 * This function invokes all functions attached to action hook $tag. It is
 * possible to create new action hooks by simply calling this function,
 * specifying the name of the new hook using the <tt>$tag</tt> parameter.
 *
 * @global array  $RosarioActions
 *
 * @param  string $tag The name of the action to be executed.
 * @param  mixed  $arg Optional additional arguments which are passed on to the functions hooked to the action.
 *
 * @return null   Will return null if $tag does not exist in $RosarioActions array
 */
function do_action( $tag, $arg = '' )
{
	global $RosarioActions;

	$args = array();

	// By default, the only argument passed to the function is the tag.
	$args[] = $tag;

	if ( ! is_array( $arg ) )
	{
		$args[] = $arg;
	}
	else
		$args = array_merge( $args, $arg );

	// Check if tag exists.
	if ( array_key_exists( (string) $tag, $RosarioActions ) )
	{
		foreach ( (array) $RosarioActions[ $tag ] as $functions )
		{
			foreach ( (array) $functions as $function => $accepted_args )
			{
				if ( ! is_null( $function ) )
				{
					call_user_func_array( $function, array_slice( $args, 0, (int) $accepted_args ) );
				}
			}
		}
	}
	else
		return null;
}
