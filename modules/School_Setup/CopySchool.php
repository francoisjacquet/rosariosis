<?php
//FJ add School Configuration, copy
$tables = array(
	'CONFIG' => _( 'School Configuration' ),
	'SCHOOL_PERIODS' => _( 'School Periods' ),
	'SCHOOL_MARKING_PERIODS' => _( 'Marking Periods' ),
	'REPORT_CARD_GRADES' => _( 'Report Card Grade Codes' ),
	'REPORT_CARD_COMMENTS' => _( 'Report Card Comment Codes' ),
	'ELIGIBILITY_ACTIVITIES' => _( 'Eligibility Activities' ),
	'ATTENDANCE_CODES' => _( 'Attendance Codes' ),
	'SCHOOL_GRADELEVELS' => _( 'Grade Levels' ),
);

$table_list = '';

foreach ( (array) $tables as $table => $name )
{
	//FJ force School Configuration copy
	$force_checked = false;

	if ( $table === 'CONFIG' )
		$force_checked = true;

	$table_list .= '<label>' . ( ! $force_checked ?
			'<input type="checkbox" value="Y" name="tables[' . $table . ']" checked />&nbsp;' :
			'<input type="hidden" value="Y" name="tables[' . $table . ']" />
			<input type="checkbox" onclick="return false;" checked disabled />&nbsp;' ) .
		$name . '</label><br />';
}

//FJ add translation
$table_list .= '<br />' .
	TextInput( _( 'New School' ), 'title', _( 'New School\'s Title' ), '', false );

DrawHeader( ProgramTitle() );

$go = Prompt(
	_( 'Confirm Copy School' ),
	button( 'help', '', '', 'bigger' ) . '<br /><br />' .
	sprintf(
		_( 'Are you sure you want to copy the data for %s to a new school?' ),
		SchoolInfo( 'TITLE' )
	),
	$table_list
);

if ( $go
	&& ! empty( $_REQUEST['tables'] ) )
{
	$id = DBSeqNextID( 'SCHOOLS_SEQ' );

	DBQuery( "INSERT INTO SCHOOLS (ID,SYEAR,TITLE,REPORTING_GP_SCALE)
		values('" . $id . "','" . UserSyear() . "','" . $_REQUEST['title'] . "',
		(SELECT REPORTING_GP_SCALE
			FROM SCHOOLS
			WHERE ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'))" );

	DBQuery( "UPDATE STAFF
		SET SCHOOLS=rtrim(SCHOOLS,',')||'," . $id . ",'
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND SCHOOLS IS NOT NULL" );

	foreach ( (array) $_REQUEST['tables'] as $table => $value )
	{
		_rollover( $table );
	}

	// Print success message
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

	$note[] = button( 'check' ) .'&nbsp;' .
		sprintf( _( 'The data have been copied to a new school called "%s".' ), $_REQUEST['title'] ) .
		' ' . SubmitButton( _( 'OK' ) );

	echo ErrorMessage( $note, 'note' );

	echo '</form>';

	unset( $_SESSION['_REQUEST_vars']['tables'] );

	// Set new current school.
	$_SESSION['UserSchool'] = $id;

	// Unset current student.
	unset( $_SESSION['student_id'] );

	UpdateSchoolArray( UserSchool() );
}

/**
 * Copy the table data for current school to new school
 *
 * Local function
 *
 * @param  string $table SQL table name
 *
 * @return void
 */
function _rollover( $table )
{
	global $id;

	switch ( $table )
	{
		//FJ copy School Configuration
		case 'CONFIG':

			DBQuery( "INSERT INTO CONFIG (SCHOOL_ID,TITLE,CONFIG_VALUE)
				SELECT '" . $id . "' AS SCHOOL_ID,TITLE,CONFIG_VALUE
					FROM CONFIG
					WHERE SCHOOL_ID='" . UserSchool() . "';" );

			DBQuery( "INSERT INTO PROGRAM_CONFIG (SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE)
				SELECT '" . $id . "' AS SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE
					FROM PROGRAM_CONFIG
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . UserSyear() . "';" );

		break;

		case 'SCHOOL_PERIODS':

			DBQuery( "INSERT INTO SCHOOL_PERIODS (PERIOD_ID,SYEAR,SCHOOL_ID,SORT_ORDER,TITLE,
					SHORT_NAME,LENGTH,ATTENDANCE)
				SELECT " . db_seq_nextval( 'SCHOOL_PERIODS_SEQ' ) . ",SYEAR,
					'" . $id . "' AS SCHOOL_ID,SORT_ORDER,TITLE,SHORT_NAME,LENGTH,ATTENDANCE
					FROM SCHOOL_PERIODS
					WHERE SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'SCHOOL_GRADELEVELS':

			$table_properties = db_properties( $table );

			$columns = '';

			foreach ( (array) $table_properties as $column => $values )
			{
				if ( $column !== 'ID'
					&& $column !== 'SCHOOL_ID'
					&& $column !== 'NEXT_GRADE_ID' )
				{
					$columns .= ',' . $column;
				}
			}

			DBQuery( "INSERT INTO " . $table . " (ID,SCHOOL_ID" . $columns . ")
				SELECT " . db_seq_nextval( $table . '_SEQ' ) . ",'" . $id . "' AS SCHOOL_ID" . $columns . "
				FROM " . $table . "
				WHERE SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'SCHOOL_MARKING_PERIODS':

			DBQuery( "INSERT INTO SCHOOL_MARKING_PERIODS (MARKING_PERIOD_ID,PARENT_ID,SYEAR,MP,
					SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,
					POST_END_DATE,DOES_GRADES,DOES_COMMENTS,ROLLOVER_ID)
				SELECT " . db_seq_nextval( 'MARKING_PERIOD_SEQ' ) . ",PARENT_ID,SYEAR,MP,
					'" . $id . "' AS SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,
					POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_COMMENTS,MARKING_PERIOD_ID
				FROM SCHOOL_MARKING_PERIODS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "UPDATE SCHOOL_MARKING_PERIODS
				SET PARENT_ID=(SELECT mp.MARKING_PERIOD_ID
					FROM SCHOOL_MARKING_PERIODS mp
					WHERE mp.SYEAR=school_marking_periods.SYEAR
					AND mp.SCHOOL_ID=school_marking_periods.SCHOOL_ID
					AND mp.ROLLOVER_ID=school_marking_periods.PARENT_ID)
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . $id . "'" );

		break;

		case 'REPORT_CARD_GRADES':

			DBQuery( "INSERT INTO REPORT_CARD_GRADE_SCALES (ID,SYEAR,SCHOOL_ID,TITLE,COMMENT,
					HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ROLLOVER_ID)
				SELECT " . db_seq_nextval( 'REPORT_CARD_GRADE_SCALES_SEQ' ) . ",SYEAR,
					'" . $id . "',TITLE,COMMENT,HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ID
				FROM REPORT_CARD_GRADE_SCALES
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "INSERT INTO REPORT_CARD_GRADES (ID,SYEAR,SCHOOL_ID,TITLE,COMMENT,BREAK_OFF,
					GPA_VALUE,GRADE_SCALE_ID,SORT_ORDER)
				SELECT " . db_seq_nextval( 'REPORT_CARD_GRADES_SEQ' ) . ",SYEAR,
					'" . $id . "',TITLE,COMMENT,BREAK_OFF,GPA_VALUE,
					(SELECT ID
						FROM REPORT_CARD_GRADE_SCALES
						WHERE ROLLOVER_ID=report_card_grades.GRADE_SCALE_ID
						AND SCHOOL_ID='" . $id . "'),
					SORT_ORDER
				FROM REPORT_CARD_GRADES
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'REPORT_CARD_COMMENTS':

			DBQuery( "INSERT INTO REPORT_CARD_COMMENTS (ID,SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,
					CATEGORY_ID,COURSE_ID)
				SELECT " . db_seq_nextval( 'REPORT_CARD_COMMENTS_SEQ' ) . ",SYEAR,
					'" . $id . "',TITLE,SORT_ORDER,NULL,NULL
				FROM REPORT_CARD_COMMENTS
				WHERE COURSE_ID IS NULL
				AND SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'ELIGIBILITY_ACTIVITIES':
		case 'ATTENDANCE_CODES':

			$table_properties = db_properties( $table );

			$columns = '';

			foreach ( (array) $table_properties as $column => $values )
			{
				if ( $column !== 'ID'
					&& $column !== 'SYEAR'
					&& $column !== 'SCHOOL_ID' )
				{
					$columns .= ',' . $column;
				}
			}

			DBQuery( "INSERT INTO " . $table . " (ID,SYEAR,SCHOOL_ID" . $columns . ")
				SELECT " . db_seq_nextval( $table . '_SEQ' ) . ",SYEAR,
					'" . $id . "' AS SCHOOL_ID" . $columns . "
				FROM " . $table . "
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

		break;
	}
}
