<?php

/**
 * Hook tags are constructed the following way:
 * [modname]|[hook_name]
 * ex.: Students/Student.php|create_student
 * Each tag contains an array of the functions to be hooked
 */
$RosarioHooks = array(
	'Students/Student.php|header' => array(),
	'Students/Student.php|create_student_checks' => array(),
	'Students/Student.php|create_student' => array(),
	'Students/Student.php|update_student_checks' => array(),
	'Students/Student.php|update_student' => array(),
	'Students/Student.php|upload_student_photo' => array(),

	'Users/User.php|header' => array(),
	'Users/User.php|create_user_checks' => array(),
	'Users/User.php|create_user' => array(),
	'Users/User.php|update_user_checks' => array(),
	'Users/User.php|update_user' => array(),
	'Users/User.php|upload_user_photo' => array(),
	'Users/User.php|delete_user' => array(),

	'Users/Preferences.php|update_password_checks' => array(),
	'Users/Preferences.php|update_password' => array(),

	'Custom/CreateParents.php|create_user' => array(),
	'Custom/CreateParents.php|user_assign_role' => array(),

	'Grades/Assignments.php|create_assignment' => array(),
	'Grades/Assignments.php|update_assignment' => array(),
	'Grades/Assignments.php|delete_assignment' => array(),

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
	'Scheduling/Scheduler.php|schedule_student' => array(),

	'School_Setup/Calendar.php|event_field' => array(),
	'School_Setup/Calendar.php|create_calendar_event' => array(),
	'School_Setup/Calendar.php|update_calendar_event' => array(),
	'School_Setup/Calendar.php|delete_calendar_event' => array(),

	'School_Setup/PortalNotes.php|portal_note_field' => array(),
	'School_Setup/PortalNotes.php|create_portal_note' => array(),
	'School_Setup/PortalNotes.php|update_portal_note' => array(),
	'School_Setup/PortalNotes.php|delete_portal_note' => array(),

	'School_Setup/Rollover.php|rollover_staff' => array(),
	'School_Setup/Rollover.php|rollover_course_subjects' => array(),
	'School_Setup/Rollover.php|rollover_courses' => array(),
	'School_Setup/Rollover.php|rollover_course_periods' => array(),
);

/**
 * Hooks a function on to a specific action.
 *
 * Actions are the hooks that the RosarioSIS core launches at specific points
 * during execution, or when specific events occur. Plugins can specify that
 * one or more of its PHP functions are executed at these points, using the
 * Action API.
 *
 * @param string $tag The name of the action to which the $function_to_add is hooked.
 * @param callback $function_to_add The name of the function you wish to be called.
 * @param int $accepted_args optional. The number of arguments the function accept (default 0).
 */
function add_action($tag, $function_to_add, $accepted_args = 0)
{
	global $RosarioHooks;

	//check if function exists
	if (function_exists( (string) $function_to_add))
		//check if tag exists
		if (array_key_exists( (string) $tag, $RosarioHooks))
			$RosarioHooks[$tag][$function_to_add] = (int) $accepted_args;
}

/**
 * Removes a function from a specified action hook.
 *
 * This function removes a function attached to a specified action hook. This
 * method can be used to remove default functions attached to a specific filter
 * hook and possibly replace them with a substitute.
 *
 * @param string $tag The action hook to which the function to be removed is hooked.
 * @param callback $function_to_remove The name of the function which should be removed.
 * @return boolean Whether the function is removed.
 */
function remove_action($tag, $function_to_remove)
{
	global $RosarioHooks;

	//check if tag exists
	if (array_key_exists( (string) $tag, $RosarioHooks))
		//check if function previously added
		if (array_key_exists( (string) function_to_remove, $RosarioHooks[$tag]))
		{
			unset($RosarioHooks[$tag][$function_to_remove]);
			return true;
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
 * @param string $tag The name of the action to be executed.
 * @param mixed $arg,... Optional additional arguments which are passed on to the functions hooked to the action.
 * @return null Will return null if $tag does not exist in $RosarioHooks array
 */
function do_action($tag, $arg = '')
{
	global $RosarioHooks;

	$args = array();

	//by default, the only argument passed to the function is the tag
	$args[] = $tag;
	
	if (!is_array($arg))
	{
		$args[] = $arg;
	}
	else
		$args = $args + $arg;

	//check if tag exists
	if (array_key_exists( (string) $tag, $RosarioHooks))
	{
		foreach ( $RosarioHooks[$tag] as $function => $accepted_args )
			if ( !is_null($function) )
				call_user_func_array($function, array_slice($args, 0, (int) $accepted_args));
	}
	else
		return null;
}
?>
