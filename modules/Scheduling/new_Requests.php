<?php
DrawHeader( ProgramTitle() );

Widgets( 'request' );

if ( ! UserStudentID() )
{
	echo '<br />';
}

Search( 'student_id', $extra );

if ( ! $_REQUEST['modfunc'] && UserStudentID() )
{
	$_REQUEST['modfunc'] = 'choose';
}

if ( $_REQUEST['modfunc'] == 'verify' )
{
	$courses_RET = DBGet( "SELECT TITLE,COURSE_ID,SUBJECT_ID
		FROM courses
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'", [], [ 'COURSE_ID' ] );

	DBQuery( "DELETE FROM schedule_requests
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'" );

	foreach ( (array) $_REQUEST['courses'] as $subject => $courses )
	{
		$courses_count = count( $courses );

		for ( $i = 0; $i < $courses_count; $i++ )
		{
			$course = $courses[$i];

			if ( ! $course )
			{
				continue;
			}

			$sql = "INSERT INTO schedule_requests (SYEAR,SCHOOL_ID,STUDENT_ID,SUBJECT_ID,COURSE_ID,MARKING_PERIOD_ID,WITH_TEACHER_ID,NOT_TEACHER_ID,WITH_PERIOD_ID,NOT_PERIOD_ID)
			VALUES('" . UserSyear() . "','" .
			UserSchool() . "','" . UserStudentID() . "','" . $courses_RET[$course][1]['SUBJECT_ID'] . "','" .
			$course . "',NULL,'" . $_REQUEST['with_teacher'][$subject][$i] . "','" .
			$_REQUEST['without_teacher'][$subject][$i] . "','" . $_REQUEST['with_period'][$subject][$i] . "','" .
			$_REQUEST['without_period'][$subject][$i] . "')";

			DBQuery( $sql );
		}
	}

	echo ErrorMessage( $error, _( 'Error' ) );

	$_SCHEDULER['student_id'] = UserStudentID();
	$_SCHEDULER['dont_run'] = true;
	require_once 'modules/Scheduling/Scheduler.php';
	$_REQUEST['modfunc'] = 'choose';
}

if ( $_REQUEST['modfunc'] == 'choose' )
{
	$functions = [ 'WITH_PERIOD_ID' => '_makeWithSelects', 'NOT_PERIOD_ID' => '_makeWithoutSelects' ];
	$requests_RET = DBGet( "SELECT sr.COURSE_ID,c.COURSE_TITLE,sr.WITH_PERIOD_ID,sr.NOT_PERIOD_ID,sr.WITH_TEACHER_ID,sr.NOT_TEACHER_ID
	FROM schedule_requests sr,courses c
	WHERE sr.SYEAR='" . UserSyear() . "'
	AND sr.STUDENT_ID='" . UserStudentID() . "'
	AND sr.COURSE_ID=c.COURSE_ID", $functions );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=verify' ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );

	$columns = [ '' ];
	ListOutput( $requests_RET, $columns, 'Request', 'Requests' );

	echo '<div class="center">' . SubmitButton() . '</div></form>';
}
