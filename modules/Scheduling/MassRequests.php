<?php
require_once 'modules/Scheduling/functions.inc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( $_SESSION['MassRequests.php'] )
	{
		if ( isset( $_REQUEST['student'] )
			&& is_array( $_REQUEST['student'] ) )
		{
			$current_RET = DBGet( "SELECT STUDENT_ID
				FROM SCHEDULE_REQUESTS
				WHERE COURSE_ID='" . $_REQUEST['MassRequests.php']['course_id'] . "'
				AND SYEAR='" . UserSyear() . "'", array(), array( 'STUDENT_ID' ) );

			foreach ( (array) $_REQUEST['student'] as $student_id )
			{
				if ( $current_RET[$student_id] )
				{
					continue;
				}

				$sql = "INSERT INTO SCHEDULE_REQUESTS (REQUEST_ID,SYEAR,SCHOOL_ID,
					STUDENT_ID,SUBJECT_ID,COURSE_ID,MARKING_PERIOD_ID,WITH_TEACHER_ID,
					NOT_TEACHER_ID,WITH_PERIOD_ID,NOT_PERIOD_ID)
					values(" . db_seq_nextval( 'SCHEDULE_REQUESTS_SEQ' ) . ",'" .
					UserSyear() . "','" . UserSchool() . "','" . $student_id . "','" .
					$_SESSION['MassRequests.php']['subject_id'] . "','" .
					$_SESSION['MassRequests.php']['course_id'] . "',NULL,'" .
					$_REQUEST['with_teacher_id'] . "','" .
					$_REQUEST['without_teacher_id'] . "','" .
					$_REQUEST['with_period_id'] . "','" .
					$_REQUEST['without_period_id'] . "')";

				DBQuery( $sql );
			}

			$note[] = button( 'check' ) . '&nbsp;' .
			_( 'This course has been added as a request for the selected students.' );
		}
		else
		{
			$error[] = _( 'You must choose at least one student.' );
		}
	}
	else
	{
		$error[] = _( 'You must choose a course.' );
	}

	// Unset modfunc redirect URL.
	RedirectURL( 'modfunc' );

	unset( $_SESSION['MassRequests.php'] );
}

if ( $_REQUEST['modfunc'] != 'choose_course' )
{
	DrawHeader( ProgramTitle() );

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Add Request to Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', _( 'Request to Add' ) );

		echo '<table><tr><td>&nbsp;</td><td><div id="course_div">';

		if ( $_SESSION['MassRequests.php'] )
		{
			$course_title = DBGetOne( "SELECT TITLE
				FROM COURSES
				WHERE COURSE_ID='" . $_SESSION['MassRequests.php']['course_id'] . "'" );

			echo $course_title;
		}

		echo '</div><a href="#" onclick=\'window.open("Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=choose_course","","scrollbars=yes,resizable=yes,width=800,height=400");\'>' . _( 'Choose a Course' ) . '</a></td></tr>';

		echo '<tr><td>' . _( 'With' ) . '</td><td>';

		echo '<table><tr class="st"><td>' . _( 'Teacher' ) . '</td><td><select name="with_teacher_id"><option value="">' . _( 'N/A' ) . '</option>';
		//FJ fix bug teacher's schools is NULL
		//$teachers_RET = DBGet( "SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME FROM STAFF WHERE SCHOOLS LIKE '%,".UserSchool().",%' AND SYEAR='".UserSyear()."' AND PROFILE='teacher' ORDER BY LAST_NAME,FIRST_NAME" );
		$teachers_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
			FROM STAFF
			WHERE (SCHOOLS LIKE '%," . UserSchool() . ",%' OR SCHOOLS IS NULL)
			AND SYEAR='" . UserSyear() . "'
			AND PROFILE='teacher'
			ORDER BY LAST_NAME,FIRST_NAME" );

		foreach ( (array) $teachers_RET as $teacher )
		{
			echo '<option value="' . $teacher['STAFF_ID'] . '">' . $teacher['FULL_NAME'] . '</option>';
		}

		echo '</select></td></tr><tr class="st"><td>' . _( 'Period' ) . '</td><td><select name="with_period_id"><option value="">' . _( 'N/A' ) . '</option>';

		$periods_RET = DBGet( "SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' ORDER BY SORT_ORDER" );

		foreach ( (array) $periods_RET as $period )
		{
			echo '<option value="' . $period['PERIOD_ID'] . '">' . $period['TITLE'] . '</option>';
		}

		echo '</select></td></tr></table>';

		echo '</td></tr><tr><td>' . _( 'Without' ) . '</td><td>';

		echo '<table><tr class="st"><td>' . _( 'Teacher' ) . '</td><td><select name="without_teacher_id"><option value="">' . _( 'N/A' ) . '</option>';

		foreach ( (array) $teachers_RET as $teacher )
		{
			echo '<option value="' . $teacher['STAFF_ID'] . '">' . $teacher['FULL_NAME'] . '</option>';
		}

		echo '</select></td></tr><tr class="st"><td>' . _( 'Period' ) . '</td><td><select name="without_period_id"><option value="">' . _( 'N/A' ) . '</option>';

		foreach ( (array) $periods_RET as $period )
		{
			echo '<option value="' . $period['PERIOD_ID'] . '">' . $period['TITLE'] . '</option>';
		}

		echo '</select></td></tr></table>';
		echo '</td></tr></table>';

		PopTable( 'footer' );

		echo '<br />';
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( $_REQUEST['search_modfunc'] != 'list' )
	{
		unset( $_SESSION['MassRequests.php'] );
	}

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
	$extra['new'] = true;

	Widgets( 'request' );
	MyWidgets( 'ly_course' );
	//Widgets('activity');

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Request to Selected Students' ) ) . "</div></form>";
	}
}

if ( $_REQUEST['modfunc'] == 'choose_course' )
{
//FJ fix bug window closed

	if ( empty( $_REQUEST['course_id'] ) )
	{
		include 'modules/Scheduling/Courses.php';
	}
	else
	{
		$_SESSION['MassRequests.php']['subject_id'] = isset( $_REQUEST['subject_id'] ) ? $_REQUEST['subject_id'] : null;
		$_SESSION['MassRequests.php']['course_id'] = isset( $_REQUEST['course_id'] ) ? $_REQUEST['course_id'] : null;

		$course_title = DBGetOne( "SELECT TITLE
			FROM COURSES
			WHERE COURSE_ID='" . $_SESSION['MassRequests.php']['course_id'] . "'" );

		echo '<script>opener.document.getElementById("course_div").innerHTML = ' .
		json_encode( $course_title ) . '; window.close();</script>';
	}
}
