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

	if ( $old_submission )
	{
		// @since 11.0 Move from serialize() to json_encode()
		$old_data = json_decode( $old_submission['DATA'], true );

		if ( json_last_error() !== JSON_ERROR_NONE )
		{
			$old_data = unserialize( $old_submission['DATA'] );
		}
	}

	// TODO: check if Student not dropped?

	$files = issetVal( $old_data['files'] );

	$assignments_path = GetAssignmentsFilesPath( $assignment['STAFF_ID'] );

	// Check if file submitted.
	if ( isset( $_FILES['submission_file'] ) )
	{
		$student_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS NAME
			FROM students
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		// Filename = [course_title]_[assignment_ID]_[student_name]_[timestamp].ext.
		$file_name_no_ext = FileNameTimestamp( $assignment['COURSE_TITLE'] . '_' . $assignment_id . '_' . $student_name );

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
			$files = [ $file ];
		}
	}

	if ( isset( $_REQUEST['submission_file'] ) )
	{
		// Submission file input enabled (may be empty).
		if ( $old_data )
		{
			$old_file = issetVal( $old_data['files'][0], '' );

			if ( file_exists( $old_file ) )
			{
				// Delete old file if any.
				unlink( $old_file );
			}
		}
	}

	// Check if HMTL submitted.
	$message = isset( $_POST['message'] ) ? SanitizeHTML( $_POST['message'], $assignments_path ) : '';

	// Serialize Assignment Data.
	$data = [ 'files' => $files, 'message' => $message, 'date' => date( 'Y-m-d H:i:s' ) ];

	// @since 11.0 Move from serialize() to json_encode()
	$data = DBEscapeString( json_encode( $data ) );

	if ( ! $old_submission )
	{
		// If no file & no message.
		if ( $message === ''
			&& ! $files )
		{
			return false;
		}
	}

	// Save assignment submission.
	// Update or insert? Upsert
	DBUpsert(
		'student_assignments',
		[ 'DATA' => $data ],
		[ 'STUDENT_ID' => UserStudentID(), 'ASSIGNMENT_ID' => (int) $assignment_id ],
		$old_submission ? 'update' : 'insert'
	);

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

	$old_file = $old_message = $old_date = '';

	if ( isset( $submission['DATA'] ) )
	{
		// @since 11.0 Move from serialize() to json_encode()
		$data = json_decode( $submission['DATA'], true );

		if ( json_last_error() !== JSON_ERROR_NONE )
		{
			$data = unserialize( $submission['DATA'] );
		}

		$old_file = issetVal( $data['files'][0], '' );

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
			DrawHeader( $old_message .
				FormatInputTitle( _( 'Message' ), '', false, '' ) );
		}

		echo ErrorMessage( [ _( 'Submissions for this assignment are closed.' ) ], 'note' );

		return false;
	}

	// File upload.
	$file_html = '<div id="submission_file_input"' . ( $old_file ? 'class="hide"' : '' ) . '>' .
	FileInput(
		'submission_file',
		_( 'File' ),
		( $old_file ? 'disabled' : '' )
	) . '</div>';

	if ( $old_file )
	{
		// Delete file icon.
		$old_file = button(
			'remove',
			'',
			'"#!" onclick="$(\'#submission_file_link\').hide(); $(\'#submission_file_input\').show();$(\'#submission_file\').prop(\'disabled\', false);"'
		) . ' ' . $old_file;

		$old_file = '<div id="submission_file_link">' . NoInput( $old_file, _( 'File' ) ) . '</div>';
	}

	// Input div onclick only if old file.
	DrawHeader(
		$old_file ? $old_file . '<br />' . $file_html : $file_html,
		$old_date ? NoInput( $old_date, _( 'Submission date' ) ) : ''
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
 * @since 10.6 Truncate Assignment Title & Category to 36 chars only if has words > 36 chars
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

	$gradebook_config = ProgramUserConfig( 'Gradebook', $assignment['STAFF_ID'] );

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
			AttrEscape( $assignment['ASSIGNMENT_TYPE_COLOR'] ) . ';">&nbsp;</span>&nbsp;';
	}

	// Truncate title to 36 chars only if has words > 36 chars.
	// Split on spaces.
	$title_words = explode( ' ', $assignment['TITLE'] );

	$truncate = false;

	foreach ( $title_words as $title_word )
	{
		if ( mb_strlen( $title_word ) > 36 )
		{
			$truncate = true;

			break;
		}
	}

	$title = ! $truncate ?
		$assignment['TITLE'] :
		'<span title="' . AttrEscape( $assignment['TITLE'] ) . '">' . mb_substr( $assignment['TITLE'], 0, 33 ) . '...</span>';

	// Truncate category to 36 chars only if has words > 36 chars.
	// Split on spaces.
	$category_words = explode( ' ', $assignment['CATEGORY'] );

	$truncate = false;

	foreach ( $category_words as $category_word )
	{
		if ( mb_strlen( $category_word ) > 36 )
		{
			$truncate = true;

			break;
		}
	}

	$category = ! $truncate ?
		$assignment['CATEGORY'] :
		'<span title="' . AttrEscape( $assignment['CATEGORY'] ) . '">' . mb_substr( $assignment['CATEGORY'], 0, 33 ) . '...</span>';

	// Title - Type.
	DrawHeader(
		_( 'Title' ) . ': <b>' . $title,
		_( 'Category' ) . ': <b>' . $type_color . $category . '</b>'
	);

	// @since 4.4 Assignment File.
	$file_header = $assignment['FILE'] ?
		_( 'File' ) . ': ' . GetAssignmentFileLink( $assignment['FILE'] ) :
		'';

	// @since 11.0 Add Weight Assignments option
	$weight_header = ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) ?
		_( 'Weight' ) . ': <b>' . issetVal( $assignment['WEIGHT'], 0 ) . '</b>' :
		'';

	// Points.
	DrawHeader(
		_( 'Points' ) . ': <b>' . $assignment['POINTS'] . '</b>',
		$file_header ? $file_header : $weight_header
	);

	if ( $file_header && $weight_header )
	{
		DrawHeader( $weight_header );
	}

	if ( $assignment['DESCRIPTION'] )
	{
		// Description.
		echo $assignment['DESCRIPTION'];
	}
}


