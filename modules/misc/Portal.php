<?php
require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
require_once 'ProgramFunctions/Dashboard.fnc.php';

if ( $RosarioModules['Discipline'] )
{
	// Discipline alerts.
	require_once 'modules/Discipline/includes/PortalAlerts.fnc.php';
}

if ( $RosarioModules['Student_Billing'] )
{
	// @since 5.4 Student Billing alerts.
	require_once 'modules/Student_Billing/includes/PortalAlerts.fnc.php';
}

if ( $_REQUEST['modfunc'] === 'redirect_take_attendance' )
{
	_redirectTakeAttendance();
}

// AJAX poll vote call.

if ( $_REQUEST['modfunc'] === 'poll_vote'
	&& ! empty( $_POST['votes'] ) )
{
	// Fix #308 Unauthenticated SQL injection. Use sanitized $_REQUEST.
	foreach ( (array) $_REQUEST['votes'] as $poll_id => $votes_array )
	{
		if ( ! empty( $votes_array ) )
		{
			// Result is displayed inside "divPortalPoll[id]" target div.
			echo PortalPollsVote( $poll_id, $votes_array );

			// Do not go further.
			exit();
		}
	}
}

DrawHeader( ProgramTitle() );

DrawHeader( '<span id="salute"></span>' );

?>
<script>
var hours = new Date().getHours(),
	salute = document.getElementById("salute");
	if (hours < 12)
		salute.innerHTML=<?php echo json_encode( sprintf( _( 'Good Morning, %s.' ), User( 'NAME' ) ) ); ?>;
	else if (hours < 18)
		salute.innerHTML=<?php echo json_encode( sprintf( _( 'Good Afternoon, %s.' ), User( 'NAME' ) ) ); ?>;
	else
		salute.innerHTML=<?php echo json_encode( sprintf( _( 'Good Evening, %s.' ), User( 'NAME' ) ) ); ?>;
</script>
<?php

$welcome = [];

if ( ! empty( $_SESSION['LAST_LOGIN'] ) )
{
	$welcome[] = sprintf(
		_( 'Your last login was <b>%s</b>.' ),
		ProperDateTime( $_SESSION['LAST_LOGIN'] )
	);
}

if ( ! empty( $failed_login ) )
{
	$welcome[] = ErrorMessage(
		[ sprintf(
			_( 'There have been <b>%d</b> failed login attempts since your last successful login.' ),
			$failed_login
		) ],
		'warning'
	);
}

switch ( User( 'PROFILE' ) )
{
	case 'admin':
		$welcome[] = _( 'You are an <b>Administrator</b> on the system.' );

		break;

	case 'teacher':
		$welcome[] = _( 'You are a <b>Teacher</b> on the system.' );

		break;

	case 'parent':
		$welcome[] = _( 'You are a <b>Parent</b> on the system.' );

		break;

	default:

		$welcome[] = _( 'You are a <b>Student</b> on the system.' );
}

DrawHeader( implode( '<br />', $welcome ) );

// Do portal_alerts hook.
do_action( 'misc/Portal.php|portal_alerts' );

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $warning, 'warning' );

// Dashboard.
Dashboard();

DashboardOutput();

$portal_LO_options = [ 'save' => false, 'search' => false ];

$notes_LO_columns = [
	'CREATED_AT' => _( 'Date Posted' ),
	'TITLE' => _( 'Title' ),
	'CONTENT' => _( 'Note' ),
	'FILE_ATTACHED' => _( 'File Attached' ),
];

$polls_LO_columns = [
	'CREATED_AT' => _( 'Date Posted' ),
	'TITLE' => _( 'Title' ),
	'OPTIONS' => _( 'Poll' ),
];

$events_LO_columns = [
	'DAY' => _( 'Day' ),
	'SCHOOL_DATE' => _( 'Date' ),
	'TITLE' => _( 'Event' ),
	'DESCRIPTION' => _( 'Description' ),
];

$missing_attendance_LO_columns = [
	'SCHOOL_DATE' => _( 'Date' ),
	'TITLE' => _( 'Period' ) . ' ' . _( 'Days' ) . ' - ' . _( 'Short Name' ) . ' - ' . _( 'Teacher' ),
];

if ( User( 'PROFILE' ) !== 'student'
	|| SchoolInfo( 'SCHOOLS_NB' ) > 1 )
{
	// More than 1 school, display School column.
	$notes_LO_columns['SCHOOL'] = _( 'School' );

	$polls_LO_columns['SCHOOL'] = _( 'School' );

	$events_LO_columns['SCHOOL'] = _( 'School' );
}

if ( User( 'PROFILE' ) === 'admin'
	&& SchoolInfo( 'SCHOOLS_NB' ) > 1 )
{
	$missing_attendance_LO_columns['SCHOOL'] = _( 'School' );
}

$assignments_LO_columns = [
	// 'DAY' => _( 'Day' ),
	'DUE_DATE' => _( 'Due Date' ),
	'ASSIGNMENT_TITLE' => _( 'Assignment' ),
	// 'DESCRIPTION' => _( 'Notes' ),
	'COURSE' => _( 'Course' ),
];

