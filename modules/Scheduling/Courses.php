<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'modules/Scheduling/includes/Courses.fnc.php';

$_REQUEST['subject_id'] = issetVal( $_REQUEST['subject_id'], '' );

if ( $_REQUEST['subject_id'] && $_REQUEST['subject_id'] !== 'new' )
{
	$_REQUEST['subject_id'] = (string) (int) $_REQUEST['subject_id'];
}

$_REQUEST['course_id'] = issetVal( $_REQUEST['course_id'], '' );

if ( $_REQUEST['course_id'] && $_REQUEST['course_id'] !== 'new' )
{
	$_REQUEST['course_id'] = (string) (int) $_REQUEST['course_id'];
}

$_REQUEST['course_period_id'] = issetVal( $_REQUEST['course_period_id'], '' );

if ( $_REQUEST['course_period_id'] && $_REQUEST['course_period_id'] !== 'new' )
{
	$_REQUEST['course_period_id'] = (string) (int) $_REQUEST['course_period_id'];
}

$_REQUEST['last_year'] = issetVal( $_REQUEST['last_year'], '' );

if ( $_REQUEST['modfunc'] !== 'choose_course' )
{
	DrawHeader( ProgramTitle() );
}

// If only one subject, select it automatically -- works for Course Setup and Choose a Course.

if ( $_REQUEST['modfunc'] !== 'delete'
	&& empty( $_REQUEST['subject_id'] ) )
{
	$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
		FROM course_subjects
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . ( $_REQUEST['modfunc'] === 'choose_course'
		&& $_REQUEST['last_year'] === 'true' ?
		UserSyear() - 1 :
		UserSyear() ) . "'" );

	if ( count( (array) $subjects_RET ) == 1 )
	{
		$_REQUEST['subject_id'] = $subjects_RET[1]['SUBJECT_ID'];
	}
}

$LO_options = [
	'save' => false,
	'search' => false,
	'responsive' => false,
];

if ( isset( $_REQUEST['course_modfunc'] )
	&& $_REQUEST['course_modfunc'] === 'search' )
{
	echo '<br />';

	PopTable( 'header', _( 'Search' ) );

	echo '<form name="search" action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=' . $_REQUEST['modfunc'] . '&course_modfunc=search&last_year=' .
		$_REQUEST['last_year']  ) . '" method="POST">'; // Fix Search: Use POST for Public Pages plugin compatibility.

	echo '<table><tr><td><input type="text" name="search_term" value="' .
		AttrEscape( DBUnescapeString( issetVal( $_REQUEST['search_term'], '' ) ) ) . '" required autofocus /></td>
		<td>' . Buttons( _( 'Search' ) ) . '</td></tr></table>';

	if ( $_REQUEST['modfunc'] === 'choose_course'
		&& $_REQUEST['modname'] === 'Scheduling/Schedule.php' )
	{
		echo '<input type="hidden" name="include_child_mps" value="' . AttrEscape( $_REQUEST['include_child_mps'] ) . '" />
			<input type="hidden" name="year_date" value="' . AttrEscape( $_REQUEST['year_date'] ) . '" />
			<input type="hidden" name="month_date" value="' . AttrEscape( $_REQUEST['month_date'] ) . '" />
			<input type="hidden" name="day_date" value="' . AttrEscape( $_REQUEST['day_date'] ) . '" />';
	}

	echo '</form>';

	echo '<script>document.search.search_term.focus();</script>';

	PopTable( 'footer' );

	if ( ! empty( $_REQUEST['search_term'] ) )
	{
		// FJ add Available Seats column to every choose course popup.

		if ( $_REQUEST['modname'] !== 'Scheduling/Schedule.php' )
		{
			$date = DBDate();
		}

		$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
			FROM course_subjects
			WHERE (UPPER(TITLE) LIKE '%" . mb_strtoupper( $_REQUEST['search_term'] ) . "%'
			OR UPPER(SHORT_NAME)='" . mb_strtoupper( $_REQUEST['search_term'] ) . "')
			AND SYEAR='" . ( $_REQUEST['modfunc'] === 'choose_course'
			&& $_REQUEST['last_year'] === 'true' ?
			UserSyear() - 1 :
			UserSyear() ) . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

		$courses_RET = DBGet( "SELECT SUBJECT_ID,COURSE_ID,TITLE
			FROM courses
			WHERE (UPPER(TITLE) LIKE '%" . mb_strtoupper( $_REQUEST['search_term'] ) . "%'
			OR UPPER(SHORT_NAME)='" . mb_strtoupper( $_REQUEST['search_term'] ) . "')
			AND SYEAR='" . ( $_REQUEST['modfunc'] === 'choose_course'
			&& $_REQUEST['last_year'] === 'true' ?
			UserSyear() - 1 :
			UserSyear() ) . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY TITLE" );

		// FJ http://centresis.org/forums/viewtopic.php?f=13&t=4112
		$periods_RET = DBGet( "SELECT c.SUBJECT_ID,cp.COURSE_ID,cp.COURSE_PERIOD_ID,
			cp.TITLE,cp.MP,cp.MARKING_PERIOD_ID,cp.CALENDAR_ID,cp.TOTAL_SEATS AS AVAILABLE_SEATS
			FROM course_periods cp,courses c
			WHERE cp.COURSE_ID=c.COURSE_ID
			AND (UPPER(cp.TITLE) LIKE '%" . mb_strtoupper( $_REQUEST['search_term'] ) . "%'
			OR UPPER(cp.SHORT_NAME)='" . mb_strtoupper( $_REQUEST['search_term'] ) . "')
			AND cp.SYEAR='" . ( $_REQUEST['modfunc'] === 'choose_course' &&
			$_REQUEST['last_year'] === 'true' ?
			UserSyear() - 1 :
			UserSyear() ) . "'
			AND cp.SCHOOL_ID='" . UserSchool() . "'" .
			( $_REQUEST['modfunc'] === 'choose_course'
				&& $_REQUEST['modname'] === 'Scheduling/Schedule.php' ?
				" AND '" . $date . "'<=(SELECT END_DATE
					FROM school_marking_periods
					WHERE SYEAR=cp.SYEAR
					AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)" :
				'' ) . "
			ORDER BY cp.SHORT_NAME,TITLE" );

		//if ( $_REQUEST['modname']=='Scheduling/Schedule.php')
		calcSeats1( $periods_RET, $date );

		$link = [];

		$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=' . $_REQUEST['modfunc'] . '&last_year=' . $_REQUEST['last_year'];

		if ( $_REQUEST['modfunc'] === 'choose_course'
			&& $_REQUEST['modname'] === 'Scheduling/Schedule.php' )
		{
			$link['TITLE']['link'] .= '&include_child_mps=' . $_REQUEST['include_child_mps'] .
				'&year_date=' . $_REQUEST['year_date'] .
				'&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'];
		}

		$link['TITLE']['variables'] = [ 'subject_id' => 'SUBJECT_ID' ];

		echo '<div class="st">';

		ListOutput(
			$subjects_RET,
			[ 'TITLE' => _( 'Subject' ) ],
			'Subject',
			'Subjects',
			$link,
			[],
			$LO_options
		);

		$link['TITLE']['variables'] = [
			'subject_id' => 'SUBJECT_ID',
			'course_id' => 'COURSE_ID',
		];

		echo '</div><div class="st">';

		ListOutput(
			$courses_RET,
			[ 'TITLE' => _( 'Course' ) ],
			'Course',
			'Courses',
			$link,
			[],
			$LO_options
		);

		$columns = [ 'TITLE' => _( 'Course Period' ) ];

		$link = [];

		if ( $_REQUEST['modname'] !== 'Scheduling/Schedule.php'
			|| ( $_REQUEST['modname'] === 'Scheduling/Schedule.php'
				&& ! $_REQUEST['include_child_mps'] ) )
		{
			$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=' . $_REQUEST['modfunc'] . '&last_year=' . $_REQUEST['last_year'];

			$link['TITLE']['variables'] = [
				'subject_id' => 'SUBJECT_ID',
				'course_id' => 'COURSE_ID',
				'course_period_id' => 'COURSE_PERIOD_ID',
			];

			if ( $_REQUEST['modfunc'] === 'choose_course' )
			{
				$link['TITLE']['link'] .= '&modfunc=' . $_REQUEST['modfunc'] .
					'&last_year=' . $_REQUEST['last_year'];
			}
		}

		//if ( $_REQUEST['modname']=='Scheduling/Schedule.php')
		$columns += [
			'AVAILABLE_SEATS' => ( ! empty( $_REQUEST['include_child_mps'] ) ?
				_( 'MP' ) . '(' . _( 'Available Seats' ) . ')' :
				_( 'Available Seats' ) ),
		];

		echo '</div><div class="st">';

		ListOutput(
			$periods_RET,
			$columns,
			'Course Period',
			'Course Periods',
			$link,
			[],
			$LO_options
		);

		echo '</div>';
	}
}

