<?php
if ( ! empty( $_REQUEST['period'] ) )
{
	// @since 10.9 Set current User Course Period before Secondary Teacher logic.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

if ( ! empty( $_REQUEST['student_id'] ) )
{
	if ( $_REQUEST['student_id'] != UserStudentID() )
	{
		SetUserStudentID( $_REQUEST['student_id'] );
	}
}
elseif ( UserStudentID() )
{
	unset( $_SESSION['student_id'] );
}

$_REQUEST['include_all_courses'] = issetVal( $_REQUEST['include_all_courses'], '' );
$_REQUEST['include_inactive'] = issetVal( $_REQUEST['include_inactive'], '' );

DrawHeader( _( 'Gradebook' ) . ' - ' . ProgramTitle() );

$max_allowed = ( Preferences( 'ANOMALOUS_MAX', 'Gradebook' ) ?
	Preferences( 'ANOMALOUS_MAX', 'Gradebook' ) / 100 :
	1 );

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

DrawHeader(
	CheckBoxOnclick(
		'include_all_courses',
		_( 'Include All Courses' )
	),
	CheckBoxOnclick(
		'include_inactive',
		_( 'Include Inactive Students' )
	)
);

if ( empty( $_REQUEST['missing'] )
	&& empty( $_REQUEST['negative'] )
	&& empty( $_REQUEST['max_allowed'] ) )
{
	$_REQUEST['missing'] = $_REQUEST['negative'] = $_REQUEST['max_allowed'] = 'Y';
}

DrawHeader(
	_( 'Include' ) . ': <label>' .
	CheckBoxOnclick( 'missing' ) . ' ' . _( 'Missing Grades' ) . '</label> &nbsp;<label>' .
	CheckBoxOnclick( 'negative' ) . ' ' . _( 'Excused and Negative Grades' ) . '</label> &nbsp;<label>' .
	CheckBoxOnclick( 'max_allowed' ) . ' ' .
	sprintf(
		_( 'Exceed %d%% and Extra Credit Grades' ),
		( $max_allowed * 100 )
	) . '</label>'
);

echo '</form>';

$extra['WHERE'] = issetVal( $extra['WHERE'], '' );

if ( UserStudentID() )
{
	$extra['WHERE'] .= " AND s.STUDENT_ID='" . UserStudentID() . "'";
}

$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
$extra['SELECT'] .= ",gg.POINTS,gg.COMMENT,ga.ASSIGNMENT_TYPE_ID,ga.ASSIGNMENT_ID,gt.TITLE AS TYPE_TITLE,ga.TITLE,ga.POINTS AS TOTAL_POINTS,'' AS LETTER_GRADE";

$extra['FROM'] = " JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) AND ga.MARKING_PERIOD_ID='" . UserMP() . "') LEFT OUTER JOIN gradebook_grades gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),gradebook_assignment_types gt";

$extra['WHERE'] .= ' AND (';

// missing

if ( ! empty( $_REQUEST['missing'] ) )
{
	$extra['WHERE'] .= 'gg.POINTS IS NULL AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)) OR ';
}

// excused or negative

if ( ! empty( $_REQUEST['negative'] ) )
{
	$extra['WHERE'] .= 'gg.POINTS<0 OR ';
}

// greater than max percent or extra credit

if ( ! empty( $_REQUEST['max_allowed'] ) )
{
	$extra['WHERE'] .= 'gg.POINTS>ga.POINTS*' . $max_allowed . ' OR ';
}

$extra['WHERE'] .= 'FALSE) AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID';
$extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";

if ( $_REQUEST['include_all_courses'] == 'Y' )
{
	$extra['SELECT'] .= ',cp.COURSE_PERIOD_ID,cp.TITLE AS COURSE_TITLE';
	$extra['all_courses'] = 'Y';
}

$extra['functions'] = [
	'POINTS' => '_makePoints',
	'TYPE_TITLE' => '_makeTitle',
	'TITLE' => '_makeTitle',
];

if ( ! UserStudentID() )
{
	$extra['group'] = [ 'STUDENT_ID' ];
}

$students_RET = GetStuList( $extra );
//echo '<pre>'; var_dump($students_RET); echo '</pre>';

if ( UserStudentID() )
{
	$columns = [ 'POINTS' => _( 'Problem' ) ];
	$link = [];
	$group = [];
}
else
{
	$columns = [
		'FULL_NAME' => _( 'Name' ),
		'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
		'POINTS' => _( 'Problem' ),
	];

	$link = [
		'FULL_NAME' => [
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&include_all_courses=' . $_REQUEST['include_all_courses'] .
				'&include_inactive=' . $_REQUEST['include_inactive'] .
				'&missing=' . issetVal( $_REQUEST['missing'], '' ) .
				'&negative=' . issetVal( $_REQUEST['negative'], '' ) .
				'&max_allowed=' . issetVal( $_REQUEST['max_allowed'], '' ),
			'variables' => [ 'student_id' => 'STUDENT_ID' ],
		]
	];

	if ( $_REQUEST['include_all_courses'] == 'Y' )
	{
		$link['FULL_NAME']['variables']['period'] = 'COURSE_PERIOD_ID';
	}

	$group = [ 'STUDENT_ID' ];
}

if ( $_REQUEST['include_all_courses'] == 'Y' )
{
	$columns += [ 'COURSE_TITLE' => _( 'Course' ) ];
}

$columns += [ 'TYPE_TITLE' => _( 'Category' ), 'TITLE' => _( 'Assignment' ), 'COMMENT' => _( 'Comment' ) ];

if ( $_REQUEST['include_inactive'] )
{
	$columns += [ 'ACTIVE' => _( 'School Status' ), 'ACTIVE_SCHEDULE' => _( 'Course Status' ) ];
}

$modname = str_replace( 'AnomalousGrades', 'Grades', $_REQUEST['modname'] );

if ( AllowUse( $modname ) )
{
	$link += [
		'TITLE' => [
			'link' => 'Modules.php?modname=' . $modname .
			'&include_inactive=' . $_REQUEST['include_inactive'],
			'variables' => [
				'type_id' => 'ASSIGNMENT_TYPE_ID',
				'assignment_id' => 'ASSIGNMENT_ID',
				'student_id' => 'STUDENT_ID',
			],
		],
	];

	if ( $_REQUEST['include_all_courses'] == 'Y' )
	{
		$link['TITLE']['variables']['period'] = 'COURSE_PERIOD_ID';
	}
}

//FJ add translation

if ( UserStudentID() )
{
	ListOutput( $students_RET, $columns, 'Anomalous Grade', 'Anomalous Grades', $link, $group, [ 'center' => false, 'save' => false, 'search' => false ] );
}
else
{
	ListOutput( $students_RET, $columns, 'Student with Anomalous Grades', 'Students with Anomalous Grades', $link, $group, [ 'center' => false, 'save' => false, 'search' => false ] );
}

/**
 * @param $value
 * @param $column
 */
function _makePoints( $value, $column )
{
	global $THIS_RET;

	if ( $value == '' )
	{
		return '<span style="color:#ff0000">' . _( 'Missing' ) . '</span>';
	}
	elseif ( $value == '-1' )
	{
		return '<span style="color:#00a000">' . _( 'Excused' ) . '</span>';
	}
	elseif ( $value < 0 )
	{
		return '<span style="color:#ff0000">' . _( 'Negative' ) . '</span>';
	}
	elseif ( $THIS_RET['TOTAL_POINTS'] == 0 )
	{
		return '<span style="color:#0000ff">' . _( 'Extra Credit' ) . '</span>';
	}

	return number_format(  ( $value / $THIS_RET['TOTAL_POINTS'] ) * 100, 0 ) . '%';
}

/**
 * Make Assignment Title
 * Truncate Assignment title to 36 chars
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 10.8
 *
 * @param  string $value  Title value.
 * @param  string $column Column. Defaults to 'TITLE'.
 *
 * @return string         Assignment title truncated to 36 chars.
 */
function _makeTitle( $value, $column = 'TITLE' )
{
	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}
