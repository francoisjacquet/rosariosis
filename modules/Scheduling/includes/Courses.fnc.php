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
 *
 * @param int $teacher_id Teacher ID.
 *
 * @return boolean True if confliciting days for the same period, else false.
 */
function CoursePeriodTeacherConflictCheck( $teacher_id )
{
	if ( ! $teacher_id )
	{
		return false;
	}

	// Get school periods for Teacher course periods.
	$school_periods_RET = DBGet( "SELECT cpsp.PERIOD_ID,cpsp.DAYS
		FROM COURSE_PERIOD_SCHOOL_PERIODS cpsp,COURSE_PERIODS cp
		WHERE cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND TEACHER_ID='" . $teacher_id . "'" );

	if ( empty( $school_periods_RET )
		|| count( $school_periods_RET ) < 2 )
	{
		return false;
	}

	$school_periods = array();

	foreach ( (array) $school_periods_RET as $school_period )
	{
		if ( isset( $school_periods[ $school_period['PERIOD_ID'] ] ) )
		{
			$days_array = str_split( $school_periods[ $school_period['PERIOD_ID'] ] );

			$days_array2 = str_split( $school_period['DAYS'] );

			$common_days = array_intersect( $days_array, $days_array2 );

			if ( $common_days )
			{
				return true;
			}
		}
		else
		{
			$school_periods[ $school_period['PERIOD_ID'] ] = '';
		}

		$school_periods[ $school_period['PERIOD_ID'] ] .= $school_period['DAYS'];
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
	$attendance_html = '<table class="cellspacing-0 width-100p"><tr class="st">';

	$attendance_cat = array();

	$i = 0;

	$categories_RET = DBGet( "SELECT '0' AS ID,'" . _( 'Attendance' ) . "' AS TITLE
		UNION SELECT ID,TITLE
		FROM ATTENDANCE_CODE_CATEGORIES
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	foreach ( (array) $categories_RET as $category )
	{
		if ( $i % 2 === 0 )
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
		) . '&nbsp;</td>';

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
 *
 * @param array  $course_period_RET Course Period data array from DB.
 * @param string $array             Input name prefix, before value index (array).
 * @param bool   $new               Is new input?
 *
 * @return array Course Period option inputs
 */
function CoursePeriodOptionInputs( $course_period_RET, $array, $new )
{
	$inputs = array();

	$inputs[] = CoursePeriodAttendanceInput( $course_period_RET['DOES_ATTENDANCE'], $array );

	$inputs[] = CheckboxInput(
		empty( $course_period_RET ) ? '' : $course_period_RET['DOES_HONOR_ROLL'],
		$array . '[DOES_HONOR_ROLL]',
		_( 'Affects Honor Roll' ),
		'',
		$new,
		button( 'check' ),
		button( 'x' )
	);

	$inputs[] = CheckboxInput(
		empty( $course_period_RET ) ? '' : $course_period_RET['DOES_CLASS_RANK'],
		$array . '[DOES_CLASS_RANK]',
		_( 'Affects Class Rank' ),
		'',
		$new,
		button( 'check' ),
		button( 'x' )
	);

	$inputs[] = SelectInput(
		empty( $course_period_RET ) ? '' : $course_period_RET['GENDER_RESTRICTION'],
		$array . '[GENDER_RESTRICTION]',
		_( 'Gender Restriction' ),
		array(
			'N' => _( 'None' ),
			'M' => _( 'Male' ),
			'F' => _( 'Female' ),
		),
		false
	);

	$options_RET = DBGet( "SELECT TITLE,ID
		FROM REPORT_CARD_GRADE_SCALES
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	$options = array();

	foreach ( (array) $options_RET as $option )
	{
		$options[$option['ID']] = $option['TITLE'];
	}

	$inputs[] = SelectInput(
		empty( $course_period_RET ) ? '' : $course_period_RET['GRADE_SCALE_ID'],
		$array . '[GRADE_SCALE_ID]',
		_( 'Grading Scale' ),
		$options,
		_( 'Not Graded' )
	);

	$inputs[] = TextInput(
		is_null( $course_period_RET['CREDITS'] ) ? '1' : (float) $course_period_RET['CREDITS'],
		$array . '[CREDITS]',
		_( 'Credits' ),
		'size=4 maxlength=5',
		( is_null( $course_period_RET['CREDITS'] ) ? false : true )
	);

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

	$inputs[] = SelectInput(
		empty( $course_period_RET ) ? '' : $course_period_RET['CALENDAR_ID'],
		$array . '[CALENDAR_ID]',
		_( 'Calendar' ),
		$options,
		false,
		'required'
	);

	$inputs[] = CheckboxInput(
		empty( $course_period_RET ) ? '' : $course_period_RET['HALF_DAY'],
		$array . '[HALF_DAY]',
		_( 'Half Day' ),
		'',
		$new,
		button( 'check' ),
		button( 'x' )
	);

	$inputs[] = CheckboxInput(
		empty( $course_period_RET ) ? '' : $course_period_RET['DOES_BREAKOFF'],
		$array . '[DOES_BREAKOFF]',
		_( 'Allow Teacher Grade Scale' ),
		'',
		$new,
		button( 'check' ),
		button( 'x' )
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
 * Use the CoursePeriodSchoolPeriodsTitleGenerate() function to complete title!
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
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $cp_id . "'" );
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

	$base_title = $mp_title . $short_name . ' - ';

	$base_title = DBEscapeString( $base_title . GetTeacher( $staff_id ) );

	$periods_title = '';

	if ( $current )
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