// FJ days display to locale.
$days_convert = [
	'U' => _( 'Sunday' ),
	'M' => _( 'Monday' ),
	'T' => _( 'Tuesday' ),
	'W' => _( 'Wednesday' ),
	'H' => _( 'Thursday' ),
	'F' => _( 'Friday' ),
	'S' => _( 'Saturday' ),
];

// FJ days numbered.

if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
{
	$days_convert = [
		'U' => '7',
		'M' => '1',
		'T' => '2',
		'W' => '3',
		'H' => '4',
		'F' => '5',
		'S' => '6',
	];
}

// UPDATING.

if ( ! empty( $_REQUEST['tables'] )
	&& $_POST['tables']
	&& AllowEdit() )
{
	$where = [
		'course_subjects' => 'SUBJECT_ID',
		'courses' => 'COURSE_ID',
		'course_periods' => 'COURSE_PERIOD_ID',
		'course_period_school_periods' => 'COURSE_PERIOD_SCHOOL_PERIODS_ID',
	];

	if ( isset( $_REQUEST['tables']['parent_id'] ) )
	{
		$_REQUEST['tables']['course_periods'][$_REQUEST['course_period_id']]['PARENT_ID'] = $_REQUEST['tables']['parent_id'];

		unset( $_REQUEST['tables']['parent_id'] );
	}

	// FJ bugfix SQL error invalid input syntax for type numeric
	// when course_period_school_periods saved before course_periods, but why?

	if ( $_REQUEST['course_period_id'] == 'new' )
	{
		foreach ( (array) $_REQUEST['tables'] as $table_name => $tables )
		{
			if ( $table_name === 'course_period_school_periods' )
			{
				unset( $_REQUEST['tables'][$table_name] );

				// Push course_period_school_periods after course_periods.
				$_REQUEST['tables'][$table_name] = $tables;

				break;
			}
		}
	}

	$temp_PERIOD_ID = [];

	foreach ( (array) $_REQUEST['tables'] as $table_name => $tables )
	{
		foreach ( (array) $tables as $id => $columns )
		{
			// FJ fix SQL bug invalid numeric data.

			if (  ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) ) && ( empty( $columns['CREDIT_HOURS'] ) || is_numeric( $columns['CREDIT_HOURS'] ) ) && ( empty( $columns['CREDITS'] ) || is_numeric( $columns['CREDITS'] ) ) )
			{
				//FJ added SQL constraint TITLE (course_subjects & courses) & SHORT_NAME, TEACHER_ID (course_periods) & PERIOD_ID (course_period_school_periods) are not null

				if ( ! (  ( isset( $columns['TITLE'] ) && empty( $columns['TITLE'] ) ) || ( $table_name == 'course_periods' && (  ( isset( $columns['SHORT_NAME'] ) && empty( $columns['SHORT_NAME'] ) ) || ( isset( $columns['TEACHER_ID'] ) && empty( $columns['TEACHER_ID'] ) ) ) ) || ( mb_strpos( $id, 'new' ) !== false && ! empty( $columns['PERIOD_ID'] ) && ! isset( $columns['DAYS'] ) ) ) )
				{
					if ( $table_name === 'courses'
						&& $columns['DESCRIPTION'] )
					{
						// Sanitize Course Description HTML. Get data from $_POST as it has HTML tags.
						$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables']['courses'][ $id ]['DESCRIPTION'] ) );
					}

					if ( isset( $columns['TOTAL_SEATS'] )
						&& ! is_numeric( $columns['TOTAL_SEATS'] ) )
					{
						$columns['TOTAL_SEATS'] = preg_replace( '/[^0-9]+/', '', $columns['TOTAL_SEATS'] );
					}

					$days = '';

					if ( ! empty( $columns['DAYS'] ) )
					{
						foreach ( (array) $columns['DAYS'] as $day => $y )
						{
							if ( $y == 'Y' )
							{
								$days .= $day;
							}
						}

						$columns['DAYS'] = $days;
					}

					if ( ! empty( $columns['DOES_ATTENDANCE'] ) )
					{
						$tbls = '';

						foreach ( (array) $columns['DOES_ATTENDANCE'] as $tbl => $y )
						{
							if ( $y == 'Y' )
							{
								$tbls .= ',' . $tbl;
							}
						}

						$columns['DOES_ATTENDANCE'] = $tbls ? $tbls . ',' : '';
					}

					// if ( $id!== 'new')

					if ( mb_strpos( $id, 'new' ) === false )
					{
						if ( $table_name == 'courses'
							&& ! empty( $columns['SUBJECT_ID'] )
							&& $columns['SUBJECT_ID'] != $_REQUEST['subject_id'] )
						{
							$_REQUEST['subject_id'] = $columns['SUBJECT_ID'];
						}

						$update_columns = [];

						if ( $table_name == 'course_periods' )
						{
							$current_cp = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,SHORT_NAME,TEACHER_ID,CREDITS
								FROM course_periods
								WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'" );

							$base_title = CoursePeriodTitleGenerate( $id, $columns );

							$update_columns['TITLE'] = DBEscapeString( $base_title );

							if ( isset( $columns['MARKING_PERIOD_ID'] ) )
							{
								if ( GetMP( $columns['MARKING_PERIOD_ID'], 'MP' ) == 'FY' )
								{
									$columns['MP'] = 'FY';
								}
								elseif ( GetMP( $columns['MARKING_PERIOD_ID'], 'MP' ) == 'SEM' )
								{
									$columns['MP'] = 'SEM';
								}
								else
								{
									$columns['MP'] = 'QTR';
								}
							}
						}

						//FJ multiple school period for a course period

						if ( $table_name == 'course_period_school_periods' )
						{
							if ( ! empty( $columns['PERIOD_ID'] )
								&& in_array( $columns['PERIOD_ID'], $temp_PERIOD_ID ) ) //prevent repeat periods
							{
								continue;
							}

							$title_add = CoursePeriodSchoolPeriodsTitlePartGenerate(
								$id,
								$_REQUEST['course_period_id'],
								$columns
							);

							$current_cp = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,SHORT_NAME,TEACHER_ID,CREDITS
								FROM course_periods
								WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'" );

							$base_title = mb_substr(
								$current_cp[1]['TITLE'],
								mb_strpos(
									$current_cp[1]['TITLE'],
									( GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'MP' ) != 'FY' ?
										GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'SHORT_NAME' ) :
										DBEscapeString( $current_cp[1]['SHORT_NAME'] ) )
								)
							);

							$title = $title_add . $base_title;

							DBUpdate(
								'course_periods',
								[ 'TITLE' => DBEscapeString( $title ) ],
								[ 'COURSE_PERIOD_ID' => (int) $_REQUEST['course_period_id'] ]
							);

							if ( empty( $columns['DAYS'] ) ) //delete school period
							{
								DBQuery( "DELETE FROM course_period_school_periods
									WHERE COURSE_PERIOD_SCHOOL_PERIODS_ID='" . (int) $id . "'" );

								break; //no update
							}
							elseif ( ! empty( $columns['PERIOD_ID'] ) )
							{
								$temp_PERIOD_ID[] = $columns['PERIOD_ID'];
							}
						}

						DBUpdate(
							$table_name,
							$update_columns + $columns,
							[ $where[$table_name] => (int) $id ]
						);

						if ( $table_name === 'course_subjects' )
						{
							// Hook.
							do_action( 'Scheduling/Courses.php|update_course_subject' );
						}
						elseif ( $table_name === 'courses' )
						{
							// Hook.
							do_action( 'Scheduling/Courses.php|update_course' );
						}
						elseif ( $table_name === 'course_periods' )
						{
							if ( isset( $columns['MARKING_PERIOD_ID'] )
								&& $current_cp[1]['MARKING_PERIOD_ID'] !== $columns['MARKING_PERIOD_ID'] )
							{
								// Update schedules marking period too.
								CoursePeriodUpdateMP( $id, $columns['MARKING_PERIOD_ID'] );
							}

							if ( isset( $columns['TEACHER_ID'] )
								&& $current_cp[1]['TEACHER_ID'] !== $columns['TEACHER_ID'] )
							{
								// Update attendance_completed + grades_completed too.
								CoursePeriodUpdateTeacher(
									$id,
									$current_cp[1]['TEACHER_ID'],
									$columns['TEACHER_ID']
								);
							}

							if ( isset( $columns['CREDITS'] )
								&& $current_cp[1]['CREDITS'] !== $columns['CREDITS'] )
							{
								// Update student_report_card_grades.
								CoursePeriodUpdateCredits(
									$id,
									$current_cp[1]['CREDITS'],
									$columns['CREDITS']
								);
							}

							// Hook.
							do_action( 'Scheduling/Courses.php|update_course_period' );
						}
					}
					else
					{
						if ( $table_name == 'course_subjects' )
						{
							$insert_columns = [
								'SYEAR' => UserSyear(),
								'SCHOOL_ID' => UserSchool(),
							];
						}
						elseif ( $table_name == 'courses' )
						{
							$insert_columns = [
								'SYEAR' => UserSyear(),
								'SCHOOL_ID' => UserSchool(),
								'SUBJECT_ID' => (int) $_REQUEST['subject_id'],
							];
						}
						elseif ( $table_name == 'course_periods' )
						{
							if ( isset( $columns['MARKING_PERIOD_ID'] ) )
							{
								if ( GetMP( $columns['MARKING_PERIOD_ID'], 'MP' ) == 'FY' )
								{
									$columns['MP'] = 'FY';
								}
								elseif ( GetMP( $columns['MARKING_PERIOD_ID'], 'MP' ) == 'SEM' )
								{
									$columns['MP'] = 'SEM';
								}
								else
								{
									$columns['MP'] = 'QTR';
								}
							}

							$base_title = CoursePeriodTitleGenerate( 0, $columns );

							$insert_columns = [
								'SYEAR' => UserSyear(),
								'SCHOOL_ID' => UserSchool(),
								'COURSE_ID' => (int) $_REQUEST['course_id'],
								'TITLE' => DBEscapeString( $base_title ),
								'FILLED_SEATS' => '0',
							];
						}

						//FJ multiple school period for a course period
						elseif ( $table_name == 'course_period_school_periods' )
						{
							// Add new school period to existing course period

							if ( isset( $columns['PERIOD_ID'] ) && empty( $columns['PERIOD_ID'] ) )
							{
								continue;
							}

							$other_school_p = DBGet( "SELECT PERIOD_ID,DAYS
								FROM course_period_school_periods
								WHERE " . $where['course_periods'] . "='" . (int) $_REQUEST['course_period_id'] . "'", [], [ 'PERIOD_ID' ] );

							if ( in_array( $columns['PERIOD_ID'], $temp_PERIOD_ID ) || in_array( $columns['PERIOD_ID'], array_keys( $other_school_p ) ) )
							{
								continue;
							}

							$temp_PERIOD_ID[] = $columns['PERIOD_ID'];

							$title_add = CoursePeriodSchoolPeriodsTitlePartGenerate(
								0,
								$_REQUEST['course_period_id'],
								$columns
							);

							$current_cp = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,SHORT_NAME,TEACHER_ID,CREDITS
								FROM course_periods
								WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'" );

							$base_title = mb_substr(
								$current_cp[1]['TITLE'],
								mb_strpos(
									$current_cp[1]['TITLE'],
									( GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'MP' ) != 'FY' ?
										GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'SHORT_NAME' ) :
										$current_cp[1]['SHORT_NAME'] )
								)
							);

							$title = $title_add . $base_title;

							DBUpdate(
								'course_periods',
								[ 'TITLE' => DBEscapeString( $title ) ],
								[ 'COURSE_PERIOD_ID' => (int) $_REQUEST['course_period_id'] ]
							);

							$insert_columns = [ 'COURSE_PERIOD_ID' => (int) $_REQUEST['course_period_id'] ];
						}

						$id = DBInsert(
							$table_name,
							$insert_columns + $columns,
							'id'
						);

						if ( $id )
						{
							if ( $table_name == 'course_subjects' )
							{
								$_REQUEST['subject_id'] = $id;

								// Hook.
								do_action( 'Scheduling/Courses.php|create_course_subject' );
							}
							elseif ( $table_name == 'courses' )
							{
								$_REQUEST['course_id'] = $id;

								// Hook.
								do_action( 'Scheduling/Courses.php|create_course' );
							}
							elseif ( $table_name == 'course_periods' )
							{
								$_REQUEST['course_period_id'] = $id;

								if ( ! isset( $columns['PARENT_ID'] ) )
								{
									$columns['PARENT_ID'] = $id;

									DBQuery( "UPDATE course_periods
										SET PARENT_ID='" . (int) $id . "'
										WHERE COURSE_PERIOD_ID='" . (int) $id . "'" );
								}

								// Hook.
								do_action( 'Scheduling/Courses.php|create_course_period' );
							}
						}
					}
				}
				else
				{
					$error[] = _( 'Please fill in the required fields' );

					if ( $table_name == 'course_periods' )
					{
						break 2; // Skip course_period_school_periods
					}
				}
			}
			else
			{
				$error[] = _( 'Please enter valid Numeric data.' );

				if ( $table_name == 'course_periods' )
				{
					break 2; // Skip course_period_school_periods
				}
			}
		}
	}

	// Unset tables & redirect URL.
	RedirectURL( [ 'tables' ] );
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	$delete_sql = [];

	if ( $_REQUEST['course_period_id'] )
	{
		$table = _( 'Course Period' );

		$delete_sql = CoursePeriodDeleteSQL( $_REQUEST['course_period_id'] );

		$unset_get = 'course_period_id';
	}
	elseif ( $_REQUEST['course_id'] )
	{
		$table = _( 'Course' );

		$delete_sql = CourseDeleteSQL( $_REQUEST['course_id'] );

		$unset_get = 'course_id';
	}
	elseif ( $_REQUEST['subject_id'] )
	{
		$table = _( 'Subject' );

		$delete_sql = "DELETE FROM course_subjects
			WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "';";

		$unset_get = 'subject_id';
	}

	if ( DeletePrompt( $table ) )
	{
		DBQuery( $delete_sql );

		if ( $_REQUEST['course_period_id'] )
		{
			// Hook.
			do_action( 'Scheduling/Courses.php|delete_course_period' );
		}
		elseif ( $_REQUEST['subject_id'] )
		{
			// Hook.
			do_action( 'Scheduling/Courses.php|delete_course_subject' );
		}
		elseif ( $_REQUEST['course_id'] )
		{
			// Hook.
			do_action( 'Scheduling/Courses.php|delete_course' );
		}

		// Unset modfunc & ID redirect URL.
		RedirectURL( [ 'modfunc', $unset_get ] );
	}
}

