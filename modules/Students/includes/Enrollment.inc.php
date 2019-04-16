<?php
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

$functions = array(
	'START_DATE' => '_makeStartInput',
	'END_DATE' => '_makeEndInput',
	'SCHOOL_ID' => '_makeSchoolInput',
);

unset( $THIS_RET );

$enrollment_RET = DBGet( "SELECT e.ID,e.ENROLLMENT_CODE,e.START_DATE,e.DROP_CODE,e.END_DATE,
		e.END_DATE AS END,e.SCHOOL_ID,e.NEXT_SCHOOL,e.CALENDAR_ID,e.GRADE_ID
	FROM STUDENT_ENROLLMENT e
	WHERE e.STUDENT_ID='" . UserStudentID() . "'
	AND e.SYEAR='" . UserSyear() . "'
	ORDER BY e.START_DATE", $functions );

$add = true;

foreach ( (array) $enrollment_RET as $value )
{
	if ( ( $value['DROP_CODE'] == ''
			|| ! $value['DROP_CODE'] )
		&& ( $value['END'] == ''
			|| ! $value['END'] ) )
	{
		$add = false;
	}
}

if ( $add )
{
	$link['add']['html'] = array(
		'START_DATE' => _makeStartInput( '','START_DATE' ),
		'SCHOOL_ID' => _makeSchoolInput( '', 'SCHOOL_ID' ),
	);
}

$columns = array(
	'START_DATE' => _( 'Attendance Start Date this School Year' ),
	'END_DATE' => _( 'Dropped' ),
	'SCHOOL_ID' => _( 'School' ),
);

$schools_RET = DBGet( "SELECT ID,TITLE
	FROM SCHOOLS
	WHERE ID!='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'" );

$next_school_options = array(
	UserSchool() => _( 'Next grade at current school' ),
	'0' => _( 'Retain' ),
	'-1' => _( 'Do not enroll after this school year' ),
);

foreach ( (array) $schools_RET as $school )
{
	$next_school_options[ $school['ID'] ] = $school['TITLE'];
}

$calendars_RET = DBGet( "SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE
	FROM ATTENDANCE_CALENDARS
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY DEFAULT_CALENDAR ASC" );

$calendar_options = array();

foreach ( (array) $calendars_RET as $calendar )
{
	$calendar_options[ $calendar['CALENDAR_ID'] ] = $calendar['TITLE'];
}

$gradelevels_RET = DBGet( "SELECT ID,TITLE
	FROM SCHOOL_GRADELEVELS
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER" );

$gradelevel_options = array();

foreach ( (array) $gradelevels_RET as $gradelevel )
{
	$gradelevel_options[ $gradelevel['ID'] ] = $gradelevel['TITLE'];
}

if ( $_REQUEST['student_id']!== 'new' && ! empty( $enrollment_RET ))
{
	$id = $enrollment_RET[count($enrollment_RET)]['ID'];

	$next_school = $enrollment_RET[count($enrollment_RET)]['NEXT_SCHOOL'];
	$calendar = $enrollment_RET[count($enrollment_RET)]['CALENDAR_ID'];
	$gradelevel_id = $enrollment_RET[count($enrollment_RET)]['GRADE_ID'];

	$div = true;
}
else
{
	$id = 'new';

	$next_school = UserSchool();
	$calendar = $calendars_RET[1]['CALENDAR_ID'];
	$gradelevel_id = $gradelevels_RET[1]['ID'];

	$div = false;
}

echo '<hr />';

echo '<table class="enrollment width-100p valign-top fixed-col"><tr class="st"><td>';

echo SelectInput(
	$gradelevel_id,
	'values[STUDENT_ENROLLMENT][' . $id . '][GRADE_ID]',
	_( 'Grade Level' ),
	$gradelevel_options,
	false,
	'required',
	$div
);

echo '</td><td>';

echo SelectInput(
	$calendar,
	'values[STUDENT_ENROLLMENT][' . $id . '][CALENDAR_ID]',
	_( 'Calendar' ),
	$calendar_options,
	false,
	'required',
	$div
);

echo '</td><td>';

echo SelectInput(
	$next_school,
	'values[STUDENT_ENROLLMENT][' . $id . '][NEXT_SCHOOL]',
	_( 'Rolling / Retention Options' ),
	$next_school_options,
	false,
	'required',
	$div
);

echo '</td></tr></table>';

if ( $PopTable_opened )
{
	PopTable( 'footer' );
}

ListOutput(
	$enrollment_RET,
	$columns,
	'Enrollment Record',
	'Enrollment Records',
	$link,
	array(),
	array( 'save' => false, 'search' => false )
);

if ( $PopTable_opened )
{
	echo '<table><tr><td>';
}
