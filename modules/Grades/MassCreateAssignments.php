<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

DrawHeader( ProgramTitle() . ' - ' . GetMP( UserMP() ) );

// Add eventual Dates to $_REQUEST['tables'].
AddRequestedDates( 'tables', 'post' );

// TODO: add Warning before create!!
if ( isset( $_POST['tables'] )
	&& ! empty( $_POST['tables'] ) )
{
	$table = isset( $_REQUEST['table'] ) ? $_REQUEST['table'] : null;

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		// FJ textarea fields HTML sanitize.
		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = SanitizeHTML( $_POST['tables'][ $id ]['DESCRIPTION'] );
		}

		// FJ added SQL constraint TITLE & POINTS are not null.
		if ( ( isset( $columns['TITLE'] )
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
					|| intval( $columns['POINTS'] ) < 0 ) )
			|| ( isset( $columns['DEFAULT_POINTS'] )
				&& $columns['DEFAULT_POINTS'] !== ''
				&& $columns['DEFAULT_POINTS'] !== '*'
				&& ( ! is_numeric( $columns['DEFAULT_POINTS'] )
					|| intval( $columns['DEFAULT_POINTS'] ) < 0 ) ) )
		{
			$error[] = _( 'Please enter valid Numeric data.' );
		}

		// FJ fix SQL bug invalid sort order.
		/*if ( ! empty( $columns['SORT_ORDER'] )
			&& ! is_numeric( $columns['SORT_ORDER'] ) )
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}*/

		if ( $table === 'GRADEBOOK_ASSIGNMENTS' )
		{
			if ( ! isset( $_REQUEST['cp_arr'] )
				|| ! is_array( $_REQUEST['cp_arr'] ) )
			{
				$error[] = _( 'You must choose a course.' );

				$cp_list = "''";
			}
			else
			{
				$cp_list = "'" . implode( "','", $_REQUEST['cp_arr'] ) . "'";
			}

			$fields = "ASSIGNMENT_ID,MARKING_PERIOD_ID,"; // ASSIGNMENT_TYPE_ID,STAFF_ID added for each CP below.

			$values = db_seq_nextval( 'GRADEBOOK_ASSIGNMENTS_SEQ' ) . ",'" . UserMP() . "',";
		}
		elseif ( $table === 'GRADEBOOK_ASSIGNMENT_TYPES' )
		{
			if ( ! isset( $_REQUEST['c_arr'] )
				|| ! is_array( $_REQUEST['c_arr'] ) )
			{
				$error[] = _( 'You must choose a course.' );
			}
			else
			{
				$c_list = "'" . implode( "','", $_REQUEST['c_arr'] ) . "'";

				$assignment_courses_teachers_RET = DBGet( "SELECT DISTINCT COURSE_ID,TEACHER_ID
				FROM COURSE_PERIODS
				WHERE COURSE_ID IN (" . $c_list . ")", array(), array( 'COURSE_ID' ) );
			}

			$fields = "ASSIGNMENT_TYPE_ID,"; // COURSE_ID,STAFF_ID added for each Course below.

			$values = db_seq_nextval( 'GRADEBOOK_ASSIGNMENT_TYPES_SEQ' ) . ",";
		}

		$go = false;

		foreach ( (array) $columns as $column => $value )
		{
			if ( ( $column === 'DUE_DATE'
					|| $column === 'ASSIGNED_DATE' )
				&& $value !== '' )
			{
				$end_of_quarter_date = GetMP( UserMP(), 'END_DATE' );

				if ( ! VerifyDate( $value ) )
				{
					$error[] = _( 'Some dates were not entered correctly.' );
				}
				elseif ( $column === 'DUE_DATE' )
				{
					if ( $value < $columns['ASSIGNED_DATE'] )
					{
						$error[] = _( 'Due date is before assigned date!' );
					}

					if ( str_replace( '-', '', $end_of_quarter_date ) + 1 < $value )
					{
						$error[] = _( 'Due date is after end of quarter!' );
					}
				}
				elseif ( $column === 'ASSIGNED_DATE'
					&& $end_of_quarter_date < $value )
				{
					$error[] = _( 'Assigned date is after end of quarter!' );
				}
			}
			elseif ( $column == 'FINAL_GRADE_PERCENT'
				&& $table == 'GRADEBOOK_ASSIGNMENT_TYPES' )
			{
				$value = preg_replace('/[^0-9.]/','',$value) / 100;
			}
			//FJ default points
			elseif ( $column == 'DEFAULT_POINTS'
				&& $value == '*'
				&& $table == 'GRADEBOOK_ASSIGNMENTS' )
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


		$sql = '';

		if ( $table === 'GRADEBOOK_ASSIGNMENTS' )
		{
			foreach ( (array) $_REQUEST['cp_arr'] as $cp_id )
			{
				$sql .= "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

				$fields_final = $fields . 'ASSIGNMENT_TYPE_ID,STAFF_ID,COURSE_PERIOD_ID,';

				$assignment_type_teacher_RET = DBGet( "SELECT ASSIGNMENT_TYPE_ID, STAFF_ID
				FROM GRADEBOOK_ASSIGNMENT_TYPES
				WHERE COURSE_ID=(SELECT COURSE_ID
					FROM COURSE_PERIODS
					WHERE COURSE_PERIOD_ID='" . $cp_id . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					LIMIT 1)
				AND TRIM(TITLE)='" . $_REQUEST['assignment_type'] . "'
				LIMIT 1" );

				if ( ! $assignment_type_teacher_RET )
				{
					continue;
				}

				$cp_teacher = $assignment_type_teacher_RET[1]['STAFF_ID'];

				$cp_assignment_type = $assignment_type_teacher_RET[1]['ASSIGNMENT_TYPE_ID'];

				$values_final = $values . "'" . $cp_assignment_type . "','" . $cp_teacher . "','" . $cp_id . "',";

				$sql .= '(' . mb_substr( $fields_final, 0, -1 ) .
					') values(' . mb_substr( $values_final, 0, -1 ) . ');';
			}
		}
		elseif ( $table === 'GRADEBOOK_ASSIGNMENT_TYPES' )
		{
			foreach ( (array) $_REQUEST['c_arr'] as $c_id )
			{
				foreach ( (array) $assignment_courses_teachers_RET[ $c_id ] as $assignment_course_teacher )
				{
					$sql .= "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

					$fields_final = $fields . 'COURSE_ID,STAFF_ID,';

					$c_teacher = $assignment_course_teacher['TEACHER_ID'];

					$values_final = $values . "'" . $c_id . "','" . $c_teacher . "',";

					$sql .= '(' . mb_substr( $fields_final, 0, -1 ) .
						') values(' . mb_substr( $values_final, 0, -1 ) . ');';
				}
			}
		}

		if ( ! $error && $go && $sql )
		{
			DBQuery( $sql );

			if ( $table === 'GRADEBOOK_ASSIGNMENTS' )
			{
				$note[] = _( 'The Assignments were successfully created.' );
			}
			elseif ( $table === 'GRADEBOOK_ASSIGNMENT_TYPES' )
			{
				$note[] = _( 'The Assignment Types were successfully created.' );
			}

			if ( $table === 'GRADEBOOK_ASSIGNMENTS' )
			{
				// TODO Hook.
				// do_action( 'Grades/MassCreateAssignments.php|mass_create_assignments' );
			}
		}
	}

	// Unset tables & redirect URL.
	RedirectURL( 'tables' );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

if ( ! $_REQUEST['modfunc'] )
{
	$course_periods_limit_sql = '';

	// Check assignment type is valid for current school & syear!
	if ( isset( $_REQUEST['assignment_type'] )
		&& $_REQUEST['assignment_type'] !== 'new' )
	{
		$assignment_type_RET = DBGet( "SELECT ASSIGNMENT_TYPE_ID
			FROM GRADEBOOK_ASSIGNMENT_TYPES
			WHERE COURSE_ID IN (SELECT COURSE_ID
				FROM COURSE_PERIODS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "')
			AND TRIM(TITLE)='" . $_REQUEST['assignment_type'] . "'" );

		if ( ! $assignment_type_RET )
		{
			// Unset assignment type & redirect URL.
			RedirectURL( 'assignment_type' );
		}
	}

	if ( $_REQUEST['assignment_type']
		&& $_REQUEST['assignment_type'] !== 'new' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&assignment_type=' . $_REQUEST['assignment_type'] . '&table=GRADEBOOK_ASSIGNMENTS" method="POST">';

		$submit_button = SubmitButton( _( 'Create Assignment for Selected Course Periods' ) );

		DrawHeader(
			_( 'New Assignment' ),
			$submit_button
		);

		$header .= '<table class="width-100p valign-top fixed-col">';
		$header .= '<tr class="st">';

		// FJ title & points are required.
		$header .= '<td>' . TextInput(
			'',
			'tables[new][TITLE]',
			_( 'Title' ),
			'required maxlength=100 size=20'
		) . '</td>';

		$header .= '<td>' . NoInput(
			$_REQUEST['assignment_type'],
			_( 'Assignment Type' )
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td>' . TextInput(
			'',
			'tables[new][POINTS]',
			_( 'Points' ) .
				'<div class="tooltip"><i>' .
					_( 'Enter 0 so you can give students extra credit' ) .
				'</i></div>',
			'required size=4 maxlength=4 min=0'
		) . '</td>';

		$header .= '<td>' . TextInput(
			'',
			'tables[new][DEFAULT_POINTS]',
			_( 'Default Points' ) .
				'<div class="tooltip"><i>' .
					_( 'Enter an asterisk (*) to excuse student' ) .
				'</i></div>',
			' size=4 maxlength=4'
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td colspan="2">' . TinyMCEInput(
			'',
			'tables[new][DESCRIPTION]',
			_( 'Description' )
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td>' . DateInput(
			DBDate(),
			'tables[new][ASSIGNED_DATE]',
			_( 'Assigned' ),
			false
		) . '</td>';

		$header .= '<td>' . CheckboxInput(
			'',
			'tables[new][SUBMISSION]',
			_( 'Enable Assignment Submission' ),
			'',
			true
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td>' . DateInput(
			'',
			'tables[new][DUE_DATE]',
			_( 'Due' ),
			false
		) . '</td>';

		$header .= '</tr></table>';
	}
	elseif ( $_REQUEST['assignment_type'] === 'new' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&table=GRADEBOOK_ASSIGNMENT_TYPES" method="POST">';

		$submit_button = SubmitButton( _( 'Create Assignment Type for Selected Courses' ) );

		DrawHeader(
			_( 'New Assignment Type' ),
			$submit_button
		);

		$header .= '<table class="width-100p valign-top fixed-col">';

		$header .= '<tr class="st">';

		// FJ title is required.
		$header .= '<td>' . TextInput(
			'',
			'tables[new][TITLE]',
			_( 'Title' ),
			'required maxlength=100 size=20'
		) . '</td>';

		$header .= '<td>' . TextInput(
			'',
			'tables[new][FINAL_GRADE_PERCENT]',
			_( 'Percent of Final Grade' )/* .
			'<div class="tooltip"><i>' .
				_( 'Will be applied only if teacher configured his gradebook so grades are Weighted' ) .
			'</i></div>'*/,
			'maxlength="5" size="4"'
		) . '</td>';

		$header .= '<td>' . ColorInput(
			'',
			'tables[new][COLOR]',
			_( 'Color' ),
			'hidden'
		) . '</td>';

		$header .= '</tr></table>';
	}
	else
		$header = false;

	if ( $header )
	{
		DrawHeader( $header );
	}

	// DISPLAY THE MENU
	// ASSIGNMENT TYPES.
	// @since 4.5 Hide previous quarters assignment types.
	$assignment_types_sql = "SELECT DISTINCT TRIM(TITLE) AS TITLE
	FROM GRADEBOOK_ASSIGNMENT_TYPES
	WHERE COURSE_ID IN (SELECT COURSE_ID
		FROM COURSE_PERIODS
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "')
	AND (CREATED_MP='" . UserMP() . "'
		OR NOT EXISTS(SELECT USER_ID
			FROM PROGRAM_USER_CONFIG
			WHERE TITLE='HIDE_PREVIOUS_ASSIGNMENT_TYPES'
			AND VALUE='Y'
			AND STAFF_ID=USER_ID))
	ORDER BY TITLE";

	$types_RET = DBGet( $assignment_types_sql );

	if ( $_REQUEST['assignment_type'] !== 'new' )
	{
		foreach ( (array) $types_RET as $key => $value )
		{
			if ( $value['TITLE'] === $_REQUEST['assignment_type'] )
			{
				$types_RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$columns = array( 'TITLE' => _( 'Assignment Type' ) );

	$link = array();

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'];

	$link['TITLE']['variables'] = array( 'assignment_type' => 'TITLE' );

	$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&assignment_type=new';

	$link['add']['first'] = 5; // number before add link moves to top

	$LO_options = array(
		'save' => false,
		'search' => false,
		'add' => true,
		'responsive' => false,
	);

	echo '<div class="st">';

	ListOutput(
		$types_RET,
		$columns,
		'Assignment Type',
		'Assignment Types',
		$link,
		array(),
		$LO_options
	);

	echo '</div>';

	if ( $header )
	{
		if ( $_REQUEST['assignment_type'] === 'new' )
		{
			$columns = array(
				'COURSE_ID' => MakeChooseCheckbox( 'Y', '', 'c_arr' ),
				'TITLE' => _( 'Title' ),
				'SUBJECT' => _( 'Subject' ),
			);

			// Display the courses list.
			// Fix SQL error when course has no periods.
			$courses_RET = DBGet( "SELECT c.COURSE_ID,
				c.TITLE,cs.TITLE AS SUBJECT
				FROM COURSES c, COURSE_SUBJECTS cs
				WHERE c.SCHOOL_ID='" . UserSchool() . "'
				AND c.SYEAR='" . UserSyear() . "'
				AND cs.SCHOOL_ID=c.SCHOOL_ID
				AND cs.SYEAR=c.SYEAR
				AND cs.SUBJECT_ID=c.SUBJECT_ID
				AND EXISTS(SELECT 1
					FROM COURSE_PERIODS cp
					WHERE cp.SCHOOL_ID=c.SCHOOL_ID
					AND cp.SYEAR=c.SYEAR
					AND cp.COURSE_ID=c.COURSE_ID)
				ORDER BY cs.TITLE, c.TITLE",
				array( 'COURSE_ID' => 'MakeChooseCheckbox', 'MARKING_PERIOD_ID' => 'GetMP' )
			);

			ListOutput(
				$courses_RET,
				$columns,
				'Course',
				'Courses'
			);
		} else {

			// Limit course periods to the ones where the assignment type exists
			// and to the ones in the current MP.
			$course_periods_limit_sql = " AND cp.COURSE_PERIOD_ID IN (SELECT cp2.COURSE_PERIOD_ID
				FROM GRADEBOOK_ASSIGNMENT_TYPES gat, COURSE_PERIODS cp2
				WHERE gat.COURSE_ID IN (SELECT COURSE_ID
					FROM COURSE_PERIODS
					WHERE SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "')
				AND TRIM(gat.TITLE)='" . $_REQUEST['assignment_type'] . "'
				AND gat.COURSE_ID=cp2.COURSE_ID
				AND cp2.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . "))";

			$columns = array(
				'COURSE_PERIOD_ID' => MakeChooseCheckbox( 'Y', '', 'cp_arr' ),
				'TITLE' => _( 'Title' ),
				'COURSE' => _( 'Course' ),
				'MARKING_PERIOD_ID' => _( 'Marking Period' ),
				// 'SUBJECT' => _( 'Subject' ),
			);

			// Display the course periods list.
			$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID, cp.TITLE,
				c.TITLE AS COURSE, cs.TITLE AS SUBJECT, cp.MARKING_PERIOD_ID
				FROM COURSE_PERIODS cp, COURSES c, COURSE_SUBJECTS cs
				WHERE cp.SCHOOL_ID='" . UserSchool() . "'
				AND cp.SYEAR='" . UserSyear() . "'
				AND cp.SCHOOL_ID=c.SCHOOL_ID
				AND cp.SYEAR=c.SYEAR
				AND cs.SCHOOL_ID=c.SCHOOL_ID
				AND cs.SYEAR=c.SYEAR
				AND cp.COURSE_ID=c.COURSE_ID
				AND cs.SUBJECT_ID=c.SUBJECT_ID" . $course_periods_limit_sql .
				" ORDER BY COURSE, cp.SHORT_NAME",
				array( 'COURSE_PERIOD_ID' => 'MakeChooseCheckbox', 'MARKING_PERIOD_ID' => 'GetMP' )
			);

			ListOutput(
				$course_periods_RET,
				$columns,
				'Course Period',
				'Course Periods'
			);
		}

		echo '<div class="center">' . $submit_button . '</div>';
		echo '</form>';
	}
}