if (  ( ! $_REQUEST['modfunc']
	|| $_REQUEST['modfunc'] === 'choose_course' )
	&& empty( $_REQUEST['course_modfunc'] ) )
{
	// Check subject ID is valid for current school & syear!

	if ( $_REQUEST['modfunc'] !== 'choose_course'
		&& $_REQUEST['subject_id']
		&& $_REQUEST['subject_id'] !== 'new' )
	{
		$subject_RET = DBGet( "SELECT SUBJECT_ID
			FROM course_subjects
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'" );

		if ( ! $subject_RET )
		{
			// Unset subject, course & course period IDs & redirect URL.
			RedirectURL( [
				'subject_id',
				'course_id',
				'course_period_id',
			] );
		}
	}

	// FJ fix SQL bug invalid sort order
	echo ErrorMessage( $error );

	$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
		FROM course_subjects
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . ( $_REQUEST['modfunc'] == 'choose_course' && $_REQUEST['last_year'] == 'true' ?
		UserSyear() - 1 :
		UserSyear() ) . "' ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	if ( $_REQUEST['modfunc'] !== 'choose_course' )
	{
		$delete_button = '';

		if ( User( 'PROFILE' ) === 'admin'
			&& AllowEdit() )
		{
			$can_delete = false;

			if ( $_REQUEST['course_period_id'] )
			{
				if ( $_REQUEST['course_period_id'] !== 'new' )
				{
					$has_student_enrolled = DBGetOne( "SELECT 1
						FROM schedule
						WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'
						AND SYEAR='" . UserSyear() . "'
						AND SCHOOL_ID='" . UserSchool() . "'" );

					if ( ! $has_student_enrolled )
					{
						// Can't delete Course Period if has Students enrolled, Eligibility, Attendance, Grades, etc.
						$can_delete = DBTransDryRun( CoursePeriodDeleteSQL( $_REQUEST['course_period_id'] ) );
					}
				}
			}
			elseif ( $_REQUEST['course_id'] )
			{
				if ( $_REQUEST['course_id'] !== 'new' )
				{
					$has_course_periods = DBGetOne( "SELECT 1
						FROM course_periods
						WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'" );

					if ( ! $has_course_periods )
					{
						// Can't delete Course if has Course Periods, etc.
						$can_delete = DBTransDryRun( CourseDeleteSQL( $_REQUEST['course_id'] ) );
					}
				}
			}
			elseif ( $_REQUEST['subject_id']
				&& $_REQUEST['subject_id'] !== 'new' )
			{
				$has_courses = DBGetOne( "SELECT 1
					FROM courses
					WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'" );

				if ( ! $has_courses )
				{
					// Can't delete Subject if has Courses.
					$can_delete = true;
				}
			}

			if ( $can_delete )
			{
				$delete_url = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] .
					'&modfunc=delete&subject_id=' . $_REQUEST['subject_id'] .
					'&course_id=' . $_REQUEST['course_id'] .
					'&course_period_id=' . $_REQUEST['course_period_id'] );

				$delete_button = '<input type="button" value="' . AttrEscape( _( 'Delete' ) ) .
					'" onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $delete_url ) . ');' ) . '" />';
			}
		}

		$header = '';

		if ( $_REQUEST['course_period_id']
			&& $_REQUEST['course_period_id'] !== 'new' )
		{
			$RET = DBGet( "SELECT PARENT_ID,TITLE,SHORT_NAME,MP,MARKING_PERIOD_ID,TEACHER_ID,
				SECONDARY_TEACHER_ID,CALENDAR_ID,ROOM,TOTAL_SEATS,DOES_ATTENDANCE,GRADE_SCALE_ID,
				DOES_HONOR_ROLL,DOES_CLASS_RANK,GENDER_RESTRICTION,
				HOUSE_RESTRICTION,CREDITS,DOES_BREAKOFF
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'" );

			if ( ! $RET )
			{
				// Unset subject, course & course period IDs & redirect URL.
				RedirectURL( [ 'subject_id', 'course_id', 'course_period_id', 'course_marking_period_id' ] );
			}
		}

		// ADDING & EDITING FORM

		if ( $_REQUEST['course_period_id'] )
		{
			if ( $_REQUEST['course_period_id'] !== 'new' )
			{
				$RET = $RET[1];

				$title = $RET['TITLE'];

				$RET2 = DBGet( "SELECT COURSE_PERIOD_SCHOOL_PERIODS_ID,PERIOD_ID,DAYS
					FROM course_period_school_periods
					WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'
					ORDER BY COURSE_PERIOD_SCHOOL_PERIODS_ID" );

				$new = false;

				// Check for Course Period Teacher conflict.
				if ( AllowEdit()
					&& CoursePeriodTeacherConflictCheck( $RET['TEACHER_ID'], $_REQUEST['course_period_id'] ) )
				{
					$warning[] = _( 'Conflict: Teacher already scheduled for this period.' );
				}

				// Check for Course Period Secondary Teacher conflict.
				if ( AllowEdit()
					&& $RET['SECONDARY_TEACHER_ID']
					&& CoursePeriodTeacherConflictCheck( $RET['SECONDARY_TEACHER_ID'], $_REQUEST['course_period_id'] ) )
				{
					$warning[] = _( 'Conflict: Secondary Teacher already scheduled for this period.' );
				}

				echo ErrorMessage( $warning, 'warning' );
			}
			else
			{
				$RET = DBGet( "SELECT TITLE
					FROM courses
					WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'" );

				$title = $RET[1]['TITLE'] . ' - ' . _( 'New Course Period' );

				$RET = [];

				$new = true;
			}

			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&subject_id=' . $_REQUEST['subject_id'] .
				'&course_id=' . $_REQUEST['course_id'] .
				'&course_period_id=' . $_REQUEST['course_period_id']  ) . '" method="POST">';

			DrawHeader( $title, $delete_button . SubmitButton() );

			// Hook.
			do_action( 'Scheduling/Courses.php|header' );

			$header = '<table class="width-100p valign-top fixed-col" id="coursesTable">';
			$header .= '<tr class="st">';

			// FJ Moodle integrator.
			$header .= '<td>' . TextInput(
				issetVal( $RET['SHORT_NAME'], '' ),
				'tables[course_periods][' . $_REQUEST['course_period_id'] . '][SHORT_NAME]',
				_( 'Short Name' ),
				'required maxlength=25',
				empty( $_REQUEST['moodle_create_course_period'] )
			) . '</td>';

			// @since 9.2.1 SQL replace use of STRPOS() with LIKE, compatible with MySQL.
			$teachers_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
				FROM staff
				WHERE (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
				AND SYEAR='" . UserSyear() . "'
				AND PROFILE='teacher'
				ORDER BY FULL_NAME" );

			$teachers = [];

			foreach ( (array) $teachers_RET as $teacher )
			{
				if ( ! empty( $_REQUEST['moodle_create_course_period'] )
					&& ! MoodleXRosarioGet( 'staff_id', $teacher['STAFF_ID'] ) )
				{
					// @since 5.8 Only display Teachers in Moodle when creating a Course Period in Moodle.
					continue;
				}

				$teachers[$teacher['STAFF_ID']] = GetTeacher( $teacher['STAFF_ID'] );
			}

			// FJ Moodle integrator.
			$header .= '<td>' . SelectInput(
				issetVal( $RET['TEACHER_ID'], '' ),
				'tables[course_periods][' . $_REQUEST['course_period_id'] . '][TEACHER_ID]',
				_( 'Teacher' ),
				$teachers,
				empty( $_REQUEST['moodle_create_course_period'] ) ? 'N/A' : false,
				'required',
				empty( $_REQUEST['moodle_create_course_period'] )
			) . '</td>';

			// @since 6.8 Add Secondary Teacher.
			$secondary_teachers = $teachers;

			if ( ! empty( $RET['TEACHER_ID'] )
				&& isset( $teachers[$RET['TEACHER_ID']] ) )
			{
				// Remove Main Teacher from Secondary Teacher options.
				unset( $secondary_teachers[$RET['TEACHER_ID']] );
			}

			if ( AllowEdit() || $RET['SECONDARY_TEACHER_ID'] )
			{
				$header .= '<td>' . SelectInput(
					issetVal( $RET['SECONDARY_TEACHER_ID'], '' ),
					'tables[course_periods][' . $_REQUEST['course_period_id'] . '][SECONDARY_TEACHER_ID]',
					_( 'Secondary Teacher' ),
					$secondary_teachers
				) . '</td>';
			}

			$header .= '</tr><tr><td colspan="3"><hr></td></tr>';

			$header .= '<tr class="st"><td>' . TextInput(
				issetVal( $RET['ROOM'], '' ),
				'tables[course_periods][' . $_REQUEST['course_period_id'] . '][ROOM]',
				_( 'Room' ),
				'maxlength=10' .
					( $_REQUEST['course_period_id'] === 'new' ? ' size="6"' : '' )
			) . '</td>';

			$periods_RET = DBGet( "SELECT PERIOD_ID,TITLE
				FROM school_periods
				WHERE SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

			foreach ( (array) $periods_RET as $period )
			{
				$periods[$period['PERIOD_ID']] = $period['TITLE'];
			}

			//$header .= '<td>' . SelectInput($RET['MP'],'tables[course_periods]['.$_REQUEST['course_period_id'].'][MP]','Length',array('FY' => 'Full Year','SEM' => 'Semester','QTR' => 'Marking Period')) . '</td>';

			// @since 11.1 SQL Use GetFullYearMP() & GetChildrenMP() functions to limit Marking Periods
			$fy_and_children_mp = "'" . GetFullYearMP() . "'";

			if ( GetChildrenMP( 'FY' ) )
			{
				$fy_and_children_mp .= "," . GetChildrenMP( 'FY' );
			}

			$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,SHORT_NAME," .
				db_case( [ 'MP', "'FY'", "'0'", "'SEM'", "'1'", "'QTR'", "'2'" ] ) . " AS TBL
				FROM school_marking_periods
				WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
				AND MARKING_PERIOD_ID IN(" . $fy_and_children_mp . ")
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				ORDER BY TBL,SORT_ORDER IS NULL,SORT_ORDER,START_DATE" );

			unset( $options );

			foreach ( (array) $mp_RET as $mp )
			{
				$options[$mp['MARKING_PERIOD_ID']] = $mp['SHORT_NAME'];
			}

			if ( AllowEdit() )
			{
				$header .= '<td>' . SelectInput(
					issetVal( $RET['MARKING_PERIOD_ID'], '' ),
					'tables[course_periods][' . $_REQUEST['course_period_id'] . '][MARKING_PERIOD_ID]',
					_( 'Marking Period' ),
					$options,
					false,
					'required',
					empty( $_REQUEST['moodle_create_course_period'] )
				) . '</td>';
			}
			else
			{
				// Non editing users: Show Full MP Title.
				$header .= '<td>' . NoInput(
					GetMP( $RET['MARKING_PERIOD_ID'] ),
					_( 'Marking Period' )
				) . '</td>';
			}

			$header .= '<td>' . TextInput(
				issetVal( $RET['TOTAL_SEATS'], '' ),
				'tables[course_periods][' . $_REQUEST['course_period_id'] . '][TOTAL_SEATS]',
				_( 'Seats' ),
				' type="number" step="1" min="0" max="9999"'
			) . '</td>';

			$header .= '</tr><tr><td colspan="3"><hr></td></tr>';

			$days = [ 'M', 'T', 'W', 'H', 'F', 'S', 'U' ];

			// FJ days numbered.

			if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
			{
				$days = array_slice( $days, 0, SchoolInfo( 'NUMBER_DAYS_ROTATION' ) );
			}

			// FJ multiple school periods for a course period.
			$i = 0;

			$not_really_new = false;

			do
			{
				$i++;

				//FJ add new school period to existing course period.

				if ( ! $new && $i > count( (array) $RET2 ) )
				{
					$new = true;
					$not_really_new = true;
					unset( $school_period );
				}

				if ( ! $new )
				{
					$school_period = $RET2[$i];
				}
				else
				{
					$school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] = 'new' . $i;
				}

				$header .= '<tr id="schoolPeriod' . $i . '" class="st">';

				//FJ existing school period not modifiable

				if ( ! $new )
				{
					$header .= '<td>' . NoInput(
						$periods[$school_period['PERIOD_ID']],
						_( 'Period' )
					) . '</td>';
				}
				else
				{
					$header .= '<td>' . SelectInput(
						issetVal( $school_period['PERIOD_ID'], '' ),
						'tables[course_period_school_periods][' . $school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] . '][PERIOD_ID]',
						_( 'Period' ),
						$periods,
						( $_REQUEST['course_period_id'] === 'new' ? false : 'N/A' )
					) . '</td>';
				}

				$header .= '<td colspan="2">';

				$days_html = '<table class="cellspacing-0"><tr class="st">';

				$day_titles = [];

				foreach ( (array) $days as $day )
				{
					if (  ( $new && $day != 'S' && $day != 'U' )
						|| ( ! empty( $school_period['DAYS'] )
							&& mb_strpos( $school_period['DAYS'], $day ) !== false ) )
					{
						$value = 'Y';
					}
					else
					{
						$value = '';
					}

					$days_html .= '<td>' . CheckboxInput(
						$value,
						'tables[course_period_school_periods][' . $school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] . '][DAYS][' . $day . ']',
						$days_convert[$day],
						'',
						true,
						button( 'check' ),
						button( 'x' )
					) . '&nbsp;</td>';

					if ( $value )
					{
						$day_titles[] = mb_substr( $days_convert[$day], 0, 3 ) . '.';
					}
				}

				$days_html .= '</tr></table>';

				$days_title_nobr = FormatInputTitle( _( 'Meeting Days' ), '', empty( $school_period['DAYS'] ), '' );

				if ( $new == false )
				{
					// Fix Delete Period when days unchecked.
					$days_html = '<input type="hidden" value="" name="tables[course_period_school_periods][' . $school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] . '][DAYS][' . $day . ']" />' . $days_html;

					$header .= InputDivOnclick(
						'days' . $i,
						$days_html . $days_title_nobr,
						implode( ' ', $day_titles ),
						FormatInputTitle( _( 'Meeting Days' ), '', empty( $school_period['DAYS'] ) )
					);
				}
				else
				{
					$header .= $days_html . $days_title_nobr;
				}

				if ( $not_really_new )
				{
					$new = false;
				}

				if ( $new )
				{
					break;
				}

				if ( ! AllowEdit()
					&& $i === count( (array) $RET2 ) )
				{
					break;
				}
			} while ( $i <= count( (array) $RET2 ) );

			if ( AllowEdit() )
			{
				$header .= '<tr class="st"><td colspan="3">
					<a href="#" onclick="' .
				( $new ?
					'newSchoolPeriod();' :
					'document.getElementById(\'schoolPeriod\'+' . $i . ').style.display=\'table-row\'; this.style.display=\'none\';' ) .
				' return false;">' .
				button( 'add' ) . ' ' . _( 'New Period' ) . '</a>
					</td></tr>';

				if ( ! $new )
				{
					$header .= '<script>document.getElementById(\'schoolPeriod\'+' . $i . ').style.display = "none";</script>';
				}

				?>
				<script>
					var nbSchoolPeriods = <?php echo $i; ?>;
					function newSchoolPeriod()
					{
						var table = document.getElementById('coursesTable');
						row = table.insertRow(4+nbSchoolPeriods);
						// insert table cells to the new row
						var tr = document.getElementById('schoolPeriod'+nbSchoolPeriods);
						row.setAttribute('id', 'schoolPeriod'+(nbSchoolPeriods+1));
						row.setAttribute('class', 'st');
						for (i = 0; i < 2; i++) {
							createCell(row.insertCell(i), tr, i, nbSchoolPeriods+1);
						}
						nbSchoolPeriods ++;
					}
					// fill the cells
					function createCell(cell, tr, i, newId) {
						cell.innerHTML = tr.cells[i].innerHTML;
						if (i == 1) cell.setAttribute('colspan', '2');
						reg = new RegExp('new' + (newId-1),'g'); //g for global string
						cell.innerHTML = cell.innerHTML.replace(reg, 'new'+newId);
						// remove required attribute
						cell.innerHTML = cell.innerHTML.replace( 'required', '' );
					}
				</script>
				<?php
			}

			$cp_inputs = CoursePeriodOptionInputs(
				$RET,
				'tables[course_periods][' . $_REQUEST['course_period_id'] . ']',
				$new
			);

			$header .= '<tr><td colspan="3"><hr></td></tr>';

			// Takes Attendance.
			$header .= '<tr class="st"><td>' . $cp_inputs[1] . '</td>';

			if ( AllowEdit() || $RET['DOES_ATTENDANCE'] )
			{
				// Hide Calendar if CP "No Attendance".
				// Calendar.
				$header .= '<td>' . $cp_inputs[0] . '</td>';
			}

			$header .= '</tr><tr><td colspan="3"><hr></td></tr>';

			// Grading Scale.
			$header .= '<tr class="st"><td>' . $cp_inputs[2] . '</td>';

			if ( AllowEdit() || $RET['GRADE_SCALE_ID'] )
			{
				// Hide Credits, Affects Class Rank & Affects Honor Roll if CP "Not Graded".
				if ( AllowEdit() || User( 'PROFILE' ) === 'teacher' )
				{
					// Show only to Teachers and Admins.
					// Allow Teacher Grade Scale.
					$header .= '<td colspan="2">' . $cp_inputs[3] . '</td>';
				}
				else
				{
					$header .= '<td colspan="2">&nbsp;</td>';
				}

				// Credits.
				$header .= '</tr><tr class="st"><td>' . $cp_inputs[4] . '</td>';

				// Affects Class Rank.
				$header .= '<td>' . $cp_inputs[5] . '</td>';

				// Affects Honor Roll.
				$header .= '<td>' . $cp_inputs[6] . '</td>';
			}

			$header .= '</tr>';

			if ( AllowEdit()
				|| $RET['GENDER_RESTRICTION'] !== 'N'
				|| $RET['PARENT_ID'] !== $_REQUEST['course_period_id'] )
			{
				// Hide hr separator from non editing users if no Gender Restriction and no Parent CP.
				$header .= '<tr><td colspan="3"><hr></td></tr><tr class="st">';
			}

			if ( AllowEdit()
				|| $RET['GENDER_RESTRICTION'] !== 'N' )
			{
				// Hide from non editing users if no Gender Restriction set.
				// Gender Restriction.
				$header .= '<td>' . $cp_inputs[7] . '</td>';
			}

			if ( AllowEdit()
				|| ( $RET['PARENT_ID'] && $RET['PARENT_ID'] !== $_REQUEST['course_period_id'] ) )
			{
				// Hide from non editing users if no Parent Course Period set.
				$parent = '';

				if ( $_REQUEST['course_period_id'] !== 'new'
					&& $RET['PARENT_ID']
					&& $RET['PARENT_ID'] !== $_REQUEST['course_period_id'] )
				{
					$parent = DBGet( "SELECT cp.TITLE as CP_TITLE,c.TITLE AS C_TITLE
						FROM course_periods cp,courses c
						WHERE c.COURSE_ID=cp.COURSE_ID
						AND cp.COURSE_PERIOD_ID='" . (int) $RET['PARENT_ID'] . "'" );

					$parent = $parent[1]['C_TITLE'] . ': ' . $parent[1]['CP_TITLE'];
				}
				elseif ( $_REQUEST['course_period_id'] !== 'new' )
				{
					$children = DBGet( "SELECT COURSE_PERIOD_ID
						FROM course_periods
						WHERE PARENT_ID='" . (int) $_REQUEST['course_period_id'] . "'
						AND COURSE_PERIOD_ID!='" . (int) $_REQUEST['course_period_id'] . "'" );

					$parent = ! empty( $children ) ? _( 'N/A' ) : _( 'None' );
				}

				$popup_link = '';

				if ( $parent != _( 'N/A' ) && AllowEdit() )
				{
					$popup_url = URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=choose_course' );

					$popup_link = '<a href="#" onclick="' . AttrEscape( 'popups.open(
						' . json_encode( $popup_url ) . '
						); return false;' ) . '">' . _( 'Choose' ) . '</a><br />';
				}

				// Parent Course Period.
				$header .= '<td colspan="2"><div id="course_div">' . $parent . '</div> ' .
				$popup_link .
				'<span class="legend-gray">' . _( 'Parent Course Period' ) . '</span></td>';
			}

			if ( AllowEdit()
				|| $RET['GENDER_RESTRICTION'] !== 'N'
				|| $RET['PARENT_ID'] !== $_REQUEST['course_period_id'] )
			{
				$header .= '</tr>';
			}

			$header .= '</table>';
		}
		elseif ( $_REQUEST['course_id'] )
		{
			if ( $_REQUEST['course_id'] !== 'new' )
			{
				$RET = DBGet( "SELECT TITLE,SHORT_NAME,GRADE_LEVEL,CREDIT_HOURS,DESCRIPTION
					FROM courses
					WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "'" );

				$RET = $RET[1];

				$title = $RET['TITLE'];
			}
			else
			{
				$RET = DBGet( "SELECT TITLE
					FROM course_subjects
					WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'" );

				$title = $RET[1]['TITLE'] . ' - ' . _( 'New Course' );

				$RET = [];
			}

			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id']  ) . '" method="POST">';

			DrawHeader( $title, $delete_button . SubmitButton() );

			$header = '<table class="width-100p valign-top"><tr class="st">';

			$header .= '<td>' . TextInput(
				issetVal( $RET['TITLE'] ),
				'tables[courses][' . $_REQUEST['course_id'] . '][TITLE]',
				_( 'Title' ),
				'required maxlength=100 size=20'
			) . '</td>';

			$header .= '<td>' . TextInput(
				issetVal( $RET['SHORT_NAME'] ),
				'tables[courses][' . $_REQUEST['course_id'] . '][SHORT_NAME]',
				_( 'Short Name' ),
				'maxlength=25'
			) . '</td>';

			//FJ add Credit Hours to Courses
			$header .= '<td>' . TextInput(
				issetVal( $RET['CREDIT_HOURS'] ),
				'tables[courses][' . $_REQUEST['course_id'] . '][CREDIT_HOURS]',
				_( 'Credit Hours' ),
				' type="number" step="any" min="0" max="9999"'
			) . '</td></tr>';

			// Add Description (TinyMCE input) to Course.
			$header .= '<tr class="st"><td colspan="3">' . TinyMCEInput(
				issetVal( $RET['DESCRIPTION'] ),
				'tables[courses][' . $_REQUEST['course_id'] . '][DESCRIPTION]',
				_( 'Description' )
			) . '</td></tr>';

			//FJ SQL error column "subject_id" specified more than once
			/*if ( $_REQUEST['modfunc']!='choose_course')
			{
			foreach ( (array) $subjects_RET as $type)
			$options[$type['SUBJECT_ID']] = $type['TITLE'];

			$header .= '<td>' . SelectInput($RET['SUBJECT_ID']?$RET['SUBJECT_ID']:$_REQUEST['subject_id'],'tables[courses]['.$_REQUEST['course_id'].'][SUBJECT_ID]',_('Subject'),$options,false) . '</td>';
			}*/
			$header .= '</tr></table>';
		}
		elseif ( $_REQUEST['subject_id'] )
		{
			if ( $_REQUEST['subject_id'] !== 'new' )
			{
				$RET = DBGet( "SELECT TITLE,SORT_ORDER
					FROM course_subjects
					WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'" );

				$RET = $RET[1];

				$title = $RET['TITLE'];
			}
			else
			{
				$title = _( 'New Subject' );
			}

			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id']  ) . '" method="POST">';

			DrawHeader( $title, $delete_button . SubmitButton() );

			$header = '<table class="width-100p valign-top fixed-col"><tr class="st">';

			$header .= '<td>' . TextInput(
				issetVal( $RET['TITLE'] ),
				'tables[course_subjects][' . $_REQUEST['subject_id'] . '][TITLE]',
				_( 'Title' ),
				'required maxlength=100 size=20'
			) . '</td>';

			if ( AllowEdit() )
			{
				// Hide Sort Order from non editing users.
				$header .= '<td>' . TextInput(
					issetVal( $RET['SORT_ORDER'] ),
					'tables[course_subjects][' . $_REQUEST['subject_id'] . '][SORT_ORDER]',
					_( 'Sort Order' ),
					' type="number" min="-9999" max="9999"'
				) . '</td>';
			}

			$header .= '</tr></table>';
		}

		DrawHeader( $header );

		echo '</form>';
	}

	// DISPLAY THE MENU.
	if ( $_REQUEST['modfunc'] === 'choose_course' )
	{
		$choose_a_header_html = _( 'Choose a' ) . ' ' .
			( $_REQUEST['subject_id'] ? ( $_REQUEST['course_id'] ?
				( $_REQUEST['last_year'] == 'true' ? _( 'Last Year Course Period' ) : _( 'Course Period' ) ) :
				( $_REQUEST['last_year'] == 'true' ? _( 'Last Year Course' ) : _( 'Course' ) ) ) :
			( $_REQUEST['last_year'] == 'true' ? _( 'Last Year Subject' ) : _( 'Subject' ) ) );

		if ( $_REQUEST['modname'] === 'Scheduling/Schedule.php' )
		{
			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

			DrawHeader(
				$choose_a_header_html,
				_( 'Enrollment Date' ) . ' ' . PrepareDate( $date, '_date', false, [ 'submit' => true ] ),
				''
			);

			DrawHeader( CheckBoxOnclick(
				'include_child_mps',
				_( 'Offer Enrollment in Child Marking Periods' )
			) );

			echo '</form>';
		}
		else
		{
			DrawHeader( $choose_a_header_html );
		}
	}
	elseif ( empty( $_REQUEST['subject_id'] ) )
	{
		DrawHeader( _( 'Courses' ) );
	}

	DrawHeader(
		'',
		'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] .
		'&course_modfunc=search&last_year=' . $_REQUEST['last_year'] .
		( $_REQUEST['modfunc'] == 'choose_course' && $_REQUEST['modname'] == 'Scheduling/Schedule.php' ?
			'&include_child_mps=' . issetVal( $_REQUEST['include_child_mps'], '' ) .
			'&year_date=' . $_REQUEST['year_date'] . '&month_date=' . $_REQUEST['month_date'] .
			'&day_date=' . $_REQUEST['day_date'] :
			'' ) ) .
		'">' . _( 'Search' ) . '</a>&nbsp;'
	);

	if ( ! empty( $subjects_RET )
		&& $_REQUEST['subject_id'] )
	{
		foreach ( (array) $subjects_RET as $key => $value )
		{
			if ( $value['SUBJECT_ID'] === $_REQUEST['subject_id'] )
			{
				$subjects_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$columns = [ 'TITLE' => _( 'Subject' ) ];
	$link = [];
	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
	$link['TITLE']['variables'] = [ 'subject_id' => 'SUBJECT_ID' ];

	if ( $_REQUEST['modfunc'] === 'choose_course' )
	{
		$link['TITLE']['link'] .= '&modfunc=' . $_REQUEST['modfunc'] .
		'&last_year=' . $_REQUEST['last_year'] .
		( $_REQUEST['modname'] == 'Scheduling/Schedule.php' ?
			'&include_child_mps=' . $_REQUEST['include_child_mps'] . '&year_date=' . $_REQUEST['year_date'] . '&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'] :
			''
		);
	}
	else
	{
		$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=new';
	}

	echo '<div class="st">';

	ListOutput(
		$subjects_RET,
		$columns,
		'Subject',
		'Subjects',
		$link,
		[],
		$LO_options
	);

	echo '</div>';

	if ( $_REQUEST['subject_id']
		&& $_REQUEST['subject_id'] !== 'new' )
	{
		$courses_RET = DBGet( "SELECT COURSE_ID,TITLE
			FROM courses
			WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'
			ORDER BY TITLE" );

		if ( ! empty( $courses_RET )
			&& ! empty( $_REQUEST['course_id'] ) )
		{
			foreach ( (array) $courses_RET as $key => $value )
			{
				if ( $value['COURSE_ID'] === $_REQUEST['course_id'] )
				{
					$courses_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
				}
			}
		}

		$columns = [ 'TITLE' => _( 'Course' ) ];

		$link = [];

		$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'];

		$link['TITLE']['variables'] = [ 'course_id' => 'COURSE_ID' ];

		if ( $_REQUEST['modfunc'] === 'choose_course' )
		{
			$link['TITLE']['link'] .= '&modfunc=' . $_REQUEST['modfunc'] .
			'&last_year=' . $_REQUEST['last_year'] .
			( $_REQUEST['modname'] == 'Scheduling/Schedule.php' ?
				'&include_child_mps=' . $_REQUEST['include_child_mps'] . '&year_date=' . $_REQUEST['year_date'] . '&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'] :
				''
			);
		}
		else
		{
			$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=new';
		}

		echo '<div class="st">';

		ListOutput(
			$courses_RET,
			$columns,
			'Course',
			'Courses',
			$link,
			[],
			$LO_options
		);

		echo '</div>';

		if ( ! empty( $_REQUEST['course_id'] )
			&& $_REQUEST['course_id'] !== 'new' )
		{
			// FJ add Available Seats column to every choose course popup.

			if ( $_REQUEST['modname'] !== 'Scheduling/Schedule.php' )
			{
				$date = DBDate();
			}

			//FJ multiple school periods for a course period
			//$periods_RET = DBGet( "SELECT '".$_REQUEST['subject_id']."' AS SUBJECT_ID,COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,TOTAL_SEATS AS AVAILABLE_SEATS FROM course_periods cp WHERE COURSE_ID='".$_REQUEST['course_id']."' ".($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php'?" AND '".$date."'<=(SELECT END_DATE FROM school_marking_periods WHERE SYEAR=cp.SYEAR AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)":'')." ORDER BY (SELECT SORT_ORDER FROM school_periods WHERE PERIOD_ID=cp.PERIOD_ID),TITLE"));
			$periods_RET = DBGet( "SELECT '" . $_REQUEST['subject_id'] . "' AS SUBJECT_ID,
				COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,
				TOTAL_SEATS AS AVAILABLE_SEATS
				FROM course_periods cp
				WHERE COURSE_ID='" . (int) $_REQUEST['course_id'] . "' " .
				( $_REQUEST['modfunc'] === 'choose_course'
					&& $_REQUEST['modname'] === 'Scheduling/Schedule.php' ?
					" AND '" . $date . "'<=(SELECT END_DATE
						FROM school_marking_periods
						WHERE SYEAR=cp.SYEAR
						AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)" :
					'' ) . "
				ORDER BY SHORT_NAME,TITLE" );

			//if ( $_REQUEST['modname']=='Scheduling/Schedule.php')
			calcSeats1( $periods_RET, $date );

			if ( ! empty( $periods_RET )
				&& $_REQUEST['course_period_id'] )
			{
				foreach ( (array) $periods_RET as $key => $value )
				{
					if ( $value['COURSE_PERIOD_ID'] === $_REQUEST['course_period_id'] )
					{
						$periods_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
					}
				}
			}

			$columns = [ 'TITLE' => _( 'Course Period' ) ];

			$link = [];

			if ( $_REQUEST['modname'] !== 'Scheduling/Schedule.php'
				|| ( $_REQUEST['modname'] === 'Scheduling/Schedule.php'
					&& ! $_REQUEST['include_child_mps'] ) )
			{
				$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'];

				$link['TITLE']['variables'] = [ 'course_period_id' => 'COURSE_PERIOD_ID', 'course_marking_period_id' => 'MARKING_PERIOD_ID' ];

				if ( $_REQUEST['modfunc'] === 'choose_course' )
				{
					$link['TITLE']['link'] .= '&modfunc=' . $_REQUEST['modfunc'] . '&student_id=' . issetVal( $_REQUEST['student_id'], '' ) . '&last_year=' . $_REQUEST['last_year'];
				}
				else
				{
					$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&course_period_id=new';
				}
			}

			//if ( $_REQUEST['modname']=='Scheduling/Schedule.php')
			$columns += [
				'AVAILABLE_SEATS' => ( ! empty( $_REQUEST['include_child_mps'] ) ?
					_( 'MP' ) . '(' . _( 'Available Seats' ) . ')' :
					_( 'Available Seats' ) ),
			];

			echo '<div class="st">';

			ListOutput(
				$periods_RET,
				$columns,
				'Course Period',
				'Course Periods',
				$link,
				[],
				$LO_options
			);

			echo '</div>';
		}
	}
}

if ( $_REQUEST['modname'] === 'Scheduling/Courses.php'
	&& $_REQUEST['modfunc'] === 'choose_course'
	&& $_REQUEST['course_period_id'] )
{
	$course_title = DBGetOne( "SELECT TITLE
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['course_period_id'] . "'" );

	$html_to_escape = $course_title .
		'<input type="hidden" name="tables[parent_id]" value="' . AttrEscape( $_REQUEST['course_period_id'] ) . '" />';

	echo '<script>opener.document.getElementById("' .
	( $_REQUEST['last_year'] === 'true' ? 'ly_' : '' ) . 'course_div").innerHTML=' .
	json_encode( $html_to_escape ) . '; window.close();</script>';
}

/**
 * Calculate available seats for course period.
 *
 * @uses calcSeats0() to get filled seats.
 *
 * @param $periods
 * @param $date
 *
 * @return string Available seats - filled seats.
 */
function calcSeats1( &$periods, $date )
{
	require_once 'modules/Scheduling/includes/calcSeats0.fnc.php';

	foreach ( (array) $periods as $key => $period )
	{
		if ( ! empty( $_REQUEST['include_child_mps'] ) )
		{
			$mps = GetChildrenMP( $period['MP'], $period['MARKING_PERIOD_ID'] );

			if ( $period['MP'] == 'FY' || $period['MP'] == 'SEM' )
			{
				$mps = "'" . $period['MARKING_PERIOD_ID'] . "'" . ( $mps ? ',' . $mps : '' );
			}
		}
		else
		{
			$mps = "'" . $period['MARKING_PERIOD_ID'] . "'";
		}

		$periods[$key]['AVAILABLE_SEATS'] = '';

		foreach ( explode( ',', $mps ) as $mp )
		{
			$mp = trim( $mp, "'" );

			if ( GetMP( $mp, 'END_DATE' ) >= $date )
			{
				$link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] .
				'&subject_id=' . $period['SUBJECT_ID'] . '&course_id=' . $period['COURSE_ID'];

				$link .= '&last_year=' . issetVal( $_REQUEST['last_year'], '' ) .
					'&year_date=' . issetVal( $_REQUEST['year_date'], '' ) .
					'&month_date=' . issetVal( $_REQUEST['month_date'], '' ) .
					'&day_date=' . issetVal( $_REQUEST['day_date'], '' );

				$link .= '&course_period_id=' . $period['COURSE_PERIOD_ID'] . '&course_marking_period_id=' . $mp;

				if ( $period['AVAILABLE_SEATS'] )
				{
					$period['MARKING_PERIOD_ID'] = $mp;

					$filled_seats = calcSeats0( $period, $date );

					if ( $filled_seats != '' )
					{
						if ( ! empty( $_REQUEST['include_child_mps'] ) )
						{
							$periods[$key]['AVAILABLE_SEATS'] .= '<a href="' . URLEscape( $link ) . '">' .
							( GetMP( $mp, 'SHORT_NAME' ) ?
								GetMP( $mp, 'SHORT_NAME' ) :
								GetMP( $mp ) ) .
							'(' . ( $period['AVAILABLE_SEATS'] - $filled_seats ) . ')</a> | ';
						}
						else
						{
							$periods[$key]['AVAILABLE_SEATS'] = $period['AVAILABLE_SEATS'] - $filled_seats;
						}
					}
				}
				else
				{
					if ( ! empty( $_REQUEST['include_child_mps'] ) )
					{
						$periods[$key]['AVAILABLE_SEATS'] .= '<a href="' . URLEscape( $link ) . '">' .
						( GetMP( $mp, 'SHORT_NAME' ) ?
							GetMP( $mp, 'SHORT_NAME' ) :
							GetMP( $mp ) ) .
						'</a> | ';
					}
					else
					{
						$periods[$key]['AVAILABLE_SEATS'] = _( 'N/A' );
					}
				}
			}
		}

		if ( ! empty( $_REQUEST['include_child_mps'] ) )
		{
			$periods[$key]['AVAILABLE_SEATS'] = mb_substr( $periods[$key]['AVAILABLE_SEATS'], 0, -3 );
		}
	}
}

/**
 * Automatically update schedules marking period.
 *
 * On the condition scheduled marking period is of greater type
 * than the new course period marking period.
 * For example: FY to SEM.
 *
 * Local function.
 *
 * @since 3.7.1
 * @deprecated since 11.1 Move _updateSchedulesCPMP() to includes/Courses.fnc.php & rename CoursePeriodUpdateMP()
 *
 * @param  string $cp_id Course Period ID.
 * @param  string $mp_id Marking Period ID.
 * @return int    Number of schedules updated.
 */
function _updateSchedulesCPMP( $cp_id, $mp_id )
{
	return CoursePeriodUpdateMP( $cp_id, $mp_id );
}
