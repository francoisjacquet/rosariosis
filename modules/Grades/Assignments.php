<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

$_REQUEST['assignment_id'] = issetVal( $_REQUEST['assignment_id'], '' );
$_REQUEST['assignment_type_id'] = issetVal( $_REQUEST['assignment_type_id'], '' );

if ( ! empty( $_REQUEST['assignment_id'] )
	&& ! empty( $_REQUEST['marking_period_id'] ) )
{
	// Outside link: Assignment is in the current MP?

	if ( $_REQUEST['marking_period_id'] != UserMP() )
	{
		// Reset current MarkingPeriod.
		$_SESSION['UserMP'] = $_REQUEST['marking_period_id'];
	}

	RedirectURL( 'marking_period_id' );
}

if ( ! empty( $_REQUEST['period'] ) )
{
	// @since 10.9 Set current User Course Period before Secondary Teacher logic.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

DrawHeader( ProgramTitle() . ' - ' . GetMP( UserMP() ) );

if ( ! UserCoursePeriod() )
{
	echo ErrorMessage( [ _( 'No courses assigned to teacher.' ) ], 'fatal' );
}

$gradebook_config = ProgramUserConfig( 'Gradebook' );

$course_id = DBGetOne( "SELECT COURSE_ID
	FROM course_periods
	WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

$_ROSARIO['allow_edit'] = true;

// Add eventual Dates to $_REQUEST['tables'].
AddRequestedDates( 'tables', 'post' );

if ( ! empty( $_POST['tables'] ) )
{
	$table = $_REQUEST['table'];

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		// FJ textarea fields HTML sanitize.

		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables'][$id]['DESCRIPTION'] ) );
		}

		// FJ added SQL constraint TITLE & POINTS are not null.

		if (  ( isset( $columns['TITLE'] )
			&& $columns['TITLE'] === '' )
			|| ( isset( $columns['POINTS'] )
				&& $columns['POINTS'] === '' ) )
		{
			$error[] = _( 'Please fill in the required fields' );
		}

		// FJ fix SQL bug invalid numeric data.
		// FJ default points.

		if ( ( isset( $columns['POINTS'] )
				&& ( ! is_numeric( $columns['POINTS'] )
					|| intval( $columns['POINTS'] ) < 0
					|| (string) (int) $columns['POINTS'] != $columns['POINTS'] ) )
			|| ( isset( $columns['DEFAULT_POINTS'] )
				&& $columns['DEFAULT_POINTS'] !== ''
				&& $columns['DEFAULT_POINTS'] !== '*'
				&& ( ! is_numeric( $columns['DEFAULT_POINTS'] )
					|| intval( $columns['DEFAULT_POINTS'] ) < 0
					|| (string) (int) $columns['DEFAULT_POINTS'] != $columns['DEFAULT_POINTS'] ) ) )
		{
			$error[] = _( 'Please enter valid Numeric data.' );
		}

		// FJ fix SQL bug invalid sort order.

		if ( ! empty( $columns['SORT_ORDER'] )
			&& ! is_numeric( $columns['SORT_ORDER'] ) )
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}

		$gradebook_assignment_update = false;

		if ( $id !== 'new' )
		{
			if ( ! empty( $columns['ASSIGNMENT_TYPE_ID'] )
				&& $columns['ASSIGNMENT_TYPE_ID'] != $_REQUEST['assignment_type_id'] )
			{
				$_REQUEST['assignment_type_id'] = $columns['ASSIGNMENT_TYPE_ID'];
			}

			$sql = "UPDATE " . DBEscapeIdentifier( $table ) . " SET ";

			//if ( ! $columns['COURSE_ID'] && $table=='gradebook_assignments')
			//	$columns['COURSE_ID'] = 'N';

			foreach ( (array) $columns as $column => $value )
			{
				if (  ( $column === 'DUE_DATE'
					|| $column === 'ASSIGNED_DATE' )
					&& $value !== '' )
				{
					if ( ! VerifyDate( $value ) )
					{
						$error[] = _( 'Some dates were not entered correctly.' );
					}
				}
				elseif ( $column == 'COURSE_ID'
					&& $value == 'Y'
					&& $table == 'gradebook_assignments' )
				{
					$value = $course_id;
					$sql .= 'COURSE_PERIOD_ID=NULL,';
				}
				elseif ( $column == 'COURSE_ID'
					&& $table == 'gradebook_assignments' )
				{
					$column = 'COURSE_PERIOD_ID';
					$value = UserCoursePeriod();
					$sql .= 'COURSE_ID=NULL,';
				}
				elseif ( $column == 'FINAL_GRADE_PERCENT'
					&& $table == 'gradebook_assignment_types' )
				{
					// Fix PHP8.1 fatal error unsupported operand types: string / int
					$value = ( (float) preg_replace( '/[^0-9.]/', '', $value ) ) / 100;

					if ( $value > 1 ) // 100%.
					{
						// Fix SQL error numeric field overflow when entering percent > 100.
						$value = '';
					}
				}

				// FJ default points.
				elseif ( $column == 'DEFAULT_POINTS'
					&& $value == '*'
					&& $table == 'gradebook_assignments' )
				{
					$value = '-1';
				}

				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE " .
			DBEscapeIdentifier( mb_substr( $table, 10, -1 ) . '_ID' ) . "='" . $id . "';";

			$go = true;

			$gradebook_assignment_update = true;
		}

		// New: check for Title.
		elseif ( $columns['TITLE'] )
		{
			$sql = "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

			if ( $table == 'gradebook_assignments' )
			{
				if ( ! empty( $columns['ASSIGNMENT_TYPE_ID'] ) )
				{
					$_REQUEST['assignment_type_id'] = $columns['ASSIGNMENT_TYPE_ID'];

					unset( $columns['ASSIGNMENT_TYPE_ID'] );
				}

				$fields = "ASSIGNMENT_TYPE_ID,STAFF_ID,MARKING_PERIOD_ID,";

				$values = "'" . (int) $_REQUEST['assignment_type_id'] . "','" .
				User( 'STAFF_ID' ) . "','" . UserMP() . "',";
			}
			elseif ( $table == 'gradebook_assignment_types' )
			{
				$fields = "STAFF_ID,COURSE_ID,CREATED_MP,";

				$values = "'" . User( 'STAFF_ID' ) . "','" . $course_id . "','" . UserMP() . "',";
			}

			$go = false;

			foreach ( (array) $columns as $column => $value )
			{
				if (  ( $column === 'DUE_DATE'
					|| $column === 'ASSIGNED_DATE' )
					&& $value !== '' )
				{
					if ( ! VerifyDate( $value ) )
					{
						$error[] = _( 'Some dates were not entered correctly.' );
					}
				}
				elseif ( $column === 'COURSE_ID'
					&& $value === 'Y' )
				{
					$value = $course_id;
				}
				elseif ( $column === 'COURSE_ID' )
				{
					$column = 'COURSE_PERIOD_ID';

					$value = UserCoursePeriod();
				}
				elseif ( $column == 'FINAL_GRADE_PERCENT'
					&& $table == 'gradebook_assignment_types' )
				{
					// Fix PHP8.1 fatal error unsupported operand types: string / int
					$value = ( (float) preg_replace( '/[^0-9.]/', '', $value ) ) / 100;

					if ( $value > 1 ) // 100%.
					{
						// Fix SQL error numeric field overflow when entering percent > 100.
						$value = '';
					}
				}

				//FJ default points
				elseif ( $column == 'DEFAULT_POINTS'
					&& $value == '*'
					&& $table == 'gradebook_assignments' )
				{
					$value = '-1';
				}

				if ( $value != '' )
				{
					$fields .= DBEscapeIdentifier( $column ) . ',';

					$values .= "'" . $value . "',";

					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ');';
		}

		if ( ! $error && $go )
		{
			DBQuery( $sql );

			if ( $id === 'new' )
			{
				$id = DBLastInsertID();

				if ( $table == 'gradebook_assignments' )
				{
					$_REQUEST['assignment_id'] = $id;
				}
				elseif ( $table == 'gradebook_assignment_types' )
				{
					$_REQUEST['assignment_type_id'] = $id;
				}
			}

			// Check if file submitted.

			if ( isset( $_FILES['assignment_file'] ) )
			{
				$file = UploadAssignmentTeacherFile(
					$id,
					User( 'STAFF_ID' ),
					'assignment_file'
				);

				if ( $file )
				{
					$old_assignment_file = DBGetOne( "SELECT FILE
						FROM gradebook_assignments
						WHERE ASSIGNMENT_ID='" . (int) $id . "'" );

					if ( ! empty( $old_assignment_file )
						&& file_exists( $old_assignment_file ) )
					{
						// Remove old File Attached.
						unlink( $old_assignment_file );
					}

					DBQuery( "UPDATE gradebook_assignments
						SET FILE='" . $file . "'
						WHERE ASSIGNMENT_ID='" . (int) $id . "';" );
				}
			}

			if ( $table === 'gradebook_assignments' )
			{
				if ( $gradebook_assignment_update )
				{
					// Hook.
					do_action( 'Grades/Assignments.php|update_assignment' );
				}
				else
				{
					// Hook.
					do_action( 'Grades/Assignments.php|create_assignment' );
				}
			}
		}
	}

	// Unset tables + related dates & redirect URL.
	RedirectURL( [ 'tables', 'day_tables', 'month_tables', 'year_tables' ] );
}

// DELETE

if ( $_REQUEST['modfunc'] === 'delete' )
{
	if ( ! empty( $_REQUEST['assignment_id'] )
		// SQL Check requested assignment belongs to teacher.
		&& DBGetOne( "SELECT 1 FROM gradebook_assignments
			WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'
			AND STAFF_ID='" . User( 'STAFF_ID' ) . "'" ) )
	{
		// Assignment.
		$prompt_title = _( 'Assignment' );

		$assignment_has_grades = DBGet( "SELECT 1
			FROM gradebook_grades
			WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" );

		if ( $assignment_has_grades )
		{
			$prompt_title = _( 'Assignment as well as the associated Grades' );
		}

		$assignment_file = DBGetOne( "SELECT FILE
			FROM gradebook_assignments
			WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" );

		$assignment_course_title = DBGetOne( "SELECT c.TITLE
			FROM gradebook_assignments ga,courses c,gradebook_assignment_types gat
			WHERE c.COURSE_ID=gat.COURSE_ID
			AND ga.ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'
			AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID" );

		$sql = "DELETE
			FROM gradebook_assignments
			WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'";
	}
	// SQL Check requested assignment type belongs to teacher.
	elseif ( DBGetOne( "SELECT 1 FROM gradebook_assignment_types
		WHERE ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'
		AND STAFF_ID='" . User( 'STAFF_ID' ) . "'" ) )
	{
		$assignment_type_has_assignments = DBGet( "SELECT 1
			FROM gradebook_assignments
			WHERE ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'" );

		// Can't delete Assignment Type if has Assignments!

		if ( $assignment_type_has_assignments )
		{
			// Do NOT translate, hacking prevention.
			echo ErrorMessage( [ 'Assignment Type has assignments, delete them first.' ], 'fatal' );
		}

		// Assignment Type.
		$prompt_title = _( 'Assignment Type' );

		$sql = "DELETE
			FROM gradebook_assignment_types
			WHERE ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'";
	}

	// Confirm Delete.
	if ( ! empty( $sql )
		&& DeletePrompt( $prompt_title ) )
	{
		DBQuery( $sql );

		if ( empty( $_REQUEST['assignment_id'] ) )
		{
			$assignments_RET = DBGet( "SELECT ASSIGNMENT_ID
				FROM gradebook_assignments
				WHERE ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'" );

			foreach ( (array) $assignments_RET as $assignment_id )
			{
				DBQuery( "DELETE FROM gradebook_grades
					WHERE ASSIGNMENT_ID='" . (int) $assignment_id['ASSIGNMENT_ID'] . "'" );

				$_REQUEST['assignment_id'] = $assignment_id['ASSIGNMENT_ID'];

				// Hook.
				do_action( 'Grades/Assignments.php|delete_assignment' );
			}

			DBQuery( "DELETE FROM gradebook_assignments
				WHERE ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'" );

			// Unset assignment type ID & redirect URL.
			RedirectURL( 'assignment_type_id' );
		}
		else
		{
			DBQuery( "DELETE FROM gradebook_grades
				WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" );

			if ( ! empty( $assignment_file )
				&& file_exists( $assignment_file ) )
			{
				// Delete File Attached.
				unlink( $assignment_file );
			}

			// Delete Student Assignment Submissions.
			DBQuery( "DELETE FROM student_assignments
				WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" );

			// Filename match = [course_title]_[assignment_ID]_*.
			$student_assignments_file_name = no_accents( $assignment_course_title . '_' . $_REQUEST['assignment_id'] . '_' ) . '*';

			// Files uploaded to AssignmentsFiles/[School_Year]/Teacher[teacher_ID]/Quarter[1,2,3,4...]/.
			$student_assignments_path = GetAssignmentsFilesPath( User( 'STAFF_ID' ) );

			$student_assignments_files = glob( $student_assignments_path . $student_assignments_file_name );

			foreach ( $student_assignments_files as $student_assignments_file )
			{
				// Remove Student Assignment Submission files.
				unlink( $student_assignments_file );
			}

			// Hook.
			do_action( 'Grades/Assignments.php|delete_assignment' );

			// Unset assignment ID & redirect URL.
			RedirectURL( 'assignment_id' );
		}

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
	elseif ( empty( $sql ) )
	{
		// Unset modfunc, assignment ID, assignment type ID & redirect URL.
		RedirectURL( [ 'modfunc', 'assignment_id', 'assignment_type_id' ] );
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$hide_previous_assignment_types_sql = '';

	if ( ! empty( $gradebook_config['HIDE_PREVIOUS_ASSIGNMENT_TYPES'] ) )
	{
		// @since 4.5 Hide previous quarters assignment types.
		$hide_previous_assignment_types_sql = " AND CREATED_MP='" . UserMP() . "' OR CREATED_MP IS NULL";
	}

	// Check assignment type ID is valid for current school & syear & quarter!

	if ( ! empty( $_REQUEST['assignment_type_id'] )
		&& $_REQUEST['assignment_type_id'] !== 'new' )
	{
		$assignment_type_sql = "SELECT ASSIGNMENT_TYPE_ID
			FROM gradebook_assignment_types
			WHERE COURSE_ID=(SELECT COURSE_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
			AND ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'" .
			$hide_previous_assignment_types_sql;

		$assignment_type_RET = DBGet( $assignment_type_sql );

		if ( ! $assignment_type_RET )
		{
			// Unset assignment & type IDs & redirect URL.
			RedirectURL( [ 'assignment_type_id', 'assignment_id' ] );
		}
	}

	if ( ! empty( $_REQUEST['assignment_id'] )
		&& $_REQUEST['assignment_id'] !== 'new' )
	{
		// SQL Check requested assignment belongs to teacher and current Marking Period.
		$assignment_RET = DBGet( "SELECT ASSIGNMENT_TYPE_ID,MARKING_PERIOD_ID
			FROM gradebook_assignments
			WHERE (COURSE_ID=(SELECT COURSE_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
				OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
			AND ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'
			AND STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND MARKING_PERIOD_ID='" . UserMP() . "'" );

		if ( ! $assignment_RET )
		{
			// Unset assignment & type IDs & redirect URL.
			RedirectURL( [ 'assignment_type_id', 'assignment_id' ] );
		}
		elseif ( empty( $_REQUEST['assignment_type_id'] )
			|| ! is_numeric( $_REQUEST['assignment_type_id'] ) )
		{
			// We have an Assignment ID but no type ID.
			$_REQUEST['assignment_type_id'] = $assignment_RET[1]['ASSIGNMENT_TYPE_ID'];
		}
	}

	// ASSIGNMENT TYPES.
	$assignment_types_sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,SORT_ORDER
		FROM gradebook_assignment_types
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND COURSE_ID=(SELECT COURSE_ID
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')" .
		$hide_previous_assignment_types_sql .
		" ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE";

	$types_RET = DBGet( $assignment_types_sql, [ 'TITLE' => '_makeTitle' ] );

	$delete_button = '';

	if ( $_REQUEST['assignment_id'] !== 'new'
		&& $_REQUEST['assignment_type_id'] !== 'new' )
	{
		$is_assignment = $_REQUEST['assignment_id'];

		$assignment_type_has_assignments = DBGet( "SELECT 1
			FROM gradebook_assignments
			WHERE ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'" );

		// Can't delete Assignment Type if has Assignments!

		if ( $is_assignment
			|| ! $assignment_type_has_assignments )
		{
			$delete_url = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] .
				'&modfunc=delete&assignment_type_id=' . $_REQUEST['assignment_type_id'] .
				'&assignment_id=' . $_REQUEST['assignment_id'] );

			$delete_button = '<input type="button" value="' . AttrEscape( _( 'Delete' ) ) .
				'" onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $delete_url ) . ');' ) . '" />';
		}
	}

	$new = false;

	// ADDING & EDITING FORM.

	if ( $_REQUEST['assignment_id']
		&& $_REQUEST['assignment_id'] !== 'new' )
	{
		$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,ASSIGNED_DATE,DUE_DATE,POINTS,COURSE_ID,
			DESCRIPTION,FILE,DEFAULT_POINTS,SUBMISSION,WEIGHT,
		CASE WHEN DUE_DATE<ASSIGNED_DATE THEN 'Y' ELSE NULL END AS DATE_ERROR,
		CASE WHEN ASSIGNED_DATE>(SELECT END_DATE
			FROM school_marking_periods
			WHERE MARKING_PERIOD_ID='" . UserMP() . "') THEN 'Y' ELSE NULL END AS ASSIGNED_ERROR,
		CASE WHEN DUE_DATE>(SELECT (END_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '1 DAY' : "'1 DAY'" ) . ")
			FROM school_marking_periods
			WHERE MARKING_PERIOD_ID='" . UserMP() . "') THEN 'Y' ELSE NULL END AS DUE_ERROR
		FROM gradebook_assignments
		WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'";

		$RET = DBGet( $sql );

		$RET = $RET[1];

		$title = $RET['TITLE'];
	}
	elseif ( $_REQUEST['assignment_type_id']
		&& $_REQUEST['assignment_type_id'] !== 'new'
		&& $_REQUEST['assignment_id'] !== 'new' )
	{
		$assignment_type_sql = "SELECT at.TITLE,at.FINAL_GRADE_PERCENT,SORT_ORDER,COLOR,
		(SELECT sum(FINAL_GRADE_PERCENT)
			FROM gradebook_assignment_types
			WHERE COURSE_ID=(SELECT COURSE_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
				AND STAFF_ID='" . User( 'STAFF_ID' ) . "'" .
		$hide_previous_assignment_types_sql .
		") AS TOTAL_PERCENT
		FROM gradebook_assignment_types at
		WHERE at.ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'";

		$RET = DBGet( $assignment_type_sql, [ 'FINAL_GRADE_PERCENT' => '_makePercent' ] );

		$RET = $RET[1];

		$title = $RET['TITLE'];

		if ( $assignment_type_has_assignments
			&& Preferences( 'WEIGHT', 'Gradebook' ) == 'Y' )
		{
			$assignment_type_has_assignments = DBGetOne( "SELECT 1
				FROM gradebook_assignments
				WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
				AND (COURSE_ID=(SELECT COURSE_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
				AND ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'
				AND MARKING_PERIOD_ID='" . UserMP() . "'" );

			$assignment_type_assignments_warn_all_0_points = ! DBGetOne( "SELECT 1
				FROM gradebook_assignments
				WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
				AND (COURSE_ID=(SELECT COURSE_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
				AND ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'
				AND POINTS<>'0'
				AND MARKING_PERIOD_ID='" . UserMP() . "'" );

			if ( $assignment_type_has_assignments
				&& $assignment_type_assignments_warn_all_0_points )
			{
				// @since 8.0 Add warning in case all Assignments in Type have 0 Points (Extra Credit).
				// Only when "Weight Grades" is checked under Grades > Configuration.
				$warning[] = _( 'Every Assignment in this Type have 0 Points (Extra Credit). Enter Points for at least one Assignment so the Total can be calculated correctly.' );
			}
		}
	}
	elseif ( $_REQUEST['assignment_id'] === 'new' )
	{
		if ( GetCurrentMP( 'QTR', DBDate(), false ) !== UserMP() )
		{
			// Add Warning if not in current Quarter.
			$warning[] = _( 'You are not in the current Quarter.' );
		}

		$title = _( 'New Assignment' );

		$new = true;
	}
	elseif ( $_REQUEST['assignment_type_id'] == 'new' )
	{
		$assignment_type_sql = "SELECT sum(FINAL_GRADE_PERCENT) AS TOTAL_PERCENT
			FROM gradebook_assignment_types
			WHERE COURSE_ID=(SELECT COURSE_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
			AND STAFF_ID='" . User( 'STAFF_ID' ) . "'" .
			$hide_previous_assignment_types_sql;

		$RET = DBGet( $assignment_type_sql, [ 'FINAL_GRADE_PERCENT' => '_makePercent' ] );

		$RET = $RET[1];

		$title = _( 'New Assignment Type' );

		$new = true;
	}

	echo ErrorMessage( $warning, 'warning' );

	$header = '';

	if ( ! empty( $_REQUEST['assignment_id'] ) )
	{
		/**
		 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
		 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
		 * Then teacher submits the form, data would be saved for CP B...
		 *
		 * Must be used in combination with
		 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
		 */
		$action = 'Modules.php?modname=' . $_REQUEST['modname'] . '&table=gradebook_assignments&assignment_type_id=' . $_REQUEST['assignment_type_id'] . '&period=' . UserCoursePeriod();

		if ( $_REQUEST['assignment_id'] !== 'new' )
		{
			$action .= '&assignment_id=' . $_REQUEST['assignment_id'];
		}

		echo '<form action="' . URLEscape( $action ) . '" method="POST">';

		DrawHeader( $title, $delete_button . SubmitButton() );

		$header .= '<table class="width-100p valign-top fixed-col">';
		$header .= '<tr class="st">';

		//FJ title & points are required
		$header .= '<td>' . TextInput(
			issetVal( $RET['TITLE'] ),
			'tables[' . $_REQUEST['assignment_id'] . '][TITLE]',
			_( 'Title' ),
			'required maxlength=100' . ( empty( $RET['TITLE'] ) ? ' size=20' : '' )
		) . '</td>';

		foreach ( (array) $types_RET as $type )
		{
			$assignment_type_options[$type['ASSIGNMENT_TYPE_ID']] = $type['TITLE'];
		}

		$header .= '<td>' . SelectInput(
			empty( $RET['ASSIGNMENT_TYPE_ID'] ) ? $_REQUEST['assignment_type_id'] : $RET['ASSIGNMENT_TYPE_ID'],
			'tables[' . $_REQUEST['assignment_id'] . '][ASSIGNMENT_TYPE_ID]',
			_( 'Assignment Type' ),
			$assignment_type_options,
			false
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$points_min = 0;

		$points_tooltip = '<div class="tooltip"><i>' .
			_( 'Enter 0 so you can give students extra credit' ) .
			'</i></div>';

		if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
		{
			// Disable Extra Credit assignments if weighting assignments.
			$points_min = 1;

			$points_tooltip = '';

			if ( isset( $RET['POINTS'] )
				&& $RET['POINTS'] === '0' )
			{
				$RET['POINTS'] = '';
			}
		}

		/**
		 * Note: If the Gradebook is configured to Weight Assignment Categories,
		 * and if there is 1 Extra Credit assignment alone,
		 * it is useless because Total Points sum 0:
		 * Division by zero is impossible.
		 * You should add other assignments with Points to the Type / Category.
		 */
		$header .= '<td>' . TextInput(
			issetVal( $RET['POINTS'] ),
			'tables[' . $_REQUEST['assignment_id'] . '][POINTS]',
			_( 'Points' ) . $points_tooltip,
			' type="number" min="' . (int) $points_min . '" max="9999" required'
		) . '</td>';

		// FJ default points.

		if ( empty( $RET['DEFAULT_POINTS'] ) )
		{
			$RET['DEFAULT_POINTS'] = '';
		}
		elseif ( $RET['DEFAULT_POINTS'] == '-1' )
		{
			$RET['DEFAULT_POINTS'] = '*';
		}

		$header .= '<td>' . TextInput(
			$RET['DEFAULT_POINTS'],
			'tables[' . $_REQUEST['assignment_id'] . '][DEFAULT_POINTS]',
			_( 'Default Points' ) .
			'<div class="tooltip"><i>' .
			_( 'Enter an asterisk (*) to excuse student' ) .
			'</i></div>',
			' size=4 maxlength=4'
		) . '</td>';

		if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
		{
			// @since 11.0 Add Weight Assignments option
			$header .= '</tr><tr class="st">';

			$header .= '<td colspan="2">' . TextInput(
				issetVal( $RET['WEIGHT'] ),
				'tables[' . $_REQUEST['assignment_id'] . '][WEIGHT]',
				_( 'Weight' ),
				' type="number" min="0" max="100" required'
			) . '</td>';
		}

		$header .= '</tr><tr class="st">';

		$header .= '<td colspan="2">' . TinyMCEInput(
			issetVal( $RET['DESCRIPTION'] ),
			'tables[' . $_REQUEST['assignment_id'] . '][DESCRIPTION]',
			_( 'Description' )
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$file_download = GetAssignmentFileLink( issetVal( $RET['FILE'], '' ) );

		$header .= '<td colspan="2">' . ( $file_download ? $file_download . '<br />' : '' ) .
		FileInput(
			'assignment_file',
			_( 'File' )
		) . '<hr></td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td>' . DateInput(
			$new && Preferences( 'DEFAULT_ASSIGNED', 'Gradebook' ) == 'Y' ?
				DBDate() :
				issetVal( $RET['ASSIGNED_DATE'] ),
			'tables[' . $_REQUEST['assignment_id'] . '][ASSIGNED_DATE]',
			_( 'Assigned' ),
			! $new
		) . '</td>';

		$header .= '<td>' . CheckboxInput(
			issetVal( $RET['COURSE_ID'] ),
			'tables[' . $_REQUEST['assignment_id'] . '][COURSE_ID]',
			_( 'Apply to all Periods for this Course' ),
			'',
			$_REQUEST['assignment_id'] == 'new'
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td>' . DateInput(
			$new && Preferences( 'DEFAULT_DUE', 'Gradebook' ) == 'Y' ?
				DBDate() :
				issetVal( $RET['DUE_DATE'] ),
			'tables[' . $_REQUEST['assignment_id'] . '][DUE_DATE]',
			_( 'Due' ),
			! $new
		) . '</td>';

		$header .= '<td>' . CheckboxInput(
			issetVal( $RET['SUBMISSION'] ),
			'tables[' . $_REQUEST['assignment_id'] . '][SUBMISSION]',
			_( 'Enable Assignment Submission' ),
			'',
			$_REQUEST['assignment_id'] == 'new'
		) . '</td>';

		$header .= '</tr>';

		if ( ! empty( $RET['DATE_ERROR'] )
			&& $RET['DATE_ERROR'] == 'Y' )
		{
			$error[] = _( 'Due date is before assigned date!' );
		}

		if ( ! empty( $RET['ASSIGNED_ERROR'] )
			&& $RET['ASSIGNED_ERROR'] == 'Y' )
		{
			$error[] = _( 'Assigned date is after end of quarter!' );
		}

		if ( ! empty( $RET['DUE_ERROR'] )
			&& $RET['DUE_ERROR'] == 'Y' )
		{
			$error[] = _( 'Due date is after end of quarter!' );
		}

		$header .= '<tr><td class="valign-top" colspan="2">' . ErrorMessage( $error ) . '</td></tr>';
		$header .= '</table>';
	}
	elseif ( ! empty( $_REQUEST['assignment_type_id'] ) )
	{
		/**
		 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
		 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
		 * Then teacher submits the form, data would be saved for CP B...
		 *
		 * Must be used in combination with
		 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
		 */
		$action = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&table=gradebook_assignment_types&period=' . UserCoursePeriod();

		if ( $_REQUEST['assignment_type_id'] !== 'new' )
		{
			$action .= '&assignment_type_id=' . $_REQUEST['assignment_type_id'];
		}

		echo '<form action="' . URLEscape( $action ) . '" method="POST">';

		DrawHeader( $title, $delete_button . SubmitButton() );

		$header .= '<table class="width-100p valign-top fixed-col">';
		$header .= '<tr class="st">';

		$header .= '<td>' . TextInput(
			issetVal( $RET['TITLE'] ),
			'tables[' . $_REQUEST['assignment_type_id'] . '][TITLE]',
			_( 'Title' ),
			'required maxlength=100' . ( empty( $RET['TITLE'] ) ? ' size=20' : '' )
		) . '</td>';

		$header .= '<td>' . TextInput(
			issetVal( $RET['SORT_ORDER'] ),
			'tables[' . $_REQUEST['assignment_type_id'] . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		$header .= '<td>' . ColorInput(
			issetVal( $RET['COLOR'] ),
			'tables[' . $_REQUEST['assignment_type_id'] . '][COLOR]',
			_( 'Color' )
		) . '</td>';

		if ( Preferences( 'WEIGHT', 'Gradebook' ) == 'Y' )
		{
			$header .= '</tr><tr class="st"><td>' . TextInput(
				issetVal( $RET['FINAL_GRADE_PERCENT'] ),
				'tables[' . $_REQUEST['assignment_type_id'] . '][FINAL_GRADE_PERCENT]',
				( ! empty( $RET['FINAL_GRADE_PERCENT'] ) ?
					_( 'Percent of Final Grade' ) :
					'<span class="legend-red">' . _( 'Percent of Final Grade' ) . '</span>' ),
				'maxlength="5" size="4"'
			) . '</td>';

			$header .= '<td>' . NoInput( $RET['TOTAL_PERCENT'] == 1 ? '100%' : '<span style="color:red">' . ( 100 * $RET['TOTAL_PERCENT'] ) . '%</span>', _( 'Percent Total' ) ) . '</td>';
		}

		$header .= '</tr></table>';
	}
	else
	{
		$header = false;
	}

	if ( $header )
	{
		DrawHeader( $header );
	}

	if ( ! empty( $_REQUEST['assignment_id'] )
		&& $_REQUEST['assignment_id'] !== 'new'
		&& AllowUse( 'Grades/Grades.php' ) )
	{
		// Grades program link header.
		$grades_program_link = '<a href="' . URLEscape( 'Modules.php?modname=Grades/Grades.php&type_id=' .
		$_REQUEST['assignment_type_id'] .
		'&assignment_id=' . $_REQUEST['assignment_id'] ) . '"><b>' .
		_( 'Grades' ) . '</b></a>';

		DrawHeader( $grades_program_link );
	}

	// @since 4.1.
	do_action( 'Grades/Assignments.php|header' );

	if ( $header )
	{
		echo '</form>';
	}

	// DISPLAY THE MENU
	$LO_options = [
		'save' => false,
		'search' => false,
		'add' => true,
		'responsive' => false,
	];

	if ( ! empty( $types_RET ) )
	{
		if ( ! empty( $_REQUEST['assignment_type_id'] ) )
		{
			foreach ( (array) $types_RET as $key => $value )
			{
				if ( $value['ASSIGNMENT_TYPE_ID'] == $_REQUEST['assignment_type_id'] )
				{
					$types_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
				}
			}
		}
	}

	echo '<div class="st">';

	$columns = [ 'TITLE' => _( 'Assignment Type' ), 'SORT_ORDER' => _( 'Order' ) ];

	$link = [];

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'];

	$link['TITLE']['variables'] = [ 'assignment_type_id' => 'ASSIGNMENT_TYPE_ID' ];

	$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&assignment_type_id=new';

	$link['add']['first'] = 5; // Number before add link moves to top.

	ListOutput(
		$types_RET,
		$columns,
		'Assignment Type',
		'Assignment Types',
		$link,
		[],
		$LO_options
	);

	echo '</div>';

	// ASSIGNMENTS

	if ( $_REQUEST['assignment_type_id'] && $_REQUEST['assignment_type_id'] !== 'new' && ! empty( $types_RET ) )
	{
		$assn_RET = DBGet( "SELECT ASSIGNMENT_ID,TITLE,POINTS
		FROM gradebook_assignments
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (COURSE_ID=(SELECT COURSE_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
		AND ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['assignment_type_id'] . "'
		AND MARKING_PERIOD_ID='" . UserMP() . "'
		ORDER BY " . DBEscapeIdentifier( Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) ) . " DESC",
		[ 'TITLE' => '_makeTitle' ] );

		if ( ! empty( $assn_RET ) )
		{
			if ( $_REQUEST['assignment_id'] && $_REQUEST['assignment_id'] !== 'new' )
			{
				foreach ( (array) $assn_RET as $key => $value )
				{
					if ( $value['ASSIGNMENT_ID'] == $_REQUEST['assignment_id'] )
					{
						$assn_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
					}
				}
			}
		}

		echo '<div class="st">';
		$columns = [ 'TITLE' => _( 'Assignment' ), 'POINTS' => _( 'Points' ) ];
		$link = [];
		$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&assignment_type_id=' . $_REQUEST['assignment_type_id'];
		$link['TITLE']['variables'] = [ 'assignment_id' => 'ASSIGNMENT_ID' ];
		$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&assignment_type_id=' . $_REQUEST['assignment_type_id'] . '&assignment_id=new';
		$link['add']['first'] = 5; // number before add link moves to top

		ListOutput( $assn_RET, $columns, 'Assignment', 'Assignments', $link, [], $LO_options );

		echo '</div>';
	}
}

/**
 * @param $value
 * @param $column
 */
function _makePercent( $value, $column )
{
	// Fix trim 0 (float) when percent > 1,000: do not use comma for thousand separator.
	return (float) number_format( $value * 100, 2, '.', '' ) . '%';
}

/**
 * Make Assignment (Type) Title
 * Truncate Assignment title to 36 chars only if has words > 36 chars
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 10.5.2
 *
 * @param  string $value  Title value.
 * @param  string $column Column. Defaults to 'TITLE'.
 *
 * @return string         Assignment title truncated to 36 chars.
 */
function _makeTitle( $value, $column = 'TITLE' )
{
	// Split on spaces.
	$title_words = explode( ' ', $value );

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
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}
