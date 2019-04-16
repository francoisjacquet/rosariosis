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
				DBQuery( "UPDATE FOOD_SERVICE_TRANSACTIONS SET SCHOOL_ID='" . $school_id . "' WHERE TRANSACTION_ID='" . $transaction_id . "'" );
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
				DBQuery( "UPDATE FOOD_SERVICE_STAFF_TRANSACTIONS SET SCHOOL_ID='" . $school_id . "' WHERE TRANSACTION_ID='" . $transaction_id . "'" );
			}
		}
	}

	// Unset modfunc & staff & student & redirect URL.
	RedirectURL( array( 'modfunc', 'staff', 'student' ) );
}

$schools_RET = DBGet( "SELECT ID,SYEAR,TITLE FROM SCHOOLS", array(), array( 'SYEAR' ) );
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
	db_case( array(
		'fst.STUDENT_ID',
		"''",
		'NULL',
		"(SELECT " . DisplayNameSQL() . " FROM STUDENTS WHERE STUDENT_ID=fst.STUDENT_ID)",
	) ) . " AS FULL_NAME,fst.ACCOUNT_ID AS STUDENTS,fst.SCHOOL_ID
	FROM FOOD_SERVICE_TRANSACTIONS
	fst WHERE fst.SCHOOL_ID IS NULL", array( 'STUDENTS' => '_students', 'SCHOOL_ID' => '_make_school' ) );

$staff_RET = DBGet( "SELECT fst.TRANSACTION_ID,fst.STAFF_ID,fst.SYEAR,
	(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=fst.STAFF_ID) AS FULL_NAME,fst.SCHOOL_ID
	FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst
	WHERE fst.SCHOOL_ID IS NULL", array( 'SCHOOL_ID' => '_make_staff_school' ) );

//echo '<pre>'; var_dump($students_RET); echo '</pre>';
//echo '<pre>'; var_dump($users_RET); echo '</pre>';

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';
DrawHeader( '', SubmitButton() );
$columns = array( 'TRANSACTION_ID' => _( 'ID' ), 'ACCOUNT_ID' => _( 'Account ID' ), 'SYEAR' => _( 'School Year' ), 'FULL_NAME' => _( 'Student' ), 'STUDENTS' => _( 'Students' ), 'SCHOOL_ID' => _( 'School' ) );
ListOutput( $students_RET, $columns, 'Student Transaction w/o School', 'Student Transactions w/o School', false, array(), array( 'save' => false, 'search' => false ) );
$columns = array( 'TRANSACTION_ID' => _( 'ID' ), 'SYEAR' => _( 'School Year' ), 'FULL_NAME' => _( 'User' ), 'SCHOOL_ID' => _( 'School' ) );
ListOutput( $staff_RET, $columns, 'User Transaction w/o School', 'User Transactions w/o School', false, array(), array( 'save' => false, 'search' => false ) );
echo '<div class="center">' . SubmitButton() . '</div>';
echo '</form>';

/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _students( $value, $column )
{
	$RET = DBGet( "SELECT s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fsa WHERE s.STUDENT_ID=fsa.STUDENT_ID AND fsa.ACCOUNT_ID='" . $value . "'" );

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
