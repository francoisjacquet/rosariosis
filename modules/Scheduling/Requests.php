<?php
// Get Courses in AJAX, returns XML.
if ( $_REQUEST['modfunc'] === 'XMLHttpRequest' )
{
	header( "Content-Type: text/xml\n\n" );

	$courses_RET = DBGet( "SELECT c.COURSE_ID,c.TITLE FROM COURSES c WHERE " .
		( $_REQUEST['subject_id'] ? "c.SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "' AND " : '' ) .
		"UPPER(c.TITLE) LIKE '" . mb_strtoupper( $_REQUEST['course_title'] ) .
		"%' AND c.SYEAR='" . UserSyear() .
		"' AND c.SCHOOL_ID='" . UserSchool() . "'" );

	echo '<?phpxml version="1.0" standalone="yes"?><courses>';

	foreach ( (array) $courses_RET as $course )
	{
		echo '<course><id>' . $course['COURSE_ID'] . '</id>
		<title>' . htmlspecialchars( $course['TITLE'], ENT_QUOTES ) . '</title>
		</course>';
	}

	echo '</courses>';

	exit;
}

DrawHeader( ProgramTitle() );

// Allow Parents & Students to Edit if have permissions.
if ( User( 'PROFILE' ) !== 'admin'
	&& User( 'PROFILE' ) !== 'teacher' )
{
	$can_edit_from_where = " FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'";

	if ( User( 'PROFILE' ) !== 'student'
		&& ! User( 'PROFILE_ID' ) )
	{
		$can_edit_from_where = " FROM STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";
	}

	$can_edit_RET = DBGet( "SELECT MODNAME " . $can_edit_from_where .
		" AND MODNAME='Scheduling/Requests.php'
		AND CAN_EDIT='Y'" );

	if ( $can_edit_RET )
	{
		$_ROSARIO['allow_edit'] = true;
	}
}

Widgets( 'request' );

Search( 'student_id', $extra );

// Remove.
if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Request' ) ) )
	{
		DBQuery( "DELETE FROM SCHEDULE_REQUESTS
			WHERE REQUEST_ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

// Update.
if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		foreach ( (array) $_REQUEST['values'] as $request_id => $columns )
		{
			$sql = "UPDATE SCHEDULE_REQUESTS SET ";

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) .
				" WHERE STUDENT_ID='" . UserStudentID() . "'
				AND REQUEST_ID='" . $request_id . "'";

			DBQuery( $sql );
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

// Add.
if ( $_REQUEST['modfunc'] === 'add' )
{
	if ( $_REQUEST['course']
		&& AllowEdit() )
	{
		$course_id = $_REQUEST['course'];

		$subject_id = DBGetOne( "SELECT SUBJECT_ID
			FROM COURSES
			WHERE COURSE_ID='" . $course_id . "'" );

		DBQuery( "INSERT INTO SCHEDULE_REQUESTS (REQUEST_ID,SYEAR,SCHOOL_ID,STUDENT_ID,SUBJECT_ID,COURSE_ID)
			SELECT " . db_seq_nextval( 'SCHEDULE_REQUESTS_SEQ' ) . ",'" .
				UserSyear() . "','" .
				UserSchool() . "','" .
				UserStudentID() . "','" .
				$subject_id . "','" .
				$course_id . "'
			WHERE NOT EXISTS (SELECT COURSE_ID
				FROM SCHEDULE_REQUESTS
				WHERE SYEAR='" .UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND STUDENT_ID='" . UserStudentID() . "'
				AND COURSE_ID='" . $course_id . "')" );
	}

	// Unset modfunc & course & redirect URL.
	RedirectURL( array( 'modfunc', 'course' ) );
}

if ( ! $_REQUEST['modfunc']
	&& UserStudentID() )
{
?>
<script>
function SendXMLRequest(subject_id,course)
{
	if (window.XMLHttpRequest)
		connection = new XMLHttpRequest();
	else if (window.ActiveXObject)
		connection = new ActiveXObject("Microsoft.XMLHTTP");
	connection.onreadystatechange = processRequest;
	connection.open("GET","Modules.php?modname=<?php echo $_REQUEST['modname']; ?>&_ROSARIO_PDF=true&modfunc=XMLHttpRequest&subject_id=" + subject_id + "&course_title=" + encodeURIComponent(course), true );
	connection.send(null);
}

function doOnClick(course)
{
	ajaxLink("Modules.php?modname=<?php echo $_REQUEST['modname']; ?>&modfunc=add&course=" + course);
}

function processRequest()
{
	// LOADED && ACCEPTED
	if (connection.readyState == 4 && connection.status == 200)
	{
		XMLResponse = connection.responseXML;
		document.getElementById("courses_div").style.display = "block";
		course_list = XMLResponse.getElementsByTagName("courses");
		course_list = course_list[0];
		courses = course_list.getElementsByTagName("course");

		for(i=0;i<courses.length;i++)
		{
			id = courses[i].getElementsByTagName("id")[0].firstChild.data;
			title = courses[i].getElementsByTagName("title")[0].firstChild.data;
			document.getElementById("courses_div").innerHTML += "<a onclick=\"doOnClick(\'"+ id +"\'); return false;\" href=\"#\">" + title + "</a><br />";
		}

		if ( courses.length === 0 )
		{
			document.getElementById("courses_div").innerHTML += <?php echo json_encode( _( 'No courses found' ) ); ?>;
		}
	}
}
</script>
<?php

	$functions = array(
		'COURSE' => '_makeCourse',
		'WITH_TEACHER_ID' => '_makeTeacher',
		'WITH_PERIOD_ID' => '_makePeriod',
	);

	$requests_RET = DBGet( "SELECT r.REQUEST_ID,c.TITLE as COURSE,r.COURSE_ID,
		r.MARKING_PERIOD_ID,r.WITH_TEACHER_ID,r.NOT_TEACHER_ID,r.WITH_PERIOD_ID,r.NOT_PERIOD_ID
		FROM SCHEDULE_REQUESTS r,COURSES c
		WHERE r.COURSE_ID=c.COURSE_ID
		AND r.SYEAR='" . UserSyear() . "'
		AND r.STUDENT_ID='" . UserStudentID() . "'", $functions );

	$columns = array(
		'COURSE' => _( 'Course' ),
		'WITH_TEACHER_ID' => _( 'Teacher' ),
		'WITH_PERIOD_ID' => _( 'Period' ),
	);

	// $link['add']['html'] = array('COURSE_ID'=>_makeCourse('','COURSE_ID'),'WITH_TEACHER_ID'=>_makeTeacher('','WITH_TEACHER_ID'),'WITH_PERIOD_ID'=>_makePeriod('','WITH_PERIOD_ID'),'MARKING_PERIOD_ID'=>_makeMP('','MARKING_PERIOD_ID'));
	$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
		FROM COURSE_SUBJECTS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	$subjects = '<select name="subject_id" onchange="document.getElementById(\'courses_div\').innerHTML = \'\';SendXMLRequest(this.form.subject_id.options[this.form.subject_id.selectedIndex].value,this.form.course_title.value);">';
	$subjects .= '<option value="">' . _( 'All Subjects' ) . '</option>';

	foreach ( (array) $subjects_RET as $subject )
	{
		$subjects .= '<option value="' . $subject['SUBJECT_ID'] . '">' . $subject['TITLE'] . '</option>';
	}

	$subjects .= '</select>';

	$link['remove'] = array(
		'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove',
		'variables' => array( 'id' => 'REQUEST_ID' ),
	);

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

	DrawHeader( '', SubmitButton() );

	$link['add']['span'] = ' ' . _( 'Add a Request' ) .
		': <span class="nobr"> ' . _( 'Subject' ) . ' ' . $subjects .
		' ' . _( 'Course Title' ) .
		' <input type="text" id="course_title" name="course_title" onkeypress="if (event.keyCode==13)return false;" onkeyup="document.getElementById(\'courses_div\').innerHTML = \'\';SendXMLRequest(this.form.subject_id.options[this.form.subject_id.selectedIndex].value,this.form.course_title.value);"></span>
		<div id="courses_div"></div>';

	echo '<div style="position:relative;">';

	ListOutput(
		$requests_RET,
		$columns,
		'Request',
		'Requests',
		$link
	);

	echo '</div>';

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

function _makeCourse( $value, $column )
{
	return $value;
}

function _makeTeacher( $value, $column )
{
	global $THIS_RET;

	$teachers_RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		s.STAFF_ID AS TEACHER_ID
		FROM STAFF s,COURSE_PERIODS cp
		WHERE s.STAFF_ID=cp.TEACHER_ID
		AND cp.COURSE_ID='" . $THIS_RET['COURSE_ID'] . "'" );

	$options = array();

	foreach ( (array) $teachers_RET as $teacher )
	{
		$options[ $teacher['TEACHER_ID'] ] = $teacher['FULL_NAME'];
	}

	return '<div style="display:table-cell;">' . _( 'With' ) . ':&nbsp;</div>
		<div style="display:table-cell;">' .
		SelectInput(
			$value,
			'values[' . $THIS_RET['REQUEST_ID'] . '][WITH_TEACHER_ID]',
			'',
			$options
		) .
		'</div>
		<div style="display:table-cell;">&nbsp;-&nbsp;' . _( 'Without' ) . ':&nbsp;</div>
		<div style="display:table-cell;">' .
		SelectInput(
			$THIS_RET['NOT_TEACHER_ID'],
			'values[' . $THIS_RET['REQUEST_ID'] . '][NOT_TEACHER_ID]',
			'',
			$options
		) .
		'</div>';
}

function _makePeriod( $value, $column )
{
	global $THIS_RET;

	// FJ multiple school periods for a course period.
	// $periods_RET = DBGet( "SELECT p.TITLE,p.PERIOD_ID FROM SCHOOL_PERIODS p,COURSE_PERIODS cp WHERE p.PERIOD_ID=cp.PERIOD_ID AND cp.COURSE_ID='".$THIS_RET['COURSE_ID']."'" );
	$periods_RET = DBGet( "SELECT p.TITLE,p.PERIOD_ID
		FROM SCHOOL_PERIODS p,COURSE_PERIODS cp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND p.PERIOD_ID=cpsp.PERIOD_ID
		AND cp.COURSE_ID='" . $THIS_RET['COURSE_ID'] . "'" );

	$options = array();

	foreach ( (array) $periods_RET as $period )
	{
		$options[ $period['PERIOD_ID'] ] = $period['TITLE'];
	}

	return '<div style="display:table-cell;">' . _( 'On' ) . ':&nbsp;</div>
		<div style="display:table-cell;">' .
		SelectInput(
			$value,
			'values[' . $THIS_RET['REQUEST_ID'] . '][WITH_PERIOD_ID]',
			'',
			$options
		) .
		'</div>
		<div style="display:table-cell;">&nbsp;-&nbsp;' . _( 'Not on' ) . ':&nbsp;</div>
		<div style="display:table-cell;">' .
		SelectInput(
			$THIS_RET['NOT_PERIOD_ID'],
			'values[' . $THIS_RET['REQUEST_ID'] . '][NOT_PERIOD_ID]',
			'',
			$options
		) .
		'</div>';
}

// DOESN'T SUPPORT MP REQUEST.
function _makeMP( $value, $column )
{
	global $THIS_RET;

	return SelectInput( $value, 'values[' . $THIS_RET['REQUEST_ID'] . '][MARKING_PERIOD_ID]' );
}