if ( User( 'PROFILE' ) === 'student'
	|| User( 'PROFILE' ) === 'parent' )
{
	// Student or Parent, add Teacher & Submitted columns.
	$assignments_LO_columns['STAFF_ID'] = _( 'Teacher' );
	$assignments_LO_columns['SUBMITTED'] = _( 'Submitted' );
}

// @todo Create functions & put in misc/includes/Portal.fnc.php.
switch ( User( 'PROFILE' ) )
{
	case 'admin':
		$PHPCheck = PHPCheck();

		if ( ! empty( $PHPCheck ) )
		{
			echo ErrorMessage( $PHPCheck, 'warning' );
		}

		// FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL.
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,CONCAT('<b>', pn.TITLE, '</b>') AS TITLE,pn.CONTENT FROM portal_notes pn,schools s,staff st WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pn.SCHOOL_ID, ',') IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(CONCAT(',', st.PROFILE_ID, ',') IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC",array('CREATED_AT' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,CONCAT('<b>', pn.TITLE, '</b>') AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM portal_notes pn,schools s,staff st
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pn.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(CONCAT(',', st.PROFILE_ID, ',') IN pn.PUBLISHED_PROFILES)>0)
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC",
			[
				'CREATED_AT' => 'ProperDate',
				'CONTENT' => 'makeTextarea',
				'FILE_ATTACHED' => 'makeFileAttached',
			] );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				[],
				[],
				$portal_LO_options
			);
		}

		//FJ Portal Polls
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.CREATED_AT) AS CREATED_AT,CONCAT('<b>', pp.TITLE, '</b>') AS TITLE,'options' AS OPTIONS,pp.ID
		FROM portal_polls pp,schools s,staff st
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pp.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(CONCAT(',', st.PROFILE_ID, ',') IN pp.PUBLISHED_PROFILES)>0)
		AND s.ID=pp.SCHOOL_ID AND s.SYEAR=pp.SYEAR
		ORDER BY pp.SORT_ORDER IS NULL,pp.SORT_ORDER,pp.CREATED_AT DESC", [ 'CREATED_AT' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ] );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				[],
				[],
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ce.ID,ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE AS SCHOOL_DATE,ce.SCHOOL_DATE AS DAY,s.TITLE AS SCHOOL
		FROM calendar_events ce,schools s,staff st
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE
		AND (CURRENT_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '11 DAY' : "'11 DAY'" ) . ")
		AND ce.SYEAR='" . UserSyear() . "'
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(CONCAT(',', ce.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
		AND s.ID=ce.SCHOOL_ID
		AND s.SYEAR=ce.SYEAR
		ORDER BY ce.SCHOOL_DATE,s.TITLE", [
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		] );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				[],
				[],
				$portal_LO_options
			);
		}

		if ( Preferences( 'HIDE_ALERTS' ) != 'Y'
			&& AllowEdit( 'School_Setup/Rollover.php' ) )
		{
			// Add Do Rollover warning when School Year has ended.
			$do_rollover = DBGetOne( "SELECT 1 AS DO_ROLLOVER
				FROM school_marking_periods
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND MP='FY'
				AND END_DATE<'" . DBDate() . "'
				AND NOT EXISTS(SELECT 1
					FROM school_marking_periods
					WHERE SYEAR='" . ( UserSyear() + 1 ) . "')" );

			if ( $do_rollover )
			{
				$do_rollover_warning = [
					sprintf(
						_( 'The school year has ended. It is time to proceed to %s.' ),
						'<a href="Modules.php?modname=School_Setup/Rollover.php">' . _( 'Rollover' ) . '</a>'
					)
				];

				echo ErrorMessage( $do_rollover_warning, 'warning' );
			}
		}

		if ( Preferences( 'HIDE_ALERTS' ) != 'Y' )
		{
			// Warn if missing attendances.
			// Fix PostgreSQL error invalid ORDER BY, only result column names can be used
			// Do not use ORDER BY SORT_ORDER IS NULL,SORT_ORDER (nulls last) in UNION.
			$categories_RET = DBGet( "SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION
			SELECT ID,TITLE,1,SORT_ORDER
			FROM attendance_code_categories
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY 3,SORT_ORDER" );

			foreach ( (array) $categories_RET as $category )
			{
				if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
				{
					// FJ days numbered.
					// FJ multiple school periods for a course period.
					// @since 10.4 SQL performance: use NOT EXISTS instead of NOT IN + LIMIT 1000.
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,s.TITLE AS SCHOOL,
					acc.SCHOOL_DATE,cp.TITLE,'" . $category['ID'] . "' AS CATEGORY_ID,sp.PERIOD_ID
					FROM attendance_calendar acc,course_periods cp,school_periods sp,schools s,
					staff st,course_period_school_periods cpsp
					WHERE EXISTS(SELECT 1
						FROM schedule se
						WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
						AND se.SYEAR='" . UserSyear() . "'
						AND acc.SCHOOL_DATE>=se.START_DATE
						AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE)
						LIMIT 1)
					AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND acc.MINUTES>0
					AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
					AND (st.SCHOOLS IS NULL OR position(CONCAT(',', acc.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
					AND cp.SYEAR='" . UserSyear() . "'
					AND cp.CALENDAR_ID=acc.CALENDAR_ID
					AND acc.SCHOOL_DATE<'" . DBDate() . "'
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP<>'PRO') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
						(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
						FROM attendance_calendar
						WHERE SCHOOL_DATE<=acc.SCHOOL_DATE
						AND SCHOOL_DATE>=(SELECT START_DATE
							FROM school_marking_periods
							WHERE START_DATE<=acc.SCHOOL_DATE
							AND END_DATE>=acc.SCHOOL_DATE
							AND MP='QTR'
							AND SCHOOL_ID=acc.SCHOOL_ID
							AND SYEAR=acc.SYEAR)
						AND CALENDAR_ID=cp.CALENDAR_ID)
						" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
						" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))
					AND NOT EXISTS(SELECT 1
						FROM attendance_completed ac
						WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE
						AND ac.STAFF_ID=cp.TEACHER_ID
						AND ac.PERIOD_ID=cpsp.PERIOD_ID
						AND TABLE_NAME='" . (int) $category['ID'] . "')
					AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
					AND s.ID=acc.SCHOOL_ID
					AND s.SYEAR=acc.SYEAR
					ORDER BY cp.TITLE,acc.SCHOOL_DATE
					LIMIT 1000", [ 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ], [ 'COURSE_PERIOD_ID' ] );
				}
				else
				{
					// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
					// @since 10.4 SQL performance: use NOT EXISTS instead of NOT IN + LIMIT 1000.
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,s.TITLE AS SCHOOL,
					acc.SCHOOL_DATE,cp.TITLE,'" . $category['ID'] . "' AS CATEGORY_ID,sp.PERIOD_ID
					FROM attendance_calendar acc,course_periods cp,school_periods sp,schools s,
					staff st, course_period_school_periods cpsp
					WHERE EXISTS(SELECT 1
						FROM schedule se
						WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
						AND se.SYEAR='" . UserSyear() . "'
						AND acc.SCHOOL_DATE>=se.START_DATE
						AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE)
						LIMIT 1)
					AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND acc.MINUTES>0
					AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
					AND (st.SCHOOLS IS NULL OR position(CONCAT(',', acc.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
					AND cp.SCHOOL_ID=acc.SCHOOL_ID
					AND cp.SYEAR='" . UserSyear() . "'
					AND cp.CALENDAR_ID=acc.CALENDAR_ID
					AND acc.SCHOOL_DATE<'" . DBDate() . "'
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP<>'PRO') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM " .
					( $DatabaseType === 'mysql' ?
						"DAYOFWEEK(acc.SCHOOL_DATE)" :
						"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
					" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))
					AND NOT EXISTS(SELECT 1
						FROM attendance_completed ac
						WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE
						AND ac.STAFF_ID=cp.TEACHER_ID
						AND ac.PERIOD_ID=cpsp.PERIOD_ID
						AND TABLE_NAME='" . (int) $category['ID'] . "')
					AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
					AND s.ID=acc.SCHOOL_ID
					AND s.SYEAR=acc.SYEAR
					ORDER BY cp.TITLE,acc.SCHOOL_DATE
					LIMIT 1000", [ 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ], [ 'COURSE_PERIOD_ID' ] );
				}

				if ( $missing_attendance_RET )
				{
					echo ErrorMessage( [ _( 'Teachers have missing attendance data' ) ], 'warning' );

					ListOutput(
						$missing_attendance_RET,
						$missing_attendance_LO_columns,
						'Course Period with missing attendance data',
						'Course Periods with missing attendance data',
						[],
						[ 'COURSE_PERIOD_ID' ],
						$portal_LO_options
					);
				}
			}
		}

		if ( $RosarioModules['Food_Service']
			&& Preferences( 'HIDE_ALERTS' ) !== 'Y' )
		{
			$FS_config = ProgramConfig( 'food_service' );

			// warn if negative food service balance
			$staff = DBGet( "SELECT (SELECT STATUS
					FROM food_service_staff_accounts
					WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
				(SELECT BALANCE
					FROM food_service_staff_accounts
					WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
				FROM staff s
				WHERE s.STAFF_ID='" . User( 'STAFF_ID' ) . "'" );

			$staff = $staff[1];

			if ( $staff['BALANCE']
				&& $staff['BALANCE'] < 0 )
			{
				echo ErrorMessage(
					[ sprintf( _( 'You have a <b>negative</b> food service balance of <span style="color:red">%s</span>' ), $staff['BALANCE'] ) ],
					'warning'
				);
			}

			// warn if students with food service balances below minimum
			$extra = [];
			$extra['SELECT'] = ',fssa.STATUS,fsa.BALANCE';
			$extra['FROM'] = ',food_service_accounts fsa,food_service_student_accounts fssa';
			$extra['WHERE'] = " AND fssa.STUDENT_ID=s.STUDENT_ID
				AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID
				AND fssa.STATUS IS NULL
				AND fsa.BALANCE<'" . (float) $FS_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'] . "'";

			$_REQUEST['_search_all_schools'] = 'Y';

			$RET = GetStuList( $extra );

			if ( $RET )
			{
				echo ErrorMessage( [ sprintf( _( 'Some students have food service balances below %1.2f' ), $FS_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'] ) ], 'warning' );

				ListOutput(
					$RET,
					[
						'FULL_NAME' => _( 'Student' ),
						'GRADE_ID' => _( 'Grade Level' ),
						'BALANCE' => _( 'Balance' ),
					],
					'Student',
					'Students',
					[],
					[],
					$portal_LO_options
				);
			}
		}

		break;

	case 'teacher':
		require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
		//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,CONCAT('<b>', pn.TITLE, '</b>') AS TITLE,pn.CONTENT FROM portal_notes pn,schools s,staff st WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pn.SCHOOL_ID, ',') IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(CONCAT(',', st.PROFILE_ID, ',') IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC",array('CREATED_AT' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,CONCAT('<b>', pn.TITLE, '</b>') AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM portal_notes pn,schools s,staff st
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pn.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL
		AND position(CONCAT(',', st.PROFILE_ID, ',') IN pn.PUBLISHED_PROFILES)>0)
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC", [ 'CREATED_AT' => 'ProperDate', 'CONTENT' => 'makeTextarea', 'FILE_ATTACHED' => 'makeFileAttached' ] );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				[],
				[],
				$portal_LO_options
			);
		}

		// FJ Portal Polls.
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.CREATED_AT) AS CREATED_AT,CONCAT('<b>', pp.TITLE, '</b>') AS TITLE,'options' AS OPTIONS,pp.ID
		FROM portal_polls pp,schools s,staff st
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pp.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL
		AND position(CONCAT(',', st.PROFILE_ID, ',') IN pp.PUBLISHED_PROFILES)>0)
		AND s.ID=pp.SCHOOL_ID
		AND s.SYEAR=pp.SYEAR
		ORDER BY pp.SORT_ORDER IS NULL,pp.SORT_ORDER,pp.CREATED_AT DESC", [ 'CREATED_AT' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ] );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				[],
				[],
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ce.ID,ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE,ce.SCHOOL_DATE AS DAY,s.TITLE AS SCHOOL
		FROM calendar_events ce,schools s
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE
		AND (CURRENT_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '11 DAY' : "'11 DAY'" ) . ")
		AND ce.SYEAR='" . UserSyear() . "'
		AND position(CONCAT(',', ce.SCHOOL_ID, ',') IN (SELECT SCHOOLS FROM staff WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'))>0
		AND s.ID=ce.SCHOOL_ID
		AND s.SYEAR=ce.SYEAR
		ORDER BY ce.SCHOOL_DATE,s.TITLE", [
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		] );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				[],
				[],
				$portal_LO_options
			);
		}

		require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

		// FJ Portal Assignments.
		$assignments_RET = DBGet( "SELECT a.ASSIGNMENT_ID,a.TITLE AS ASSIGNMENT_TITLE,
			a.DUE_DATE,a.DUE_DATE AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,
			c.TITLE AS COURSE,a.MARKING_PERIOD_ID,
			(SELECT cp.COURSE_PERIOD_ID FROM course_periods cp
				WHERE cp.COURSE_ID=c.COURSE_ID
				AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
				LIMIT 1) AS COURSE_PERIOD_ID
		FROM gradebook_assignments a,courses c,school_marking_periods mp
		WHERE (a.COURSE_ID=c.COURSE_ID
		OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM course_periods cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
		AND a.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
		AND a.DUE_DATE>=CURRENT_DATE
		AND mp.MARKING_PERIOD_ID=a.MARKING_PERIOD_ID
		AND mp.SYEAR='" . UserSyear() . "'
		ORDER BY a.DUE_DATE,a.TITLE",
			[
				'DUE_DATE' => 'ProperDate',
				/*'DAY' => '_eventDay',*/
				'ASSIGNED_DATE' => 'ProperDate',
				'ASSIGNMENT_TITLE' => 'MakeAssignmentTitle',
			] );

		if ( $assignments_RET
			&& $RosarioModules['Grades']
			&& AllowUse( 'Grades/Assignments.php' ) )
		{
			ListOutput(
				$assignments_RET,
				$assignments_LO_columns,
				'Upcoming Assignment',
				'Upcoming Assignments',
				[],
				[],
				$portal_LO_options
			);
		}

		if ( Preferences( 'HIDE_ALERTS' ) != 'Y' )
		{
			// Warn if missing attendances.
			// Fix PostgreSQL error invalid ORDER BY, only result column names can be used
			// Do not use ORDER BY SORT_ORDER IS NULL,SORT_ORDER (nulls last) in UNION.
			$categories_RET = DBGet( "SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION
				SELECT ID,TITLE,1,SORT_ORDER
				FROM attendance_code_categories
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY 3,SORT_ORDER" );

			foreach ( (array) $categories_RET as $category )
			{
				if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
				{
					// FJ days numbered.
					// FJ multiple school periods for a course period.
					// @since 6.9 Add Secondary Teacher.
					// @since 10.4 SQL performance: use NOT EXISTS instead of NOT IN + LIMIT 1000.
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,acc.SCHOOL_DATE,
					cp.TITLE,'" . $category['ID'] . "' AS CATEGORY_ID,sp.PERIOD_ID
					FROM attendance_calendar acc,course_periods cp,school_periods sp,
					course_period_school_periods cpsp
					WHERE EXISTS(SELECT 1
						FROM schedule se
						WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
						AND se.SYEAR='" . UserSyear() . "'
						AND acc.SCHOOL_DATE>=se.START_DATE
						AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE)
						LIMIT 1)
					AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND acc.MINUTES>0
					AND cp.SYEAR='" . UserSyear() . "'
					AND acc.SCHOOL_DATE<'" . DBDate() . "'
					AND cp.CALENDAR_ID=acc.CALENDAR_ID
					AND (cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
						OR SECONDARY_TEACHER_ID='" . User( 'STAFF_ID' ) . "')
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP<>'PRO') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
						(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
						FROM attendance_calendar
						WHERE SCHOOL_DATE<=acc.SCHOOL_DATE
						AND SCHOOL_DATE>=(SELECT START_DATE
							FROM school_marking_periods
							WHERE START_DATE<=acc.SCHOOL_DATE
							AND END_DATE>=acc.SCHOOL_DATE
							AND MP='QTR'
							AND SCHOOL_ID=acc.SCHOOL_ID
							AND SYEAR=acc.SYEAR)
						AND CALENDAR_ID=acc.CALENDAR_ID)
						" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
						" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))
					AND NOT EXISTS(SELECT 1
						FROM attendance_completed ac
						WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE
						AND ac.STAFF_ID=cp.TEACHER_ID
						AND ac.PERIOD_ID=cpsp.PERIOD_ID
						AND TABLE_NAME='" . (int) $category['ID'] . "')
					AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
					ORDER BY cp.TITLE,acc.SCHOOL_DATE
					LIMIT 1000", [ 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ], [ 'COURSE_PERIOD_ID' ] );
				}
				else
				{
					// @since 6.9 Add Secondary Teacher.
					// @since 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
					// @since 10.4 SQL performance: use NOT EXISTS instead of NOT IN + LIMIT 1000.
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,acc.SCHOOL_DATE,
					cp.TITLE,'" . $category['ID'] . "' AS CATEGORY_ID,sp.PERIOD_ID
					FROM attendance_calendar acc,course_periods cp,school_periods sp,
					course_period_school_periods cpsp
					WHERE EXISTS(SELECT 1
						FROM schedule se
						WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
						AND se.SYEAR='" . UserSyear() . "'
						AND acc.SCHOOL_DATE>=se.START_DATE
						AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE)
						LIMIT 1)
					AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND acc.MINUTES>0
					AND cp.SYEAR='" . UserSyear() . "'
					AND acc.SCHOOL_DATE<'" . DBDate() . "'
					AND cp.CALENDAR_ID=acc.CALENDAR_ID
					AND (cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
						OR SECONDARY_TEACHER_ID='" . User( 'STAFF_ID' ) . "')
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP<>'PRO') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM " .
					( $DatabaseType === 'mysql' ?
						"DAYOFWEEK(acc.SCHOOL_DATE)" :
						"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
					" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))
					AND NOT EXISTS(SELECT 1
						FROM attendance_completed ac
						WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE
						AND ac.STAFF_ID=cp.TEACHER_ID
						AND ac.PERIOD_ID=cpsp.PERIOD_ID
						AND TABLE_NAME='" . (int) $category['ID'] . "')
					AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
					ORDER BY cp.TITLE,acc.SCHOOL_DATE
					LIMIT 1000", [ 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ], [ 'COURSE_PERIOD_ID' ] );
				}

				if ( $missing_attendance_RET )
				{
					echo ErrorMessage( [ _( 'You have missing attendance data' ) ], 'warning' );

					ListOutput(
						$missing_attendance_RET,
						$missing_attendance_LO_columns,
						'Course Period with missing attendance data',
						'Course Periods with missing attendance data',
						[],
						[ 'COURSE_PERIOD_ID' ],
						$portal_LO_options
					);
				}
			}
		}

		if ( $RosarioModules['Food_Service'] && Preferences( 'HIDE_ALERTS' ) != 'Y' )
		{
			// warn if negative food service balance
			$staff = DBGet( "SELECT (SELECT STATUS FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
				(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
				FROM staff s
				WHERE s.STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
			$staff = $staff[1];

			if ( $staff['BALANCE'] && $staff['BALANCE'] < 0 )
			{
				echo ErrorMessage( [ sprintf( _( 'You have a <b>negative</b> food service balance of <span style="color:red">%s</span>' ), $staff['BALANCE'] ) ], 'warning' );
			}
		}

		break;

	case 'parent':
		require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
		// FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL.
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,pn.TITLE,pn.CONTENT FROM portal_notes pn,schools s,staff st WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM students_join_users sju, student_enrollment se WHERE sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pn.SCHOOL_ID, ',') IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(CONCAT(',', st.PROFILE_ID, ',') IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC",array('CREATED_AT' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM portal_notes pn,schools s,staff st
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM students_join_users sju, student_enrollment se WHERE sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL))
		AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pn.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(CONCAT(',', st.PROFILE_ID, ',') IN pn.PUBLISHED_PROFILES)>0)
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC", [ 'CREATED_AT' => 'ProperDate', 'CONTENT' => 'makeTextarea', 'FILE_ATTACHED' => 'makeFileAttached' ] );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				[],
				[],
				$portal_LO_options
			);
		}

		// FJ Portal Polls.
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.CREATED_AT) AS CREATED_AT,CONCAT('<b>', pp.TITLE, '</b>') AS TITLE,'options' AS OPTIONS,pp.ID
		FROM portal_polls pp,schools s,staff st
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(CONCAT(',', pp.SCHOOL_ID, ',') IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL
		AND position(CONCAT(',', st.PROFILE_ID, ',') IN pp.PUBLISHED_PROFILES)>0)
		AND s.ID=pp.SCHOOL_ID
		AND s.SYEAR=pp.SYEAR
		ORDER BY pp.SORT_ORDER IS NULL,pp.SORT_ORDER,pp.CREATED_AT DESC", [ 'CREATED_AT' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ] );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				[],
				[],
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ce.ID,ce.TITLE,ce.SCHOOL_DATE,ce.SCHOOL_DATE AS DAY,ce.DESCRIPTION,s.TITLE AS SCHOOL
		FROM calendar_events ce,schools s
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE
		AND (CURRENT_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '11 DAY' : "'11 DAY'" ) . ")
		AND ce.SYEAR='" . UserSyear() . "'
		AND ce.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM students_join_users sju, student_enrollment se WHERE sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND se.SYEAR=ce.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL))
		AND s.ID=ce.SCHOOL_ID
		AND s.SYEAR=ce.SYEAR
		ORDER BY ce.SCHOOL_DATE,s.TITLE", [
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		] );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				[],
				[],
				$portal_LO_options
			);
		}

		// FJ Portal Assignments.

		if ( $RosarioModules['Grades']
			&& AllowUse( 'Grades/StudentAssignments.php' ) )
		{
			require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

			$assignments_RET = DBGet( "SELECT a.ASSIGNMENT_ID,a.TITLE AS ASSIGNMENT_TITLE,
				a.DUE_DATE,a.DUE_DATE AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,
				c.TITLE AS COURSE,a.SUBMISSION,a.MARKING_PERIOD_ID,
				(SELECT 1
				FROM student_assignments sa
				WHERE a.ASSIGNMENT_ID=sa.ASSIGNMENT_ID
				AND sa.STUDENT_ID=s.STUDENT_ID) AS SUBMITTED
			FROM gradebook_assignments a,schedule s,courses c,school_marking_periods mp
			WHERE (a.COURSE_ID=c.COURSE_ID
			OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM course_periods cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
			AND (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
			AND s.STUDENT_ID='" . UserStudentID() . "'
			AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
			AND s.START_DATE<=CURRENT_DATE
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
			AND a.DUE_DATE>=CURRENT_DATE
			AND mp.MARKING_PERIOD_ID=a.MARKING_PERIOD_ID
			AND mp.SYEAR='" . UserSyear() . "'
			ORDER BY a.DUE_DATE,a.TITLE",
				[
					'DUE_DATE' => 'MakeAssignmentDueDate',
					/*'DAY' => '_eventDay',*/
					/*'DESCRIPTION' => 'makeTextarea',*/
					'STAFF_ID' => 'GetTeacher',
					'SUBMITTED' => 'MakeAssignmentSubmitted',
					'ASSIGNMENT_TITLE' => 'MakeAssignmentTitle',
				] );

			if ( $assignments_RET )
			{
				ListOutput(
					$assignments_RET,
					$assignments_LO_columns,
					'Upcoming Assignment',
					'Upcoming Assignments',
					[],
					[],
					$portal_LO_options
				);
			}
		}

		if ( $RosarioModules['Food_Service']
			&& Preferences( 'HIDE_ALERTS' ) !== 'Y' )
		{
			$FS_config = ProgramConfig( 'food_service' );

			// Warn if students with low food service balances.
			$extra['SELECT'] = ',fssa.STATUS,fsa.ACCOUNT_ID,fsa.BALANCE AS BALANCE,' .
				(float) $FS_config['FOOD_SERVICE_BALANCE_TARGET'][1]['VALUE'] . '-fsa.BALANCE AS DEPOSIT';
			$extra['FROM'] = ',food_service_accounts fsa,food_service_student_accounts fssa';
			$extra['WHERE'] = " AND fssa.STUDENT_ID=s.STUDENT_ID
				AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID
				AND fssa.STATUS IS NULL
				AND fsa.BALANCE<'" . (float) $FS_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'] . "'";
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );

			$RET = GetStuList( $extra );

			if ( $RET )
			{
				echo ErrorMessage(
					[ sprintf( _( 'You have students with food service balance below %1.2f - please deposit at least the Minimum Deposit into you children\'s accounts.' ), $FS_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'] ) ],
					'warning'
				);

				ListOutput(
					$RET,
					[
						'FULL_NAME' => _( 'Student' ),
						'GRADE_ID' => _( 'Grade Level' ),
						'ACCOUNT_ID' => _( 'Account ID' ),
						'BALANCE' => _( 'Balance' ),
						'DEPOSIT' => _( 'Minimum Deposit' ),
					],
					'Student',
					'Students',
					[],
					[],
					$portal_LO_options
				);
			}

			// Warn if negative food service balance.
			$staff = DBGet( "SELECT (SELECT STATUS FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
				(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
				FROM staff s
				WHERE s.STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
			$staff = $staff[1];

			if ( $staff['BALANCE'] && $staff['BALANCE'] < 0 )
			{
				echo ErrorMessage( [ sprintf( _( 'You have a <b>negative</b> food service balance of <span style="color:red">%s</span>' ), Currency( $staff['BALANCE'] ) ) ], 'warning' );
			}
		}

		break;

	case 'student':
		require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
		// FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL.
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,pn.TITLE,pn.CONTENT FROM portal_notes pn,schools s WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND pn.SCHOOL_ID='".UserSchool()."' AND  position(',0,' IN pn.PUBLISHED_PROFILES)>0 AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC",array('CREATED_AT' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.CREATED_AT) AS CREATED_AT,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM portal_notes pn,schools s
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND pn.SCHOOL_ID='" . UserSchool() . "'
		AND position(',0,' IN pn.PUBLISHED_PROFILES)>0
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER IS NULL,pn.SORT_ORDER,pn.CREATED_AT DESC", [ 'CREATED_AT' => 'ProperDate', 'CONTENT' => 'makeTextarea', 'FILE_ATTACHED' => 'makeFileAttached' ] );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				[],
				[],
				$portal_LO_options
			);
		}

		// FJ Portal Polls.
		// FJ Portal Polls add students teacher.
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.CREATED_AT) AS CREATED_AT,pp.TITLE,'options' AS OPTIONS,pp.ID
		FROM portal_polls pp,schools s
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND pp.SCHOOL_ID='" . UserSchool() . "'
		AND position(',0,' IN pp.PUBLISHED_PROFILES)>0
		AND s.ID=pp.SCHOOL_ID
		AND s.SYEAR=pp.SYEAR
		AND (pp.STUDENTS_TEACHER_ID IS NULL OR pp.STUDENTS_TEACHER_ID IN (SELECT cp.TEACHER_ID FROM schedule sch, course_periods cp WHERE sch.SYEAR='" . UserSyear() . "' AND sch.SCHOOL_ID='" . UserSchool() . "' AND sch.STUDENT_ID='" . UserStudentID() . "' AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID))
		ORDER BY pp.SORT_ORDER IS NULL,pp.SORT_ORDER,pp.CREATED_AT DESC", [ 'CREATED_AT' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ] );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				[],
				[],
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ID,TITLE,SCHOOL_DATE,SCHOOL_DATE AS DAY,DESCRIPTION
		FROM calendar_events
		WHERE SCHOOL_DATE BETWEEN CURRENT_DATE
		AND (CURRENT_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '11 DAY' : "'11 DAY'" ) . ")
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'", [
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		] );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				[],
				[],
				$portal_LO_options
			);
		}

		// FJ Portal Assignments.

		if ( $RosarioModules['Grades']
			&& AllowUse( 'Grades/StudentAssignments.php' ) )
		{
			require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

			$assignments_RET = DBGet( "SELECT a.ASSIGNMENT_ID,a.TITLE AS ASSIGNMENT_TITLE,
				a.DUE_DATE,a.DUE_DATE AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,
				c.TITLE AS COURSE,a.SUBMISSION,a.MARKING_PERIOD_ID,
				(SELECT 1
				FROM student_assignments sa
				WHERE a.ASSIGNMENT_ID=sa.ASSIGNMENT_ID
				AND sa.STUDENT_ID=s.STUDENT_ID) AS SUBMITTED
			FROM gradebook_assignments a,schedule s,courses c,school_marking_periods mp
			WHERE (a.COURSE_ID=c.COURSE_ID
			OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM course_periods cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
			AND (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
			AND s.STUDENT_ID='" . UserStudentID() . "'
			AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
			AND s.START_DATE<=CURRENT_DATE
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
			AND a.DUE_DATE>=CURRENT_DATE
			AND mp.MARKING_PERIOD_ID=a.MARKING_PERIOD_ID
			AND mp.SYEAR='" . UserSyear() . "'
			ORDER BY a.DUE_DATE,a.TITLE",
				[
					'DUE_DATE' => 'MakeAssignmentDueDate',
					/*'DAY' => '_eventDay',*/
					/*'DESCRIPTION' => 'makeTextarea',*/
					'STAFF_ID' => 'GetTeacher',
					'SUBMITTED' => 'MakeAssignmentSubmitted',
					'ASSIGNMENT_TITLE' => 'MakeAssignmentTitle',
				] );

			if ( $assignments_RET )
			{
				ListOutput(
					$assignments_RET,
					$assignments_LO_columns,
					'Upcoming Assignment',
					'Upcoming Assignments',
					[],
					[],
					$portal_LO_options
				);
			}
		}

		break;
}

/**
 * Check PHP min version, safe mode, and required functions.
 *
 * @return array Warning messages for failed PHP checks.
 */
function PHPCheck()
{
	$ret = [];

	if ( version_compare( PHP_VERSION, '5.5.9' ) == -1 )
	{
		$ret[] = 'RosarioSIS requires PHP 5.5.9 to run, your version is : ' . PHP_VERSION;
	}

	if ( (bool) ini_get( 'safe_mode' ) )
	{
		$ret[] = 'safe_mode is set to On in your PHP configuration.';
	}

	if ( mb_strpos( ini_get( 'disable_functions' ), 'passthru' ) !== false )
	{
		$ret[] = 'passthru is disabled in your PHP configuration.';
	}

	return $ret;
}

/**
 * Get day of week (textual) from Date
 *
 * @since 9.2.1 SQL use PHP strftime_compat() instead of SQL to_char() for MySQL compatibility
 *
 * DBGet() callback
 *
 * @param string $date   Date.
 * @param string $column Column "DAY".
 *
 * @return string Day of week (textual)
 */
function _eventDay( $date, $column )
{
	return strftime_compat( '%A', $date );
}

/**
 * Make Take Attendance link
 *
 * DBGet callback function.
 *
 * @since 3.6
 *
 * @param  string $value  School date.
 * @param  string $column 'SCHOOL_DATE'.
 * @return string Proper school date + take attendance link.
 */
function _makeTakeAttendanceLink( $value, $column )
{
	global $THIS_RET;

	if ( ! $value )
	{
		return $value;
	}

	$proper_date = ProperDate( $value );

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Printing PDF or exporting list.
		return $proper_date;
	}

	$modname = 'Attendance/TakeAttendance.php';

	if ( User( 'PROFILE' ) === 'admin' )
	{
		$modname = 'Users/TeacherPrograms.php&include=' . $modname;

		if ( ! AllowEdit( $modname ) )
		{
			// Admin cannot take attendance.
			return $proper_date;
		}
	}
	elseif ( ! AllowUse( $modname ) )
	{
		// Teacher cannot take attendance?
		return $proper_date;
	}

	// Attendance category / table.
	$table = $THIS_RET['CATEGORY_ID'];

	// Redirect to TakeAttendance from Portal,
	// in case current School, SYear, MP, CP or Period are wrong.
	$take_attendance_redirect_url = 'Modules.php?modname=misc/Portal.php&modfunc=redirect_take_attendance';

	$take_attendance_redirect_url .= '&school_date=' . $THIS_RET['SCHOOL_DATE'] . '&table=' . $table;

	// Right course period & school period.
	$take_attendance_redirect_url .= '&period=' . $THIS_RET['COURSE_PERIOD_ID'];

	$take_attendance_redirect_url .= '&school_period=' . $THIS_RET['PERIOD_ID'];

	return '<a href="' . URLEscape( $take_attendance_redirect_url ) . '">' . $proper_date . '</a>';
}

