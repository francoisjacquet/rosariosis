<?php
// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY
$eligibility_config = ProgramConfig( 'eligibility' );

foreach ( (array) $eligibility_config as $value )
{
	${$value[1]['TITLE']} = $value[1]['VALUE'];
}

switch ( date( 'D' ) )
{
	case 'Mon':
		$today = 1;
		break;
	case 'Tue':
		$today = 2;
		break;
	case 'Wed':
		$today = 3;
		break;
	case 'Thu':
		$today = 4;
		break;
	case 'Fri':
		$today = 5;
		break;
	case 'Sat':
		$today = 6;
		break;
	case 'Sun':
		$today = 7;
		break;
}

$days = array( _( 'Sunday' ), _( 'Monday' ), _( 'Tuesday' ), _( 'Wednesday' ), _( 'Thursday' ), _( 'Friday' ), _( 'Saturday' ) );

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

$current_RET = DBGet( "SELECT ELIGIBILITY_CODE,STUDENT_ID FROM ELIGIBILITY WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'", array(), array( 'STUDENT_ID' ) );

if ( $_REQUEST['modfunc'] == 'gradebook' )
{
	$gradebook_config = ProgramUserConfig( 'Gradebook' );

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

	$course_period_id = UserCoursePeriod();
	$course_id = DBGet( "SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );
	$course_id = $course_id[1]['COURSE_ID'];

	$grades_RET = DBGet( "SELECT ID,TITLE,GPA_VALUE FROM REPORT_CARD_GRADES WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "'", array(), array( 'ID' ) );

	if ( $gradebook_config['WEIGHT'] == 'Y' )
	{
		$points_RET = DBGet( "SELECT DISTINCT ON (s.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID) s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ) ) . ") AS PARTIAL_POINTS,sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . ") AS PARTIAL_TOTAL, gt.FINAL_GRADE_PERCENT
		FROM STUDENTS s
		JOIN SCHEDULE ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.COURSE_PERIOD_ID='" . $course_period_id . "')
		JOIN GRADEBOOK_ASSIGNMENTS ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID='" . $course_id . "' AND ga.STAFF_ID='" . User( 'STAFF_ID' ) . "') AND ga.MARKING_PERIOD_ID" . ( $gradebook_config['ELIGIBILITY_CUMULITIVE'] == 'Y' ? " IN (" . GetChildrenMP( 'SEM', UserMP() ) . ")" : "='" . UserMP() . "'" ) . ")
		LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID),
		GRADEBOOK_ASSIGNMENT_TYPES gt
		WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
		AND gt.COURSE_ID='" . $course_id . "'
		AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL)
		GROUP BY s.STUDENT_ID,ss.START_DATE,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT", array(), array( 'STUDENT_ID' ) );
	}
	else
	{
		$points_RET = DBGet( "SELECT DISTINCT ON (s.STUDENT_ID) s.STUDENT_ID,'-1' AS ASSIGNMENT_TYPE_ID,sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ) ) . ") AS PARTIAL_POINTS,sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . ") AS PARTIAL_TOTAL,'1' AS FINAL_GRADE_PERCENT
		FROM STUDENTS s
		JOIN SCHEDULE ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.COURSE_PERIOD_ID='" . $course_period_id . "')
		JOIN GRADEBOOK_ASSIGNMENTS ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID='" . $course_id . "' AND ga.STAFF_ID='" . User( 'STAFF_ID' ) . "') AND ga.MARKING_PERIOD_ID" . ( $gradebook_config['ELIGIBILITY_CUMULITIVE'] == 'Y' ? " IN (" . GetChildrenMP( 'SEM', UserMP() ) . ")" : "='" . UserMP() . "'" ) . ")
		LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID)
		WHERE ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL)
		GROUP BY s.STUDENT_ID,ss.START_DATE", array(), array( 'STUDENT_ID' ) );
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

			$grade = $grades_RET[_makeLetterGrade( $total, 0, 0, 'ID' )][1];

			if ( $grade['GPA_VALUE'] == '0' || ! $grade['GPA_VALUE'] )
			{
				$code = 'FAILING';
			}
			elseif ( $grade['GPA_VALUE'] < ( SchoolInfo( 'REPORTING_GP_SCALE' ) / 2 ) )
			{
				$code = 'BORDERLINE';
			}
			else
			{
				$code = 'PASSING';
			}

			if ( $current_RET[$student_id] )
			{
				$sql = "UPDATE ELIGIBILITY SET ELIGIBILITY_CODE='" . $code . "' WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "' AND STUDENT_ID='" . $student_id . "'";
			}
			else
			{
				$sql = "INSERT INTO ELIGIBILITY (STUDENT_ID,SCHOOL_DATE,SYEAR,PERIOD_ID,COURSE_PERIOD_ID,ELIGIBILITY_CODE) values('" . $student_id . "','" . DBDate() . "','" . UserSyear() . "','" . UserPeriod() . "','" . $course_period_id . "','" . $code . "')";
			}

			DBQuery( $sql );
		}

		$current_RET = DBGet( "SELECT ELIGIBILITY_CODE,STUDENT_ID FROM ELIGIBILITY WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'", array(), array( 'STUDENT_ID' ) );
	}
}

