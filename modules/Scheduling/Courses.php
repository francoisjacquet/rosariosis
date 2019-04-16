<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

if ( ! isset( $_REQUEST['last_year'] ) )
{
	$_REQUEST['last_year'] = '';
}

if ( $_REQUEST['modfunc'] !== 'choose_course' )
{
	DrawHeader( ProgramTitle() );
}

//unset($_SESSION['_REQUEST_vars']['subject_id']);
//unset($_SESSION['_REQUEST_vars']['course_id']);
//unset($_SESSION['_REQUEST_vars']['course_period_id']);

// If only one subject, select it automatically -- works for Course Setup and Choose a Course.

if ( $_REQUEST['modfunc'] !== 'delete'
	&& ! $_REQUEST['subject_id'] )
{
	$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
		FROM COURSE_SUBJECTS
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

$LO_options = array(
	'save' => false,
	'search' => false,
	'responsive' => false,
);

if ( $_REQUEST['course_modfunc'] === 'search' )
{
	echo '<br />';

	PopTable( 'header', _( 'Search' ) );

	echo '<form name="search" action="Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=' . $_REQUEST['modfunc'] . '&course_modfunc=search&last_year=' .
		$_REQUEST['last_year'] . '" method="POST">';

	echo '<table><tr><td><input type="text" name="search_term" value="' .
	$_REQUEST['search_term'] . '" required autofocus /></td>
		<td>' . Buttons( _( 'Search' ) ) . '</td></tr></table>';

	if ( $_REQUEST['modfunc'] === 'choose_course'
		&& $_REQUEST['modname'] === 'Scheduling/Schedule.php' )
	{
		echo '<input type="hidden" name="include_child_mps" value="' .
			$_REQUEST['include_child_mps'] . '" />
			<input type="hidden" name="year_date" value="' . $_REQUEST['year_date'] . '" />
			<input type="hidden" name="month_date" value="' . $_REQUEST['month_date'] . '" />
			<input type="hidden" name="day_date" value="' . $_REQUEST['day_date'] . '" />';
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
			FROM COURSE_SUBJECTS
			WHERE (UPPER(TITLE) LIKE '%" . mb_strtoupper( $_REQUEST['search_term'] ) . "%'
			OR UPPER(SHORT_NAME)='" . mb_strtoupper( $_REQUEST['search_term'] ) . "')
			AND SYEAR='" . ( $_REQUEST['modfunc'] === 'choose_course'
			&& $_REQUEST['last_year'] === 'true' ?
			UserSyear() - 1 :
			UserSyear() ) . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER,TITLE" );

		$courses_RET = DBGet( "SELECT SUBJECT_ID,COURSE_ID,TITLE
			FROM COURSES
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
			FROM COURSE_PERIODS cp,COURSES c
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
					FROM SCHOOL_MARKING_PERIODS
					WHERE SYEAR=cp.SYEAR
					AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)" :
				'' ) . "
			ORDER BY cp.SHORT_NAME,TITLE" );

		//if ( $_REQUEST['modname']=='Scheduling/Schedule.php')
		calcSeats1( $periods_RET, $date );

		$link = array();

		$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=' . $_REQUEST['modfunc'] . '&last_year=' . $_REQUEST['last_year'];

		if ( $_REQUEST['modfunc'] === 'choose_course'
			&& $_REQUEST['modname'] === 'Scheduling/Schedule.php' )
		{
			$link['TITLE']['link'] .= '&include_child_mps=' . $_REQUEST['include_child_mps'] .
				'&year_date=' . $_REQUEST['year_date'] .
				'&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'];
		}

		$link['TITLE']['variables'] = array( 'subject_id' => 'SUBJECT_ID' );

		echo '<div class="st">';

		ListOutput(
			$subjects_RET,
			array( 'TITLE' => _( 'Subject' ) ),
			'Subject',
			'Subjects',
			$link,
			array(),
			$LO_options
		);

		$link['TITLE']['variables'] = array(
			'subject_id' => 'SUBJECT_ID',
			'course_id' => 'COURSE_ID',
		);

		echo '</div><div class="st">';

		ListOutput(
			$courses_RET,
			array( 'TITLE' => _( 'Course' ) ),
			'Course',
			'Courses',
			$link,
			array(),
			$LO_options
		);

		$columns = array( 'TITLE' => _( 'Course Period' ) );

		$link = array();

		if ( $_REQUEST['modname'] !== 'Scheduling/Schedule.php'
			|| ( $_REQUEST['modname'] === 'Scheduling/Schedule.php'
				&& ! $_REQUEST['include_child_mps'] ) )
		{
			$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=' . $_REQUEST['modfunc'] . '&last_year=' . $_REQUEST['last_year'];

			$link['TITLE']['variables'] = array(
				'subject_id' => 'SUBJECT_ID',
				'course_id' => 'COURSE_ID',
				'course_period_id' => 'COURSE_PERIOD_ID',
			);

			if ( $_REQUEST['modfunc'] === 'choose_course' )
			{
				$link['TITLE']['link'] .= '&modfunc=' . $_REQUEST['modfunc'] .
					'&last_year=' . $_REQUEST['last_year'];
			}
		}

		//if ( $_REQUEST['modname']=='Scheduling/Schedule.php')
		$columns += array(
			'AVAILABLE_SEATS' => ( $_REQUEST['include_child_mps'] ?
				_( 'MP' ) . '(' . _( 'Available Seats' ) . ')' :
				_( 'Available Seats' ) ),
		);

		echo '</div><div class="st">';

		ListOutput(
			$periods_RET,
			$columns,
			'Course Period',
			'Course Periods',
			$link,
			array(),
			$LO_options
		);

		echo '</div>';
	}
}

// FJ days display to locale.
$days_convert = array(
	'U' => _( 'Sunday' ),
	'M' => _( 'Monday' ),
	'T' => _( 'Tuesday' ),
	'W' => _( 'Wednesday' ),
	'H' => _( 'Thursday' ),
	'F' => _( 'Friday' ),
	'S' => _( 'Saturday' ),
);

// FJ days numbered.

if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
{
	$days_convert = array(
		'U' => '7',
		'M' => '1',
		'T' => '2',
		'W' => '3',
		'H' => '4',
		'F' => '5',
		'S' => '6',
	);
}

// UPDATING.

