<?php

$_REQUEST['modfunc'] = 'choose_course';

if ( empty( $_REQUEST['course_period_id'] ) )
{
	include 'modules/Scheduling/Courses.php';
}
else
{
	$course_title = DBGetOne( "SELECT TITLE
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'" );

	$html_to_escape = $course_title . '<input type="hidden" name="w_' . ( $_REQUEST['last_year'] == 'true' ? 'ly_' : '' ) .
	'course_period_id" value="' . $_REQUEST['course_period_id'] . '" /><br />
	<label><input type="radio" name="w_' . ( $_REQUEST['last_year'] == 'true' ? 'ly_' : '' ) .
	'course_period_id_which" value="course_period" checked /> ' . _( 'Course Period' ) . '</label>
	<label><input type="radio" name="w_' . ( $_REQUEST['last_year'] == 'true' ? 'ly_' : '' ) .
	'course_period_id_which" value="course" /> ' . _( 'Course' ) . '</label>';

	echo '<script>opener.document.getElementById("' . ( $_REQUEST['last_year'] == 'true' ? 'ly_' : '' ) .
	'course_div").innerHTML = ';

	echo json_encode( $html_to_escape );

	echo '; window.close();</script>';
}
