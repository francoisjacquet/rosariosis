<?php

$_REQUEST['modfunc'] = 'choose_course';

if ( empty( $_REQUEST['course_id'] ) )
{
	include 'modules/Scheduling/Courses.php';
}
else
{
	$course_title = DBGetOne( "SELECT TITLE
		FROM courses
		WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'" );

	$html_to_escape = $course_title .
	'<input type="hidden" name="request_course_id" value="' . AttrEscape( $_REQUEST['course_id'] ) . '" /><br />
	<label><input type="checkbox" name="missing_request_course" value="Y" /> ' .
	_( 'Not Requested' ) . '</label>';

	echo '<script>opener.document.getElementById("request_div").innerHTML = ';

	echo json_encode( $html_to_escape );

	echo '; window.close();</script>';
}
