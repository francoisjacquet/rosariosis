<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

$_REQUEST['assignment_type'] = issetVal( $_REQUEST['assignment_type'], '' );

DrawHeader( ProgramTitle() . ' - ' . GetMP( UserMP() ) );

// Add eventual Dates to $_REQUEST['tables'].
AddRequestedDates( 'tables', 'post' );

// Get admin's Gradebook Configuration. Empty if does not override individual teacher configuration.
$gradebook_config = ProgramUserConfig( 'Gradebook' );

// TODO: add Warning before create!!
if ( AllowEdit()
	&& isset( $_POST['tables'] )
	&& ! empty( $_POST['tables'] ) )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'gradebook_assignment_types', 'gradebook_assignments' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		// FJ textarea fields HTML sanitize.
		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables'][ $id ]['DESCRIPTION'] ) );
		}

		// FJ added SQL constraint TITLE & POINTS are not null.
		if ( ( isset( $columns['TITLE'] )
				&& $columns['TITLE'] === '' )
			|| ( isset( $columns['POINTS'] )
				&& $columns['POINTS'] === '' )
			|| ( $table === 'gradebook_assignments'
				&& ! isset( $columns['TITLE'] ) ) )
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
		/*if ( ! empty( $columns['SORT_ORDER'] )
			&& ! is_numeric( $columns['SORT_ORDER'] ) )
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}*/

		if ( $table === 'gradebook_assignments' )
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

			$fields = "MARKING_PERIOD_ID,"; // ASSIGNMENT_TYPE_ID,STAFF_ID added for each CP below.

			$values = "'" . UserMP() . "',";
		}
		elseif ( $table === 'gradebook_assignment_types' )
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
				FROM course_periods
				WHERE COURSE_ID IN (" . $c_list . ")", [], [ 'COURSE_ID' ] );
			}

			$fields = ""; // COURSE_ID,STAFF_ID added for each Course below.

			$values = "";
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


		$sql = '';

		if ( $table === 'gradebook_assignments'
			&& ! empty( $_REQUEST['cp_arr'] ) )
		{
			foreach ( (array) $_REQUEST['cp_arr'] as $cp_id )
			{
				$cp_teacher = DBGetOne( "SELECT TEACHER_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
				AND SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

				$cp_assignment_type = DBGetOne( "SELECT ASSIGNMENT_TYPE_ID, STAFF_ID
				FROM gradebook_assignment_types
				WHERE COURSE_ID=(SELECT COURSE_ID
					FROM course_periods
					WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
					AND SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					LIMIT 1)
				AND TRIM(TITLE)='" . $_REQUEST['assignment_type'] . "'
				AND STAFF_ID='" . (int) $cp_teacher . "'
				LIMIT 1" );

				if ( ! $cp_assignment_type )
				{
					continue;
				}

				$sql .= "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

				$fields_final = $fields . 'ASSIGNMENT_TYPE_ID,STAFF_ID,COURSE_PERIOD_ID,';

				$values_final = $values . "'" . $cp_assignment_type . "','" . $cp_teacher . "','" . $cp_id . "',";

				$sql .= '(' . mb_substr( $fields_final, 0, -1 ) .
					') values(' . mb_substr( $values_final, 0, -1 ) . ');';
			}
		}
		elseif ( $table === 'gradebook_assignment_types'
			&& ! empty( $_REQUEST['c_arr'] ) )
		{
			foreach ( (array) $_REQUEST['c_arr'] as $c_id )
			{
				foreach ( (array) $assignment_courses_teachers_RET[ $c_id ] as $assignment_course_teacher )
				{
					$c_teacher = $assignment_course_teacher['TEACHER_ID'];

					// @since 11.2 Do NOT create Assignment Type if already exists for Course & Teacher
					$assignment_type_exists = DBGetOne( "SELECT 1
						FROM " . DBEscapeIdentifier( $table ) . "
						WHERE COURSE_ID='" . (int) $c_id . "'
						AND STAFF_ID='" . (int) $c_teacher . "'
						AND TRIM(TITLE)=TRIM('" . $columns['TITLE'] . "')" );

					if ( $assignment_type_exists )
					{
						continue;
					}

					$sql .= "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

					$fields_final = $fields . 'COURSE_ID,STAFF_ID,';

					$values_final = $values . "'" . $c_id . "','" . $c_teacher . "',";

					$sql .= '(' . mb_substr( $fields_final, 0, -1 ) .
						') values(' . mb_substr( $values_final, 0, -1 ) . ');';
				}
			}
		}

		if ( ! $error && $go && $sql )
		{
			DBQuery( $sql );

			if ( $table === 'gradebook_assignments' )
			{
				$note[] = _( 'The Assignments were successfully created.' );
			}
			elseif ( $table === 'gradebook_assignment_types' )
			{
				$note[] = _( 'The Assignment Types were successfully created.' );
			}

			if ( $table === 'gradebook_assignments' )
			{
				// TODO Hook.
				// do_action( 'Grades/MassCreateAssignments.php|mass_create_assignments' );
			}
		}
	}

	// Unset tables + related dates + CP array & redirect URL.
	RedirectURL( [ 'tables', 'day_tables', 'month_tables', 'year_tables', 'cp_arr' ] );
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
			FROM gradebook_assignment_types
			WHERE COURSE_ID IN (SELECT COURSE_ID
				FROM course_periods
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
		// Fix URL encode assignment_type value to encode "/"
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&assignment_type=' . urlencode( $_REQUEST['assignment_type'] ) . '&table=gradebook_assignments' ) . '" method="POST">';

		$submit_button = SubmitButton( _( 'Create Assignment for Selected Course Periods' ) );

		DrawHeader(
			_( 'New Assignment' ),
			$submit_button
		);

		$header = '<table class="width-100p valign-top fixed-col">';
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

		$points_min = 0;

		$points_tooltip = '<div id="points_tooltip" class="tooltip"><i>' .
			_( 'Enter 0 so you can give students extra credit' ) .
			'</i></div>';

		if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
		{
			// Disable Extra Credit assignments if "Weight Assignments".
			$points_min = 1;

			$points_tooltip = '';
		}

		$header .= '<td>' . TextInput(
			'',
			'tables[new][POINTS]',
			_( 'Points' ) . $points_tooltip,
			' type="number" min="' . (int) $points_min . '" max="9999" required'
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

		if ( empty( $gradebook_config )
			|| ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
		{
			// @since 11.0 Add Weight Assignments option
			$header .= '</tr><tr class="st">';

			$required = ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) ? ' required' : '';

			$header .= '<td colspan="2">' . TextInput(
				'',
				'tables[new][WEIGHT]',
				_( 'Weight' ),
				' type="number" min="0" max="100"' . $required
			) . '</td>';

			if ( ! $required )
			{
				ob_start();

				// JS handle case: Weight is set => Set min Points to 1 & hide tooltip.
				?>
				<script>
					$('#tablesnewWEIGHT').change(function() {
						if ($(this).val() != '') {
							$('#tablesnewPOINTS').attr('min', 1);
							$('#points_tooltip').hide();
						} else {
							$('#tablesnewPOINTS').attr('min', 0);
							$('#points_tooltip').show().css('display', 'inline-block');
						}
					});
				</script>
				<?php

				$header .= ob_get_clean();
			}
		}

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
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&table=gradebook_assignment_types' ) . '" method="POST">';

		$submit_button = SubmitButton( _( 'Create Assignment Type for Selected Courses' ) );

		DrawHeader(
			_( 'New Assignment Type' ),
			$submit_button
		);

		$header = '<table class="width-100p valign-top fixed-col">';

		$header .= '<tr class="st">';

		// FJ title is required.
		$header .= '<td>' . TextInput(
			'',
			'tables[new][TITLE]',
			_( 'Title' ),
			'required maxlength=100 size=20'
		) . '</td>';

		if ( empty( $gradebook_config )
			|| $gradebook_config['WEIGHT'] == 'Y' )
		{
			$header .= '<td>' . TextInput(
				'',
				'tables[new][FINAL_GRADE_PERCENT]',
				_( 'Percent of Final Grade' )/* .
				'<div class="tooltip"><i>' .
					_( 'Will be applied only if teacher configured his gradebook so grades are Weighted' ) .
				'</i></div>'*/,
				'maxlength="5" size="4"'
			) . '</td>';
		}

		$header .= '<td>' . ColorInput(
			'',
			'tables[new][COLOR]',
			_( 'Color' )
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
	$assignment_types_sql = "SELECT DISTINCT TRIM(TITLE) AS TITLE,TRIM(TITLE) AS TITLE_FOR_LINK
	FROM gradebook_assignment_types
	WHERE COURSE_ID IN (SELECT COURSE_ID
		FROM course_periods
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "')
	AND (CREATED_MP='" . UserMP() . "'
		OR NOT EXISTS(SELECT USER_ID
			FROM program_user_config
			WHERE TITLE='HIDE_PREVIOUS_ASSIGNMENT_TYPES'
			AND VALUE='Y'
			AND STAFF_ID=USER_ID))
	ORDER BY TITLE";

	$types_RET = DBGet( $assignment_types_sql, [ 'TITLE' => '_makeTitle' ] );

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

	$columns = [ 'TITLE' => _( 'Assignment Type' ) ];

	$link = [];

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'];

	$link['TITLE']['variables'] = [ 'assignment_type' => 'TITLE_FOR_LINK' ];

	$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&assignment_type=new';

	$link['add']['first'] = 5; // number before add link moves to top

	$LO_options = [
		'save' => false,
		'search' => false,
		'add' => true,
		'responsive' => false,
	];

	echo '<div class="st">';

	ListOutput(
		$types_RET,
		$columns,
		'Assignment Type',
		'Assignment Types',
		$link,
		[],
		$LO_options
	);

	echo '</div><div class="st">';

	if ( $header )
	{
		if ( $_REQUEST['assignment_type'] === 'new' )
		{
			$columns = [
				'COURSE_ID' => MakeChooseCheckbox( '', '', 'c_arr' ),
				'TITLE' => _( 'Course' ),
				'SUBJECT' => _( 'Subject' ),
			];

			// Display the courses list.
			// Fix SQL error when course has no periods.
			$courses_RET = DBGet( "SELECT c.COURSE_ID,
				c.TITLE,cs.TITLE AS SUBJECT
				FROM courses c, course_subjects cs
				WHERE c.SCHOOL_ID='" . UserSchool() . "'
				AND c.SYEAR='" . UserSyear() . "'
				AND cs.SCHOOL_ID=c.SCHOOL_ID
				AND cs.SYEAR=c.SYEAR
				AND cs.SUBJECT_ID=c.SUBJECT_ID
				AND EXISTS(SELECT 1
					FROM course_periods cp
					WHERE cp.SCHOOL_ID=c.SCHOOL_ID
					AND cp.SYEAR=c.SYEAR
					AND cp.COURSE_ID=c.COURSE_ID)
				ORDER BY cs.TITLE, c.TITLE",
				[ 'COURSE_ID' => 'MakeChooseCheckbox', 'MARKING_PERIOD_ID' => 'GetMP' ]
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
				FROM gradebook_assignment_types gat, course_periods cp2
				WHERE TRIM(gat.TITLE)='" . $_REQUEST['assignment_type'] . "'
				AND gat.STAFF_ID=cp2.TEACHER_ID
				AND gat.COURSE_ID IN (SELECT COURSE_ID
					FROM course_periods
					WHERE SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "')
				AND gat.COURSE_ID=cp2.COURSE_ID
				AND cp2.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . "))";

			$columns = [
				'COURSE_PERIOD_ID' => MakeChooseCheckbox( '', '', 'cp_arr' ),
				'COURSE' => _( 'Course' ),
				'TITLE' => _( 'Period' ) . ' ' . _( 'Days' ) . ' - ' . _( 'Short Name' ) . ' - ' . _( 'Teacher' ),
				'MARKING_PERIOD_ID' => _( 'Marking Period' ),
				// 'SUBJECT' => _( 'Subject' ),
			];

			// Display the course periods list.
			$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID, cp.TITLE,
				c.TITLE AS COURSE, cs.TITLE AS SUBJECT, cp.MARKING_PERIOD_ID
				FROM course_periods cp, courses c, course_subjects cs
				WHERE cp.SCHOOL_ID='" . UserSchool() . "'
				AND cp.SYEAR='" . UserSyear() . "'
				AND cp.SCHOOL_ID=c.SCHOOL_ID
				AND cp.SYEAR=c.SYEAR
				AND cs.SCHOOL_ID=c.SCHOOL_ID
				AND cs.SYEAR=c.SYEAR
				AND cp.COURSE_ID=c.COURSE_ID
				AND cs.SUBJECT_ID=c.SUBJECT_ID" . $course_periods_limit_sql .
				" ORDER BY COURSE, cp.SHORT_NAME",
				[ 'COURSE_PERIOD_ID' => 'MakeChooseCheckbox', 'MARKING_PERIOD_ID' => 'GetMP' ]
			);

			ListOutput(
				$course_periods_RET,
				$columns,
				'Course Period',
				'Course Periods'
			);
		}

		echo '</div><div class="center" style="clear: left">' . $submit_button . '</div>';
		echo '</form>';
	}
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
