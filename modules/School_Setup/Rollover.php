<?php

DrawHeader( ProgramTitle() );

if ( AllowEdit( 'School_Setup/DatabaseBackup.php' ) )
{
	DrawHeader( '<a href="Modules.php?modname=School_Setup/DatabaseBackup.php">' .
		_( 'Database Backup' ) . '</a>' );
}

$next_syear = UserSyear() + 1;

$tables = [
	'schools' => _( 'Schools' ),
	'staff' => _( 'Users' ),
	'school_periods' => _( 'School Periods' ),
	'school_marking_periods' => _( 'Marking Periods' ),
	'attendance_calendars' => _( 'Calendars' ),
	'attendance_codes' => _( 'Attendance Codes' ),
	'courses' => _( 'Courses' ),
	'student_enrollment_codes' => _( 'Student Enrollment Codes' ),
	'student_enrollment' => _( 'Students' ),
	'report_card_grades' => _( 'Grading Scales' ),
	'report_card_comments' => _( 'Report Card Comments' ),
	'program_config' => _( 'School Configuration' ),
];

$tables_tooltip = [
	'courses' => _( 'You <i>must</i> roll users, school periods, marking periods, calendars, and report card codes at the same time or before rolling courses.' ),
	'student_enrollment' => _( 'You <i>must</i> roll enrollment codes at the same time or before rolling students.' ),
	'report_card_comments' => _( 'You <i>must</i> roll courses at the same time or before rolling report card comments.' ),
];