/**
 * Get Assignment details from DB.
 *
 * @example $assignment = GetAssignment( $assignment_id );
 *
 * @since 2.9
 * @since 4.4 Adapt function for Teachers (no Student).
 * @since 10.7 Check Assignment is in current MP
 *
 * @param  string        $assignment_id Assignment ID.
 * @return boolean|array Assignment details array or false.
 */
function GetAssignment( $assignment_id )
{
	/**
	 * @var array
	 */
	static $assignment = [];

	if ( isset( $assignment[$assignment_id] ) )
	{
		return $assignment[$assignment_id];
	}

	// Check Assignment ID is int > 0.
	if ( (string) (int) $assignment_id != $assignment_id
		|| $assignment_id < 1 )
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
		$where_user = ",schedule ss WHERE ss.STUDENT_ID='" . UserStudentID() . "'
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
		ga.TITLE, ga.ASSIGNED_DATE, ga.DUE_DATE, ga.POINTS, ga.WEIGHT,
		ga.DESCRIPTION, ga.FILE, ga.SUBMISSION, c.TITLE AS COURSE_TITLE,
		gat.TITLE AS CATEGORY, gat.COLOR AS ASSIGNMENT_TYPE_COLOR
		FROM gradebook_assignments ga,courses c,gradebook_assignment_types gat
		" . $where_user .
		" AND ga.ASSIGNMENT_ID='" . (int) $assignment_id . "'
		AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
		AND ga.MARKING_PERIOD_ID='" . UserMP() . "'"; // Why not?

	$assignment_RET = DBGet( $assignment_sql, [], [ 'ASSIGNMENT_ID' ] );

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
		FROM student_assignments
		WHERE ASSIGNMENT_ID='" . (int) $assignment_id . "'
		AND STUDENT_ID='" . (int) $student_id . "'";

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

	// @since 9.0 Add microseconds to filename format to make it harder to predict.
	$microseconds = substr( (string) microtime(), 2, 6 );

	// Filename = [course_title]_[assignment_ID].ext.
	$file_name_no_ext = no_accents( $assignment['COURSE_TITLE'] . '_' . $assignment_id . '.' . $microseconds );

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
	$assignments_sql = "SELECT ga.ASSIGNMENT_ID,ga.STAFF_ID,ga.COURSE_PERIOD_ID,ga.COURSE_ID,
		ga.ASSIGNMENT_TYPE_ID,ga.TITLE,ga.ASSIGNED_DATE,ga.DUE_DATE,ga.POINTS,ga.SUBMISSION,
		c.TITLE AS COURSE_TITLE,
		(SELECT 1
			FROM student_assignments sa
			WHERE ga.ASSIGNMENT_ID=sa.ASSIGNMENT_ID
			AND sa.STUDENT_ID=ss.STUDENT_ID) AS SUBMITTED
		FROM gradebook_assignments ga, schedule ss, courses c, course_periods cp
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
		ORDER BY ga.SUBMISSION IS NULL,ga.SUBMISSION,ga.DUE_DATE IS NULL,ga.DUE_DATE,c.TITLE,ga.TITLE";

	$assignments_RET = DBGet(
		DBQuery( $assignments_sql ),
		[
			'TITLE' => 'MakeAssignmentTitle',
			'STAFF_ID' => 'GetTeacher',
			'DUE_DATE' => 'MakeAssignmentDueDate',
			'ASSIGNED_DATE' => 'ProperDate',
			'SUBMITTED' => 'MakeAssignmentSubmitted',
		]
	);

	$columns = [
		'TITLE' => _( 'Title' ),
		'DUE_DATE' => _( 'Due Date' ),
		'ASSIGNED_DATE' => _( 'Assigned Date' ),
		'COURSE_TITLE' => _( 'Course Title' ),
		'STAFF_ID' => _( 'Teacher' ),
		'SUBMITTED' => _( 'Submitted' ),
	];

	$LO_options = [
		'save' => false,
	];

	ListOutput(
		$assignments_RET,
		$columns,
		_( 'Assignment' ),
		_( 'Assignments' ),
		[],
		[],
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

		if ( ! empty( $_REQUEST['LO_save'] ) )
		{
			// Export list.
			return $value;
		}

		// Truncate value to 36 chars.
		$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

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

		if ( ! empty( $THIS_RET['MARKING_PERIOD_ID'] ) )
		{
			// @since 3.9 Add MP to outside links (see Portal), so current MP is correct.
			$view_assignment_link .= '&marking_period_id=' . $THIS_RET['MARKING_PERIOD_ID'];
		}

		if ( ! empty( $THIS_RET['COURSE_PERIOD_ID'] ) )
		{
			// @since 10.9 Add CP to outside links (see Portal), so current CP is correct.
			$view_assignment_link .= '&period=' . $THIS_RET['COURSE_PERIOD_ID'];
		}

		return '<a href="' . URLEscape( $view_assignment_link ) . '">' . $title . '</a>';
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

	if ( ! isset( $THIS_RET['SUBMISSION'] )
		|| $THIS_RET['SUBMISSION'] !== 'Y' )
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
		// @since 11.0 Move from serialize() to json_encode()
		$data = json_decode( $submission['DATA'], true );

		if ( json_last_error() !== JSON_ERROR_NONE )
		{
			$data = unserialize( $submission['DATA'] );
		}

		$file = issetVal( $data['files'][0], '' );

		$message = $data['message'];

		$date = ProperDateTime( $data['date'], 'short' );

		$submission_column_html = '<a class="colorboxinline" href="#submission' . $THIS_RET['ASSIGNMENT_ID'] . '-' . $student_id . '">
		<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/visualize.png" class="button bigger" /> ' .
		_( 'View Online' ) . '</a>';

		$file_html = $message_html = '';

		if ( $file )
		{
			$file_html = NoInput( GetAssignmentFileLink( $file ), _( 'File' ) ) . '<br />';
		}

		if ( $message )
		{
			$message_html = $message . FormatInputTitle( _( 'Message' ), '', false, '' );
		}

		$submission_column_html .= '<div class="hide">
			<div id="submission' . $THIS_RET['ASSIGNMENT_ID'] . '-' . $student_id . '">' .
			NoInput( $date, _( 'Submission date' ) ) . '<br />' .
			$file_html .
			$message_html .
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

	$file_name = basename( $file_path );

	$file_size = HumanFilesize( filesize( $file_path ) );

	return button(
		'download',
		_( 'Download' ),
		'"' . URLEscape( $file_path ) . '" target="_blank" title="' . AttrEscape( $file_name . ' (' . $file_size . ')' ) . '"',
		'bigger'
	);
}
