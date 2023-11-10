<?php
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

if ( ( isset( $_POST['values'] )
	|| isset( $_POST['month_values'] ) )
	&& AllowEdit() )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values' );

	$tables = [
		'student_medical_alerts',
		'student_medical',
		'student_medical_visits',
	];

	foreach ( $tables as $table )
	{
		if ( empty( $_REQUEST['values'][ $table ] ) )
		{
			continue;
		}

		foreach ( $_REQUEST['values'][ $table ] as $id => $columns )
		{
			if ( $id === 'new' )
			{
				// Check required columns on INSERT.
				if ( $table === 'student_medical'
					&& ( empty( $columns['TYPE'] )
						|| empty( $columns['MEDICAL_DATE'] ) ) )
				{
					continue;
				}

				if ( $table === 'student_medical_alerts'
					&& empty( $columns['TITLE'] ) )
				{
					continue;
				}

				if ( $table === 'student_medical_visits'
					&& empty( $columns['SCHOOL_DATE'] ) )
				{
					continue;
				}
			}

			$where_columns = [ 'STUDENT_ID' => UserStudentID() ];

			if ( $id !== 'new' )
			{
				$where_columns['ID'] = $id;
			}

			DBUpsert(
				$table,
				$columns,
				$where_columns,
				( $id === 'new' ? 'insert' : 'update' )
			);
		}
	}

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
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

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

	$table = 'student_medical';

	$functions = [
		'TYPE' => '_makeType',
		'MEDICAL_DATE' => '_makeDate',
		'COMMENTS' => '_makeComments',
	];

	$med_RET = DBGet( "SELECT ID,TYPE,MEDICAL_DATE,COMMENTS
		FROM student_medical
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
		'&modfunc=delete_medical&table=student_medical&title=' . _( 'Immunization or Physical' );

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

	$table = 'student_medical_alerts';

	$functions = [ 'TITLE' => '_makeComments' ];

	$med_RET = DBGet( "SELECT ID,TITLE
		FROM student_medical_alerts
		WHERE STUDENT_ID='" . UserStudentID() . "'
		ORDER BY ID", $functions );

	$columns = [ 'TITLE' => _( 'Medical Alert' ) ];

	$link['add']['html'] = [ 'TITLE' => _makeComments( '', 'TITLE' ) ];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&modfunc=delete_medical&table=student_medical_alerts&title=' . _( 'Medical Alert' );

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
		$table = 'student_medical_visits';

		$functions = [
			'SCHOOL_DATE' => '_makeDate',
			'TIME_IN' => '_makeComments',
			'TIME_OUT' => '_makeComments',
			'REASON' => '_makeComments',
			'RESULT' => '_makeComments',
			'COMMENTS' => '_makeComments',
		];

		$med_RET = DBGet( "SELECT ID,SCHOOL_DATE,TIME_IN,TIME_OUT,REASON,RESULT,COMMENTS
			FROM student_medical_visits
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
			'&modfunc=delete_medical&table=student_medical_visits&title=' . _( 'Nurse Visit' );

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
		echo '<div><table><tr><td>';
	}
}
