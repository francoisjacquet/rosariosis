<?php
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

if ( ( isset( $_POST['values'] )
	|| isset( $_POST['month_values'] ) )
	&& AllowEdit() )
{
	SaveData(
		[
			'STUDENT_MEDICAL_ALERTS' => "ID='__ID__'",
			'STUDENT_MEDICAL' => "ID='__ID__'",
			'STUDENT_MEDICAL_VISITS' => "ID='__ID__'",
			'fields' => [
				'STUDENT_MEDICAL' => 'ID,STUDENT_ID,',
				'STUDENT_MEDICAL_ALERTS' => 'ID,STUDENT_ID,',
				'STUDENT_MEDICAL_VISITS' => 'ID,STUDENT_ID,',
			],
			'values' => [
				'STUDENT_MEDICAL' => db_seq_nextval( 'student_medical_id_seq' ) . ",'" . UserStudentID() . "',",
				'STUDENT_MEDICAL_ALERTS' => db_seq_nextval( 'student_medical_alerts_id_seq' ) . ",'" . UserStudentID() . "',",
				'STUDENT_MEDICAL_VISITS' => db_seq_nextval( 'student_medical_visits_id_seq' ) . ",'" . UserStudentID() . "',",
			],
		]
	);

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $_REQUEST['modfunc'] === 'delete_medical'
	&& AllowEdit() )
{
	if ( ! isset( $_REQUEST['delete_ok'] )
		&& ! isset( $_REQUEST['delete_cancel'] ) )
	{
		echo '</form>';
	}

	if ( DeletePrompt( $_REQUEST['title'] ) )
	{
		DBQuery( "DELETE FROM " . DBEscapeIdentifier( $_REQUEST['table'] ) . "
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & table & title & redirect URL.
		RedirectURL( [ 'modfunc', 'id', 'table', 'title' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	require_once 'modules/Students/includes/Other_Info.inc.php';

	if ( ! empty( $PopTable_opened ) )
	{
		PopTable( 'footer' );
	}

	$table = 'STUDENT_MEDICAL';

	$functions = [
		'TYPE' => '_makeType',
		'MEDICAL_DATE' => '_makeDate',
		'COMMENTS' => '_makeComments',
	];

	$med_RET = DBGet( "SELECT ID,TYPE,MEDICAL_DATE,COMMENTS
		FROM STUDENT_MEDICAL
		WHERE STUDENT_ID='" . UserStudentID() . "'
		ORDER BY MEDICAL_DATE,TYPE", $functions );

	$columns = [
		'TYPE' => '<span class="a11y-hidden">' . _( 'Type' ) . '</span>',
		'MEDICAL_DATE' => _( 'Date' ),
		'COMMENTS' => _( 'Comments' ),
	];

	$link['add']['html'] = [
		'TYPE' => _makeType( '', '' ),
		'MEDICAL_DATE' => _makeDate( '', 'MEDICAL_DATE' ),
		'COMMENTS' => _makeComments( '', 'COMMENTS' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&modfunc=delete_medical&table=STUDENT_MEDICAL&title=' . _( 'Immunization or Physical' );

	$link['remove']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$med_RET,
		$columns,
		'Immunization or Physical',
		'Immunizations or Physicals',
		$link,
		[],
		[ 'search' => false, 'save' => false ]
	);

	$table = 'STUDENT_MEDICAL_ALERTS';

	$functions = [ 'TITLE' => '_makeComments' ];

	$med_RET = DBGet( "SELECT ID,TITLE
		FROM STUDENT_MEDICAL_ALERTS
		WHERE STUDENT_ID='" . UserStudentID() . "'
		ORDER BY ID", $functions );

	$columns = [ 'TITLE' => _( 'Medical Alert' ) ];

	$link['add']['html'] = [ 'TITLE' => _makeComments( '', 'TITLE' ) ];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&modfunc=delete_medical&table=STUDENT_MEDICAL_ALERTS&title=' . _( 'Medical Alert' );

	$link['remove']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$med_RET,
		$columns,
		'Medical Alert',
		'Medical Alerts',
		$link,
		[],
		[ 'search' => false, 'save' => false ]
	);

	if ( User( 'PROFILE' ) === 'admin'
		|| User( 'PROFILE' ) === 'teacher' )
	{
		$table = 'STUDENT_MEDICAL_VISITS';

		$functions = [
			'SCHOOL_DATE' => '_makeDate',
			'TIME_IN' => '_makeComments',
			'TIME_OUT' => '_makeComments',
			'REASON' => '_makeComments',
			'RESULT' => '_makeComments',
			'COMMENTS' => '_makeComments',
		];

		$med_RET = DBGet( "SELECT ID,SCHOOL_DATE,TIME_IN,TIME_OUT,REASON,RESULT,COMMENTS
			FROM STUDENT_MEDICAL_VISITS
			WHERE STUDENT_ID='" . UserStudentID() . "'
			ORDER BY SCHOOL_DATE", $functions );

		$columns = [
			'SCHOOL_DATE' => _( 'Date' ),
			'TIME_IN' => _( 'Time In' ),
			'TIME_OUT' => _( 'Time Out' ),
			'REASON' => _( 'Reason' ),
			'RESULT' => _( 'Result' ),
			'COMMENTS' => _( 'Comments' ),
		];

		$link['add']['html'] = [
			'SCHOOL_DATE' => _makeDate( '', 'SCHOOL_DATE' ),
			'TIME_IN' => _makeComments( '', 'TIME_IN' ),
			'TIME_OUT' => _makeComments( '', 'TIME_OUT' ),
			'REASON' => _makeComments( '', 'REASON' ),
			'RESULT' => _makeComments( '', 'RESULT' ),
			'COMMENTS' => _makeComments( '', 'COMMENTS' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $_REQUEST['category_id'] .
			'&modfunc=delete_medical&table=STUDENT_MEDICAL_VISITS&title=' . _( 'Nurse Visit' );

		$link['remove']['variables'] = [ 'id' => 'ID' ];

		ListOutput(
			$med_RET,
			$columns,
			'Nurse Visit',
			'Nurse Visits',
			$link,
			[],
			[ 'search' => false, 'save' => false ]
		);
	}

	if ( ! empty( $PopTable_opened ) )
	{
		// FJ bugfix display in PrintStudentInfo.php.
		echo '<table><tr><td>';
	}
}
