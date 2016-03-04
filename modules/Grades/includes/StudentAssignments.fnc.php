<?php
/**
 * Student Assignments functions
 *
 * @package RosarioSIS
 * @subpackage modules/Grades
 */

/**
 * Submit Student Assignment
 * Save eventual uploaded file
 * & TinyMCE message.
 *
 * @example $submitted = StudentAssignmentSubmit( $_REQUEST['assignment_id'], $error );
 *
 * @since 2.9
 *
 * @uses GetAssignment()
 * @uses GetAssignmentsFilesPath()
 * @uses FileUpload()
 * @uses SanitizeHTML()
 *
 * @param string $assignment_id Assignment ID.
 * @param array  $error         Global errors array.
 *
 * @return boolean False if error(s), else true.
 */
function StudentAssignmentSubmit( $assignment_id, &$error )
{
	require_once 'ProgramFunctions/FileUpload.fnc.php';

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	$assignment = GetAssignment( $assignment_id );

	if ( ! $assignment )
	{
		$error[] = _( 'You are not allowed to access this assignment.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	if ( ! $assignment['SUBMISSION'] )
	{
		$error[] = _( 'Assignment submission is not permitted.' );

		return false;
	}

	// TODO: check if Student not dropped?

	$files = array();

	$timestamp = date( 'Y-m-d H:i:s' );

	// Check if file submitted.
	if ( isset( $_FILES[ 'submission_file' ] ) )
	{
		$file_attached_ext_white_list = array(
			// Micro$oft Office.
			'.doc',
			'.docx',
			'.xls',
			'.xlsx',
			'.ppt',
			'.pptx',
			// Libre Office.
			'.odt',
			'.ods',
			'.odp',
			// Images.
			'.jpg',
			'.jpeg',
			'.png',
			'.gif',
			// Sound.
			'.mp3',
			'.ogg',
			'.wav',
			// Video.
			'.avi',
			'.mp4',
			'.ogv',
			'.webm',
			// Others.
			'.zip',
			'.txt',
			'.pdf',
			'.csv',
		);

		// Filename = [assignment_ID]_[student_ID]_[timestamp].ext.
		$file_name_no_ext = $assignment_id . '_' . UserStudentID() . '_' . $timestamp;

		$assignments_path = GetAssignmentsFilesPath( $assignment['STAFF_ID'] );

		// Upload file to AssignmentsFiles/[School_Year]/Teacher[teacher_ID]/Quarter[1,2,3,4...]/.
		$file = FileUpload(
			'submission_file',
			$assignments_path,
			$file_attached_ext_white_list,
			0,
			$error,
			'',
			$file_name_no_ext
		);

		if ( $file )
		{
			$files[] = $file;

			// Delete old file if any.
			$old_files = glob( $assignments_path . '/' .
				$assignment_id . '_' . UserStudentID() . '_*.*' );

			foreach ( (array) $old_files as $old_file )
			{
				unlink( $old_file );
			}
		}
	}

	// Check if HMTL submitted.
	$message = isset( $_POST['message'] ) ? SanitizeHTML( $_POST['message'] ) : '';

	// Serialize Assignment Data.
	$data = array( 'files' => $files, 'message' => $message, 'date' => $timestamp );

	$data = DBEScapeString( serialize( $data ) );

	// Save assignment submission.
	// Update or insert?
	$update_assignment = DBGet( DBQuery( "SELECT 1
		FROM STUDENT_ASSIGNMENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND ASSIGNMENT_ID='" . $assignment_id . "'" ) );

	if ( $update_assignment )
	{
		// Update.
		$assignment_submission_sql = "UPDATE STUDENT_ASSIGNMENTS
			SET DATA='" . $data . "'";
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
 * @since 2.9
 *
 * @uses GetAssignmentSubmission()
 * @uses TinyMCEInput()
 *
 * @param string $assignment_id Assignment ID.
 *
 * @return boolean true if can submit, else false.
 */
function StudentAssignmentSubmissionOutput( $assignment_id )
{
	$assignment = GetAssignment( $assignment_id );

	if ( ! $assignment )
	{
		$error[] = _( 'You are not allowed to access this assignment.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	// Past due, in red.
	$due_date = _makeAssignmentDueDate( $assignment['DUE_DATE'] );

	// Display Assignment details.
	// Due date - Assigned date.
	DrawHeader(
		_( 'Due Date' ) . ': <b>' . $due_date . '</b>',
		_( 'Assigned Date' ) . ': <b>' . ProperDate( $assignment['ASSIGNED_DATE'] ) . '</b>'
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
		_( 'Assignment Type' ) . ': <b>' . $type_color . $assignment['ASSIGNMENT_TYPE_TITLE'] . '</b>'
	);

	// Points.
	DrawHeader( _( 'Points' ) . ': <b>' . $assignment['POINTS'] . '</b>' );

	if ( $assignment['DESCRIPTION'] )
	{
		// Description.
		DrawHeader( _( 'Description' ) . ':<br />
			<div class="markdown-to-html">' . $assignment['DESCRIPTION'] . '</div>' );
	}

	if ( ! $assignment['SUBMISSION'] )
	{
		return false;
	}

	// Check if Assignment can be submitted (TODAY <= DUE_DATE).
	if ( $assignment['DUE_DATE']
		&& DBDate() <= $assignment['DUE_DATE'] )
	{
		return false;
	}

	// Get assignment submission if any.
	$submission = GetAssignmentSubmission( $assignment_id, UserStudentID() );

	$old_file = $old_message = '';

	if ( isset( $submission['DATA'] ) )
	{
		$data = unserialize( $submission['DATA'] );

		$old_file = isset( $data['files'][0] ) ? $data['files'][0] : '';

		$old_message = $data['message'];
	}

	// File upload.
	$file_id = 'submission_file';

	$file_ftitle = FormatInputTitle( _( 'File' ), $file_id );

	$file_html = '<input type="file" id="' . $file_id . '" name="' . $file_id . '" size="14" title="' .
		sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ) . '" />
		<span id="loading"></span>' . $file_ftitle;

	// Input div onclick only if old file.
	echo $old_file ? InputDivOnclick( $file_id, $file_html, $file, $file_ftitle ) : $file_html;

	// HTML message (TinyMCE).
	echo TinyMCEInput(
		$old_message,
		'message',
		_( 'Message' )
	);

	echo '<br />' . SubmitButton( _( 'Submit Assignment' ) );

	return true;
}



/**
 * Get Assignment details from DB.
 *
 * @example $assignment = GetAssignment( $assignment_id );
 *
 * @since 2.9
 *
 * @param string $assignment_id Assignment ID.
 *
 * @return boolean|array Assignment details array or false.
 */
function GetAssignment( $assignment_id )
{
	static $assignment = array();

	if ( isset( $assignment[ $assignment_id ] ) )
	{
		return $assignment[ $assignment_id ];
	}

	// Check Assignment ID is int > 0.
	if ( ! $assignment_id
		|| (string) (int) $assignment_id !== $assignment_id
		|| $assignment_id < 1 )
	{
		return false;
	}

	$student_id = UserStudentID() ? UserStudentID() : $_SESSION['STUDENT_ID'];

	$assignment_sql = "SELECT ga.ASSIGNMENT_ID, ga.STAFF_ID, ga.COURSE_PERIOD_ID, ga.COURSE_ID,
		ga.TITLE, ga.ASSIGNED_DATE, ga.DUE_DATE, ga.POINTS,
		ga.DESCRIPTION, ga.SUBMISSION, c.TITLE AS COURSE_TITLE,
		gat.TITLE AS ASSIGNMENT_TYPE_TITLE, gat.COLOR AS ASSIGNMENT_TYPE_COLOR
		FROM GRADEBOOK_ASSIGNMENTS ga, SCHEDULE ss, COURSES c, GRADEBOOK_ASSIGNMENT_TYPES gat
		WHERE ss.STUDENT_ID='" . $student_id . "'
		AND ss.SYEAR='" . UserSyear() . "'
		AND ss.SCHOOL_ID='" . UserSchool() . "'
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND ga.ASSIGNMENT_ID='" . $assignment_id . "'
		AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
		AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
		AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND ( ga.DUE_DATE IS NULL
			OR ( ga.DUE_DATE>=ss.START_DATE
				AND ( ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE ) ) )
		AND c.COURSE_ID=ss.COURSE_ID
		AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID"; // Why not?

	$assignment_RET = DBGet( DBQuery( $assignment_sql ), array(), array( 'ASSIGNMENT_ID' ) );

	$assignment[ $assignment_id ] = isset( $assignment_RET[ $assignment_id ] ) ?
		$assignment_RET[ $assignment_id ][1] : false;

	return $assignment[ $assignment_id ];
}


/**
 * Get `AssignmentsFiles/` folder full path
 *
 * @example $assignments_path = GetAssignmentsFilesPath( $assignment['STAFF_ID'] );
 *
 * @since 2.9
 *
 * @global $AssignmentsFilesPath
 *
 * @param string $teacher_id Teacher ID.
 *
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
	return $AssignmentsFilesPath . '/' . UserSyear() . '/Quarter' . UserMP() . '/Teacher' . $teacher_id . '/';
}



function StudentAssignmentsListOutput()
{
	$student_id = UserStudentID() ? UserStudentID() : $_SESSION['STUDENT_ID'];

	// TODO: get Assignment type color!
	$assignments_sql = "SELECT ga.ASSIGNMENT_ID, ga.STAFF_ID, ga.COURSE_PERIOD_ID, ga.COURSE_ID,
		ga.ASSIGNMENT_TYPE_ID, ga.TITLE, ga.ASSIGNED_DATE, ga.DUE_DATE, ga.POINTS, ga.SUBMISSION,
		c.TITLE AS COURSE_TITLE,
		(SELECT 1
			FROM STUDENT_ASSIGNMENTS sa
			WHERE ga.ASSIGNMENT_ID=sa.ASSIGNMENT_ID
			AND sa.STUDENT_ID=ss.STUDENT_ID) AS SUBMITTED
		FROM GRADEBOOK_ASSIGNMENTS ga, SCHEDULE ss, COURSES c
		WHERE ss.STUDENT_ID='" . $student_id . "'
		AND ss.SYEAR='" . UserSyear() . "'
		AND ss.SCHOOL_ID='" . UserSchool() . "'
		AND ga.MARKING_PERIOD_ID='" . UserMP() . "'
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
		AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
		AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND ( ga.DUE_DATE IS NULL
			OR ( ga.DUE_DATE>=ss.START_DATE
				AND ( ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE ) ) )
		AND c.COURSE_ID=ss.COURSE_ID
		ORDER BY ga.SUBMISSION, ga.DUE_DATE";

	$assignments_RET = DBGet(
		DBQuery( $assignments_sql ),
		array(
			'TITLE' => '_makeAssignmentTitle',
			'STAFF_ID' => 'GetTeacher',
			'DUE_DATE' => '_makeAssignmentDueDate',
			'ASSIGNED_DATE' => 'ProperDate',
			'SUBMITTED' => '_makeAssignmentSubmitted',
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

	ListOutput( $assignments_RET, $columns, _( 'Assignment' ), _( 'Assignments' ) );

	return true;
}


function _makeAssignmentTitle( $value, $column )
{
	global $THIS_RET;

	// Truncate value to 36 chars.
	$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . $value . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	$view_assignment_link = PreparePHP_SELF(
		$_REQUEST,
		array( 'search_modfunc' ),
		array( 'assignment_id' => $THIS_RET['ASSIGNMENT_ID'] )
	);

	return '<a href="' . $view_assignment_link . '">' . $title . '</a>';
}


function _makeAssignmentDueDate( $value, $column = 'DUE_DATE' )
{
	$due_date = ProperDate( $value );

	if ( $value
		&& $value >= DBDate() )
	{
		// Past due, in red.
		$due_date = '<span style="color:red;">' . $due_date . '</span>';
	}

	return $due_date;
}


function _makeAssignmentSubmitted( $value, $column )
{
	global $THIS_RET;

	if ( $THIS_RET['SUBMISSION'] !== 'Y' )
	{
		return '';
	}

	return $value ? button( 'check' ) : button( 'x' );
}
