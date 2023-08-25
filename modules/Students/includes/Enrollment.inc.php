<?php
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

$functions = [
	'START_DATE' => '_makeStartInput',
	'END_DATE' => '_makeEndInput',
	'SCHOOL_ID' => '_makeSchoolInput',
];

unset( $THIS_RET );

// SQL ORDER BY fix issue when Transferring to another school & new start date is <= old start date.
$enrollment_RET = DBGet( "SELECT e.ID,e.ENROLLMENT_CODE,e.START_DATE,e.DROP_CODE,e.END_DATE,
		e.END_DATE AS END,e.SCHOOL_ID,e.NEXT_SCHOOL,e.CALENDAR_ID,e.GRADE_ID
	FROM student_enrollment e
	WHERE e.STUDENT_ID='" . UserStudentID() . "'
	AND e.SYEAR='" . UserSyear() . "'
	ORDER BY e.END_DATE IS NULL,e.END_DATE,e.START_DATE IS NULL,e.START_DATE", $functions );

$add = true;

foreach ( (array) $enrollment_RET as $value )
{
	if ( $value['END'] == ''
		|| ! $value['END'] )
	{
		$add = false;
	}
}

$link = [];

if ( $add )
{
	$link['add']['html'] = [
		'START_DATE' => _makeStartInput( '','START_DATE' ),
		'SCHOOL_ID' => _makeSchoolInput( '', 'SCHOOL_ID' ),
	];
}

$columns = [
	'START_DATE' => _( 'Attendance Start Date this School Year' ),
	'END_DATE' => _( 'Dropped' ),
	'SCHOOL_ID' => _( 'School' ),
];

$schools_RET = DBGet( "SELECT ID,TITLE
	FROM schools
	WHERE ID!='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'" );

$next_school_options = [
	UserSchool() => _( 'Next grade at current school' ),
	'0' => _( 'Retain' ),
	'-1' => _( 'Do not enroll after this school year' ),
];

foreach ( (array) $schools_RET as $school )
{
	$next_school_options[ $school['ID'] ] = $school['TITLE'];
}

$calendars_RET = DBGet( "SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE
	FROM attendance_calendars
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY DEFAULT_CALENDAR IS NULL,DEFAULT_CALENDAR ASC,TITLE" );

$calendar_options = [];

foreach ( (array) $calendars_RET as $calendar )
{
	$calendar_options[ $calendar['CALENDAR_ID'] ] = $calendar['TITLE'];
}

$gradelevels_RET = DBGet( "SELECT ID,TITLE
	FROM school_gradelevels
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

$gradelevel_options = [];

foreach ( (array) $gradelevels_RET as $gradelevel )
{
	$gradelevel_options[ $gradelevel['ID'] ] = $gradelevel['TITLE'];
}

if ( $_REQUEST['student_id'] !== 'new' && ! empty( $enrollment_RET ) )
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
	$calendar = issetVal( $calendars_RET[1]['CALENDAR_ID'] );
	$gradelevel_id = issetVal( $gradelevels_RET[1]['ID'] );

	$div = false;
}

echo '<hr>';

echo '<table class="enrollment width-100p valign-top fixed-col"><tr class="st"><td>';

echo SelectInput(
	$gradelevel_id,
	'values[student_enrollment][' . $id . '][GRADE_ID]',
	_( 'Grade Level' ),
	$gradelevel_options,
	false,
	'required',
	$div
);

echo '</td><td>';

echo SelectInput(
	$calendar,
	'values[student_enrollment][' . $id . '][CALENDAR_ID]',
	_( 'Calendar' ),
	$calendar_options,
	false,
	'required',
	$div
);

echo '</td><td>';

echo SelectInput(
	$next_school,
	'values[student_enrollment][' . $id . '][NEXT_SCHOOL]',
	_( 'Rolling / Retention Options' ),
	$next_school_options,
	false,
	'required',
	$div
);

$can_enroll_next_syear = AllowEdit()
	&& User( 'PROFILE' ) === 'admin'
	&& $id !== 'new'
	&& StudentCanEnrollNextSchoolYear( UserStudentID() );

if ( $_REQUEST['modfunc'] === 'enroll_next_syear'
	&& $can_enroll_next_syear )
{
	// @since 10.2 Add "Enroll student for next school year"
	StudentEnrollNextSchoolYear( UserStudentID() );

	$can_enroll_next_syear = false;

	// Remove modfunc from URL & redirect.
	RedirectURL( 'modfunc' );
}

if ( $can_enroll_next_syear
	&& ! is_null( $next_school ) )
{
	// @since 10.2 Add "Enroll student for next school year"
	$enroll_next_syear_link = PreparePHP_SELF( [], [], [ 'modfunc' => 'enroll_next_syear' ] );

	echo '<a href="' . $enroll_next_syear_link . '">' . _( 'Enroll student for next school year' ) . '</a>';
}


echo '</td></tr></table>';

if ( ! empty( $PopTable_opened ) )
{
	PopTable( 'footer' );
}

ListOutput(
	$enrollment_RET,
	$columns,
	'Enrollment Record',
	'Enrollment Records',
	$link,
	[],
	[ 'save' => false, 'search' => false ]
);

if ( ! empty( $PopTable_opened ) )
{
	echo '<div><table><tr><td>';
}