/**
 * Redirect to Take Attendance program.
 * Redirect from Portal,
 * in case current School, SYear, MP, CP or Period are wrong.
 *
 * @see _makeTakeAttendanceLink
 * @since 3.6
 *
 * @return bool False if hack or wrong parameters, else 302 redirection.
 */
function _redirectTakeAttendance()
{
	if ( empty( $_REQUEST['period'] )
		|| empty( $_REQUEST['school_period'] )
		|| ! isset( $_REQUEST['table'] )
		|| empty( $_REQUEST['school_date'] )
		|| ! VerifyDate( $_REQUEST['school_date'] ) )
	{
		// Not enough parameters to redirect.
		return false;
	}

	// Get Course Period info.
	// @since 6.9 Add Secondary Teacher.
	$cp_RET = DBGet( "SELECT SCHOOL_ID,TEACHER_ID,SECONDARY_TEACHER_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['period'] . "'
		AND SYEAR='" . UserSyear() . "'
		LIMIT 1" );

	if ( ! isset( $cp_RET[1]['SCHOOL_ID'] ) )
	{
		// Course Period not found.
		return false;
	}

	$modname = 'Attendance/TakeAttendance.php';

	if ( User( 'PROFILE' ) === 'admin' )
	{
		$modname = 'Users/TeacherPrograms.php&include=' . $modname;

		if ( ! AllowEdit( $modname ) )
		{
			// Admin cannot take attendance.
			return false;
		}

		// Admin: Teacher Programs.
		$modname .= '&staff_id=' . $cp_RET[1]['TEACHER_ID'];
	}
	elseif ( ! AllowUse( $modname )
		|| ( User( 'STAFF_ID' ) !== $cp_RET[1]['TEACHER_ID']
			&& User( 'STAFF_ID' ) !== $cp_RET[1]['SECONDARY_TEACHER_ID'] ) )
	{
		// Teacher cannot take attendance?
		// Teachers cannot take others attendance?
		return false;
	}

	$modname .= '&period=' . $_REQUEST['period'] . '&school_period=' . $_REQUEST['school_period'];

	if ( UserSchool() != $cp_RET[1]['SCHOOL_ID'] )
	{
		if ( User( 'SCHOOLS' )
			&& mb_strpos( User( 'SCHOOLS' ), ',' . $cp_RET[1]['SCHOOL_ID'] . ',' ) === false )
		{
			// User does not belong to this school...
			return false;
		}

		// Update current School.
		$_SESSION['UserSchool'] = $cp_RET[1]['SCHOOL_ID'];
	}

	$cp_mp_id = GetCurrentMP( 'QTR', $_REQUEST['school_date'], false );

	if ( UserMP() != $cp_mp_id )
	{
		// Update current MP.
		$_SESSION['UserMP'] = $cp_mp_id;
	}

	// Get month, day & year from School Date.
	$date = ExplodeDate( $_REQUEST['school_date'] );

	$take_attendance_url = 'Modules.php?modname=' . $modname . '&table=' . $_REQUEST['table'] .
		'&month_date=' . $date['month'] . '&day_date=' . $date['day'] . '&year_date=' . $date['year'];

	header( 'Location: ' . URLEscape( $take_attendance_url ) );

	// echo '<script>ajaxLink(' . json_encode( $take_attendance_url ) . ');</script>';

	// Warehouse( 'footer' );

	return die();
}
