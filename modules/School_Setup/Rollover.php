<?php

DrawHeader( ProgramTitle() );

if ( AllowEdit( 'School_Setup/DatabaseBackup.php' ) )
{
	DrawHeader( '<a href="Modules.php?modname=School_Setup/DatabaseBackup.php">' .
		_( 'Database Backup' ) . '</a>' );
}

$next_syear = UserSyear() + 1;

$tables = [
	'SCHOOLS' => _( 'Schools' ),
	'STAFF' => _( 'Users' ),
	'SCHOOL_PERIODS' => _( 'School Periods' ),
	'SCHOOL_MARKING_PERIODS' => _( 'Marking Periods' ),
	'attendance_calendars' => _( 'Calendars' ),
	'attendance_codes' => _( 'Attendance Codes' ),
	'courses' => _( 'Courses' ),
	'STUDENT_ENROLLMENT_CODES' => _( 'Student Enrollment Codes' ),
	'STUDENT_ENROLLMENT' => _( 'Students' ),
	'REPORT_CARD_GRADES' => _( 'Report Card Grade Codes' ),
	'REPORT_CARD_COMMENTS' => _( 'Report Card Comment Codes' ),
	'program_config' => _( 'School Configuration' ),
];

$tables_tooltip = [
	'courses' => _( 'You <i>must</i> roll users, school periods, marking periods, calendars, attendance codes, and report card codes at the same time or before rolling courses.' ),
	'STUDENT_ENROLLMENT' => _( 'You <i>must</i> roll enrollment codes at the same time or before rolling students.' ),
	'REPORT_CARD_COMMENTS' => _( 'You <i>must</i> roll courses at the same time or before rolling report card comments.' ),
];

$no_school_tables = [ 'SCHOOLS' => true, 'STUDENT_ENROLLMENT_CODES' => true, 'STAFF' => true ];

if ( $RosarioModules['Eligibility'] )
{
	$tables += [ 'eligibility_activities' => _( 'Eligibility Activities' ) ];
}

if ( $RosarioModules['Food_Service'] )
{
	$tables += [ 'food_service_staff_accounts' => _( 'Food Service Staff Accounts' ) ];
}

if ( $RosarioModules['Discipline'] )
{
	$tables += [ 'discipline_field_usage' => _( 'Referral Form' ) ];
}

$table_list = '<table class="widefat center">';

