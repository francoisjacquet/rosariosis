<?php

$_REQUEST['modfunc'] = 'choose_course';

if ( empty( $_REQUEST['course_period_id'] ) )
{
	include 'modules/Scheduling/Courses.php';
}
else
{
	$course = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cs.TITLE AS SUBJECT_TITLE
		FROM course_periods cp,courses c,course_subjects cs
		WHERE c.COURSE_ID=cp.COURSE_ID
		AND cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'
		AND c.SUBJECT_ID=cs.SUBJECT_ID
		AND cs.SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'" );

	$last_year = $_REQUEST['last_year'] == 'true' ? 'ly_' : '';

	// @since 6.5 Course Widget: add Subject and Not options.
	$html_to_escape = '<span id="w_course_period_title">' .$course[1]['TITLE'] . '</span>
		<span id="w_course_title" class="hide">' . $course[1]['COURSE_TITLE'] . '</span>
		<span id="w_subject_title" class="hide">' . $course[1]['SUBJECT_TITLE'] . '</span>';

	$html_to_escape .= '<input type="hidden" name="w_' . $last_year . 'course_period_id" value="' . AttrEscape( $_REQUEST['course_period_id'] ) . '" />
	<input type="hidden" name="w_' . $last_year . 'course_id" value="' . AttrEscape( $_REQUEST['course_id'] ) . '" />
	<input type="hidden" name="w_' . $last_year . 'subject_id" value="' . AttrEscape( $_REQUEST['subject_id'] ) . '" />';

	$html_to_escape .= '<br />
	<label><input type="checkbox" name="w_' . $last_year . 'course_period_id_not" value="Y" /> ' .
		_( 'Not' ) . '</label>
	<label><select name="w_' . $last_year . 'course_period_id_which"
		onchange="wCourseTitleUpdate(this.value);" autocomplete="off">
	<option value="course_period"> ' . _( 'Course Period' ) . '</option>
	<option value="course"> ' . _( 'Course' ) . '</option>
	<option value="subject"> ' . _( 'Subject' ) . '</option>
	</select></label>';

	// JS function to update Title depending on selected option.
	// @link https://stackoverflow.com/questions/1197575/can-scripts-be-inserted-with-innerhtml#answer-7054216
	$js_to_escape = 'var wCourseTitleUpdate = function(sel){
		$("#w_course_period_title,#w_course_title,#w_subject_title").addClass( "hide" );
		$("#w_" + sel + "_title").removeClass( "hide" );
	};';

	echo '<script>
		var wCourseTitleUpdateScript = opener.document.createElement("script"),
		courseDiv = opener.document.getElementById("' . $last_year . 'course_div");

		wCourseTitleUpdateScript.text = ' . json_encode( $js_to_escape ) . '
		courseDiv.parentNode.insertBefore(wCourseTitleUpdateScript, courseDiv);
		courseDiv.innerHTML = ' . json_encode( $html_to_escape ) . '
		window.close();
	</script>';
}
