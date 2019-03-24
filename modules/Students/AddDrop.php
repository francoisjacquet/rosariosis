<?php

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

$enrollment_RET = DBGet( "SELECT se.START_DATE AS START_DATE,NULL AS END_DATE,se.START_DATE AS DATE,se.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,sch.TITLE
FROM STUDENT_ENROLLMENT se,STUDENTS s,SCHOOLS sch
WHERE s.STUDENT_ID=se.STUDENT_ID
AND se.START_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
AND sch.ID=se.SCHOOL_ID
UNION
SELECT NULL AS START_DATE,se.END_DATE AS END_DATE,se.END_DATE AS DATE,se.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,sch.TITLE
FROM STUDENT_ENROLLMENT se,STUDENTS s,SCHOOLS sch
WHERE s.STUDENT_ID=se.STUDENT_ID
AND se.END_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
AND sch.ID=se.SCHOOL_ID
ORDER BY DATE DESC", array( 'START_DATE' => 'ProperDate', 'END_DATE' => 'ProperDate' ) );

$columns = array(
	'FULL_NAME' => _( 'Student' ),
	'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
	'TITLE' => _( 'School' ),
	'START_DATE' => _( 'Enrolled' ),
	'END_DATE' => _( 'Dropped' ),
);

ListOutput( $enrollment_RET, $columns, 'Enrollment Record', 'Enrollment Records' );
