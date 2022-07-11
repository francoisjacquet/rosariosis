<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( $_REQUEST['student']
		&& $_POST['student'] )
	{
		foreach ( (array) $_REQUEST['student'] as $transaction_id => $school_id )
		{
			if ( $school_id )
			{
				DBQuery( "UPDATE food_service_transactions SET SCHOOL_ID='" . (int) $school_id . "' WHERE TRANSACTION_ID='" . (int) $transaction_id . "'" );
			}
		}
	}

	if ( $_REQUEST['staff']
		&& $_POST['staff'] )
	{
		foreach ( (array) $_REQUEST['staff'] as $transaction_id => $school_id )
		{
			if ( $school_id )
			{
				DBQuery( "UPDATE food_service_staff_transactions SET SCHOOL_ID='" . (int) $school_id . "' WHERE TRANSACTION_ID='" . (int) $transaction_id . "'" );
			}
		}
	}

	// Unset modfunc & staff & student & redirect URL.
	RedirectURL( [ 'modfunc', 'staff', 'student' ] );
}

$schools_RET = DBGet( "SELECT ID,SYEAR,TITLE FROM schools", [], [ 'SYEAR' ] );
//echo '<pre>'; var_dump($schools_RET); echo '</pre>';

foreach ( (array) $schools_RET as $syear => $schools )
{
	foreach ( (array) $schools as $school )
	{
		$schools_select[$syear][$school['ID']] = $school['TITLE'];
	}
}

//echo '<pre>'; var_dump($schools_select); echo '</pre>';

$students_RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.ACCOUNT_ID,fst.SYEAR," .
	db_case( [
		'fst.STUDENT_ID',
		"''",
		'NULL',
		"(SELECT " . DisplayNameSQL() . " FROM students WHERE STUDENT_ID=fst.STUDENT_ID)",
	] ) . " AS FULL_NAME,fst.ACCOUNT_ID AS STUDENTS,fst.SCHOOL_ID
	FROM food_service_transactions
	fst WHERE fst.SCHOOL_ID IS NULL", [ 'STUDENTS' => '_students', 'SCHOOL_ID' => '_make_school' ] );

$staff_RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.STAFF_ID,fst.SYEAR,
	(SELECT " . DisplayNameSQL() . " FROM staff WHERE STAFF_ID=fst.STAFF_ID) AS FULL_NAME,fst.SCHOOL_ID
	FROM food_service_staff_transactions fst
	WHERE fst.SCHOOL_ID IS NULL", [ 'SCHOOL_ID' => '_make_staff_school' ] );

//echo '<pre>'; var_dump($students_RET); echo '</pre>';
//echo '<pre>'; var_dump($users_RET); echo '</pre>';

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';
DrawHeader( '', SubmitButton() );
$columns = [ 'TRANSACTION_ID' => _( 'ID' ), 'ACCOUNT_ID' => _( 'Account ID' ), 'SYEAR' => _( 'School Year' ), 'FULL_NAME' => _( 'Student' ), 'STUDENTS' => _( 'Students' ), 'SCHOOL_ID' => _( 'School' ) ];
ListOutput( $students_RET, $columns, 'Student Transaction w/o School', 'Student Transactions w/o School', false, [], [ 'save' => false, 'search' => false ] );
$columns = [ 'TRANSACTION_ID' => _( 'ID' ), 'SYEAR' => _( 'School Year' ), 'FULL_NAME' => _( 'User' ), 'SCHOOL_ID' => _( 'School' ) ];
ListOutput( $staff_RET, $columns, 'User Transaction w/o School', 'User Transactions w/o School', false, [], [ 'save' => false, 'search' => false ] );
echo '<div class="center">' . SubmitButton() . '</div>';
echo '</form>';

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _students( $value, $column )
{
	$RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . "  AS FULL_NAME FROM students s,food_service_student_accounts fsa WHERE s.STUDENT_ID=fsa.STUDENT_ID AND fsa.ACCOUNT_ID='" . (int) $value . "'" );

	foreach ( (array) $RET as $student )
	{
		$ret .= $student['FULL_NAME'] . '<br />';
	}

	$ret = mb_substr( $ret, 0, -4 );

	return $ret;
}

/**
 * @param $value
 * @param $column
 */
function _make_school( $value, $column )
{
	global $THIS_RET, $schools_select;

	return SelectInput( $value, 'student[' . $THIS_RET['TRANSACTION_ID'] . ']', '', $schools_select[$THIS_RET['SYEAR']] );
}

/**
 * @param $value
 * @param $column
 */
function _make_staff_school( $value, $column )
{
	global $THIS_RET, $schools_select;

	return SelectInput( $value, 'staff[' . $THIS_RET['TRANSACTION_ID'] . ']', '', $schools_select[$THIS_RET['SYEAR']] );
}
