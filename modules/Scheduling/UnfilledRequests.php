<?php

require_once 'modules/Scheduling/includes/calcSeats0.fnc.php';

//include calcSeats, _makeRequestTeacher & _makeRequestPeriod functions
require_once 'modules/Scheduling/includes/unfilledRequests.inc.php';

if ( $_REQUEST['modname'] == 'Scheduling/UnfilledRequests.php' )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=modify" method="POST">';

		DrawHeader( CheckBoxOnclick( 'include_seats', _( 'Show Available Seats' ) ) );

		echo '</form>';
	}
}
else
{
	$extra['suppress_save'] = $extra['NoSearchTerms'] = true;
}

//FJ multiple school periods for a course period
/*$extra['SELECT'] = ',s.CUSTOM_200000000,c.TITLE AS COURSE,sr.SUBJECT_ID,sr.COURSE_ID,sr.WITH_TEACHER_ID,sr.NOT_TEACHER_ID,sr.WITH_PERIOD_ID,sr.NOT_PERIOD_ID,\'0\' AS AVAILABLE_SEATS,(SELECT count(*) AS SECTIONS FROM COURSE_PERIODS cp WHERE cp.COURSE_ID=sr.COURSE_ID AND (cp.GENDER_RESTRICTION=\'N\' OR cp.GENDER_RESTRICTION=substring(s.CUSTOM_200000000,1,1)) AND (sr.WITH_TEACHER_ID IS NULL OR sr.WITH_TEACHER_ID=cp.TEACHER_ID) AND (sr.NOT_TEACHER_ID IS NULL OR sr.NOT_TEACHER_ID!=cp.TEACHER_ID) AND (sr.WITH_PERIOD_ID IS NULL OR sr.WITH_PERIOD_ID=cp.PERIOD_ID) AND (sr.NOT_PERIOD_ID IS NULL OR sr.NOT_PERIOD_ID!=cp.PERIOD_ID)) AS SECTIONS ';*/
$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE FROM CUSTOM_FIELDS WHERE ID=200000000", array(), array( 'ID' ) );

if ( $custom_fields_RET['200000000'] && $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
{
	$extra['SELECT'] = ',s.CUSTOM_200000000,c.TITLE AS COURSE,sr.SUBJECT_ID,sr.COURSE_ID,sr.WITH_TEACHER_ID,sr.NOT_TEACHER_ID,sr.WITH_PERIOD_ID,sr.NOT_PERIOD_ID,\'0\' AS AVAILABLE_SEATS,(SELECT count(*) AS SECTIONS FROM COURSE_PERIODS cp WHERE cp.COURSE_ID=sr.COURSE_ID AND (cp.GENDER_RESTRICTION=\'N\' OR cp.GENDER_RESTRICTION=substring(s.CUSTOM_200000000,1,1)) AND (sr.WITH_TEACHER_ID IS NULL OR sr.WITH_TEACHER_ID=cp.TEACHER_ID) AND (sr.NOT_TEACHER_ID IS NULL OR sr.NOT_TEACHER_ID!=cp.TEACHER_ID)) AS SECTIONS ';
}
else //'None' as GENDER
{
	$extra['SELECT'] = ',\'None\' AS CUSTOM_200000000,c.TITLE AS COURSE,sr.SUBJECT_ID,sr.COURSE_ID,sr.WITH_TEACHER_ID,sr.NOT_TEACHER_ID,sr.WITH_PERIOD_ID,sr.NOT_PERIOD_ID,\'0\' AS AVAILABLE_SEATS,(SELECT count(*) AS SECTIONS FROM COURSE_PERIODS cp WHERE cp.COURSE_ID=sr.COURSE_ID AND (cp.GENDER_RESTRICTION=\'N\' OR cp.GENDER_RESTRICTION=substring(\'None\',1,1)) AND (sr.WITH_TEACHER_ID IS NULL OR sr.WITH_TEACHER_ID=cp.TEACHER_ID) AND (sr.NOT_TEACHER_ID IS NULL OR sr.NOT_TEACHER_ID!=cp.TEACHER_ID)) AS SECTIONS ';
}

//$extra['FROM'] = ',SCHEDULE_REQUESTS sr,COURSES c';
$extra['FROM'] = ',SCHEDULE_REQUESTS sr,COURSES c';
//$extra['WHERE'] = ' AND sr.STUDENT_ID=ssm.STUDENT_ID AND sr.SYEAR=ssm.SYEAR AND sr.SCHOOL_ID=ssm.SCHOOL_ID AND sr.COURSE_ID=c.COURSE_ID AND NOT EXISTS (SELECT \'\' FROM SCHEDULE s WHERE s.STUDENT_ID=sr.STUDENT_ID AND s.COURSE_ID=sr.COURSE_ID)';
$extra['WHERE'] = ' AND sr.STUDENT_ID=ssm.STUDENT_ID AND sr.SYEAR=ssm.SYEAR AND sr.SCHOOL_ID=ssm.SCHOOL_ID AND sr.COURSE_ID=c.COURSE_ID AND NOT EXISTS (SELECT \'\' FROM SCHEDULE s WHERE s.STUDENT_ID=sr.STUDENT_ID AND s.COURSE_ID=sr.COURSE_ID)';
$extra['functions'] = array( 'WITH_TEACHER_ID' => '_makeRequestTeacher', 'WITH_PERIOD_ID' => '_makeRequestPeriod' );

if ( ! empty( $_REQUEST['include_seats'] ) )
{
	$extra['functions'] += array( 'AVAILABLE_SEATS' => 'CalcSeats' );
}

$extra['columns_after'] = array( 'COURSE' => _( 'Request' ) );

if ( ! empty( $_REQUEST['include_seats'] ) )
{
	$extra['columns_after'] += array( 'AVAILABLE_SEATS' => _( 'Available Seats' ) );
}

$extra['columns_after'] += array( 'SECTIONS' => _( 'Sections' ), 'WITH_TEACHER_ID' => _( 'Teacher' ), 'WITH_PERIOD_ID' => _( 'Period' ) );
$extra['singular'] = _( 'Unfilled Request' );
$extra['plural'] = _( 'Unfilled Requests' );

if ( ! $extra['link']['FULL_NAME'] )
{
	$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=Scheduling/Requests.php';
	$extra['link']['FULL_NAME']['variables']['student_id'] = 'STUDENT_ID';
}

$extra['new'] = true;
$extra['Redirect'] = false;

// Deactivate List saving.
$extra['options']['save'] = false;

Search( 'student_id', $extra );