if ( ! empty( $_REQUEST['values'] )
	&& ! empty( $_POST['values'] ) )
{
	$course_period_id = UserCoursePeriod();

	foreach ( (array) $_REQUEST['values'] as $student_id => $value )
	{
		if ( $current_RET[$student_id] )
		{
			$sql = "UPDATE ELIGIBILITY SET ELIGIBILITY_CODE='" . $value . "' WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND PERIOD_ID='" . UserPeriod() . "' AND STUDENT_ID='" . $student_id . "'";
		}
		else
		{
			$sql = "INSERT INTO ELIGIBILITY (STUDENT_ID,SCHOOL_DATE,SYEAR,PERIOD_ID,COURSE_PERIOD_ID,ELIGIBILITY_CODE) values('" . $student_id . "','" . DBDate() . "','" . UserSyear() . "','" . UserPeriod() . "','" . $course_period_id . "','" . $value . "')";
		}

		DBQuery( $sql );
	}

	$RET = DBGet( "SELECT 'completed' AS COMPLETED FROM ELIGIBILITY_COMPLETED WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "' AND SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND PERIOD_ID='" . UserPeriod() . "'" );

	if ( ! empty( $RET ) )
	{
		DBQuery( "INSERT INTO ELIGIBILITY_COMPLETED (STAFF_ID,SCHOOL_DATE,PERIOD_ID) values('" . User( 'STAFF_ID' ) . "','" . DBDate() . "','" . UserPeriod() . "')" );
	}

	$current_RET = DBGet( "SELECT ELIGIBILITY_CODE,STUDENT_ID FROM ELIGIBILITY WHERE SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND PERIOD_ID='" . UserPeriod() . "'", array(), array( 'STUDENT_ID' ) );
}

$extra['SELECT'] .= ",'' AS PASSING,'' AS BORDERLINE,'' AS FAILING,'' AS INCOMPLETE";
$extra['functions'] = array( 'PASSING' => 'makeRadio', 'BORDERLINE' => 'makeRadio', 'FAILING' => 'makeRadio', 'INCOMPLETE' => 'makeRadio' );
$columns = array( 'PASSING' => _( 'Passing' ), 'BORDERLINE' => _( 'Borderline' ), 'FAILING' => _( 'Failing' ), 'INCOMPLETE' => _( 'Incomplete' ) );

$stu_RET = GetStuList( $extra );

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';
DrawHeader( ProgramTitle() );

if ( $today > $END_DAY || $today < $START_DAY || ( $today == $START_DAY && date( 'Gi' ) < ( $START_HOUR . $START_MINUTE ) ) || ( $today == $END_DAY && date( 'Gi' ) > ( $END_HOUR . $END_MINUTE ) ) )
{
	echo ErrorMessage( array( sprintf( _( 'You can only enter eligibility from %s %s to %s %s.' ), $days[$START_DAY], $START_HOUR . ':' . $START_MINUTE, $days[$END_DAY], $END_HOUR . ':' . $END_MINUTE ) ), 'error' );
}
else
{
	DrawHeader(
		'<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=gradebook">' .
		_( 'Use Gradebook Grades' ) . '</a>',
		Buttons( _( 'Save' ) )
	);

	$LO_columns = array(
		'FULL_NAME' => _( 'Student' ),
		'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
		'GRADE_ID' => _( 'Grade Level' ),
	) + $columns;

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

	if (  ( isset( $current_RET[$THIS_RET['STUDENT_ID']][1]['ELIGIBILITY_CODE'] ) && $current_RET[$THIS_RET['STUDENT_ID']][1]['ELIGIBILITY_CODE'] == $title ) || ( $title == 'PASSING' && ! $current_RET[$THIS_RET['STUDENT_ID']][1]['ELIGIBILITY_CODE'] ) )
	{
		return '<input type="radio" name="values[' . $THIS_RET['STUDENT_ID'] . ']" value="' . $title . '" checked />';
	}
	else
	{
		return '<input type="radio" name="values[' . $THIS_RET['STUDENT_ID'] . ']" value="' . $title . '">';
	}
}
