<?php
/**
 * Courses functions
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Check for Course Period Teacher conflict
 *
 * @since 4.8
 * @since 6.9 Add Secondary Teacher.
 * @since 9.0 Add $course_period_id param to limit check to a single Course Period.
 * @since 10.6.2 Fix #338 Only check against Course Periods having overlapping Marking Period
 *
 * @param int $teacher_id       Teacher ID.
 * @param int $course_period_id Course Period ID.
 *
 * @return boolean True if confliciting days for the same period, else false.
 */
function CoursePeriodTeacherConflictCheck( $teacher_id, $course_period_id )
{
	if ( ! $teacher_id
		|| ! $course_period_id )
	{
		return false;
	}

	$this_school_periods_RET = DBGet( "SELECT cpsp.PERIOD_ID,cpsp.DAYS,cp.MARKING_PERIOD_ID
		FROM course_period_school_periods cpsp,course_periods cp
		WHERE cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND cp.SYEAR='" . UserSyear() . "'
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND (TEACHER_ID='" . (int) $teacher_id . "'
			OR SECONDARY_TEACHER_ID='" . (int) $teacher_id . "')
		AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

	if ( empty( $this_school_periods_RET[1]['MARKING_PERIOD_ID'] ) )
	{
		return false;
	}

	$all_mp = GetAllMP(
		GetMP( $this_school_periods_RET[1]['MARKING_PERIOD_ID'], 'MP' ),
		$this_school_periods_RET[1]['MARKING_PERIOD_ID']
	);

	if ( ! $all_mp )
	{
		return false;
	}

	// Get school periods for Teacher course periods (having an MP which overlaps this MP).
	$school_periods_RET = DBGet( "SELECT cpsp.PERIOD_ID,cpsp.DAYS,cp.COURSE_PERIOD_ID
		FROM course_period_school_periods cpsp,course_periods cp
		WHERE cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND cp.SYEAR='" . UserSyear() . "'
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND (TEACHER_ID='" . (int) $teacher_id . "'
			OR SECONDARY_TEACHER_ID='" . (int) $teacher_id . "')
		AND cp.COURSE_PERIOD_ID<>'" . (int) $course_period_id . "'
		AND cp.MARKING_PERIOD_ID IN(" . $all_mp . ")" );

	if ( empty( $school_periods_RET ) )
	{
		return false;
	}

	$this_school_periods = [];

	foreach ( (array) $this_school_periods_RET as $this_school_period )
	{
		$this_school_periods[ $this_school_period['PERIOD_ID'] ] = $this_school_period['DAYS'];
	}

	foreach ( (array) $school_periods_RET as $school_period )
	{
		if ( isset( $this_school_periods[ $school_period['PERIOD_ID'] ] ) )
		{
			$days_array = str_split( $this_school_periods[ $school_period['PERIOD_ID'] ] );

			$days_array2 = str_split( $school_period['DAYS'] );

			$common_days = array_intersect( $days_array, $days_array2 );

			if ( $common_days )
			{
				return true;
			}
		}
	}

	return false;
}




/**
 * Course Period Takes Attendance input
 *
 * @since 4.9
 *
 * @param string $does_attendance DOES_ATTENDANCE value.
 * @param string $array           Input name prefix, before value index (array).
 *
 * @return string Course Takes Attendance HTML
 */
function CoursePeriodAttendanceInput( $does_attendance, $array )
{
	$attendance_html = '<table class="cellspacing-0"><tr class="st">';

	$attendance_cat = [];

	$i = 0;

	$categories_RET = DBGet( "SELECT '0' AS ID,'" . DBEscapeString( _( 'Attendance' ) ) . "' AS TITLE
		UNION SELECT ID,TITLE
		FROM attendance_code_categories
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	foreach ( (array) $categories_RET as $category )
	{
		if ( $i && $i % 2 === 0 )
		{
			$attendance_html .= '</tr><tr class="st">';
		}

		$value = '';

		if ( mb_strpos( $does_attendance, ',' . $category['ID'] . ',' ) !== false )
		{
			$value = 'Y';
		}

		$attendance_html .= '<td>' . CheckboxInput(
			$value,
			$array . '[DOES_ATTENDANCE][' . $category['ID'] . ']',
			$category['TITLE'],
			'',
			true
		) . ' &nbsp; </td>';

		$i++;
	}

	$attendance_html .= '</tr></table>';

	$attendance_html .= FormatInputTitle( _( 'Takes Attendance' ), '', false, '' );

	return $attendance_html;
}


/**
 * Course Period option inputs
 *
 * @since 4.9
 * @since 8.9 Remove Half Day option
 *
 * @param array  $course_period_RET Course Period data array from DB.
 * @param string $array             Input name prefix, before value index (array).
 * @param bool   $new               Is new input?
 *
 * @return array Course Period option inputs
 */
function CoursePeriodOptionInputs( $course_period_RET, $array, $new )
{
	$inputs = [];

	$options_RET = DBGet( "SELECT TITLE,CALENDAR_ID
		FROM attendance_calendars
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY DEFAULT_CALENDAR IS NULL,DEFAULT_CALENDAR ASC,TITLE" );

	$options = [];

	foreach ( (array) $options_RET as $option )
	{
		$options[$option['CALENDAR_ID']] = $option['TITLE'];
	}

	$inputs[] = SelectInput(
		issetVal( $course_period_RET['CALENDAR_ID'], '' ),
		$array . '[CALENDAR_ID]',
		_( 'Calendar' ),
		$options,
		false,
		'required'
	);

	$inputs[] = CoursePeriodAttendanceInput( issetVal( $course_period_RET['DOES_ATTENDANCE'], '' ), $array );

	$options_RET = DBGet( "SELECT TITLE,ID
		FROM report_card_grade_scales
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	$options = [];

	foreach ( (array) $options_RET as $option )
	{
		$options[$option['ID']] = $option['TITLE'];
	}

	$inputs[] = SelectInput(
		issetVal( $course_period_RET['GRADE_SCALE_ID'], '' ),
		$array . '[GRADE_SCALE_ID]',
		_( 'Grading Scale' ),
		$options,
		_( 'Not Graded' )
	);

	$inputs[] = CheckboxInput(
		issetVal( $course_period_RET['DOES_BREAKOFF'], '' ),
		$array . '[DOES_BREAKOFF]',
		_( 'Allow Teacher Grade Scale' ),
		'',
		$new,
		button( 'check' ),
		button( 'x' )
	);

	$inputs[] = TextInput(
		! isset( $course_period_RET['CREDITS'] ) || is_null( $course_period_RET['CREDITS'] ) ?
			'1' :
			(float) $course_period_RET['CREDITS'],
		$array . '[CREDITS]',
		_( 'Credits' ),
		// @since 11.1 SQL set min Credits to 0 & fix division by zero error
		' type="number" step="0.01" min="0" max="9999"',
		( ! isset( $course_period_RET['CREDITS'] ) || is_null( $course_period_RET['CREDITS'] ) ? false : true )
	);

	$inputs[] = CheckboxInput(
		issetVal( $course_period_RET['DOES_CLASS_RANK'], '' ),
		$array . '[DOES_CLASS_RANK]',
		_( 'Affects Class Rank' ),
		'',
		$new,
		button( 'check' ),
		button( 'x' )
	);

	$inputs[] = CheckboxInput(
		issetVal( $course_period_RET['DOES_HONOR_ROLL'], '' ),
		$array . '[DOES_HONOR_ROLL]',
		_( 'Affects Honor Roll' ),
		'',
		$new,
		button( 'check' ),
		button( 'x' )
	);

	$inputs[] = SelectInput(
		issetVal( $course_period_RET['GENDER_RESTRICTION'], '' ),
		$array . '[GENDER_RESTRICTION]',
		_( 'Gender Restriction' ),
		[
			'N' => _( 'None' ),
			'M' => _( 'Male' ),
			'F' => _( 'Female' ),
		],
		false
	);

	/* $inputs[] = CheckboxInput(
		$course_period_RET['HOUSE_RESTRICTION'],
		$array . '[HOUSE_RESTRICTION]',
		'Restricts House',
		'',
		$new
	); */

	return $inputs;
}


/**
 * Generate Course Period Title
 * Use the CoursePeriodSchoolPeriodsTitlePartGenerate() function to complete title!
 *
 * @since 4.9
 *
 * @param integer $cp_id   Course Period ID, set to 0 on INSERT.
 * @param array   $columns Course Period columns and values array.
 *
 * @return string Course Period Title.
 */
function CoursePeriodTitleGenerate( $cp_id, $columns )
{
	if ( empty( $cp_id )
		&& empty( $columns ) )
	{
		return '';
	}

	if ( $cp_id )
	{
		$current = DBGet( "SELECT TEACHER_ID,MARKING_PERIOD_ID,
			SHORT_NAME,TITLE
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );
	}

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

	if ( isset( $columns['SHORT_NAME'] ) )
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

	$base_title = $mp_title . $short_name . ' - ' . GetTeacher( $staff_id );

	$periods_title = '';

	if ( ! empty( $current ) )
	{
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
	}

	return $periods_title . $base_title;
}


/**
 * Generate Course Period School Periods title part
 *
 * @since 4.9
 * @since 7.0 Fix Numbered days display
 *
 * @param integer $cpsp_id Course Period School Period ID, set to 0 on INSERT.
 * @param integer $cp_id   Course Period ID.
 * @param array   $columns Course Period School Period columns and values array.
 *
 * @return string Course Period School Periods title part
 */
function CoursePeriodSchoolPeriodsTitlePartGenerate( $cpsp_id, $cp_id, $columns )
{
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

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		// FJ days numbered.
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

	$other_school_p = DBGet( "SELECT PERIOD_ID,DAYS
		FROM course_period_school_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND COURSE_PERIOD_SCHOOL_PERIODS_ID<>'" . (int) $cpsp_id . "'" );

	$periods_title = '';

	foreach ( $other_school_p as $school_p )
	{
		$school_p_title = DBGetOne( "SELECT TITLE
			FROM school_periods
			WHERE PERIOD_ID='" . (int) $school_p['PERIOD_ID'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		$nb_days = mb_strlen( $school_p['DAYS'] );

		if ( ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null
				&& $nb_days == SchoolInfo( 'NUMBER_DAYS_ROTATION' ) )
			|| ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) === null
				&& $nb_days >= 5 ) )
		{
			$periods_title .= $school_p_title . ' - ';

			continue;
		}

		// $columns_days_locale = $nb_days > 1 ? ' ' . _( 'Days' ) . ' ' :	( $nb_days == 0 ? '' : ' ' . _( 'Day' ) . ' ' );
		$columns_days_locale = ' ';

		for ( $i = 0; $i < $nb_days; $i++ )
		{
			$columns_days_locale .= mb_substr( $days_convert[mb_substr( $school_p['DAYS'], $i, 1 )], 0, 3 ) . '.';
		}

		$periods_title .= $school_p_title . $columns_days_locale . ' - ';
	}

	if ( empty( $columns['DAYS'] ) )
	{
		return $periods_title;
	}

	if ( $cpsp_id )
	{
		$school_period_title = DBGetOne( "SELECT sp.TITLE
			FROM school_periods sp,course_period_school_periods cpsp
			WHERE sp.PERIOD_ID=cpsp.PERIOD_ID
			AND cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID='" . (int) $cpsp_id . "'
			AND sp.SCHOOL_ID='" . UserSchool() . "'
			AND sp.SYEAR='" . UserSyear() . "'" );
	}
	else
	{
		$school_period_title = DBGetOne( "SELECT TITLE
			FROM school_periods
			WHERE PERIOD_ID='" . (int) $columns['PERIOD_ID'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );
	}

	$nb_days = mb_strlen( $columns['DAYS'] );

	if ( ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null
			&& $nb_days == SchoolInfo( 'NUMBER_DAYS_ROTATION' ) )
		|| ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) === null
			&& $nb_days >= 5 ) )
	{
		return $school_period_title . ' - ' . $periods_title;
	}

	// $columns_days_locale = $nb_days > 1 ? ' ' . _( 'Days' ) . ' ' : ( $nb_days == 0 ? '' : ' ' . _( 'Day' ) . ' ' );
	$columns_days_locale = ' ';

	for ( $i = 0; $i < $nb_days; $i++ )
	{
		$columns_days_locale .= mb_substr( $days_convert[mb_substr( $columns['DAYS'], $i, 1 )], 0, 3 ) . '.';
	}

	$title = $school_period_title . $columns_days_locale . ' - ' . $periods_title;

	return $title;
}

/**
 * Course Period Delete SQL queries
 *
 * @since 6.1
 *
 * @param int $course_period_id Course Period ID.
 *
 * @return string Delete SQL queries.
 */
function CoursePeriodDeleteSQL( $course_period_id )
{
	$course_period_id = intval( $course_period_id );

	$delete_sql = "UPDATE course_periods
		SET PARENT_ID=NULL
		WHERE PARENT_ID='" . (int) $course_period_id . "';";

	$delete_sql .= "DELETE FROM schedule
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "';";

	$delete_sql .= "DELETE FROM gradebook_assignments
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "';";

	$delete_sql .= "DELETE FROM course_period_school_periods
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "';";

	$delete_sql .= "DELETE FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "';";

	return $delete_sql;
}

/**
 * Course Delete SQL queries
 *
 * @since 6.1
 *
 * @param int $course_id Course ID.
 *
 * @return string Delete SQL queries.
 */
function CourseDeleteSQL( $course_id )
{
	$course_id = intval( $course_id );

	$delete_sql = "UPDATE course_periods
		SET PARENT_ID=NULL
		WHERE PARENT_ID IN (SELECT COURSE_PERIOD_ID
			FROM course_periods
			WHERE COURSE_ID='" . (int) $course_id . "');";

	$delete_sql .= "DELETE FROM course_periods
		WHERE COURSE_ID='" . (int) $course_id . "';";

	$delete_sql .= "DELETE FROM schedule
		WHERE COURSE_ID='" . (int) $course_id . "';";

	$delete_sql .= "DELETE FROM schedule_requests
		WHERE COURSE_ID='" . (int) $course_id . "';";

	$delete_sql .= "DELETE FROM gradebook_assignment_types
		WHERE COURSE_ID='" . (int) $course_id . "';";

	$delete_sql .= "DELETE FROM courses
		WHERE COURSE_ID='" . (int) $course_id . "';";

	return $delete_sql;
}

/**
 * Automatically update schedules marking period.
 *
 * On the condition scheduled (old) marking period is of greater type
 * than the new course marking period.
 * For example: FY to SEM.
 *
 * @since 11.1 Move local function from Courses.php & rename to CoursePeriodUpdateMP()
 *
 * @param  string $cp_id Course Period ID.
 * @param  string $mp_id Marking Period ID.
 * @return int    Number of schedules updated.
 */
function CoursePeriodUpdateMP( $cp_id, $mp_id )
{
	global $db_connection,
		$DatabaseType;

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
	$update = DBQuery( "UPDATE schedule SET
		MP='" . $cp_mp . "',
		MARKING_PERIOD_ID='" . (int) $mp_id . "'
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND MP IN (" . $schedule_mp_in . ")" );

	// Return number of updated schedules.
	return $DatabaseType === 'mysql' ?
		mysqli_affected_rows( $db_connection ) :
		pg_affected_rows( $update );
}

/**
 * Automatically update teacher:
 * In attendance_completed & grades_completed tables
 * Fix the false "Missing attendance" portal alerts
 *
 * @since 11.1
 *
 * @param  int $cp_id          Course Period ID.
 * @param  int $old_teacher_id Old Teacher ID.
 * @param  int $new_teacher_id New Teacher ID.
 *
 * @return bool True if teacher updated.
 */
function CoursePeriodUpdateTeacher( $cp_id, $old_teacher_id, $new_teacher_id )
{
	global $DatabaseType;

	if ( ! $cp_id
		|| ! $old_teacher_id
		|| ! $new_teacher_id
		|| $old_teacher_id == $new_teacher_id )
	{
		return false;
	}

	$where_days_sql = " AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM " .
		( $DatabaseType === 'mysql' ?
			"DAYOFWEEK(acc.SCHOOL_DATE)" :
			"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
		" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$where_days_sql = " AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
			(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE SCHOOL_DATE<=acc.SCHOOL_DATE
			AND SCHOOL_DATE>=(SELECT START_DATE
				FROM school_marking_periods
				WHERE START_DATE<=acc.SCHOOL_DATE
				AND END_DATE>=acc.SCHOOL_DATE
				AND MP='QTR'
				AND SCHOOL_ID=acc.SCHOOL_ID
				AND SYEAR=acc.SYEAR)
			AND CALENDAR_ID=cp.CALENDAR_ID)
			" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
			" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";
	}

	// Get School Periods
	$periods_RET = DBGet( "SELECT PERIOD_ID
		FROM course_period_school_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	foreach ( $periods_RET as $period )
	{
		// Update attendance_completed.
		// SQL fix duplicate key value violates unique constraint "attendance_completed_pkey"
		DBQuery( "UPDATE attendance_completed ac SET STAFF_ID='" . (int) $new_teacher_id . "'
			WHERE ac.STAFF_ID='" . (int) $old_teacher_id . "'
			AND ac.PERIOD_ID='" . (int) $period['PERIOD_ID'] . "'
			AND ac.SCHOOL_DATE IN(SELECT acc.SCHOOL_DATE
				FROM attendance_calendar acc,school_periods sp,course_periods cp,course_period_school_periods cpsp
				WHERE cpsp.PERIOD_ID='" . (int) $period['PERIOD_ID'] . "'
				AND sp.PERIOD_ID=cpsp.PERIOD_ID
				AND cp.COURSE_PERIOD_ID='" . (int) $cp_id . "'
				AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
				AND acc.CALENDAR_ID=cp.CALENDAR_ID
				" . $where_days_sql . ")
			AND ac.SCHOOL_DATE BETWEEN (SELECT START_DATE
				FROM school_marking_periods
				WHERE MARKING_PERIOD_ID=(SELECT MARKING_PERIOD_ID FROM course_periods
					WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'))
			AND (SELECT END_DATE
				FROM school_marking_periods
				WHERE MARKING_PERIOD_ID=(SELECT MARKING_PERIOD_ID FROM course_periods
					WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'))
			AND NOT EXISTS(SELECT 1
				FROM attendance_completed ac2
				WHERE ac2.STAFF_ID='" . (int) $new_teacher_id . "'
				AND ac2.PERIOD_ID='" . (int) $period['PERIOD_ID'] . "'
				AND ac2.SCHOOL_DATE=ac.SCHOOL_DATE
				AND ac2.TABLE_NAME=ac.TABLE_NAME)" );
	}

	// Update grades_completed.
	DBUpdate(
		'grades_completed',
		[ 'STAFF_ID' => (int) $new_teacher_id ],
		[ 'STAFF_ID' => (int) $old_teacher_id, 'COURSE_PERIOD_ID' => (int) $cp_id ]
	);

	return true;
}

/**
 * Automatically update credits:
 * Recalculate Credits (attempted and earned); will also recalculate GPA
 *
 * @since 11.1
 *
 * @param  int $cp_id       Course Period ID.
 * @param  int $old_credits Old Credits.
 * @param  int $new_credits New Credits.
 *
 * @return bool True if credits updated.
 */
function CoursePeriodUpdateCredits( $cp_id, $old_credits, $new_credits )
{
	global $DatabaseType;

	if ( ! $cp_id
		|| ! $old_credits
		|| ! $new_credits
		|| $old_credits == $new_credits )
	{
		return false;
	}

	$cp_mp_id = DBGetOne( "SELECT MARKING_PERIOD_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	if ( ! $cp_mp_id )
	{
		return false;
	}

	$gp_passing_value = DBGetOne( "SELECT gs.GP_PASSING_VALUE
		FROM course_periods cp,report_card_grade_scales gs
		WHERE cp.COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND cp.GRADE_SCALE_ID=gs.ID" );

	// Get all the MP's associated with the course period MP
	$mp_ids = explode( "','", trim( GetChildrenMP( GetMP( $cp_mp_id, 'MP' ), $cp_mp_id ), "'" ) );

	$mp_ids[] = $cp_mp_id;

	foreach ( (array) $mp_ids as $mp_id )
	{
		// Update student_report_card_grades will trigger t_update_mp_stats() SQL procedure.
		DBQuery( "UPDATE student_report_card_grades
			SET CREDIT_ATTEMPTED=credit('" . (int) $cp_id . "','" . (int) $mp_id . "'),
				CREDIT_EARNED=(SELECT CASE WHEN (WEIGHTED_GP>0
					AND WEIGHTED_GP>='" . (float) $gp_passing_value . "')
					THEN credit('" . (int) $cp_id . "','" . (int) $mp_id . "')
					ELSE '0' END)
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND COURSE_PERIOD_ID='" . (int) $cp_id . "'
			AND MARKING_PERIOD_ID='" . (int) $mp_id . "'" );
	}

	return true;
}
