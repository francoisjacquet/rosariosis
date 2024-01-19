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
$RosarioActions = [];
/**  @example 'action_tag' => [ $arg1, $arg2, &$arg_passed_by_ref... ]
	'Students/Student.php|header' => [],
	'Students/Student.php|create_student_checks' => [],
	'Students/Student.php|create_student' => [],
	'Students/Student.php|update_student_checks' => [],
	'Students/Student.php|update_student' => [],
	'Students/Student.php|upload_student_photo' => [],
	'Students/Student.php|add_student_address' => [],
	'Students/Student.php|update_student_address' => [],

	'Students/AddUsers.php|user_assign_role' => [],
	'Students/AddUsers.php|user_unassign_role' => [],

	'Users/User.php|header' => [],
	'Users/User.php|create_user_checks' => [],
	'Users/User.php|create_user' => [],
	'Users/User.php|update_user_checks' => [],
	'Users/User.php|update_user' => [],
	'Users/User.php|upload_user_photo' => [],
	'Users/User.php|delete_user' => [],

	'Users/Preferences.php|update_password_checks' => [],
	'Users/Preferences.php|update_password' => [],

	'Users/AddStudents.php|user_assign_role' => [],
	'Users/AddStudents.php|user_unassign_role' => [],

	'Custom/CreateParents.php|create_user' => [],
	'Custom/CreateParents.php|user_assign_role' => [],

	'Grades/Assignments.php|create_assignment' => [],
	'Grades/Assignments.php|update_assignment' => [],
	'Grades/Assignments.php|delete_assignment' => [],

	'Scheduling/Courses.php|header' => [],
	'Scheduling/Courses.php|create_course_subject' => [],
	'Scheduling/Courses.php|create_course' => [],
	'Scheduling/Courses.php|create_course_period' => [],
	'Scheduling/Courses.php|update_course_subject' => [],
	'Scheduling/Courses.php|update_course' => [],
	'Scheduling/Courses.php|update_course_period' => [],
	'Scheduling/Courses.php|delete_course_subject' => [],
	'Scheduling/Courses.php|delete_course' => [],
	'Scheduling/Courses.php|delete_course_period' => [],

	'Scheduling/MassSchedule.php|schedule_student' => [],
	'Scheduling/MassDrops.php|drop_student' => [],
	'Scheduling/Schedule.php|drop_student' => [],
	'Scheduling/Schedule.php|schedule_student' => [],
	'Scheduling/Scheduler.php|schedule_student' => [],

	'School_Setup/Calendar.php|event_field' => [],
	'School_Setup/Calendar.php|create_calendar_event' => [],
	'School_Setup/Calendar.php|update_calendar_event' => [],
	'School_Setup/Calendar.php|delete_calendar_event' => [],

	'School_Setup/PortalNotes.php|portal_note_field' => [ $id ],
	'School_Setup/PortalNotes.php|create_portal_note' => [],
	'School_Setup/PortalNotes.php|update_portal_note' => [],
	'School_Setup/PortalNotes.php|delete_portal_note' => [],

	'School_Setup/Rollover.php|rollover_warnings' => [],
	'School_Setup/Rollover.php|rollover_checks' => [],

	/**
	 * Portal Alerts.
	 *
	 * @since 2.9
	 */
	/*'misc/Portal.php|portal_alerts' => [],

	/**
	 * Bottom Buttons.
	 *
	 * @since 2.9
	 */
	/*'Bottom.php|bottom_buttons' => [],

	/**
	 * Create Student Account.
	 *
	 * @since 2.9.8
	 */
	/*'Students/Student.php|account_created' => [],

	/**
	 * Login.
	 *
	 * @since 2.9.8
	 */
	/*'index.php|login_check' => [ $username ],

	/**
	 * PDF start.
	 *
	 * @since 3.4
	 */
	/*'functions/PDF.php|pdf_start' => [],

	/**
	 * Student Payments Header.
	 *
	 * @since 3.4.1
	 */
	/*'Student_Billing/StudentPayments.php|student_payments_header' => [],

	/**
	 * Before Send.
	 *
	 * @since 3.6.1
	 */
	/*'ProgramFunctions/SendEmail.fnc.php|before_send' => [],

	/**
	 * Warehouse Header Head.
	 *
	 * @since 3.8
	 */
	/*'Warehouse.php|header_head' => [],

	/**
	 * Warehouse Footer.
	 *
	 * @since 3.8
	 */
	/*'Warehouse.php|footer' => [],

	/**
	 * Take Attendance.
	 *
	 * @since 3.9
	 */
	/*'Attendance/TakeAttendance.php|insert_attendance' => [],
	'Attendance/TakeAttendance.php|update_attendance' => [],
	'Attendance/TakeAttendance.php|header' => [],

	/**
	 * Student Fees Header.
	 *
	 * @since 3.9
	 */
	/*'Student_Billing/StudentFees.php|student_fees_header' => [],

	/**
	 * List Before and After.
	 *
	 * @since 4.0
	 */
	/*'functions/ListOutput.fnc.php|list_before' => [],
	'functions/ListOutput.fnc.php|list_after' => [],

	/**
	 * Report Cards array.
	 *
	 * @since 4.0
	 */
	/*'Grades/ReportCards.php|report_cards_array' => [],

	/**
	 * Assignments & Assignment Submission header.
	 *
	 * @since 4.1
	 */
	/*'Grades/Assignments.php|header' => [],
	'Grades/includes/StudentAssignments.fnc.php|submission_header' => [],

	/**
	 * Assignment Grades Submission column.
	 *
	 * @since 4.2
	 */
	/*'Grades/Assignments.php|header' => [],
	'Grades/includes/StudentAssignments.fnc.php|grades_submission_column' => [],

	/**
	 * DBQuery after.
	 *
	 * @since 4.4
	 */
	/*'database.inc.php|dbquery_after' => [ $sql, $result ],

	/**
	 * Warehouse Header.
	 *
	 * @since 4.4
	 */
	/*'Warehouse.php|header' => [],

	/**
	 * Calendar Header.
	 *
	 * @since 4.5
	 */
	/*'School_Setup/Calendar.php|header' => [],

	/**
	 * Report Cards Header.
	 *
	 * @since 4.5
	 */
	/*'Grades/ReportCards.php|header' => [],

	/**
	 * Report Cards PDF HTML array.
	 *
	 * @since 4.5
	 */
	/*'Grades/ReportCards.php|report_cards_html_array' => [],

	/**
	 * Report Cards PDF Header.
	 *
	 * @since 4.5
	 */
	/*'Grades/includes/ReportCards.fnc.php|pdf_header' => [ $student_id ],

	/**
	 * Referral Input.
	 *
	 * @since 4.5
	 */
	/*'Discipline/includes/Referral.fnc.php|referral_input' => [
		'category' => $category,
		'value' => $value,
		'new' => $new,
		'input' => &$input,
	],

	/**
	 * Rollover After.
	 *
	 * @since 4.5
	 */
	/*'School_Setup/Rollover.php|rollover_after' => [],

	/**
	 * Transcripts Header.
	 *
	 * @since 4.8
	 */
	/*'Grades/Transcripts.php|header' => [],

	/**
	 * Transcripts PDF HTML array.
	 *
	 * @since 4.8
	 */
	/*'Grades/Transcripts.php|transcripts_html_array' => [],

	/**
	 * Transcript PDF Header.
	 *
	 * @since 4.8
	 */
	/*'Grades/includes/Transcripts.fnc.php|pdf_header' => [ $student['ID'], &$header_html ],

	/**
	 * Transcript PDF Footer.
	 *
	 * @since 4.8
	 */
	/*'Grades/includes/Transcripts.fnc.php|pdf_footer' => [ $student['ID'], $last_grade, &$footer_html ],

	/**
	 * TinyMCE before init.
	 *
	 * @since 5.3
	 */
	/*'functions/Inputs.php|tinymce_before_init' => [],

	/**
	 * Match password.
	 *
	 * @since 5.4
	 */
	/*'functions/Password.php|match_password' => [ &$crypted, $plain ],

	/**
	 * Copy School Header.
	 *
	 * @since 5.8
	 */
	/*'School_Setup/CopySchool.php|header' => [],

	/**
	 * Copy School.
	 *
	 * @since 5.8
	 */
	/*'School_Setup/CopySchool.php|copy_school' => [],

	/**
	 * Update School.
	 *
	 * @since 5.8
	 */
	/*'School_Setup/Schools.php|update_school' => [],

	/**
	 * Delete School.
	 *
	 * @since 5.8
	 */
	/*'School_Setup/Schools.php|delete_school' => [],

	/**
	 * Purchase Element.
	 *
	 * @since 5.8
	 */
	/*'Billing_Elements/Elements.php|purchase_element' => [],

	/**
	 * Configuration School Table.
	 *
	 * @since 5.8
	 */
	/*'School_Setup/Configuration.php|school_table' => [],

	/**
	 * Configuration School Tabs.
	 *
	 * @since 6.2
	 */
	/*'School_Setup/Configuration.php|school_tabs' => [ &$tabs ],

	/**
	 * Before First Login form.
	 *
	 * @since 7.3
	 */
	/*'index.php|before_first_login_form' => [],

	/**
	 * Report Cards PDF footer.
	 *
	 * @since 7.5
	 */
	/*'Grades/includes/ReportCards.fnc.php|pdf_footer' => [ $student_id, &$freetext ],

	/**
	 * Login form link.
	 *
	 * @since 7.6
	 */
	/*'index.php|login_form_link' => [],

	/**
	 * Export fields list (form).
	 * Add or remove any field (& category) to/from the global variable $fields_list.
	 *
	 * @since 8.1
	 */
	/*'misc/Export.php|fields_list' => [],

	/**
	 * Export fields list + extra SQL (student list).
	 * Add or remove any field to/from the global variable $fields_list.
	 * Add or remove SQL for any field to/from the global variable $extra.
	 * Use in conjonction with the 'misc/Export.php|fields_list' action hook.
	 *
	 * @since 8.1
	 */
	/*'misc/Export.php|fields_list_extra_sql' => [],

	/**
	 * Send email error.
	 *
	 * @since 8.7
	 */
	/*'ProgramFunctions/SendEmail.fnc.php|send_error' => [ $e->errorMessage() ],

	/**
	 * Widgets init
	 * Add your add-on custom widgets to the $myextra['Widgets'] var:
	 * $myextra['Widgets']['Addon_Name'] = [ 'widget_1', 'widget_2' ];
	 * And load your custom \Addon_Name\Widget_[widget_name] class(es).
	 *
	 * @since 10.4
	 */
	/*'functions/Widgets.fnc.php|widgets_init' => [ $item, &$myextra ],

	/**
	 * Staff Widgets init
	 * Add your add-on custom staff widgets to the $myextra['Widgets'] var:
	 * $myextra['Widgets']['Addon_Name'] = [ 'widget_1', 'widget_2' ];
	 * And load your custom \Addon_Name\StaffWidget_[widget_name] class(es).
	 *
	 * @since 10.4
	 */
	/*'functions/StaffWidgets.fnc.php|widgets_init' => [ $item, &$myextra ],

	/**
	 * Filter Total Minutes Present
	 *
	 * @since 11.2
	 *
	 * @example add_action( 'Attendance/includes/UpdateAttendanceDaily.fnc.php|total_minutes', 'MyFilter', 5 );
	 * @example function MyFilter( $tag, &$total_present, $total_minutes, $total_absent, $total_half ) { $total_present = ... }
	 *
	 */
	/*'Attendance/includes/UpdateAttendanceDaily.fnc.php|total_minutes_present' => [],

	/**
	 * Filter each menu item in the loop
	 *
	 * @since 11.2
	 *
	 * @example add_action( 'Food_Service/Kiosk.php|menu_item_loop', 'MyFilter', 3 );
	 * @example function MyFilter( $tag, &$kiosk_menu_item, $item ) { $kiosk_menu_item = ... }
	 *
	 */
	/*'Food_Service/Kiosk.php|menu_item_loop' => [ &$kiosk_menu_item, $item ],

	/**
	 * Student Account fields table after
	 * Add your own fields
	 *
	 * @since 11.2
	 */
	/*'Food_Service/Students/Accounts.php|table_after' => [ $student ],

	/**
	 * Food Service tab fields table after
	 * Add your own fields
	 *
	 * @since 11.2
	 */
	/*'Food_Service/Student.inc.php|table_after' => [ $student ],

	/**
	 * Student Info tab fields after
	 * Add your own fields
	 * Note: increment $i var for every field you add.
	 *
	 * @since 11.4
	 */
	/*'Students/includes/Other_Info.inc.php|fields_after' => [ $per_row, &$i ],

	/**
	 * User Info tab fields after
	 * Add your own fields
	 * Note: increment $i var for every field you add.
	 *
	 * @since 11.4
	 */
	/*'Users/includes/Other_Info.inc.php|fields_after' => [ $per_row, &$i ],

	/**
	 * Filter Grade Level
	 * Note: edit the $return_value var
	 *
	 * @since 11.4
	 */
	/*'functions/GetGrade.fnc.php|filter_grade_level' => [ &$return_value, $column ],

	/**
	 * PDF Stop (wkhtmltopdf)
	 * Note: $pdf is a mikehaertl\wkhtmlto\Pdf object
	 *
	 * @since 11.4.1
	 */
	/*'functions/PDF.php|pdf_stop_pdf' => [ $pdf, $handle ],

	/**
	 * PDF Stop HTML (no wkhtmltopdf)
	 *
	 * @since 11.4.1
	 */
	/*'functions/PDF.php|pdf_stop_html' => [ $html, $handle ],

	/**
	 * Print Letters header
	 * Add your headers to
	 * @global $extra['extra_header_left']
	 *
	 * @since 11.4.1
	 */
	/*'Students/Letters.php|header' => [],

	/**
	 * Honor Roll header
	 * Add your headers to
	 * @global $extra['extra_header_left']
	 *
	 * @since 11.4.1
	 */
	/*'Grades/HonorRoll.php|header' => [],

	/**
	 * Template before get
	 * Filter &$modname var
	 *
	 * @since 11.4.1
	 */
	/*'ProgramFunctions/Template.fnc.php|before_get' => [ &$modname ],

	/**
	 * Template before save
	 * Filter &$modname, &$staff_id var
	 *
	 * @since 11.4.1
	 */
	/*'ProgramFunctions/Template.fnc.php|before_save' => [ &$modname, &$staff_id ],
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
		foreach ( $RosarioActions[ $tag ] as $priority => $actions )
		{
			// Check if function previously added.
			if ( array_key_exists( (string) $function_to_remove, $actions ) )
			{
				unset( $RosarioActions[ $tag ][ $priority ][ $function_to_remove ] );

				return true;
			}
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
 * @since 5.7.7 Sort functions by priority.
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

	$args = [];

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
		// Sort functions by priority.
		ksort( $RosarioActions[ $tag ] );

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
