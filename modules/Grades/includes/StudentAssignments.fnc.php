<?php
/**
 * Student Assignments functions
 *
 * @package RosarioSIS
 * @subpackage modules/Grades
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';

// Assignments Files upload path global.

if ( ! isset( $AssignmentsFilesPath ) )
{
	$AssignmentsFilesPath = 'assets/AssignmentsFiles/';
}

/**
 * Submit Student Assignment
 * Save eventual uploaded file
 * & TinyMCE message.
 *
 * @example $submitted = StudentAssignmentSubmit( $_REQUEST['assignment_id'], $error );
 *
 * @uses GetAssignment()
 * @uses GetAssignmentsFilesPath()
 * @uses FileUpload()
 * @uses SanitizeHTML()
 * @since 2.9
 *
 * @param  string  $assignment_id Assignment ID.
 * @param  array   $error         Global errors array.
 * @return boolean False if error(s), else true.
 */
function StudentAssignmentSubmit( $assignment_id, &$error )
{
	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	$assignment = GetAssignment( $assignment_id );

	if ( ! $assignment )
	{
		$error[] = _( 'You are not allowed to access this assignment.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	if ( ! $assignment['SUBMISSION'] )
	{
		$error[] = _( 'Assignment submission is not enabled.' );

		return false;
	}

	// Old submission.
	$old_submission = GetAssignmentSubmission( $assignment_id, UserStudentID() );

	// TODO: check if Student not dropped?

	$files = $old_data['files'];

	$timestamp = date( 'Y-m-d His' );

	$assignments_path = GetAssignmentsFilesPath( $assignment['STAFF_ID'] );

	// Check if file submitted.

	if ( isset( $_FILES['submission_file'] ) )
	{
		$student_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS NAME
			FROM STUDENTS
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		// Filename = [course_title]_[assignment_ID]_[student_name]_[timestamp].ext.
		$file_name_no_ext = no_accents( $assignment['COURSE_TITLE'] . '_' . $assignment_id . '_' .
			$student_name ) . '_' . $timestamp;

		// Upload file to AssignmentsFiles/[School_Year]/Teacher[teacher_ID]/Quarter[1,2,3,4...]/.
		$file = FileUpload(
			'submission_file',
			$assignments_path,
			FileExtensionWhiteList(),
			0,
			$error,
			'',
			$file_name_no_ext
		);

		if ( $file )
		{
			$files = array( $file );

			if ( $old_submission )
			{
				$old_data = unserialize( $old_submission['DATA'] );

				$old_file = isset( $old_data['files'][0] ) ? $old_data['files'][0] : '';

				if ( file_exists( $old_file ) )
				{
					// Delete old file if any.
					unlink( $old_file );
				}
			}
		}
	}

	// Check if HMTL submitted.
	$message = isset( $_POST['message'] ) ? SanitizeHTML( $_POST['message'], $assignments_path ) : '';

	// Serialize Assignment Data.
	$data = array( 'files' => $files, 'message' => $message, 'date' => $timestamp );

	$data = DBEScapeString( serialize( $data ) );

	// Save assignment submission.
	// Update or insert?

	if ( $old_submission )
	{
		// Update.
		$assignment_submission_sql = "UPDATE STUDENT_ASSIGNMENTS
			SET DATA='" . $data . "'
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND ASSIGNMENT_ID='" . $assignment_id . "'";
	}
	else
	{
		// If no file & no message.

		if ( $message = ''
			&& ! $files )
		{
			return false;
		}

		// Insert.
		$assignment_submission_sql = "INSERT INTO STUDENT_ASSIGNMENTS
			(STUDENT_ID, ASSIGNMENT_ID, DATA)
			VALUES ('" . UserStudentID() . "', '" . $assignment_id . "', '" . $data . "')";
	}

	DBQuery( $assignment_submission_sql );

	return empty( $error );
}

/**
 * Student Assignment details
 * & Submission form.
 *
 * @example echo StudentAssignmentSubmission( $_REQUEST['assignment_id'] );
 *
 * @uses GetAssignmentSubmission()
 * @uses TinyMCEInput()
 * @since 2.9
 *
 * @since 4.5 Move headers to StudentAssignmentDrawHeaders() function
 *
 * @param  string  $assignment_id Assignment ID.
 * @return boolean true if can submit, else false.
 */
function StudentAssignmentSubmissionOutput( $assignment_id )
{
	require_once 'ProgramFunctions/FileUpload.fnc.php';

	$assignment = GetAssignment( $assignment_id );

	if ( ! $assignment )
	{
		$error[] = _( 'You are not allowed to access this assignment.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	StudentAssignmentDrawHeaders( $assignment );

	// @since 4.1 Submission header action hook.
	do_action( 'Grades/includes/StudentAssignments.fnc.php|submission_header' );

	if ( ! $assignment['SUBMISSION'] )
	{
		return false;
	}

	// Get assignment submission if any.
	$submission = GetAssignmentSubmission(
		$assignment_id,
		UserStudentID()
	);

	$old_file = $old_message = '';

	if ( isset( $submission['DATA'] ) )
	{
		$data = unserialize( $submission['DATA'] );

		$old_file = isset( $data['files'][0] ) ? $data['files'][0] : '';

		$old_file = GetAssignmentFileLink( $old_file );

		$old_message = $data['message'];

		$old_date = ProperDateTime( $data['date'], 'short' );
	}

	// Check if Assignment can be submitted (TODAY <= DUE_DATE) or (!DUE_DATE && TODAY > User MP END_DATE).

	if (  ( $assignment['DUE_DATE']
		&& DBDate() > $assignment['DUE_DATE'] )
		|| ( ! $assignment['DUE_DATE']
			&& DBDate() > GetMP( UserMP(), 'END_DATE' ) ) )
	{
		if ( $old_file )
		{
			// Display assignment file.
			DrawHeader(
				NoInput( $old_file, _( 'File' ) ),
				NoInput( $old_date, _( 'Submission date' ) )
			);
		}

		if ( $old_message )
		{
			// Display assignment message.
			DrawHeader( $old_message . $message .
				FormatInputTitle( _( 'Message' ), '', false, '' ) );
		}

		echo ErrorMessage( array( _( 'Submissions for this assignment are closed.' ) ), 'note' );

		return false;
	}

	// File upload.
	$file_id = 'submission_file';

	$file_html = FileInput( $file_id, _( 'File' ) );

	// Input div onclick only if old file.
	DrawHeader(
		$old_file ? $old_file . '<br />' . $file_html : $file_html,
		$old_file ? NoInput( $old_date, _( 'Submission date' ) ) : ''
	);

	// HTML message (TinyMCE).
	DrawHeader( TinyMCEInput( $old_message, 'message', _( 'Message' ) ) );

	echo '<br /><div class="center">' . SubmitButton( _( 'Submit Assignment' ), 'submit_assignment' ) . '</div>';

	return true;
}


/**
 * Student Assignment Draw Headers with details
 *
 * @since 4.5
 *
 * @param array $assignment Assignment details array
 */
function StudentAssignmentDrawHeaders( $assignment )
{
	if ( ! $assignment
		|| ! is_array( $assignment ) )
	{
		return;
	}

	// Past due, in red.
	$due_date = $assignment['DUE_DATE'] ? MakeAssignmentDueDate( $assignment['DUE_DATE'] ) : _( 'N/A' );

	$assigned_date = $assignment['ASSIGNED_DATE'] ? ProperDate( $assignment['ASSIGNED_DATE'] ) : _( 'N/A' );

	// Display Assignment details.
	// Due date - Assigned date.
	DrawHeader(
		_( 'Due Date' ) . ': <b>' . $due_date . '</b>',
		_( 'Assigned Date' ) . ': <b>' . $assigned_date . '</b>'
	);

	// Course - Teacher.
	DrawHeader(
		_( 'Course Title' ) . ': <b>' . $assignment['COURSE_TITLE'] . '</b>',
		_( 'Teacher' ) . ': <b>' . GetTeacher( $assignment['STAFF_ID'] ) . '</b>'
	);

	$type_color = '';

	if ( $assignment['ASSIGNMENT_TYPE_COLOR'] )
	{
		$type_color = '<span style="background-color: ' .
			$assignment['ASSIGNMENT_TYPE_COLOR'] . ';">&nbsp;</span>&nbsp;';
	}

	// Title - Type.
	DrawHeader(
		_( 'Title' ) . ': <b>' . $assignment['TITLE'],
		_( 'Category' ) . ': <b>' . $type_color . $assignment['CATEGORY'] . '</b>'
	);

	// @since 4.4 Assignment File.
	$file_header = $assignment['FILE'] ?
		_( 'File' ) . ': ' . GetAssignmentFileLink( $assignment['FILE'] ) :
		'';

	// Points.
	DrawHeader(
		_( 'Points' ) . ': <b>' . $assignment['POINTS'] . '</b>',
		$file_header
	);

	if ( $assignment['DESCRIPTION'] )
	{
		// Description.
		DrawHeader( _( 'Description' ) . ':<br />'. $assignment['DESCRIPTION'] );
	}
}


/**
 * Get Assignment details from DB.
 *
 * @example $assignment = GetAssignment( $assignment_id );
 *
 * @since 2.9
 * @since 4.4 Adapt function for Teachers (no Student).
 *
 * @param  string        $assignment_id Assignment ID.
 * @return boolean|array Assignment details array or false.
 */
function GetAssignment( $assignment_id )
{
	/**
	 * @var array
	 */
	static $assignment = array();

	if ( isset( $assignment[$assignment_id] ) )
	{
		return $assignment[$assignment_id];
	}

	// Check Assignment ID is int > 0.

	if ( $assignment_id < 1 )
	{
		return false;
	}

	$where_user = "1";

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$where_user = "WHERE ga.STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND c.COURSE_ID=gat.COURSE_ID
			AND (ga.COURSE_PERIOD_ID IS NULL OR ga.COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
			AND (ga.COURSE_ID IS NULL OR ga.COURSE_ID=c.COURSE_ID)";
	}
	elseif ( UserStudentID() )
	{
		$where_user = ",SCHEDULE ss WHERE ss.STUDENT_ID='" . UserStudentID() . "'
			AND ss.SYEAR='" . UserSyear() . "'
			AND ss.SCHOOL_ID='" . UserSchool() . "'
			AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
			AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
			AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
			AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
			AND ( ga.DUE_DATE IS NULL
				OR ( ga.DUE_DATE>=ss.START_DATE
					AND ( ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE ) ) )
			AND c.COURSE_ID=ss.COURSE_ID";
	}

	$assignment_sql = "SELECT ga.ASSIGNMENT_ID, ga.STAFF_ID, ga.COURSE_PERIOD_ID, ga.COURSE_ID,
		ga.TITLE, ga.ASSIGNED_DATE, ga.DUE_DATE, ga.POINTS,
		ga.DESCRIPTION, ga.FILE, ga.SUBMISSION, c.TITLE AS COURSE_TITLE,
		gat.TITLE AS CATEGORY, gat.COLOR AS ASSIGNMENT_TYPE_COLOR
		FROM GRADEBOOK_ASSIGNMENTS ga,COURSES c,GRADEBOOK_ASSIGNMENT_TYPES gat
		" . $where_user .
		" AND ga.ASSIGNMENT_ID='" . $assignment_id . "'
		AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID"; // Why not?

	$assignment_RET = DBGet( $assignment_sql, array(), array( 'ASSIGNMENT_ID' ) );

	$assignment[$assignment_id] = isset( $assignment_RET[$assignment_id] ) ?
	$assignment_RET[$assignment_id][1] : false;

	return $assignment[$assignment_id];
}

/**
 * @param $assignment_id
 * @param $student_id
 */
function GetAssignmentSubmission( $assignment_id, $student_id )
{
	// Check Assignment ID is int > 0 & Student ID.

	if ( ! $assignment_id
		|| (string) (int) $assignment_id !== $assignment_id
		|| $assignment_id < 1
		|| ! $student_id )
	{
		return false;
	}

	$submission_sql = "SELECT DATA
		FROM STUDENT_ASSIGNMENTS
		WHERE ASSIGNMENT_ID='" . $assignment_id . "'
		AND STUDENT_ID='" . $student_id . "'";

	$submission_RET = DBGet( $submission_sql );

	return isset( $submission_RET[1] ) ? $submission_RET[1] : false;
}

/**
 * Get `AssignmentsFiles/` folder full path
 *
 * @example $assignments_path = GetAssignmentsFilesPath( $assignment['STAFF_ID'] );
 *
 * @global $AssignmentsFilesPath
 * @since 2.9
 *
 * @param  string $teacher_id                                                                Teacher ID.
 * @return string AssignmentsFiles/[School_Year]/Quarter[1,2,3,4...]/Teacher[teacher_ID]/
 */
function GetAssignmentsFilesPath( $teacher_id )
{
	global $AssignmentsFilesPath;

	if ( ! $teacher_id )
	{
		return $AssignmentsFilesPath;
	}

	// File path = AssignmentsFiles/[School_Year]/Quarter[1,2,3,4...]/Teacher[teacher_ID]/.

	return $AssignmentsFilesPath . UserSyear() . '/Quarter' . UserMP() . '/Teacher' . $teacher_id . '/';
}


/**
 * Upload Assignment Teacher File
 * Delete any existing file.
 *
 * @since 4.4
 *
 * @param int    $teacher_id    Teacher staff ID.
 * @param int    $assignment_id Assignment ID.
 * @param string $file_input_id File input ID.
 *
 * @return string File full path.
 */
function UploadAssignmentTeacherFile( $assignment_id, $teacher_id, $file_input_id )
{
	global $error;

	$assignment = GetAssignment( $assignment_id );

	if ( ! $assignment )
	{
		return '';
	}

	// Filename = [course_title]_[assignment_ID].ext.
	$file_name_no_ext = no_accents( $assignment['COURSE_TITLE'] . '_' . $assignment_id );

	if ( ! empty( $assignment['FILE'] )
		&& file_exists( $assignment['FILE'] ) )
	{
		// Delete existing Assignment File.
		unlink( $assignment['FILE'] );
	}

	$assignments_path = GetAssignmentsFilesPath( User( 'STAFF_ID' ) );

	// Upload file to AssignmentsFiles/[School_Year]/Teacher[teacher_ID]/Quarter[1,2,3,4...]/.
	$file = FileUpload(
		$file_input_id,
		$assignments_path,
		FileExtensionWhiteList(),
		0,
		$error,
		'',
		$file_name_no_ext
	);

	return $file;
}


function StudentAssignmentsListOutput()
{
	// TODO: get Assignment type color!
	$assignments_sql = "SELECT ga.ASSIGNMENT_ID, ga.STAFF_ID, ga.COURSE_PERIOD_ID, ga.COURSE_ID,
		ga.ASSIGNMENT_TYPE_ID, ga.TITLE, ga.ASSIGNED_DATE, ga.DUE_DATE, ga.POINTS, ga.SUBMISSION,
		c.TITLE AS COURSE_TITLE,
		(SELECT 1
			FROM STUDENT_ASSIGNMENTS sa
			WHERE ga.ASSIGNMENT_ID=sa.ASSIGNMENT_ID
			AND sa.STUDENT_ID=ss.STUDENT_ID) AS SUBMITTED
		FROM GRADEBOOK_ASSIGNMENTS ga, SCHEDULE ss, COURSES c, COURSE_PERIODS cp
		WHERE ss.STUDENT_ID='" . UserStudentID() . "'
		AND ss.SYEAR='" . UserSyear() . "'
		AND ss.SCHOOL_ID='" . UserSchool() . "'
		AND ga.MARKING_PERIOD_ID='" . UserMP() . "'
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
		AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
		AND ga.STAFF_ID=cp.TEACHER_ID
		AND cp.COURSE_ID=c.COURSE_ID
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND ( ga.DUE_DATE IS NULL
			OR ( ga.DUE_DATE>=ss.START_DATE
				AND ( ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE ) ) )
		AND c.COURSE_ID=ss.COURSE_ID
		ORDER BY ga.SUBMISSION, ga.DUE_DATE";

	$assignments_RET = DBGet(
		DBQuery( $assignments_sql ),
		array(
			'TITLE' => 'MakeAssignmentTitle',
			'STAFF_ID' => 'GetTeacher',
			'DUE_DATE' => 'MakeAssignmentDueDate',
			'ASSIGNED_DATE' => 'ProperDate',
			'SUBMITTED' => 'MakeAssignmentSubmitted',
		)
	);

	$columns = array(
		'TITLE' => _( 'Title' ),
		'DUE_DATE' => _( 'Due Date' ),
		'ASSIGNED_DATE' => _( 'Assigned Date' ),
		'COURSE_TITLE' => _( 'Course Title' ),
		'STAFF_ID' => _( 'Teacher' ),
		'SUBMITTED' => _( 'Submitted' ),
	);

	$LO_options = array(
		'save' => false,
	);

	ListOutput(
		$assignments_RET,
		$columns,
		_( 'Assignment' ),
		_( 'Assignments' ),
		array(),
		array(),
		$LO_options
	);

	return true;
}

if ( ! function_exists( 'MakeAssignmentTitle' ) )
{
	/**
	 * Make Assignment title and link.
	 *
	 * @global $THIS_RET current row from DBGet.
	 * @since 4.1 Override this function in your custom module or plugin.
	 *
	 * @param  string $value  Title value.
	 * @param  string $column Column, 'TITLE'.
	 * @return Title  and link.
	 */
	function MakeAssignmentTitle( $value, $column )
	{
		global $THIS_RET;

		// Truncate value to 36 chars.
		$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . $value . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$view_assignment_link = 'Modules.php?modname=Grades/Assignments.php';
		}
		else
		{
			$view_assignment_link = 'Modules.php?modname=Grades/StudentAssignments.php';
		}

		if ( ! empty( $THIS_RET['ASSIGNMENT_ID'] ) )
		{
			$view_assignment_link .= '&assignment_id=' . $THIS_RET['ASSIGNMENT_ID'];
		}

		if ( ! empty( $THIS_RET['ASSIGNMENT_ID'] ) )
		{
			// @since 3.9 Add MP to outside links (see Portal), so current MP is correct.
			$view_assignment_link .= '&marking_period_id=' . $THIS_RET['MARKING_PERIOD_ID'];
		}

		return '<a href="' . $view_assignment_link . '">' . $title . '</a>';
	}
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function MakeAssignmentDueDate( $value, $column = 'DUE_DATE' )
{
	$due_date = ProperDate( $value );

	if ( $value
		&& $value <= DBDate() )
	{
		// Past due, in red.
		$due_date = '<span style="color:red;">' . $due_date . '</span>';
	}

	return $due_date;
}

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function MakeAssignmentSubmitted( $value, $column )
{
	global $THIS_RET;

	if ( $THIS_RET['SUBMISSION'] !== 'Y' )
	{
		return '';
	}

	return $value ? button( 'check' ) : button( 'x' );
}


/**
 * Make Student Assignment Submission View
 *
 * DBGet callback
 *
 * @since 4.2
 *
 * @param string $value
 * @param string $column 'SUBMISSION'
 * @return string Column HTML.
 */
function MakeStudentAssignmentSubmissionView( $value, $column )
{
	global $THIS_RET,
		$submission_column_html;

	$student_id = UserStudentID() ? UserStudentID() : $THIS_RET['STUDENT_ID'];

	$submission = GetAssignmentSubmission( $THIS_RET['ASSIGNMENT_ID'], $student_id );

	$submission_column_html = button( 'x' );

	if ( $value !== 'Y' )
	{
		$submission_column_html = '';
	}

	if ( $submission )
	{
		$data = unserialize( $submission['DATA'] );

		$file = isset( $data['files'][0] ) ? $data['files'][0] : '';

		$message = $data['message'];

		$date = ProperDateTime( $data['date'], 'short' );

		$submission_column_html = '<a class="colorboxinline" href="#submission' . $THIS_RET['ASSIGNMENT_ID'] . '-' . $student_id . '">
		<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/visualize.png" class="button bigger" /> ' .
		_( 'View Online' ) . '</a>';

		$submission_column_html .= '<div class="hide">
			<div id="submission' . $THIS_RET['ASSIGNMENT_ID'] . '-' . $student_id . '">' .
		NoInput( $date, _( 'Submission date' ) ) . '<br />' .
		NoInput( GetAssignmentFileLink( $file ), _( 'File' ) ) .
		$message . FormatInputTitle( _( 'Message' ), '', false, '' ) .
			'</div></div>';

		return $submission_column_html;
	}

	/**
	 * Do action hook
	 * Assignment Grades Submission column.
	 *
	 * Submission Column HTML is a global var so it can be filtered.
	 *
	 * @since 4.2
	 */
	do_action( 'Grades/includes/StudentAssignments.fnc.php|grades_submission_column' );

	return $submission_column_html;
}


/**
 * @param $file_path
 */
function GetAssignmentFileLink( $file_path )
{
	if ( ! file_exists( $file_path ) )
	{
		return '';
	}

	$file_name = mb_substr( mb_strrchr( $file_path, '/' ), 1 );

	$file_size = HumanFilesize( filesize( $file_path ) );

	return button(
		'download',
		_( 'Download' ),
		'"' . $file_path . '" target="_blank" title="' . $file_name . ' (' . $file_size . ')"',
		'bigger'
	);
}
