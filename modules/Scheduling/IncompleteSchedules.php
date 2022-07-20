<?php
$periods_RET = DBGet( "SELECT PERIOD_ID,TITLE,SHORT_NAME
	FROM school_periods
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

/*
$period_select =  "<select name=period><option value=''>All</option>";
foreach ( (array) $periods_RET as $period)
$period_select .= "<option value=$period[PERIOD_ID]".(($_REQUEST['period']==$period['PERIOD_ID'])?' selected':'').">".$period['TITLE']."</option>";
$period_select .= "</select>";
 */

DrawHeader( ProgramTitle() );

if ( ! empty( $period_select ) )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';
	DrawHeader( $period_select );
	echo '</form>';
}

Widgets( 'course' );
Widgets( 'request' );

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	$extra2 = $extra;

	$extra2['SELECT'] .= ',sp.PERIOD_ID';
	//FJ multiple school periods for a course period
	//$extra['FROM'] .= ',school_periods sp,schedule ss,course_periods cp';
	$extra2['FROM'] .= ',school_periods sp,schedule ss,course_periods cp,course_period_school_periods cpsp';
	/*$extra['WHERE'] .= ' AND (\''.DBDate().'\' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL) AND ss.SCHOOL_ID=ssm.SCHOOL_ID AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',UserMP()).') AND ss.STUDENT_ID=ssm.STUDENT_ID AND ss.SYEAR=ssm.SYEAR AND ss.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.PERIOD_ID=sp.PERIOD_ID ';*/
	$extra2['WHERE'] .= ' AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND (\'' . DBDate() . '\' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL) AND ss.SCHOOL_ID=ssm.SCHOOL_ID AND ss.MARKING_PERIOD_ID IN (' . GetAllMP( 'QTR', UserMP() ) . ') AND ss.STUDENT_ID=ssm.STUDENT_ID AND ss.SYEAR=ssm.SYEAR AND ss.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID=sp.PERIOD_ID ';
	//if (UserStudentID())
	//	$extra['WHERE'] .= " AND s.STUDENT_ID='".UserStudentID()."' ";
	$extra2['group'] = [ 'STUDENT_ID', 'PERIOD_ID' ];

	$schedule_RET = GetStuList( $extra2 );
}

$extra['new'] = true;

foreach ( (array) $periods_RET as $period )
{
	$extra['SELECT'] .= ',NULL AS PERIOD_' . $period['PERIOD_ID'];

	// $extra['columns_after']['PERIOD_' . $period['PERIOD_ID']] = $period['TITLE'];

	// Use Period Short Name to gain space.
	$period_column_label = $period['SHORT_NAME'] ? $period['SHORT_NAME'] : $period['TITLE'];

	$extra['columns_after']['PERIOD_' . $period['PERIOD_ID']] = $period_column_label;

	$extra['functions']['PERIOD_' . $period['PERIOD_ID']] = '_preparePeriods';
}

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	Search( 'student_id', $extra );
}
else
{
	if ( ! empty( $_ROSARIO['SearchTerms'] ) )
	{
		DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );
	}

	$students_RET = GetStuList( $extra );
	$bad_students[0] = [];

	foreach ( (array) $students_RET as $student )
	{
		if ( empty( $schedule_RET[$student['STUDENT_ID']] )
			|| count( (array) $schedule_RET[$student['STUDENT_ID']] ) != count( (array) $periods_RET ) )
		{
			$bad_students[] = $student;
		}
	}

	if ( ! isset( $extra['columns_after'] ) || ! is_array( $extra['columns_after'] ) )
	{
		$extra['columns_after'] = [];
	}

	unset( $bad_students[0] );

	if ( AllowUse( 'Scheduling/Schedule.php' ) )
	{
		$link['FULL_NAME']['link'] = "Modules.php?modname=Scheduling/Schedule.php";
		$link['FULL_NAME']['variables'] = [ 'student_id' => 'STUDENT_ID' ];

		if ( isset( $_REQUEST['_search_all_schools'] )
			&& $_REQUEST['_search_all_schools'] === 'Y' )
		{
			$link['FULL_NAME']['variables']['school_id'] = 'SCHOOL_ID';
		}
	}
	else
	{
		$link = [];
	}

	ListOutput(
		$bad_students,
		[
			'FULL_NAME' => _( 'Student' ),
			'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
			'GRADE_ID' => _( 'Grade Level' )
		] + $extra['columns_after'],
		'Student with an incomplete schedule',
		'Students with incomplete schedules',
		$link
	);
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _preparePeriods( $value, $name )
{
	global $THIS_RET, $schedule_RET;

	$period_id = mb_substr( $name, 7 );

	if ( empty( $schedule_RET[$THIS_RET['STUDENT_ID']][$period_id] ) )
	{
		return isset( $_REQUEST['LO_save'] ) ? _( 'No' ) : button( 'x' );
	}

	return isset( $_REQUEST['LO_save'] ) ? _( 'Yes' ) : button( 'check' );
}
