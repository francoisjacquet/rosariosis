<?php

$tables = [
	'config' => _( 'School Configuration' ),
	'school_marking_periods' => _( 'Marking Periods' ),
	'school_periods' => _( 'School Periods' ),
	'school_gradelevels' => _( 'Grade Levels' ),
	'report_card_grades' => _( 'Report Card Grade Codes' ),
	'report_card_comments' => _( 'Report Card Comment Codes' ),
	'eligibility_activities' => _( 'Eligibility Activities' ),
	'attendance_codes' => _( 'Attendance Codes' ),
];

$table_list = [];

foreach ( (array) $tables as $table => $name )
{
	// Force School Configuration copy.
	$force_checked = false;

	if ( $table === 'config' )
		$force_checked = true;

	$table_list[] = '<label>' . ( ! $force_checked ?
			'<input type="checkbox" value="Y" name="tables[' . $table . ']" checked />&nbsp;' :
			'<input type="hidden" value="Y" name="tables[' . $table . ']" />
			<input type="checkbox" onclick="return false;" checked disabled />&nbsp;' ) .
		$name . '</label>';
}

$table_list[] = TextInput(
	_( 'New School' ),
	'title',
	_( 'New School\'s Title' ),
	'required',
	false
);

DrawHeader( ProgramTitle() );

// @since 5.8 Hook.
do_action( 'School_Setup/CopySchool.php|header' );

$table_list_html = '<table class="widefat center"><tr><td>' .
	implode( '</td></tr><tr><td>', $table_list ) . '</td></tr></table>';

$go = Prompt(
	_( 'Confirm Copy School' ),
	button( 'help', '', '', 'bigger' ) . '<br /><br />' .
	sprintf(
		_( 'Are you sure you want to copy the data for %s to a new school?' ),
		SchoolInfo( 'TITLE' )
	),
	$table_list_html
);

if ( $go
	&& ! empty( $_REQUEST['tables'] )
	&& ! empty( $_REQUEST['title'] ) )
{
	DBQuery( "INSERT INTO schools (SYEAR,TITLE,REPORTING_GP_SCALE)
		(SELECT '" . UserSyear() . "','" . $_REQUEST['title'] . "',REPORTING_GP_SCALE
			FROM schools
			WHERE ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "')" );

	$id = DBLastInsertID();

	/**
	 * SQL TRIM() both compatible with PostgreSQL and MySQL.
	 *
	 * @link https://www.sqltutorial.org/sql-string-functions/sql-trim/
	 */
	DBQuery( "UPDATE staff
		SET SCHOOLS=CONCAT(trim(trailing ',' from SCHOOLS), '," . $id . ",')
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND SCHOOLS IS NOT NULL" );

	foreach ( (array) $_REQUEST['tables'] as $table => $value )
	{
		_rollover( $table );
	}

	// Print success message
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

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

	// @since 5.8 Hook.
	do_action( 'School_Setup/CopySchool.php|copy_school' );
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
	global $id,
		$DatabaseType;

	switch ( $table )
	{
		//FJ copy School Configuration
		case 'config':

			DBQuery( "INSERT INTO config (SCHOOL_ID,TITLE,CONFIG_VALUE)
				SELECT '" . $id . "' AS SCHOOL_ID,TITLE,CONFIG_VALUE
					FROM config
					WHERE SCHOOL_ID='" . UserSchool() . "';" );

			DBQuery( "INSERT INTO program_config (SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE)
				SELECT '" . $id . "' AS SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE
					FROM program_config
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . UserSyear() . "';" );

		break;

		case 'school_periods':

			DBQuery( "INSERT INTO school_periods (SYEAR,SCHOOL_ID,SORT_ORDER,TITLE,
					SHORT_NAME,LENGTH,ATTENDANCE)
				SELECT SYEAR,
					'" . $id . "' AS SCHOOL_ID,SORT_ORDER,TITLE,SHORT_NAME,LENGTH,ATTENDANCE
					FROM school_periods
					WHERE SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'school_gradelevels':

			$table_properties = db_properties( $table );

			$columns = '';

			foreach ( (array) $table_properties as $column => $values )
			{
				if ( $column !== 'ID'
					&& $column !== 'SCHOOL_ID'
					&& $column !== 'NEXT_GRADE_ID' )
				{
					$columns .= ',' . DBEscapeIdentifier( $column );
				}
			}

			DBQuery( "INSERT INTO " . DBEscapeIdentifier( $table ) . " (SCHOOL_ID" . $columns . ")
				SELECT '" . $id . "' AS SCHOOL_ID" . $columns . "
				FROM " . DBEscapeIdentifier( $table ) . "
				WHERE SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'school_marking_periods':

			DBQuery( "INSERT INTO school_marking_periods (PARENT_ID,SYEAR,MP,
					SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,
					POST_END_DATE,DOES_GRADES,DOES_COMMENTS,ROLLOVER_ID)
				SELECT PARENT_ID,SYEAR,MP,
					'" . $id . "' AS SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,
					POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_COMMENTS,MARKING_PERIOD_ID
				FROM school_marking_periods
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			if ( $DatabaseType === 'mysql' )
			{
				/**
				 * Fix MySQL 5.6 error Can't specify target table for update in FROM clause.
				 *
				 * @link https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
				 */
				DBQuery( "UPDATE school_marking_periods AS mp
					INNER JOIN school_marking_periods AS mp2 ON (mp2.SYEAR=mp.SYEAR
						AND mp2.SCHOOL_ID=mp.SCHOOL_ID
						AND mp2.ROLLOVER_ID=mp.PARENT_ID)
					SET mp.PARENT_ID=mp2.MARKING_PERIOD_ID
					WHERE mp.SYEAR='" . UserSyear() . "'
					AND mp.SCHOOL_ID='" . (int) $id . "'" );
			}
			else
			{
				DBQuery( "UPDATE school_marking_periods
					SET PARENT_ID=(SELECT mp.MARKING_PERIOD_ID
						FROM school_marking_periods mp
						WHERE mp.SYEAR=school_marking_periods.SYEAR
						AND mp.SCHOOL_ID=school_marking_periods.SCHOOL_ID
						AND mp.ROLLOVER_ID=school_marking_periods.PARENT_ID)
					WHERE SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . (int) $id . "'" );
			}

		break;

		case 'report_card_grades':

			DBQuery( "INSERT INTO report_card_grade_scales (SYEAR,SCHOOL_ID,TITLE,COMMENT,
					HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ROLLOVER_ID,GP_SCALE,GP_PASSING_VALUE,HRS_GPA_VALUE)
				SELECT SYEAR,
					'" . $id . "',TITLE,COMMENT,HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ID,
					GP_SCALE,GP_PASSING_VALUE,HRS_GPA_VALUE
				FROM report_card_grade_scales
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "INSERT INTO report_card_grades (SYEAR,SCHOOL_ID,TITLE,COMMENT,BREAK_OFF,
					GPA_VALUE,GRADE_SCALE_ID,SORT_ORDER)
				SELECT SYEAR,
					'" . $id . "',TITLE,COMMENT,BREAK_OFF,GPA_VALUE,
					(SELECT ID
						FROM report_card_grade_scales
						WHERE ROLLOVER_ID=report_card_grades.GRADE_SCALE_ID
						AND SCHOOL_ID='" . (int) $id . "'),
					SORT_ORDER
				FROM report_card_grades
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'report_card_comments':

			DBQuery( "INSERT INTO report_card_comments (SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,
					CATEGORY_ID,COURSE_ID)
				SELECT SYEAR,
					'" . $id . "',TITLE,SORT_ORDER,NULL,NULL
				FROM report_card_comments
				WHERE COURSE_ID IS NULL
				AND SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

		break;

		case 'eligibility_activities':
		case 'attendance_codes':

			$table_properties = db_properties( $table );

			$columns = '';

			foreach ( (array) $table_properties as $column => $values )
			{
				if ( $column !== 'ID'
					&& $column !== 'SYEAR'
					&& $column !== 'SCHOOL_ID' )
				{
					$columns .= ',' . DBEscapeIdentifier( $column );
				}
			}

			DBQuery( "INSERT INTO " . DBEscapeIdentifier( $table ) . " (SYEAR,SCHOOL_ID" . $columns . ")
				SELECT SYEAR,
					'" . $id . "' AS SCHOOL_ID" . $columns . "
				FROM " . DBEscapeIdentifier( $table ) . "
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

		break;
	}
}
