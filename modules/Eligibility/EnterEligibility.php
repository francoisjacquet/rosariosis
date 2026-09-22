<?php

if ( ! empty( $_REQUEST['period'] ) )
{
	// @since 10.9 Set current User Course Period before Secondary Teacher logic.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

// @since 6.9 Set School Period per program.
$school_period = DBGetOne( "SELECT PERIOD_ID
	FROM course_period_school_periods
	WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY
$eligibility_config = ProgramConfig( 'eligibility' );

foreach ( (array) $eligibility_config as $value )
{
	${$value[1]['TITLE']} = $value[1]['VALUE'];
}

// Day of the week: 1 (for Monday) through 7 (for Sunday).
$today = date( 'w' ) ? date( 'w' ) : 7;

$days = [ _( 'Sunday' ), _( 'Monday' ), _( 'Tuesday' ), _( 'Wednesday' ), _( 'Thursday' ), _( 'Friday' ), _( 'Saturday' ) ];

if ( mb_strlen( $START_MINUTE ) == 1 )
{
	$START_MIN = '0' . $START_MINUTE;
}

if ( mb_strlen( $END_MINUTE ) == 1 )
{
	$END_MINUTE = '0' . $END_MINUTE;
}

$start_date = date( 'Y-m-d', time() - ( $today - $START_DAY ) * 60 * 60 * 24 );

$end_date = date( 'Y-m-d', time() + ( $END_DAY - $today ) * 60 * 60 * 24 );

$current_RET = DBGet( "SELECT ELIGIBILITY_CODE,STUDENT_ID
	FROM eligibility
	WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
	AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'", [], [ 'STUDENT_ID' ] );

if ( $_REQUEST['modfunc'] == 'gradebook' )
{
	$gradebook_config = ProgramUserConfig( 'Gradebook' );

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

	$course_period_id = UserCoursePeriod();

	$course_id = DBGetOne( "SELECT COURSE_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

	$grades_RET = DBGet( "SELECT ID,TITLE,GPA_VALUE
		FROM report_card_grades
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'", [], [ 'ID' ] );

	if ( ! empty( $gradebook_config['WEIGHT'] ) )
	{
		// @since 10.0 Use GROUP BY instead of DISTINCT ON for MySQL compatibility
		$points_RET = DBGet( "SELECT s.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,
			sum(CASE WHEN gg.POINTS<0 THEN '0' ELSE gg.POINTS END) AS PARTIAL_POINTS,
			sum(CASE WHEN gg.POINTS<0 THEN '0' ELSE ga.POINTS END) AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT
		FROM students s
		JOIN schedule ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.COURSE_PERIOD_ID='" . (int) $course_period_id . "')
		JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID='" . (int) $course_id . "' AND ga.STAFF_ID='" . User( 'STAFF_ID' ) . "') AND ga.MARKING_PERIOD_ID" .
		( isset( $gradebook_config['ELIGIBILITY_CUMULITIVE'] ) && $gradebook_config['ELIGIBILITY_CUMULITIVE'] == 'Y' ?
			" IN (" . GetChildrenMP( 'SEM', UserMP() ) . ")" :
			"='" . UserMP() . "'" ) . ")
		LEFT OUTER JOIN gradebook_grades gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID),
		gradebook_assignment_types gt
		WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
		AND gt.COURSE_ID='" . (int) $course_id . "'
		AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL)
		GROUP BY s.STUDENT_ID,ss.START_DATE,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT", [], [ 'STUDENT_ID' ] );
	}
	else
	{
		// @since 10.0 Use GROUP BY instead of DISTINCT ON for MySQL compatibility
		$points_RET = DBGet( "SELECT s.STUDENT_ID,'-1' AS ASSIGNMENT_TYPE_ID,
			sum(CASE WHEN gg.POINTS<0 THEN '0' ELSE gg.POINTS END) AS PARTIAL_POINTS,
			sum(CASE WHEN gg.POINTS<0 THEN '0' ELSE ga.POINTS END) AS PARTIAL_TOTAL,'1' AS FINAL_GRADE_PERCENT
		FROM students s
		JOIN schedule ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.COURSE_PERIOD_ID='" . (int) $course_period_id . "')
		JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID='" . (int) $course_id . "' AND ga.STAFF_ID='" . User( 'STAFF_ID' ) . "') AND ga.MARKING_PERIOD_ID" .
		( isset( $gradebook_config['ELIGIBILITY_CUMULITIVE'] ) && $gradebook_config['ELIGIBILITY_CUMULITIVE'] == 'Y' ?
			" IN (" . GetChildrenMP( 'SEM', UserMP() ) . ")" :
			"='" . UserMP() . "'" ) . ")
		LEFT OUTER JOIN gradebook_grades gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID)
		WHERE ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL)
		GROUP BY s.STUDENT_ID,ss.START_DATE", [], [ 'STUDENT_ID' ] );
	}

	if ( ! empty( $points_RET ) )
	{
		foreach ( (array) $points_RET as $student_id => $student )
		{
			$total = $total_percent = 0;

			foreach ( (array) $student as $partial_points )
			{
				if ( $partial_points['PARTIAL_TOTAL'] != 0 )
				{
					$total += $partial_points['PARTIAL_POINTS'] * $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'];
					$total_percent += $partial_points['FINAL_GRADE_PERCENT'];
				}
			}

			if ( $total_percent != 0 )
			{
				$total /= $total_percent;
			}

			$grade_id = _makeLetterGrade( $total, 0, 0, 'ID' );

			$grade = isset( $grades_RET[ $grade_id ][1] ) ? $grades_RET[ $grade_id ][1] : [];

			$code = 'PASSING';

			if ( $total_percent == 0 )
			{
				/**
				 * Excused (*) for all assignments case
				 * or only E/C (extra credit) assignments case
				 *
				 * @since 12.4 Select Incomplete code when N/A grade
				 */
				$code = 'INCOMPLETE';
			}
			elseif ( ! $grade // Grade not found
				|| $grade['GPA_VALUE'] == '0'
				|| ! $grade['GPA_VALUE'] )
			{
				$code = 'FAILING';
			}
			elseif ( $grade['GPA_VALUE'] < ( SchoolInfo( 'REPORTING_GP_SCALE' ) / 2 ) )
			{
				$code = 'BORDERLINE';
			}

			if ( ! empty( $current_RET[$student_id] ) )
			{
				DBQuery( "UPDATE eligibility
					SET ELIGIBILITY_CODE='" . $code . "'
					WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
					AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'
					AND STUDENT_ID='" . (int) $student_id . "'
					AND SYEAR='" . UserSyear() . "'" );
			}
			else
			{
				DBInsert(
					'eligibility',
					[
						'STUDENT_ID' => (int) $student_id,
						'SCHOOL_DATE' => DBDate(),
						'SYEAR' => UserSyear(),
						'PERIOD_ID' => (int) $school_period,
						'COURSE_PERIOD_ID' => (int) $course_period_id,
						'ELIGIBILITY_CODE' => $code,
					]
				);
			}
		}

		$current_RET = DBGet( "SELECT ELIGIBILITY_CODE,STUDENT_ID
			FROM eligibility
			WHERE SCHOOL_DATE
			BETWEEN '" . $start_date . "' AND '" . $end_date . "'
			AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'", [], [ 'STUDENT_ID' ] );
	}

	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	$course_period_id = UserCoursePeriod();

	// Security fix #377 IDOR: Unauthorized Cross-Roster Data Assignment
	$st_list = implode( ',', array_map( 'intval', array_keys( $_REQUEST['values'] ) ) );

	$students_RET = DBGet( "SELECT DISTINCT STUDENT_ID
		FROM schedule
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		AND STUDENT_ID IN(" . ( $st_list ? $st_list : '0' ) . ")",
		[], [ 'STUDENT_ID' ] );

	$student_ids = empty( $students_RET ) ? [] : array_keys( $students_RET );

	foreach ( (array) $_REQUEST['values'] as $student_id => $value )
	{
		if ( ! in_array( $student_id, $student_ids ) )
		{
			// Security fix #377 IDOR: Unauthorized Cross-Roster Data Assignment
			continue;
		}

		if ( ! empty( $current_RET[$student_id] ) )
		{
			DBQuery( "UPDATE eligibility
				SET ELIGIBILITY_CODE='" . $value . "'
				WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
				AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'
				AND STUDENT_ID='" . (int) $student_id . "'" );
		}
		else
		{
			DBInsert(
				'eligibility',
				[
					'STUDENT_ID' => (int) $student_id,
					'SCHOOL_DATE' => DBDate(),
					'SYEAR' => UserSyear(),
					'PERIOD_ID' => (int) $school_period,
					'COURSE_PERIOD_ID' => (int) $course_period_id,
					'ELIGIBILITY_CODE' => $value,
				]
			);
		}
	}

	$completed_RET = DBGet( "SELECT 'completed' AS COMPLETED
		FROM eligibility_completed
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND SCHOOL_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		AND PERIOD_ID='" . (int) $school_period . "'" );

	if ( empty( $completed_RET ) )
	{
		// SQL Insert eligibility completed once for All $school_period.
		DBQuery( "INSERT INTO eligibility_completed (STAFF_ID,SCHOOL_DATE,PERIOD_ID)
			SELECT '" . User( 'STAFF_ID' ) . "' AS STAFF_ID,'" . DBDate() . "' AS SCHOOL_DATE,PERIOD_ID
			FROM course_period_school_periods
			WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );
	}

	$current_RET = DBGet( "SELECT ELIGIBILITY_CODE,STUDENT_ID
		FROM eligibility
		WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'", [], [ 'STUDENT_ID' ] );

	RedirectURL( [ 'modfunc', 'values' ] );
}

$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
$extra['SELECT'] .= ",'' AS PASSING,'' AS BORDERLINE,'' AS FAILING,'' AS INCOMPLETE";

$extra['functions'] = [
	'FULL_NAME' => 'makePhotoTipMessage',
	'PASSING' => 'makeRadio',
	'BORDERLINE' => 'makeRadio',
	'FAILING' => 'makeRadio',
	'INCOMPLETE' => 'makeRadio',
];

$columns = [
	'PASSING' => _( 'Passing' ),
	'BORDERLINE' => _( 'Borderline' ),
	'FAILING' => _( 'Failing' ),
	'INCOMPLETE' => _( 'Incomplete' ),
];

$stu_RET = GetStuList( $extra );

DrawHeader( ProgramTitle() );

$cp_title = DBGetOne( "SELECT TITLE
	FROM course_periods
	WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

if ( $cp_title )
{
	// Add Course Period title header.
	DrawHeader( $cp_title );
}

/**
 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
 * Then teacher submits the form, data would be saved for CP B...
 *
 * Must be used in combination with
 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
 */
echo '<form action="' . URLEscape(
	'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&period=' . UserCoursePeriod()
) . '" method="POST">';

if ( $today > $END_DAY
	| $today < $START_DAY
	|| ( $today == $START_DAY && date( 'Gi' ) < ( $START_HOUR . $START_MINUTE ) )
	|| ( $today == $END_DAY && date( 'Gi' ) > ( $END_HOUR . $END_MINUTE ) ) )
{
	echo ErrorMessage( [ sprintf(
		_( 'You can only enter eligibility from %s %s to %s %s.' ),
		$days[ ( $START_DAY == 7 ? 0 : $START_DAY ) ],
		$START_HOUR . ':' . $START_MINUTE,
		$days[ ( $END_DAY == 7 ? 0 : $END_DAY ) ],
		$END_HOUR . ':' . $END_MINUTE
	) ], 'error' );
}
else
{
	DrawHeader(
		'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=gradebook' ) . '">' .
		_( 'Use Gradebook Grades' ) . '</a>',
		Buttons( _( 'Save' ) )
	);

	$completed_RET = DBGet( "SELECT 'completed' AS COMPLETED
		FROM eligibility_completed
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND SCHOOL_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		AND PERIOD_ID='" . (int) $school_period . "'" );

	if ( ! empty( $completed_RET ) )
	{
		$note[] = button( 'check' ) . '&nbsp;' .
			_( 'You already have entered eligibility this week for this course period.' );

		echo ErrorMessage( $note, 'note' );
	}

	$LO_columns = [
		'FULL_NAME' => _( 'Student' ),
		'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
		'GRADE_ID' => _( 'Grade Level' ),
	] + $columns;

	ListOutput( $stu_RET, $LO_columns, 'Student', 'Students' );

	echo '<div class="center">' . Buttons( _( 'Save' ) ) . '</div>';
}

echo '</form>';

/**
 * @param $value
 * @param $title
 */
function makeRadio( $value, $title )
{
	global $THIS_RET, $current_RET;

	if ( ( isset( $current_RET[$THIS_RET['STUDENT_ID']][1]['ELIGIBILITY_CODE'] )
			&& $current_RET[$THIS_RET['STUDENT_ID']][1]['ELIGIBILITY_CODE'] == $title )
		|| ( $title == 'PASSING'
			&& empty( $current_RET[$THIS_RET['STUDENT_ID']][1]['ELIGIBILITY_CODE'] ) ) )
	{
		return '<input type="radio" name="values[' . AttrEscape( $THIS_RET['STUDENT_ID'] ) . ']" value="' . AttrEscape( $title ) . '" checked />';
	}

	return '<input type="radio" name="values[' . AttrEscape( $THIS_RET['STUDENT_ID'] ) . ']" value="' . AttrEscape( $title ) . '">';
}