foreach ( (array) $tables as $table => $name )
{
	if ( $table != 'food_service_staff_accounts' )
	{
		$exists_RET[$table] = DBGet( "SELECT count(*) AS COUNT
			FROM " . DBEscapeIdentifier( $table ) . "
			WHERE SYEAR='" . $next_syear . "'" .
			( empty( $no_school_tables[$table] ) ? " AND SCHOOL_ID='" . UserSchool() . "'" : '' ) );
	}
	else
	{
		$exists_RET['food_service_staff_accounts'] = DBGet( "SELECT count(*) AS COUNT
			FROM STAFF
			WHERE SYEAR='" . $next_syear . "'
			AND exists(SELECT * FROM food_service_staff_accounts WHERE STAFF_ID=STAFF.STAFF_ID)" );
	}

	$input_title = $name;

	if ( $exists_RET[$table][1]['COUNT'] > 0 )
	{
		$input_title = '<span style="color:grey">' . $input_title . ' (' . $exists_RET[$table][1]['COUNT'] . ')</span>';
	}

	if ( ! empty( $tables_tooltip[ $table ] ) )
	{
		$input_title .= '<div class="tooltip"><i>' . $tables_tooltip[ $table ] . '</i></div>';
	}

	$checked = ( $exists_RET[$table][1]['COUNT'] > 0 ) ? '' : ' checked';

	$table_list .= '<tr><td><label><input type="checkbox" value="Y" name="tables[' . $table . ']"' .
		$checked . '>&nbsp;' .
		$input_title . '</label></td></tr>';
}

$table_list .= '</table>';

$note[] = _( 'Greyed items already have data in the next school year (They might have been rolled).' );
$note[] = _( 'Rolling greyed items will delete already existing data in the next school year.' );

$table_list .= ErrorMessage( $note, 'note' );

// Hook.
do_action( 'School_Setup/Rollover.php|rollover_warnings' );

//FJ school year over one/two calendar years format

if ( Prompt(
	_( 'Confirm' ) . ' ' . _( 'Rollover' ),
	button( 'help', '', '', 'bigger' ) . '<br /><br />' .
	sprintf(
		_( 'Are you sure you want to roll the data for %s to the next school year?' ),
		FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) )
	),
	$table_list
) )
{
	if ( isset( $_REQUEST['tables']['courses'] )
		&& $exists_RET['REPORT_CARD_COMMENTS'][1]['COUNT']
		&& ! $_REQUEST['tables']['REPORT_CARD_COMMENTS'] )
	{
		// Fix SQL error foreign keys: Roll again Report Card Comment Codes when rolling Courses.
		$_REQUEST['tables']['REPORT_CARD_COMMENTS'] = 'Y';
	}

	if ( isset( $_REQUEST['tables']['SCHOOL_MARKING_PERIODS'] )
		&& $exists_RET['courses'][1]['COUNT']
		&& ! isset( $_REQUEST['tables']['courses'] ) )
	{
		// Fix SQL error foreign keys: Roll again Courses when rolling Marking Periods.
		$_REQUEST['tables']['courses'] = 'Y';
	}

	if ( isset( $_REQUEST['tables']['STUDENT_ENROLLMENT'] )
		&& ! $exists_RET['SCHOOLS'][1]['COUNT']
		&& ! isset( $_REQUEST['tables']['SCHOOLS'] ) )
	{
		// Fix SQL error foreign keys: Roll Schools before rolling Student Enrollment.
		// Insert SCHOOLS first.
		$_REQUEST['tables'] = array_merge( [ 'SCHOOLS' => 'Y' ], $_REQUEST['tables'] );
	}

	if ( ! ( isset( $_REQUEST['tables']['courses'] )
		&& ( ( ! isset( $_REQUEST['tables']['STAFF'] ) && $exists_RET['STAFF'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['SCHOOL_PERIODS'] ) && $exists_RET['SCHOOL_PERIODS'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['SCHOOL_MARKING_PERIODS'] ) && $exists_RET['SCHOOL_MARKING_PERIODS'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['attendance_calendars'] ) && $exists_RET['attendance_calendars'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['REPORT_CARD_GRADES'] ) && $exists_RET['REPORT_CARD_GRADES'][1]['COUNT'] < 1 ) ) ) )
	{
		if ( ! ( isset( $_REQUEST['tables']['REPORT_CARD_COMMENTS'] )
			&&  ( ! isset( $_REQUEST['tables']['courses'] )
				&& $exists_RET['courses'][1]['COUNT'] < 1 ) ) )
		{
			if ( ! empty( $_REQUEST['tables'] ) )
			{
				// Hook.
				do_action( 'School_Setup/Rollover.php|rollover_checks' );

				// Fix SQL error foreign keys: Process tables in reverse order.
				$tables_reverse = array_reverse( $_REQUEST['tables'] );

				foreach ( (array) $tables_reverse as $table => $value )
				{
					if ( ! $error )
					{
						// Delete first.
						Rollover( $table, 'delete' );
					}
				}

				foreach ( (array) $_REQUEST['tables'] as $table => $value )
				{
					if ( ! $error )
					{
						// Then insert, in normal order.
						Rollover( $table, 'insert' );
					}
				}

				/**
				 * Avoid regression due to lowercase table names:
				 * Maintain compatibility with add-ons using rollover_after action hooks & $_REQUEST['tables']
				 * to check if table rolled over:
				 * Add uppercase table names to $_REQUEST['tables']
				 *
				 * @deprecated since 10.0
				 */
				if ( ! empty( $_REQUEST['tables'] ) )
				{
					$request_uppercase_tables = [];

					foreach ( $_REQUEST['tables'] as $lowercase_table_name => $value )
					{
						$request_uppercase_tables[ mb_strtoupper( $lowercase_table_name ) ] = $value;
					}

					$_REQUEST['tables'] = array_merge( $_REQUEST['tables'], $request_uppercase_tables );
				}

				// @since 4.5 Rollover After action hook.
				do_action( 'School_Setup/Rollover.php|rollover_after' );
			}
		}
		else
		{
			$error[] = _( 'You <i>must</i> roll courses at the same time or before rolling report card comments.' );
		}
	}
	else
	{
		$error[] = _( 'You <i>must</i> roll users, school periods, marking periods, calendars, and report card codes at the same time or before rolling courses.' );
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

	if ( ! $error )
	{
		echo ErrorMessage(
			[
				button( 'check', '', '', 'bigger' ) .
				'&nbsp;' . _( 'The data have been rolled.' ),
			],
			'note'
		);

		$update_syear_warning = sprintf(
			_( 'Do not forget to update the $DefaultSyear to \'%d\' in the config.inc.php file when ready.' ),
			UserSyear() + 1
		);

		if ( strpos( $_SERVER['HTTP_HOST'], '.rosariosis.com' ) !== false )
		{
			$locale_short = substr( $locale, 0, 2 ) === 'fr' || substr( $locale, 0, 2 ) === 'es' ?
				substr( $locale, 0, 2 ) . '/' : '';

			$update_syear_warning = sprintf(
				_( 'Do not forget to update the default school year to \'%d\' from <a href="%s" target="_blank">your account</a> when ready.' ),
				UserSyear() + 1,
				URLEscape( 'https://www.rosariosis.com/' . $locale_short .	'account/' )
			);
		}

		echo ErrorMessage(
			[
				$update_syear_warning,
			],
			'warning'
		);
	}
	else
	{
		echo ErrorMessage( $error );
	}

	echo '<div class="center"><input type="submit" value="' . AttrEscape( _( 'OK' ) ) . '" /></div></form>';

	// Unset tables & redirect URL.
	RedirectURL( 'tables' );

	// Reload Side menu so new school year appear in the dropdown menu.
	echo '<script>ajaxLink("Side.php");</script>';
}

/**
 * Rollover table
 *
 * @param $table
 */
function Rollover( $table, $mode = 'delete' )
{
	global $next_syear, $RosarioModules;

	switch ( $table )
	{
		case 'SCHOOLS':

			if ( $mode === 'delete' )
			{
				$delete_schools_sql = "DELETE FROM SCHOOLS WHERE SYEAR='" . $next_syear . "'";

				$can_delete = DBTransDryRun( $delete_schools_sql );

				if ( $can_delete )
				{
					DBQuery( $delete_schools_sql );
				}

				break;
			}

			//FJ add School Fields
			$school_custom = '';
			$fields_RET = DBGet( "SELECT ID FROM school_fields" );

			foreach ( (array) $fields_RET as $field )
			{
				$school_custom .= ',CUSTOM_' . $field['ID'];
			}

			DBQuery( "INSERT INTO SCHOOLS (SYEAR,ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,
				PRINCIPAL,WWW_ADDRESS,SCHOOL_NUMBER,SHORT_NAME,REPORTING_GP_SCALE,
				NUMBER_DAYS_ROTATION" . $school_custom . ")
				SELECT SYEAR+1,ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,
				SCHOOL_NUMBER,SHORT_NAME,REPORTING_GP_SCALE,NUMBER_DAYS_ROTATION" . $school_custom . "
				FROM SCHOOLS
				WHERE SYEAR='" . UserSyear() . "'
				AND ID NOT IN(SELECT ID FROM SCHOOLS WHERE SYEAR='" . $next_syear . "')" );
			break;

		case 'STAFF':

			if ( $mode === 'delete' )
			{
				$delete_sql = "DELETE FROM food_service_staff_accounts
						WHERE exists(SELECT * FROM STAFF
							WHERE STAFF_ID=food_service_staff_accounts.STAFF_ID
							AND SYEAR='" . $next_syear . "');";

				$delete_sql .= "DELETE FROM STUDENTS_JOIN_USERS
					WHERE STAFF_ID IN (SELECT STAFF_ID FROM STAFF WHERE SYEAR='" . $next_syear . "');";

				$delete_sql .= "DELETE FROM STAFF_EXCEPTIONS
					WHERE USER_ID IN (SELECT STAFF_ID FROM STAFF WHERE SYEAR='" . $next_syear . "');";

				$delete_sql .= "DELETE FROM program_user_config
					WHERE USER_ID IN (SELECT STAFF_ID FROM STAFF WHERE SYEAR='" . $next_syear . "');";

				DBQuery( $delete_sql );

				$delete_staff_sql = "DELETE FROM STAFF WHERE SYEAR='" . $next_syear . "';";

				$can_delete = DBTransDryRun( $delete_staff_sql );

				if ( $can_delete )
				{
					DBQuery( $delete_staff_sql );
				}

				break;
			}

			$user_custom = '';
			$fields_RET = DBGet( "SELECT ID FROM staff_fields" );

			foreach ( (array) $fields_RET as $field )
			{
				if ( $field['ID'] === '200000000' )
				{
					// SQL Add Email & Phone to Staff Fields: skip Email, still in EMAIL column.
					continue;
				}

				$user_custom .= ',CUSTOM_' . $field['ID'];
			}

			if ( $RosarioModules['Food_Service'] )
			{
				DBQuery( "UPDATE food_service_staff_accounts
					SET STAFF_ID=(SELECT ROLLOVER_ID
						FROM STAFF
						WHERE STAFF_ID=food_service_staff_accounts.STAFF_ID
						LIMIT 1)
					WHERE exists(SELECT * FROM STAFF
						WHERE STAFF_ID=food_service_staff_accounts.STAFF_ID
						AND ROLLOVER_ID IS NOT NULL
						AND SYEAR='" . $next_syear . "')" );
			}

			// Roll Users again: update users which could not be deleted.
			DBQuery( "UPDATE STAFF SET
				CURRENT_SCHOOL_ID=s.CURRENT_SCHOOL_ID,TITLE=s.TITLE,FIRST_NAME=s.FIRST_NAME,
				LAST_NAME=s.LAST_NAME,MIDDLE_NAME=s.MIDDLE_NAME,NAME_SUFFIX=s.NAME_SUFFIX,
				USERNAME=s.USERNAME,PASSWORD=s.PASSWORD,EMAIL=s.EMAIL,
				PROFILE=s.PROFILE,HOMEROOM=s.HOMEROOM,LAST_LOGIN=s.LAST_LOGIN,SCHOOLS=s.SCHOOLS,
				PROFILE_ID=s.PROFILE_ID
				FROM (SELECT STAFF_ID,CURRENT_SCHOOL_ID,TITLE,FIRST_NAME,
					LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,EMAIL,PROFILE,
					HOMEROOM,LAST_LOGIN,SCHOOLS,PROFILE_ID
					FROM STAFF
					WHERE SYEAR='" . UserSyear() . "') s
				WHERE SYEAR='" . $next_syear . "'
				AND ROLLOVER_ID=s.STAFF_ID" );

			DBQuery( "INSERT INTO STAFF (SYEAR,CURRENT_SCHOOL_ID,TITLE,FIRST_NAME,
				LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,EMAIL,PROFILE,
				HOMEROOM,LAST_LOGIN,SCHOOLS,PROFILE_ID,ROLLOVER_ID" . $user_custom . ")
				SELECT SYEAR+1,CURRENT_SCHOOL_ID,TITLE,
				FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,EMAIL,
				PROFILE,HOMEROOM,NULL,SCHOOLS,PROFILE_ID,STAFF_ID" . $user_custom . "
				FROM STAFF
				WHERE SYEAR='" . UserSyear() . "'
				AND STAFF_ID NOT IN(SELECT ROLLOVER_ID FROM STAFF WHERE SYEAR='" . $next_syear . "')" );

			DBQuery( "INSERT INTO program_user_config (USER_ID,PROGRAM,TITLE,VALUE)
				SELECT s.STAFF_ID,puc.PROGRAM,puc.TITLE,puc.VALUE
				FROM STAFF s,program_user_config puc
				WHERE puc.USER_ID=s.ROLLOVER_ID
				AND s.SYEAR='" . $next_syear . "'" );

			DBQuery( "INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT)
				SELECT STAFF_ID,MODNAME,CAN_USE,CAN_EDIT
				FROM STAFF,STAFF_EXCEPTIONS
				WHERE USER_ID=ROLLOVER_ID
				AND SYEAR='" . $next_syear . "'" );

			DBQuery( "INSERT INTO STUDENTS_JOIN_USERS (STUDENT_ID,STAFF_ID)
				SELECT j.STUDENT_ID,s.STAFF_ID
				FROM STAFF s,STUDENTS_JOIN_USERS j
				WHERE j.STAFF_ID=s.ROLLOVER_ID
				AND s.SYEAR='" . $next_syear . "'" );

			break;

		case 'SCHOOL_PERIODS':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM SCHOOL_PERIODS
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . $next_syear . "'" );

				break;
			}

			DBQuery( "INSERT INTO SCHOOL_PERIODS (SYEAR,SCHOOL_ID,SORT_ORDER,TITLE,
				SHORT_NAME,LENGTH,ATTENDANCE,ROLLOVER_ID)
				SELECT SYEAR+1,SCHOOL_ID,SORT_ORDER,TITLE,
				SHORT_NAME,LENGTH,ATTENDANCE,PERIOD_ID FROM SCHOOL_PERIODS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			break;

		case 'attendance_calendars':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM attendance_calendars
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . $next_syear . "'" );

				break;
			}

			DBQuery( "INSERT INTO attendance_calendars (SYEAR,SCHOOL_ID,TITLE,DEFAULT_CALENDAR,ROLLOVER_ID)
				SELECT SYEAR+1,SCHOOL_ID,TITLE,DEFAULT_CALENDAR,CALENDAR_ID
				FROM attendance_calendars
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			break;

		case 'attendance_codes':

			if ( $mode === 'delete' )
			{
				$delete_sql = "DELETE FROM attendance_codes
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM attendance_code_categories
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				DBQuery( $delete_sql );

				break;
			}

			DBQuery( "INSERT INTO attendance_code_categories (SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,ROLLOVER_ID)
				SELECT SYEAR+1,SCHOOL_ID,TITLE,SORT_ORDER,ID
				FROM attendance_code_categories
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "INSERT INTO attendance_codes (SYEAR,SCHOOL_ID,TITLE,SHORT_NAME,TYPE,
				STATE_CODE,DEFAULT_CODE,TABLE_NAME,SORT_ORDER)
				SELECT c.SYEAR+1,c.SCHOOL_ID,c.TITLE,
				c.SHORT_NAME,c.TYPE,c.STATE_CODE,c.DEFAULT_CODE," .
				db_case( [ 'c.TABLE_NAME', "'0'", "'0'", '(SELECT ID FROM attendance_code_categories WHERE SCHOOL_ID=c.SCHOOL_ID AND ROLLOVER_ID=c.TABLE_NAME)' ] ) . ",c.SORT_ORDER
				FROM attendance_codes c
				WHERE c.SYEAR='" . UserSyear() . "'
				AND c.SCHOOL_ID='" . UserSchool() . "'" );

			break;

		case 'SCHOOL_MARKING_PERIODS':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM SCHOOL_MARKING_PERIODS
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );

				break;
			}

			DBQuery( "INSERT INTO SCHOOL_MARKING_PERIODS (PARENT_ID,SYEAR,MP,
				SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,
				POST_END_DATE,DOES_GRADES,DOES_COMMENTS,ROLLOVER_ID)
				SELECT PARENT_ID,SYEAR+1,MP,
				SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE+365,END_DATE+365,
				POST_START_DATE+365,POST_END_DATE+365,DOES_GRADES,DOES_COMMENTS,MARKING_PERIOD_ID
				FROM SCHOOL_MARKING_PERIODS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "UPDATE SCHOOL_MARKING_PERIODS
				SET PARENT_ID=(SELECT mp.MARKING_PERIOD_ID
					FROM SCHOOL_MARKING_PERIODS mp
					WHERE mp.SYEAR=school_marking_periods.SYEAR
					AND mp.SCHOOL_ID=school_marking_periods.SCHOOL_ID
					AND mp.ROLLOVER_ID=school_marking_periods.PARENT_ID
					LIMIT 1)
				WHERE SYEAR='" . $next_syear . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			//FJ ROLL Gradebook Config's Final Grading Percentages
			$db_case_array = [ 'puc.TITLE' ];

			$mp_next = DBGet( "SELECT MARKING_PERIOD_ID,ROLLOVER_ID,MP
				FROM SCHOOL_MARKING_PERIODS
				WHERE (MP='QTR' OR MP='SEM')
				AND SYEAR='" . $next_syear . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER" );

			// Fix SQL error NULL as TITLE when various. Explicitely list rollover MP titles.
			$mp_titles = [];

			foreach ( (array) $mp_next as $mp )
			{
				$db_case_array[] = "'FY-" . $mp['ROLLOVER_ID'] . "'";
				$db_case_array[] = "'FY-" . $mp['MARKING_PERIOD_ID'] . "'";

				$mp_titles[] = "'FY-" . $mp['ROLLOVER_ID'] . "'";

				if ( $mp['MP'] == 'QTR' )
				{
					$db_case_array[] = "'SEM-" . $mp['ROLLOVER_ID'] . "'";
					$db_case_array[] = "'SEM-" . $mp['MARKING_PERIOD_ID'] . "'";

					$mp_titles[] = "'SEM-" . $mp['ROLLOVER_ID'] . "'";
				}
			}

			DBQuery( "UPDATE program_user_config puc
				SET TITLE=(SELECT (" . db_case( $db_case_array ) . ")
					FROM STAFF s
					WHERE (puc.TITLE IN(" . implode( ',', $mp_titles ) . "))
					AND puc.PROGRAM='Gradebook'
					AND puc.USER_ID=s.STAFF_ID
					AND s.SYEAR='" . $next_syear . "')
				FROM STAFF s
				WHERE (puc.TITLE IN(" . implode( ',', $mp_titles ) . "))
				AND puc.PROGRAM='Gradebook'
				AND puc.USER_ID=s.STAFF_ID
				AND s.SYEAR='" . $next_syear . "'" );

			break;

		case 'courses':

			if ( $mode === 'delete' )
			{
				// Fix SQL error foreign key exists on tables GRADEBOOK_ASSIGNMENTS,GRADEBOOK_ASSIGNMENT_TYPES,SCHEDULE_REQUESTS
				// Error happens when an Assignment,or a Schedule request
				// was added for a rolled-over Course.
				$delete_sql = "DELETE FROM GRADEBOOK_ASSIGNMENTS
					WHERE COURSE_ID IN(SELECT COURSE_ID FROM courses
						WHERE SYEAR='" . $next_syear . "'
						AND SCHOOL_ID='" . UserSchool() . "')
					OR COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID FROM course_periods
						WHERE SYEAR='" . $next_syear . "'
						AND SCHOOL_ID='" . UserSchool() . "');";

				$delete_sql .= "DELETE FROM GRADEBOOK_ASSIGNMENT_TYPES
					WHERE COURSE_ID IN(SELECT COURSE_ID FROM courses
						WHERE SYEAR='" . $next_syear . "'
						AND SCHOOL_ID='" . UserSchool() . "');";

				$delete_sql .= "DELETE FROM SCHEDULE_REQUESTS
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM course_period_school_periods cpsp
					WHERE EXISTS (SELECT COURSE_PERIOD_ID
						FROM course_periods cp
						WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
						AND cp.SYEAR='" . $next_syear . "'
						AND cp.SCHOOL_ID='" . UserSchool() . "');";

				$delete_sql .= "DELETE FROM course_periods
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM courses
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM course_subjects
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				DBQuery( $delete_sql );

				break;
			}

			// ROLL course_subjects
			DBQuery( "INSERT INTO course_subjects (SYEAR,SCHOOL_ID,TITLE,SHORT_NAME,
				SORT_ORDER,ROLLOVER_ID)
				SELECT SYEAR+1,SCHOOL_ID,TITLE,
				SHORT_NAME,SORT_ORDER,SUBJECT_ID
				FROM course_subjects
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			// ROLL courses
			DBQuery( "INSERT INTO courses (SYEAR,SUBJECT_ID,SCHOOL_ID,GRADE_LEVEL,TITLE,
				SHORT_NAME,CREDIT_HOURS,DESCRIPTION,ROLLOVER_ID)
				SELECT SYEAR+1,
				(SELECT SUBJECT_ID
					FROM course_subjects s
					WHERE s.SCHOOL_ID=c.SCHOOL_ID
					AND s.ROLLOVER_ID=c.SUBJECT_ID
					LIMIT 1),SCHOOL_ID,GRADE_LEVEL,TITLE,SHORT_NAME,
				CREDIT_HOURS,DESCRIPTION,COURSE_ID FROM courses c
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			// ROLL course_periods
			DBQuery( "INSERT INTO course_periods (SYEAR,SCHOOL_ID,COURSE_ID,TITLE,
				SHORT_NAME,MP,MARKING_PERIOD_ID,TEACHER_ID,ROOM,TOTAL_SEATS,FILLED_SEATS,
				DOES_ATTENDANCE,GRADE_SCALE_ID,DOES_HONOR_ROLL,DOES_CLASS_RANK,DOES_BREAKOFF,
				GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,AVAILABILITY,PARENT_ID,
				CALENDAR_ID,ROLLOVER_ID)
				SELECT SYEAR+1,SCHOOL_ID,
				(SELECT COURSE_ID
					FROM courses c
					WHERE c.SCHOOL_ID=p.SCHOOL_ID
					AND c.ROLLOVER_ID=p.COURSE_ID
					LIMIT 1),TITLE,SHORT_NAME,MP,
				(SELECT MARKING_PERIOD_ID
					FROM SCHOOL_MARKING_PERIODS n
					WHERE n.MP=p.MP
					AND n.SCHOOL_ID=p.SCHOOL_ID
					AND n.ROLLOVER_ID=p.MARKING_PERIOD_ID
					LIMIT 1),
				(SELECT STAFF_ID
					FROM STAFF n
					WHERE n.ROLLOVER_ID=p.TEACHER_ID
					LIMIT 1),ROOM,TOTAL_SEATS,0 AS FILLED_SEATS,
				DOES_ATTENDANCE,(SELECT ID
					FROM REPORT_CARD_GRADE_SCALES
					WHERE SCHOOL_ID=p.SCHOOL_ID
					AND ROLLOVER_ID=p.GRADE_SCALE_ID
					LIMIT 1),DOES_HONOR_ROLL,DOES_CLASS_RANK,DOES_BREAKOFF,
				GENDER_RESTRICTION,HOUSE_RESTRICTION,CREDITS,AVAILABILITY,PARENT_ID,
				(SELECT CALENDAR_ID
					FROM attendance_calendars
					WHERE SCHOOL_ID=p.SCHOOL_ID
					AND ROLLOVER_ID=p.CALENDAR_ID
					LIMIT 1),COURSE_PERIOD_ID
				FROM course_periods p
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "UPDATE course_periods
				SET PARENT_ID=(SELECT cp.COURSE_PERIOD_ID
					FROM course_periods cp
					WHERE cp.ROLLOVER_ID=course_periods.PARENT_ID
					LIMIT 1)
				WHERE PARENT_ID IS NOT NULL
				AND SYEAR='" . $next_syear . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			$categories_RET = DBGet( "SELECT ID,ROLLOVER_ID
				FROM attendance_code_categories
				WHERE SYEAR='" . $next_syear . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND ROLLOVER_ID IS NOT NULL" );

			foreach ( (array) $categories_RET as $value )
			{
				DBQuery( "UPDATE course_periods
					SET DOES_ATTENDANCE=replace(DOES_ATTENDANCE,'," . $value['ROLLOVER_ID'] . ",','," . $value['ID'] . ",')
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );
			}

			//FJ multiple school periods for a course period
			//FJ bugfix SQL bug more than one row returned by a subquery
			// ROLL course_period_school_periods
			DBQuery( "INSERT INTO course_period_school_periods
				(COURSE_PERIOD_ID,PERIOD_ID,DAYS)
				SELECT (SELECT cp.COURSE_PERIOD_ID
						FROM course_periods cp
						WHERE cpsp.COURSE_PERIOD_ID=cp.ROLLOVER_ID
						LIMIT 1),
					(SELECT n.PERIOD_ID
						FROM SCHOOL_PERIODS n
						WHERE n.ROLLOVER_ID=cpsp.PERIOD_ID
						AND n.SYEAR='" . $next_syear . "'
						AND n.SCHOOL_ID='" . UserSchool() . "'
						LIMIT 1),
					DAYS
				FROM course_period_school_periods cpsp, course_periods cp
				WHERE cp.SYEAR='" . UserSyear() . "'
				AND cp.SCHOOL_ID='" . UserSchool() . "'
				AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID" );

			break;

		case 'STUDENT_ENROLLMENT':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM STUDENT_ENROLLMENT
					WHERE SYEAR='" . $next_syear . "'
					AND LAST_SCHOOL='" . UserSchool() . "'" );

				break;
			}

			$next_start_date = DBDate();

			// ROLL STUDENTS TO NEXT GRADE.
			// FJ do NOT roll students where next grade is NULL.
			DBQuery( "INSERT INTO STUDENT_ENROLLMENT
				(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
					DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
				SELECT SYEAR+1,SCHOOL_ID,STUDENT_ID,
					(SELECT NEXT_GRADE_ID
						FROM SCHOOL_GRADELEVELS g
						WHERE g.ID=e.GRADE_ID
						LIMIT 1),
					'" . $next_start_date . "' AS START_DATE,NULL AS END_DATE,
					(SELECT ID
						FROM STUDENT_ENROLLMENT_CODES
						WHERE SYEAR=e.SYEAR+1
						AND TYPE='Add'
						AND DEFAULT_CODE='Y'
						LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
					(SELECT CALENDAR_ID
						FROM attendance_calendars
						WHERE ROLLOVER_ID=e.CALENDAR_ID
						LIMIT 1),SCHOOL_ID,SCHOOL_ID
				FROM STUDENT_ENROLLMENT e
				WHERE e.SYEAR='" . UserSyear() . "'
				AND e.SCHOOL_ID='" . UserSchool() . "'
				AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
					AND '" . DBDate() . "'>=e.START_DATE )
				AND e.NEXT_SCHOOL='" . UserSchool() . "'
				AND (SELECT NEXT_GRADE_ID
					FROM SCHOOL_GRADELEVELS g
					WHERE g.ID=e.GRADE_ID
					LIMIT 1) IS NOT NULL" );

			// ROLL STUDENTS WHO ARE TO BE RETAINED.
			DBQuery( "INSERT INTO STUDENT_ENROLLMENT
				(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
					DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
				SELECT SYEAR+1,SCHOOL_ID,
					STUDENT_ID,GRADE_ID,'" . $next_start_date . "' AS START_DATE,
					NULL AS END_DATE,
					(SELECT ID
						FROM STUDENT_ENROLLMENT_CODES
						WHERE SYEAR=e.SYEAR+1
						AND TYPE='Add'
						AND DEFAULT_CODE='Y'
						LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
					(SELECT CALENDAR_ID
						FROM attendance_calendars
						WHERE ROLLOVER_ID=e.CALENDAR_ID
						LIMIT 1),SCHOOL_ID,SCHOOL_ID
				FROM STUDENT_ENROLLMENT e
				WHERE e.SYEAR='" . UserSyear() . "'
				AND e.SCHOOL_ID='" . UserSchool() . "'
				AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
					AND '" . DBDate() . "'>=e.START_DATE)
				AND e.NEXT_SCHOOL='0'" );

			// ROLL STUDENTS TO NEXT SCHOOL.
			// @since 6.4 SQL Roll students to next school: match Grade Level on Title.
			DBQuery( "INSERT INTO STUDENT_ENROLLMENT
				(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
					DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
				SELECT SYEAR+1,
					NEXT_SCHOOL,STUDENT_ID,
					(SELECT g.ID
						FROM SCHOOL_GRADELEVELS g
						WHERE (g.TITLE=(SELECT g2.TITLE
								FROM SCHOOL_GRADELEVELS g2
								WHERE g2.SCHOOL_ID=e.SCHOOL_ID
								AND g2.ID=e.GRADE_ID)
							OR g.SORT_ORDER=1)
						AND g.SCHOOL_ID=e.NEXT_SCHOOL
						ORDER BY g.SORT_ORDER DESC
						LIMIT 1),
					'" . $next_start_date . "' AS START_DATE,NULL AS END_DATE,
					(SELECT ID
						FROM STUDENT_ENROLLMENT_CODES
						WHERE SYEAR=e.SYEAR+1
						AND TYPE='Add'
						AND DEFAULT_CODE='Y'
						LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
					(SELECT CALENDAR_ID
						FROM attendance_calendars
						WHERE SCHOOL_ID=e.NEXT_SCHOOL
						AND SYEAR=e.SYEAR+1
						AND DEFAULT_CALENDAR='Y'
						LIMIT 1),NEXT_SCHOOL,SCHOOL_ID
				FROM STUDENT_ENROLLMENT e
				WHERE e.SYEAR='" . UserSyear() . "'
				AND e.SCHOOL_ID='" . UserSchool() . "'
				AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
					AND '" . DBDate() . "'>=e.START_DATE)
				AND e.NEXT_SCHOOL NOT IN ('" . UserSchool() . "','0','-1')" );

			break;

		case 'REPORT_CARD_GRADES':

			if ( $mode === 'delete' )
			{
				$delete_sql = "DELETE FROM REPORT_CARD_GRADE_SCALES
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM REPORT_CARD_GRADES
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				DBQuery( $delete_sql );

				break;
			}

			DBQuery( "INSERT INTO REPORT_CARD_GRADE_SCALES (SYEAR,SCHOOL_ID,TITLE,COMMENT,
				HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ROLLOVER_ID,GP_SCALE,HRS_GPA_VALUE,GP_PASSING_VALUE)
				SELECT SYEAR+1,SCHOOL_ID,
				TITLE,COMMENT,HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ID,GP_SCALE,HRS_GPA_VALUE,GP_PASSING_VALUE
				FROM REPORT_CARD_GRADE_SCALES
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "INSERT INTO REPORT_CARD_GRADES (SYEAR,SCHOOL_ID,TITLE,COMMENT,BREAK_OFF,
				GPA_VALUE,GRADE_SCALE_ID,SORT_ORDER)
				SELECT SYEAR+1,SCHOOL_ID,TITLE,
				COMMENT,BREAK_OFF,GPA_VALUE,(SELECT ID
					FROM REPORT_CARD_GRADE_SCALES
					WHERE ROLLOVER_ID=GRADE_SCALE_ID
					AND SCHOOL_ID=report_card_grades.SCHOOL_ID),SORT_ORDER
				FROM REPORT_CARD_GRADES
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			break;

		case 'REPORT_CARD_COMMENTS':

			if ( $mode === 'delete' )
			{
				$delete_sql = "DELETE FROM REPORT_CARD_COMMENT_CATEGORIES
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM REPORT_CARD_COMMENTS
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				DBQuery( $delete_sql );

				break;
			}

			DBQuery( "INSERT INTO REPORT_CARD_COMMENT_CATEGORIES (SYEAR,SCHOOL_ID,TITLE,
				SORT_ORDER,COURSE_ID,ROLLOVER_ID)
				SELECT SYEAR+1,
				SCHOOL_ID,TITLE,SORT_ORDER," .
				db_case( [ 'COURSE_ID', "''", 'NULL', "(SELECT COURSE_ID FROM courses WHERE ROLLOVER_ID=rc.COURSE_ID LIMIT 1)" ] ) . ",ID
				FROM REPORT_CARD_COMMENT_CATEGORIES rc
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "INSERT INTO REPORT_CARD_COMMENTS (SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,
				COURSE_ID,CATEGORY_ID,SCALE_ID)
				SELECT SYEAR+1,SCHOOL_ID,TITLE,
				SORT_ORDER," .
				db_case( [ 'COURSE_ID', "''", 'NULL', "(SELECT COURSE_ID FROM courses WHERE ROLLOVER_ID=rc.COURSE_ID LIMIT 1)" ] ) . "," .
				db_case( [ 'CATEGORY_ID', "''", 'NULL', "(SELECT ID FROM REPORT_CARD_COMMENT_CATEGORIES WHERE ROLLOVER_ID=rc.CATEGORY_ID)" ] ) . ",SCALE_ID
				FROM REPORT_CARD_COMMENTS rc
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			break;

		case 'eligibility_activities':
		case 'discipline_field_usage':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM " . DBEscapeIdentifier( $table ) . "
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );

				break;
			}

			$table_properties = db_properties( $table );
			$columns = '';

			foreach ( (array) $table_properties as $column => $values )
			{
				if ( $column != 'ID' && $column != 'SYEAR' )
				{
					$columns .= ',' . $column;
				}
			}

			DBQuery( "INSERT INTO " . DBEscapeIdentifier( $table ) . " (SYEAR" . $columns . ")
				SELECT SYEAR+1" . $columns . "
				FROM " . DBEscapeIdentifier( $table ) . "
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			break;

		// DOESN'T HAVE A SCHOOL_ID
		case 'STUDENT_ENROLLMENT_CODES':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM " . DBEscapeIdentifier( $table ) . " WHERE SYEAR='" . $next_syear . "'" );

				break;
			}

			$table_properties = db_properties( $table );
			$columns = '';

			foreach ( (array) $table_properties as $column => $values )
			{
				if ( $column != 'ID' && $column != 'SYEAR' )
				{
					$columns .= ',' . $column;
				}
			}

			DBQuery( "INSERT INTO " . DBEscapeIdentifier( $table ) . " (SYEAR" . $columns . ")
				SELECT SYEAR+1" . $columns . "
				FROM " . DBEscapeIdentifier( $table ) . "
				WHERE SYEAR='" . UserSyear() . "'" );

			break;

		case 'food_service_staff_accounts':

			DBQuery( "UPDATE food_service_staff_accounts fs1
				SET STAFF_ID=(SELECT STAFF_ID FROM STAFF WHERE ROLLOVER_ID=fs1.STAFF_ID)
				WHERE exists(SELECT *
					FROM STAFF
					WHERE ROLLOVER_ID=fs1.STAFF_ID
					AND SYEAR='" . $next_syear . "')
				AND NOT EXISTS(SELECT 1
					FROM food_service_staff_accounts fs2,STAFF st
					WHERE fs2.STAFF_ID=st.STAFF_ID
					AND st.SYEAR='" . $next_syear . "')" );

			break;

		case 'program_config':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM program_config WHERE SYEAR='" . $next_syear . "'" );

				break;
			}

			DBQuery( "INSERT INTO program_config (SYEAR,SCHOOL_ID,PROGRAM,TITLE,VALUE)
				SELECT SYEAR+1,SCHOOL_ID,PROGRAM,TITLE,VALUE
				FROM program_config
				WHERE SYEAR='" . UserSyear() . "'" );

			break;
	}
}