$no_school_tables = [ 'schools' => true, 'student_enrollment_codes' => true, 'staff' => true ];

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
			FROM staff
			WHERE SYEAR='" . $next_syear . "'
			AND exists(SELECT 1 FROM food_service_staff_accounts WHERE STAFF_ID=staff.STAFF_ID)" );
	}

	$exists_table_count = $exists_RET[$table][1]['COUNT'];

	if ( $RosarioPlugins['Moodle']
		&& ( $table === 'staff' || $table === 'courses' )
		&& $exists_table_count > 0 )
	{
		// @since 11.3 Moodle plugin: only roll Users & Courses once
		continue;
	}

	$input_title = $name;

	if ( $exists_table_count > 0 )
	{
		$input_title = '<span style="color:grey">' . $input_title . ' (' . $exists_table_count . ')</span>';
	}

	if ( ! empty( $tables_tooltip[ $table ] ) )
	{
		$input_title .= '<div class="tooltip"><i>' . $tables_tooltip[ $table ] . '</i></div>';
	}

	$checked = ( $exists_table_count > 0 ) ? '' : ' checked';

	// Fix SQL error foreign keys: force roll Schools
	$readonly = ( $table === 'schools' && ! $exists_table_count ) ? ' onclick="return false;"' : '';

	$table_list .= '<tr><td><label><input type="checkbox" value="Y" name="tables[' . $table . ']"' .
		$checked . $readonly . '>&nbsp;' . $input_title . '</label>';

	if ( $table === 'courses' )
	{
		// @since 10.3 Add "Course Periods" checkbox
		$disabled = ( $exists_table_count > 0 ) ? ' disabled' : '';

		$table_list .= '<br />&#10551;&nbsp;<label><input type="checkbox" value="Y" id="course_periods" name="course_periods"' .
			$checked . $disabled . '>&nbsp;';

		$cp_title = _( 'Course Periods' );

		if ( $exists_table_count > 0 )
		{
			$cp_title = '<span style="color:grey">' . $cp_title . '</span>';
		}

		$table_list .= $cp_title . '</label>';

		// JS Enable / disable checkbox on Courses checkbox change.
		ob_start();

		?>
		<script>
			$('input[name="tables[courses]"]').change(function() {
				$('#course_periods').prop( 'disabled', ! this.checked );
			});
		</script>
		<?php

		$table_list .= ob_get_clean();
	}

	$table_list .= '</td></tr>';
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
		&& $exists_RET['report_card_comments'][1]['COUNT']
		&& ! $_REQUEST['tables']['report_card_comments'] )
	{
		// Fix SQL error foreign keys: Roll again Report Card Comment Codes when rolling Courses.
		$_REQUEST['tables']['report_card_comments'] = 'Y';
	}

	if ( isset( $_REQUEST['tables']['school_marking_periods'] )
		&& $exists_RET['courses'][1]['COUNT']
		&& ! isset( $_REQUEST['tables']['courses'] ) )
	{
		// Fix SQL error foreign keys: Roll again Courses when rolling Marking Periods.
		$_REQUEST['tables']['courses'] = 'Y';
	}

	if ( isset( $_REQUEST['tables']['student_enrollment'] )
		&& ! $exists_RET['schools'][1]['COUNT']
		&& ! isset( $_REQUEST['tables']['schools'] ) )
	{
		// Fix SQL error foreign keys: Roll Schools before rolling Student Enrollment.
		// Insert schools first.
		$_REQUEST['tables'] = array_merge( [ 'schools' => 'Y' ], $_REQUEST['tables'] );
	}

	if ( ! ( isset( $_REQUEST['tables']['courses'] )
		&& ( ( ! isset( $_REQUEST['tables']['staff'] ) && $exists_RET['staff'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['school_periods'] ) && $exists_RET['school_periods'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['school_marking_periods'] ) && $exists_RET['school_marking_periods'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['attendance_calendars'] ) && $exists_RET['attendance_calendars'][1]['COUNT'] < 1 )
			|| ( ! isset( $_REQUEST['tables']['report_card_grades'] ) && $exists_RET['report_card_grades'][1]['COUNT'] < 1 ) ) ) )
	{
		if ( ! ( isset( $_REQUEST['tables']['report_card_comments'] )
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
					$request_uppercase_tables = array_change_key_case( $_REQUEST['tables'], CASE_UPPER );

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
	global $next_syear,
		$RosarioModules,
		$DatabaseType;

	switch ( $table )
	{
		case 'schools':

			if ( $mode === 'delete' )
			{
				$delete_schools_sql = "DELETE FROM schools WHERE SYEAR='" . $next_syear . "'";

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

			DBQuery( "INSERT INTO schools (SYEAR,ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,
				PRINCIPAL,WWW_ADDRESS,SCHOOL_NUMBER,SHORT_NAME,REPORTING_GP_SCALE,
				NUMBER_DAYS_ROTATION" . $school_custom . ")
				SELECT SYEAR+1,ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,
				SCHOOL_NUMBER,SHORT_NAME,REPORTING_GP_SCALE,NUMBER_DAYS_ROTATION" . $school_custom . "
				FROM schools
				WHERE SYEAR='" . UserSyear() . "'
				AND ID NOT IN(SELECT s2.ID FROM schools s2 WHERE s2.SYEAR='" . $next_syear . "')" );
			break;

		case 'staff':

			if ( $mode === 'delete' )
			{
				$delete_sql = "DELETE FROM food_service_staff_accounts
					WHERE exists(SELECT 1 FROM staff
						WHERE STAFF_ID=food_service_staff_accounts.STAFF_ID
						AND SYEAR='" . $next_syear . "');";

				$delete_sql .= "DELETE FROM students_join_users
					WHERE STAFF_ID IN (SELECT STAFF_ID FROM staff WHERE SYEAR='" . $next_syear . "');";

				$delete_sql .= "DELETE FROM staff_exceptions
					WHERE USER_ID IN (SELECT STAFF_ID FROM staff WHERE SYEAR='" . $next_syear . "');";

				$delete_sql .= "DELETE FROM program_user_config
					WHERE USER_ID IN (SELECT STAFF_ID FROM staff WHERE SYEAR='" . $next_syear . "');";

				DBQuery( $delete_sql );

				$delete_staff_sql = "DELETE FROM staff WHERE SYEAR='" . $next_syear . "';";

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
						FROM staff
						WHERE STAFF_ID=food_service_staff_accounts.STAFF_ID
						LIMIT 1)
					WHERE exists(SELECT 1 FROM staff
						WHERE STAFF_ID=food_service_staff_accounts.STAFF_ID
						AND ROLLOVER_ID IS NOT NULL
						AND SYEAR='" . $next_syear . "')" );
			}

			/**
			 * Fix MySQL syntax error: no FROM allowed inside UPDATE, use multi-table syntax
			 *
			 * Note: WITH clause only available in MySQL 8+ & PostgreSQL 9.1+.
			 */
			if ( $DatabaseType === 'mysql' )
			{
				$update_users_sql = "UPDATE staff s,(SELECT STAFF_ID,CURRENT_SCHOOL_ID,TITLE,FIRST_NAME,
					LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,EMAIL,PROFILE,
					HOMEROOM,LAST_LOGIN,SCHOOLS,PROFILE_ID
					FROM staff
					WHERE SYEAR='" . UserSyear() . "') s2 SET
				s.CURRENT_SCHOOL_ID=s2.CURRENT_SCHOOL_ID,s.TITLE=s2.TITLE,s.FIRST_NAME=s2.FIRST_NAME,
				s.LAST_NAME=s2.LAST_NAME,s.MIDDLE_NAME=s2.MIDDLE_NAME,s.NAME_SUFFIX=s2.NAME_SUFFIX,
				s.USERNAME=s2.USERNAME,s.PASSWORD=s2.PASSWORD,s.EMAIL=s2.EMAIL,
				s.PROFILE=s2.PROFILE,s.HOMEROOM=s2.HOMEROOM,s.LAST_LOGIN=s2.LAST_LOGIN,s.SCHOOLS=s2.SCHOOLS,
				s.PROFILE_ID=s2.PROFILE_ID
				WHERE s.SYEAR='" . $next_syear . "'
				AND s.ROLLOVER_ID=s2.STAFF_ID";
			}
			else
			{
				$update_users_sql = "UPDATE staff SET
				CURRENT_SCHOOL_ID=s2.CURRENT_SCHOOL_ID,TITLE=s2.TITLE,FIRST_NAME=s2.FIRST_NAME,
				LAST_NAME=s2.LAST_NAME,MIDDLE_NAME=s2.MIDDLE_NAME,NAME_SUFFIX=s2.NAME_SUFFIX,
				USERNAME=s2.USERNAME,PASSWORD=s2.PASSWORD,EMAIL=s2.EMAIL,
				PROFILE=s2.PROFILE,HOMEROOM=s2.HOMEROOM,LAST_LOGIN=s2.LAST_LOGIN,SCHOOLS=s2.SCHOOLS,
				PROFILE_ID=s2.PROFILE_ID
				FROM (SELECT STAFF_ID,CURRENT_SCHOOL_ID,TITLE,FIRST_NAME,
					LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,EMAIL,PROFILE,
					HOMEROOM,LAST_LOGIN,SCHOOLS,PROFILE_ID
					FROM staff
					WHERE SYEAR='" . UserSyear() . "') s2
				WHERE SYEAR='" . $next_syear . "'
				AND ROLLOVER_ID=s2.STAFF_ID";
			}

			// Roll Users again: update users which could not be deleted.
			DBQuery( $update_users_sql );

			DBQuery( "INSERT INTO staff (SYEAR,CURRENT_SCHOOL_ID,TITLE,FIRST_NAME,
				LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,EMAIL,PROFILE,
				HOMEROOM,LAST_LOGIN,SCHOOLS,PROFILE_ID,ROLLOVER_ID" . $user_custom . ")
				SELECT SYEAR+1,CURRENT_SCHOOL_ID,TITLE,
				FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,USERNAME,PASSWORD,EMAIL,
				PROFILE,HOMEROOM,NULL,SCHOOLS,PROFILE_ID,STAFF_ID" . $user_custom . "
				FROM staff
				WHERE SYEAR='" . UserSyear() . "'
				AND STAFF_ID NOT IN(SELECT s2.ROLLOVER_ID FROM staff s2 WHERE s2.SYEAR='" . $next_syear . "')" );

			DBQuery( "INSERT INTO program_user_config (USER_ID,PROGRAM,TITLE,VALUE)
				SELECT s.STAFF_ID,puc.PROGRAM,puc.TITLE,puc.VALUE
				FROM staff s,program_user_config puc
				WHERE puc.USER_ID=s.ROLLOVER_ID
				AND s.SYEAR='" . $next_syear . "'" );

			DBQuery( "INSERT INTO staff_exceptions (USER_ID,MODNAME,CAN_USE,CAN_EDIT)
				SELECT STAFF_ID,MODNAME,CAN_USE,CAN_EDIT
				FROM staff,staff_exceptions
				WHERE USER_ID=ROLLOVER_ID
				AND SYEAR='" . $next_syear . "'" );

			DBQuery( "INSERT INTO students_join_users (STUDENT_ID,STAFF_ID)
				SELECT j.STUDENT_ID,s.STAFF_ID
				FROM staff s,students_join_users j
				WHERE j.STAFF_ID=s.ROLLOVER_ID
				AND s.SYEAR='" . $next_syear . "'" );

			break;

		case 'school_periods':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM school_periods
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . $next_syear . "'" );

				break;
			}

			DBQuery( "INSERT INTO school_periods (SYEAR,SCHOOL_ID,SORT_ORDER,TITLE,
				SHORT_NAME,LENGTH,ATTENDANCE,ROLLOVER_ID)
				SELECT SYEAR+1,SCHOOL_ID,SORT_ORDER,TITLE,
				SHORT_NAME,LENGTH,ATTENDANCE,PERIOD_ID FROM school_periods
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

		case 'school_marking_periods':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM school_marking_periods
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );

				break;
			}

			DBQuery( "INSERT INTO school_marking_periods (PARENT_ID,SYEAR,MP,
				SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,
				POST_END_DATE,DOES_GRADES,DOES_COMMENTS,ROLLOVER_ID)
				SELECT PARENT_ID,SYEAR+1,MP,
				SCHOOL_ID,TITLE,SHORT_NAME,SORT_ORDER,
				(START_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '365 DAY' : "'365 DAY'" ) . "),
				(END_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '365 DAY' : "'365 DAY'" ) . "),
				(POST_START_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '365 DAY' : "'365 DAY'" ) . "),
				(POST_END_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '365 DAY' : "'365 DAY'" ) . "),
				DOES_GRADES,DOES_COMMENTS,MARKING_PERIOD_ID
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
					WHERE mp.SYEAR='" . $next_syear . "'
					AND mp.SCHOOL_ID='" . UserSchool() . "'" );
			}
			else
			{
				DBQuery( "UPDATE school_marking_periods
					SET PARENT_ID=(SELECT mp.MARKING_PERIOD_ID
						FROM school_marking_periods mp
						WHERE mp.SYEAR=school_marking_periods.SYEAR
						AND mp.SCHOOL_ID=school_marking_periods.SCHOOL_ID
						AND mp.ROLLOVER_ID=school_marking_periods.PARENT_ID)
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );
			}

			// ROLL Gradebook Config's Final Grading Percentages
			$db_case_array = [ 'puc.TITLE' ];

			$mp_next = DBGet( "SELECT MARKING_PERIOD_ID,ROLLOVER_ID,MP
				FROM school_marking_periods
				WHERE (MP='QTR' OR MP='SEM')
				AND SYEAR='" . $next_syear . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

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

			if ( $mp_next )
			{
				// Fix MySQL syntax error: no FROM allowed inside UPDATE, use subquery
				DBQuery( "UPDATE program_user_config puc
					SET TITLE=(SELECT (" . db_case( $db_case_array ) . ")
						FROM staff s
						WHERE (puc.TITLE IN(" . implode( ',', $mp_titles ) . "))
						AND puc.PROGRAM='Gradebook'
						AND puc.USER_ID=s.STAFF_ID
						AND s.SYEAR='" . $next_syear . "')
					WHERE (puc.TITLE IN(" . implode( ',', $mp_titles ) . "))
					AND puc.PROGRAM='Gradebook'
					AND puc.USER_ID IN (SELECT s2.STAFF_ID
						FROM staff s2
						WHERE s2.SYEAR='" . $next_syear . "')" );

				// @since 10.7 ROLL Gradebook Config's Final Grading Percentages for Admin (overridden)
				DBQuery( "INSERT INTO program_user_config (USER_ID,PROGRAM,TITLE,VALUE)
					SELECT puc.USER_ID,puc.PROGRAM,(" . db_case( $db_case_array ) . "),puc.VALUE
					FROM program_user_config puc
					WHERE puc.USER_ID='-1'
					AND puc.PROGRAM='Gradebook'
					AND puc.TITLE IN(" . implode( ',', $mp_titles ) . ")" );
			}

			break;

		case 'courses':

			if ( $mode === 'delete' )
			{
				// Fix SQL error foreign key exists on tables gradebook_assignments,gradebook_assignment_types,schedule_requests
				// Error happens when an Assignment,or a Schedule request
				// was added for a rolled-over Course.
				$delete_sql = "DELETE FROM gradebook_assignments
					WHERE COURSE_ID IN(SELECT COURSE_ID FROM courses
						WHERE SYEAR='" . $next_syear . "'
						AND SCHOOL_ID='" . UserSchool() . "')
					OR COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID FROM course_periods
						WHERE SYEAR='" . $next_syear . "'
						AND SCHOOL_ID='" . UserSchool() . "');";

				$delete_sql .= "DELETE FROM gradebook_assignment_types
					WHERE COURSE_ID IN(SELECT COURSE_ID FROM courses
						WHERE SYEAR='" . $next_syear . "'
						AND SCHOOL_ID='" . UserSchool() . "');";

				$delete_sql .= "DELETE FROM schedule_requests
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				/**
				 * Fix MySQL syntax error: no table alias in DELETE.
				 *
				 * @link https://stackoverflow.com/questions/34353799/can-aliases-be-used-in-a-sql-delete-query
				 */
				$delete_sql .= "DELETE FROM course_period_school_periods
					WHERE COURSE_PERIOD_ID IN (SELECT COURSE_PERIOD_ID
						FROM course_periods
						WHERE SYEAR='" . $next_syear . "'
						AND SCHOOL_ID='" . UserSchool() . "');";

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

			if ( ! isset( $_REQUEST['course_periods'] )
				|| $_REQUEST['course_periods'] !== 'Y' )
			{
				// Do NOT roll Course Periods, break.
				// @since 10.3 Add "Course Periods" checkbox
				break;
			}

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
					FROM school_marking_periods n
					WHERE n.MP=p.MP
					AND n.SCHOOL_ID=p.SCHOOL_ID
					AND n.ROLLOVER_ID=p.MARKING_PERIOD_ID
					LIMIT 1),
				(SELECT STAFF_ID
					FROM staff n
					WHERE n.ROLLOVER_ID=p.TEACHER_ID
					LIMIT 1),ROOM,TOTAL_SEATS,0 AS FILLED_SEATS,
				DOES_ATTENDANCE,(SELECT ID
					FROM report_card_grade_scales
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

			if ( $DatabaseType === 'mysql' )
			{
				/**
				 * Fix MySQL 5.6 error Can't specify target table for update in FROM clause.
				 *
				 * @link https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
				 */
				DBQuery( "UPDATE course_periods cp
					INNER JOIN course_periods AS cp2 ON (cp2.ROLLOVER_ID=cp.PARENT_ID)
					SET cp.PARENT_ID=cp2.COURSE_PERIOD_ID
					WHERE cp.PARENT_ID IS NOT NULL
					AND cp.SYEAR='" . $next_syear . "'
					AND cp.SCHOOL_ID='" . UserSchool() . "'" );
			}
			else
			{
				DBQuery( "UPDATE course_periods
					SET PARENT_ID=(SELECT cp.COURSE_PERIOD_ID
						FROM course_periods cp
						WHERE cp.ROLLOVER_ID=course_periods.PARENT_ID
						LIMIT 1)
					WHERE PARENT_ID IS NOT NULL
					AND SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "'" );
			}

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
						FROM school_periods n
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

		case 'student_enrollment':

			if ( $mode === 'delete' )
			{
				DBQuery( "DELETE FROM student_enrollment
					WHERE SYEAR='" . $next_syear . "'
					AND LAST_SCHOOL='" . UserSchool() . "'" );

				break;
			}

			$next_start_date = DBDate();

			// ROLL STUDENTS TO NEXT GRADE.
			// FJ do NOT roll students where next grade is NULL.
			DBQuery( "INSERT INTO student_enrollment
				(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
					DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
				SELECT SYEAR+1,SCHOOL_ID,STUDENT_ID,
					(SELECT NEXT_GRADE_ID
						FROM school_gradelevels g
						WHERE g.ID=e.GRADE_ID
						LIMIT 1),
					'" . $next_start_date . "' AS START_DATE,NULL AS END_DATE,
					(SELECT ID
						FROM student_enrollment_codes
						WHERE SYEAR=e.SYEAR+1
						AND TYPE='Add'
						AND DEFAULT_CODE='Y'
						LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
					(SELECT CALENDAR_ID
						FROM attendance_calendars
						WHERE ROLLOVER_ID=e.CALENDAR_ID
						LIMIT 1),SCHOOL_ID,SCHOOL_ID
				FROM student_enrollment e
				WHERE e.SYEAR='" . UserSyear() . "'
				AND e.SCHOOL_ID='" . UserSchool() . "'
				AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
					AND '" . DBDate() . "'>=e.START_DATE )
				AND e.NEXT_SCHOOL='" . UserSchool() . "'
				AND (SELECT NEXT_GRADE_ID
					FROM school_gradelevels g
					WHERE g.ID=e.GRADE_ID
					LIMIT 1) IS NOT NULL" );

			// ROLL STUDENTS WHO ARE TO BE RETAINED.
			DBQuery( "INSERT INTO student_enrollment
				(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
					DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
				SELECT SYEAR+1,SCHOOL_ID,
					STUDENT_ID,GRADE_ID,'" . $next_start_date . "' AS START_DATE,
					NULL AS END_DATE,
					(SELECT ID
						FROM student_enrollment_codes
						WHERE SYEAR=e.SYEAR+1
						AND TYPE='Add'
						AND DEFAULT_CODE='Y'
						LIMIT 1) AS ENROLLMENT_CODE,NULL AS DROP_CODE,
					(SELECT CALENDAR_ID
						FROM attendance_calendars
						WHERE ROLLOVER_ID=e.CALENDAR_ID
						LIMIT 1),SCHOOL_ID,SCHOOL_ID
				FROM student_enrollment e
				WHERE e.SYEAR='" . UserSyear() . "'
				AND e.SCHOOL_ID='" . UserSchool() . "'
				AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
					AND '" . DBDate() . "'>=e.START_DATE)
				AND e.NEXT_SCHOOL='0'" );

			// ROLL STUDENTS TO NEXT SCHOOL.
			// @since 6.4 SQL Roll students to next school: match Grade Level on Title.
			DBQuery( "INSERT INTO student_enrollment
				(SYEAR,SCHOOL_ID,STUDENT_ID,GRADE_ID,START_DATE,END_DATE,ENROLLMENT_CODE,
					DROP_CODE,CALENDAR_ID,NEXT_SCHOOL,LAST_SCHOOL)
				SELECT SYEAR+1,
					NEXT_SCHOOL,STUDENT_ID,
					(SELECT g.ID
						FROM school_gradelevels g
						WHERE (g.TITLE=(SELECT g2.TITLE
								FROM school_gradelevels g2
								WHERE g2.SCHOOL_ID=e.SCHOOL_ID
								AND g2.ID=e.GRADE_ID)
							OR g.SORT_ORDER=1)
						AND g.SCHOOL_ID=e.NEXT_SCHOOL
						ORDER BY g.SORT_ORDER IS NULL,g.SORT_ORDER DESC
						LIMIT 1),
					'" . $next_start_date . "' AS START_DATE,NULL AS END_DATE,
					(SELECT ID
						FROM student_enrollment_codes
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
				FROM student_enrollment e
				WHERE e.SYEAR='" . UserSyear() . "'
				AND e.SCHOOL_ID='" . UserSchool() . "'
				AND ( ('" . DBDate() . "' BETWEEN e.START_DATE AND e.END_DATE OR e.END_DATE IS NULL)
					AND '" . DBDate() . "'>=e.START_DATE)
				AND e.NEXT_SCHOOL NOT IN ('" . UserSchool() . "','0','-1')" );

			break;

		case 'report_card_grades':

			if ( $mode === 'delete' )
			{
				$delete_sql = "DELETE FROM report_card_grade_scales
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM report_card_grades
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				DBQuery( $delete_sql );

				break;
			}

			DBQuery( "INSERT INTO report_card_grade_scales (SYEAR,SCHOOL_ID,TITLE,COMMENT,
				HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ROLLOVER_ID,GP_SCALE,HRS_GPA_VALUE,GP_PASSING_VALUE)
				SELECT SYEAR+1,SCHOOL_ID,
				TITLE,COMMENT,HR_GPA_VALUE,HHR_GPA_VALUE,SORT_ORDER,ID,GP_SCALE,HRS_GPA_VALUE,GP_PASSING_VALUE
				FROM report_card_grade_scales
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "INSERT INTO report_card_grades (SYEAR,SCHOOL_ID,TITLE,COMMENT,BREAK_OFF,
				GPA_VALUE,GRADE_SCALE_ID,SORT_ORDER)
				SELECT SYEAR+1,SCHOOL_ID,TITLE,
				COMMENT,BREAK_OFF,GPA_VALUE,(SELECT ID
					FROM report_card_grade_scales
					WHERE ROLLOVER_ID=GRADE_SCALE_ID
					AND SCHOOL_ID=report_card_grades.SCHOOL_ID),SORT_ORDER
				FROM report_card_grades
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			break;

		case 'report_card_comments':

			if ( $mode === 'delete' )
			{
				$delete_sql = "DELETE FROM report_card_comment_categories
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				$delete_sql .= "DELETE FROM report_card_comments
					WHERE SYEAR='" . $next_syear . "'
					AND SCHOOL_ID='" . UserSchool() . "';";

				DBQuery( $delete_sql );

				break;
			}

			DBQuery( "INSERT INTO report_card_comment_categories (SYEAR,SCHOOL_ID,TITLE,
				SORT_ORDER,COURSE_ID,ROLLOVER_ID)
				SELECT SYEAR+1,
				SCHOOL_ID,TITLE,SORT_ORDER," .
				db_case( [ 'COURSE_ID', "''", 'NULL', "(SELECT COURSE_ID FROM courses WHERE ROLLOVER_ID=rc.COURSE_ID LIMIT 1)" ] ) . ",ID
				FROM report_card_comment_categories rc
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			DBQuery( "INSERT INTO report_card_comments (SYEAR,SCHOOL_ID,TITLE,SORT_ORDER,
				COURSE_ID,CATEGORY_ID,SCALE_ID)
				SELECT SYEAR+1,SCHOOL_ID,TITLE,
				SORT_ORDER," .
				db_case( [ 'COURSE_ID', "''", 'NULL', "(SELECT COURSE_ID FROM courses WHERE ROLLOVER_ID=rc.COURSE_ID LIMIT 1)" ] ) . "," .
				db_case( [ 'CATEGORY_ID', "''", 'NULL', "(SELECT ID FROM report_card_comment_categories WHERE ROLLOVER_ID=rc.CATEGORY_ID)" ] ) . ",SCALE_ID
				FROM report_card_comments rc
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
		case 'student_enrollment_codes':

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

			/**
			 * Fix MySQL 5.6 error Can't specify target table for update in FROM clause.
			 *
			 * @link https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
			 */
			DBQuery( "UPDATE food_service_staff_accounts fs1
				SET STAFF_ID=(SELECT STAFF_ID FROM staff WHERE ROLLOVER_ID=fs1.STAFF_ID LIMIT 1)
				WHERE exists(SELECT 1
					FROM staff
					WHERE ROLLOVER_ID=fs1.STAFF_ID
					AND SYEAR='" . $next_syear . "')
				AND NOT EXISTS(SELECT 1 FROM (SELECT 1
					FROM food_service_staff_accounts fs2,staff st
					WHERE fs2.STAFF_ID=st.STAFF_ID
					AND st.SYEAR='" . $next_syear . "') AS fs3)" );

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