if ( $_REQUEST['tables']
	&& $_POST['tables']
	&& AllowEdit() )
{
	$where = array(
		'COURSE_SUBJECTS' => 'SUBJECT_ID',
		'COURSES' => 'COURSE_ID',
		'COURSE_PERIODS' => 'COURSE_PERIOD_ID',
		'COURSE_PERIOD_SCHOOL_PERIODS' => 'COURSE_PERIOD_SCHOOL_PERIODS_ID',
	);

	if ( isset( $_REQUEST['tables']['parent_id'] ) )
	{
		$_REQUEST['tables']['COURSE_PERIODS'][$_REQUEST['course_period_id']]['PARENT_ID'] = $_REQUEST['tables']['parent_id'];

		unset( $_REQUEST['tables']['parent_id'] );
	}

	// FJ bugfix SQL error invalid input syntax for type numeric
	// when COURSE_PERIOD_SCHOOL_PERIODS saved before COURSE_PERIODS, but why?

	if ( $_REQUEST['course_period_id'] == 'new' )
	{
		foreach ( (array) $_REQUEST['tables'] as $table_name => $tables )
		{
			if ( $table_name === 'COURSE_PERIOD_SCHOOL_PERIODS' )
			{
				unset( $_REQUEST['tables'][$table_name] );

				// Push COURSE_PERIOD_SCHOOL_PERIODS after COURSE_PERIODS.
				$_REQUEST['tables'][$table_name] = $tables;

				break;
			}
		}
	}

	$temp_PERIOD_ID = array();

	foreach ( (array) $_REQUEST['tables'] as $table_name => $tables )
	{
		foreach ( (array) $tables as $id => $columns )
		{
			// FJ fix SQL bug invalid numeric data.

			if (  ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) ) && ( empty( $columns['CREDIT_HOURS'] ) || is_numeric( $columns['CREDIT_HOURS'] ) ) && ( empty( $columns['CREDITS'] ) || is_numeric( $columns['CREDITS'] ) ) )
			{
				//FJ added SQL constraint TITLE (course_subjects & courses) & SHORT_NAME, TEACHER_ID (course_periods) & PERIOD_ID (course_period_school_periods) are not null

				if ( ! (  ( isset( $columns['TITLE'] ) && empty( $columns['TITLE'] ) ) || ( $table_name == 'COURSE_PERIODS' && (  ( isset( $columns['SHORT_NAME'] ) && empty( $columns['SHORT_NAME'] ) ) || ( isset( $columns['TEACHER_ID'] ) && empty( $columns['TEACHER_ID'] ) ) ) ) || ( mb_strpos( $id, 'new' ) !== false && ! empty( $columns['PERIOD_ID'] ) && ! isset( $columns['DAYS'] ) ) ) )
				{
					if ( $table_name === 'COURSES'
						&& $columns['DESCRIPTION'] )
					{
						// Sanitize Course Description HTML. Get data from $_POST as it has HTML tags.
						$columns['DESCRIPTION'] = SanitizeHTML( $_POST['tables']['COURSES'][ $id ]['DESCRIPTION'] );
					}

					if ( $columns['TOTAL_SEATS'] && ! is_numeric( $columns['TOTAL_SEATS'] ) )
					{
						$columns['TOTAL_SEATS'] = preg_replace( '/[^0-9]+/', '', $columns['TOTAL_SEATS'] );
					}

					$days = '';

					if ( $columns['DAYS'] )
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

					if ( $columns['DOES_ATTENDANCE'] )
					{
						foreach ( (array) $columns['DOES_ATTENDANCE'] as $tbl => $y )
						{
							if ( $y == 'Y' )
							{
								$tbls .= ',' . $tbl;
							}
						}

						if ( $tbls )
						{
							$columns['DOES_ATTENDANCE'] = $tbls . ',';
						}
						else
						{
							$columns['DOES_ATTENDANCE'] = '';
						}
					}

					// if ( $id!== 'new')

					if ( mb_strpos( $id, 'new' ) === false )
					{
						if ( $table_name == 'COURSES' && $columns['SUBJECT_ID'] && $columns['SUBJECT_ID'] != $_REQUEST['subject_id'] )
						{
							$_REQUEST['subject_id'] = $columns['SUBJECT_ID'];
						}

						$sql = "UPDATE " . DBEscapeIdentifier( $table_name ) . " SET ";

						if ( $table_name == 'COURSE_PERIODS' )
						{
							//$current = DBGet( "SELECT TEACHER_ID,PERIOD_ID,MARKING_PERIOD_ID,DAYS,SHORT_NAME FROM COURSE_PERIODS WHERE ".$where[ $table_name ]."='".$id."'" );
							$current = DBGet( "SELECT TEACHER_ID,MARKING_PERIOD_ID,
								SHORT_NAME,TITLE
								FROM COURSE_PERIODS
								WHERE " . $where[$table_name] . "='" . $id . "'" );

							if ( isset( $columns['TEACHER_ID'] ) )
							{
								$staff_id = $columns['TEACHER_ID'];
							}
							else
							{
								$staff_id = $current[1]['TEACHER_ID'];
							}

							if ( isset( $columns['MARKING_PERIOD_ID'] ) )
							{
								$marking_period_id = $columns['MARKING_PERIOD_ID'];
							}
							else
							{
								$marking_period_id = $current[1]['MARKING_PERIOD_ID'];
							}

							if ( $columns['SHORT_NAME'] )
							{
								$short_name = $columns['SHORT_NAME'];
							}
							else
							{
								$short_name = $current[1]['SHORT_NAME'];
							}

							$mp_title = '';

							if ( GetMP( $marking_period_id, 'MP' ) != 'FY' )
							{
								$mp_title = GetMP( $marking_period_id, 'SHORT_NAME' ) . ' - ';
							}

							$base_title = $mp_title . $short_name . ' - ';

							// $base_title = str_replace("'","''",$base_title.$teacher[1]['FIRST_NAME'].' '.$teacher[1]['MIDDLE_NAME'].' '.$teacher[1]['LAST_NAME']);
							// FJ remove teacher's middle name to gain space.
							$base_title = DBEscapeString( $base_title . GetTeacher( $staff_id ) );

							$periods_title = '';

							// Get missing part of the title before short name:
							$base_title_pos = mb_strpos(
								$current[1]['TITLE'],
								( GetMP( $current[1]['MARKING_PERIOD_ID'], 'MP' ) !== 'FY' ?
									GetMP( $current[1]['MARKING_PERIOD_ID'], 'SHORT_NAME' ) :
									$current[1]['SHORT_NAME'] )
							);

							if ( $base_title_pos != 0 )
							{
								$periods_title = mb_substr( $current[1]['TITLE'], 0, $base_title_pos );
							}

							$sql .= "TITLE='" . $periods_title . $base_title . "',";

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

						if ( $table_name == 'COURSE_PERIOD_SCHOOL_PERIODS' )
						{
							$other_school_p = DBGet( "SELECT PERIOD_ID,DAYS FROM COURSE_PERIOD_SCHOOL_PERIODS WHERE " . $where['COURSE_PERIODS'] . "='" . $_REQUEST['course_period_id'] . "' AND " . $where[$table_name] . "<>'" . $id . "'" );

							if ( in_array( $columns['PERIOD_ID'], $temp_PERIOD_ID ) ) //prevent repeat periods
							{
								continue;
							}

							$periods_title = '';

							foreach ( $other_school_p as $school_p )
							{
								$school_p_title = DBGetOne( "SELECT TITLE
									FROM SCHOOL_PERIODS
									WHERE PERIOD_ID='" . $school_p['PERIOD_ID'] . "'
									AND SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "'" );

								//FJ days display to locale
								$nb_days = mb_strlen( $school_p['DAYS'] );
								$columns_DAYS_locale = $nb_days > 1 ? ' ' . _( 'Days' ) . ' ' : ( $nb_days == 0 ? '' : ' ' . _( 'Day' ) . ' ' );

								for ( $i = 0; $i < $nb_days; $i++ )
								{
									$columns_DAYS_locale .= mb_substr( $days_convert[mb_substr( $school_p['DAYS'], $i, 1 )], 0, 3 ) . '.';
								}

								if ( mb_strlen( $school_p['DAYS'] ) < 5 )
								{
									$periods_title .= $school_p_title . $columns_DAYS_locale . ' - ';
								}
								else
								{
									$periods_title .= $school_p_title . ' - ';
								}
							}

							if ( empty( $base_title ) )
							{
								$current_cp = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,SHORT_NAME
									FROM COURSE_PERIODS
									WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'" );

								$base_title = mb_substr( $current_cp[1]['TITLE'], mb_strpos( $current_cp[1]['TITLE'], ( GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'MP' ) != 'FY' ? GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'SHORT_NAME' ) : $current_cp[1]['SHORT_NAME'] ) ) );
							}

							if ( ! empty( $columns['DAYS'] ) )
							{
								//FJ days display to locale
								$nb_days = mb_strlen( $columns['DAYS'] );
								$columns_DAYS_locale = $nb_days > 1 ? ' ' . _( 'Days' ) . ' ' : ( $nb_days == 0 ? '' : ' ' . _( 'Day' ) . ' ' );

								for ( $i = 0; $i < $nb_days; $i++ )
								{
									$columns_DAYS_locale .= mb_substr( $days_convert[mb_substr( $columns['DAYS'], $i, 1 )], 0, 3 ) . '.';
								}

								$school_period_title = DBGetOne( "SELECT sp.TITLE
									FROM SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp
									WHERE sp.PERIOD_ID=cpsp.PERIOD_ID
									AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='" . $id . "'
									AND sp.SCHOOL_ID='" . UserSchool() . "'
									AND sp.SYEAR='" . UserSyear() . "'" );

								if ( mb_strlen( $columns['DAYS'] ) < 5 )
								{
									$title = $school_period_title . $columns_DAYS_locale . ' - ' . $periods_title . $base_title;
								}
								else
								{
									$title = $school_period_title . ' - ' . $periods_title . $base_title;
								}
							}
							else
							{
								$title = $periods_title . $base_title;
							}

							DBQuery( "UPDATE COURSE_PERIODS SET TITLE='" . $title . "' WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'" );

							if ( empty( $columns['DAYS'] ) ) //delete school period
							{
								DBQuery( "DELETE FROM COURSE_PERIOD_SCHOOL_PERIODS
									WHERE COURSE_PERIOD_SCHOOL_PERIODS_ID='" . $id . "'" );

								break; //no update
							}
							else
							{
								$temp_PERIOD_ID[] = $columns['PERIOD_ID'];
							}
						}

						foreach ( (array) $columns as $column => $value )
						{
							$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
						}

						$sql = mb_substr( $sql, 0, -1 ) .
							" WHERE " . $where[$table_name] . "='" . $id . "'";

						DBQuery( $sql );

						if ( $table_name === 'COURSE_SUBJECTS' )
						{
							// Hook.
							do_action( 'Scheduling/Courses.php|update_course_subject' );
						}
						elseif ( $table_name === 'COURSES' )
						{
							// Hook.
							do_action( 'Scheduling/Courses.php|update_course' );
						}
						elseif ( $table_name === 'COURSE_PERIODS' )
						{
							if ( isset( $columns['MARKING_PERIOD_ID'] )
								&& $current[1]['MARKING_PERIOD_ID'] !== $columns['MARKING_PERIOD_ID'] )
							{
								// Update schedules marking period too.
								_updateSchedulesCPMP( $id, $columns['MARKING_PERIOD_ID'] );
							}

							// Hook.
							do_action( 'Scheduling/Courses.php|update_course_period' );
						}
					}
					else
					{
						$sql = "INSERT INTO " . $table_name . " ";

						if ( $table_name == 'COURSE_SUBJECTS' )
						{
							$id = DBSeqNextID( 'COURSE_SUBJECTS_SEQ' );
							$fields = 'SUBJECT_ID,SCHOOL_ID,SYEAR,';
							$values = "'" . $id . "','" . UserSchool() . "','" . UserSyear() . "',";
							$_REQUEST['subject_id'] = $id;
						}
						elseif ( $table_name == 'COURSES' )
						{
							$id = DBSeqNextID( 'COURSES_SEQ' );
							$fields = 'COURSE_ID,SUBJECT_ID,SCHOOL_ID,SYEAR,';
							$values = "'" . $id . "','" . $_REQUEST['subject_id'] . "','" . UserSchool() . "','" . UserSyear() . "',";
							/*					$fields = 'COURSE_ID,SCHOOL_ID,SYEAR,';
							$values = "'".$id."','".UserSchool()."','".UserSyear()."',";*/
							$_REQUEST['course_id'] = $id;
						}
						elseif ( $table_name == 'COURSE_PERIODS' )
						{
							$id = DBSeqNextID( 'COURSE_PERIODS_SEQ' );

							$fields = 'SYEAR,SCHOOL_ID,COURSE_PERIOD_ID,COURSE_ID,TITLE,FILLED_SEATS,';

							if ( ! isset( $columns['PARENT_ID'] ) )
							{
								$columns['PARENT_ID'] = $id;
							}

							$mp_title = '';

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

								if ( GetMP( $columns['MARKING_PERIOD_ID'], 'MP' ) != 'FY' )
								{
									$mp_title = GetMP( $columns['MARKING_PERIOD_ID'], 'SHORT_NAME' ) . ' - ';
								}
							}

							if ( $columns['SHORT_NAME'] )
							{
								$base_title = $mp_title . $columns['SHORT_NAME'] . ' - ';
							}

							//$base_title = str_replace("'","''",$base_title.$teacher[1]['FIRST_NAME'].' '.$teacher[1]['MIDDLE_NAME'].' '.$teacher[1]['LAST_NAME']);
							//FJ remove teacher's middle name to gain space
							$base_title = DBEscapeString( $base_title . GetTeacher( $columns['TEACHER_ID'] ) );

							$values = "'" . UserSyear() . "','" . UserSchool() . "','" . $id . "','" . $_REQUEST['course_id'] . "','" . $base_title . "','0',";
							$_REQUEST['course_period_id'] = $id;
						}

						//FJ multiple school period for a course period
						elseif ( $table_name == 'COURSE_PERIOD_SCHOOL_PERIODS' )
						{
							//FJ add new school period to existing course period

							if ( isset( $columns['PERIOD_ID'] ) && empty( $columns['PERIOD_ID'] ) )
							{
								continue;
							}

							$other_school_p = DBGet( "SELECT PERIOD_ID,DAYS
								FROM COURSE_PERIOD_SCHOOL_PERIODS
								WHERE " . $where['COURSE_PERIODS'] . "='" . $_REQUEST['course_period_id'] . "'", array(), array( 'PERIOD_ID' ) );

							if ( in_array( $columns['PERIOD_ID'], $temp_PERIOD_ID ) || in_array( $columns['PERIOD_ID'], array_keys( $other_school_p ) ) )
							{
								continue;
							}

							$temp_PERIOD_ID[] = $columns['PERIOD_ID'];

							$fields = 'COURSE_PERIOD_SCHOOL_PERIODS_ID,COURSE_PERIOD_ID,';
							$values = "nextval('COURSE_PERIOD_SCHOOL_PERIODS_SEQ'),'" . $_REQUEST['course_period_id'] . "',";

							//FJ days display to locale
							$nb_days = mb_strlen( $columns['DAYS'] );
							$columns_DAYS_locale = $nb_days > 1 ? ' ' . _( 'Days' ) . ' ' : ( $nb_days == 0 ? '' : ' ' . _( 'Day' ) . ' ' );

							for ( $i = 0; $i < $nb_days; $i++ )
							{
								$columns_DAYS_locale .= mb_substr( $days_convert[mb_substr( $columns['DAYS'], $i, 1 )], 0, 3 ) . '.';
							}

							$school_period_title = DBGetOne( "SELECT TITLE
								FROM SCHOOL_PERIODS
								WHERE PERIOD_ID='" . $columns['PERIOD_ID'] . "'
								AND SCHOOL_ID='" . UserSchool() . "'
								AND SYEAR='" . UserSyear() . "'" );

							if ( mb_strlen( $columns['DAYS'] ) < 5 )
							{
								$title_add = $school_period_title . $columns_DAYS_locale;
							}
							else
							{
								$title_add = $school_period_title;
							}

							DBQuery( "UPDATE COURSE_PERIODS SET TITLE=COALESCE('" . $title_add . "'||' - '||TITLE) WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'" );
						}

						$go = 0;

						foreach ( (array) $columns as $column => $value )
						{
							if ( isset( $value ) )
							{
								$fields .= DBEscapeIdentifier( $column ) . ',';
								$values .= "'" . $value . "',";
								$go = true;
							}
						}

						$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

						if ( $go )
						{
							DBQuery( $sql );

							if ( $table_name == 'COURSE_SUBJECTS' )
							{
								//hook
								do_action( 'Scheduling/Courses.php|create_course_subject' );
							}
							elseif ( $table_name == 'COURSES' )
							{
								//hook
								do_action( 'Scheduling/Courses.php|create_course' );
							}
							elseif ( $table_name == 'COURSE_PERIODS' )
							{
								//hook
								do_action( 'Scheduling/Courses.php|create_course_period' );
							}
						}
					}
				}
				else
				{
					$error[] = _( 'Please fill in the required fields' );

					if ( $table_name == 'COURSE_PERIODS' )
					{
						break 2; // Skip COURSE_PERIOD_SCHOOL_PERIODS
					}
				}
			}
			else
			{
				$error[] = _( 'Please enter valid Numeric data.' );

				if ( $table_name == 'COURSE_PERIODS' )
				{
					break 2; // Skip COURSE_PERIOD_SCHOOL_PERIODS
				}
			}
		}
	}

	// Unset tables & redirect URL.
	RedirectURL( array( 'tables' ) );
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	$delete_sql = array();

	if ( ! empty( $_REQUEST['course_period_id'] ) )
	{
		$table = _( 'Course Period' );

		$delete_sql[] = "UPDATE COURSE_PERIODS
			SET PARENT_ID=NULL
			WHERE PARENT_ID='" . $_REQUEST['course_period_id'] . "'";

		$delete_sql[] = "DELETE FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'";

		$delete_sql[] = "DELETE FROM SCHEDULE
			WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'";

		// FJ multiple school period for a course period.
		$delete_sql[] = "DELETE FROM COURSE_PERIOD_SCHOOL_PERIODS
			WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'";

		$unset_get = 'course_period_id';
	}
	elseif ( ! empty( $_REQUEST['course_id'] ) )
	{
		$table = _( 'Course' );

		$delete_sql[] = "DELETE FROM COURSES
			WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'";

		$delete_sql[] = "UPDATE COURSE_PERIODS
			SET PARENT_ID=NULL
			WHERE PARENT_ID IN (SELECT COURSE_PERIOD_ID
				FROM COURSE_PERIODS
				WHERE COURSE_ID='" . $_REQUEST['course_id'] . "')";

		$delete_sql[] = "DELETE FROM COURSE_PERIODS
			WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'";

		$delete_sql[] = "DELETE FROM SCHEDULE
			WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'";

		$delete_sql[] = "DELETE FROM SCHEDULE_REQUESTS
			WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'";

		$unset_get = 'course_id';
	}
	elseif ( ! empty( $_REQUEST['subject_id'] ) )
	{
		$table = _( 'Subject' );

		$delete_sql[] = "DELETE FROM COURSE_SUBJECTS
			WHERE SUBJECT_ID='" . $_REQUEST['subject_id'] . "'";

		$courses = DBGet( "SELECT COURSE_ID
			FROM COURSES
			WHERE SUBJECT_ID='" . $_REQUEST['subject_id'] . "'" );

		foreach ( (array) $courses as $course )
		{
			$delete_sql[] = "DELETE FROM COURSES
				WHERE COURSE_ID='" . $course['COURSE_ID'] . "'";

			$delete_sql[] = "UPDATE COURSE_PERIODS
				SET PARENT_ID=NULL
				WHERE PARENT_ID IN (SELECT COURSE_PERIOD_ID
					FROM COURSE_PERIODS
					WHERE COURSE_ID='" . $course['COURSE_ID'] . "')";

			$delete_sql[] = "DELETE FROM COURSE_PERIODS
				WHERE COURSE_ID='" . $course['COURSE_ID'] . "'";

			$delete_sql[] = "DELETE FROM SCHEDULE
				WHERE COURSE_ID='" . $course['COURSE_ID'] . "'";

			$delete_sql[] = "DELETE FROM SCHEDULE_REQUESTS
				WHERE COURSE_ID='" . $course['COURSE_ID'] . "'";
		}

		$unset_get = 'subject_id';
	}

	if ( DeletePrompt( $table ) )
	{
		foreach ( (array) $delete_sql as $delete_query )
		{
			DBQuery( $delete_query );
		}

		if ( ! empty( $_REQUEST['course_period_id'] ) )
		{
			// Hook.
			do_action( 'Scheduling/Courses.php|delete_course_period' );
		}
		elseif ( ! empty( $_REQUEST['subject_id'] ) )
		{
			// Hook.
			do_action( 'Scheduling/Courses.php|delete_course_subject' );
		}
		elseif ( ! empty( $_REQUEST['course_id'] ) )
		{
			// Hook.
			do_action( 'Scheduling/Courses.php|delete_course' );
		}

		// Unset modfunc & ID redirect URL.
		RedirectURL( array( 'modfunc', $unset_get ) );
	}
}

if (  ( ! $_REQUEST['modfunc']
	|| $_REQUEST['modfunc'] === 'choose_course' )
	&& ! $_REQUEST['course_modfunc'] )
{
	// Check subject ID is valid for current school & syear!

	if ( $_REQUEST['modfunc'] !== 'choose_course'
		&& isset( $_REQUEST['subject_id'] )
		&& $_REQUEST['subject_id'] !== 'new' )
	{
		$subject_RET = DBGet( "SELECT SUBJECT_ID
			FROM COURSE_SUBJECTS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND SUBJECT_ID='" . $_REQUEST['subject_id'] . "'" );

		if ( ! $subject_RET )
		{
			// Unset subject, course & course period IDs & redirect URL.
			RedirectURL( array(
				$_REQUEST['subject_id'],
				$_REQUEST['course_id'],
				$_REQUEST['course_period_id'],
			) );
		}
	}

	// FJ fix SQL bug invalid sort order
	echo ErrorMessage( $error );

	$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
		FROM COURSE_SUBJECTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . ( $_REQUEST['modfunc'] == 'choose_course' && $_REQUEST['last_year'] == 'true' ?
		UserSyear() - 1 :
		UserSyear() ) . "' ORDER BY SORT_ORDER,TITLE" );

	if ( $_REQUEST['modfunc'] !== 'choose_course' )
	{
		if ( AllowEdit() )
		{
			$delete_url = "'Modules.php?modname=" . $_REQUEST['modname'] .
				'&modfunc=delete&subject_id=' . $_REQUEST['subject_id'] .
				'&course_id=' . $_REQUEST['course_id'] .
				'&course_period_id=' . $_REQUEST['course_period_id'] . "'";

			$delete_button = '<input type="button" value="' . _( 'Delete' ) . '" onClick="javascript:ajaxLink(' . $delete_url . ');" />';
		}

		// ADDING & EDITING FORM

		if ( ! empty( $_REQUEST['course_period_id'] ) )
		{
			if ( $_REQUEST['course_period_id'] !== 'new' )
			{
				//FJ multiple school periods for a course period
				/*$sql = "SELECT PARENT_ID,TITLE,SHORT_NAME,PERIOD_ID,DAYS,
				MP,MARKING_PERIOD_ID,TEACHER_ID,CALENDAR_ID,
				ROOM,TOTAL_SEATS,DOES_ATTENDANCE,
				GRADE_SCALE_ID,DOES_HONOR_ROLL,DOES_CLASS_RANK,
				GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,
				HALF_DAY,DOES_BREAKOFF
				FROM COURSE_PERIODS
				WHERE COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'";*/

				$RET = DBGet( "SELECT PARENT_ID,TITLE,SHORT_NAME,MP,MARKING_PERIOD_ID,TEACHER_ID,
					CALENDAR_ID,ROOM,TOTAL_SEATS,DOES_ATTENDANCE,GRADE_SCALE_ID,
					DOES_HONOR_ROLL,DOES_CLASS_RANK,GENDER_RESTRICTION,
					HOUSE_RESTRICTION,CREDITS,HALF_DAY,DOES_BREAKOFF
					FROM COURSE_PERIODS
					WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'" );

				$RET = $RET[1];

				$title = $RET['TITLE'];

				$RET2 = DBGet( "SELECT COURSE_PERIOD_SCHOOL_PERIODS_ID,PERIOD_ID,DAYS
					FROM COURSE_PERIOD_SCHOOL_PERIODS
					WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'
					ORDER BY COURSE_PERIOD_SCHOOL_PERIODS_ID" );

				$new = false;
			}
			else
			{
				$RET = DBGet( "SELECT TITLE
					FROM COURSES
					WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'" );

				$title = $RET[1]['TITLE'] . ' - ' . _( 'New Course Period' );

				unset( $delete_button );

				$RET = array();

				$checked = 'CHECKED';

				$new = true;
			}

			echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
				'&subject_id=' . $_REQUEST['subject_id'] .
				'&course_id=' . $_REQUEST['course_id'] .
				'&course_period_id=' . $_REQUEST['course_period_id'] . '" method="POST">';

			DrawHeader( $title, $delete_button . SubmitButton() );

			// Hook.
			do_action( 'Scheduling/Courses.php|header' );

			$header .= '<table class="width-100p valign-top fixed-col" id="coursesTable">';
			$header .= '<tr class="st">';

			// FJ Moodle integrator.
			$header .= '<td>' . TextInput(
				$RET['SHORT_NAME'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][SHORT_NAME]',
				_( 'Short Name' ),
				'required maxlength=25',
				( $_REQUEST['moodle_create_course_period'] ? false : true )
			) . '</td>';

			$teachers_RET = DBGet( "SELECT STAFF_ID
				FROM STAFF WHERE (SCHOOLS IS NULL OR STRPOS(SCHOOLS,'," . UserSchool() . ",')>0)
				AND SYEAR='" . UserSyear() . "'
				AND PROFILE='teacher'
				ORDER BY LAST_NAME,FIRST_NAME" );

			foreach ( (array) $teachers_RET as $teacher )
			{
				$teachers[$teacher['STAFF_ID']] = GetTeacher( $teacher['STAFF_ID'] );
			}

			// FJ Moodle integrator.
			$header .= '<td colspan="2">' . SelectInput(
				$RET['TEACHER_ID'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][TEACHER_ID]',
				_( 'Teacher' ),
				$teachers,
				! $_REQUEST['moodle_create_course_period'] ? 'N/A' : false,
				'required',
				! $_REQUEST['moodle_create_course_period']
			) . '</td>';

			$header .= '<td>' . TextInput(
				$RET['ROOM'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][ROOM]',
				_( 'Room' ),
				'maxlength=10'
			) . '</td>';

			$periods_RET = DBGet( "SELECT PERIOD_ID,TITLE
				FROM SCHOOL_PERIODS
				WHERE SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				ORDER BY SORT_ORDER,TITLE" );

			foreach ( (array) $periods_RET as $period )
			{
				$periods[$period['PERIOD_ID']] = $period['TITLE'];
			}

			//$header .= '<td>' . SelectInput($RET['MP'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][MP]','Length',array('FY' => 'Full Year','SEM' => 'Semester','QTR' => 'Marking Period')) . '</td>';
			$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,SHORT_NAME," .
				db_case( array( 'MP', "'FY'", "'0'", "'SEM'", "'1'", "'QTR'", "'2'" ) ) . " AS TBL
				FROM SCHOOL_MARKING_PERIODS
				WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				ORDER BY TBL,SORT_ORDER" );

			unset( $options );

			foreach ( (array) $mp_RET as $mp )
			{
				$options[$mp['MARKING_PERIOD_ID']] = $mp['SHORT_NAME'];
			}

			// FJ Moodle integrator.
			$header .= '<td>' . SelectInput(
				$RET['MARKING_PERIOD_ID'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][MARKING_PERIOD_ID]',
				_( 'Marking Period' ),
				$options,
				false,
				'required',
				( $_REQUEST['moodle_create_course_period'] ? false : true )
			) . '</td>';

			$header .= '<td>' . TextInput(
				$RET['TOTAL_SEATS'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][TOTAL_SEATS]',
				_( 'Seats' ),
				'size=4 maxlength=4'
			) . '</td>';

			$header .= '</tr><tr><td colspan="6"><hr /></td></tr>';

			$days = array( 'M', 'T', 'W', 'H', 'F', 'S', 'U' );

			// FJ days numbered.

			if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
			{
				$days = array_slice( $days, 0, SchoolInfo( 'NUMBER_DAYS_ROTATION' ) );
			}

			// FJ multiple school periods for a course period.
			$i = 0;

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
						$school_period['PERIOD_ID'],
						'tables[COURSE_PERIOD_SCHOOL_PERIODS][' . $school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] . '][PERIOD_ID]',
						_( 'Period' ),
						$periods
					) . '</td>';
				}

				$header .= '<td colspan="5">';

				$days_html = '<table class="cellspacing-0"><tr class="st">';

				$day_titles = array();

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
						'tables[COURSE_PERIOD_SCHOOL_PERIODS][' . $school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] . '][DAYS][' . $day . ']',
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

				$days_id = 'days' . $i;

				$days_title = FormatInputTitle( _( 'Meeting Days' ), $days_id, empty( $school_period['DAYS'] ) );

				if ( $new == false )
				{
					// Fix Delete Period when days unchecked.
					$days_html = '<input type="hidden" value="" name="tables[COURSE_PERIOD_SCHOOL_PERIODS][' . $school_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] . '][DAYS][' . $day . ']" />' . $days_html;

					$header .= InputDivOnclick(
						$days_id,
						$days_html . str_replace( '<br />', '', $days_title ),
						implode( ' ', $day_titles ),
						$days_title
					);
				}
				else
				{
					$header .= $days_html . str_replace( '<br />', '', $days_title );
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
				$header .= '<tr class="st"><td colspan="6">
					<a href="#" onclick="' .
				( $new ?
					'newSchoolPeriod();' :
					'document.getElementById(\'schoolPeriod\'+' . $i . ').style.display=\'table-row\';' ) .
				' return false;">' .
				button( 'add' ) . ' ' . _( 'New Period' ) . '</a>
					<hr /></td></tr>';

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
						row = table.insertRow(1+nbSchoolPeriods);
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
						if (i == 1) cell.setAttribute('colspan', '5');
						reg = new RegExp('new' + (newId-1),'g'); //g for global string
						cell.innerHTML = cell.innerHTML.replace(reg, 'new'+newId);
						// remove required attribute
						cell.innerHTML = cell.innerHTML.replace( 'required', '' );
					}
				</script>
				<?php
}

			$header .= '<tr class="st"><td colspan="2">';

			$categories_RET = DBGet( "SELECT '0' AS ID,'" . _( 'Attendance' ) . "' AS TITLE
				UNION SELECT ID,TITLE
				FROM ATTENDANCE_CODE_CATEGORIES
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			$attendance_html = '<table class="cellspacing-0 width-100p"><tr class="st">';

			$attendance_cat = array();

			$i = 0;

			foreach ( (array) $categories_RET as $category )
			{
				if ( $i % 2 === 0 )
				{
					$attendance_html .= '</tr><tr class="st">';
				}

				if ( mb_strpos( $RET['DOES_ATTENDANCE'], ',' . $category['ID'] . ',' ) !== false )
				{
					$value = 'Y';
				}
				else
				{
					$value = '';
				}

				$attendance_html .= '<td>' . CheckboxInput(
					$value,
					'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][DOES_ATTENDANCE][' . $category['ID'] . ']',
					$category['TITLE'],
					'',
					true
				) . '&nbsp;</td>';

				$i++;
			}

			$attendance_html .= '</tr></table>';

			$attendance_title = FormatInputTitle( _( 'Takes Attendance' ), 'attendance', false, '' );

			$header .= $attendance_html . $attendance_title;

			$header .= '</td><td colspan="2">' . CheckboxInput(
				$RET['DOES_HONOR_ROLL'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][DOES_HONOR_ROLL]',
				_( 'Affects Honor Roll' ),
				$checked,
				$new,
				button( 'check' ),
				button( 'x' )
			) . '</td>';

			$header .= '<td colspan="2">' . CheckboxInput(
				$RET['DOES_CLASS_RANK'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][DOES_CLASS_RANK]',
				_( 'Affects Class Rank' ),
				$checked,
				$new,
				button( 'check' ),
				button( 'x' )
			) . '</td>';

			$header .= '</tr><tr class="st"><td colspan="2">' . SelectInput(
				$RET['GENDER_RESTRICTION'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][GENDER_RESTRICTION]',
				_( 'Gender Restriction' ),
				array(
					'N' => _( 'None' ),
					'M' => _( 'Male' ),
					'F' => _( 'Female' ),
				),
				false
			) . '</td>';

			$options_RET = DBGet( "SELECT TITLE,ID
				FROM REPORT_CARD_GRADE_SCALES
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			$options = array();

			foreach ( (array) $options_RET as $option )
			{
				$options[$option['ID']] = $option['TITLE'];
			}

			$header .= '<td colspan="2">' . SelectInput(
				$RET['GRADE_SCALE_ID'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][GRADE_SCALE_ID]',
				_( 'Grading Scale' ),
				$options,
				_( 'Not Graded' )
			) . '</td>';

			// bjj Added to handle credits.
			$header .= '<td>' . TextInput(
				is_null( $RET['CREDITS'] ) ? '1' : (float) $RET['CREDITS'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][CREDITS]',
				_( 'Credits' ),
				'size=4 maxlength=5',
				( is_null( $RET['CREDITS'] ) ? false : true )
			) . '</td>';

			$options_RET = DBGet( "SELECT TITLE,CALENDAR_ID
				FROM ATTENDANCE_CALENDARS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY DEFAULT_CALENDAR ASC,TITLE" );

			$options = array();

			foreach ( (array) $options_RET as $option )
			{
				$options[$option['CALENDAR_ID']] = $option['TITLE'];
			}

			$header .= '<td>' . SelectInput(
				$RET['CALENDAR_ID'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][CALENDAR_ID]',
				_( 'Calendar' ),
				$options,
				false,
				'required'
			) . '</td>';

			// BJJ Parent course select was here...  moved it down.
			$header .= '</tr><tr class="st">';

			//$header .= '<td>' . CheckboxInput($RET['HOUSE_RESTRICTION'],'tables[COURSE_PERIODS]['.$_REQUEST['course_period_id'].'][HOUSE_RESTRICTION]','Restricts House','',$new) . '</td>';

			$header .= '<td>' . CheckboxInput(
				$RET['HALF_DAY'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][HALF_DAY]',
				_( 'Half Day' ),
				$checked,
				$new,
				button( 'check' ),
				button( 'x' )
			) . '</td>';

			$header .= '<td colspan="3">' . CheckboxInput(
				$RET['DOES_BREAKOFF'],
				'tables[COURSE_PERIODS][' . $_REQUEST['course_period_id'] . '][DOES_BREAKOFF]',
				_( 'Allow Teacher Grade Scale' ),
				$checked,
				$new,
				button( 'check' ),
				button( 'x' )
			) . '</td>';

			if ( $_REQUEST['course_period_id'] !== 'new'
				&& $RET['PARENT_ID'] !== $_REQUEST['course_period_id'] )
			{
				$parent = DBGet( "SELECT cp.TITLE as CP_TITLE,c.TITLE AS C_TITLE
					FROM COURSE_PERIODS cp,COURSES c
					WHERE c.COURSE_ID=cp.COURSE_ID
					AND cp.COURSE_PERIOD_ID='" . $RET['PARENT_ID'] . "'" );

				$parent = $parent[1]['C_TITLE'] . ': ' . $parent[1]['CP_TITLE'];
			}
			elseif ( $_REQUEST['course_period_id'] !== 'new' )
			{
				$children = DBGet( "SELECT COURSE_PERIOD_ID
					FROM COURSE_PERIODS
					WHERE PARENT_ID='" . $_REQUEST['course_period_id'] . "'
					AND COURSE_PERIOD_ID!='" . $_REQUEST['course_period_id'] . "'" );

				if ( ! empty( $children ) )
				{
					$parent = _( 'N/A' );
				}
				else
				{
					$parent = _( 'None' );
				}
			}

			$header .= '<td colspan="2"><div id="course_div">' . $parent . '</div> ' .
			( $parent != _( 'N/A' ) && AllowEdit() ?
				'<a href="#" onclick=\'popups.open(
						"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=choose_course"
					); return false;\'>' . _( 'Choose' ) . '</a><br />' :
				'' ) .
			'<span class="legend-gray">' . _( 'Parent Course Period' ) . '</span></td>';

			$header .= '</tr></table>';

			DrawHeader( $header );
			//echo '</form>';
		}
		elseif ( ! empty( $_REQUEST['course_id'] ) )
		{
			if ( $_REQUEST['course_id'] !== 'new' )
			{
				$RET = DBGet( "SELECT TITLE,SHORT_NAME,GRADE_LEVEL,CREDIT_HOURS,DESCRIPTION
					FROM COURSES
					WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'" );

				$RET = $RET[1];

				$title = $RET['TITLE'];
			}
			else
			{
				$RET = DBGet( "SELECT TITLE
					FROM COURSE_SUBJECTS
					WHERE SUBJECT_ID='" . $_REQUEST['subject_id'] . "'" );

				$title = $RET[1]['TITLE'] . ' - ' . _( 'New Course' );

				unset( $delete_button );

				unset( $RET );
			}

			echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '" method="POST">';
			DrawHeader( $title, $delete_button . SubmitButton() );
			$header .= '<table class="width-100p valign-top">';
			$header .= '<tr class="st">';

			//FJ title required
			$header .= '<td>' . TextInput(
				$RET['TITLE'],
				'tables[COURSES][' . $_REQUEST['course_id'] . '][TITLE]',
				_( 'Title' ),
				'required maxlength=100 size=20'
			) . '</td>';

			$header .= '<td>' . TextInput(
				$RET['SHORT_NAME'],
				'tables[COURSES][' . $_REQUEST['course_id'] . '][SHORT_NAME]',
				_( 'Short Name' ),
				'maxlength=25'
			) . '</td>';

			//FJ add Credit Hours to Courses
			$header .= '<td>' . TextInput(
				$RET['CREDIT_HOURS'],
				'tables[COURSES][' . $_REQUEST['course_id'] . '][CREDIT_HOURS]',
				_( 'Credit Hours' ),
				'maxlength=7 size=7'
			) . '</td></tr>';

			// Add Description (TinyMCE input) to Course.
			$header .= '<tr class="st"><td colspan="3">' . TinyMCEInput(
				$RET['DESCRIPTION'],
				'tables[COURSES][' . $_REQUEST['course_id'] . '][DESCRIPTION]',
				_( 'Description' )
			) . '</td></tr>';

			//FJ SQL error column "subject_id" specified more than once
			/*if ( $_REQUEST['modfunc']!='choose_course')
			{
			foreach ( (array) $subjects_RET as $type)
			$options[$type['SUBJECT_ID']] = $type['TITLE'];

			$header .= '<td>' . SelectInput($RET['SUBJECT_ID']?$RET['SUBJECT_ID']:$_REQUEST['subject_id'],'tables[COURSES]['.$_REQUEST['course_id'].'][SUBJECT_ID]',_('Subject'),$options,false) . '</td>';
			}*/
			$header .= '</tr></table>';

			DrawHeader( $header );

			echo '</form>';
		}
		elseif ( ! empty( $_REQUEST['subject_id'] ) )
		{
			if ( $_REQUEST['subject_id'] !== 'new' )
			{
				$RET = DBGet( "SELECT TITLE,SORT_ORDER
					FROM COURSE_SUBJECTS
					WHERE SUBJECT_ID='" . $_REQUEST['subject_id'] . "'" );

				$RET = $RET[1];

				$title = $RET['TITLE'];
			}
			else
			{
				$title = _( 'New Subject' );

				unset( $delete_button );
			}

			echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '" method="POST">';
			DrawHeader( $title, $delete_button . SubmitButton() );
			$header .= '<table class="width-100p valign-top fixed-col">';
			$header .= '<tr class="st">';

			//FJ title required
			$header .= '<td>' . TextInput(
				$RET['TITLE'],
				'tables[COURSE_SUBJECTS][' . $_REQUEST['subject_id'] . '][TITLE]',
				_( 'Title' ),
				'required maxlength=100 size=20'
			) . '</td>';

			$header .= '<td>' . TextInput(
				$RET['SORT_ORDER'],
				'tables[COURSE_SUBJECTS][' . $_REQUEST['subject_id'] . '][SORT_ORDER]',
				_( 'Sort Order' ),
				'maxlength=3 size=5'
			) . '</td>';

			$header .= '</tr></table>';

			DrawHeader( $header );

			echo '</form>';
		}
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
			echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

			DrawHeader(
				$choose_a_header_html,
				_( 'Enrollment Date' ) . ' ' . PrepareDate( $date, '_date', false, array( 'submit' => true ) ),
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
		'<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] .
		'&course_modfunc=search&last_year=' . $_REQUEST['last_year'] .
		( $_REQUEST['modfunc'] == 'choose_course' && $_REQUEST['modname'] == 'Scheduling/Schedule.php' ?
			'&include_child_mps=' . $_REQUEST['include_child_mps'] . '&year_date=' . $_REQUEST['year_date'] . '&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'] :
			'' ) .
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

	$columns = array( 'TITLE' => _( 'Subject' ) );
	$link = array();
	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
	$link['TITLE']['variables'] = array( 'subject_id' => 'SUBJECT_ID' );

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
		array(),
		$LO_options
	);

	echo '</div>';

	if ( $_REQUEST['subject_id']
		&& $_REQUEST['subject_id'] !== 'new' )
	{
		$courses_RET = DBGet( "SELECT COURSE_ID,TITLE
			FROM COURSES
			WHERE SUBJECT_ID='" . $_REQUEST['subject_id'] . "'
			ORDER BY TITLE" );

		if ( ! empty( $courses_RET )
			&& $_REQUEST['course_id'] )
		{
			foreach ( (array) $courses_RET as $key => $value )
			{
				if ( $value['COURSE_ID'] === $_REQUEST['course_id'] )
				{
					$courses_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
				}
			}
		}

		$columns = array( 'TITLE' => _( 'Course' ) );

		$link = array();

		$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'];

		$link['TITLE']['variables'] = array( 'course_id' => 'COURSE_ID' );

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
			array(),
			$LO_options
		);

		echo '</div>';

		if ( $_REQUEST['course_id']
			&& $_REQUEST['course_id'] !== 'new' )
		{
			// FJ add Available Seats column to every choose course popup.

			if ( $_REQUEST['modname'] !== 'Scheduling/Schedule.php' )
			{
				$date = DBDate();
			}

			//FJ multiple school periods for a course period
			//$periods_RET = DBGet( "SELECT '".$_REQUEST['subject_id']."' AS SUBJECT_ID,COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,TOTAL_SEATS AS AVAILABLE_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='".$_REQUEST['course_id']."' ".($_REQUEST['modfunc']=='choose_course' && $_REQUEST['modname']=='Scheduling/Schedule.php'?" AND '".$date."'<=(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE SYEAR=cp.SYEAR AND MARKING_PERIOD_ID=cp.MARKING_PERIOD_ID)":'')." ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID),TITLE"));
			$periods_RET = DBGet( "SELECT '" . $_REQUEST['subject_id'] . "' AS SUBJECT_ID,
				COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,
				TOTAL_SEATS AS AVAILABLE_SEATS
				FROM COURSE_PERIODS cp
				WHERE COURSE_ID='" . $_REQUEST['course_id'] . "' " .
				( $_REQUEST['modfunc'] === 'choose_course'
					&& $_REQUEST['modname'] === 'Scheduling/Schedule.php' ?
					" AND '" . $date . "'<=(SELECT END_DATE
						FROM SCHOOL_MARKING_PERIODS
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

			$columns = array( 'TITLE' => _( 'Course Period' ) );

			$link = array();

			if ( $_REQUEST['modname'] !== 'Scheduling/Schedule.php'
				|| ( $_REQUEST['modname'] === 'Scheduling/Schedule.php'
					&& ! $_REQUEST['include_child_mps'] ) )
			{
				$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'];

				$link['TITLE']['variables'] = array( 'course_period_id' => 'COURSE_PERIOD_ID', 'course_marking_period_id' => 'MARKING_PERIOD_ID' );

				if ( $_REQUEST['modfunc'] === 'choose_course' )
				{
					$link['TITLE']['link'] .= '&modfunc=' . $_REQUEST['modfunc'] . '&student_id=' . $_REQUEST['student_id'] . '&last_year=' . $_REQUEST['last_year'];
				}
				else
				{
					$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&course_period_id=new';
				}
			}

			//if ( $_REQUEST['modname']=='Scheduling/Schedule.php')
			$columns += array(
				'AVAILABLE_SEATS' => ( $_REQUEST['include_child_mps'] ?
					_( 'MP' ) . '(' . _( 'Available Seats' ) . ')' :
					_( 'Available Seats' ) ),
			);

			echo '<div class="st">';

			ListOutput(
				$periods_RET,
				$columns,
				'Course Period',
				'Course Periods',
				$link,
				array(),
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
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . $_REQUEST['course_period_id'] . "'" );

	$html_to_escape = $course_title .
		'<input type="hidden" name="tables[parent_id]" value="' . $_REQUEST['course_period_id'] . '" />';

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

				$link .= '&last_year=' . $_REQUEST['last_year'] . '&year_date=' . $_REQUEST['year_date'] .
				'&month_date=' . $_REQUEST['month_date'] . '&day_date=' . $_REQUEST['day_date'];

				$link .= '&course_period_id=' . $period['COURSE_PERIOD_ID'] . '&course_marking_period_id=' . $mp;

				if ( $period['AVAILABLE_SEATS'] )
				{
					$period['MARKING_PERIOD_ID'] = $mp;

					$filled_seats = calcSeats0( $period, $date );

					if ( $filled_seats != '' )
					{
						if ( ! empty( $_REQUEST['include_child_mps'] ) )
						{
							$periods[$key]['AVAILABLE_SEATS'] .= '<a href=' . $link . '>' .
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
						$periods[$key]['AVAILABLE_SEATS'] .= '<a href=' . $link . '>' .
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
 *
 * @param  string $cp_id Course Period ID.
 * @param  string $mp_id Marking Period ID.
 * @return int    Number of schedules updated.
 */
function _updateSchedulesCPMP( $cp_id, $mp_id )
{
	// Get CP MP.
	$cp_mp = GetMP( $mp_id, 'MP' );

	if ( ! $cp_id
		|| ! $mp_id
		|| ! $cp_mp )
	{
		return 0;
	}

	if ( $cp_mp === 'FY' )
	{
		// CP MP is Full Year, no need to update.
		return 0;
	}

	if ( $cp_mp !== 'SEM'
		&& $cp_mp !== 'QTR' )
	{
		// CP MP is not a Semester neither a Quarter...!
		return 0;
	}

	$schedule_mp_in = ( $cp_mp === 'QTR' ? "'FY','SEM'" : "'FY'" );

	// Update Schedules for CP where MP is of greater type
	// than the new course period marking period.
	$update = DBQuery( "UPDATE SCHEDULE SET
		MP='" . $cp_mp . "',
		MARKING_PERIOD_ID='" . $mp_id . "'
		WHERE COURSE_PERIOD_ID='" . $cp_id . "'
		AND MP IN (" . $schedule_mp_in . ")" );

	// Return number of updated schedules.

	return pg_affected_rows( $update );
}
