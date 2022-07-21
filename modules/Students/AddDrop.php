<?php
require_once 'ProgramFunctions/TipMessage.fnc.php';

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

echo '<form action="' . PreparePHP_SELF() . '" method="GET">';

DrawHeader(
	_( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) . ' ' .
	_( 'to' ) . ' ' . PrepareDate( $end_date, '_end', false ) . ' ' .
	SubmitButton( _( 'Go' ) ) );

echo '</form>';

$schools_where_sql = '';

// Search All Schools.
if ( User( 'SCHOOLS' ) )
{
	$schools_where_sql = " AND se.SCHOOL_ID IN (" . mb_substr( str_replace( ',', "','", User( 'SCHOOLS' ) ), 2, -2 ) . ") ";
}

$enrollment_RET = DBGet( "SELECT se.START_DATE AS START_DATE,NULL AS END_DATE,se.START_DATE AS DATE,
se.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,sch.TITLE,se.SCHOOL_ID
FROM student_enrollment se,students s,schools sch
WHERE s.STUDENT_ID=se.STUDENT_ID
AND se.START_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
AND sch.ID=se.SCHOOL_ID" . $schools_where_sql . "
AND se.SYEAR='" . UserSyear() . "'
UNION
SELECT NULL AS START_DATE,se.END_DATE AS END_DATE,se.END_DATE AS DATE,
se.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,sch.TITLE,se.SCHOOL_ID
FROM student_enrollment se,students s,schools sch
WHERE s.STUDENT_ID=se.STUDENT_ID
AND se.END_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
AND sch.ID=se.SCHOOL_ID" . $schools_where_sql . "
AND se.SYEAR='" . UserSyear() . "'
ORDER BY DATE DESC", [
	'FULL_NAME' => '_makeStudentInfoLink',
	'START_DATE' => 'ProperDate',
	'END_DATE' => 'ProperDate',
] );

$columns = [
	'FULL_NAME' => _( 'Student' ),
	'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
	'TITLE' => _( 'School' ),
	'START_DATE' => _( 'Enrolled' ),
	'END_DATE' => _( 'Dropped' ),
];

ListOutput( $enrollment_RET, $columns, 'Enrollment Record', 'Enrollment Records' );

/**
 * Make Student Info link
 *
 * @since 7.2
 * @since 9.0 Add Student Photo Tip Message
 *
 * Local function
 * DBGet() callback
 *
 * @param  string $value  Student Full Name.
 * @param  string $column Column.
 *
 * @return string         Link to Student Info program.
 */
function _makeStudentInfoLink( $value, $column = 'FULL_NAME' )
{
	global $THIS_RET;

	$modname = 'Students/Student.php';

	if ( ! AllowUse( $modname )
		|| ! $THIS_RET['STUDENT_ID'] )
	{
		return $value;
	}

	$link = 'Modules.php?modname=' . $modname . '&student_id=' . $THIS_RET['STUDENT_ID'];

	if ( $THIS_RET['SCHOOL_ID'] !== UserSchool() )
	{
		$link .= '&school_id=' . $THIS_RET['SCHOOL_ID'];
	}

	return '<a href="' . URLEscape( $link ) . '">' . MakeStudentPhotoTipMessage( $THIS_RET['STUDENT_ID'], $value ) . '</a>';
}
